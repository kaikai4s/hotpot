<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Configuration;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ReservationService
{
    public function getAvailableTables(string $date, string $timeSlot, int $guestCount, ?int $duration = 120): array
    {
        // 获取所有桌位（用于布局图显示）
        $allTables = Table::all();
        
        // 获取指定日期和时间段已被预约的桌位ID
        $reservedTableIds = Reservation::where('date', $date)
            ->where('time_slot', $timeSlot)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('table_id')
            ->toArray();

        // 计算每个桌位的实际状态
        $tablesWithStatus = $allTables->map(function ($table) use ($reservedTableIds, $guestCount) {
            // 如果桌位在指定时间段已被预约，状态为reserved
            $actualStatus = in_array($table->id, $reservedTableIds) ? 'reserved' : $table->status;
            
            return [
                'id' => $table->id,
                'name' => $table->name,
                'capacity' => $table->capacity,
                'type' => $table->type,
                'status' => $actualStatus,
                'position_x' => $table->position_x,
                'position_y' => $table->position_y,
            ];
        });

        // 筛选可用桌位（用于预约选择）
        $availableTables = $tablesWithStatus->filter(function ($table) use ($guestCount, $reservedTableIds) {
            return $table['status'] === 'available' 
                && $table['capacity'] >= $guestCount
                && !in_array($table['id'], $reservedTableIds);
        });

        return [
            'tables' => $tablesWithStatus->values()->toArray(),
            'available_count' => $availableTables->count(),
            'total_count' => $allTables->count(),
        ];
    }

    public function createReservation(
        User $user,
        int $tableId,
        string $date,
        string $timeSlot,
        int $guestCount,
        string $contactName,
        string $contactPhone,
        ?string $specialRequests,
        string $idempotencyKey
    ): Reservation {
        // 检查幂等性
        $existing = Reservation::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        // 检查桌位是否可用
        $table = Table::lockForUpdate()->findOrFail($tableId);
        if ($table->status !== 'available') {
            throw new \Exception('桌位不可用', 400);
        }

        // 检查时间冲突
        $conflict = Reservation::where('table_id', $tableId)
            ->where('date', $date)
            ->where('time_slot', $timeSlot)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($conflict) {
            throw new \Exception('该时间段已被预约', 400);
        }

        return DB::transaction(function () use (
            $user,
            $tableId,
            $date,
            $timeSlot,
            $guestCount,
            $contactName,
            $contactPhone,
            $specialRequests,
            $idempotencyKey,
            $table
        ) {
            // 检查是否启用预约定金
            $depositEnabled = (bool) Configuration::getValue('reservation_deposit_enabled', true);
            $depositAmount = 0;
            $depositStatus = 'unpaid';

            if ($depositEnabled) {
                // 获取预约定金金额配置
                $depositAmount = (float) Configuration::getValue('reservation_deposit_amount', 50);
            }

            // 生成预约编码
            $reservationCode = 'RES' . date('Ymd') . str_pad((string) (Reservation::whereDate('created_at', today())->count() + 1), 6, '0', STR_PAD_LEFT);

            // 创建预约
            $reservation = Reservation::create([
                'reservation_code' => $reservationCode,
                'user_id' => $user->id,
                'table_id' => $tableId,
                'date' => $date,
                'time_slot' => $timeSlot,
                'guest_count' => $guestCount,
                'contact_name' => $contactName,
                'contact_phone' => $contactPhone,
                'special_requests' => $specialRequests,
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
                'expires_at' => now()->addMinutes(15),
                'deposit_amount' => $depositAmount,
                'deposit_status' => $depositStatus,
            ]);

            // 更新桌位状态
            $table->update(['status' => 'reserved']);

            return $reservation;
        });
    }

    public function confirmReservation(int $reservationId): Reservation
    {
        $reservation = Reservation::findOrFail($reservationId);

        if ($reservation->status !== 'pending') {
            throw new \Exception('预约状态不允许确认', 409);
        }

        if ($reservation->expires_at && $reservation->expires_at->isPast()) {
            throw new \Exception('预约已过期', 409);
        }

        $reservation->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return $reservation;
    }

    public function cancelReservation(int $reservationId, ?string $reason = null): Reservation
    {
        $reservation = Reservation::with('table')->findOrFail($reservationId);

        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            throw new \Exception('预约状态不允许取消', 409);
        }

        // 检查是否在取消时间限制内（预约开始前1小时内不可取消）
        $cancelHoursLimit = (int) Configuration::getValue('reservation_cancel_hours_limit', 1);
        
        if ($reservation->date && $reservation->time_slot) {
            // 确保 date 只取日期部分（YYYY-MM-DD），time_slot 只取时间部分（HH:MM 或 HH:MM:SS）
            $dateStr = $reservation->date instanceof \Carbon\Carbon 
                ? $reservation->date->format('Y-m-d') 
                : (is_string($reservation->date) ? explode(' ', $reservation->date)[0] : $reservation->date);
            
            // 移除 time_slot 中可能包含的日期部分，只保留时间部分
            $timeStr = is_string($reservation->time_slot) 
                ? trim(explode(' ', $reservation->time_slot)[count(explode(' ', $reservation->time_slot)) - 1])
                : $reservation->time_slot;
            
            // 确保时间格式正确（最多8个字符：HH:MM:SS）
            if (strlen($timeStr) > 8) {
                $timeStr = substr($timeStr, 0, 8);
            }
            
            $reservationDateTime = \Carbon\Carbon::parse($dateStr . ' ' . $timeStr);
            $cancelDeadline = $reservationDateTime->copy()->subHours($cancelHoursLimit);
            
            if (now()->greaterThanOrEqualTo($cancelDeadline)) {
                throw new \Exception("预约开始前{$cancelHoursLimit}小时内不可取消预约", 400);
            }
        }

        return DB::transaction(function () use ($reservation, $reason) {
            // 如果已支付定金，自动返还
            if ($reservation->deposit_status === 'paid' && !$reservation->deposit_refunded_at) {
                $reservation->update([
                    'deposit_status' => 'refunded',
                    'deposit_refunded_at' => now(),
                ]);
            }

            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // 释放桌位
            $table = $reservation->table;
            if ($table instanceof Table) {
                $table->update(['status' => 'available']);
            }

            return $reservation;
        });
    }

    /**
     * 确认到达
     */
    public function markArrived(int $reservationId): Reservation
    {
        $reservation = Reservation::findOrFail($reservationId);

        if ($reservation->status !== 'confirmed') {
            throw new \Exception('只有已确认的预约才能标记为到达', 409);
        }

        if ($reservation->arrived_at) {
            throw new \Exception('预约已标记为到达', 409);
        }

        $reservation->update([
            'arrived_at' => now(),
        ]);

        return $reservation;
    }
}

