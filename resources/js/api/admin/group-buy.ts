/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from '../admin-client';

export interface ApiResponse<T> {
  code: number;
  message: string;
  data?: T;
}

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
  orders_count?: number;
  paid_orders_count?: number;
  used_orders_count?: number;
}

export interface GroupBuyListResponse {
  group_buys: GroupBuy[];
  pagination: {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
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
  user?: {
    id: number;
    nickname?: string;
    phone?: string;
  };
  order?: any;
  groupBuy?: GroupBuy;
}

export const groupBuyApi = {
  /**
   * 获取团购列表
   */
  getList(params?: {
    search?: string;
    status?: string;
    is_active?: boolean;
    sort_by?: string;
    sort_order?: 'asc' | 'desc';
    per_page?: number;
    page?: number;
  }): Promise<ApiResponse<GroupBuyListResponse>> {
    return adminApiClient.get('/admin/v1/group-buys', { params });
  },

  /**
   * 获取团购详情
   */
  getDetail(id: number): Promise<ApiResponse<{ group_buy: GroupBuy }>> {
    return adminApiClient.get(`/admin/v1/group-buys/${id}`);
  },

  /**
   * 创建团购
   */
  create(data: Partial<GroupBuy> & { items: GroupBuyItem[] }): Promise<ApiResponse<{ group_buy: GroupBuy }>> {
    return adminApiClient.post('/admin/v1/group-buys', data);
  },

  /**
   * 更新团购
   */
  update(id: number, data: Partial<GroupBuy> & { items?: GroupBuyItem[] }): Promise<ApiResponse<{ group_buy: GroupBuy }>> {
    return adminApiClient.put(`/admin/v1/group-buys/${id}`, data);
  },

  /**
   * 删除团购
   */
  delete(id: number): Promise<ApiResponse<void>> {
    return adminApiClient.delete(`/admin/v1/group-buys/${id}`);
  },

  /**
   * 更新团购状态
   */
  updateStatus(id: number, status: GroupBuy['status']): Promise<ApiResponse<{ group_buy: GroupBuy }>> {
    return adminApiClient.put(`/admin/v1/group-buys/${id}/status`, { status });
  },

  /**
   * 获取团购订单列表
   */
  getOrders(id: number, params?: {
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
    return adminApiClient.get(`/admin/v1/group-buys/${id}/orders`, { params });
  },
};

