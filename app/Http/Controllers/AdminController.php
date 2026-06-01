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
use Illuminate\Support\Facades\Cache;
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
        if (!auth()->user()->isSuperAdmin()) {
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
        $totalDonations = Transaction::where('type', 'income')
                                     ->where('description', 'LIKE', '%doação%')
                                     ->count();

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

        // 6. Últimas campanhas de e-mail
        $latestCampaigns = \App\Models\EmailCampaign::with('creator')
            ->latest()
            ->limit(5)
            ->get();

        // 7. Jobs falhados
        $failedJobsCount = DB::table('failed_jobs')->count();

        return view('admin.dashboard', compact(
            'mrr', 'newClientsMonth', 'churnRate',
            'growthLabels', 'growthValues',
            'churnRiskUsers',
            'totalBlocks', 'totalDonations',
            'leadSourceData',
            'totalTenants', 'totalUsers', 'onlineUsers', 'recentTenants', 'lpMetrics', 'planDistribution',
            'latestCampaigns',
            'failedJobsCount'
        ));
    }

    public function liveUsers()
    {
        if (!auth()->user()->isSuperAdmin()) abort(403);

        $users = User::where('last_seen_at', '>=', now()->subMinutes(10))
            ->select('id', 'name', 'role', 'tenant_id', 'last_seen_at')
            ->with('tenant:id,name')
            ->orderByDesc('last_seen_at')
            ->limit(20)
            ->get()
            ->map(fn($u) => [
                'id'      => $u->id,
                'name'    => $u->name,
                'role'    => $u->role,
                'tenant'  => $u->tenant?->name ?? 'Plataforma',
                'seen'    => $u->last_seen_at?->diffForHumans(null, true, true) ?? 'N/A',
                'initial' => strtoupper(substr($u->name, 0, 1)),
            ]);

        return response()->json(['count' => $users->count(), 'users' => $users]);
    }

    public function serverHealth()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $isLinux = strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN';

        // ── Load Average ──────────────────────────────────────────────────────
        $loadRaw = function_exists('sys_getloadavg') ? (sys_getloadavg() ?: [0, 0, 0]) : [0, 0, 0];
        $load = [
            '1m'  => number_format($loadRaw[0], 2),
            '5m'  => number_format($loadRaw[1], 2),
            '15m' => number_format($loadRaw[2], 2),
        ];

        // ── Memory (Linux) ────────────────────────────────────────────────────
        $memTotal = $memUsed = $memFree = $memPct = 0;
        if ($isLinux) {
            $freeOut = shell_exec('free -m 2>/dev/null');
            if ($freeOut) {
                $parts = array_values(array_filter(explode(' ', explode("\n", trim($freeOut))[1] ?? '')));
                $memTotal = (int)($parts[1] ?? 0);
                $memUsed  = (int)($parts[2] ?? 0);
                $memFree  = (int)($parts[3] ?? 0);
                $memPct   = $memTotal > 0 ? round($memUsed / $memTotal * 100) : 0;
            }
        }

        // ── Disk ──────────────────────────────────────────────────────────────
        $diskTotal = $diskUsed = $diskFree = $diskPct = 0;
        if ($isLinux) {
            $dfLine = shell_exec("df -BM / 2>/dev/null | tail -1");
            if ($dfLine) {
                $parts = array_values(array_filter(explode(' ', trim($dfLine))));
                $diskTotal = (int)str_replace('M', '', $parts[1] ?? 0);
                $diskUsed  = (int)str_replace('M', '', $parts[2] ?? 0);
                $diskFree  = (int)str_replace('M', '', $parts[3] ?? 0);
                $diskPct   = (int)str_replace('%', '', $parts[4] ?? 0);
            }
        } else {
            $diskFree  = (int)round(disk_free_space("C:") / 1024 / 1024);
            $diskTotal = (int)round(disk_total_space("C:") / 1024 / 1024);
            $diskUsed  = $diskTotal - $diskFree;
            $diskPct   = $diskTotal > 0 ? round($diskUsed / $diskTotal * 100) : 0;
        }

        // ── Uptime ────────────────────────────────────────────────────────────
        $uptime = 'N/A';
        if ($isLinux) {
            $uptime = str_replace('up ', '', trim(shell_exec('uptime -p 2>/dev/null') ?? 'N/A'));
        }

        // ── DB Version ────────────────────────────────────────────────────────
        $dbVersion = 'N/A';
        try { $dbVersion = DB::select('SELECT VERSION() as v')[0]->v ?? 'N/A'; } catch (\Throwable $e) {}

        // ── Redis ─────────────────────────────────────────────────────────────
        $redisOk = false;
        try {
            \Illuminate\Support\Facades\Cache::store('redis')->put('_sa_health', 1, 5);
            $redisOk = \Illuminate\Support\Facades\Cache::store('redis')->get('_sa_health') === 1;
            \Illuminate\Support\Facades\Cache::store('redis')->forget('_sa_health');
        } catch (\Throwable $e) {}

        // ── Security Checks (APIs via SystemSetting — não via .env) ──────────
        $loginFails = 0;
        try {
            $loginFails = DB::table('login_activities')
                ->where('logged_in_at', '>=', now()->subDay())
                ->where('success', false)->count();
        } catch (\Throwable $e) {}

        $iaOk       = (bool) \App\Models\SystemSetting::getValue('deepseek_api_key')
                   || (bool) \App\Models\SystemSetting::getValue('gemini_api_key');
        $emailOk    = (bool) \App\Models\SystemSetting::getValue('brevo_api_key');
        $pagamentoOk= (bool) \App\Models\SystemSetting::getValue('abacatepay_api_key')
                   || (bool) \App\Models\SystemSetting::getValue('pagseguro_token');
        $metaOk     = (bool) \App\Models\SystemSetting::getValue('meta_app_secret');

        $checks = [
            ['label' => 'HTTPS ativo',          'desc' => 'Conexão criptografada SSL/TLS',       'ok' => str_starts_with(config('app.url', ''), 'https')],
            ['label' => 'Debug desligado',       'desc' => 'Erros não exibidos publicamente',     'ok' => !config('app.debug')],
            ['label' => 'APP_KEY configurada',   'desc' => 'Chave de criptografia presente',      'ok' => strlen(config('app.key', '')) > 10],
            ['label' => 'IA configurada',        'desc' => 'DeepSeek ou Gemini no painel admin',  'ok' => $iaOk],
            ['label' => 'E-mail (Brevo)',         'desc' => 'Chave Brevo no painel admin',         'ok' => $emailOk],
            ['label' => 'Pagamento configurado', 'desc' => 'AbacatePay ou PagSeguro ativo',       'ok' => $pagamentoOk],
            ['label' => 'Meta WhatsApp',         'desc' => 'App Secret da Meta configurado',      'ok' => $metaOk],
            ['label' => 'Falhas de login 24h',   'desc' => $loginFails . ' tentativa(s)',         'ok' => $loginFails < 50],
        ];

        $securityScore = count(array_filter(array_column($checks, 'ok')));
        $securityTotal = count($checks);
        $securityPct   = $securityTotal > 0 ? round($securityScore / $securityTotal * 100) : 0;

        // ── Stats reais da plataforma ─────────────────────────────────────────
        $statTenants = $statUsers = $statTransactions = 0;
        $statWppMsgs = $statBeneficiaries = $statOpenTickets = 0;
        try {
            $statTenants       = DB::table('tenants')->count();
            $statUsers         = DB::table('users')->count();
            $statTransactions  = DB::table('transactions')->count();
            $statWppMsgs       = DB::table('whatsapp_messages')->count();
            $statBeneficiaries = DB::table('beneficiaries')->count();
            $statOpenTickets   = DB::table('support_tickets')->where('status', 'open')->count();
        } catch (\Throwable $e) {}

        return view('admin.health', compact(
            'isLinux', 'load',
            'memTotal', 'memUsed', 'memFree', 'memPct',
            'diskTotal', 'diskUsed', 'diskFree', 'diskPct',
            'uptime', 'dbVersion', 'redisOk',
            'checks', 'securityScore', 'securityTotal', 'securityPct',
            'statTenants', 'statUsers', 'statTransactions',
            'statWppMsgs', 'statBeneficiaries', 'statOpenTickets'
        ));
    }

    public function tenants()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $tenants = Tenant::leftJoin('subscription_plans', 'tenants.plan_id', '=', 'subscription_plans.id')
                         ->select('tenants.*', 'subscription_plans.name as plan_type')
                         ->paginate(20);
        return view('admin.tenants.index', compact('tenants'));
    }

    public function showTenant($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $tenant = Tenant::findOrFail($id);
        $user = User::where('tenant_id', $tenant->id)->first(); // Main user
        $plan = DB::table('subscription_plans')->where('id', $tenant->plan_id)->first();

        return view('admin.tenants.show', compact('tenant', 'user', 'plan'));
    }

    public function emailLogs()
    {
        if (!auth()->user()->isSuperAdmin()) {
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
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $tenant = Tenant::findOrFail($id);
        $tenant->update(['subscription_status' => 'suspended']);
        Cache::forget("tenant.{$tenant->id}");

        return back()->with('success', 'Organização suspensa com sucesso. O acesso foi bloqueado.');
    }

    public function activateTenant($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $tenant = Tenant::findOrFail($id);
        $tenant->update(['subscription_status' => 'active']);
        Cache::forget("tenant.{$tenant->id}");

        return back()->with('success', 'Organização reativada com sucesso. O acesso foi liberado.');
    }

    public function destroyTenant($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $tenant = Tenant::findOrFail($id);

        // Só permite deletar contas inativas (pending, canceled, suspended)
        if ($tenant->subscription_status === 'active') {
            return back()->with('error', 'Não é possível deletar uma conta ativa. Suspenda-a primeiro.');
        }

        $tenantName = $tenant->name;

        // Remove usuários e o tenant
        User::where('tenant_id', $tenant->id)->delete();
        $tenant->delete();

        return redirect()->route('admin.tenants.index')
            ->with('success', "Conta \"{$tenantName}\" e todos os seus usuários foram removidos.");
    }

    public function createTenant()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $plans = SubscriptionPlan::all();
        return view('admin.tenants.create', compact('plans'));
    }

    public function storeTenant(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
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
                'ngo_admin'      => 'ngo',
                'project_manager'=> 'business',
                'client'         => 'common',
                default          => 'common'
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
                    'ngo_admin'       => 'ngo',
                    'project_manager' => 'manager',
                    default           => $request->account_type
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
