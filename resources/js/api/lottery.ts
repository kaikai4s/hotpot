/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';
import type { ApiResponse, Pagination } from '../types';

export interface LotteryActivity {
  id: number;
  name: string;
  description: string | null;
  image_url: string | null;
  start_time: string;
  end_time: string;
  daily_limit: number;
  total_limit: number;
  points_cost: number;
  is_active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
  prizes?: LotteryPrize[];
}

export interface LotteryPrize {
  id: number;
  lottery_activity_id: number;
  name: string;
  description: string | null;
  image_url: string | null;
  prize_type: 'coupon' | 'points' | 'dish';
  prize_id: number | null;
  prize_value: number | null;
  probability: number;
  stock: number;
  daily_stock: number;
  sort_order: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  // 剩余库存信息
  remaining_stock?: number | null;
  used_stock?: number;
  remaining_daily_stock?: number | null;
  used_daily_stock?: number;
  is_available?: boolean;
  // 实时概率（基于可用奖品的总概率）
  real_time_probability?: number;
}

export interface LotteryRecord {
  id: number;
  user_id: number;
  lottery_activity_id: number;
  lottery_prize_id: number | null;
  prize_type: 'coupon' | 'points' | 'dish' | 'none';
  prize_id: number | null;
  prize_value: number | null;
  is_winner: boolean;
  created_at: string;
  updated_at: string;
  activity?: LotteryActivity;
  prize?: LotteryPrize;
}

export interface DrawResult {
  is_winner: boolean;
  prize: LotteryPrize | null;
  record: LotteryRecord;
}

export interface UserStats {
  today_draw_count: number;
  total_draw_count: number;
  can_draw: boolean;
}

export interface UserPoints {
  available_points: number;
  total_points: number;
}

export const lotteryApi = {
  /**
   * 获取抽奖活动列表
   */
  getActivities: (): Promise<ApiResponse<{ activities: LotteryActivity[] }>> => {
    return apiClient.get('/v1/lottery/activities');
  },

  /**
   * 获取抽奖活动详情
   */
  getActivity: (id: number): Promise<ApiResponse<{ activity: LotteryActivity; user_stats: UserStats; user_points: UserPoints | null }>> => {
    return apiClient.get(`/v1/lottery/activities/${id}`);
  },

  /**
   * 执行抽奖
   */
  draw: (activityId: number): Promise<ApiResponse<DrawResult>> => {
    return apiClient.post(`/v1/lottery/activities/${activityId}/draw`);
  },

  /**
   * 获取我的抽奖记录
   */
  getMyRecords: (params?: { activity_id?: number; page?: number; page_size?: number }): Promise<ApiResponse<{ records: LotteryRecord[]; pagination: Pagination }>> => {
    return apiClient.get('/v1/lottery/records', { params });
  },
};

