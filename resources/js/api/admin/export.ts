/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import axios from 'axios';

export interface ExportParams {
  status?: string;
  date_from?: string;
  date_to?: string;
  category_id?: number;
  is_available?: string;
  is_active?: string;
}

/**
 * 下载导出文件 - 使用独立的axios实例避免响应拦截器处理blob
 */
const downloadExport = async (url: string, params: ExportParams, filename: string) => {
  const queryParams = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== '') {
      queryParams.append(key, String(value));
    }
  });
  
  const fullUrl = `/api/admin/v1${url}${queryParams.toString() ? '?' + queryParams.toString() : ''}`;
  
  // 获取token
  const token = sessionStorage.getItem('admin_token');
  
  const response = await axios.get(fullUrl, {
    responseType: 'blob',
    headers: {
      'Authorization': token ? `Bearer ${token}` : '',
    },
  });
  
  // 创建下载链接
  const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8' });
  const downloadUrl = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = downloadUrl;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(downloadUrl);
};

export const adminExportApi = {
  /**
   * 导出订单数据
   */
  exportOrders: (params: ExportParams = {}) => {
    return downloadExport('/export/orders', params, `orders_${new Date().toISOString().slice(0, 10)}.csv`);
  },

  /**
   * 导出预约数据
   */
  exportReservations: (params: ExportParams = {}) => {
    return downloadExport('/export/reservations', params, `reservations_${new Date().toISOString().slice(0, 10)}.csv`);
  },

  /**
   * 导出用户数据
   */
  exportUsers: (params: ExportParams = {}) => {
    return downloadExport('/export/users', params, `users_${new Date().toISOString().slice(0, 10)}.csv`);
  },

  /**
   * 导出评价数据
   */
  exportReviews: (params: ExportParams = {}) => {
    return downloadExport('/export/reviews', params, `reviews_${new Date().toISOString().slice(0, 10)}.csv`);
  },

  /**
   * 导出菜品数据
   */
  exportDishes: (params: ExportParams = {}) => {
    return downloadExport('/export/dishes', params, `dishes_${new Date().toISOString().slice(0, 10)}.csv`);
  },

  /**
   * 导出排队数据
   */
  exportQueues: (params: ExportParams = {}) => {
    return downloadExport('/export/queues', params, `queues_${new Date().toISOString().slice(0, 10)}.csv`);
  },
};
