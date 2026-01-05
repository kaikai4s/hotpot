/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from '../admin-client';

export interface AchievementTemplate {
  id: number;
  name: string;
  description?: string | null;
  icon?: string | null;
  category: 'consume' | 'review' | 'invite' | 'checkin' | 'points';
  target_value: Record<string, any>;
  reward_points: number;
  reward_coupon_id?: number | null;
  is_active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
  reward_coupon?: any;
}

export interface AchievementTemplatesResponse {
  templates: AchievementTemplate[];
  pagination: {
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
  };
}

export const achievementTemplateApi = {
  /**
   * 获取成就模板列表
   */
  getList: (params?: {
    category?: string;
    is_active?: boolean;
    page?: number;
    page_size?: number;
  }): Promise<{ code: number; message: string; data: AchievementTemplatesResponse }> => {
    return adminApiClient.get('/admin/v1/achievement-templates', { params });
  },

  /**
   * 获取成就模板详情
   */
  getById: (id: number): Promise<{ code: number; message: string; data: { template: AchievementTemplate } }> => {
    return adminApiClient.get(`/admin/v1/achievement-templates/${id}`);
  },

  /**
   * 创建成就模板
   */
  create: (data: Partial<AchievementTemplate>): Promise<{ code: number; message: string; data: { template: AchievementTemplate } }> => {
    return adminApiClient.post('/admin/v1/achievement-templates', data);
  },

  /**
   * 更新成就模板
   */
  update: (id: number, data: Partial<AchievementTemplate>): Promise<{ code: number; message: string; data: { template: AchievementTemplate } }> => {
    return adminApiClient.put(`/admin/v1/achievement-templates/${id}`, data);
  },

  /**
   * 删除成就模板
   */
  delete: (id: number): Promise<{ code: number; message: string }> => {
    return adminApiClient.delete(`/admin/v1/achievement-templates/${id}`);
  },
};

