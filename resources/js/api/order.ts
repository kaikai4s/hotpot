/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';

export interface OrderItem {
  dish_id: number;
  quantity: number;
}

export interface CreateOrderPayload {
  items: OrderItem[];
  table_id?: number;
  reservation_id?: number;
  use_deposit?: boolean;
  use_points?: boolean;
  points_used?: number;
}

export interface Order {
  id: number;
  order_no: string;
  user_id: number;
  table_id?: number;
  reservation_id?: number;
  total_amount: string;
  deposit_discount?: string;
  points_discount?: string;
  points_used?: number;
  final_amount?: string;
  status: 'pending' | 'paid' | 'pending_review' | 'completed' | 'cancelled';
  payment_method?: 'wechat' | 'mock';
  payment_transaction_id?: string;
  payment_data?: any;
  paid_at?: string;
  completed_at?: string;
  created_at: string;
  updated_at: string;
  items?: OrderItemDetail[];
  user?: any;
  table?: any;
  reservation?: any;
}

export interface OrderItemDetail {
  id: number;
  order_id: number;
  dish_id: number;
  quantity: number;
  price: string;
  subtotal: string;
  dish?: any;
}

export interface PaymentMethod {
  code: 'wechat' | 'mock';
  name: string;
  description: string;
  is_default: boolean;
}

export interface PayOrderPayload {
  payment_method: 'wechat' | 'mock';
}

export interface UpdateOrderPayload {
  reservation_id?: number;
  use_deposit?: boolean;
  use_points?: boolean;
  points_used?: number;
  user_coupon_id?: number | null;
}

export const orderApi = {
  /**
   * 获取可用的支付方式
   */
  async getPaymentMethods() {
    return apiClient.get<{
      code: number;
      message: string;
      data: {
        methods: PaymentMethod[];
        default_method: string;
      };
    }>('/v1/orders/payment-methods');
  },

  /**
   * 创建订单
   */
  async create(payload: CreateOrderPayload) {
    return apiClient.post<{
      code: number;
      message: string;
      data: Order;
    }>('/v1/orders', payload);
  },

  /**
   * 获取订单列表
   */
  async getList(params?: { status?: string }) {
    return apiClient.get<{
      code: number;
      message: string;
      data: {
        data: Order[];
        current_page: number;
        total_pages: number;
        total_count: number;
        page_size: number;
      };
    }>('/v1/orders', { params });
  },

  /**
   * 获取订单详情
   */
  async getDetail(orderId: number) {
    return apiClient.get<{
      code: number;
      message: string;
      data: Order;
    }>(`/v1/orders/${orderId}`);
  },

  /**
   * 更新订单（应用抵扣选项）
   */
  async update(orderId: number, payload: UpdateOrderPayload) {
    return apiClient.put<{
      code: number;
      message: string;
      data: Order;
    }>(`/v1/orders/${orderId}`, payload);
  },

  /**
   * 支付订单
   */
  async pay(orderId: number, payload: PayOrderPayload) {
    return apiClient.post<{
      code: number;
      message: string;
      data: Order;
    }>(`/v1/orders/${orderId}/pay`, payload);
  },

  /**
   * 取消订单
   */
  async cancel(orderId: number) {
    return apiClient.post<{
      code: number;
      message: string;
      data: Order;
    }>(`/v1/orders/${orderId}/cancel`);
  },

  /**
   * 跳过评价，完成订单
   */
  async skipReview(orderId: number) {
    return apiClient.post<{
      code: number;
      message: string;
      data: Order;
    }>(`/v1/orders/${orderId}/skip-review`);
  },
};

