<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MemberDayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendMemberDayReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'member-day:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '发送会员日活动提醒（会员日前1天）';

    public function __construct(
        private MemberDayService $memberDayService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 检查会员日功能是否启用
        if (!$this->memberDayService->isEnabled()) {
            $this->info('会员日功能未启用，跳过提醒');
            return Command::SUCCESS;
        }

        // 检查明天是否是会员日
        $daysUntilMemberDay = $this->memberDayService->getDaysUntilMemberDay();
        
        if ($daysUntilMemberDay !== 1) {
            $this->info("距离会员日还有 {$daysUntilMemberDay} 天，不需要发送提醒");
            return Command::SUCCESS;
        }

        $this->info('明天是会员日，开始发送提醒...');

        try {
            // 获取所有活跃用户
            $users = User::whereNotNull('openid')
                ->where('status', 'active')
                ->cursor();

            $sentCount = 0;
            $failedCount = 0;

            foreach ($users as $user) {
                try {
                    $this->sendMemberDayReminderNotification($user);
                    $sentCount++;
                    
                    if ($sentCount % 100 === 0) {
                        $this->line("已发送 {$sentCount} 条提醒...");
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('发送会员日提醒失败', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("会员日提醒发送完成: 成功 {$sentCount} 条, 失败 {$failedCount} 条");

            Log::info('会员日提醒定时任务执行完成', [
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('会员日提醒任务执行失败: ' . $e->getMessage());
            Log::error('会员日提醒定时任务执行失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * 发送会员日提醒通知
     */
    private function sendMemberDayReminderNotification(User $user): void
    {
        // 获取用户会员等级对应的折扣
        $memberPoint = $user->memberPoints;
        $level = $memberPoint?->level ?? 'bronze';
        $discount = $this->memberDayService->getMemberDayDiscount($level);
        $discountPercent = $discount * 100;
        $bonusRate = $this->memberDayService->getMemberDayPointsBonus() * 100;

        // 构建通知内容
        $message = [
            'title' => '🎉 明天是会员日',
            'content' => "亲爱的{$user->nickname}，明天是每月会员日！",
            'privileges' => [
                "💰 全场消费享 {$discountPercent}% 折扣",
                "✨ 积分获取额外 +{$bonusRate}% 加成",
            ],
            'tips' => '会员日当天到店消费即可自动享受以上优惠，不要错过哦！',
        ];

        // TODO: 实际发送通知（微信模板消息、短信等）
        // 这里可以通过事件或队列发送
        // event(new MemberDayReminderEvent($user, $message));
        
        Log::debug('会员日提醒通知已准备', [
            'user_id' => $user->id,
            'level' => $level,
            'discount' => $discount,
        ]);
    }
}
