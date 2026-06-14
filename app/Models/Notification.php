<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'employee_id',
        'type',
        'level',
        'title',
        'content',
        'related_type',
        'related_id',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Level constants
    const LEVEL_INFO = 'info';
    const LEVEL_SUCCESS = 'success';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

    // Type constants
    const TYPE_ORDER_STATUS = 'order_status';
    const TYPE_APPROVAL_RESULT = 'approval_result';
    const TYPE_INVENTORY_WARNING = 'inventory_warning';
    const TYPE_SYSTEM = 'system';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_PAYMENT = 'payment';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}