<?php
/**
 * Diagnostico + fix: le valor atual da SystemSetting.together_image_model,
 * seta pra SDXL, esvazia cache, confirma persistencia.
 *
 * Roda: php scripts/set-together-sdxl.php
 */
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

echo "=== Antes ===\n";
$row = DB::table('system_settings')->where('key', 'together_image_model')->first();
echo "DB: " . ($row ? $row->value : '(nao existe)') . "\n";
echo "getValue: " . (SystemSetting::getValue('together_image_model') ?: '(vazio)') . "\n\n";

echo "=== Setando ===\n";
SystemSetting::setValue('together_image_model', 'stabilityai/stable-diffusion-xl-base-1.0', 'social_ai');
Cache::forget('system_setting.together_image_model');

echo "=== Depois ===\n";
$row = DB::table('system_settings')->where('key', 'together_image_model')->first();
echo "DB: " . ($row ? $row->value : '(nao existe)') . "\n";
echo "getValue: " . (SystemSetting::getValue('together_image_model') ?: '(vazio)') . "\n";
