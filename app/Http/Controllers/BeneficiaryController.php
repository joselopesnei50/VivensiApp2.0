<?php

namespace App\Http\Controllers;

use App\Jobs\GeocodeAddressJob;
use App\Jobs\GeneratePdfJob;
use App\Models\Beneficiary;
use App\Models\Attendance;
use App\Models\GeneratedReport;
use App\Support\AuditDownload;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CRUD + import/export + insights do modulo /ngo/beneficiaries.
 *
 * Ate 2026-07-18 este controller tinha 1516 linhas e 28 metodos. Foi
 * quebrado em 4 controllers:
 *   - BeneficiaryController (aqui) — CRUD + import + insights + print + PDF + export
 *   - BeneficiaryReportController — annualReport + 8 variantes de export
 *   - BeneficiaryAttendanceController — atendimentos + printAttendance + exports
 *   - BeneficiaryFamilyController — familiares
 * Nenhuma logica mudou; sao movimentacoes literais + repointing de rotas.
 */
class BeneficiaryController extends Controller
{
    // Faixas etarias usadas nos filtros do index/print/export. Segunda posicao
    // null = sem teto (60+ = idoso). Buckets sao mais legiveis pro assistente
    // social que dois inputs numericos e evitam confusao (idade "17" tabela
    // como jovem ou adolescente?).
    public const AGE_BRACKETS = [
        'infantil'    => [0, 6],
        'crianca'     => [7, 14],
        'adolescente' => [15, 17],
        'jovem'       => [18, 29],
        'adulto'      => [30, 59],
        'idoso'       => [60, null],
    ];

    public const AGE_BRACKET_LABELS = [
        'infantil'    => 'Primeira infância (0-6)',
        'crianca'     => 'Criança (7-14)',
        'adolescente' => 'Adolescente (15-17)',
        'jovem'       => 'Jovem (18-29)',
        'adulto'      => 'Adulto (30-59)',
        'idoso'       => 'Idoso (60+)',
    ];

    public const GENDER_OPTIONS = [
        'masculino'            => 'Masculino',
        'feminino'             => 'Feminino',
        'nao_binario'          => 'Não-binário',
        'outro'                => 'Outro',
        'prefiro_nao_informar' => 'Prefiro não informar',
    ];

    public const EDUCATION_OPTIONS = [
        'sem_escolaridade'       => 'Sem escolaridade',
        'fundamental_incompleto' => 'Fundamental incompleto',
        'fundamental_completo'   => 'Fundamental completo',
        'medio_incompleto'       => 'Médio incompleto',
        'medio_completo'         => 'Médio completo',
        'superior_incompleto'    => 'Superior incompleto',
        'superior_completo'      => 'Superior completo',
        'pos_graduacao'          => 'Pós-graduação',
    ];

    public const NOVO_DIAS_OPTIONS = [
        7  => 'Novos últimos 7 dias',
        30 => 'Novos últimos 30 dias',
        90 => 'Novos últimos 90 dias',
    ];

    public function insights(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $from = $request->get('from');
        $to = $request->get('to');

        $fromDt = !empty($from) ? Carbon::parse($from)->startOfDay() : now()->subDays(90)->startOfDay();
        $toDt = !empty($to) ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        // KPI: beneficiaries
        $statsRows = DB::table('beneficiaries')
            ->where('tenant_id', $tenantId)
            ->select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->get();
        $totalBenef = (int) $statsRows->sum('c');
        $activeBenef = (int) ($statsRows->firstWhere('status', 'active')->c ?? 0);
        $inactiveBenef = (int) ($statsRows->firstWhere('status', 'inactive')->c ?? 0);
        $graduatedBenef = (int) ($statsRows->firstWhere('status', 'graduated')->c ?? 0);

        // KPI: attendances in range
        $attBase = DB::table('attendances')->where('tenant_id', $tenantId)->whereBetween('date', [$fromDt->toDateString(), $toDt->toDateString()]);
        $totalAttendances = (int) (clone $attBase)->count();
        $uniqueBeneficiariesAttended = (int) (clone $attBase)->distinct('beneficiary_id')->count('beneficiary_id');

        // Monthly series (last 12 months)
        $startMonth = now()->startOfMonth()->subMonths(11);
        $endMonth = now()->endOfMonth();
        $monthlyRows = DB::table('attendances')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$startMonth->toDateString(), $endMonth->toDateString()])
            ->select(DB::raw('YEAR(date) as y'), DB::raw('MONTH(date) as m'), DB::raw('COUNT(*) as c'))
            ->groupBy(DB::raw('YEAR(date)'), DB::raw('MONTH(date)'))
            ->orderBy('y')->orderBy('m')
            ->get();

        $monthly = [];
        $cursor = $startMonth->copy();
        while ($cursor <= $endMonth) {
            $key = $cursor->format('Y-m');
            $monthly[$key] = 0;
            $cursor->addMonth();
        }
        foreach ($monthlyRows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            if (array_key_exists($key, $monthly)) $monthly[$key] = (int) $r->c;
        }

        $topTypes = DB::table('attendances')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$fromDt->toDateString(), $toDt->toDateString()])
            ->select('type', DB::raw('COUNT(*) as c'))
            ->groupBy('type')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $topUsers = DB::table('attendances as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->where('a.tenant_id', $tenantId)
            ->whereBetween('a.date', [$fromDt->toDateString(), $toDt->toDateString()])
            ->select(DB::raw("COALESCE(u.name, 'Sistema') as name"), DB::raw('COUNT(*) as c'))
            ->groupBy(DB::raw("COALESCE(u.name, 'Sistema')"))
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $topFamilies = DB::table('attendances as a')
            ->join('beneficiaries as b', 'b.id', '=', 'a.beneficiary_id')
            ->where('a.tenant_id', $tenantId)
            ->where('b.tenant_id', $tenantId)
            ->whereBetween('a.date', [$fromDt->toDateString(), $toDt->toDateString()])
            ->select('b.id', 'b.name', 'b.status', DB::raw('COUNT(*) as c'))
            ->groupBy('b.id', 'b.name', 'b.status')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $kpis = compact(
            'totalBenef',
            'activeBenef',
            'inactiveBenef',
            'graduatedBenef',
            'totalAttendances',
            'uniqueBeneficiariesAttended'
        );

        return view('ngo.beneficiaries.insights', compact(
            'from',
            'to',
            'fromDt',
            'toDt',
            'kpis',
            'monthly',
            'topTypes',
            'topUsers',
            'topFamilies'
        ));
    }

    /**
     * Filtro de busca livre.
     *
     * Nome/telefone sao LIKE parcial. CPF/NIS estao cifrados (Crypt::encryptString
     * gera ciphertext nao-deterministico) — LIKE nunca casa. Store/update/import
     * normalizam pra digitos e populam *_bidx via hash_hmac; entao busca por
     * documento so funciona com match exato do blind index. Aceita 11 digitos
     * (com ou sem mascara) — CPF e NIS tem ambos 11 no Brasil.
     */
    private function applyBeneficiarySearch($query, string $q): void
    {
        $digits = (string) preg_replace('/\D+/', '', $q);
        $bidx = strlen($digits) === 11
            ? hash_hmac('sha256', $digits, config('app.key'))
            : null;

        $query->where(function ($w) use ($q, $bidx) {
            $w->where('name', 'like', '%' . $q . '%')
              ->orWhere('phone', 'like', '%' . $q . '%');
            if ($bidx !== null) {
                $w->orWhere('cpf_bidx', $bidx)
                  ->orWhere('nis_bidx', $bidx);
            }
        });
    }

    /**
     * Le os parametros de filtro da request. Usado por index/print/exportCsv
     * pra garantir mesma leitura de nomes de campo.
     */
    private function collectListFilters(Request $request): array
    {
        return [
            'q'           => trim((string) $request->get('q', '')),
            'status'      => trim((string) $request->get('status', '')),
            'gender'      => trim((string) $request->get('gender', '')),
            'education'   => trim((string) $request->get('education', '')),
            'age_bracket' => trim((string) $request->get('age_bracket', '')),
            'project_id'  => trim((string) $request->get('project_id', '')),
            'novo_dias'   => trim((string) $request->get('novo_dias', '')),
        ];
    }

    /**
     * Aplica todos os filtros do index (q + status + demograficos + projeto +
     * novo_dias) no query builder. Compartilhado entre index/print/exportCsv
     * pra garantir que a lista visivel e o export tenham o mesmo escopo.
     */
    private function applyBeneficiaryListFilters($query, array $inputs, int $tenantId): void
    {
        if (!empty($inputs['q'])) {
            $this->applyBeneficiarySearch($query, (string) $inputs['q']);
        }
        if (!empty($inputs['status'])) {
            $query->where('status', $inputs['status']);
        }
        if (!empty($inputs['gender']) && isset(self::GENDER_OPTIONS[$inputs['gender']])) {
            $query->where('gender', $inputs['gender']);
        }
        if (!empty($inputs['education']) && isset(self::EDUCATION_OPTIONS[$inputs['education']])) {
            $query->where('education', $inputs['education']);
        }
        if (!empty($inputs['age_bracket']) && isset(self::AGE_BRACKETS[$inputs['age_bracket']])) {
            [$minAge, $maxAge] = self::AGE_BRACKETS[$inputs['age_bracket']];
            // Idade >= min => nasceu ha ao menos min anos => birth_date <= hoje - min anos
            $query->where('birth_date', '<=', now()->subYears($minAge)->toDateString());
            if ($maxAge !== null) {
                // Idade <= max => nasceu apos hoje - (max+1) anos
                $query->where('birth_date', '>', now()->subYears($maxAge + 1)->toDateString());
            }
        }
        if (!empty($inputs['project_id'])) {
            $pid = (int) $inputs['project_id'];
            $query->whereIn('id', function ($sub) use ($pid, $tenantId) {
                $sub->select('beneficiary_id')
                    ->from('project_people')
                    ->where('tenant_id', $tenantId)
                    ->where('project_id', $pid)
                    ->whereNotNull('beneficiary_id');
            });
        }
        if (!empty($inputs['novo_dias'])) {
            $dias = (int) $inputs['novo_dias'];
            if ($dias > 0) {
                $query->where('created_at', '>=', now()->subDays($dias));
            }
        }
    }

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $filters  = $this->collectListFilters($request);

        $beneficiariesQ = Beneficiary::where('tenant_id', $tenantId)
            ->withCount('attendances')
            ->orderBy('name');

        $this->applyBeneficiaryListFilters($beneficiariesQ, $filters, $tenantId);

        $beneficiaries = $beneficiariesQ->paginate(15)->appends($request->query());

        $statsRows = DB::table('beneficiaries')
            ->where('tenant_id', $tenantId)
            ->select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->get();

        $total = (int) $statsRows->sum('c');
        $active = (int) ($statsRows->firstWhere('status', 'active')->c ?? 0);
        $inactive = (int) ($statsRows->firstWhere('status', 'inactive')->c ?? 0);
        $graduated = (int) ($statsRows->firstWhere('status', 'graduated')->c ?? 0);

        $monthAttendances = (int) DB::table('attendances')
            ->where('tenant_id', $tenantId)
            ->whereYear('date', date('Y'))
            ->whereMonth('date', date('m'))
            ->count();

        $stats = compact('total', 'active', 'inactive', 'graduated', 'monthAttendances');

        // Opcoes pros selects de filtro (projetos do proprio tenant + enums)
        $projectsForFilter = \App\Models\Project::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $filterOptions = [
            'genders'      => self::GENDER_OPTIONS,
            'educations'   => self::EDUCATION_OPTIONS,
            'ageBrackets'  => self::AGE_BRACKET_LABELS,
            'novoDias'     => self::NOVO_DIAS_OPTIONS,
            'projects'     => $projectsForFilter,
        ];

        // Alias q/status pra compatibilidade com o blade atual antes do refactor da view
        $q = $filters['q'];
        $status = $filters['status'];

        return view('ngo.beneficiaries.index', compact('beneficiaries', 'q', 'status', 'stats', 'filters', 'filterOptions'));
    }

    public function create()
    {
        return view('ngo.beneficiaries.create');
    }

    public function downloadImportTemplate()
    {
        $filename = 'modelo_importacao_familias.csv';
        $headers = [
            'Nome', 'NIS', 'CPF', 'Data_Nascimento', 'Genero', 'Raca_Cor', 'Escolaridade',
            'Telefone', 'CEP', 'Rua', 'Numero', 'Complemento', 'Bairro', 'Cidade', 'UF', 'Status',
        ];

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            // Example rows
            fputcsv($out, [
                'Maria da Silva', '12345678910', '123.456.789-10', '1985-05-20',
                'feminino', 'parda', 'medio_completo', '(11) 99999-9999',
                '01001-000', 'Rua Exemplo', '123', 'Casa B', 'Centro', 'São Paulo', 'SP', 'ativo',
            ]);
            fputcsv($out, [
                'João Pereira', '', '987.654.321-00', '20/03/1990',
                'masculino', 'prefiro_nao_informar', 'fundamental_completo', '(11) 98888-7777',
                '', '', '', '', '', 'Guarulhos', 'SP', 'ativo',
            ]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        // Check for BOM and skip
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        $columnMap = $this->buildImportColumnMap(is_array($header) ? $header : []);

        $successCount = 0;
        $errorCount = 0;
        $duplicatesUpdated = 0;
        $errors = [];
        $lineNum = 1; // linha 1 = cabecalho

        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;
            if (empty(array_filter($row))) continue;

            $get = fn (string $field) => trim((string) ($row[$columnMap[$field] ?? -1] ?? ''));

            $name = $get('name');
            $nis  = preg_replace('/\D+/', '', $get('nis'));
            $cpf  = preg_replace('/\D+/', '', $get('cpf'));

            if (empty($name)) {
                $errorCount++;
                $errors[] = "Linha {$lineNum}: Nome é obrigatório.";
                continue;
            }

            try {
                // Duplicate detection logic: Search by CPF or NIS within the same tenant
                $existing = null;
                if (!empty($cpf)) {
                    $existing = Beneficiary::where('tenant_id', $tenantId)->where('cpf_bidx', hash_hmac('sha256', $cpf, config('app.key')))->first();
                }
                if (!$existing && !empty($nis)) {
                    $existing = Beneficiary::where('tenant_id', $tenantId)->where('nis_bidx', hash_hmac('sha256', $nis, config('app.key')))->first();
                }

                $beneficiaryData = [
                    'name'       => $name,
                    'nis'        => $nis ?: null,
                    'cpf'        => $cpf ?: null,
                    'birth_date' => $this->parseImportDate($get('birth_date'), $lineNum, $errors),
                    'gender'     => $this->normalizeImportEnum($get('gender'), ['masculino', 'feminino', 'nao_binario', 'outro', 'prefiro_nao_informar'], 'Gênero', $lineNum, $errors),
                    'race_color' => $this->normalizeImportEnum($get('race_color'), ['branca', 'preta', 'parda', 'amarela', 'indigena', 'prefiro_nao_informar'], 'Raça/Cor', $lineNum, $errors),
                    'education'  => $this->normalizeImportEnum($get('education'), ['sem_escolaridade', 'fundamental_incompleto', 'fundamental_completo', 'medio_incompleto', 'medio_completo', 'superior_incompleto', 'superior_completo', 'pos_graduacao'], 'Escolaridade', $lineNum, $errors),
                    'phone'      => $get('phone') ?: null,
                    'address_zip'          => mb_substr($get('address_zip'), 0, 10) ?: null,
                    'address_street'       => $get('address_street') ?: null,
                    'address_number'       => mb_substr($get('address_number'), 0, 20) ?: null,
                    'address_complement'   => mb_substr($get('address_complement'), 0, 100) ?: null,
                    'address_neighborhood' => $get('address_neighborhood') ?: null,
                    'address_city'         => $get('address_city') ?: null,
                    'address_state'        => mb_substr(mb_strtoupper($get('address_state')), 0, 2) ?: null,
                    'status'     => $this->normalizeImportStatus($get('status')),
                    'tenant_id'  => $tenantId,
                ];

                // Endereco: campos estruturados compoem o texto (composeAddress
                // devolve "Brasil" mesmo vazio — so chamar se houver parte real);
                // planilha antiga cai na coluna unica. Geocodificacao fica por
                // conta do BeneficiaryObserver::saved() quando address muda.
                $hasStructured = $beneficiaryData['address_street'] || $beneficiaryData['address_city']
                    || $beneficiaryData['address_zip'] || $beneficiaryData['address_neighborhood'];
                $beneficiaryData['address'] = $hasStructured
                    ? $this->composeAddress($beneficiaryData)
                    : ($get('address') ?: null);

                if ($existing) {
                    $existing->update($beneficiaryData);
                    $duplicatesUpdated++;
                } else {
                    Beneficiary::create($beneficiaryData);
                    $successCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Import beneficiary error for '{$name}'", ['error' => $e->getMessage()]);
                $errors[] = "Linha {$lineNum}: erro ao salvar '{$name}' — verifique os dados e tente novamente.";
            }
        }

        fclose($handle);

        $msg = "Importação concluída: $successCount novos cadastros, $duplicatesUpdated duplicatas atualizadas.";
        if ($errorCount > 0 || count($errors) > 0) {
            $msg .= $errorCount > 0 ? " Houve $errorCount falhas." : ' Alguns campos foram ignorados (veja avisos).';
            return redirect()->back()->with('warning', $msg)->with('import_errors', $errors);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Mapeia cabecalho da planilha -> campo do model (aceita o modelo novo de
     * 16 colunas e o legado de 7). Sem cabecalho reconhecivel, cai no
     * posicional legado: Nome, NIS, CPF, Nascimento, Telefone, Endereco, Status.
     */
    private function buildImportColumnMap(array $header): array
    {
        $aliases = [
            'nome' => 'name',
            'nis'  => 'nis',
            'cpf'  => 'cpf',
            'data_nascimento' => 'birth_date', 'data_de_nascimento' => 'birth_date', 'nascimento' => 'birth_date',
            'genero' => 'gender', 'sexo' => 'gender',
            'raca_cor' => 'race_color', 'raca' => 'race_color', 'cor' => 'race_color',
            'escolaridade' => 'education',
            'telefone' => 'phone', 'celular' => 'phone', 'fone' => 'phone',
            'endereco' => 'address',
            'cep' => 'address_zip',
            'rua' => 'address_street', 'logradouro' => 'address_street',
            'numero' => 'address_number',
            'complemento' => 'address_complement',
            'bairro' => 'address_neighborhood',
            'cidade' => 'address_city', 'municipio' => 'address_city',
            'uf' => 'address_state', 'estado' => 'address_state',
            'status' => 'status', 'situacao' => 'status',
        ];

        $map = [];
        foreach ($header as $index => $label) {
            $key = $this->normalizeImportKey((string) $label);
            if (isset($aliases[$key]) && !isset($map[$aliases[$key]])) {
                $map[$aliases[$key]] = $index;
            }
        }

        if (!isset($map['name'])) {
            return ['name' => 0, 'nis' => 1, 'cpf' => 2, 'birth_date' => 3, 'phone' => 4, 'address' => 5, 'status' => 6];
        }

        return $map;
    }

    private function normalizeImportKey(string $value): string
    {
        $key = mb_strtolower(trim(Str::ascii($value)));
        return trim(preg_replace('/[^a-z0-9]+/', '_', $key), '_');
    }

    private function parseImportDate(string $value, int $lineNum, array &$errors): ?string
    {
        if ($value === '') return null;

        try {
            // Formato BR — checkdate evita overflow silencioso (31/02 viraria 02/03).
            if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
                if (!checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                    throw new \InvalidArgumentException("data inexistente: {$value}");
                }
                return Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->toDateString();
            }
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            $errors[] = "Linha {$lineNum}: data de nascimento '{$value}' inválida — campo ignorado.";
            return null;
        }
    }

    private function normalizeImportEnum(string $value, array $allowed, string $label, int $lineNum, array &$errors): ?string
    {
        if ($value === '') return null;

        $normalized = $this->normalizeImportKey($value);
        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        $errors[] = "Linha {$lineNum}: valor '{$value}' inválido para {$label} — campo ignorado.";
        return null;
    }

    private function normalizeImportStatus(string $value): string
    {
        $map = ['ativo' => 'active', 'inativo' => 'inactive', 'graduado' => 'graduated', 'egresso' => 'graduated'];
        $normalized = $this->normalizeImportKey($value);
        $normalized = $map[$normalized] ?? $normalized;

        return in_array($normalized, ['active', 'inactive', 'graduated'], true) ? $normalized : 'active';
    }

    public function show(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $beneficiary = Beneficiary::where('tenant_id', $tenantId)
                                  ->where('id', $id)
                                  ->with([
                                      'familyMembers',
                                      'projectMemberships.project:id,name,status',
                                      // So matriculas ativas — 'saiu'/'concluido' viram historico, nao clutter na tela
                                      'projectMemberships.enrollments' => fn ($q) =>
                                          $q->where('status', \App\Models\ProjectClassEnrollment::STATUS_ATIVO)
                                            ->with('projectClass:id,name,status'),
                                  ])
                                  ->firstOrFail();

        $from = $request->get('from');
        $to = $request->get('to');
        $type = trim((string) $request->get('type', ''));
        $q = trim((string) $request->get('q', ''));

        $attendancesQ = Attendance::where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->with('user');

        if (!empty($from)) $attendancesQ->whereDate('date', '>=', $from);
        if (!empty($to)) $attendancesQ->whereDate('date', '<=', $to);
        if ($type !== '') $attendancesQ->where('type', $type);
        if ($q !== '') {
            $attendancesQ->where(function ($w) use ($q) {
                $w->where('description', 'like', '%' . $q . '%')
                  ->orWhere('type', 'like', '%' . $q . '%');
            });
        }

        $attendances = $attendancesQ
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->appends($request->query());

        $stats = [
            'attendances_total' => (int) Attendance::where('tenant_id', $tenantId)->where('beneficiary_id', $beneficiary->id)->count(),
            'last_attendance_at' => Attendance::where('tenant_id', $tenantId)->where('beneficiary_id', $beneficiary->id)->max('date'),
        ];

        $types = Attendance::where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->all();

        // Historico de matriculas (status = saiu | concluido). Reaproveita
        // os ids ja carregados em projectMemberships pra evitar subselect
        // whereHas — 2 queries no pior caso (uma ja feita no eager load).
        $personIds = $beneficiary->projectMemberships->pluck('id');
        $historicoMatriculas = $personIds->isEmpty()
            ? collect()
            : \App\Models\ProjectClassEnrollment::where('tenant_id', $tenantId)
                ->whereIn('project_person_id', $personIds)
                ->whereIn('status', [
                    \App\Models\ProjectClassEnrollment::STATUS_SAIU,
                    \App\Models\ProjectClassEnrollment::STATUS_CONCLUIDO,
                ])
                ->with(['projectClass:id,name,project_id', 'projectClass.project:id,name'])
                ->orderByDesc('unenrolled_at')
                ->orderByDesc('enrolled_at')
                ->get();

        return view('ngo.beneficiaries.show', compact('beneficiary', 'attendances', 'stats', 'from', 'to', 'type', 'q', 'types', 'historicoMatriculas'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        if (isset($data['cpf'])) $data['cpf'] = preg_replace('/\D+/', '', (string) $data['cpf']);
        if (isset($data['nis'])) $data['nis'] = preg_replace('/\D+/', '', (string) $data['nis']);
        if (isset($data['phone'])) $data['phone'] = trim((string) $data['phone']);

        $tenantId = auth()->user()->tenant_id;
        $validated = \Illuminate\Support\Facades\Validator::make($data, [
            'name' => 'required|string|max:255',
            'cpf' => [
                'nullable', 'string', 'max:20',
                function ($attribute, $value, $fail) use ($tenantId) {
                    if (!empty($value) && Beneficiary::where('tenant_id', $tenantId)->where('cpf_bidx', hash_hmac('sha256', $value, config('app.key')))->exists()) {
                        $fail('Este CPF já está cadastrado para outro beneficiário.');
                    }
                },
            ],
            'nis' => [
                'nullable', 'string', 'max:30',
                function ($attribute, $value, $fail) use ($tenantId) {
                    if (!empty($value) && Beneficiary::where('tenant_id', $tenantId)->where('nis_bidx', hash_hmac('sha256', $value, config('app.key')))->exists()) {
                        $fail('Este NIS já está cadastrado para outro beneficiário.');
                    }
                },
            ],
            'birth_date'  => 'nullable|date',
            'gender'      => 'nullable|in:masculino,feminino,nao_binario,outro,prefiro_nao_informar',
            'race_color'  => 'nullable|in:branca,preta,parda,amarela,indigena,prefiro_nao_informar',
            'education'   => 'nullable|in:sem_escolaridade,fundamental_incompleto,fundamental_completo,medio_incompleto,medio_completo,superior_incompleto,superior_completo,pos_graduacao',
            'phone'                => 'nullable|string|max:60',
            'address'              => 'nullable|string|max:255',
            'address_zip'          => 'nullable|string|max:10',
            'address_street'       => 'nullable|string|max:255',
            'address_number'       => 'nullable|string|max:20',
            'address_complement'   => 'nullable|string|max:100',
            'address_neighborhood' => 'nullable|string|max:255',
            'address_city'         => 'nullable|string|max:255',
            'address_state'        => 'nullable|string|max:2',
            'status'               => 'nullable|in:active,inactive,graduated',
        ])->validate();

        $validated['address'] = $this->composeAddress($validated);

        $beneficiary = new Beneficiary($validated);
        $beneficiary->tenant_id = $tenantId;
        $beneficiary->save();

        if (!empty($beneficiary->address)) {
            GeocodeAddressJob::dispatch($beneficiary)->delay(now()->addSeconds(3));
        }

        return redirect('/ngo/beneficiaries')->with('success', 'Beneficiário cadastrado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $data = $request->all();
        if (isset($data['cpf'])) $data['cpf'] = preg_replace('/\D+/', '', (string) $data['cpf']);
        if (isset($data['nis'])) $data['nis'] = preg_replace('/\D+/', '', (string) $data['nis']);
        if (isset($data['phone'])) $data['phone'] = trim((string) $data['phone']);

        $validated = \Illuminate\Support\Facades\Validator::make($data, [
            'name' => 'required|string|max:255',
            'cpf' => [
                'nullable', 'string', 'max:20',
                function ($attribute, $value, $fail) use ($tenantId, $beneficiary) {
                    if (!empty($value) && Beneficiary::where('tenant_id', $tenantId)->where('cpf_bidx', hash_hmac('sha256', $value, config('app.key')))->where('id', '!=', $beneficiary->id)->exists()) {
                        $fail('Este CPF já está cadastrado para outro beneficiário.');
                    }
                },
            ],
            'nis' => [
                'nullable', 'string', 'max:30',
                function ($attribute, $value, $fail) use ($tenantId, $beneficiary) {
                    if (!empty($value) && Beneficiary::where('tenant_id', $tenantId)->where('nis_bidx', hash_hmac('sha256', $value, config('app.key')))->where('id', '!=', $beneficiary->id)->exists()) {
                        $fail('Este NIS já está cadastrado para outro beneficiário.');
                    }
                },
            ],
            'birth_date'  => 'nullable|date',
            'gender'      => 'nullable|in:masculino,feminino,nao_binario,outro,prefiro_nao_informar',
            'race_color'  => 'nullable|in:branca,preta,parda,amarela,indigena,prefiro_nao_informar',
            'education'   => 'nullable|in:sem_escolaridade,fundamental_incompleto,fundamental_completo,medio_incompleto,medio_completo,superior_incompleto,superior_completo,pos_graduacao',
            'phone'                => 'nullable|string|max:60',
            'address'              => 'nullable|string|max:255',
            'address_zip'          => 'nullable|string|max:10',
            'address_street'       => 'nullable|string|max:255',
            'address_number'       => 'nullable|string|max:20',
            'address_complement'   => 'nullable|string|max:100',
            'address_neighborhood' => 'nullable|string|max:255',
            'address_city'         => 'nullable|string|max:255',
            'address_state'        => 'nullable|string|max:2',
            'status'               => 'required|in:active,inactive,graduated',
        ])->validate();

        $oldAddress = $beneficiary->address;
        $validated['address'] = $this->composeAddress($validated);
        $beneficiary->fill($validated);
        $beneficiary->save();

        try {
            if (!empty($beneficiary->address) && $beneficiary->address !== $oldAddress) {
                $beneficiary->update(['latitude' => null, 'longitude' => null]);
                GeocodeAddressJob::dispatch($beneficiary->fresh())->delay(now()->addSeconds(3));
            }
        } catch (\Throwable $e) {
            Log::warning('Geocode dispatch failed on beneficiary update: ' . $e->getMessage(), ['id' => $beneficiary->id]);
        }

        return redirect()->back()->with('success', 'Cadastro atualizado.');
    }

    public function destroy($id)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();
        $beneficiary->delete();
        return redirect('/ngo/beneficiaries')->with('success', 'Beneficiário removido.');
    }

    /**
     * Bulk actions do index: alterar status ou remover em lote.
     *
     * ids[] IDs sao filtrados por tenant antes de qualquer escrita (defesa-em
     * -profundidade contra IDOR mesmo com CSRF ok). Delete percorre um a um
     * pra disparar o hook Booted::deleting no Beneficiary (nullifica
     * ProjectPerson.beneficiary_id — nao dependemos so do FK ON DELETE
     * porque SQLite in-memory pula quando a coluna foi ALTERada).
     *
     * Limite de 500 IDs por request bate com o chunk() do exportCsv — pra
     * lotes maiores o fluxo esperado e CSV + reimport, nao bulk UI.
     */
    public function bulk(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'action' => 'required|in:status,delete',
            'value'  => 'required_if:action,status|nullable|in:active,inactive,graduated',
            'ids'    => 'required|array|min:1|max:500',
            'ids.*'  => 'integer|min:1',
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));

        $found = Beneficiary::where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        if (empty($found)) {
            return redirect()->back()->with('error', 'Nenhum beneficiário válido selecionado.');
        }

        if ($validated['action'] === 'status') {
            Beneficiary::where('tenant_id', $tenantId)
                ->whereIn('id', $found)
                ->update(['status' => $validated['value']]);

            Log::info('NGO Beneficiaries bulk status', [
                'tenant_id' => $tenantId,
                'value'     => $validated['value'],
                'count'     => count($found),
                'by'        => auth()->id(),
            ]);

            $labelMap = ['active' => 'Ativo', 'inactive' => 'Inativo', 'graduated' => 'Graduado'];
            $label = $labelMap[$validated['value']] ?? $validated['value'];

            return redirect()->back()->with('success', count($found) . ' beneficiário(s) marcado(s) como ' . $label . '.');
        }

        // delete: percorre um a um pra disparar hook deleting
        Beneficiary::where('tenant_id', $tenantId)
            ->whereIn('id', $found)
            ->get()
            ->each
            ->delete();

        Log::info('NGO Beneficiaries bulk delete', [
            'tenant_id' => $tenantId,
            'count'     => count($found),
            'by'        => auth()->id(),
        ]);

        return redirect()->back()->with('success', count($found) . ' beneficiário(s) removido(s).');
    }

    public function pdf(Request $request, $id)
    {
        $tenantId    = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $from = $request->get('from');
        $to   = $request->get('to');
        $type = trim((string) $request->get('type', ''));
        $q    = trim((string) $request->get('q', ''));

        AuditDownload::log('Beneficiaries:Term', (int) $beneficiary->id, [
            'format' => 'pdf', 'from' => $from, 'to' => $to, 'type' => $type, 'q' => $q,
        ]);

        $report = GeneratedReport::create([
            'tenant_id' => $tenantId,
            'user_id'   => auth()->id(),
            'type'      => 'beneficiary_term',
            'params'    => [
                'tenant_id'      => $tenantId,
                'beneficiary_id' => (int) $beneficiary->id,
                'from'           => $from,
                'to'             => $to,
                'type'           => $type,
                'q'              => $q,
                'org_name'       => ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL',
                'emitter'        => auth()->user()->name ?? '—',
            ],
        ]);

        GeneratePdfJob::dispatch($report->id);

        return response()->json(['report_id' => $report->id, 'status_url' => route('reports.status', $report->id)]);
    }

    public function exportCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $filters = $this->collectListFilters($request);

        AuditDownload::log('Beneficiaries', null, array_merge(['format' => 'csv'], $filters));

        $filename = 'beneficiarios-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $filters) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'NIS', 'CPF', 'Nascimento', 'Telefone', 'Endereço', 'Status', 'Atendimentos']);

            $baseQ = Beneficiary::where('tenant_id', $tenantId)->withCount('attendances')->orderBy('name');
            $this->applyBeneficiaryListFilters($baseQ, $filters, $tenantId);

            $baseQ->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $b) {
                    fputcsv($out, [
                        $b->name,
                        $b->nis,
                        $b->cpf,
                        optional($b->birth_date)->format('Y-m-d'),
                        $b->phone,
                        $b->address,
                        $b->status,
                        $b->attendances_count,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $filters = $this->collectListFilters($request);

        $beneficiariesQ = Beneficiary::where('tenant_id', $tenantId)->withCount('attendances')->orderBy('name');
        $this->applyBeneficiaryListFilters($beneficiariesQ, $filters, $tenantId);

        $beneficiaries = $beneficiariesQ->limit(500)->get();
        $truncated     = $beneficiaries->count() === 500;

        $orgName = ($tenantId == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL';
        $generatedAt = now()->format('d/m/Y H:i');

        // Alias q/status pro blade atual — o print.blade.php ainda le esses names
        $q      = $filters['q'];
        $status = $filters['status'];

        return view('ngo.beneficiaries.print', compact('beneficiaries', 'truncated', 'orgName', 'generatedAt', 'q', 'status'));
    }

    private function composeAddress(array $data): ?string
    {
        $street       = trim(($data['address_street'] ?? '') . ', ' . ($data['address_number'] ?? ''), ', ');
        $complement   = trim($data['address_complement'] ?? '');
        $neighborhood = trim($data['address_neighborhood'] ?? '');
        $city         = trim($data['address_city'] ?? '');
        $state        = trim($data['address_state'] ?? '');
        $zip          = trim($data['address_zip'] ?? '');

        $line1 = implode(', ', array_filter([$street, $complement, $neighborhood]));
        $line2 = implode(' - ', array_filter([$city, $state]));
        $full  = implode(', ', array_filter([$line1, $line2, $zip]));

        // Sem nenhuma parte real, devolve null — nunca "Brasil" sozinho
        // (gerava endereco fantasma + job de geocode inutil).
        return $full !== '' ? "{$full}, Brasil" : null;
    }
}
