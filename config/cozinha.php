<?php

/**
 * Cozinha Solidária — config do módulo (Fase 1+).
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Raio do geofence anti-fraude
    |--------------------------------------------------------------------------
    | Distância máxima (metros) entre lat/lng do registro de refeição e
    | a lat/lng cadastrada da cozinha. Acima disso, o registro é marcado como
    | pendente ("não prestável") e exige justificativa/correção.
    |
    | Default 500m — suficiente pra capturar fotos no entorno da unidade sem
    | dar margem pra registro fraudulento "noutra cidade".
    */
    'geofence_radius_meters' => (int) env('COZINHA_GEOFENCE_RADIUS_METERS', 500),

    /*
    |--------------------------------------------------------------------------
    | Disk de storage para fotos do lastro
    |--------------------------------------------------------------------------
    | Default: usa o cloud disk (s3 em prod) se configurado, senão local em dev.
    */
    'storage_disk' => env('COZINHA_STORAGE_DISK', env('FILESYSTEM_CLOUD', env('FILESYSTEM_DISK', 'local'))),

    /*
    |--------------------------------------------------------------------------
    | Realizado vs Meta — cache TTL (segundos)
    |--------------------------------------------------------------------------
    */
    'realizado_cache_ttl' => (int) env('COZINHA_REALIZADO_CACHE_TTL', 300),

];
