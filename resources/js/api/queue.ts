/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';
import type { ApiResponse } from '../types';

export interface QueueInfo {
  queue_id: number;
  queue_number: string;
  current_position: number;
  ahead_count: number;
  estimated_wait_time: number;
  status: 'waiting' | 'called' | 'cancelled' | 'seated';
  guest_count?: number;
  table_type?: string | null;
  joined_at?: string;
  called_at?: string | null;
  seated_at?: string | null;
}

export interface JoinQueuePayload {
  guest_count: number;
  table_type?: string | null;
}

export const queueApi = {
  /**
   * 加入排队
   */
  join: (payload: JoinQueuePayload): Promise<ApiResponse<QueueInfo>> => {
    return apiClient.post('/v1/queue/join', payload);
  },

  /**
   * 获取排队状态
   */
  getStatus: (queueId: number): Promise<ApiResponse<QueueInfo>> => {
    return apiClient.get(`/v1/queue/${queueId}`);
  },

  /**
   * 获取当前用户的排队状态
   */
  getMyQueue: (): Promise<ApiResponse<QueueInfo | null>> => {
    return apiClient.get('/v1/queue/my');
  },

  /**
   * 取消排队
   */
  cancel: (queueId: number): Promise<ApiResponse<void>> => {
    return apiClient.post(`/v1/queue/${queueId}/cancel`);
  },
};

