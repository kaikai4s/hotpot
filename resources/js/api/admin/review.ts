/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from '../admin-client';
import type { Review, ReviewFilters } from '../review';

export interface AdminReviewListResponse {
  reviews: Review[];
  pagination: {
    current_page: number;
    total_pages: number;
    total_count: number;
    page_size: number;
  };
}

export const adminReviewApi = {
  /**
   * 获取评价列表
   */
  getReviews: (filters?: ReviewFilters & { rating?: number }) => {
    return adminApiClient.get<AdminReviewListResponse>('/admin/v1/reviews', { params: filters });
  },

  /**
   * 获取评价详情
   */
  getReview: (id: number) => {
    return adminApiClient.get<Review>(`/admin/v1/reviews/${id}`);
  },

  /**
   * 审核评价
   */
  approveReview: (id: number, action: 'approve' | 'reject', reason?: string) => {
    return adminApiClient.put<Review>(`/admin/v1/reviews/${id}/approve`, { action, reason });
  },

  /**
   * 回复评价
   */
  replyReview: (id: number, reply: string) => {
    return adminApiClient.post<Review>(`/admin/v1/reviews/${id}/reply`, { reply });
  },

  /**
   * 采纳评价建议
   */
  adoptReview: (id: number) => {
    return adminApiClient.post<Review>(`/admin/v1/reviews/${id}/adopt`);
  },

  /**
   * 更新追踪状态
   */
  updateTrackingStatus: (id: number, status: 'pending' | 'in_progress' | 'completed' | 'cancelled', message?: string) => {
    return adminApiClient.put<Review>(`/admin/v1/reviews/${id}/tracking`, { status, message });
  },

  /**
   * 添加追踪更新
   */
  addTrackingUpdate: (id: number, message: string) => {
    return adminApiClient.post<Review>(`/admin/v1/reviews/${id}/tracking-update`, { message });
  },
};

