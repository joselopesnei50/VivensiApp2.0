<?php

namespace App\Http\Controllers;

use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\ProjectPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PublicAttendanceController extends Controller
{
    // Tela publica de check-in via token. Sem auth/subscription — token e a
    // credencial. Espelha padrao de DonorPortalController::show.
    public function show(string $token)
    {
        $session = $this->resolveSession($token);

        if ($this->isExpired($session)) {
            return response()->view('public.attendance.expired', ['session' => null], 410);
        }

        // Modo fechada: aluno digita nome, autocomplete contra matriculados ativos.
        // Modo aberta: aluno cadastra proprio nome + telefone (obrigatorios).
        $students = [];
        if ($session->mode === 'fechada') {
            // Somente nomes — sem PII adicional exposta.
            $students = ProjectPerson::withoutGlobalScopes()
                ->where('project_id', $session->project_id)
                ->where('tenant_id', $session->tenant_id)
                ->where('enrollment_status', 'ativo')
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all();
        }

        return view('public.attendance.show', compact('session', 'token', 'students'));
    }

    public function checkin(Request $request, string $token)
    {
        $session = $this->resolveSession($token);

        if ($this->isExpired($session)) {
            return response()->view('public.attendance.expired', ['session' => null], 410);
        }

        // Rate limit adicional (belt-and-suspenders alem do throttle da rota):
        // 10 checkins por minuto por token+IP.
        $rlKey = 'public_checkin:' . sha1($token . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($rlKey, 10)) {
            return back()->withErrors(['rate' => 'Muitas tentativas. Aguarde alguns segundos e tente novamente.']);
        }
        RateLimiter::hit($rlKey, 60);

        $validated = $request->validate([
            'name'     => 'required|string|max:120',
            'phone'    => 'nullable|string|max:30',
            'consent'  => 'accepted',
        ]);

        $ipHash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));

        try {
            DB::transaction(function () use ($session, $validated, $ipHash) {
            if ($session->mode === 'fechada') {
                // Match case-insensitive contra matriculados ativos do projeto.
                $person = ProjectPerson::withoutGlobalScopes()
                    ->where('project_id', $session->project_id)
                    ->where('tenant_id', $session->tenant_id)
                    ->where('enrollment_status', 'ativo')
                    ->whereRaw('LOWER(name) = ?', [Str::lower(trim($validated['name']))])
                    ->first();

                if (!$person) {
                    abort(422, 'Aluno não encontrado na lista de matriculados. Confirme o nome com o professor.');
                }

                $via = 'link_publico';
            } else {
                // Modo aberta: dedup por nome (case-insensitive) — se o aluno
                // já se cadastrou em sessão anterior deste mesmo projeto,
                // reusa. Evita poluição do relatório com N cadastros do
                // mesmo aluno digitando o nome levemente diferente.
                $nameLower = Str::lower(trim($validated['name']));
                $person = ProjectPerson::withoutGlobalScopes()
                    ->where('project_id', $session->project_id)
                    ->where('tenant_id', $session->tenant_id)
                    ->where('enrollment_status', 'ativo')
                    ->whereRaw('LOWER(name) = ?', [$nameLower])
                    ->first();

                if (!$person) {
                    $person = ProjectPerson::withoutGlobalScopes()->create([
                        'tenant_id'         => $session->tenant_id,
                        'project_id'        => $session->project_id,
                        'name'              => trim($validated['name']),
                        'phone'             => $validated['phone'] ?? null,
                        'enrollment_status' => 'ativo',
                    ]);
                } elseif (!empty($validated['phone']) && empty($person->phone)) {
                    // Enriquece telefone se antes tinha faltado
                    $person->phone = $validated['phone'];
                    $person->save();
                }
                $via = 'auto_cadastro';
            }

                // Anti-reuso: unique(session, project_person) na DB previne
                // duplicata em race condition. Aqui checa antes pra dar UX
                // clara ("já registrado") em vez de update silencioso.
                $existing = ClassAttendance::withoutGlobalScopes()
                    ->where('class_session_id', $session->id)
                    ->where('project_person_id', $person->id)
                    ->exists();

                if ($existing) {
                    throw new \DomainException('ALREADY_REGISTERED');
                }

                ClassAttendance::withoutGlobalScopes()->create([
                    'class_session_id'  => $session->id,
                    'project_person_id' => $person->id,
                    'tenant_id'         => $session->tenant_id,
                    'status'            => 'presente',
                    'checked_in_via'    => $via,
                    'checked_in_at'     => now(),
                    'ip_hash'           => $ipHash,
                ]);
            });
        } catch (\DomainException $e) {
            if ($e->getMessage() === 'ALREADY_REGISTERED') {
                // Presença já está gravada — redireciona pra tela OK com flag
                return redirect("/chamada/{$token}/ok?ja_registrado=1");
            }
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            // Race condition raro: unique constraint disparou. Trata igual.
            if (str_contains($e->getMessage(), 'class_attendances_session_person_unique')
                || $e->getCode() === '23000'
            ) {
                return redirect("/chamada/{$token}/ok?ja_registrado=1");
            }
            throw $e;
        }

        return redirect("/chamada/{$token}/ok");
    }

    public function success(string $token, Request $request)
    {
        $session = $this->resolveSession($token);
        // Mesmo apos expirar exibe a tela de OK — o registro ja foi feito.
        return view('public.attendance.ok', [
            'session'     => $session,
            'token'       => $token,
            'jaRegistrado' => $request->boolean('ja_registrado'),
        ]);
    }

    private function resolveSession(string $token): ClassSession
    {
        $session = ClassSession::findByPublicToken($token);
        abort_if(!$session, 404);
        return $session;
    }

    private function isExpired(ClassSession $session): bool
    {
        if (!$session->public_token_bidx) {
            return true;
        }
        if ($session->public_enabled_until === null) {
            return false;
        }
        return $session->public_enabled_until->isPast();
    }
}
