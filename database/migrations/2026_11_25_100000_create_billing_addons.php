<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adicionais contratados à parte do plano (2026-11-25):
 *  - billing_addons: catálogo (número extra no bot, pacote de e-mails/dia),
 *    cada item ligado a um produto avulso da AbacatePay;
 *  - tenant_addons: quanto cada organização contratou de cada item;
 *  - users.bot_access: quem usa o bot de gestão no WhatsApp (1 incluso no
 *    plano + números extras). No deploy só o administrador fica com acesso;
 *  - invoices.items: linhas da fatura (plano + adicionais) — o checkout da
 *    AbacatePay recebe os mesmos itens, numa cobrança só.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_addons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('description', 300)->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('unit_amount')->default(1)->comment('Quanto 1 unidade libera: 1 número, 25 e-mails/dia');
            $table->string('abacatepay_product_id')->nullable();
            $table->unsignedSmallInteger('max_quantity')->default(20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tenant_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('billing_addon_id')->constrained('billing_addons')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'billing_addon_id']);
        });

        if (!Schema::hasColumn('users', 'bot_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('bot_access')->default(false);
            });
        }

        if (!Schema::hasColumn('invoices', 'items')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->json('items')->nullable();
            });
        }

        $now = now();
        DB::table('billing_addons')->insert([
            [
                'code'                  => 'bot_number',
                'name'                  => 'Número extra no bot de gestão (WhatsApp)',
                'description'           => 'Mais uma pessoa da equipe usando o bot de gestão pelo WhatsApp.',
                'unit_price'            => 19.90,
                'unit_amount'           => 1,
                'abacatepay_product_id' => 'prod_GyBj0phnbrskDxPWaXTSssYU',
                'max_quantity'          => 20,
                'is_active'             => true,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'code'                  => 'email_pack',
                'name'                  => 'Pacote de +25 e-mails por dia',
                'description'           => 'Aumenta a cota diária de envio de campanhas de e-mail.',
                'unit_price'            => 9.90,
                'unit_amount'           => 25,
                'abacatepay_product_id' => 'prod_6WtM3C4LNX4d4RefEhGXEMEB',
                'max_quantity'          => 20,
                'is_active'             => true,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
        ]);

        // Acesso ao bot: só o administrador mais antigo de cada organização
        // (o número incluso no plano). Os demais precisam ser liberados.
        $admins = DB::table('users')
            ->join('tenants', 'tenants.id', '=', 'users.tenant_id')
            ->whereNotNull('users.tenant_id')
            ->where(fn ($q) => $q->where('users.status', 'active')->orWhereNull('users.status'))
            ->where(function ($q) {
                $q->whereIn('users.role', ['ngo', 'common'])
                  ->orWhere(fn ($q2) => $q2->where('users.role', 'manager')
                      ->where(fn ($q3) => $q3->where('tenants.type', '!=', 'ngo')->orWhereNull('tenants.type')));
            })
            ->groupBy('users.tenant_id')
            ->selectRaw('MIN(users.id) as id')
            ->pluck('id');
        foreach ($admins->chunk(500) as $ids) {
            DB::table('users')->whereIn('id', $ids->all())->update(['bot_access' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'items')) {
            Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('items'));
        }
        if (Schema::hasColumn('users', 'bot_access')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('bot_access'));
        }
        Schema::dropIfExists('tenant_addons');
        Schema::dropIfExists('billing_addons');
    }
};
