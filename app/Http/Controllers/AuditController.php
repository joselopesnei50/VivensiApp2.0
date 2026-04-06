<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\AuditDownload;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $q = trim((string) $request->get('q', ''));
        $event = trim((string) $request->get('event', ''));
        $userId = $request->get('user_id');
        $from = $request->get('from');
        $to = $request->get('to');

        $logsQ = AuditLog::where('tenant_id', $tenantId)
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($q !== '') {
            $logsQ->where(function ($w) use ($q) {
                $w->where('auditable_type', 'like', '%' . $q . '%')
                  ->orWhere('event', 'like', '%' . $q . '%')
                  ->orWhere('ip_address', 'like', '%' . $q . '%')
                  ->orWhere('url', 'like', '%' . $q . '%');
            });
        }
        if ($event !== '') {
            $logsQ->where('event', $event);
        }
        if (!empty($userId)) {
            $logsQ->where('user_id', $userId);
        }
        if (!empty($from)) {
            $logsQ->whereDate('created_at', '>=', $from);
        }
        if (!empty($to)) {
            $logsQ->whereDate('created_at', '<=', $to);
        }

        $logs = $logsQ->paginate(20)->appends($request->query());

        // For filter dropdowns
        $users = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $events = ['created', 'updated', 'deleted', 'login', 'download', 'validate'];

        return view('ngo.audit.index', compact('logs', 'users', 'events'));
    }

    public function show($id)
    {
        $log = AuditLog::where('tenant_id', auth()->user()->tenant_id)
                        ->where('id', $id)
                        ->with('user')
                        ->firstOrFail();

        return view('ngo.audit.show', compact('log'));
    }

    public function exportCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $q = trim((string) $request->get('q', ''));
        $event = trim((string) $request->get('event', ''));
        $userId = $request->get('user_id');
        $from = $request->get('from');
        $to = $request->get('to');

        $filename = 'audit-' . date('Y-m-d_His') . '.csv';

        AuditDownload::log('AuditLog', null, [
            'format' => 'csv',
            'q' => $q,
            'event' => $event,
            'user_id' => $userId,
            'from' => $from,
            'to' => $to,
        ]);

        return response()->streamDownload(function () use ($tenantId, $q, $event, $userId, $from, $to) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Usuário', 'Evento', 'Tipo', 'ID', 'IP', 'URL']);

            $baseQ = AuditLog::where('tenant_id', $tenantId)->with('user')->orderBy('created_at', 'desc');
            if ($q !== '') {
                $baseQ->where(function ($w) use ($q) {
                    $w->where('auditable_type', 'like', '%' . $q . '%')
                      ->orWhere('event', 'like', '%' . $q . '%')
                      ->orWhere('ip_address', 'like', '%' . $q . '%')
                      ->orWhere('url', 'like', '%' . $q . '%');
                });
            }
            if ($event !== '') $baseQ->where('event', $event);
            if (!empty($userId)) $baseQ->where('user_id', $userId);
            if (!empty($from)) $baseQ->whereDate('created_at', '>=', $from);
            if (!empty($to)) $baseQ->whereDate('created_at', '<=', $to);

            $baseQ->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $log) {
                    fputcsv($out, [
                        optional($log->created_at)->format('Y-m-d H:i:s'),
                        $log->user->name ?? 'Sistema',
                        $log->event,
                        $log->auditable_type,
                        $log->auditable_id,
                        $log->ip_address,
                        $log->url,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
