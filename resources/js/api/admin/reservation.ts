/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from '../admin-client';
import type { ApiResponse, Reservation, Pagination } from '../../types';

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

export const adminReservationApi = {
  /**
   * 获取预约列表
   */
  getList: (params?: GetReservationsParams): Promise<ApiResponse<ReservationsResponse>> => {
    return adminApiClient.get('/admin/v1/reservations', { params });
  },

  /**
   * 获取预约详情
   */
  getDetail: (reservationId: number): Promise<ApiResponse<Reservation>> => {
    return adminApiClient.get(`/admin/v1/reservations/${reservationId}`);
  },

  /**
   * 确认预约
   */
  confirm: (reservationId: number): Promise<ApiResponse<Reservation>> => {
    return adminApiClient.post(`/admin/v1/reservations/${reservationId}/confirm`);
  },

  /**
   * 取消预约
   */
  cancel: (reservationId: number, reason?: string): Promise<ApiResponse<Reservation>> => {
    return adminApiClient.post(`/admin/v1/reservations/${reservationId}/cancel`, { reason });
  },
};

