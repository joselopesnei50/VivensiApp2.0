<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function page()
    {
        $userId = auth()->id();

        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $unreadCount = Notification::where('user_id', $userId)->unread()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function index()
    {
        $userId = auth()->id();

        $limit = (int) request()->query('limit', 10);
        if ($limit <= 0) {
            $limit = 10;
        }
        if ($limit > 50) {
            $limit = 50;
        }

        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $unreadCount = Notification::where('user_id', $userId)->unread()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function unreadCount()
    {
        $userId = auth()->id();
        $unreadCount = Notification::where('user_id', $userId)->unread()->count();
        return response()->json(['unread_count' => $unreadCount]);
    }

    public function markAsReadWeb($id)
    {
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notification->update(['read_at' => now()]);

        if ($notification->link) {
            return redirect($notification->link);
        }

        return back();
    }

    public function markAllAsReadWeb()
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notification->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
