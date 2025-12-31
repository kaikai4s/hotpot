/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';
import adminApiClient from './admin-client';
import type { ApiResponse, Reservation, Pagination, Table } from '../types';
import type { RestaurantArea } from './area';

export interface GetReservationsParams {
  status?: string;
  date?: string;
  page?: number;
  page_size?: number;
}

export interface ReservationsResponse {
  reservations: Reservation[];
  pagination: Pagination;
}

export interface GetAvailableTablesParams {
  date: string;
  time_slot: string;
  guest_count: number;
  duration?: number;
}

export interface AvailableTablesResponse {
  tables: Table[];
  available_count: number;
  total_count: number;
  areas?: RestaurantArea[];
}

export interface CreateReservationRequest {
  table_id: number;
  date: string;
  time_slot: string;
  guest_count: number;
  contact_name: string;
  contact_phone: string;
  special_requests?: string;
}

export interface CreateReservationResponse {
  reservation_id: number;
  reservation_code: string;
  status: string;
  table_name: string;
  date: string;
  time_slot: string;
  expires_at?: string;
  deposit_amount?: number;
  deposit_status?: string;
}

export interface PayDepositRequest {
  payment_method: 'wechat' | 'mock';
}

export interface PayDepositResponse {
  reservation_id: number;
  deposit_status: string;
  transaction_id?: string;
  payment_params?: any;
}

export const reservationApi = {
  // 后台管理接口：使用 adminApiClient（需要 admin_token）
  getAdminList: (params?: GetReservationsParams): Promise<ApiResponse<ReservationsResponse>> => {
    return adminApiClient.get('/admin/v1/reservations', { params });
  },
  // 前台用户接口：使用 apiClient（需要 token）
  getAvailableTables: (params: GetAvailableTablesParams): Promise<ApiResponse<AvailableTablesResponse>> => {
    return apiClient.get('/v1/reservations/tables', { params });
  },
  // 前台用户创建预约：使用 apiClient（需要 token）
  create: (data: CreateReservationRequest): Promise<ApiResponse<CreateReservationResponse>> => {
    // 生成幂等性key（基于用户信息、桌位、日期时间）
    const idempotencyKey = `reservation_${data.table_id}_${data.date}_${data.time_slot}_${Date.now()}`;
    return apiClient.post('/v1/reservations', {
      ...data,
      idempotency_key: idempotencyKey,
    });
  },
  // 获取预约详情
  getDetail: (reservationId: number): Promise<ApiResponse<Reservation>> => {
    return apiClient.get(`/v1/reservations/${reservationId}`);
  },
  // 支付预约定金
  payDeposit: (reservationId: number, data: PayDepositRequest): Promise<ApiResponse<PayDepositResponse>> => {
    return apiClient.post(`/v1/reservations/${reservationId}/pay-deposit`, data);
  },
  // 获取用户预约列表（前台）
  getList: (params?: { status?: string; page?: number; page_size?: number }): Promise<ApiResponse<ReservationsResponse>> => {
    return apiClient.get('/v1/reservations', { params });
  },
  // 确认到达
  arrive: (reservationId: number): Promise<ApiResponse<Reservation>> => {
    return apiClient.post(`/v1/reservations/${reservationId}/arrive`);
  },
  // 取消预约
  cancel: (reservationId: number, data?: { reason?: string }): Promise<ApiResponse<{ reservation_id: number; status: string; cancelled_at?: string }>> => {
    return apiClient.put(`/v1/reservations/${reservationId}/cancel`, data || {});
  },
};

