<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Volunteer;
use App\Models\Project;
use App\Models\VolunteerCertificate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Support\AuditDownload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use Carbon\Carbon;

class HumanResourcesController extends Controller
{
    private function certAuthCode(int $tenantId, int $volunteerId, int $certId, int $timestamp): string
    {
        return strtoupper(substr(hash('sha256', $tenantId . '|' . $volunteerId . '|' . $certId . '|' . $timestamp), 0, 16));
    }

    private function certCandidateCodes(int $tenantId, int $volunteerId, int $certId, $issuedAt): array
    {
        try {
            $base = $issuedAt instanceof Carbon ? $issuedAt : Carbon::parse((string) $issuedAt, config('app.timezone'));
        } catch (\Throwable $e) {
            $base = now();
        }

        $candidatesTs = [];

        // Common representations
        $candidatesTs[] = $base->timestamp;
        $candidatesTs[] = $base->copy()->timezone('UTC')->timestamp;
        $candidatesTs[] = $base->copy()->startOfDay()->timestamp;
        $candidatesTs[] = $base->copy()->startOfDay()->timezone('UTC')->timestamp;

        // Tolerate timezone storage differences (+/- 18h)
        foreach (range(-18, 18) as $h) {
            $candidatesTs[] = $base->copy()->addHours($h)->timestamp;
        }

        $candidatesTs = array_values(array_unique($candidatesTs));

        $codes = [];
        foreach ($candidatesTs as $ts) {
            $codes[] = $this->certAuthCode($tenantId, $volunteerId, $certId, (int) $ts);
        }

        return array_values(array_unique($codes));
    }

    public function index()
    {
        $tenant_id = auth()->user()->tenant_id;
        
        $employees = Employee::where('tenant_id', $tenant_id)->get();
        $volunteers = Volunteer::where('tenant_id', $tenant_id)->get();
        $projects = Project::where('tenant_id', $tenant_id)->get();

        $certByVolunteer = collect();
        $certCodes = [];
        if ($volunteers->count() > 0) {
            $certs = VolunteerCertificate::whereIn('volunteer_id', $volunteers->pluck('id')->all())
                ->orderBy('issued_at', 'desc')
                ->get();
            $certByVolunteer = $certs->groupBy('volunteer_id');

            foreach ($certs as $c) {
                try {
                    $issued = $c->issued_at ? Carbon::parse($c->issued_at) : now();
                    $code = $this->certAuthCode((int) $tenant_id, (int) $c->volunteer_id, (int) $c->id, (int) $issued->timestamp);
                    $certCodes[(int) $c->id] = $code;
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        $stats = [
            'employees_count' => $employees->count(),
            'volunteers_count' => $volunteers->count(),
            'monthly_payroll' => (float) $employees->sum('salary'),
            'avg_salary' => (float) ($employees->count() > 0 ? ($employees->avg('salary') ?? 0) : 0),
        ];

        return view('ngo.hr.index', compact('employees', 'volunteers', 'projects', 'stats', 'certByVolunteer', 'certCodes'));
    }

    public function storeEmployee(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $data = $request->all();
        
        // Sanitização de Moeda
        if (isset($data['salary'])) {
             $data['salary'] = str_replace('.', '', $data['salary']);
             $data['salary'] = str_replace(',', '.', $data['salary']);
        }

        $validated = \Illuminate\Support\Facades\Validator::make($data, [
            'name' => 'required|string',
            'position' => 'required|string',
            'contract_type' => 'required|in:clt,pj,trainee,temporary',
            'salary' => 'required|numeric',
            'work_hours_weekly' => 'required|string',
            'hired_at' => 'required|date',
            'project_id' => [
                'nullable',
                Rule::exists('projects', 'id')->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                }),
            ],
        ])->validate();

        $employee = new Employee($validated);
        $employee->tenant_id = $tenantId;
        $employee->save();

        return redirect()->back()->with('success', 'Funcionário cadastrado com sucesso!');
    }

    public function storeVolunteer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'skills' => 'nullable|string',
            'availability' => 'nullable|in:morning,afternoon,night,weekends',
        ]);

        $volunteer = new Volunteer($validated);
        $volunteer->tenant_id = auth()->user()->tenant_id;
        $volunteer->save();

        return redirect()->back()->with('success', 'Voluntário cadastrado com sucesso!');
    }

    public function exportEmployeesCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $q = trim((string) $request->get('q', ''));

        $filename = 'rh-funcionarios-' . date('Y-m-d_His') . '.csv';

        AuditDownload::log('HR:Employees', null, [
            'format' => 'csv',
            'q' => $q,
        ]);

        return response()->streamDownload(function () use ($tenantId, $q) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'Cargo', 'Contrato', 'Salário', 'Carga Horária', 'Admissão', 'Projeto', 'Status']);

            $query = Employee::where('tenant_id', $tenantId)->orderBy('name');
            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', '%' . $q . '%')
                      ->orWhere('position', 'like', '%' . $q . '%')
                      ->orWhere('contract_type', 'like', '%' . $q . '%');
                });
            }

            $projects = Project::where('tenant_id', $tenantId)->get(['id', 'name'])->keyBy('id');

            $query->chunk(500, function ($rows) use ($out, $projects) {
                foreach ($rows as $e) {
                    $proj = $e->project_id ? ($projects[$e->project_id]->name ?? ('Projeto #' . $e->project_id)) : '';
                    fputcsv($out, [
                        $e->name,
                        $e->position,
                        $e->contract_type,
                        $e->salary,
                        $e->work_hours_weekly,
                        optional($e->hired_at)->format('Y-m-d'),
                        $proj,
                        $e->status,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportVolunteersCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $q = trim((string) $request->get('q', ''));

        $filename = 'rh-voluntarios-' . date('Y-m-d_His') . '.csv';

        AuditDownload::log('HR:Volunteers', null, [
            'format' => 'csv',
            'q' => $q,
        ]);

        return response()->streamDownload(function () use ($tenantId, $q) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'E-mail', 'Telefone', 'Habilidades', 'Disponibilidade', 'Criado em']);

            $query = Volunteer::where('tenant_id', $tenantId)->orderBy('name');
            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', '%' . $q . '%')
                      ->orWhere('email', 'like', '%' . $q . '%')
                      ->orWhere('skills', 'like', '%' . $q . '%');
                });
            }

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $v) {
                    fputcsv($out, [
                        $v->name,
                        $v->email,
                        $v->phone,
                        $v->skills,
                        $v->availability,
                        optional($v->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function payrollPdf(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $month = (int) $request->get('month', (int) date('n'));
        $year = (int) $request->get('year', (int) date('Y'));
        $status = trim((string) $request->get('status', 'active')); // active|vacation|terminated|all
        $includeBonus = (string) $request->get('include_bonus', '1') !== '0';

        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2000 || $year > ((int) date('Y') + 2)) {
            $year = (int) date('Y');
        }

        AuditDownload::log('HR:Payroll', null, [
            'format' => 'pdf',
            'month' => $month,
            'year' => $year,
            'status' => $status,
            'include_bonus' => $includeBonus ? 1 : 0,
        ]);

        $employeesQ = Employee::where('tenant_id', $tenantId)->orderBy('name');
        if ($status !== '' && $status !== 'all') {
            $employeesQ->where('status', $status);
        }
        $employees = $employeesQ->get();

        $projects = Project::where('tenant_id', $tenantId)->get(['id', 'name'])->keyBy('id');

        $sumSalary = (float) $employees->sum('salary');
        $sumBonus = $includeBonus ? (float) $employees->sum('bonus') : 0.0;
        $sumTotal = $sumSalary + $sumBonus;

        $byProject = $employees->groupBy(function ($e) {
            return $e->project_id ?: 0;
        })->map(function ($rows) use ($includeBonus) {
            $salary = (float) $rows->sum('salary');
            $bonus = $includeBonus ? (float) $rows->sum('bonus') : 0.0;
            return [
                'count' => (int) $rows->count(),
                'salary' => $salary,
                'bonus' => $bonus,
                'total' => $salary + $bonus,
            ];
        })->sortByDesc('total');

        $orgName = (auth()->user()->tenant_id == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $generatedAt = now()->format('d/m/Y H:i');
        $referenceLabel = str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '/' . $year;

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.hr.payroll_pdf', compact(
            'employees',
            'projects',
            'month',
            'year',
            'status',
            'includeBonus',
            'sumSalary',
            'sumBonus',
            'sumTotal',
            'byProject',
            'orgName',
            'generatedAt',
            'referenceLabel'
        ));

        $filename = 'folha-' . $referenceLabel . '-' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    public function issueVolunteerCertificate(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $volunteer = Volunteer::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'activity_description' => 'required|string|max:255',
            'hours' => 'required|integer|min:1|max:1000',
            'issued_at' => 'nullable|date',
        ]);

        $issuedAt = !empty($validated['issued_at']) ? \Carbon\Carbon::parse($validated['issued_at']) : now();

        $cert = VolunteerCertificate::create([
            'volunteer_id' => $volunteer->id,
            'activity_description' => $validated['activity_description'],
            'hours' => (int) $validated['hours'],
            'issued_at' => $issuedAt,
            'file_path' => null,
        ]);

        $orgName = ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $generatedAt = now()->format('d/m/Y H:i');
        $certificateNo = 'VOL-' . $cert->id . '-' . $issuedAt->format('Y');
        $authCode = strtoupper(substr(hash('sha256', $tenantId . '|' . $volunteer->id . '|' . $cert->id . '|' . $issuedAt->timestamp), 0, 16));

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.hr.volunteer_certificate_pdf', compact(
            'volunteer',
            'cert',
            'orgName',
            'generatedAt',
            'certificateNo',
            'authCode'
        ));

        $output = $pdf->output();

        // Save to storage for historical record (optional but professional)
        try {
            $dir = 'volunteer-certificates/' . $tenantId . '/' . $volunteer->id;
            $filenameBase = 'cert-' . $cert->id . '-' . Str::slug($volunteer->name) . '.pdf';
            $path = $dir . '/' . $filenameBase;
            Storage::disk('public')->put($path, $output);
            $cert->file_path = $path;
            $cert->save();
        } catch (\Throwable $e) {
            // Do not block certificate issuance if storage fails.
        }

        AuditDownload::log('HR:VolunteerCertificate', (int) $cert->id, [
            'format' => 'pdf',
            'volunteer_id' => (int) $volunteer->id,
            'hours' => (int) $cert->hours,
        ]);

        $downloadName = 'certificado-voluntariado-' . Str::slug($volunteer->name) . '-' . date('Y-m-d_His') . '.pdf';
        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
        ]);
    }

    public function downloadVolunteerCertificate($id)
    {
        $tenantId = auth()->user()->tenant_id;

        $cert = VolunteerCertificate::findOrFail($id);
        $volunteer = Volunteer::where('tenant_id', $tenantId)->findOrFail($cert->volunteer_id);

        $downloadName = 'certificado-voluntariado-' . Str::slug($volunteer->name) . '-cert-' . $cert->id . '.pdf';

        if (!empty($cert->file_path) && Storage::disk('public')->exists($cert->file_path)) {
            AuditDownload::log('HR:VolunteerCertificate', (int) $cert->id, [
                'format' => 'pdf',
                'volunteer_id' => (int) $volunteer->id,
                'source' => 'storage',
            ]);

            return Storage::disk('public')->download($cert->file_path, $downloadName, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // Fallback: regenerate if missing.
        $issuedAt = $cert->issued_at ? \Carbon\Carbon::parse($cert->issued_at) : now();
        $orgName = ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $generatedAt = now()->format('d/m/Y H:i');
        $certificateNo = 'VOL-' . $cert->id . '-' . $issuedAt->format('Y');
        $authCode = strtoupper(substr(hash('sha256', $tenantId . '|' . $volunteer->id . '|' . $cert->id . '|' . $issuedAt->timestamp), 0, 16));

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.hr.volunteer_certificate_pdf', compact(
            'volunteer',
            'cert',
            'orgName',
            'generatedAt',
            'certificateNo',
            'authCode'
        ));

        $output = $pdf->output();

        try {
            $dir = 'volunteer-certificates/' . $tenantId . '/' . $volunteer->id;
            $filenameBase = 'cert-' . $cert->id . '-' . Str::slug($volunteer->name) . '.pdf';
            $path = $dir . '/' . $filenameBase;
            Storage::disk('public')->put($path, $output);
            $cert->file_path = $path;
            $cert->save();
        } catch (\Throwable $e) {
            // Ignore storage failures
        }

        AuditDownload::log('HR:VolunteerCertificate', (int) $cert->id, [
            'format' => 'pdf',
            'volunteer_id' => (int) $volunteer->id,
            'source' => 'regenerated',
        ]);

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
        ]);
    }

    public function certificatesIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $q = trim((string) $request->get('q', ''));
        $volunteerId = $request->get('volunteer_id');
        $from = $request->get('from');
        $to = $request->get('to');

        $certsQ = VolunteerCertificate::query()
            ->join('volunteers as v', 'v.id', '=', 'volunteer_certificates.volunteer_id')
            ->where('v.tenant_id', $tenantId)
            ->select([
                'volunteer_certificates.*',
                'v.name as volunteer_name',
                'v.email as volunteer_email',
                'v.phone as volunteer_phone',
            ])
            ->orderByDesc('volunteer_certificates.issued_at')
            ->orderByDesc('volunteer_certificates.id');

        if ($q !== '') {
            $certsQ->where(function ($w) use ($q) {
                $w->where('volunteer_certificates.activity_description', 'like', '%' . $q . '%')
                  ->orWhere('v.name', 'like', '%' . $q . '%')
                  ->orWhere('v.email', 'like', '%' . $q . '%');
            });
        }

        if (!empty($volunteerId)) {
            $certsQ->where('volunteer_certificates.volunteer_id', (int) $volunteerId);
        }

        if (!empty($from)) {
            $certsQ->whereDate('volunteer_certificates.issued_at', '>=', $from);
        }
        if (!empty($to)) {
            $certsQ->whereDate('volunteer_certificates.issued_at', '<=', $to);
        }

        $certs = $certsQ->paginate(20)->appends($request->query());

        $volunteers = Volunteer::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        $validateLinks = [];
        foreach ($certs as $c) {
            try {
                $issued = $c->issued_at instanceof Carbon ? $c->issued_at : Carbon::parse((string) $c->issued_at, config('app.timezone'));
                $code = $this->certAuthCode((int) $tenantId, (int) $c->volunteer_id, (int) $c->id, (int) $issued->timestamp);
                $validateLinks[(int) $c->id] = url('/validar-certificado/' . (int) $c->id) . '?code=' . $code;
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return view('ngo.hr.certificates', compact('certs', 'volunteers', 'q', 'volunteerId', 'from', 'to', 'validateLinks'));
    }

    public function exportCertificatesCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $q = trim((string) $request->get('q', ''));
        $volunteerId = $request->get('volunteer_id');
        $from = $request->get('from');
        $to = $request->get('to');

        AuditDownload::log('HR:VolunteerCertificates', null, [
            'format' => 'csv',
            'q' => $q,
            'volunteer_id' => $volunteerId,
            'from' => $from,
            'to' => $to,
        ]);

        $filename = 'certificados-voluntariado-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $q, $volunteerId, $from, $to) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'Data Emissão', 'Voluntário', 'E-mail', 'Atividade', 'Horas', 'Arquivo (storage)']);

            $baseQ = VolunteerCertificate::query()
                ->join('volunteers as v', 'v.id', '=', 'volunteer_certificates.volunteer_id')
                ->where('v.tenant_id', $tenantId)
                ->select([
                    'volunteer_certificates.id',
                    'volunteer_certificates.issued_at',
                    'volunteer_certificates.activity_description',
                    'volunteer_certificates.hours',
                    'volunteer_certificates.file_path',
                    'v.name as volunteer_name',
                    'v.email as volunteer_email',
                ])
                ->orderByDesc('volunteer_certificates.id');

            if ($q !== '') {
                $baseQ->where(function ($w) use ($q) {
                    $w->where('volunteer_certificates.activity_description', 'like', '%' . $q . '%')
                      ->orWhere('v.name', 'like', '%' . $q . '%')
                      ->orWhere('v.email', 'like', '%' . $q . '%');
                });
            }
            if (!empty($volunteerId)) {
                $baseQ->where('volunteer_certificates.volunteer_id', (int) $volunteerId);
            }
            if (!empty($from)) {
                $baseQ->whereDate('volunteer_certificates.issued_at', '>=', $from);
            }
            if (!empty($to)) {
                $baseQ->whereDate('volunteer_certificates.issued_at', '<=', $to);
            }

            $baseQ->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $c) {
                    fputcsv($out, [
                        (int) $c->id,
                        $c->issued_at ? (string) $c->issued_at : '',
                        $c->volunteer_name ?? '',
                        $c->volunteer_email ?? '',
                        $c->activity_description ?? '',
                        (int) ($c->hours ?? 0),
                        $c->file_path ?? '',
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function publicValidateVolunteerCertificate(Request $request, $id)
    {
        $codeRaw = (string) $request->get('code', '');
        $code = strtoupper(trim($codeRaw));
        // Remove spaces / line breaks / punctuation from copied codes.
        $code = preg_replace('/[^A-Z0-9]/', '', $code) ?? '';

        $certRow = VolunteerCertificate::query()
            ->join('volunteers as v', 'v.id', '=', 'volunteer_certificates.volunteer_id')
            ->where('volunteer_certificates.id', (int) $id)
            ->select([
                'volunteer_certificates.*',
                'v.tenant_id as tenant_id',
                'v.name as volunteer_name',
            ])
            ->firstOrFail();

        $tenantId = (int) $certRow->tenant_id;
        $volunteerId = (int) $certRow->volunteer_id;
        $candidateCodes = $this->certCandidateCodes($tenantId, $volunteerId, (int) $certRow->id, $certRow->issued_at);
        $isValid = ($code !== '' && in_array($code, $candidateCodes, true));

        $issuedAtLocal = $certRow->issued_at instanceof Carbon ? $certRow->issued_at : Carbon::parse((string) $certRow->issued_at, config('app.timezone'));
        $certificateNo = 'VOL-' . (int) $certRow->id . '-' . $issuedAtLocal->format('Y');
        $orgName = ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';

        // Optional audit entry (public)
        try {
            AuditLog::create([
                'tenant_id' => $tenantId,
                'user_id' => null,
                'event' => 'validate',
                'auditable_type' => 'HR:VolunteerCertificate',
                'auditable_id' => (int) $certRow->id,
                'old_values' => null,
                'new_values' => [
                    'result' => $isValid ? 'valid' : 'invalid',
                ],
                'ip_address' => \Illuminate\Support\Facades\Request::ip(),
                'user_agent' => \Illuminate\Support\Facades\Request::userAgent(),
                'url' => \Illuminate\Support\Facades\Request::fullUrl(),
            ]);
        } catch (\Throwable $e) {
            // never block validation
        }

        return view('public.volunteer_certificate_validate', [
            'cert' => $certRow,
            'orgName' => $orgName,
            'certificateNo' => $certificateNo,
            'providedCode' => $code,
            'isValid' => $isValid,
        ]);
    }
}
