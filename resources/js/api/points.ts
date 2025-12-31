import adminApiClient from './admin-client';
import type { ApiResponse, Pagination } from '../types';

export interface MemberPoint {
  id: number;
  user_id: number;
  total_points: number;
  available_points: number;
  frozen_points: number;
  level: 'bronze' | 'silver' | 'gold' | 'platinum';
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    nickname: string;
    phone: string;
    avatar_url?: string;
  };
}

export interface PointTransaction {
  id: number;
  user_id: number;
  type: 'earn' | 'redeem' | 'expire' | 'adjust';
  points: number;
  balance_after: number;
  source_type?: string;
  source_id?: number;
  description?: string;
  created_at: string;
  updated_at: string;
}

interface PointsListResponse {
  points: MemberPoint[];
  pagination: Pagination;
}

interface TransactionsResponse {
  transactions: PointTransaction[];
  pagination: Pagination;
}

interface AdjustPointsPayload {
  points: number;
  type: 'earn' | 'redeem' | 'adjust';
  description?: string;
}

export const adminPointsApi = {
  /**
   * 获取积分用户列表
   */
  getList: (params?: {
    search?: string;
    level?: string;
    sort_by?: string;
    sort_order?: 'asc' | 'desc';
    per_page?: number;
    page?: number;
  }): Promise<ApiResponse<PointsListResponse>> => {
    return adminApiClient.get('/admin/v1/points', { params });
  },

  /**
   * 获取用户积分详情
   */
  getDetail: (userId: number): Promise<ApiResponse<{ member_point: MemberPoint }>> => {
    return adminApiClient.get(`/admin/v1/points/${userId}`);
  },

  /**
   * 获取用户积分交易记录
   */
  getTransactions: (
    userId: number,
    params?: {
      type?: string;
      start_date?: string;
      end_date?: string;
      per_page?: number;
    }
  ): Promise<ApiResponse<TransactionsResponse>> => {
    return adminApiClient.get(`/admin/v1/points/${userId}/transactions`, { params });
  },

  /**
   * 调整用户积分
   */
  adjustPoints: (userId: number, payload: AdjustPointsPayload): Promise<ApiResponse<{ member_point: MemberPoint }>> => {
    return adminApiClient.post(`/admin/v1/points/${userId}/adjust`, payload);
  },
};

