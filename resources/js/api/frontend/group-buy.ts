/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from '../client';

export interface GroupBuyItem {
  id?: number;
  dish_id: number;
  quantity: number;
  sort_order?: number;
  dish?: {
    id: number;
    name: string;
    price: number;
    image_url?: string;
  };
}

export interface GroupBuy {
  id: number;
  name: string;
  description?: string;
  image_url?: string;
  original_price: number;
  group_price: number;
  stock: number;
  sold_count: number;
  start_time?: string;
  end_time?: string;
  valid_from?: string;
  valid_to?: string;
  valid_days: number;
  limit_per_user: number;
  is_active: boolean;
  sort_order: number;
  rules?: Record<string, any>;
  status: 'draft' | 'published' | 'ongoing' | 'ended' | 'cancelled';
  created_at: string;
  updated_at: string;
  items?: GroupBuyItem[];
}

export interface GroupBuyOrder {
  id: number;
  group_buy_id: number;
  user_id: number;
  order_id?: number;
  quantity: number;
  total_price: number;
  status: 'pending' | 'paid' | 'used' | 'expired' | 'refunded';
  paid_at?: string;
  used_at?: string;
  expires_at?: string;
  created_at: string;
  updated_at: string;
  groupBuy?: GroupBuy;
}

export interface ApiResponse<T> {
  code: number;
  message: string;
  data?: T;
}

export const groupBuyApi = {
  /**
   * 获取团购列表（前台）
   */
  getList(params?: {
    per_page?: number;
    page?: number;
  }): Promise<ApiResponse<{
    group_buys: GroupBuy[];
    pagination: {
      total: number;
      per_page: number;
      current_page: number;
      last_page: number;
    };
  }>> {
    return apiClient.get('/api/v1/group-buys', { params });
  },

  /**
   * 获取团购详情（前台）
   */
  getDetail(id: number): Promise<ApiResponse<{ group_buy: GroupBuy }>> {
    return apiClient.get(`/api/v1/group-buys/${id}`);
  },

  /**
   * 购买团购
   */
  purchase(id: number, data: {
    quantity: number;
    payment_method: 'wechat' | 'mock';
  }): Promise<ApiResponse<{
    group_buy_order: GroupBuyOrder;
    order: any;
  }>> {
    return apiClient.post(`/api/v1/group-buys/${id}/purchase`, data);
  },

  /**
   * 获取我的团购订单列表
   */
  getMyOrders(params?: {
    status?: string;
    per_page?: number;
    page?: number;
  }): Promise<ApiResponse<{
    orders: GroupBuyOrder[];
    pagination: {
      total: number;
      per_page: number;
      current_page: number;
      last_page: number;
    };
  }>> {
    return apiClient.get('/api/v1/group-buys/my/orders', { params });
  },

  /**
   * 获取我的团购订单详情
   */
  getMyOrder(id: number): Promise<ApiResponse<{ order: GroupBuyOrder }>> {
    return apiClient.get(`/api/v1/group-buys/my/orders/${id}`);
  },
};

