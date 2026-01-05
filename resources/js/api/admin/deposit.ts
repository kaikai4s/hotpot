/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from '../admin-client';
import type { ApiResponse, Reservation, Pagination } from '../../types';

export interface GetDepositsParams {
  deposit_status?: 'unpaid' | 'paid' | 'refunded' | 'forfeited';
  reservation_code?: string;
  date_from?: string;
  date_to?: string;
  page?: number;
  page_size?: number;
}

export interface DepositsResponse {
  reservations: Reservation[];
  pagination: Pagination;
  statistics: {
    total_amount: number;
    refunded_amount: number;
    forfeited_amount: number;
  };
  unviewed_count?: number;
}

export const adminDepositApi = {
  /**
   * 获取定金列表
   */
  getDeposits: (params?: GetDepositsParams): Promise<ApiResponse<DepositsResponse>> => {
    return adminApiClient.get('/admin/v1/deposits', { params });
  },

  /**
   * 获取定金详情
   */
  getDeposit: (reservationId: number): Promise<ApiResponse<Reservation>> => {
    return adminApiClient.get(`/admin/v1/deposits/${reservationId}`);
  },

  /**
   * 手动返还定金
   */
  refundDeposit: (reservationId: number, reason?: string): Promise<ApiResponse<Reservation>> => {
    return adminApiClient.post(`/admin/v1/deposits/${reservationId}/refund`, { reason });
  },

  /**
   * 批量标记为已查看
   */
  markAsViewed: (ids: number[]): Promise<ApiResponse<{ count: number }>> => {
    return adminApiClient.post('/admin/v1/deposits/mark-viewed', { ids });
  },

  /**
   * 批量删除定金记录
   */
  batchDelete: (ids: number[]): Promise<ApiResponse<{ count: number }>> => {
    return adminApiClient.delete('/admin/v1/deposits/batch', { data: { ids } });
  },
};


