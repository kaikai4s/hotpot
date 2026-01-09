<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BirthdayPrivilegeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBirthdayReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'birthday:send-reminders {--days=7 : 提前多少天发送提醒}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '发送生日提醒通知（提前7天）';

    public function __construct(
        private BirthdayPrivilegeService $birthdayPrivilegeService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysAhead = (int) $this->option('days');
        
        $this->info("正在查找 {$daysAhead} 天后过生日的用户...");

        try {
            $userBirthdays = $this->birthdayPrivilegeService->getUsersWithUpcomingBirthday($daysAhead);

            if ($userBirthdays->isEmpty()) {
                $this->info('没有找到即将过生日的用户');
                return Command::SUCCESS;
            }

            $sentCount = 0;
            $failedCount = 0;

            foreach ($userBirthdays as $userBirthday) {
                $user = $userBirthday->user;
                
                if (!$user) {
                    continue;
                }

                try {
                    // 发送生日提醒
                    $this->sendBirthdayReminderNotification($user, $daysAhead);
                    $sentCount++;
                    
                    $this->line("已发送提醒给用户: {$user->id} ({$user->nickname})");
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('发送生日提醒失败', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("发送失败: 用户 {$user->id} - {$e->getMessage()}");
                }
            }

            $this->info("生日提醒发送完成: 成功 {$sentCount} 条, 失败 {$failedCount} 条");

            Log::info('生日提醒定时任务执行完成', [
                'days_ahead' => $daysAhead,
                'total_users' => $userBirthdays->count(),
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('生日提醒任务执行失败: ' . $e->getMessage());
            Log::error('生日提醒定时任务执行失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * 发送生日提醒通知
     */
    private function sendBirthdayReminderNotification($user, int $daysAhead): void
    {
        // 获取用户会员等级对应的优惠券面额
        $memberPoint = $user->memberPoints;
        $level = $memberPoint?->level ?? 'bronze';
        $couponAmount = $this->birthdayPrivilegeService->getBirthdayCouponAmount($level);

        // 构建通知内容
        $message = [
            'title' => '🎂 生日特权即将开启',
            'content' => "亲爱的{$user->nickname}，您的生日即将到来！{$daysAhead}天后您将享受以下专属特权：",
            'privileges' => [
                "🎁 {$couponAmount}元生日专属优惠券",
                '🍰 免费生日甜品一份',
                '✨ 消费积分双倍',
            ],
            'tips' => '生日当天到店消费即可自动获得以上特权，记得来店庆祝哦！',
        ];

        // TODO: 实际发送通知（微信模板消息、短信等）
        // 这里可以通过事件或队列发送
        // event(new BirthdayReminderEvent($user, $message));
        
        Log::info('生日提醒通知已准备', [
            'user_id' => $user->id,
            'days_ahead' => $daysAhead,
            'coupon_amount' => $couponAmount,
        ]);
    }
}
