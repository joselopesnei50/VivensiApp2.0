@extends('layouts.app')
@section('title', 'Agenda de Reuniões')

@section('content')
<div class="header-page" style="margin-bottom:32px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <span style="background:#4f46e5; width:12px; height:3px; border-radius:2px;"></span>
                <h6 style="color:#4f46e5; font-weight:800; text-transform:uppercase; margin:0; letter-spacing:2px; font-size:0.7rem;">Super Admin</h6>
            </div>
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2.2rem; letter-spacing:-1px;">Agenda de Reuniões</h2>
            <p style="color:#64748b; margin:6px 0 0; font-size:1rem;">Gerencie os agendamentos feitos pela página pública.</p>
        </div>
        <a href="{{ route('booking.index') }}" target="_blank"
           style="display:inline-flex; align-items:center; gap:8px; background:#f1f5f9; color:#475569; padding:12px 20px; border-radius:12px; font-weight:700; font-size:0.85rem; text-decoration:none; border:1px solid #e2e8f0;">
            <i class="fas fa-external-link-alt"></i> Ver página pública
        </a>
    </div>
</div>

@if(session('success'))
    <div style="background:#ecfdf5; color:#065f46; padding:16px 20px; border-radius:12px; margin-bottom:24px; border:1px solid #a7f3d0; font-weight:700; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

{{-- Cards de resumo --}}
<div class="row g-3 mb-4">
    @php
        $cards = [
            ['label'=>'Confirmadas','value'=>$counts['confirmed'],'icon'=>'fa-calendar-check','color'=>'#059669','bg'=>'#ecfdf5','filter'=>'confirmed'],
            ['label'=>'Próximas','value'=>$upcoming,'icon'=>'fa-clock','color'=>'#4f46e5','bg'=>'#eff6ff','filter'=>'confirmed'],
            ['label'=>'Canceladas','value'=>$counts['cancelled'],'icon'=>'fa-calendar-xmark','color'=>'#dc2626','bg'=>'#fef2f2','filter'=>'cancelled'],
            ['label'=>'Total','value'=>$counts['all'],'icon'=>'fa-calendar','color'=>'#64748b','bg'=>'#f1f5f9','filter'=>'all'],
        ];
    @endphp
    @foreach($cards as $c)
    <div class="col-6 col-md-3">
        <a href="?status={{ $c['filter'] }}" style="text-decoration:none;">
            <div class="vivensi-card" style="padding:22px; border-radius:16px; border:2px solid {{ $status === $c['filter'] ? $c['color'] : 'transparent' }}; transition:all 0.2s;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <div style="width:44px; height:44px; background:{{ $c['bg'] }}; border-radius:12px; display:flex; align-items:center; justify-content:center; color:{{ $c['color'] }}; font-size:1.1rem; flex-shrink:0;">
                        <i class="fas {{ $c['icon'] }}"></i>
                    </div>
                    <div>
                        <div style="font-size:1.8rem; font-weight:900; color:#1e293b; letter-spacing:-1px; line-height:1;">{{ $c['value'] }}</div>
                        <div style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">{{ $c['label'] }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- Lista de agendamentos --}}
<div class="vivensi-card" style="border-radius:20px; overflow:hidden; border:1px solid #f1f5f9;">
    @if($bookings->isEmpty())
        <div style="text-align:center; padding:80px 20px; color:#94a3b8;">
            <i class="fas fa-calendar-xmark" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.3;"></i>
            <p style="font-weight:700; margin:0;">Nenhum agendamento encontrado.</p>
        </div>
    @else
        <div class="table-responsive">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                        <th style="padding:14px 20px; text-align:left; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px; white-space:nowrap;">Data / Hora</th>
                        <th style="padding:14px 20px; text-align:left; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Participante</th>
                        <th style="padding:14px 20px; text-align:left; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Contato</th>
                        <th style="padding:14px 20px; text-align:center; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Status</th>
                        <th style="padding:14px 20px; text-align:left; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Link</th>
                        <th style="padding:14px 20px; text-align:right; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $b)
                    @php
                        $isPast    = $b->meeting_date->isPast();
                        $isToday   = $b->meeting_date->isToday();
                        $isFuture  = $b->meeting_date->isFuture();
                    @endphp
                    <tr style="border-bottom:1px solid #f1f5f9; {{ $isToday ? 'background:#fffbeb;' : '' }}"
                        onmouseover="this.style.background='{{ $isToday ? '#fef9c3' : '#fafafa' }}'"
                        onmouseout="this.style.background='{{ $isToday ? '#fffbeb' : '' }}'">

                        {{-- Data/Hora --}}
                        <td style="padding:16px 20px; white-space:nowrap;">
                            @if($isToday)
                                <span style="background:#fef3c7; color:#d97706; font-weight:800; font-size:0.7rem; padding:3px 8px; border-radius:6px; display:block; margin-bottom:4px;">HOJE</span>
                            @endif
                            <div style="font-weight:800; color:#1e293b; font-size:0.92rem;">
                                {{ $b->meeting_date->locale('pt_BR')->isoFormat('ddd, D MMM') }}
                            </div>
                            <div style="font-size:0.82rem; color:#4f46e5; font-weight:700;">
                                <i class="fas fa-clock me-1"></i>{{ substr($b->meeting_time, 0, 5) }}
                            </div>
                            <div style="font-size:0.7rem; color:#94a3b8; margin-top:2px;">
                                {{ $b->meeting_date->locale('pt_BR')->isoFormat('YYYY') }}
                            </div>
                        </td>

                        {{-- Participante --}}
                        <td style="padding:16px 20px;">
                            <div style="font-weight:800; color:#1e293b; font-size:0.9rem;">{{ $b->name }}</div>
                            @if($b->notes)
                                <div style="color:#64748b; font-size:0.75rem; margin-top:3px; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $b->notes }}">
                                    <i class="fas fa-comment-dots me-1"></i>{{ $b->notes }}
                                </div>
                            @endif
                            @if($b->admin_notes)
                                <div style="color:#4f46e5; font-size:0.72rem; margin-top:3px;">
                                    <i class="fas fa-sticky-note me-1"></i>{{ $b->admin_notes }}
                                </div>
                            @endif
                        </td>

                        {{-- Contato --}}
                        <td style="padding:16px 20px;">
                            <a href="mailto:{{ $b->email }}" style="color:#3b82f6; font-weight:600; font-size:0.82rem; text-decoration:none;">
                                <i class="fas fa-envelope me-1"></i>{{ $b->email }}
                            </a>
                            @if($b->phone)
                                <div style="margin-top:4px;">
                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $b->phone) }}" target="_blank"
                                       style="color:#25d366; font-weight:600; font-size:0.78rem; text-decoration:none;">
                                        <i class="fab fa-whatsapp me-1"></i>{{ $b->phone }}
                                    </a>
                                </div>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td style="padding:16px 20px; text-align:center;">
                            @if($b->status === 'confirmed')
                                <span style="background:#ecfdf5; color:#059669; font-weight:800; font-size:0.72rem; padding:5px 12px; border-radius:20px; white-space:nowrap;">
                                    <i class="fas fa-check me-1"></i>Confirmada
                                </span>
                            @else
                                <span style="background:#fef2f2; color:#dc2626; font-weight:800; font-size:0.72rem; padding:5px 12px; border-radius:20px; white-space:nowrap;">
                                    <i class="fas fa-xmark me-1"></i>Cancelada
                                </span>
                            @endif
                            @if($isPast && $b->status === 'confirmed')
                                <div style="color:#94a3b8; font-size:0.68rem; margin-top:4px;">Realizada</div>
                            @endif
                        </td>

                        {{-- Link da reunião --}}
                        <td style="padding:16px 20px;">
                            @if($b->meeting_link)
                                <a href="{{ $b->meeting_link }}" target="_blank"
                                   style="display:inline-flex; align-items:center; gap:6px; background:#eff6ff; color:#3b82f6; font-weight:700; font-size:0.78rem; padding:6px 12px; border-radius:8px; text-decoration:none; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <i class="fas fa-video"></i> Abrir link
                                </a>
                            @else
                                <span style="color:#cbd5e1; font-size:0.78rem;">Sem link</span>
                            @endif
                        </td>

                        {{-- Ações --}}
                        <td style="padding:16px 20px; text-align:right;">
                            <div style="display:flex; gap:6px; justify-content:flex-end;">
                                {{-- Botão de editar link/notas --}}
                                <button onclick="openModal({{ $b->id }}, '{{ addslashes($b->meeting_link ?? '') }}', '{{ addslashes($b->admin_notes ?? '') }}')"
                                        style="padding:7px 12px; border-radius:8px; background:#eff6ff; color:#3b82f6; font-size:0.78rem; font-weight:700; border:none; cursor:pointer;">
                                    <i class="fas fa-link"></i>
                                </button>

                                {{-- Cancelar / Reativar --}}
                                @if($b->status === 'confirmed')
                                    <form action="{{ route('admin.bookings.status', $b) }}" method="POST"
                                          onsubmit="return confirm('Cancelar este agendamento?')">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit"
                                                style="padding:7px 12px; border-radius:8px; background:#fef2f2; color:#dc2626; font-size:0.78rem; font-weight:700; border:none; cursor:pointer;">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.bookings.status', $b) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit"
                                                style="padding:7px 12px; border-radius:8px; background:#ecfdf5; color:#059669; font-size:0.78rem; font-weight:700; border:none; cursor:pointer;">
                                            <i class="fas fa-rotate-left"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($bookings->hasPages())
            <div style="padding:20px 24px; border-top:1px solid #f1f5f9;">
                {{ $bookings->appends(['status' => $status])->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Modal: Link + Notas --}}
<div id="linkModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:white; border-radius:20px; padding:36px; max-width:480px; width:100%; box-shadow:0 25px 60px rgba(0,0,0,0.2);">
        <h3 style="margin:0 0 6px; font-weight:900; color:#1e293b; font-size:1.2rem;">
            <i class="fas fa-video me-2" style="color:#4f46e5;"></i>Link & Notas da Reunião
        </h3>
        <p style="color:#64748b; font-size:0.85rem; margin:0 0 24px;">Adicione o link de videoconferência e envie para o participante.</p>

        <form id="linkForm" method="POST">
            @csrf @method('PATCH')
            <div style="margin-bottom:18px;">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">
                    Link da reunião (Google Meet, Zoom, etc.)
                </label>
                <input type="url" name="meeting_link" id="modalLink"
                       placeholder="https://meet.google.com/xxx-xxxx-xxx"
                       style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box;"
                       onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#f1f5f9'">
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Notas internas</label>
                <input type="text" name="admin_notes" id="modalNotes"
                       placeholder="Ex: Cliente interessado no plano NGO"
                       style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box;"
                       onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#f1f5f9'">
            </div>
            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:0.88rem; font-weight:700; color:#1e293b; margin-bottom:24px;">
                <input type="checkbox" name="notify_guest" value="1" id="modalNotify" style="width:18px; height:18px; accent-color:#4f46e5;">
                Enviar link por e-mail para o participante
            </label>
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="closeModal()"
                        style="flex:1; padding:14px; border-radius:12px; border:2px solid #e2e8f0; background:white; font-weight:700; color:#64748b; cursor:pointer; font-size:0.9rem;">
                    Cancelar
                </button>
                <button type="submit"
                        style="flex:1; padding:14px; border-radius:12px; border:none; background:#4f46e5; color:white; font-weight:800; cursor:pointer; font-size:0.9rem;">
                    <i class="fas fa-save me-1"></i>Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id, link, notes) {
    document.getElementById('linkForm').action = '/admin/bookings/' + id + '/link';
    document.getElementById('modalLink').value  = link;
    document.getElementById('modalNotes').value = notes;
    document.getElementById('modalNotify').checked = false;
    document.getElementById('linkModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('linkModal').style.display = 'none';
}
document.getElementById('linkModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endsection
