import adminApiClient from './admin-client';
import type { ApiResponse } from '../types';

export interface PointStatistic {
  id: number;
  stat_date: string;
  total_earned: number;
  total_redeemed: number;
  total_expired: number;
  active_users: number;
  created_at: string;
  updated_at: string;
}

export interface PointStatisticsReport {
  statistics: PointStatistic[];
  summary: {
    total_earned: number;
    total_redeemed: number;
    total_expired: number;
    total_active_users: number;
    avg_earned_per_day: number;
    avg_redeemed_per_day: number;
  };
}

export interface UserRankingItem {
  user_id: number;
  nickname: string;
  avatar_url?: string;
  total_points: number;
  available_points: number;
  level: string;
}

export interface SourceStatisticsItem {
  source_type: string;
  total_points: number;
  count: number;
}

interface PointStatisticsReportResponse {
  statistics: PointStatistic[];
  summary: PointStatisticsReport['summary'];
}

interface UserRankingResponse {
  ranking: UserRankingItem[];
}

interface SourceStatisticsResponse {
  statistics: SourceStatisticsItem[];
}

export const adminPointStatisticsApi = {
  /**
   * 获取积分统计报表
   */
  getReport: (params?: {
    start_date?: string;
    end_date?: string;
  }): Promise<ApiResponse<PointStatisticsReportResponse>> => {
    return adminApiClient.get('/admin/v1/point-statistics/report', { params });
  },

  /**
   * 获取用户积分排行榜
   */
  getRanking: (params?: {
    limit?: number;
  }): Promise<ApiResponse<UserRankingResponse>> => {
    return adminApiClient.get('/admin/v1/point-statistics/ranking', { params });
  },

  /**
   * 获取积分来源统计
   */
  getSourceStatistics: (params: {
    start_date: string;
    end_date: string;
  }): Promise<ApiResponse<SourceStatisticsResponse>> => {
    return adminApiClient.get('/admin/v1/point-statistics/source-statistics', { params });
  },
};

