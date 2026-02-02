@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Beneficiários</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Gestão de famílias atendidas e histórico de evolução.</p>
    </div>
    <a href="{{ url('/ngo/beneficiaries/create') }}" class="btn-premium">
        <i class="fas fa-plus"></i> Novo Beneficiário
    </a>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Nome</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">NIS / CPF</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Idade</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Atendimentos</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Status</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($beneficiaries as $beneficiary)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 15px;">
                    <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}" style="color: #4f46e5; font-weight: 600; text-decoration: none;">
                        {{ $beneficiary->name }}
                    </a>
                </td>
                <td style="padding: 15px; text-align: center; color: #64748b; font-size: 0.9rem;">
                    {{ $beneficiary->nis ?? $beneficiary->cpf ?? '-' }}
                </td>
                <td style="padding: 15px; text-align: center; color: #64748b;">
                    {{ $beneficiary->birth_date ? \Carbon\Carbon::parse($beneficiary->birth_date)->age . ' anos' : '-' }}
                </td>
                <td style="padding: 15px; text-align: center;">
                    <span style="background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 700;">
                        {{ $beneficiary->attendances_count }}
                    </span>
                </td>
                <td style="padding: 15px; text-align: center;">
                    @if($beneficiary->status == 'active')
                        <span style="color: #16a34a; background: #dcfce7; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;">ATIVO</span>
                    @elseif($beneficiary->status == 'graduated')
                        <span style="color: #2563eb; background: #dbeafe; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;">GRADUADO</span>
                    @else
                        <span style="color: #94a3b8; background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;">INATIVO</span>
                    @endif
                </td>
                <td style="padding: 15px; text-align: center;">
                    <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}" class="btn-premium" style="padding: 5px 10px; font-size: 0.8rem; background: #f1f5f9; color: #475569;">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding: 20px;">
        {{ $beneficiaries->links() }}
    </div>
</div>
@endsection
