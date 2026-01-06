/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import apiClient from './client';

export interface UpdateProfileParams {
  nickname?: string;
  avatar_url?: string;
  phone?: string;
  phone_verification_code?: string;
  gender?: 0 | 1 | 2;
  password?: string;
  password_confirmation?: string;
  current_password?: string;
}

export interface SetPasswordParams {
  password: string;
  password_confirmation: string;
  current_password?: string;
}

export interface UserProfile {
  id: number;
  nickname: string;
  avatar_url: string | null;
  phone: string | null;
  gender: number | null;
  equipped_title: string | null;
  has_password?: boolean; // 是否已设置密码
  level: {
    code: string;
    name: string;
    icon: string;
    color: string;
  } | null;
}

/**
 * 更新用户资料
 */
export async function updateProfile(params: UpdateProfileParams): Promise<UserProfile> {
  const response = await apiClient.put('/v1/users/profile', params);
  return response.data.user;
}

/**
 * 设置密码
 */
export async function setPassword(params: SetPasswordParams): Promise<void> {
  await apiClient.post('/v1/users/set-password', params);
}
