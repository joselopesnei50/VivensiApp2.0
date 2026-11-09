<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Perfil do público atendido no período: gênero, faixa etária e cor/raça.
 *
 * Conta cada beneficiário (titular do cadastro) uma vez, desde que tenha ao
 * menos um atendimento no período — mesmo universo de "Famílias únicas
 * atendidas" do relatório anual, com os mesmos filtros (status e tipo).
 *
 * Idade calculada no fim do período (ou hoje, se o período ainda não acabou).
 * Faixas seguem os marcos legais usados na prestação de contas: primeira
 * infância (Marco Legal), criança e adolescente (ECA), jovem (Estatuto da
 * Juventude) e pessoa idosa (Estatuto da Pessoa Idosa).
 */
class AudienceProfileService
{
    public const GENDERS = [
        'feminino'             => 'Feminino',
        'masculino'            => 'Masculino',
        'nao_binario'          => 'Não binário',
        'outro'                => 'Outro',
        'prefiro_nao_informar' => 'Prefere não informar',
    ];

    public const RACES = [
        'branca'               => 'Branca',
        'preta'                => 'Preta',
        'parda'                => 'Parda',
        'amarela'              => 'Amarela',
        'indigena'             => 'Indígena',
        'prefiro_nao_informar' => 'Prefere não informar',
    ];

    /** [rótulo, idade mínima, idade máxima] */
    public const AGE_BANDS = [
        ['0 a 5 anos (primeira infância)', 0, 5],
        ['6 a 11 anos (criança)', 6, 11],
        ['12 a 17 anos (adolescente)', 12, 17],
        ['18 a 29 anos (jovem)', 18, 29],
        ['30 a 59 anos (adulto)', 30, 59],
        ['60 anos ou mais (pessoa idosa)', 60, 130],
    ];

    public const NOT_INFORMED = 'Não informado';

    /**
     * @return array{total:int, referenceDate:string, gender:array, age:array, race:array}
     *   Cada dimensão é uma lista de ['label' => string, 'count' => int, 'pct' => float|null].
     */
    public function build(int $tenantId, string $from, string $to, string $benefStatus = '', string $type = ''): array
    {
        $attended = DB::table('attendances as a')
            ->where('a.tenant_id', $tenantId)
            ->whereBetween('a.date', [$from, $to])
            ->when($type !== '', fn ($q) => $q->where('a.type', $type))
            ->select('a.beneficiary_id');

        $people = DB::table('beneficiaries as b')
            ->where('b.tenant_id', $tenantId)
            ->whereIn('b.id', $attended)
            ->when($benefStatus !== '', fn ($q) => $q->where('b.status', $benefStatus))
            ->get(['b.gender', 'b.race_color', 'b.birth_date']);

        $reference = Carbon::parse($to)->endOfDay();
        if ($reference->isFuture()) {
            $reference = now();
        }

        $gender = array_fill_keys(array_values(self::GENDERS), 0) + [self::NOT_INFORMED => 0];
        $race   = array_fill_keys(array_values(self::RACES), 0) + [self::NOT_INFORMED => 0];
        $age    = array_fill_keys(array_column(self::AGE_BANDS, 0), 0) + [self::NOT_INFORMED => 0];

        foreach ($people as $p) {
            $gender[self::GENDERS[$p->gender] ?? self::NOT_INFORMED]++;
            $race[self::RACES[$p->race_color] ?? self::NOT_INFORMED]++;
            $age[$this->ageBand($p->birth_date, $reference)]++;
        }

        $total = $people->count();

        return [
            'total'         => $total,
            'referenceDate' => $reference->format('d/m/Y'),
            'gender'        => $this->rows($gender, $total),
            'age'           => $this->rows($age, $total),
            'race'          => $this->rows($race, $total),
        ];
    }

    private function ageBand(?string $birthDate, Carbon $reference): string
    {
        if (! $birthDate) {
            return self::NOT_INFORMED;
        }
        try {
            $birth = Carbon::parse($birthDate);
        } catch (\Throwable) {
            return self::NOT_INFORMED;
        }
        if ($birth->greaterThan($reference)) {
            return self::NOT_INFORMED; // data de nascimento depois do período: cadastro errado
        }

        $years = $birth->diffInYears($reference);
        foreach (self::AGE_BANDS as [$label, $min, $max]) {
            if ($years >= $min && $years <= $max) {
                return $label;
            }
        }
        return self::NOT_INFORMED;
    }

    /** "Não informado" só aparece quando há alguém nele. */
    private function rows(array $counts, int $total): array
    {
        $out = [];
        foreach ($counts as $label => $count) {
            if ($label === self::NOT_INFORMED && $count === 0) {
                continue;
            }
            $out[] = [
                'label' => $label,
                'count' => $count,
                'pct'   => $total > 0 ? (float) ($count / $total * 100) : null,
            ];
        }
        return $out;
    }
}
