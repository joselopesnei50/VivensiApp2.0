<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Support\AuditDownload;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Atendimentos vinculados a beneficiarios (rota /ngo/beneficiaries/{id}/attendance/*
 * + /ngo/beneficiaries/attendances/export).
 *
 * Extraido de BeneficiaryController em 2026-07-18. Zero mudanca de logica:
 * mesmo padrao de tenant guard via `Beneficiary::where('tenant_id',...)`
 * + `->where('id', $id)->firstOrFail()` anti-IDOR.
 */
class BeneficiaryAttendanceController extends Controller
{
    public function exportAllAttendancesCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $from = $request->get('from');
        $to = $request->get('to');
        $type = trim((string) $request->get('type', ''));
        $q = trim((string) $request->get('q', ''));
        $benefStatus = trim((string) $request->get('benef_status', ''));

        AuditDownload::log('Beneficiaries:Attendances:All', null, [
            'format' => 'csv',
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'q' => $q,
            'benef_status' => $benefStatus,
        ]);

        $filename = 'atendimentos-ong-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $from, $to, $type, $q, $benefStatus) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Beneficiário', 'Status', 'NIS', 'CPF', 'Data', 'Tipo', 'Descrição', 'Registrado por']);

            $baseQ = DB::table('attendances as a')
                ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->where('a.tenant_id', $tenantId)
                ->where('b.tenant_id', $tenantId)
                ->select([
                    'b.name as beneficiary_name',
                    'b.status as beneficiary_status',
                    'b.nis as beneficiary_nis',
                    'b.cpf as beneficiary_cpf',
                    'a.date',
                    'a.type',
                    'a.description',
                    DB::raw("COALESCE(u.name, 'Sistema') as user_name"),
                ])
                ->orderByDesc('a.date')
                ->orderByDesc('a.id');

            if (!empty($from)) $baseQ->whereDate('a.date', '>=', $from);
            if (!empty($to)) $baseQ->whereDate('a.date', '<=', $to);
            if ($type !== '') $baseQ->where('a.type', $type);
            if ($benefStatus !== '') $baseQ->where('b.status', $benefStatus);
            if ($q !== '') {
                $baseQ->where(function ($w) use ($q) {
                    $w->where('a.description', 'like', '%' . $q . '%')
                      ->orWhere('a.type', 'like', '%' . $q . '%')
                      ->orWhere('b.name', 'like', '%' . $q . '%');
                });
            }

            $baseQ->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->beneficiary_name,
                        $r->beneficiary_status,
                        $this->decryptPii($r->beneficiary_nis),
                        $this->decryptPii($r->beneficiary_cpf),
                        $r->date,
                        $r->type,
                        $r->description,
                        $r->user_name,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeAttendance(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;

        // Ensure beneficiary belongs to current tenant (critical for multi-tenant safety)
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|string',
            'description' => 'required|string'
        ]);

        $attendance = new Attendance($validated);
        $attendance->tenant_id = $tenantId;
        $attendance->beneficiary_id = $beneficiary->id;
        $attendance->user_id = auth()->id();
        $attendance->save();

        return redirect()->back()->with('success', 'Atendimento registrado com sucesso!');
    }

    public function updateAttendance(Request $request, $id, $attendanceId)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $attendance = Attendance::where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->where('id', $attendanceId)
            ->firstOrFail();

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|string|max:150',
            'description' => 'required|string|max:5000',
        ]);

        $attendance->fill($validated);
        $attendance->save();

        return redirect()->back()->with('success', 'Atendimento atualizado.');
    }

    public function destroyAttendance($id, $attendanceId)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $attendance = Attendance::where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->where('id', $attendanceId)
            ->firstOrFail();

        $attendance->delete();

        return redirect()->back()->with('success', 'Atendimento removido.');
    }

    public function exportAttendanceCsv(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $from = $request->get('from');
        $to = $request->get('to');
        $type = trim((string) $request->get('type', ''));
        $q = trim((string) $request->get('q', ''));

        AuditDownload::log('Beneficiaries:Attendances', (int) $beneficiary->id, [
            'format' => 'csv',
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'q' => $q,
        ]);

        $filename = 'atendimentos-' . Str::slug($beneficiary->name) . '-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $beneficiary, $from, $to, $type, $q) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Tipo', 'Descrição', 'Registrado por']);

            $baseQ = Attendance::where('tenant_id', $tenantId)
                ->where('beneficiary_id', $beneficiary->id)
                ->with('user');

            if (!empty($from)) $baseQ->whereDate('date', '>=', $from);
            if (!empty($to)) $baseQ->whereDate('date', '<=', $to);
            if ($type !== '') $baseQ->where('type', $type);
            if ($q !== '') {
                $baseQ->where(function ($w) use ($q) {
                    $w->where('description', 'like', '%' . $q . '%')
                      ->orWhere('type', 'like', '%' . $q . '%');
                });
            }

            $baseQ->orderBy('date', 'desc')->orderBy('id', 'desc')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $a) {
                    fputcsv($out, [
                        optional($a->date)->format('Y-m-d'),
                        $a->type,
                        $a->description,
                        $a->user?->name ?? 'Sistema',
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function printAttendance(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $from = $request->get('from');
        $to = $request->get('to');
        $type = trim((string) $request->get('type', ''));
        $q = trim((string) $request->get('q', ''));

        $baseQ = Attendance::where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->with('user')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($from)) $baseQ->whereDate('date', '>=', $from);
        if (!empty($to)) $baseQ->whereDate('date', '<=', $to);
        if ($type !== '') $baseQ->where('type', $type);
        if ($q !== '') {
            $baseQ->where(function ($w) use ($q) {
                $w->where('description', 'like', '%' . $q . '%')
                  ->orWhere('type', 'like', '%' . $q . '%');
            });
        }

        $attendances = $baseQ->get();

        $tenant = auth()->user()->tenant;
        $orgName = mb_strtoupper($tenant?->brand_name ?: ($tenant?->name ?? 'Organização Social'));
        $generatedAt = now()->format('d/m/Y H:i');

        return view('ngo.beneficiaries.attendance_print', compact(
            'beneficiary',
            'attendances',
            'orgName',
            'generatedAt',
            'from',
            'to',
            'type',
            'q'
        ));
    }

    /**
     * CPF/NIS vem cifrado do banco (query builder pula o accessor do model).
     * Fallback pro valor cru cobre linhas legadas ainda em plaintext.
     */
    private function decryptPii(?string $value): ?string
    {
        if ($value === null || $value === '') return $value;
        try { return Crypt::decryptString($value); } catch (DecryptException) { return $value; }
    }
}
