/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from '../admin-client';
import type { ApiResponse, Pagination } from '../../types';

export interface Queue {
  id: number;
  queue_number: string;
  user_id: number;
  guest_count: number;
  table_type?: string | null;
  position: number;
  status: 'waiting' | 'called' | 'cancelled' | 'seated';
  joined_at: string;
  called_at?: string | null;
  seated_at?: string | null;
  created_at: string;
  updated_at: string;
  is_timeout?: boolean; // 是否超时
  timeout_at?: string | null; // 超时时间
  remaining_minutes?: number | null; // 剩余分钟数（负数表示已超时）
  user?: {
    id: number;
    nickname: string;
    avatar_url?: string | null;
    phone?: string | null;
  };
}

export interface GetQueuesParams {
  status?: string;
  queue_number?: string;
  user_nickname?: string;
  date_from?: string;
  date_to?: string;
  page?: number;
  page_size?: number;
}

export interface QueuesResponse {
  queues: Queue[];
  pagination: Pagination;
  statistics: {
    waiting_count: number;
    called_count: number;
    today_count: number;
  };
}

export const adminQueueApi = {
  /**
   * 获取排队列表
   */
  getList: (params?: GetQueuesParams): Promise<ApiResponse<QueuesResponse>> => {
    return adminApiClient.get('/admin/v1/queues', { params });
  },

  /**
   * 获取排队详情
   */
  getDetail: (queueId: number): Promise<ApiResponse<Queue>> => {
    return adminApiClient.get(`/admin/v1/queues/${queueId}`);
  },

  /**
   * 叫号（下一个）
   */
  callNext: (): Promise<ApiResponse<{ queue: Queue }>> => {
    return adminApiClient.post('/admin/v1/queues/call-next');
  },

  /**
   * 标记为已入座
   */
  markSeated: (queueId: number, tableId?: number): Promise<ApiResponse<{ queue: Queue }>> => {
    return adminApiClient.post(`/admin/v1/queues/${queueId}/seated`, { table_id: tableId });
  },

  /**
   * 取消排队
   */
  cancel: (queueId: number): Promise<ApiResponse<{ queue: Queue }>> => {
    return adminApiClient.post(`/admin/v1/queues/${queueId}/cancel`);
  },

  /**
   * 调整排队位置
   */
  adjustPosition: (queueId: number, position: number): Promise<ApiResponse<{ queue: Queue }>> => {
    return adminApiClient.post(`/admin/v1/queues/${queueId}/adjust-position`, { position });
  },

  /**
   * 批量删除
   */
  batchDelete: (ids: number[]): Promise<ApiResponse<{ deleted_count: number }>> => {
    return adminApiClient.delete('/admin/v1/queues/batch', { data: { ids } });
  },
};

