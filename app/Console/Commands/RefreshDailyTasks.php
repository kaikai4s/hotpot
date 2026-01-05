<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TaskService;
use Illuminate\Console\Command;

class RefreshDailyTasks extends Command
{
    protected $signature = 'tasks:refresh-daily';
    protected $description = '为所有活跃用户刷新每日任务';

    public function __construct(
        private TaskService $taskService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('开始刷新每日任务...');

        $users = User::where('is_active', true)->get();
        $count = 0;

        foreach ($users as $user) {
            try {
                $this->taskService->createDailyTasksForUser($user);
                $count++;
            } catch (\Exception $e) {
                $this->error("用户 {$user->id} 创建每日任务失败: " . $e->getMessage());
            }
        }

        $this->info("每日任务刷新完成！共处理 {$count} 个用户");

        return Command::SUCCESS;
    }
}

