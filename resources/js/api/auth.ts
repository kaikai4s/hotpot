/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';
import type { ApiResponse } from '../types';

export interface UserInfo {
  id: number;
  nickname: string;
  avatar_url?: string | null;
  phone?: string | null;
  equipped_title?: string | null;
  level?: {
    code: string;
    name: string;
    icon?: string | null;
    color?: string | null;
  } | null;
}

export interface UserLoginResponse {
  token: string;
  user: UserInfo;
}

export interface WechatConfig {
  app_id: string | null;
}

export const userAuthApi = {
  /**
   * 获取微信配置（AppID）
   */
  getWechatConfig: (): Promise<ApiResponse<WechatConfig>> => {
    return apiClient.get('/v1/wechat/config');
  },
  
  /**
   * 微信登录
   */
  wechatLogin: (code: string, inviteCode?: string): Promise<ApiResponse<UserLoginResponse>> => {
    const payload: { code: string; invite_code?: string } = { code };
    if (inviteCode) {
      payload.invite_code = inviteCode;
    }
    return apiClient.post('/v1/auth/wechat-login', payload);
  },
  
  /**
   * 获取当前用户信息
   */
  me: (): Promise<ApiResponse<UserInfo>> => {
    return apiClient.get('/v1/users/me');
  },
  
  /**
   * 退出登录
   */
  logout: (): Promise<ApiResponse<void>> => {
    return apiClient.post('/v1/auth/logout');
  },

  /**
   * 获取公开配置
   */
  getPublicConfig: (key: string): Promise<ApiResponse<{ key: string; value: any }>> => {
    return apiClient.get(`/v1/configs/${key}`);
  },
};

