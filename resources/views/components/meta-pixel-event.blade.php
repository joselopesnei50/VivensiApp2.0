{{--
    Meta Pixel — evento adicional (Lead, Purchase, Contact, CompleteRegistration...).

    Uso:
        <x-meta-pixel-event event="Lead" />
        <x-meta-pixel-event event="Purchase" :value="349.00" currency="BRL" />
        <x-meta-pixel-event event="Contact" :params="['content_name' => 'whatsapp_click']" />

    Precisa ser renderizado DEPOIS de <x-meta-pixel /> (nao antes) — assume fbq
    ja carregado. Se pixel_id vazio na config, nao renderiza nada.

    Props:
    - event (obrigatorio): nome do evento standard Meta (Lead, Purchase, etc)
      OU custom (fbq trackCustom fica pra outro component se precisar).
    - value (opcional): valor monetario. Se null, omite.
    - currency (opcional, default 'BRL'): so usado se value definido.
    - params (opcional): array de params adicionais (content_name, content_ids...).
--}}
@props([
    'event'    => null,
    'value'    => null,
    'currency' => 'BRL',
    'params'   => [],
])
@php
    $_metaPixelId = config('services.meta.pixel_id');
    if (!$_metaPixelId || !$event) return;

    $_payload = $params;
    if ($value !== null) {
        $_payload['value']    = (float) $value;
        $_payload['currency'] = $currency;
    }
    $_payloadJson = !empty($_payload) ? ', ' . json_encode($_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
@endphp
<!-- Meta Pixel Event: {{ $event }} -->
<script>
    if (typeof fbq === 'function') {
        fbq('track', @json($event){!! $_payloadJson !!});
    }
</script>
