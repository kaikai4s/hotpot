import adminApiClient from './admin-client';
import type { ApiResponse } from '../types';

export interface PointAnomaly {
  type: string;
  severity: 'high' | 'medium' | 'low';
  message: string;
  user_id?: number;
  transaction_id?: number;
  points?: number;
  source_type?: string;
  created_at?: string;
  transaction_count?: number;
  time_window?: number;
  avg_daily_earned?: number;
  available_points?: number;
  total_points?: number;
  expiration_rate?: number;
  total_earned?: number;
  total_expired?: number;
}

export interface AnomalySummary {
  total: number;
  high_severity: number;
  medium_severity: number;
  by_type: Record<string, number>;
}

interface PointAnomalyListResponse {
  anomalies: PointAnomaly[];
  total: number;
}

interface PointAnomalySummaryResponse {
  summary: AnomalySummary;
}

export const adminPointAnomalyApi = {
  /**
   * 获取积分异常列表
   */
  getList: (params?: {
    large_earn_threshold?: number;
    max_transactions_per_hour?: number;
    time_window_hours?: number;
    expiration_threshold?: number;
  }): Promise<ApiResponse<PointAnomalyListResponse>> => {
    return adminApiClient.get('/admin/v1/point-anomalies', { params });
  },

  /**
   * 获取异常统计摘要
   */
  getSummary: (): Promise<ApiResponse<PointAnomalySummaryResponse>> => {
    return adminApiClient.get('/admin/v1/point-anomalies/summary');
  },
};

