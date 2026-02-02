<?php

use App\Models\FinancialCategory;

$tenantId = 1;
$categories = [
    ['name' => 'Alimentação', 'type' => 'expense'],
    ['name' => 'Moradia', 'type' => 'expense'],
    ['name' => 'Transporte', 'type' => 'expense'],
    ['name' => 'Saúde', 'type' => 'expense'],
    ['name' => 'Educação', 'type' => 'expense'],
    ['name' => 'Lazer', 'type' => 'expense'],
    ['name' => 'Salário', 'type' => 'income'],
    ['name' => 'Investimentos', 'type' => 'income'],
    ['name' => 'Outros', 'type' => 'expense'],
];

foreach ($categories as $cat) {
    FinancialCategory::firstOrCreate(
        ['tenant_id' => $tenantId, 'name' => $cat['name']],
        ['type' => $cat['type']]
    );
}

echo "Categorias criadas com sucesso para Tenant 1!" . PHP_EOL;
