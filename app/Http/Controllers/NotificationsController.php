<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Support ?only=unread (default all)
        $builder = $request->query('only') === 'unread'
            ? $user->unreadNotifications()->latest('created_at')
            : $user->notifications()->latest('created_at');

        $limit = min((int) $request->query('limit', 50), 100);

        $items = $builder->take($limit)->get()->map(function ($n) {
            return [
                'id'         => $n->id,
                'type'       => class_basename($n->type),
                'data'       => $n->data,
                'read_at'    => optional($n->read_at)->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
            ];
        })->values();

        $unreadCount = $user->unreadNotifications()->count();

        // Transitional payload:
        // - 'notifications' for existing tests
        // - 'data.notifications' for the Bible envelope
        return response()->json([
            'notifications' => $items,                // <-- legacy key (test asserts on this)
            'data' => [
                'notifications' => $items,            // <-- envelope
            ],
            'meta' => [
                'count'        => $items->count(),
                'unread_count' => $unreadCount,
            ],
        ]);
    }
}
