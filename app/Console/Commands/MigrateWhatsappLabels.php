<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Models\WhatsappLabel;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Migração de dados das etiquetas WhatsApp:
 *  1) Para cada tenant ativo, cria as 7 etiquetas default (mesmas do array
 *     antigo hardcoded em chat.blade.php) se ainda não existirem.
 *  2) Para cada chat com whatsapp_chats.labels (JSON) não-vazio, faz lookup
 *     da etiqueta pelo slug do nome e popula a tabela pivot whatsapp_chat_label.
 *     Se o nome não bate com nenhuma etiqueta existente, cria uma custom.
 *
 * Idempotente: rodar duas vezes não duplica nada. firstOrCreate + syncWithoutDetaching.
 */
class MigrateWhatsappLabels extends Command
{
    protected $signature   = 'whatsapp:migrate-labels {--apply : Aplica as mudanças no banco (sem essa flag o command é dry-run)}';
    protected $description = 'Migra etiquetas WhatsApp da coluna JSON legada para tabelas relacionais';

    private const DEFAULT_LABELS = [
        ['name' => 'Novo Lead', 'slug' => 'novo-lead', 'color' => '#1d4ed8', 'background' => '#dbeafe'],
        ['name' => 'Suporte',   'slug' => 'suporte',   'color' => '#c2410c', 'background' => '#ffedd5'],
        ['name' => 'Venda',     'slug' => 'venda',     'color' => '#15803d', 'background' => '#dcfce7'],
        ['name' => 'Urgente',   'slug' => 'urgente',   'color' => '#b91c1c', 'background' => '#fee2e2'],
        ['name' => 'VIP',       'slug' => 'vip',       'color' => '#7e22ce', 'background' => '#f3e8ff'],
        ['name' => 'Agendado',  'slug' => 'agendado',  'color' => '#0e7490', 'background' => '#cffafe'],
        ['name' => 'Concluído', 'slug' => 'concluido', 'color' => '#64748b', 'background' => '#f1f5f9'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply ? '== Modo APLICAR — mudanças vão ao banco ==' : '== Modo DRY-RUN — nada é gravado ==');

        // ── Fase 1: seed etiquetas default por tenant ──────────────────
        $tenantIds = WhatsappChat::withoutGlobalScopes()
            ->select('tenant_id')
            ->distinct()
            ->pluck('tenant_id')
            ->all();

        if (empty($tenantIds)) {
            $this->warn('Nenhum tenant com whatsapp_chats encontrado.');
            return self::SUCCESS;
        }

        $this->info("Tenants com chats: " . count($tenantIds));

        $defaultsCreated = 0;
        foreach ($tenantIds as $tid) {
            foreach (self::DEFAULT_LABELS as $d) {
                $exists = WhatsappLabel::withoutGlobalScopes()
                    ->where('tenant_id', $tid)
                    ->where('slug', $d['slug'])
                    ->exists();

                if (!$exists) {
                    if ($apply) {
                        WhatsappLabel::create(array_merge($d, ['tenant_id' => $tid]));
                    }
                    $defaultsCreated++;
                }
            }
        }
        $this->line("Etiquetas default a criar: {$defaultsCreated}");

        // ── Fase 2: backfill da coluna JSON para o pivot ───────────────
        $chats = WhatsappChat::withoutGlobalScopes()
            ->whereNotNull('labels')
            ->where('labels', '!=', '[]')
            ->get(['id', 'tenant_id', 'labels']);

        $this->info("Chats com labels JSON não-vazio: " . $chats->count());

        $linksCreated  = 0;
        $customCreated = 0;
        $bar = $chats->count() > 0 ? $this->output->createProgressBar($chats->count()) : null;
        $bar?->start();

        foreach ($chats as $chat) {
            $names = is_array($chat->labels) ? $chat->labels : [];
            if (empty($names)) {
                $bar?->advance();
                continue;
            }

            $labelIds = [];
            foreach ($names as $name) {
                $name = trim((string) $name);
                if ($name === '') continue;
                $slug = Str::slug($name) ?: 'etiqueta';

                $label = WhatsappLabel::withoutGlobalScopes()
                    ->where('tenant_id', $chat->tenant_id)
                    ->where('slug', $slug)
                    ->first();

                if (!$label) {
                    // Etiqueta custom — não está nos defaults. Cria com cor neutra cinza.
                    if ($apply) {
                        $label = WhatsappLabel::create([
                            'tenant_id'  => $chat->tenant_id,
                            'name'       => $name,
                            'slug'       => $slug,
                            'color'      => '#64748b',
                            'background' => '#f1f5f9',
                        ]);
                    }
                    $customCreated++;
                }

                if ($label) {
                    $labelIds[] = $label->id;
                }
            }

            if ($apply && !empty($labelIds)) {
                $chat->labelTags()->syncWithoutDetaching($labelIds);
            }
            $linksCreated += count($labelIds);
            $bar?->advance();
        }

        $bar?->finish();
        $this->newLine(2);

        $this->table(
            ['Tenants', 'Defaults a criar', 'Customs a criar', 'Vínculos chat-label a criar'],
            [[count($tenantIds), $defaultsCreated, $customCreated, $linksCreated]]
        );

        if (!$apply && ($defaultsCreated > 0 || $customCreated > 0 || $linksCreated > 0)) {
            $this->warn('Re-execute com --apply para gravar as mudanças.');
        }

        if ($apply) {
            $this->info('Migração concluída.');
        }

        return self::SUCCESS;
    }
}
