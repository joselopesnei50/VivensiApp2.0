<?php

namespace App\Http\Controllers;

use App\Models\LgpdDataRequest;
use App\Models\LoginActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function edit()
    {
        $user           = auth()->user();
        $loginActivities = LoginActivity::where('user_id', $user->id)
            ->orderBy('logged_in_at', 'desc')
            ->limit(10)
            ->get();

        return view('profile.edit', compact('user', 'loginActivities'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }

    /**
     * Atualiza o tipo de negócio do tenant (mei/autônomo/PJ simples/outro).
     * Só usuários do painel common (role=common) veem essa opção — outros
     * roles (ngo/manager/super_admin) têm painel próprio e o campo é ignorado.
     */
    public function updateBusinessType(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'common') {
            abort(403);
        }

        $validated = $request->validate([
            'business_type' => ['required', 'in:mei,autonomo,pj_simples,outro'],
        ]);

        $tenant = \App\Models\Tenant::find($user->tenant_id);
        if (!$tenant) {
            return back()->withErrors(['business_type' => 'Tenant não encontrado.']);
        }

        $old = $tenant->business_type;
        $tenant->business_type = $validated['business_type'];
        $tenant->save();

        Log::info('Tenant business_type alterado', [
            'tenant_id' => $tenant->id,
            'user_id'   => $user->id,
            'de'        => $old,
            'para'      => $validated['business_type'],
        ]);

        return back()->with('success', 'Tipo de negócio atualizado. O painel foi ajustado às novas configurações.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) {
                if (!Hash::check($value, auth()->user()->password)) {
                    $fail('A senha atual está incorreta.');
                }
            }],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Senha alterada com sucesso!');
    }

    // ── LGPD: Portabilidade de dados (Art. 18, VI LGPD) ─────────────────────
    public function exportData()
    {
        $user = auth()->user();

        LgpdDataRequest::create([
            'user_id'    => $user->id,
            'tenant_id'  => $user->tenant_id,
            'type'       => 'export',
            'status'     => 'completed',
            'ip_address' => request()->ip(),
        ]);

        $data = [
            'exportado_em' => now()->toIso8601String(),
            'titular'      => [
                'id'           => $user->id,
                'nome'         => $user->name,
                'email'        => $user->email,
                'telefone'     => $user->phone,
                'cargo'        => $user->role,
                'departamento' => $user->department,
                'criado_em'    => optional($user->created_at)->toIso8601String(),
                'ultimo_login' => optional($user->last_login_at)->toIso8601String(),
                'termos_aceitos_em' => optional($user->terms_accepted_at)->toIso8601String(),
            ],
            'tarefas' => DB::table('tasks')
                ->where('assigned_to', $user->id)
                ->select('id', 'title', 'status', 'due_date', 'created_at')
                ->get(),
            'projetos' => DB::table('project_members')
                ->join('projects', 'projects.id', '=', 'project_members.project_id')
                ->where('project_members.user_id', $user->id)
                ->select('projects.id', 'projects.title', 'projects.status', 'project_members.role', 'project_members.created_at')
                ->get(),
            'mensagens_whatsapp' => DB::table('whatsapp_messages')
                ->where('tenant_id', $user->tenant_id)
                ->where('direction', 'outbound')
                ->select('id', 'to_number', 'body', 'status', 'created_at')
                ->limit(500)
                ->get(),
            'chats_internos' => DB::table('internal_chat_messages')
                ->where('sender_id', $user->id)
                ->select('id', 'body', 'created_at')
                ->get(),
            'notificacoes' => DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->select('id', 'type', 'data', 'read_at', 'created_at')
                ->limit(200)
                ->get(),
            'historico_acessos' => DB::table('login_activities')
                ->where('user_id', $user->id)
                ->select('ip_address', 'device', 'browser', 'platform', 'success', 'logged_in_at')
                ->orderBy('logged_in_at', 'desc')
                ->limit(100)
                ->get(),
        ];

        $filename = 'meus_dados_vivensi_' . now()->format('Ymd_His') . '.json';

        Log::info('LGPD export', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id, 'ip' => request()->ip()]);

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Type'        => 'application/json; charset=utf-8',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // ── LGPD: Solicitação de apagamento (Art. 18, VI LGPD) ──────────────────
    public function requestDelete(Request $request)
    {
        $request->validate([
            'confirm_phrase' => ['required', 'in:EXCLUIR MINHA CONTA'],
            'reason'         => ['nullable', 'string', 'max:1000'],
        ]);

        $user = auth()->user();

        // Evita duplicidade de pedido pendente
        $existing = LgpdDataRequest::where('user_id', $user->id)
            ->where('type', 'delete')
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existing) {
            return back()->with('lgpd_info', 'Você já possui uma solicitação de exclusão em andamento (protocolo #' . $existing->id . ').');
        }

        $req = LgpdDataRequest::create([
            'user_id'    => $user->id,
            'tenant_id'  => $user->tenant_id,
            'type'       => 'delete',
            'status'     => 'pending',
            'ip_address' => $request->ip(),
            'notes'      => $request->reason,
        ]);

        Log::warning('LGPD delete request', [
            'user_id'    => $user->id,
            'tenant_id'  => $user->tenant_id,
            'request_id' => $req->id,
            'ip'         => $request->ip(),
        ]);

        // Notifica o super admin via log estruturado (DPO deve monitorar)
        Log::channel('stack')->critical('LGPD_DELETE_REQUEST', [
            'protocolo'  => $req->id,
            'user_id'    => $user->id,
            'user_email' => $user->email,
            'tenant_id'  => $user->tenant_id,
            'prazo_lgpd' => now()->addDays(15)->toDateString(),
        ]);

        return back()->with('lgpd_success', 'Solicitação de exclusão registrada. Protocolo: #' . $req->id . '. Nossa equipe processará em até 15 dias úteis conforme a LGPD.');
    }
}
