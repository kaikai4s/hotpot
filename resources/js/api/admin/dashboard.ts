/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from '../admin-client';
import type { ApiResponse } from '../../types';

export interface DashboardStats {
  today_reservations: number;
  reservations_growth: number;
  pending_reviews: number;
  active_queue: number;
  today_revenue: string;
  revenue_growth: number;
}

export interface PendingTask {
  id: string;
  type: 'review' | 'reservation';
  title: string;
  description: string;
  count: number;
}

export interface DashboardStatisticsResponse {
  stats: DashboardStats;
  pending_tasks: PendingTask[];
}

export const adminDashboardApi = {
  /**
   * 获取仪表盘统计数据
   */
  getStatistics: (): Promise<ApiResponse<DashboardStatisticsResponse>> => {
    return adminApiClient.get('/admin/v1/dashboard/statistics');
  },
};

