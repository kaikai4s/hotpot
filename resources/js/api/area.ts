/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from './admin-client';
import type { ApiResponse } from '../types';

export interface RestaurantArea {
  id: number;
  name: string;
  type: string;
  boundaries: {
    x1?: number;
    y1?: number;
    x2?: number;
    y2?: number;
    x?: number;
    y?: number;
    width?: number;
    height?: number;
  };
  color?: string;
  sort_order: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface AreasResponse {
  areas: RestaurantArea[];
}

export interface UpdateAreaRequest {
  name?: string;
  type?: string;
  boundaries?: RestaurantArea['boundaries'];
  color?: string;
  sort_order?: number;
  is_active?: boolean;
}

export interface UpdateBatchRequest {
  areas: Array<{
    id: number;
  } & UpdateAreaRequest>;
}

export const areaApi = {
  getList: (): Promise<ApiResponse<AreasResponse>> => {
    return adminApiClient.get('/admin/v1/areas');
  },
  create: (data: UpdateAreaRequest): Promise<ApiResponse<{ area: RestaurantArea }>> => {
    return adminApiClient.post('/admin/v1/areas', data);
  },
  update: (id: number, data: UpdateAreaRequest): Promise<ApiResponse<{ area: RestaurantArea }>> => {
    return adminApiClient.put(`/admin/v1/areas/${id}`, data);
  },
  updateBatch: (data: UpdateBatchRequest): Promise<ApiResponse<void>> => {
    return adminApiClient.post('/admin/v1/areas/batch-update', data);
  },
  delete: (id: number): Promise<ApiResponse<void>> => {
    return adminApiClient.delete(`/admin/v1/areas/${id}`);
  },
};

