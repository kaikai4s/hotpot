/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';

export interface UserShare {
  id: number;
  user_id: number;
  share_type: 'review' | 'order' | 'achievement' | 'task';
  share_content_id: number;
  share_platform: 'wechat' | 'moments';
  reward_points: number;
  reward_issued: boolean;
  created_at: string;
}

export interface ShareStats {
  total_shares: number;
  rewarded_shares: number;
  total_reward_points: number;
  by_type: Record<string, { count: number; total_points: number }>;
}

export const shareApi = {
  /**
   * 记录分享
   */
  recordShare: (shareType: string, shareContentId: number, sharePlatform: string = 'moments'): Promise<{ code: number; message: string; data: UserShare }> => {
    return apiClient.post('/v1/shares', {
      share_type: shareType,
      share_content_id: shareContentId,
      share_platform: sharePlatform,
    });
  },

  /**
   * 获取分享统计
   */
  getStats: (type?: string): Promise<{ code: number; message: string; data: ShareStats }> => {
    return apiClient.get('/v1/shares/stats', { params: type ? { type } : {} });
  },

  /**
   * 获取分享列表
   */
  getShares: (type?: string, limit?: number): Promise<{ code: number; message: string; data: { shares: UserShare[] } }> => {
    return apiClient.get('/v1/shares', { params: { type, limit } });
  },
};

