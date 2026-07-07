<?php

namespace App\Http\Controllers;

use App\Models\FacebookDataDeletionRequest;

class PublicLgpdController extends Controller
{
    /**
     * Página pública de acompanhamento de solicitação de exclusão de dados
     * originada pela Meta (Data Deletion Callback). O usuário é redirecionado
     * aqui pelo Facebook após pedir exclusão pelas configurações do FB.
     */
    public function status(string $code)
    {
        $request = FacebookDataDeletionRequest::where('confirmation_code', $code)->first();

        return view('lgpd.status', [
            'code'    => $code,
            'request' => $request,
        ]);
    }
}
