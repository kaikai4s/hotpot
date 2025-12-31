/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';
import type { ApiResponse } from '../types';

export interface UserCoupon {
  id: number;
  user_id: number;
  coupon_id: number;
  code: string;
  status: 'unused' | 'used' | 'expired';
  used_at: string | null;
  expires_at: string | null;
  obtained_from: string | null;
  obtained_at: string | null;
  created_at: string;
  updated_at: string;
  coupon?: {
    id: number;
    name: string;
    type: 'fixed_amount' | 'percentage' | 'dish_exchange';
    value: number;
    min_amount: number;
    description: string | null;
    usage_instructions: string | null;
    dish?: {
      id: number;
      name: string;
      price: number;
    };
  };
}

export const couponApi = {
  /**
   * 获取用户已拥有的优惠券
   */
  getUserCoupons: (params?: {
    min_amount?: number;
  }): Promise<ApiResponse<{ coupons: UserCoupon[] }>> => {
    return apiClient.get('/v1/coupons/my', { params });
  },

  /**
   * 获取可兑换的优惠券列表
   */
  getAvailableCoupons: (): Promise<ApiResponse<{ coupons: any[] }>> => {
    return apiClient.get('/v1/coupons/available');
  },
};

