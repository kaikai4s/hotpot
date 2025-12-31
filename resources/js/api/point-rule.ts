import adminApiClient from './admin-client';
import type { ApiResponse } from '../types';

export interface PointRule {
  id: number;
  rule_key: string;
  rule_name: string;
  rule_type: 'earn' | 'use' | 'expire';
  config: Record<string, any>;
  is_active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
}

interface PointRuleListResponse {
  rules: PointRule[];
}

interface PointRuleCreateUpdatePayload {
  rule_key: string;
  rule_name: string;
  rule_type: 'earn' | 'use' | 'expire';
  config: Record<string, any>;
  is_active?: boolean;
  sort_order?: number;
}

export const adminPointRuleApi = {
  /**
   * 获取积分规则列表
   */
  getList: (params?: {
    rule_type?: 'earn' | 'use' | 'expire';
    is_active?: boolean;
  }): Promise<ApiResponse<PointRuleListResponse>> => {
    return adminApiClient.get('/admin/v1/point-rules', { params });
  },

  /**
   * 获取积分规则详情
   */
  getDetail: (id: number): Promise<ApiResponse<{ rule: PointRule }>> => {
    return adminApiClient.get(`/admin/v1/point-rules/${id}`);
  },

  /**
   * 创建积分规则
   */
  create: (payload: PointRuleCreateUpdatePayload): Promise<ApiResponse<{ rule: PointRule }>> => {
    return adminApiClient.post('/admin/v1/point-rules', payload);
  },

  /**
   * 更新积分规则
   */
  update: (id: number, payload: Partial<PointRuleCreateUpdatePayload>): Promise<ApiResponse<{ rule: PointRule }>> => {
    return adminApiClient.put(`/admin/v1/point-rules/${id}`, payload);
  },

  /**
   * 删除积分规则
   */
  delete: (id: number): Promise<ApiResponse<void>> => {
    return adminApiClient.delete(`/admin/v1/point-rules/${id}`);
  },
};

