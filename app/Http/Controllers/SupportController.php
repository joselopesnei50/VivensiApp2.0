<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportController extends Controller
{
    // --- USER METHODS ---

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = SupportTicket::query();

        // 1. Authorization Scope
        if ($user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        } else {
            $query->where('user_id', $user->id);
        }

        // 2. Filters
        if ($request->filled('search')) {
            $query->where('subject', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->orderBy('created_at', 'desc')->get();
        
        return view('support.index', compact('tickets'));
    }

    public function create()
    {
        // Redirect to index because we use a Modal there and auto-open it
        return redirect()->route('support.index')->with('open_modal', true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string',
            'priority' => 'required|string',
            'message' => 'required|string'
        ]);

        $ticket = SupportTicket::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'category' => $request->category,
            'priority' => $request->priority,
            'status' => 'open'
        ]);

        SupportMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'is_admin_reply' => false
        ]);

        // 📧 Notify Admin via Brevo
        $adminEmail = \App\Models\SystemSetting::getValue('email_from');
        if ($adminEmail) {
            app(\App\Services\BrevoService::class)->sendNewTicketToAdmin(
                $adminEmail, 
                Auth::user()->name, 
                $ticket->subject, 
                $ticket->id, 
                Auth::user()->tenant_id
            );
        }

        return redirect()->route('support.index')->with('success', 'Ticket criado com sucesso!');
    }

    public function show($id)
    {
        $ticket = SupportTicket::with(['messages.user', 'user'])->findOrFail($id);
        
        $this->authorizeTicketAccess($ticket);

        return view('support.show', compact('ticket'));
    }

    public function reply(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        
        $this->authorizeTicketAccess($ticket);

        $request->validate(['message' => 'required']);

        $isAdmin = Auth::user()->role == 'super_admin';

        SupportMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'is_admin_reply' => $isAdmin
        ]);

        // Update ticket status
        if ($isAdmin) {
            $ticket->update(['status' => 'answered_by_admin']);
            
            // 📧 Notify User via Brevo
            app(\App\Services\BrevoService::class)->sendTicketReplyToUser(
                $ticket->user, 
                $ticket->id, 
                $ticket->tenant_id
            );
        } else {
            $ticket->update(['status' => 'open']);
            
            // 📧 Notify Admin via Brevo
            $adminEmail = \App\Models\SystemSetting::getValue('email_from');
            if ($adminEmail) {
                app(\App\Services\BrevoService::class)->sendNewTicketToAdmin(
                    $adminEmail, 
                    Auth::user()->name, 
                    "Resposta no chamado #" . $ticket->id, 
                    $ticket->id, 
                    Auth::user()->tenant_id
                );
            }
        }

        return back()->with('success', 'Resposta enviada!');
    }

    // --- ADMIN METHODS (SAAS) ---
    
    public function adminIndex(Request $request)
    {
        if (Auth::user()->role != 'super_admin') abort(403);

        $query = SupportTicket::with('user');

        // Filters
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('subject', 'like', '%'.$term.'%')
                  ->orWhereHas('user', function($u) use ($term) {
                      $u->where('name', 'like', '%'.$term.'%');
                  });
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Sorting
        $tickets = $query->orderByRaw("FIELD(status, 'open', 'answered_by_user', 'answered_by_admin', 'closed')")
                    ->orderBy('created_at', 'desc')
                    ->get();
        
        // Calculate Stats
        $totalOpen = $tickets->where('status', 'open')->count();
        $totalClosed = $tickets->where('status', 'closed')->count();
        $avgResponseTime = 'N/A';

        try {
            // Calculate Average Response Time (Real Data)
            // We get tickets that have at least one admin reply
            $respondedTickets = SupportTicket::whereHas('messages', function($q) {
                $q->where('is_admin_reply', true);
            })->with(['messages' => function($q) {
                $q->where('is_admin_reply', true)->orderBy('created_at', 'asc'); // Get earliest admin reply
            }])->get();

            $totalMinutes = 0;
            $count = 0;

            foreach ($respondedTickets as $ticket) {
                $firstReply = $ticket->messages->first();
                if ($firstReply) {
                    // ABS to handle any potential clock skew, though normally Created < Reply
                    $minutes = $ticket->created_at->diffInMinutes($firstReply->created_at);
                    $totalMinutes += $minutes;
                    $count++;
                }
            }

            if ($count > 0) {
                $avgMinutes = $totalMinutes / $count;
                $hours = floor($avgMinutes / 60);
                $mins = $avgMinutes % 60;
                $avgResponseTime = "{$hours}h {$mins}m";
            }
        } catch (\Exception $e) {
            // Table support_messages might not exist yet
            \Log::error('Support Stats Error: ' . $e->getMessage());
        }

        return view('admin.support.index', compact('tickets', 'totalOpen', 'totalClosed', 'avgResponseTime'));
    }

    // --- HELPER ---
    private function authorizeTicketAccess($ticket)
    {
        if (Auth::user()->role == 'super_admin') return;

        if (Auth::user()->tenant_id) {
            if ($ticket->tenant_id != Auth::user()->tenant_id) abort(403);
        } else {
            if ($ticket->user_id != Auth::id()) abort(403);
        }
    }
}
