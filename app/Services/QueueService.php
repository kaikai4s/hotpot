<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Queue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QueueService
{
    public function joinQueue(User $user, int $guestCount, ?string $tableType = null): Queue
    {
        // 检查用户是否已在队列中
        $existing = Queue::where('user_id', $user->id)
            ->whereIn('status', ['waiting', 'called'])
            ->first();

        if ($existing) {
            throw new \Exception('已在队列中', 429);
        }

        return DB::transaction(function () use ($user, $guestCount, $tableType) {
            // 获取当前最大位置
            $maxPosition = Queue::where('status', 'waiting')->max('position') ?? 0;
            $position = $maxPosition + 1;

            // 生成排队号（格式：前缀 + 序号，如 C001）
            $prefix = $this->getQueuePrefix();
            $queueNumber = $this->generateQueueNumber($prefix);

            // 创建排队记录
            $queue = Queue::create([
                'queue_number' => $queueNumber,
                'user_id' => $user->id,
                'guest_count' => $guestCount,
                'table_type' => $tableType,
                'position' => $position,
                'status' => 'waiting',
            ]);

            return $queue;
        });
    }

    /**
     * 生成排队号（格式：前缀 + 序号，如 C001）
     * 基于该前缀已有的最大序号 +1
     */
    private function generateQueueNumber(string $prefix): string
    {
        // 查找该前缀的最大序号
        $lastQueue = Queue::where('queue_number', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(queue_number, 2) AS UNSIGNED) DESC')
            ->first();

        if ($lastQueue) {
            $lastNumber = (int) substr($lastQueue->queue_number, 1);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad((string) $newNumber, 3, '0', STR_PAD_LEFT);
    }

    public function getQueueStatus(int $queueId): array
    {
        $queue = Queue::findOrFail($queueId);
        $aheadCount = Queue::where('status', 'waiting')
            ->where('position', '<', $queue->position)
            ->count();

        // 估算等待时间（假设每桌平均用餐时间2小时）
        $estimatedWaitTime = $aheadCount * 30; // 简化计算，实际应该更复杂

        return [
            'queue_id' => $queue->id,
            'queue_number' => $queue->queue_number,
            'current_position' => $queue->position,
            'ahead_count' => $aheadCount,
            'estimated_wait_time' => $estimatedWaitTime,
            'status' => $queue->status,
        ];
    }

    public function callNext(): ?Queue
    {
        $queue = Queue::where('status', 'waiting')
            ->orderBy('position')
            ->first();

        if (!$queue) {
            return null;
        }

        $queue->update([
            'status' => 'called',
            'called_at' => now(),
        ]);

        return $queue;
    }

    private function getQueuePrefix(): string
    {
        $prefixes = ['A', 'B', 'C'];
        $hour = (int) date('H');
        
        if ($hour >= 17 && $hour < 20) {
            return $prefixes[0]; // 晚餐高峰期
        } elseif ($hour >= 11 && $hour < 14) {
            return $prefixes[1]; // 午餐高峰期
        }
        
        return $prefixes[2]; // 其他时间
    }
}

