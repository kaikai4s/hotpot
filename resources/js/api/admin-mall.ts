/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from './admin-client';

export interface MallProduct {
  id: number;
  name: string;
  description: string | null;
  image_url: string | null;
  type: 'physical' | 'experience';
  points_required: number;
  stock: number;
  per_user_limit: number | null;
  status: 'active' | 'inactive' | 'sold_out';
  created_at: string;
  updated_at: string;
}

export interface ProductRedemption {
  id: number;
  user_id: number;
  product_id: number;
  points_used: number;
  status: 'pending' | 'shipped' | 'completed' | 'cancelled';
  shipping_address: string | null;
  tracking_number: string | null;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    nickname: string;
    phone: string;
  };
  product?: MallProduct;
}

export interface MemberDayConfig {
  id: number;
  day_of_month: number;
  is_enabled: boolean;
  base_discount: number;
  points_bonus_rate: number;
  discount_by_level: Record<string, number>;
  override_date: string | null;
}

interface ApiResponse<T> {
  code: number;
  message: string;
  data?: T;
}

export const adminMallApi = {
  // 商品管理
  getProducts: (params?: {
    type?: string;
    status?: string;
    keyword?: string;
    per_page?: number;
    page?: number;
  }): Promise<ApiResponse<{
    products: MallProduct[];
    pagination: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  }>> => adminApiClient.get('/admin/v1/mall/products', { params }),

  getProduct: (id: number): Promise<ApiResponse<{ product: MallProduct }>> =>
    adminApiClient.get(`/admin/v1/mall/products/${id}`),

  createProduct: (data: Partial<MallProduct>): Promise<ApiResponse<{ product: MallProduct }>> =>
    adminApiClient.post('/admin/v1/mall/products', data),

  updateProduct: (id: number, data: Partial<MallProduct>): Promise<ApiResponse<{ product: MallProduct }>> =>
    adminApiClient.put(`/admin/v1/mall/products/${id}`, data),

  deleteProduct: (id: number): Promise<ApiResponse<null>> =>
    adminApiClient.delete(`/admin/v1/mall/products/${id}`),

  updateProductStatus: (id: number, status: string): Promise<ApiResponse<{ product: MallProduct }>> =>
    adminApiClient.put(`/admin/v1/mall/products/${id}/status`, { status }),

  // 兑换管理
  getRedemptions: (params?: {
    status?: string;
    per_page?: number;
    page?: number;
  }): Promise<ApiResponse<{
    redemptions: ProductRedemption[];
    pagination: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  }>> => adminApiClient.get('/admin/v1/mall/redemptions', { params }),

  updateRedemptionStatus: (id: number, status: string, trackingNumber?: string): Promise<ApiResponse<{ redemption: ProductRedemption }>> =>
    adminApiClient.put(`/admin/v1/mall/redemptions/${id}/status`, {
      status,
      tracking_number: trackingNumber,
    }),
};

export const adminMemberDayApi = {
  getConfig: (): Promise<ApiResponse<{ config: MemberDayConfig }>> =>
    adminApiClient.get('/admin/v1/member-day/config'),

  updateConfig: (data: Partial<MemberDayConfig>): Promise<ApiResponse<{ config: MemberDayConfig }>> =>
    adminApiClient.put('/admin/v1/member-day/config', data),

  setOverride: (date: string | null): Promise<ApiResponse<{ config: MemberDayConfig }>> =>
    adminApiClient.put('/admin/v1/member-day/override', { override_date: date }),
};
