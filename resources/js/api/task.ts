/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';

export interface TaskTemplate {
  id: number;
  name: string;
  description: string | null;
  type: 'daily' | 'weekly' | 'achievement';
  category: 'sign' | 'review' | 'share' | 'order' | 'invite' | 'browse';
  target_value: Record<string, any>;
  reward_points: number;
  reward_coupon_id: number | null;
  is_active: boolean;
  sort_order: number;
}

export interface UserTask {
  id: number;
  user_id: number;
  task_template_id: number;
  status: 'pending' | 'in_progress' | 'completed' | 'expired';
  progress: {
    current: number;
    target: number;
  };
  completed_at: string | null;
  reward_issued: boolean;
  expires_at: string | null;
  task_template: TaskTemplate;
}

export const taskApi = {
  /**
   * 获取任务列表
   */
  getTasks: (type?: string): Promise<{ code: number; message: string; data: { tasks: Record<string, UserTask[]> } }> => {
    return apiClient.get('/v1/tasks', { params: type ? { type } : {} });
  },

  /**
   * 获取任务详情
   */
  getTask: (id: number): Promise<{ code: number; message: string; data: UserTask }> => {
    return apiClient.get(`/v1/tasks/${id}`);
  },

  /**
   * 完成任务
   */
  completeTask: (id: number): Promise<{ code: number; message: string; data: UserTask }> => {
    return apiClient.post(`/v1/tasks/${id}/complete`);
  },
};

