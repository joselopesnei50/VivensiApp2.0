<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    private const ALLOWED_ABILITIES = [
        'transactions:read', 'transactions:write',
        'projects:read',
        'tasks:read', 'tasks:write',
    ];

    public function index()
    {
        $tokens = auth()->user()->tokens()->orderBy('created_at', 'desc')->get();
        return view('settings.api-tokens', compact('tokens'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'abilities' => 'nullable|array',
            'abilities.*' => 'in:' . implode(',', self::ALLOWED_ABILITIES),
        ]);

        $abilities = $request->input('abilities', ['*']);

        $token = auth()->user()->createToken(
            $request->input('name'),
            $abilities
        );

        return back()->with('new_token', $token->plainTextToken)
                     ->with('success', 'Token criado com sucesso. Copie agora — ele não será exibido novamente.');
    }

    public function destroy(int $id)
    {
        auth()->user()->tokens()->where('id', $id)->delete();
        return back()->with('success', 'Token revogado com sucesso.');
    }
}
