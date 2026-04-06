<?php

namespace App\Http\Controllers;

use App\Models\InternalMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InternalChatController extends Controller
{
    /**
     * Display the chat interface
     */
    public function index()
    {
        $user = Auth::user();
        
        // List members of the same tenant (ISOLATION)
        $contacts = User::where('tenant_id', $user->tenant_id)
                        ->where('id', '!=', $user->id);

        // Se for time da plataforma, filtrar para ver apenas o time da plataforma
        if ($user->is_platform_team) {
            $contacts->where('is_platform_team', true);
        } else {
            // Clientes só veem seus colegas de tenant (e não o suporte da plataforma aqui, o suporte é via tickets)
            $contacts->where('is_platform_team', false);
        }

        $contacts = $contacts->get();

        return view('admin.chat.index', compact('contacts'));
    }

    /**
     * Fetch messages between users
     */
    public function getMessages($receiverId)
    {
        $user = Auth::user();
        
        // Rigorous verification of tenant isolation
        $receiver = User::findOrFail($receiverId);
        if ($receiver->tenant_id !== $user->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = InternalMessage::where('tenant_id', $user->tenant_id)
            ->where(function($q) use ($user, $receiverId) {
                $q->where(function($sq) use ($user, $receiverId) {
                    $sq->where('sender_id', $user->id)->where('receiver_id', $receiverId);
                })->orWhere(function($sq) use ($user, $receiverId) {
                    $sq->where('sender_id', $receiverId)->where('receiver_id', $user->id);
                });
            })
            ->orderBy('created_at', 'asc')
            ->with('sender')
            ->get();

        return response()->json($messages);
    }

    /**
     * Send a new message
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string'
        ]);

        $user = Auth::user();
        $receiver = User::findOrFail($request->receiver_id);

        // Security check: must be same tenant
        if ($receiver->tenant_id !== $user->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $internalMessage = InternalMessage::create([
            'tenant_id' => $user->tenant_id,
            'sender_id' => $user->id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $internalMessage->load('sender')
        ]);
    }
}
