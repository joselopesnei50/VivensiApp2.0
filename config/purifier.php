<?php

/**
 * HTMLPurifier (mews/purifier) — perfil restrito usado por sanitize_user_html().
 * Allowlist explícita: mudar tags permitidas = mudar aqui, nunca no caller.
 */
return [
    'encoding'         => 'UTF-8',
    'finalize'         => true,
    'ignoreNonStrings' => false,
    'cachePath'        => storage_path('app/purifier'),
    'cacheFileMode'    => 0755,

    'settings' => [
        'default' => [
            'HTML.Allowed' => 'h1,h2,h3,h4,h5,h6,p,br,strong,b,em,i,u,'
                . 'ul,ol,li,a[href|title|target],blockquote,table,thead,tbody,tr,th,td,'
                . 'img[src|alt|width|height],span,div',
            'HTML.ForbiddenElements'    => 'script,object,embed,iframe,form,input,button',
            'CSS.AllowedProperties'     => '',
            'AutoFormat.AutoParagraph'  => false,
            'AutoFormat.RemoveEmpty'    => true,
            'URI.AllowedSchemes'        => ['http' => true, 'https' => true, 'mailto' => true],
            'Attr.AllowedFrameTargets'  => ['_blank'],
            'HTML.SafeObject'           => false,
            'HTML.SafeEmbed'            => false,
        ],
    ],
];
