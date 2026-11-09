{{-- Perfil do público no PDF do relatório anual. $profile vem do AudienceProfileService. --}}
<div class="box">
    <h2>Perfil do público atendido</h2>
    <div class="muted small" style="margin-bottom:6px;">
        {{ number_format((int) $profile['total']) }} pessoa(s) — titulares dos cadastros com atendimento no período. Idade em {{ $profile['referenceDate'] }}.
    </div>
    <table>
        <tr>
            @foreach(['gender' => 'Gênero', 'age' => 'Faixa etária', 'race' => 'Cor/raça'] as $key => $title)
                <td style="width:33%; border-bottom:none; padding:0 6px 0 0;">
                    <table>
                        <thead><tr><th>{{ $title }}</th><th class="right" style="width:38px;">Qtde</th><th class="right" style="width:38px;">%</th></tr></thead>
                        <tbody>
                            @foreach($profile[$key] as $row)
                                <tr>
                                    <td class="small">{{ $row['label'] }}</td>
                                    <td class="right small"><strong>{{ number_format($row['count']) }}</strong></td>
                                    <td class="right small">{{ $row['pct'] === null ? '—' : number_format($row['pct'], 1, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            @endforeach
        </tr>
    </table>
</div>
