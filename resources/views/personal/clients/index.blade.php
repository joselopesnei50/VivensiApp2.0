@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Mini CRM</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.8rem; letter-spacing: -1.5px;">Meus Clientes</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Gerencie sua base de clientes, compras e contatos.</p>
        </div>
        <div style="display: flex; gap: 12px;">
             <a href="{{ route('clients.create') }}" class="btn-premium" style="background: #1e293b; text-decoration: none; border: none; font-weight: 700;">
                <i class="fas fa-plus me-2" style="color: #10b981;"></i> Novo Cliente
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="vivensi-card p-4" style="background: white; border-radius: 24px; border: 1px solid #f1f5f9;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="border-collapse: separate; border-spacing: 0 10px;">
                    <thead>
                        <tr style="color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">
                            <th style="border: none;">Nome do Cliente</th>
                            <th style="border: none;">Contato</th>
                            <th style="border: none;">Tipo</th>
                            <th style="border: none;">Último Registro</th>
                            <th style="border: none; text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                        <tr style="background: #f8fafc; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.2s;">
                            <td style="border: none; border-radius: 12px 0 0 12px; padding: 15px 20px;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="width: 40px; height: 40px; background: #e0e7ff; color: #4f46e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900;">
                                        {{ strtoupper(substr($client->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 800; color: #1e293b;">{{ $client->name }}</div>
                                        <div style="font-size: 0.75rem; color: #64748b;">{{ $client->document ?: 'Sem documento' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="border: none;">
                                <div style="font-size: 0.85rem; color: #475569;"><i class="fas fa-envelope text-muted me-1"></i> {{ $client->email ?: '-' }}</div>
                                <div style="font-size: 0.85rem; color: #475569;"><i class="fab fa-whatsapp text-muted me-1"></i> {{ $client->phone ?: '-' }}</div>
                            </td>
                            <td style="border: none;">
                                <span class="badge" style="background: {{ $client->type == 'company' ? '#fef3c7; color: #d97706;' : '#e0f2fe; color: #0284c7;' }}; padding: 6px 10px; border-radius: 8px;">
                                    {{ $client->type == 'company' ? 'Pessoa Jurídica' : 'Pessoa Física' }}
                                </span>
                            </td>
                            <td style="border: none; font-size: 0.85rem; color: #64748b;">
                                {{ $client->updated_at->format('d/m/Y H:i') }}
                            </td>
                            <td style="border: none; border-radius: 0 12px 12px 0; text-align: right; padding: 15px 20px;">
                                <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-light" style="border-radius: 8px; color: #4f46e5; font-weight: 700;"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('clients.destroy', $client) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Deseja realmente excluir este cliente?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light" style="border-radius: 8px; color: #ef4444; font-weight: 700;"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5" style="border: none;">
                                <div style="font-size: 3rem; color: #e2e8f0; margin-bottom: 15px;"><i class="fas fa-users-slash"></i></div>
                                <h5 style="color: #64748b; font-weight: 800;">Nenhum cliente cadastrado.</h5>
                                <p style="color: #94a3b8; font-size: 0.9rem;">Comece adicionando o seu primeiro cliente e crie relacionamentos!</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($clients->hasPages())
            <div class="mt-4">
                {{ $clients->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
