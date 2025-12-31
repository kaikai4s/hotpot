/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import adminApiClient from './admin-client';
import type { ApiResponse, Table } from '../types';

export interface TablesResponse {
  tables: Table[];
}

export interface UpdateTablePosition {
  id: number;
  position_x?: number | null;
  position_y?: number | null;
  type?: string;
}

export interface UpdatePositionsRequest {
  tables: UpdateTablePosition[];
}

export interface CreateTableRequest {
  name: string;
  capacity: number;
  type: 'window' | 'corner' | 'center';
  status?: 'available' | 'reserved' | 'occupied' | 'maintenance';
  position_x?: number | null;
  position_y?: number | null;
}

export const tableApi = {
  getList: (): Promise<ApiResponse<TablesResponse>> => {
    return adminApiClient.get('/admin/v1/tables');
  },
  getById: (id: number): Promise<ApiResponse<{ table: Table }>> => {
    return adminApiClient.get(`/admin/v1/tables/${id}`);
  },
  create: (data: CreateTableRequest): Promise<ApiResponse<{ table: Table }>> => {
    return adminApiClient.post('/admin/v1/tables', data);
  },
  update: (id: number, data: Partial<Table>): Promise<ApiResponse<{ table: Table }>> => {
    return adminApiClient.put(`/admin/v1/tables/${id}`, data);
  },
  delete: (id: number): Promise<ApiResponse<void>> => {
    return adminApiClient.delete(`/admin/v1/tables/${id}`);
  },
  updatePositions: (tables: UpdateTablePosition[]): Promise<ApiResponse<void>> => {
    return adminApiClient.post('/admin/v1/tables/positions', { tables });
  },
};

