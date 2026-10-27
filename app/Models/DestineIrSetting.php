<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DestineIrSetting extends Model
{
    protected $table = 'destine_ir_settings';

    protected $guarded = [];

    protected $casts = [
        'is_published'          => 'boolean',
        'show_deadline_banner'  => 'boolean',
        'show_simulator'        => 'boolean',
        'show_partners_wall'    => 'boolean',
        'show_share_bar'        => 'boolean',
        'deadline_date'         => 'date',
        'path1_steps'           => 'array',
        'path2_steps'           => 'array',
        'faq_items'             => 'array',
        'total_visits'          => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOr(fn () => static::seedDefaults());
    }

    public function getYoutubeIdAttribute(): ?string
    {
        $url = (string) $this->video_youtube_url;
        if ($url === '') return null;

        // Suporta youtube.com/watch?v=, youtu.be/, youtube.com/shorts/, youtube.com/embed/, youtube.com/v/
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|shorts/|embed/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    public static function seedDefaults(): self
    {
        return static::create([
            'is_published'   => false,
            'deadline_date'  => now()->setDate(now()->year, 12, 31)->startOfDay(),
            'what_is_text'   => "Uma parte do imposto que iria para a União vai para o Fundo Municipal dos Direitos da Criança e do Adolescente (FMDCA), gerido pelo COMCRIAR, o Conselho Municipal dos Direitos da Criança e do Adolescente de Araraquara. O dinheiro financia projetos aprovados pelo conselho e executados por entidades da cidade.",
            'who_can_text'   => "Pessoa física que declara no modelo completo (mesmo quem tem imposto a restituir) · Empresas tributadas pelo lucro real: até 1% do imposto devido.",
            'path1_steps'    => [
                ['title' => 'Faça o PIX', 'text' => 'Transfira o valor para o CNPJ do fundo: 17.837.230/0001-02 (Fundo Municipal dos Direitos da Criança e do Adolescente de Araraquara).'],
                ['title' => 'Envie o comprovante', 'text' => 'Mande para cmdca.araraquara@gmail.com informando nome, CPF ou CNPJ, valor e o comprovante. Se quiser apoiar uma entidade específica, informe o nome dela.'],
                ['title' => 'Guarde o recibo', 'text' => 'O conselho emite recibo. Informe a doação na declaração do ano seguinte.'],
            ],
            'path2_steps'    => [
                ['title' => 'Abra a ficha ECA', 'text' => 'No programa do IR, modelo completo, abra "Doações Diretamente na Declaração – ECA".'],
                ['title' => 'Escolha o destino', 'text' => 'Selecione Municipal · SP · Araraquara.'],
                ['title' => 'Use o valor calculado', 'text' => 'Use o valor disponível que o programa mostra (até 3% do imposto devido).'],
                ['title' => 'Pague o DARF', 'text' => 'Pague o DARF gerado dentro do prazo de entrega da declaração. Sem pagamento, a dedução é perdida.'],
                ['title' => 'Envie o DARF ao conselho', 'text' => 'Envie o DARF pago para cmdca.araraquara@gmail.com informando a entidade, se quiser.'],
            ],
            'cost_text'      => 'Nada a mais. O valor é abatido do imposto a pagar ou volta na restituição, corrigido pela Selic.',
            'faq_items'      => [
                ['q' => 'Quem tem restituição pode destinar?', 'a' => 'Sim.'],
                ['q' => 'Quem usa o desconto simplificado pode?', 'a' => 'Não, só o modelo completo (aquele em que você detalha deduções em vez do desconto padrão).'],
                ['q' => 'Posso escolher a entidade?', 'a' => 'Pode indicar o nome dela no e-mail ao conselho.'],
                ['q' => 'O dinheiro passa pela entidade ou pela Vivensi?', 'a' => 'Não. Vai direto para o fundo municipal.'],
            ],
            'footer_legal_text' => 'A Vivensi não recebe nem intermedeia doações. Os valores vão direto ao FMDCA de Araraquara. Base legal: ECA, arts. 260 a 260-K, e Lei 14.692/2023.',
            'og_title'       => 'Destine seu Imposto de Renda para crianças e adolescentes de Araraquara',
            'og_description' => 'Parte do seu IR pode ficar na nossa cidade, sem você pagar nada a mais. Veja o passo a passo.',
        ]);
    }
}
