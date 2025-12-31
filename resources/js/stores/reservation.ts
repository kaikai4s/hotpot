/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import { defineStore } from 'pinia';
import { ref } from 'vue';
import { reservationApi } from '../api/reservation';
import type { Reservation, Pagination } from '../types';

export const useReservationStore = defineStore('reservation', () => {
  const reservations = ref<Reservation[]>([]);
  const pagination = ref<Pagination | null>(null);
  const loading = ref(false);

  const fetchReservations = async (params?: { status?: string; date?: string; page?: number; page_size?: number }) => {
    // 检查是否有token，没有token则不调用API（使用 sessionStorage）
    const token = sessionStorage.getItem('admin_token');
    if (!token) {
      console.warn('没有token，跳过获取预约列表');
      loading.value = false;
      return;
    }
    
    loading.value = true;
    try {
      const { adminReservationApi } = await import('../api/admin/reservation');
      const response = await adminReservationApi.getList(params);
      if (response.code === 200 && response.data) {
        reservations.value = response.data.reservations;
        pagination.value = response.data.pagination;
      }
    } catch (error) {
      console.error('获取预约列表失败:', error);
      // 不抛出错误，让调用方处理
    } finally {
      loading.value = false;
    }
  };

  return {
    reservations,
    pagination,
    loading,
    fetchReservations,
  };
});

