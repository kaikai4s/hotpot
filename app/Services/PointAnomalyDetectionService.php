<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\PointTransaction;
use App\Models\PointStatistic;
use App\Models\MemberPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointAnomalyDetectionService
{
    /**
     * 检测积分异常
     */
    public function detectAnomalies(array $options = []): array
    {
        $anomalies = [];

        // 检测异常大额积分获得
        $anomalies = array_merge($anomalies, $this->detectLargeEarnTransactions($options));

        // 检测异常频繁交易
        $anomalies = array_merge($anomalies, $this->detectFrequentTransactions($options));

        // 检测异常用户行为
        $anomalies = array_merge($anomalies, $this->detectAbnormalUserBehavior($options));

        // 检测积分流失异常
        $anomalies = array_merge($anomalies, $this->detectAbnormalExpiration($options));

        return $anomalies;
    }

    /**
     * 检测异常大额积分获得
     */
    private function detectLargeEarnTransactions(array $options = []): array
    {
        $threshold = $options['large_earn_threshold'] ?? 10000; // 默认阈值10000积分
        $hours = $options['time_window_hours'] ?? 24; // 默认24小时内

        $transactions = PointTransaction::where('type', 'earn')
            ->where('points', '>', $threshold)
            ->where('created_at', '>=', now()->subHours($hours))
            ->with('user')
            ->get();

        $anomalies = [];
        foreach ($transactions as $transaction) {
            $anomalies[] = [
                'type' => 'large_earn',
                'severity' => $transaction->points > $threshold * 2 ? 'high' : 'medium',
                'message' => "用户 {$transaction->user->nickname} 在 {$transaction->created_at->format('Y-m-d H:i:s')} 获得异常大额积分：{$transaction->points}",
                'user_id' => $transaction->user_id,
                'transaction_id' => $transaction->id,
                'points' => $transaction->points,
                'source_type' => $transaction->source_type,
                'created_at' => $transaction->created_at->toDateTimeString(),
            ];
        }

        return $anomalies;
    }

    /**
     * 检测异常频繁交易
     */
    private function detectFrequentTransactions(array $options = []): array
    {
        $maxTransactions = $options['max_transactions_per_hour'] ?? 50; // 默认1小时内最多50笔
        $hours = $options['time_window_hours'] ?? 1;

        $users = DB::table('point_transactions')
            ->where('created_at', '>=', now()->subHours($hours))
            ->select('user_id', DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > ?', [$maxTransactions])
            ->get();

        // 加载用户信息
        $userIds = $users->pluck('user_id')->toArray();
        $usersWithInfo = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        $anomalies = [];
        foreach ($users as $user) {
            $userInfo = $usersWithInfo[$user->user_id] ?? null;
            $nickname = $userInfo ? $userInfo->nickname : "用户ID:{$user->user_id}";
            
            $anomalies[] = [
                'type' => 'frequent_transactions',
                'severity' => $user->transaction_count > $maxTransactions * 2 ? 'high' : 'medium',
                'message' => "用户 {$nickname} 在 {$hours} 小时内进行了 {$user->transaction_count} 笔交易，可能存在异常",
                'user_id' => $user->user_id,
                'transaction_count' => $user->transaction_count,
                'time_window' => $hours,
            ];
        }

        return $anomalies;
    }

    /**
     * 检测异常用户行为
     */
    private function detectAbnormalUserBehavior(array $options = []): array
    {
        $anomalies = [];

        // 检测短时间内大量获得和兑换
        $users = MemberPoint::where('total_points', '>', 0)
            ->with('user')
            ->get();

        foreach ($users as $memberPoint) {
            // 检测积分增长速度异常
            $recentEarned = PointTransaction::where('user_id', $memberPoint->user_id)
                ->where('type', 'earn')
                ->where('created_at', '>=', now()->subDays(7))
                ->sum('points');

            $avgDailyEarned = $recentEarned / 7;
            if ($avgDailyEarned > 5000) { // 日均获得超过5000积分
                $anomalies[] = [
                    'type' => 'abnormal_growth',
                    'severity' => 'medium',
                    'message' => "用户 {$memberPoint->user->nickname} 近7天日均获得积分 {$avgDailyEarned}，增长速度异常",
                    'user_id' => $memberPoint->user_id,
                    'avg_daily_earned' => round($avgDailyEarned, 2),
                ];
            }

            // 检测积分余额异常（可用积分大于总积分）
            if ($memberPoint->available_points > $memberPoint->total_points) {
                $anomalies[] = [
                    'type' => 'balance_anomaly',
                    'severity' => 'high',
                    'message' => "用户 {$memberPoint->user->nickname} 积分余额异常：可用积分({$memberPoint->available_points})大于总积分({$memberPoint->total_points})",
                    'user_id' => $memberPoint->user_id,
                    'available_points' => $memberPoint->available_points,
                    'total_points' => $memberPoint->total_points,
                ];
            }
        }

        return $anomalies;
    }

    /**
     * 检测积分流失异常
     */
    private function detectAbnormalExpiration(array $options = []): array
    {
        $threshold = $options['expiration_threshold'] ?? 0.3; // 默认过期率超过30%为异常

        // 统计最近30天的过期情况
        $totalEarned = PointTransaction::where('type', 'earn')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('points');

        $totalExpired = abs(PointTransaction::where('type', 'expire')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('points'));

        if ($totalEarned > 0) {
            $expirationRate = $totalExpired / $totalEarned;
            if ($expirationRate > $threshold) {
                return [
                    [
                        'type' => 'high_expiration_rate',
                        'severity' => 'medium',
                        'message' => "近30天积分过期率异常：{$expirationRate}% ({$totalExpired}/{$totalEarned})",
                        'expiration_rate' => round($expirationRate * 100, 2),
                        'total_earned' => $totalEarned,
                        'total_expired' => $totalExpired,
                    ],
                ];
            }
        }

        return [];
    }

    /**
     * 获取异常统计摘要
     */
    public function getAnomalySummary(): array
    {
        $anomalies = $this->detectAnomalies();
        
        $summary = [
            'total' => count($anomalies),
            'high_severity' => 0,
            'medium_severity' => 0,
            'by_type' => [],
        ];

        foreach ($anomalies as $anomaly) {
            if ($anomaly['severity'] === 'high') {
                $summary['high_severity']++;
            } else {
                $summary['medium_severity']++;
            }

            $type = $anomaly['type'];
            if (!isset($summary['by_type'][$type])) {
                $summary['by_type'][$type] = 0;
            }
            $summary['by_type'][$type]++;
        }

        return $summary;
    }
}

