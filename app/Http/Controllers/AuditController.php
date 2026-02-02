<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index()
    {
        $logs = AuditLog::where('tenant_id', auth()->user()->tenant_id)
                        ->with('user')
                        ->orderBy('created_at', 'desc')
                        ->paginate(20);

        return view('ngo.audit.index', compact('logs'));
    }

    public function show($id)
    {
        $log = AuditLog::where('tenant_id', auth()->user()->tenant_id)
                        ->where('id', $id)
                        ->with('user')
                        ->firstOrFail();

        return view('ngo.audit.show', compact('log'));
    }
}
