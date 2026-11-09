{{-- Termo de consentimento LGPD na ficha do beneficiário.
     Recebe $beneficiary, $consentHistory (últimos registros) e $consentMinor. --}}
@php
    $last = $consentHistory->first();
    $signed = $beneficiary->consent_at !== null;
    $revoked = !$signed && $last && $last->action === \App\Models\BeneficiaryConsent::ACTION_REVOKED;
    $openForm = $errors->has('signer_role') || $errors->has('signer_name') || $errors->has('signed_at');
@endphp
<div class="vivensi-card" id="consentimento">
    <h4 style="margin-top: 0; color: var(--ds-text); border-bottom: 1px solid var(--ds-border); padding-bottom: 10px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
        <span><i class="fas fa-user-shield me-1"></i> Termo LGPD</span>
        @if($signed)
            <span class="badge bg-success-subtle text-success-emphasis" style="font-size:.72rem;">assinado</span>
        @elseif($revoked)
            <span class="badge bg-danger-subtle text-danger-emphasis" style="font-size:.72rem;">revogado</span>
        @else
            <span class="badge bg-warning-subtle text-warning-emphasis" style="font-size:.72rem;">pendente</span>
        @endif
    </h4>

    @if($signed && $last)
        <p style="margin:0 0 6px 0; font-size:.9rem; color: var(--ds-text);">
            Assinado em <strong>{{ $beneficiary->consent_at->format('d/m/Y') }}</strong>
            por {{ $last->signer_name }} ({{ mb_strtolower($last->roleLabel()) }}).
        </p>
        <p style="margin:0 0 10px 0; font-size:.85rem; color: var(--ds-text-muted);">
            Uso de imagem:
            @if($beneficiary->image_consent)
                <strong style="color: var(--ds-text);">autorizado</strong>
            @else
                <strong style="color: var(--ds-text);">não autorizado</strong> — não use fotos ou vídeos desta pessoa em divulgação.
            @endif
        </p>
    @elseif($revoked)
        <p style="margin:0 0 10px 0; font-size:.9rem; color: var(--ds-text);">
            Consentimento revogado em <strong>{{ $last->signed_at->format('d/m/Y') }}</strong>.
            @if($last->notes)<br><span style="color: var(--ds-text-muted); font-size:.85rem;">{{ $last->notes }}</span>@endif
        </p>
    @else
        <p style="margin:0 0 10px 0; font-size:.9rem; color: var(--ds-text-muted);">
            Ainda não há termo de consentimento registrado. Imprima, colha a assinatura e registre aqui.
        </p>
    @endif

    @if($consentMinor && !$signed)
        <p style="margin:0 0 10px 0; font-size:.85rem; padding:8px 10px; border-radius:8px; background: var(--vv-tint-warning); border:1px solid var(--vv-tint-warning-border); color: var(--ds-text);">
            <i class="fas fa-child me-1"></i> Menor de 18 anos: o termo deve ser assinado pelo responsável legal.
        </p>
    @endif

    <div style="display:flex; flex-wrap:wrap; gap:8px;">
        <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/consent/term') }}" class="btn-ds btn-ds-outline" style="font-size:.85rem;">
            <i class="fas fa-print"></i> Imprimir termo
        </a>
        <button type="button" class="btn-premium" style="font-size:.85rem;" onclick="document.getElementById('consentForm').style.display='block'">
            <i class="fas fa-signature"></i> {{ $signed ? 'Registrar novo termo' : 'Registrar assinatura' }}
        </button>
        <a href="{{ route('attachments.index', ['morphType' => 'beneficiary', 'morphId' => $beneficiary->id]) }}" class="btn-ds btn-ds-ghost" style="font-size:.85rem;">
            <i class="fas fa-paperclip"></i> Anexar termo assinado
        </a>
    </div>

    <div id="consentForm" style="display: {{ $openForm ? 'block' : 'none' }}; margin-top: 12px; background: var(--vv-surface-soft); padding: 14px; border-radius: 10px; border: 1px solid var(--ds-border);">
        <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/consent') }}">
            @csrf
            @php $roleOld = old('signer_role', $consentMinor ? 'responsavel_legal' : 'titular'); @endphp
            <div class="form-group">
                <label style="display:block; font-size:.8rem; margin-bottom:5px;">Quem assinou</label>
                <select name="signer_role" class="form-control-vivensi" onchange="document.getElementById('consentSignerName').style.display = this.value === 'responsavel_legal' ? 'block' : 'none'">
                    @foreach(\App\Models\BeneficiaryConsent::ROLES as $k => $label)
                        <option value="{{ $k }}" @selected($roleOld === $k)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('signer_role')<small style="color:#dc2626;">{{ $message }}</small>@enderror
            </div>
            <div class="form-group" id="consentSignerName" style="display: {{ $roleOld === 'responsavel_legal' ? 'block' : 'none' }};">
                <label style="display:block; font-size:.8rem; margin-bottom:5px;">Nome do responsável legal</label>
                <input type="text" name="signer_name" value="{{ old('signer_name') }}" maxlength="255" class="form-control-vivensi">
                @error('signer_name')<small style="color:#dc2626;">{{ $message }}</small>@enderror
            </div>
            <div class="form-group">
                <label style="display:block; font-size:.8rem; margin-bottom:5px;">Data da assinatura</label>
                <input type="date" name="signed_at" value="{{ old('signed_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="form-control-vivensi" required>
                @error('signed_at')<small style="color:#dc2626;">{{ $message }}</small>@enderror
            </div>
            <label style="display:flex; gap:8px; align-items:flex-start; font-size:.85rem; color: var(--ds-text); margin-bottom: 10px;">
                <input type="hidden" name="image_use" value="0">
                <input type="checkbox" name="image_use" value="1" @checked(old('image_use')) style="margin-top:3px;">
                Autorizou o uso de imagem e voz (item 7 do termo)
            </label>
            <div style="text-align:right;">
                <button type="button" onclick="document.getElementById('consentForm').style.display='none'" style="border: none; background: none; color: var(--ds-text-muted); margin-right: 12px; cursor: pointer;">Cancelar</button>
                <button type="submit" class="btn-premium" style="display:inline-flex; width:auto; font-size:.9rem;">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>

    @if($consentHistory->isNotEmpty())
        <details style="margin-top: 12px; font-size:.85rem;">
            <summary style="cursor:pointer; color: var(--ds-text-muted);">Histórico ({{ $consentHistory->count() }})</summary>
            <ul style="list-style:none; padding:0; margin:8px 0 0 0;">
                @foreach($consentHistory as $c)
                    <li style="padding:6px 0; border-bottom:1px solid var(--ds-border); color: var(--ds-text);">
                        <strong>{{ $c->signed_at->format('d/m/Y') }}</strong> —
                        @if($c->action === \App\Models\BeneficiaryConsent::ACTION_GRANTED)
                            assinado por {{ $c->signer_name }} ({{ mb_strtolower($c->roleLabel()) }}), imagem {{ $c->image_use ? 'autorizada' : 'não autorizada' }}
                        @else
                            revogado
                        @endif
                        <span style="color: var(--ds-text-muted);">· registrado por {{ $c->recorder->name ?? '—' }}</span>
                    </li>
                @endforeach
            </ul>
        </details>
    @endif

    @if($signed)
        <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/consent/revoke') }}" style="margin-top: 10px;"
              onsubmit="return confirm('Registrar que o titular revogou o consentimento?');">
            @csrf
            <details>
                <summary style="cursor:pointer; color: var(--ds-text-muted); font-size:.82rem;">O titular pediu para revogar o consentimento</summary>
                <div style="margin-top:8px;">
                    <input type="text" name="notes" maxlength="1000" class="form-control-vivensi" placeholder="Observação (opcional): como foi o pedido">
                    <button type="submit" class="btn-ds btn-ds-danger" style="margin-top:8px; font-size:.85rem;">
                        <i class="fas fa-ban"></i> Registrar revogação
                    </button>
                </div>
            </details>
        </form>
    @endif
</div>
