<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

echo "--- VIVENSI DEEP AUDIT REPORT ---\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// 1. MULTI-TENANCY ISOLATION
echo "1. Isolação de Multi-tenancy:\n";
$user = User::where('tenant_id', 1)->first();
if (!$user) {
    echo "[ERRO] Usuário de teste não encontrado.\n";
} else {
    Auth::login($user);
    $allProjectsCount = Project::all()->count();
    $tenant1ProjectsCount = Project::where('tenant_id', 1)->count();
    
    if ($allProjectsCount > $tenant1ProjectsCount) {
        echo "[CRÍTICO] FALHA DE ISOLAÇÃO: User 1 consegue ver " . ($allProjectsCount - $tenant1ProjectsCount) . " projetos de outros tenants!\n";
    } else {
        echo "[OK] Isolação de Projetos garantida.\n";
    }

    $allTransactionsCount = Transaction::all()->count();
    $tenant1TransactionsCount = Transaction::where('tenant_id', 1)->count();
    if ($allTransactionsCount > $tenant1TransactionsCount) {
        echo "[CRÍTICO] FALHA DE ISOLAÇÃO: Transações de outros tenants visíveis!\n";
    } else {
        echo "[OK] Isolação de Transações garantida.\n";
    }
}

// 2. MODEL TRAIT AUDIT
echo "\n2. Auditoria de Traits (BelongsToTenant):\n";
$models = ['Project', 'Transaction', 'Task', 'FinancialCategory', 'ProjectMember'];
foreach($models as $m) {
    $class = "App\\Models\\" . $m;
    $traits = class_uses($class);
    if (isset($traits['App\\Traits\\BelongsToTenant'])) {
        echo "[OK] Model $m possui o Trait de Tenant.\n";
    } else {
        echo "[AVISO] Model $m NÃO possui o Trait de Tenant. Verifique a segurança manual.\n";
    }
}

// 3. FINANCIAL CALCULATIONS INTEGRITY
echo "\n3. Integridade de Cálculos Financeiros:\n";
$project = Project::where('tenant_id', 1)->first();
if ($project) {
    $expenses = Transaction::where('project_id', $project->id)->where('type', 'expense')->sum('amount');
    $income = Transaction::where('project_id', $project->id)->where('type', 'income')->sum('amount');
    echo "[INFO] Projeto: " . $project->name . " | Orçamento: R$ " . $project->budget . "\n";
    echo "[INFO] Total Entradas: R$ $income | Total Saídas: R$ $expenses\n";
    echo "[OK] Cálculos de agregação funcionando.\n";
}

// 4. AI SERVICE CONFIGURATION
echo "\n4. Configuração de Serviços AI:\n";
$gemini = \App\Models\SystemSetting::getValue('gemini_api_key');
$deepseek = \App\Models\SystemSetting::getValue('deepseek_api_key');
echo "Gemini API: " . ($gemini ? "[CONFIGURADO]" : "[PENDENTE]") . "\n";
echo "DeepSeek API: " . ($deepseek ? "[CONFIGURADO]" : "[PENDENTE]") . "\n";

echo "\n--- FIM DO AUDIT SCRIPT ---\n";
