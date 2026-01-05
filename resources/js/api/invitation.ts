/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';

export interface InvitationInfo {
  invite_code: string;
  total_invites: number;
  successful_invites: number;
  total_rewards_points: number;
  friends: Array<{
    id: number;
    nickname: string;
    avatar_url: string | null;
    status: string;
    registered_at: string | null;
    first_order_at: string | null;
    reward_issued: boolean;
  }>;
}

export interface FriendInfo {
  id: number;
  nickname: string;
  avatar_url: string | null;
  status: string;
  registered_at: string | null;
  first_order_at: string | null;
  reward_issued: boolean;
}

export const invitationApi = {
  /**
   * 获取我的邀请信息
   */
  getMyInvitation: (): Promise<{ code: number; message: string; data: InvitationInfo }> => {
    return apiClient.get('/v1/invitations/my');
  },

  /**
   * 获取我邀请的好友列表
   */
  getFriends: (): Promise<{ code: number; message: string; data: FriendInfo[] }> => {
    return apiClient.get('/v1/invitations/friends');
  },

  /**
   * 使用邀请码注册
   */
  registerWithInviteCode: (inviteCode: string): Promise<{ code: number; message: string; data: any }> => {
    return apiClient.post('/v1/invitations/register', { invite_code: inviteCode });
  },
};

