import apiClient from './client';
import type { ApiResponse, Pagination } from '../types';

export interface FrontendLevelInfo {
  id: number;
  name: string;
  code: string;
  icon: string | null;
  color: string | null;
  description: string | null;
  discount_type: 'none' | 'percentage' | 'fixed';
  discount_value: number;
  max_discount_amount: number | null;
  min_order_amount: number;
  multiplier?: number;
}

export interface FrontendNextLevelInfo {
  name: string;
  code: string;
  min_points: number;
}

export interface PointRuleInfo {
  name: string;
  base_ratio?: number;
  min_amount?: number;
  max_points_per_order?: number | null;
  base_points?: number;
  with_image_bonus?: number;
  first_review_bonus?: number;
  use_ratio?: number;
  min_points?: number;
  max_percent?: number;
  expire_days?: number;
}

export interface PointRulesInfo {
  order_earn?: PointRuleInfo | null;
  review_earn?: PointRuleInfo | null;
  review_adoption?: PointRuleInfo | null;
  point_use?: PointRuleInfo | null;
  point_expire?: PointRuleInfo | null;
}

export interface FrontendMemberPoint {
  total_points: number;
  available_points: number;
  frozen_points: number;
  level: string; // 改为 string，因为段位代码现在是动态的
  level_text: string;
  level_info: FrontendLevelInfo | null;
  next_level_info: FrontendNextLevelInfo | null;
  points_to_next_level: number;
  expiring_points: Array<{
    points: number;
    expire_at: string;
    days_left: number;
  }>;
  total_expiring: number;
  rules_info?: PointRulesInfo;
}

export interface FrontendPointTransaction {
  id: number;
  user_id: number;
  type: 'earn' | 'redeem' | 'expire' | 'adjust';
  points: number;
  balance_after: number;
  source_type?: string;
  source_id?: number;
  description?: string;
  expire_at?: string | null;
  created_at: string;
  updated_at: string;
}

export interface FrontendCoupon {
  id: number;
  name: string;
  type: 'discount' | 'cash' | 'points';
  value?: number | null;
  points_required: number;
  stock: number;
  valid_from?: string | null;
  valid_to?: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

interface PointTransactionsResponse {
  transactions: FrontendPointTransaction[];
  pagination: Pagination;
}

interface ExpiringPointsResponse {
  expiring_points: Array<{
    points: number;
    expire_at: string;
    days_left: number;
  }>;
  total_expiring: number;
}

interface AvailableCouponsResponse {
  coupons: FrontendCoupon[];
}

export const frontendPointsApi = {
  /**
   * 获取当前用户积分信息
   */
  getPoints: (): Promise<ApiResponse<FrontendMemberPoint>> => {
    // 添加时间戳防止缓存
    return apiClient.get('/v1/points', {
      params: {
        _t: Date.now(),
      },
    });
  },

  /**
   * 获取所有启用的段位列表
   */
  getLevels: (): Promise<ApiResponse<{ levels: FrontendLevelInfo[] }>> => {
    return apiClient.get('/v1/points/levels');
  },

  /**
   * 获取当前用户积分交易记录
   */
  getTransactions: (params?: {
    type?: 'earn' | 'redeem' | 'expire' | 'adjust';
    start_date?: string;
    end_date?: string;
    per_page?: number;
  }): Promise<ApiResponse<PointTransactionsResponse>> => {
    return apiClient.get('/v1/points/transactions', { params });
  },

  /**
   * 获取即将过期的积分
   */
  getExpiringPoints: (): Promise<ApiResponse<ExpiringPointsResponse>> => {
    return apiClient.get('/v1/points/expiring');
  },

  /**
   * 兑换优惠券
   */
  redeemCoupon: (payload: {
    reward_id: number;
    idempotency_key: string;
  }): Promise<ApiResponse<{ coupon_id: number; points_used: number; remaining_points: number }>> => {
    return apiClient.post('/v1/points/redeem', payload);
  },
};

export const frontendCouponApi = {
  /**
   * 获取可兑换的优惠券列表
   */
  getAvailableCoupons: (): Promise<ApiResponse<AvailableCouponsResponse>> => {
    return apiClient.get('/v1/coupons/available');
  },
};

