import apiClient from './client';
import type { ApiResponse, Pagination } from '../types';

// 会员权益概览
export interface PrivilegeOverview {
  level: string;
  level_name: string;
  level_privileges: LevelPrivilege[];
  next_level_privileges: LevelPrivilege[] | null;
  birthday_info: BirthdayInfo | null;
  member_day_info: MemberDayInfo;
  points_multiplier: number;
}

export interface LevelPrivilege {
  name: string;
  description: string;
  value: string | number;
}

export interface BirthdayInfo {
  birthday: string | null;
  can_modify: boolean;
  last_modified_year: number | null;
  is_birthday_today: boolean;
}

export interface BirthdayPrivileges {
  is_birthday_today: boolean;
  is_in_birthday_period: boolean;
  has_coupon_this_year: boolean;
  has_dessert_this_year: boolean;
  coupon_amount: number;
  available_dessert_voucher: DessertVoucher | null;
  points_multiplier: number;
}

export interface DessertVoucher {
  id: number;
  code: string;
  status: string;
  expires_at: string;
}

export interface MemberDayInfo {
  is_enabled: boolean;
  is_member_day_today: boolean;
  day_of_month: number;
  next_member_day: string;
  days_until_member_day: number;
  discount: number;
  points_bonus_rate: number;
}

export interface MemberDayDiscount {
  is_member_day: boolean;
  discount: number;
  discount_percent: number;
  applicable: boolean;
}

export interface PrivilegeStats {
  total_saved_amount: number;
  total_bonus_points: number;
  birthday_coupons_used: number;
  member_day_orders: number;
}

// 积分商城
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
  shipping_address: ShippingAddress | null;
  tracking_number: string | null;
  created_at: string;
  updated_at: string;
  product?: MallProduct;
}

export interface ShippingAddress {
  name: string;
  phone: string;
  address: string;
}

export interface RedeemCheck {
  can_redeem: boolean;
  message?: string;
}

// API 响应类型
interface ProductsResponse {
  products: MallProduct[];
  pagination: Pagination;
}

interface RedemptionsResponse {
  redemptions: ProductRedemption[];
  pagination: Pagination;
}

// 会员权益 API
export const memberPrivilegeApi = {
  /**
   * 获取会员权益概览
   */
  getPrivileges: (): Promise<ApiResponse<PrivilegeOverview>> => {
    return apiClient.get('/v1/member/privileges');
  },

  /**
   * 获取权益统计
   */
  getPrivilegeStats: (): Promise<ApiResponse<PrivilegeStats>> => {
    return apiClient.get('/v1/member/privileges/stats');
  },

  /**
   * 获取生日信息
   */
  getBirthday: (): Promise<ApiResponse<BirthdayInfo>> => {
    return apiClient.get('/v1/member/birthday');
  },

  /**
   * 设置/修改生日
   */
  setBirthday: (birthday: string): Promise<ApiResponse<{ birthday: string; can_modify: boolean }>> => {
    return apiClient.post('/v1/member/birthday', { birthday });
  },

  /**
   * 获取生日特权状态
   */
  getBirthdayPrivileges: (): Promise<ApiResponse<BirthdayPrivileges>> => {
    return apiClient.get('/v1/member/birthday/privileges');
  },

  /**
   * 获取会员日信息
   */
  getMemberDay: (): Promise<ApiResponse<MemberDayInfo>> => {
    return apiClient.get('/v1/member/member-day');
  },

  /**
   * 获取会员日折扣
   */
  getMemberDayDiscount: (): Promise<ApiResponse<MemberDayDiscount>> => {
    return apiClient.get('/v1/member/member-day/discount');
  },
};

// 积分商城 API
export const pointsMallApi = {
  /**
   * 获取商品列表
   */
  getProducts: (params?: {
    type?: 'physical' | 'experience';
    min_points?: number;
    max_points?: number;
    per_page?: number;
  }): Promise<ApiResponse<ProductsResponse>> => {
    return apiClient.get('/v1/mall/products', { params });
  },

  /**
   * 获取商品详情
   */
  getProduct: (id: number): Promise<ApiResponse<{ product: MallProduct; can_redeem: boolean; redeem_message?: string }>> => {
    return apiClient.get(`/v1/mall/products/${id}`);
  },

  /**
   * 兑换商品
   */
  redeemProduct: (
    productId: number,
    shippingAddress?: ShippingAddress
  ): Promise<ApiResponse<{ redemption_id: number; points_used: number; status: string }>> => {
    return apiClient.post(`/v1/mall/products/${productId}/redeem`, {
      shipping_address: shippingAddress,
    });
  },

  /**
   * 获取兑换记录
   */
  getRedemptions: (params?: {
    status?: string;
    per_page?: number;
  }): Promise<ApiResponse<RedemptionsResponse>> => {
    return apiClient.get('/v1/mall/redemptions', { params });
  },

  /**
   * 获取兑换详情
   */
  getRedemption: (id: number): Promise<ApiResponse<{ redemption: ProductRedemption }>> => {
    return apiClient.get(`/v1/mall/redemptions/${id}`);
  },
};
