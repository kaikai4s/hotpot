/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from '../admin-client';

export interface OrderFilters {
  status?: 'pending' | 'paid' | 'completed' | 'cancelled';
  payment_method?: 'wechat' | 'mock';
  order_no?: string;
  user_id?: number;
  date_from?: string;
  date_to?: string;
  page?: number;
  page_size?: number;
}

export interface Order {
  id: number;
  order_no: string;
  user_id: number;
  table_id?: number;
  total_amount: string;
  status: 'pending' | 'paid' | 'completed' | 'cancelled';
  payment_method?: 'wechat' | 'mock';
  payment_transaction_id?: string;
  payment_data?: any;
  paid_at?: string;
  completed_at?: string;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    nickname: string;
    phone: string;
  };
  table?: {
    id: number;
    name: string;
  };
  items?: Array<{
    id: number;
    dish_id: number;
    quantity: number;
    price: string;
    subtotal: string;
    dish?: {
      id: number;
      name: string;
      price: string;
    };
  }>;
}

export const adminOrderApi = {
  /**
   * 获取订单列表
   */
  getOrders: (filters?: OrderFilters) => {
    return adminApiClient.get('/admin/v1/orders', { params: filters });
  },

  /**
   * 获取订单详情
   */
  getOrder: (id: number) => {
    return adminApiClient.get(`/admin/v1/orders/${id}`);
  },

  /**
   * 更新订单状态
   */
  updateStatus: (id: number, status: 'pending' | 'paid' | 'completed' | 'cancelled') => {
    return adminApiClient.put(`/admin/v1/orders/${id}/status`, { status });
  },

  /**
   * 取消订单
   */
  cancel: (id: number) => {
    return adminApiClient.post(`/admin/v1/orders/${id}/cancel`);
  },

  /**
   * 完成订单
   */
  complete: (id: number) => {
    return adminApiClient.post(`/admin/v1/orders/${id}/complete`);
  },
};

