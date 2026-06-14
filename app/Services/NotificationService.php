<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Employee;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * 发送通知给单个员工
     */
    public function sendToEmployee(
        int $employeeId,
        string $type,
        string $title,
        string $content,
        array $options = []
    ): Notification {
        return Notification::create([
            'employee_id' => $employeeId,
            'type' => $type,
            'level' => $options['level'] ?? Notification::LEVEL_INFO,
            'title' => $title,
            'content' => $content,
            'related_type' => $options['related_type'] ?? null,
            'related_id' => $options['related_id'] ?? null,
            'data' => $options['data'] ?? null,
        ]);
    }

    /**
     * 发送通知给多个员工
     */
    public function sendToEmployees(
        array $employeeIds,
        string $type,
        string $title,
        string $content,
        array $options = []
    ): Collection {
        $notifications = collect();
        foreach ($employeeIds as $employeeId) {
            $notifications->push($this->sendToEmployee($employeeId, $type, $title, $content, $options));
        }
        return $notifications;
    }

    /**
     * 发送通知给指定角色（如 admin）
     */
    public function sendToRole(
        string $roleSlug,
        string $type,
        string $title,
        string $content,
        array $options = []
    ): Collection {
        $employeeIds = Employee::whereHas('role', function ($query) use ($roleSlug) {
            $query->where('slug', $roleSlug);
        })->pluck('id');
        return $this->sendToEmployees($employeeIds->toArray(), $type, $title, $content, $options);
    }

    /**
     * 订单状态变更通知
     */
    public function orderStatusChanged($order, string $oldStatus, string $newStatus): void
    {
        $statusLabels = [
            'draft' => 'Draft',
            'pending' => 'Pending',
            'approved' => 'Approved',
            'partial' => 'Partial',
            'shipped' => 'Shipped',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $title = 'Order Status Changed';
        $content = sprintf(
            'Order %s status changed from [%s] to [%s]',
            $order->order_no ?? $order->id,
            $statusLabels[$oldStatus] ?? $oldStatus,
            $statusLabels[$newStatus] ?? $newStatus
        );

        $level = $newStatus === 'cancelled'
            ? Notification::LEVEL_WARNING
            : ($newStatus === 'completed' ? Notification::LEVEL_SUCCESS : Notification::LEVEL_INFO);

        if (!empty($order->employee_id)) {
            $this->sendToEmployee($order->employee_id, Notification::TYPE_ORDER_STATUS, $title, $content, [
                'level' => $level,
                'related_type' => get_class($order),
                'related_id' => $order->id,
                'data' => [
                    'order_no' => $order->order_no ?? '',
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ],
            ]);
        }
    }

    /**
     * 采购单状态变更通知
     */
    public function purchaseOrderStatusChanged($order, string $oldStatus, string $newStatus): void
    {
        $statusLabels = [
            'draft' => 'Draft',
            'pending' => 'Pending',
            'approved' => 'Approved',
            'partial' => 'Partial',
            'shipped' => 'Shipped',
            'received' => 'Received',
        ];

        $title = 'Purchase Order Status Changed';
        $content = sprintf(
            'Purchase Order %s status changed from [%s] to [%s]',
            $order->order_no ?? $order->id,
            $statusLabels[$oldStatus] ?? $oldStatus,
            $statusLabels[$newStatus] ?? $newStatus
        );

        $level = $newStatus === 'received' ? Notification::LEVEL_SUCCESS : Notification::LEVEL_INFO;

        if (!empty($order->employee_id)) {
            $this->sendToEmployee($order->employee_id, Notification::TYPE_ORDER_STATUS, $title, $content, [
                'level' => $level,
                'related_type' => get_class($order),
                'related_id' => $order->id,
                'data' => [
                    'order_no' => $order->order_no ?? '',
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ],
            ]);
        }
    }

    /**
     * 审批结果通知
     */
    public function approvalResult($approvalRecord, bool $approved, string $reason = ''): void
    {
        $title = $approved ? 'Approval Passed' : 'Approval Rejected';
        $content = $approved
            ? sprintf('Your approval request has been passed')
            : sprintf('Your approval request has been rejected: %s', $reason);

        $this->sendToEmployee($approvalRecord->employee_id, Notification::TYPE_APPROVAL_RESULT, $title, $content, [
            'level' => $approved ? Notification::LEVEL_SUCCESS : Notification::LEVEL_WARNING,
            'related_type' => get_class($approvalRecord),
            'related_id' => $approvalRecord->id,
            'data' => [
                'approved' => $approved,
                'reason' => $reason,
            ],
        ]);
    }

    /**
     * 库存预警通知
     */
    public function inventoryWarning($inventory): void
    {
        $level = $inventory->quantity <= 0
            ? Notification::LEVEL_ERROR
            : Notification::LEVEL_WARNING;

        $productName = $inventory->product->name ?? '';
        $skuCode = $inventory->sku->sku_code ?? '';

        $title = 'Inventory Warning';
        $content = sprintf(
            'Product [%s] SKU [%s] stock is low. Current: %d, Safe stock: %d',
            $productName,
            $skuCode,
            $inventory->quantity,
            $inventory->safe_stock ?? 0
        );

        $this->sendToRole('admin', Notification::TYPE_INVENTORY_WARNING, $title, $content, [
            'level' => $level,
            'related_type' => get_class($inventory),
            'related_id' => $inventory->id,
            'data' => [
                'product_name' => $productName,
                'sku_code' => $skuCode,
                'current_quantity' => $inventory->quantity,
                'safe_stock' => $inventory->safe_stock ?? 0,
            ],
        ]);
    }
}