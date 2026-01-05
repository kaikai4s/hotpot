/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';

export interface AchievementTemplate {
  id: number;
  name: string;
  description: string | null;
  icon: string | null;
  category: 'consume' | 'review' | 'invite' | 'checkin' | 'points';
  target_value: Record<string, any>;
  reward_points: number;
  reward_coupon_id: number | null;
  is_active: boolean;
  sort_order: number;
}

export interface UserAchievement {
  id: number;
  user_id: number;
  achievement_template_id: number;
  progress: {
    current: number;
    target: number;
  };
  completed_at: string | null;
  reward_issued: boolean;
  achievement_template: AchievementTemplate;
}

export const achievementApi = {
  /**
   * 获取成就列表
   */
  getAchievements: (category?: string): Promise<{ code: number; message: string; data: { achievements: Record<string, UserAchievement[]> | UserAchievement[]; statistics: { completed: number; total: number; progress: number } } }> => {
    return apiClient.get('/v1/achievements', { params: category ? { category } : {} });
  },

  /**
   * 获取成就详情
   */
  getAchievement: (id: number): Promise<{ code: number; message: string; data: UserAchievement }> => {
    return apiClient.get(`/v1/achievements/${id}`);
  },

  /**
   * 佩戴称号
   */
  equipTitle: (achievementId: number): Promise<{ code: number; message: string; data: { equipped_title: string | null } }> => {
    return apiClient.post('/v1/achievements/equip-title', { achievement_id: achievementId });
  },

  /**
   * 卸下称号
   */
  unequipTitle: (): Promise<{ code: number; message: string; data: { equipped_title: null } }> => {
    return apiClient.post('/v1/achievements/unequip-title');
  },
};

