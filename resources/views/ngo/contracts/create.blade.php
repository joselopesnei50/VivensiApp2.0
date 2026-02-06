@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
@endphp
<div class="vivensi-card" style="max-width: 800px; margin: 0 auto; padding: 40px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h3 style="margin: 0; font-size: 1.5rem; color: #1e293b;">Novo Contrato Digital</h3>
        <p style="color: #64748b; margin: 5px 0 0 0;">Redija o contrato e envie para assinatura com link seguro.</p>
        <p style="color: #94a3b8; margin: 10px 0 0 0; font-size: 0.9rem;">
            O link público de assinatura expira em <strong>{{ config('contracts.public_sign_ttl_days', 30) }}</strong> dias (configurável).
        </p>
    </div>

    <form action="{{ $basePath . '/ngo/contracts' }}" method="POST">
        @csrf

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Título do Documento</label>
            <input type="text" name="title" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Ex: Contrato de Prestação de Serviços - Consultoria">
        </div>

        <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Nome do Signatário</label>
                <input type="text" name="signer_name" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Nome Completo">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Email (Opcional)</label>
                <input type="email" name="signer_email" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="email@exemplo.com">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Endereço de quem irá assinar</label>
            <input type="text" name="signer_address" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Rua, Número, Bairro, Cidade - UF">
        </div>

        <div class="grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">WhatsApp / Telefone</label>
                <input type="text" name="signer_phone" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="(00) 00000-0000">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">CPF</label>
                <input type="text" name="signer_cpf" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="000.000.000-00">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">RG</label>
                <input type="text" name="signer_rg" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="0.000.000">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 30px;">
            <div style="display:flex; justify-content:space-between; align-items:end; gap: 15px; flex-wrap:wrap;">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Termos do Contrato</label>
                <div style="display:flex; gap:10px; align-items:center;">
                    <label style="color:#64748b; font-size:0.9rem;">Modelo:</label>
                    <select id="templateSelect" class="form-control-vivensi" style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px;">
                        <option value="">— Selecionar —</option>
                        <option value="service">Prestação de Serviços (simples)</option>
                        <option value="term">Termo de Voluntariado (simples)</option>
                        <option value="donation">Termo de Doação (simples)</option>
                    </select>
                    <button type="button" class="btn-outline" style="padding: 8px 12px; border-radius: 10px;" onclick="applyTemplate()">
                        Inserir
                    </button>
                </div>
            </div>
            <textarea id="contentInput" name="content" class="form-control-vivensi" required style="width: 100%; height: 360px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; font-family: sans-serif; resize: vertical;" placeholder="Cole aqui o texto do contrato..."></textarea>
            <p style="margin:10px 0 0 0; color:#94a3b8; font-size:0.85rem; line-height:1.4;">
                Dica: use linguagem clara e inclua <strong>objeto</strong>, <strong>prazo</strong>, <strong>responsabilidades</strong>, <strong>confidencialidade</strong> e <strong>foro</strong>.
            </p>
        </div>

        <div style="display: flex; gap: 15px;">
            <a href="{{ $basePath . '/ngo/contracts' }}" class="btn btn-outline" style="flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px;">Cancelar</a>
            <button type="submit" class="btn-premium" style="flex: 2; border: none; cursor: pointer; justify-content: center;">Gerar e Enviar</button>
        </div>
    </form>
</div>

<script>
    function applyTemplate() {
        const select = document.getElementById('templateSelect');
        const textarea = document.getElementById('contentInput');
        const v = select.value;
        if (!v) return;

        const templates = {
            service: `CONTRATO DE PRESTAÇÃO DE SERVIÇOS\n\nPARTES:\nContratante: {{TENANT_NAME}}\nContratado(a): {{SIGNER_NAME}} ({{SIGNER_CPF}})\n\nOBJETO:\nAs partes acordam a prestação de serviços descritos a seguir: ______________________________.\n\nPRAZO:\nInício: ___/___/____. Término: ___/___/____.\n\nVALOR E PAGAMENTO (se aplicável):\nR$ ________ (________________________________). Forma: ______________________________.\n\nRESPONSABILIDADES:\n1) Do(a) Contratado(a): _______________________.\n2) Do Contratante: ____________________________.\n\nCONFIDENCIALIDADE:\nAs partes se comprometem a manter sigilo sobre informações sensíveis.\n\nFORO:\nFica eleito o foro da comarca de ______________________.\n\nDeclaram, por fim, estarem de acordo com os termos.\n`,
            term: `TERMO DE VOLUNTARIADO\n\nEste Termo regula a atuação voluntária de {{SIGNER_NAME}} junto a {{TENANT_NAME}}.\n\nATIVIDADES:\nO(a) voluntário(a) realizará as seguintes atividades: ______________________________.\n\nJORNADA:\nDias/horários: ______________________________.\n\nNATUREZA DA ATIVIDADE:\nA atividade é voluntária, sem vínculo empregatício, conforme legislação aplicável.\n\nRESPONSABILIDADES E CONDUTA:\nO(a) voluntário(a) compromete-se a agir com ética e respeito às normas internas.\n\nVIGÊNCIA:\nEste termo é válido de ___/___/____ a ___/___/____.\n\nFORO:\nFica eleito o foro da comarca de ______________________.\n`,
            donation: `TERMO DE DOAÇÃO\n\nDOADOR(A): {{SIGNER_NAME}} ({{SIGNER_CPF}})\nDONATÁRIA: {{TENANT_NAME}}\n\nOBJETO:\nDoação do(s) bem(ns)/valor(es): ______________________________.\n\nCONDIÇÕES:\nA doação é feita de forma livre e espontânea, sem ônus, salvo se indicado: __________________.\n\nDECLARAÇÕES:\nO(a) doador(a) declara ser legítimo(a) proprietário(a) do bem/valor doado.\n\nFORO:\nFica eleito o foro da comarca de ______________________.\n`,
        };

        const tpl = templates[v] || '';
        if (!tpl) return;

        if (textarea.value && !confirm('Substituir o texto atual pelo modelo selecionado?')) {
            return;
        }
        textarea.value = tpl;
        textarea.focus();
    }
</script>
@endsection
