<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_code',
        'user_id',
        'table_id',
        'date',
        'time_slot',
        'guest_count',
        'contact_name',
        'contact_phone',
        'special_requests',
        'status',
        'idempotency_key',
        'expires_at',
        'confirmed_at',
        'cancelled_at',
        'deposit_amount',
        'deposit_status',
        'deposit_paid_at',
        'deposit_refunded_at',
        'deposit_transaction_id',
        'deposit_data',
        'arrived_at',
        'order_id',
    ];

    protected $casts = [
        'date' => 'date',
        'time_slot' => 'string',
        'guest_count' => 'integer',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'deposit_amount' => 'decimal:2',
        'deposit_paid_at' => 'datetime',
        'deposit_refunded_at' => 'datetime',
        'arrived_at' => 'datetime',
        'deposit_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

