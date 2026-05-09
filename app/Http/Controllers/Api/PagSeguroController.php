<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagSeguroController extends Controller
{
    public function checkout(Request $request)
    {
        Log::warning('PagSeguroController@checkout — endpoint descontinuado.', [
            'ip' => $request->ip(), 'user_id' => $request->user()?->id,
        ]);
        return response()->json([
            'status'  => 'deprecated',
            'message' => 'PagSeguro foi descontinuado. Use AbacatePay (/api/abacatepay/checkout).',
        ], 410);
    }
}
