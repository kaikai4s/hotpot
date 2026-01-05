/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';

export interface Table {
  id: number;
  name: string;
  capacity: number;
  type: 'window' | 'corner' | 'center';
  status: 'available' | 'reserved' | 'occupied' | 'maintenance';
  position_x?: number | null;
  position_y?: number | null;
  occupied_by_user_id?: number | null;
  occupied_by_user?: {
    id: number;
    nickname: string;
  } | null;
  team_code?: string | null;
}

export interface TablesResponse {
  tables: Table[];
}

export interface JoinTeamRequest {
  team_code: string;
}

export interface JoinTeamResponse {
  table: Table;
}

export const frontendTableApi = {
  /**
   * 获取可用桌位列表（用于点餐时选择桌位）
   */
  getAvailableTables: (): Promise<{ code: number; message: string; data: TablesResponse }> => {
    return apiClient.get('/v1/tables/available');
  },
  
  /**
   * 加入团队点餐
   */
  joinTeam: (data: JoinTeamRequest): Promise<{ code: number; message: string; data: JoinTeamResponse }> => {
    return apiClient.post('/v1/tables/join-team', data);
  },
};

