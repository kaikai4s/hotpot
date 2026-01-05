<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Console\Command;

class GenerateInviteCodesForExistingUsers extends Command
{
    protected $signature = 'invitation:generate-codes';
    protected $description = '为现有用户生成邀请码';

    public function __construct(
        private InvitationService $invitationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('开始为现有用户生成邀请码...');

        $users = User::whereNull('invite_code')->get();
        $count = 0;

        foreach ($users as $user) {
            try {
                $inviteCode = $this->invitationService->generateInviteCode($user);
                $count++;
                $this->line("用户 {$user->id} ({$user->nickname}): {$inviteCode}");
            } catch (\Exception $e) {
                $this->error("用户 {$user->id} 生成邀请码失败: " . $e->getMessage());
            }
        }

        $this->info("完成！共为 {$count} 个用户生成邀请码");

        return Command::SUCCESS;
    }
}

