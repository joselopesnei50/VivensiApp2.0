<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request)
    {
        $user   = $request->user();
        $tenant = $user->tenant;

        return response()->json([
            'data' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'locale'      => $user->locale ?? app()->getLocale(),
                'tenant'      => $tenant ? [
                    'id'   => $tenant->id,
                    'name' => $tenant->name,
                    'type' => $tenant->type ?? 'default',
                ] : null,
                'token_abilities' => $user->currentAccessToken()?->abilities ?? ['*'],
            ],
        ]);
    }
}
