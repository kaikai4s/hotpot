/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios';
import { ElMessage } from 'element-plus';
import router from '../router';

const adminApiClient: AxiosInstance = axios.create({
  baseURL: '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// 请求拦截器：添加Token
adminApiClient.interceptors.request.use(
  (config) => {
    // 登录接口不需要携带 token
    if (config.url && !config.url.includes('/auth/login')) {
      // 后台使用 sessionStorage，确保前后台完全隔离
      // sessionStorage 在标签页关闭时自动清除，更安全
      const token = sessionStorage.getItem('admin_token');
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// 响应拦截器：处理错误和Token过期
adminApiClient.interceptors.response.use(
  (response) => {
    return response.data;
  },
  (error) => {
    if (error.response) {
      const { status, data } = error.response;

      if (status === 401) {
        // 登录接口的 401 错误不应该显示"登录已过期"
        const isLoginRequest = error.config?.url?.includes('/auth/login');
        
        if (isLoginRequest) {
          // 登录接口的 401 错误，可能是用户名或密码错误，不处理，让登录页面自己处理
          // 清除可能存在的无效 token（使用 sessionStorage）
          sessionStorage.removeItem('admin_token');
          sessionStorage.removeItem('admin_info');
        } else {
          // 其他接口的 401 错误，表示 token 过期
          const currentPath = router.currentRoute.value.path;
          
          // 检查是否是刚登录后的请求（通过检查 sessionStorage）
          const isJustLoggedIn = sessionStorage.getItem('just_logged_in') === 'true';
          
          if (isJustLoggedIn) {
            // 清除标记，避免下次误判
            sessionStorage.removeItem('just_logged_in');
            // 刚登录后的401可能是token验证延迟，不立即跳转
            console.warn('登录后首次请求返回401，可能是token验证延迟');
            return Promise.reject(error);
          }
          
          // 清除token和信息（使用 sessionStorage）
          sessionStorage.removeItem('admin_token');
          sessionStorage.removeItem('admin_info');
          
          // 避免在登录页面时重复跳转，也避免在刚登录时立即跳转
          // 只有在后台路由且非登录页面时才跳转
          if (currentPath.startsWith('/admin/') && currentPath !== '/admin/login') {
            // 延迟跳转，避免在页面加载时立即跳转
            setTimeout(() => {
              // 再次检查当前路径和token，避免重复跳转
              const currentToken = sessionStorage.getItem('admin_token');
              if (!currentToken && router.currentRoute.value.path !== '/admin/login') {
                ElMessage.error('登录已过期，请重新登录');
                router.push('/admin/login');
              }
            }, 500);
          }
        }
          } else if (status === 403) {
            // 没有权限 - 不跳转到登录页，只显示错误消息
            ElMessage.error(data?.message || '没有权限执行此操作');
            // 403 错误不应该清除 token，因为用户已经登录，只是没有权限
            return Promise.reject(error);
          } else if (status === 422) {
        // 验证错误
        let message = '请求参数错误';
        if (data?.errors) {
          // Laravel 验证错误格式
          const firstError = Object.values(data.errors)[0];
          message = Array.isArray(firstError) ? firstError[0] : firstError;
        } else if (data?.message) {
          message = data.message;
        }
        ElMessage.error(message);
      } else if (status >= 500) {
        // 服务器错误
        ElMessage.error('服务器错误，请稍后重试');
      }
    } else {
      ElMessage.error('网络错误，请检查网络连接');
    }

    return Promise.reject(error);
  }
);

export default adminApiClient;

