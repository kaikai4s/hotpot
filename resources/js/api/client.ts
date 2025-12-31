/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import axios, { AxiosInstance, AxiosRequestConfig } from 'axios';
import type { ApiResponse } from '../types';

const apiClient: AxiosInstance = axios.create({
  baseURL: '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// 请求拦截器
apiClient.interceptors.request.use(
  (config) => {
    // 前台API只使用前台token，绝不使用admin_token
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    // 确保不会误用admin_token
    if (config.headers.Authorization && config.headers.Authorization.includes('admin')) {
      delete config.headers.Authorization;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// 响应拦截器
apiClient.interceptors.response.use(
  (response) => {
    return response.data;
  },
  (error) => {
    if (error.response?.status === 401) {
      // 前台用户未登录，清除前台token（不影响后台登录状态）
      localStorage.removeItem('token');
      localStorage.removeItem('user_info');
      
      // 如果是前台路由且不是登录页，跳转到前台登录页
      const currentPath = window.location.pathname;
      if (currentPath.startsWith('/frontend/') && currentPath !== '/frontend/login' && !currentPath.startsWith('/admin/')) {
        // 保存当前路径用于登录后跳转
        window.location.href = `/frontend/login?redirect=${encodeURIComponent(currentPath)}`;
        return Promise.reject(error);
      }
    }
    // 将错误响应数据也传递出去，方便组件处理
    if (error.response?.data) {
      error.message = error.response.data.message || error.message;
    }
    return Promise.reject(error);
  }
);

export default apiClient;

