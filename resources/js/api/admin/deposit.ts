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
}

export const adminDepositApi = {
  /**
   * 获取定金列表
   */
  getDeposits: (params?: GetDepositsParams): Promise<ApiResponse<DepositsResponse>> => {
    return adminApiClient.get('/admin/v1/deposits', { params });
  },

  /**
   * 手动返还定金
   */
  refundDeposit: (reservationId: number, reason?: string): Promise<ApiResponse<Reservation>> => {
    return adminApiClient.post(`/admin/v1/deposits/${reservationId}/refund`, { reason });
  },
};


