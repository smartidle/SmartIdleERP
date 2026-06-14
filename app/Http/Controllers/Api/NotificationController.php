<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * 创建通知（内部使用）
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer',
            'type' => 'required|string|max:100',
            'level' => 'nullable|string|max:20',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'related_type' => 'nullable|string|max:100',
            'related_id' => 'nullable|integer',
            'data' => 'nullable|array',
        ]);

        $notification = Notification::create($validated);

        return $this->success($notification, 'Notification created');
    }

    /**
     * 获取通知列表
     */
    public function index(Request $request)
    {
        $query = Notification::with('employee:id,name')
            ->where('employee_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        $perPage = min($request->input('per_page', 20), 50);
        $notifications = $query->paginate($perPage);

        return $this->paginate($notifications);
    }

    /**
     * 获取未读数量
     */
    public function unreadCount(Request $request)
    {
        $count = Notification::where('employee_id', $request->user()->id)
            ->unread()
            ->count();

        return $this->success(['count' => $count]);
    }

    /**
     * 获取通知详情
     */
    public function show(Request $request, Notification $notification)
    {
        if ($notification->employee_id !== $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }

        $notification->load('employee:id,name');

        return $this->success($notification);
    }

    /**
     * 标记单条通知为已读
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        if ($notification->employee_id !== $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }

        $notification->markAsRead();

        return $this->success($notification, 'Marked as read');
    }

    /**
     * 标记所有通知为已读
     */
    public function markAllAsRead(Request $request)
    {
        $updated = Notification::where('employee_id', $request->user()->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return $this->success(['updated' => $updated], 'All marked as read');
    }

    /**
     * 删除通知
     */
    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->employee_id !== $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }

        $notification->delete();

        return $this->success(null, 'Notification deleted');
    }
}