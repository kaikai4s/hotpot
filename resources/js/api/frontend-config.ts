/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';
import type { ApiResponse } from '../types';

export const frontendConfigApi = {
  /**
   * 获取公开配置
   */
  getPublicConfig: (key: string): Promise<ApiResponse<{ key: string; value: any }>> => {
    return apiClient.get(`/v1/configs/${key}`);
  },
};

