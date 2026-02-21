<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Tenant;
use App\Models\LandingPageMetric;
use App\Models\LandingPage;
use App\Models\LandingPageSection;
use App\Models\Transaction;
use App\Models\SubscriptionPlan;
use App\Services\BrevoService;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminController extends Controller
{
    protected $brevo;

    public function __construct(BrevoService $brevo)
    {
        $this->brevo = $brevo;
    }

    public function index()
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403, 'Acesso restrito ao CEO.');
        }

        // 1. KPIs Rápidos
        $mrr = DB::table('tenants')
                 ->join('subscription_plans', 'tenants.plan_id', '=', 'subscription_plans.id')
                 ->where('tenants.subscription_status', 'active')
                 ->sum('subscription_plans.price');

        $newClientsMonth = Tenant::whereMonth('created_at', Carbon::now()->month)
                                 ->whereYear('created_at', Carbon::now()->year)
                                 ->count();

        $totalActive = Tenant::where('subscription_status', 'active')->count();
        $canceledMonth = Tenant::where('subscription_status', 'canceled')
                               ->whereMonth('updated_at', Carbon::now()->month)
                               ->count();
        $churnRate = $totalActive > 0 ? ($canceledMonth / $totalActive) * 100 : 0;

        // 2. Gráfico de Crescimento (Últimos 6 meses)
        $growthLabels = [];
        $growthValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $growthLabels[] = $month->format('M/Y');
            
            // Simulação de faturamento por enquanto baseada na criação de tenants ativos
            $val = DB::table('tenants')
                     ->join('subscription_plans', 'tenants.plan_id', '=', 'subscription_plans.id')
                     ->where('tenants.subscription_status', 'active')
                     ->whereMonth('tenants.created_at', '<=', $month->month)
                     ->whereYear('tenants.created_at', '<=', $month->year)
                     ->sum('subscription_plans.price');
            $growthValues[] = $val;
        }

        // 3. Risco de Churn (Últimos 5 que não logam há 10 dias)
        $churnRiskUsers = User::where('role', 'manager')
                              ->where(function($q) {
                                  $q->where('last_login_at', '<', Carbon::now()->subDays(10))
                                    ->orWhereNull('last_login_at');
                              })
                              ->with('tenant')
                              ->orderBy('last_login_at', 'asc')
                              ->take(5)
                              ->get();

        // 4. Adoção de Features (Lego Builder vs Doações)
        $totalBlocks = LandingPageSection::count();
        // $totalDonations = Transaction::where('category_id', function($query) {
        //      $query->select('id')->from('financial_categories')->where('name', 'LIKE', '%Doação%')->limit(1);
        // })->count();
        $totalDonations = 0; // TODO: Corrigir referência à tabela de categorias que ainda não foi migrada/padronizada

        // 5. Origem dos Leads (Atribuição que criamos)
        $leadSourceData = LandingPageMetric::select('page_key', DB::raw('SUM(registrations) as count'))
                                           ->groupBy('page_key')
                                           ->get();

        // Dados originais que a view usa
        $totalTenants = Tenant::count();
        $totalUsers = User::count();
        $onlineUsers = User::where('last_seen_at', '>=', now()->subMinutes(5))->count();
        $recentTenants = Tenant::leftJoin('subscription_plans', 'tenants.plan_id', '=', 'subscription_plans.id')
                               ->select('tenants.*', 'subscription_plans.name as plan_type')
                               ->orderBy('tenants.created_at', 'desc')
                               ->limit(5)
                               ->get();
        $lpMetrics = LandingPageMetric::select('page_key', 
                                              DB::raw('SUM(views) as total_views'), 
                                              DB::raw('SUM(registrations) as total_registrations'))
                                     ->groupBy('page_key')
                                     ->get();
        
        $planDistribution = DB::table('subscription_plans')
                              ->leftJoin('tenants', 'subscription_plans.id', '=', 'tenants.plan_id')
                              ->select('subscription_plans.name', DB::raw('count(tenants.id) as count'))
                              ->groupBy('subscription_plans.id', 'subscription_plans.name')
                              ->get();

        return view('admin.dashboard', compact(
            'mrr', 'newClientsMonth', 'churnRate', 
            'growthLabels', 'growthValues', 
            'churnRiskUsers', 
            'totalBlocks', 'totalDonations',
            'leadSourceData',
            'totalTenants', 'totalUsers', 'onlineUsers', 'recentTenants', 'lpMetrics', 'planDistribution'
        ));
    }

    public function serverHealth()
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $load = 'N/A';
        $memory = 'N/A';
        $disk = 'N/A';
        $uptime = 'N/A';

        if (function_exists('sys_getloadavg')) {
            $loadavg = sys_getloadavg();
            if ($loadavg) $load = implode(' ', $loadavg);
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $memory = 'Windows Server - Check Task Manager';
            $disk = disk_free_space("C:") / 1024 / 1024 / 1024;
            $disk = number_format($disk, 2) . ' GB Free';
        } else {
            $free = shell_exec('free -m');
            $free = (string)trim($free);
            $free_arr = explode("\n", $free);
            if (isset($free_arr[1])) {
                $mem = array_filter(explode(" ", $free_arr[1]));
                $mem = array_merge($mem);
                $memory = $mem[2] . 'MB / ' . $mem[1] . 'MB Used';
            }
            $disk = shell_exec("df -h | grep '/$' | awk '{print $4}'");
            $disk = trim($disk) . ' Free';
            $uptime = shell_exec('uptime -p');
        }

        return view('admin.health', compact('load', 'memory', 'disk', 'uptime'));
    }

    public function tenants()
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $tenants = Tenant::leftJoin('subscription_plans', 'tenants.plan_id', '=', 'subscription_plans.id')
                         ->select('tenants.*', 'subscription_plans.name as plan_type')
                         ->paginate(20);
        return view('admin.tenants.index', compact('tenants'));
    }

    public function showTenant($id)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $tenant = Tenant::findOrFail($id);
        $user = User::where('tenant_id', $tenant->id)->first(); // Main user
        $plan = DB::table('subscription_plans')->where('id', $tenant->plan_id)->first();

        return view('admin.tenants.show', compact('tenant', 'user', 'plan'));
    }

    public function emailLogs()
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $logs = DB::table('email_logs')
                  ->leftJoin('tenants', 'email_logs.tenant_id', '=', 'tenants.id')
                  ->select('email_logs.*', 'tenants.name as tenant_name')
                  ->orderBy('email_logs.created_at', 'desc')
                  ->paginate(50);

        return view('admin.email_logs', compact('logs'));
    }
    public function suspendTenant($id)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $tenant = Tenant::findOrFail($id);
        $tenant->update(['subscription_status' => 'suspended']);

        return back()->with('success', 'Organização suspensa com sucesso. O acesso foi bloqueado.');
    }

    public function activateTenant($id)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $tenant = Tenant::findOrFail($id);
        $tenant->update(['subscription_status' => 'active']);

        return back()->with('success', 'Organização reativada com sucesso. O acesso foi liberado.');
    }

    public function createTenant()
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $plans = SubscriptionPlan::all();
        return view('admin.tenants.create', compact('plans'));
    }

    public function storeTenant(Request $request)
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'tenant_name' => 'required|string|max:255',
            'plan_id' => 'required|exists:subscription_plans,id',
            'account_type' => 'required|in:ngo_admin,project_manager,client',
            'billing_mode' => 'required|in:courtesy,manual_pay,trial',
        ]);

        try {
            DB::beginTransaction();

            $plan = SubscriptionPlan::find($request->plan_id);

            // 1. Determine Tenant Type and Status
            $tenantType = match($request->account_type) {
                'ngo_admin' => 'ngo',
                'project_manager' => 'business',
                'client' => 'common',
                default => 'common'
            };

            $status = match($request->billing_mode) {
                'courtesy' => 'active',
                'manual_pay' => 'pending',
                'trial' => 'trialing',
                default => 'trialing'
            };

            $trialEndsAt = $request->billing_mode === 'trial' ? now()->addDays(7) : null;

            // 2. Create Tenant
            $tenant = Tenant::create([
                'name' => $request->tenant_name,
                'email' => $request->email,
                'type' => $tenantType,
                'plan_id' => $plan->id,
                'subscription_status' => $status,
                'trial_ends_at' => $trialEndsAt,
                'billing_method' => 'manual', // Indica que foi criado pelo admin
            ]);

            // 3. Create User (Normalized Role)
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => match($request->account_type) {
                    'ngo_admin' => 'ngo',
                    'project_manager' => 'manager',
                    default => $request->account_type
                },
                'status' => 'active',
            ]);

            DB::commit();

            // 4. Send Welcome Email via Brevo
            try {
                $this->brevo->sendManualWelcomeEmail(
                    $user, 
                    $request->password, 
                    $plan->name, 
                    $request->billing_mode
                );
            } catch (\Exception $e) {
                Log::error('Erro ao enviar e-mail de boas-vindas manual: ' . $e->getMessage());
                // Não falhamos a criação se apenas o e-mail falhar.
            }

            $message = "Cliente criado com sucesso!";
            if ($request->billing_mode === 'manual_pay') {
                $checkoutUrl = route('checkout.index', ['plan_id' => $plan->id]);
                // Em um cenário real, poderíamos disparar um e-mail aqui.
                $message .= " Modo de pagamento manual selecionado. O link de checkout é: " . $checkoutUrl;
            }

            return redirect()->route('admin.tenants.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao criar cliente: ' . $e->getMessage())->withInput();
        }
    }
}
