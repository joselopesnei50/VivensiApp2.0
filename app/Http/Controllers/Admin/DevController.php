<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DevController extends Controller
{
    public function gate()
    {
        if ($redirect = $this->requireVerifiedTwoFactor()) {
            return $redirect;
        }

        if (session('dev_authenticated')) {
            return redirect()->route('admin.dev.dashboard');
        }

        return view('admin.dev.gate');
    }

    public function authenticate(Request $request)
    {
        if ($redirect = $this->requireVerifiedTwoFactor()) {
            return $redirect;
        }

        $request->validate([
            'dev_password' => 'required|string',
        ]);

        $storedHash = SystemSetting::getValue('dev_page_password');

        if (!$storedHash || !Hash::check($request->input('dev_password'), $storedHash)) {
            return back()->with('error', 'Senha incorreta.');
        }

        session([
            'dev_authenticated'    => true,
            'dev_authenticated_at' => now(),
        ]);

        // Auditoria de acesso: o Dev Portal expõe schema/rotas — IP sempre
        // hasheado, nunca cru (padrão sem-PII-em-log do projeto).
        Log::info('Dev Portal: acesso autenticado', [
            'user_id'    => auth()->id(),
            'ip_hash'    => hash('sha256', (string) $request->ip()),
            'user_agent' => substr((string) $request->userAgent(), 0, 200),
        ]);

        return redirect()->route('admin.dev.dashboard');
    }

    /**
     * Defesa em profundidade: o RequireTwoFactor no grupo web já cobre estas
     * rotas, mas o Dev Portal expõe schema/rotas internas — se o grupo mudar,
     * esta camada explícita continua exigindo 2FA ativo e verificado.
     */
    private function requireVerifiedTwoFactor()
    {
        $user = auth()->user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('2fa.show')
                ->with('warning', 'Ative o 2FA antes de acessar o Dev Portal.');
        }

        if (!session('2fa_verified')) {
            return redirect()->route('2fa.challenge')
                ->with('warning', 'Verifique seu segundo fator para continuar.');
        }

        return null;
    }

    public function logout()
    {
        session()->forget(['dev_authenticated', 'dev_authenticated_at']);
        return redirect()->route('admin.dev.gate')->with('success', 'Sessão encerrada.');
    }

    public function dashboard()
    {
        $schema    = $this->buildSchema();
        $routes    = $this->buildRoutes();
        $queues    = $this->buildQueues();
        $arch      = $this->buildArchitecture();

        return view('admin.dev.dashboard', compact('schema', 'routes', 'queues', 'arch'));
    }

    /**
     * Colunas de credenciais/tokens ficam fora do schema dump — reduzem o
     * reconhecimento de um atacante que comprometa a conta do super admin.
     */
    public static function isSensitiveColumn(string $name): bool
    {
        return (bool) preg_match('/(password|secret|token|bidx|recovery_codes|api_key)/i', $name);
    }

    // ── Private builders ──────────────────────────────────────────────────────

    private function buildSchema(): array
    {
        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $key    = "Tables_in_{$dbName}";
        $result = [];

        foreach ($tables as $row) {
            $table   = $row->$key;
            $columns = DB::select("SHOW FULL COLUMNS FROM `{$table}`");
            $indexes = DB::select("SHOW INDEX FROM `{$table}`");

            $indexMap = [];
            foreach ($indexes as $idx) {
                $indexMap[$idx->Column_name][] = ($idx->Key_name === 'PRIMARY')
                    ? 'PK'
                    : ($idx->Non_unique ? 'IDX' : 'UQ');
            }

            $cols = [];
            foreach ($columns as $col) {
                if (self::isSensitiveColumn($col->Field)) {
                    continue;
                }

                $flags = $indexMap[$col->Field] ?? [];
                $cols[] = [
                    'name'    => $col->Field,
                    'type'    => $col->Type,
                    'null'    => $col->Null,
                    'default' => $col->Default,
                    'extra'   => $col->Extra,
                    'comment' => $col->Comment,
                    'flags'   => $flags,
                ];
            }

            $result[] = [
                'table'   => $table,
                'columns' => $cols,
            ];
        }

        usort($result, fn($a, $b) => strcmp($a['table'], $b['table']));

        return $result;
    }

    private function buildRoutes(): array
    {
        $routes = Route::getRoutes();
        $result = [];

        foreach ($routes as $route) {
            $action = $route->getAction();
            $uses   = $action['uses'] ?? null;

            if (is_string($uses)) {
                $controller = $uses;
            } elseif (isset($action['controller'])) {
                $controller = $action['controller'];
            } else {
                $controller = 'Closure';
            }

            foreach ($route->methods() as $method) {
                if ($method === 'HEAD') continue;

                $result[] = [
                    'method'     => $method,
                    'uri'        => '/' . ltrim($route->uri(), '/'),
                    'name'       => $route->getName() ?? '—',
                    'controller' => $controller,
                    'middleware' => implode(', ', array_map(
                        fn($m) => is_string($m) ? $m : '[Closure]',
                        $route->gatherMiddleware()
                    )),
                ];
            }
        }

        // Sort by URI
        usort($result, fn($a, $b) => strcmp($a['uri'], $b['uri']));

        return $result;
    }

    private function buildQueues(): array
    {
        $jobFiles = glob(app_path('Jobs/*.php'));
        $jobs = [];

        foreach ($jobFiles as $file) {
            $class   = 'App\\Jobs\\' . basename($file, '.php');
            $reflect = null;

            try {
                $reflect = new \ReflectionClass($class);
            } catch (\Throwable) {
                continue;
            }

            if (!$reflect->isInstantiable()) continue;

            $props = [];
            foreach (['queue', 'connection', 'tries', 'timeout', 'maxExceptions', 'failOnTimeout', 'delay', 'backoff'] as $p) {
                if ($reflect->hasProperty($p)) {
                    $prop = $reflect->getProperty($p);
                    $prop->setAccessible(true);
                    try {
                        $props[$p] = $prop->isStatic()
                            ? $prop->getValue()
                            : '(instance)';
                    } catch (\Throwable) {
                        $props[$p] = '?';
                    }
                }
            }

            $ifaces = array_map(fn($i) => $i->getShortName(), $reflect->getInterfaces());

            $jobs[] = [
                'class'      => $class,
                'short'      => $reflect->getShortName(),
                'interfaces' => $ifaces,
                'props'      => $props,
            ];
        }

        usort($jobs, fn($a, $b) => strcmp($a['short'], $b['short']));

        $config = [
            'driver'      => config('queue.default'),
            'retry_after' => config('queue.connections.' . config('queue.default') . '.retry_after'),
        ];

        return ['jobs' => $jobs, 'config' => $config];
    }

    private function buildArchitecture(): array
    {
        return [
            'stack' => [
                ['label' => 'Framework',  'value' => 'Laravel 9 + PHP 8.1'],
                ['label' => 'DB',         'value' => 'MySQL 8 — multi-tenant (tenant_id)'],
                ['label' => 'Cache/Queue','value' => 'Redis — driver: ' . config('queue.default')],
                ['label' => 'Broadcast',  'value' => 'Pusher / Soketi (Reverb)'],
                ['label' => 'Storage',    'value' => 'Laravel Storage — local (public) + S3 optional'],
                ['label' => 'Mail',       'value' => 'Brevo (SendinBlue) SMTP'],
                ['label' => 'WhatsApp',   'value' => 'Evolution API v2 (Baileys)'],
                ['label' => 'AI',         'value' => 'DeepSeek / Gemini / Together AI'],
                ['label' => 'Payments',   'value' => 'AbacatePay · OpenPix (PIX)'],
                ['label' => 'Auth',       'value' => 'Laravel Sanctum · 2FA (TOTP) · Super Admin'],
            ],
            'tenancy' => [
                'Strategy'            => 'Single-database, shared tables, tenant_id column',
                'Global Scope'        => 'BelongsToTenant — aplicado via boot() em cada model',
                'Bypass scope'        => 'withoutGlobalScopes() — usado em jobs/console',
                'Super Admin'         => 'tenant_id = NULL — acessa todos os tenants',
            ],
            'middleware_groups' => [
                'web'          => 'Sessions, CSRF, Auth, LGPD, 2FA',
                'api'          => 'Throttle, Sanctum, ApiHeaders (remove X-Powered-By)',
                'super_admin'  => 'EnsureSuperAdmin — redireciona se não for super admin',
                'subscription' => 'CheckSubscription — bloqueia tenants sem plano ativo',
                'dev_auth'     => 'DevPageAuth — sessão de 30 min protegida por senha',
            ],
            'key_models' => [
                'User'               => 'Usuários do sistema (todos os tenants + super admin)',
                'Tenant'             => 'Tenant / organização',
                'SystemSetting'      => 'Configurações globais key/value do SaaS',
                'WhatsappConfig'     => 'Config de instância WhatsApp por tenant',
                'WhatsappInstance'   => 'Instâncias Evolution API por tenant',
                'WhatsappChat'       => 'Contatos/chats WhatsApp',
                'WhatsappMessage'    => 'Mensagens trocadas via WhatsApp',
                'BroadcastCampaign'  => 'Campanhas de disparo em massa',
                'Transaction'        => 'Lançamentos financeiros',
                'Project'            => 'Projetos gerenciados',
                'Task'               => 'Tarefas dentro de projetos',
                'SubscriptionPlan'   => 'Planos de assinatura',
                'LgpdRequest'        => 'Requisições LGPD (art. 18–20)',
                'MeetingBooking'     => 'Agendamentos de reunião',
            ],
            'key_services' => [
                'EvolutionApiService'      => 'Comunicação com Evolution API (envio, QR, webhook)',
                'AntiBanManager'           => 'Janela segura, delay, short-URL blocker',
                'BruceAIService'           => 'Integração IA — DeepSeek / Gemini',
                'WhatsappBotService'       => 'Bot de gestão interna (comandos por WhatsApp)',
                'WhatsappAttendanceService'=> 'Bot de atendimento ao cliente externo',
            ],
            'scheduled_commands' => [
                'broadcast:process-scheduled' => 'Despacha campanhas agendadas (atomic lock)',
                'whatsapp:send-scheduled'      => 'Mensagens individuais agendadas',
                'queue:prune-failed'           => 'Limpa failed jobs antigos',
            ],
        ];
    }
}
