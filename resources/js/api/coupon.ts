import adminApiClient from './admin-client';
import type { ApiResponse, Pagination } from '../types';

export interface Coupon {
  id: number;
  name: string;
  type: 'discount' | 'cash' | 'points' | 'dish_exchange' | 'fixed_amount' | 'percentage';
  value: number;
  dish_id?: number | null;
  min_amount: number;
  points_required: number;
  stock: number;
  valid_from: string | null;
  valid_to: string | null;
  is_active: boolean;
  description?: string | null;
  usage_instructions?: string | null;
  image_url?: string | null;
  created_at: string;
  updated_at: string;
  used_count?: number;
  unused_count?: number;
  dish?: {
    id: number;
    name: string;
    price: number;
  };
}

export interface UserCoupon {
  id: number;
  user_id: number;
  coupon_id: number;
  code: string;
  status: 'unused' | 'used' | 'expired';
  used_at: string | null;
  expires_at: string | null;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    nickname: string;
    phone: string;
  };
  coupon?: Coupon;
}

interface CouponsListResponse {
  coupons: Coupon[];
  pagination: Pagination;
}

interface CouponUsageResponse {
  usage: UserCoupon[];
  pagination: Pagination;
}

interface CouponCreateUpdatePayload {
  name: string;
  type: 'discount' | 'cash' | 'points' | 'dish_exchange' | 'fixed_amount' | 'percentage';
  value: number;
  dish_id?: number | null;
  min_amount?: number;
  points_required: number;
  stock: number;
  valid_from?: string | null;
  valid_to?: string | null;
  is_active?: boolean;
  description?: string | null;
  usage_instructions?: string | null;
  image_url?: string | null;
}

export const adminCouponApi = {
  /**
   * 获取优惠券列表
   */
  getList: (params?: {
    search?: string;
    type?: string;
    is_active?: boolean;
    per_page?: number;
  }): Promise<ApiResponse<CouponsListResponse>> => {
    return adminApiClient.get('/admin/v1/coupons', { params });
  },

  /**
   * 获取优惠券详情
   */
  getDetail: (id: number): Promise<ApiResponse<{ coupon: Coupon }>> => {
    return adminApiClient.get(`/admin/v1/coupons/${id}`);
  },

  /**
   * 创建优惠券
   */
  create: (payload: CouponCreateUpdatePayload): Promise<ApiResponse<{ coupon: Coupon }>> => {
    return adminApiClient.post('/admin/v1/coupons', payload);
  },

  /**
   * 更新优惠券
   */
  update: (id: number, payload: Partial<CouponCreateUpdatePayload>): Promise<ApiResponse<{ coupon: Coupon }>> => {
    return adminApiClient.put(`/admin/v1/coupons/${id}`, payload);
  },

  /**
   * 删除优惠券
   */
  delete: (id: number): Promise<ApiResponse<void>> => {
    return adminApiClient.delete(`/admin/v1/coupons/${id}`);
  },

  /**
   * 获取优惠券使用记录
   */
  getUsage: (
    id: number,
    params?: {
      status?: string;
      per_page?: number;
    }
  ): Promise<ApiResponse<CouponUsageResponse>> => {
    return adminApiClient.get(`/admin/v1/coupons/${id}/usage`, { params });
  },
};

