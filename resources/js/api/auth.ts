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
  has_password?: boolean; // 是否已设置密码
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

export interface SendPhoneCodeParams {
  phone: string;
  type?: 'login' | 'register' | 'reset_password';
}

export interface PhoneCodeLoginParams {
  phone: string;
  code: string;
}

export interface PhonePasswordLoginParams {
  phone: string;
  password: string;
}

export interface AccountPasswordLoginParams {
  account: string; // 账户名（昵称或手机号）
  password: string;
}

export interface SendPhoneCodeResponse {
  code?: string; // 开发环境返回验证码
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
   * 发送手机验证码
   */
  sendPhoneCode: (params: SendPhoneCodeParams): Promise<ApiResponse<SendPhoneCodeResponse>> => {
    return apiClient.post('/v1/auth/send-phone-code', params);
  },
  
  /**
   * 手机号+验证码登录
   */
  phoneCodeLogin: (params: PhoneCodeLoginParams): Promise<ApiResponse<UserLoginResponse>> => {
    return apiClient.post('/v1/auth/phone-code-login', params);
  },
  
  /**
   * 手机号+密码登录
   */
  phonePasswordLogin: (params: PhonePasswordLoginParams): Promise<ApiResponse<UserLoginResponse>> => {
    return apiClient.post('/v1/auth/phone-password-login', params);
  },
  
  /**
   * 账户名+密码登录（账户名可以是昵称或手机号）
   */
  accountPasswordLogin: (params: AccountPasswordLoginParams): Promise<ApiResponse<UserLoginResponse>> => {
    return apiClient.post('/v1/auth/account-password-login', params);
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
