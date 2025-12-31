/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';

export interface Review {
  id: number;
  user_id: number;
  order_id: number;
  dish_id: number;
  rating: number;
  content?: string;
  images?: string[];
  tags?: string[];
  status: 'pending' | 'approved' | 'rejected';
  helpful_count: number;
  reviewed_at?: string;
  admin_reply?: string;
  admin_replied_at?: string;
  admin_replied_by?: number;
  is_adopted: boolean;
  adopted_at?: string;
  adopted_by?: number;
  tracking_status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  tracking_updates?: Array<{
    action: string;
    admin_id?: number;
    admin_name?: string;
    old_status?: string;
    new_status?: string;
    message: string;
    created_at: string;
  }>;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    nickname: string;
  };
  dish?: {
    id: number;
    name: string;
  };
  order?: {
    id: number;
    order_no: string;
  };
  admin_replier?: {
    id: number;
    name: string;
    username: string;
  };
  adopter?: {
    id: number;
    name: string;
    username: string;
  };
}

export interface CreateReviewPayload {
  order_id: number;
  dish_id: number;
  rating: number;
  content?: string;
  images?: string[];
  tags?: string[];
}

export interface ReviewFilters {
  status?: 'pending' | 'approved' | 'rejected';
  tracking_status?: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  is_adopted?: boolean;
  my_reviews?: boolean; // 是否只获取当前用户的评价
  page?: number;
  page_size?: number;
}

export const reviewApi = {
  /**
   * 创建评价
   */
  create: (payload: CreateReviewPayload) => {
    return apiClient.post<{
      code: number;
      message: string;
      data: {
        review_id: number;
        status: string;
        submitted_at: string;
      };
    }>('/v1/reviews', payload);
  },

  /**
   * 获取所有评价
   */
  getList: (filters?: ReviewFilters) => {
    return apiClient.get<{
      code: number;
      message: string;
      data: {
        reviews: Review[];
        pagination: {
          current_page: number;
          total_pages: number;
          total_count: number;
          page_size: number;
        };
      };
    }>('/v1/reviews', { params: filters });
  },

  /**
   * 获取追踪优化的评价
   */
  getTrackingReviews: (filters?: ReviewFilters) => {
    return apiClient.get<{
      code: number;
      message: string;
      data: {
        reviews: Review[];
        pagination: {
          current_page: number;
          total_pages: number;
          total_count: number;
          page_size: number;
        };
      };
    }>('/v1/reviews/tracking', { params: filters });
  },

  /**
   * 获取菜品评价
   */
  getDishReviews: (dishId: number, params?: { page?: number; page_size?: number; sort?: string }) => {
    return apiClient.get<{
      code: number;
      message: string;
      data: {
        reviews: Review[];
        pagination: {
          current_page: number;
          total_pages: number;
          total_count: number;
          page_size: number;
        };
        summary: {
          average_rating: number;
          total_reviews: number;
          rating_distribution: Record<string, number>;
        };
      };
    }>(`/v1/dishes/${dishId}/reviews`, { params });
  },
};
