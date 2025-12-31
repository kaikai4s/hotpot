<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800 mb-2">仪表盘</h1>
      <p class="text-gray-600">实时监控餐厅运营数据</p>
    </div>

    <!-- 加载状态 -->
    <div v-if="loading" class="text-center py-20">
      <el-icon class="is-loading text-4xl text-red-600"><Loading /></el-icon>
      <p class="mt-4 text-gray-600">加载中...</p>
    </div>

    <!-- 统计卡片 -->
    <div v-if="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium">今日预约</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ stats.todayReservations }}</p>
            <p v-if="stats.reservationsGrowth > 0" class="text-xs text-green-600 mt-1">↑ {{ stats.reservationsGrowth }}% 较昨日</p>
            <p v-else-if="stats.reservationsGrowth < 0" class="text-xs text-red-600 mt-1">↓ {{ Math.abs(stats.reservationsGrowth) }}% 较昨日</p>
            <p v-else class="text-xs text-gray-500 mt-1">与昨日持平</p>
          </div>
          <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
            <el-icon class="text-blue-600 text-2xl"><Calendar /></el-icon>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium">待审核评价</p>
            <p class="text-3xl font-bold text-orange-600 mt-2">{{ stats.pendingReviews }}</p>
            <p class="text-xs text-gray-500 mt-1">需要处理</p>
          </div>
          <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center">
            <el-icon class="text-orange-600 text-2xl"><Star /></el-icon>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium">当前排队</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">{{ stats.activeQueue }}</p>
            <p class="text-xs text-gray-500 mt-1">等待中</p>
          </div>
          <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center">
            <el-icon class="text-purple-600 text-2xl"><Clock /></el-icon>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium">今日营业额</p>
            <p class="text-3xl font-bold text-green-600 mt-2">¥{{ formatRevenue(stats.todayRevenue) }}</p>
            <p v-if="stats.revenueGrowth > 0" class="text-xs text-green-600 mt-1">↑ {{ stats.revenueGrowth }}% 较昨日</p>
            <p v-else-if="stats.revenueGrowth < 0" class="text-xs text-red-600 mt-1">↓ {{ Math.abs(stats.revenueGrowth) }}% 较昨日</p>
            <p v-else class="text-xs text-gray-500 mt-1">与昨日持平</p>
          </div>
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
            <el-icon class="text-green-600 text-2xl"><Money /></el-icon>
          </div>
        </div>
      </div>
    </div>

    <!-- 图表和列表 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- 最近预约 -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">最近预约</h2>
        <div class="space-y-4">
          <div v-if="recentReservations.length === 0" class="text-center py-8 text-gray-500">
            暂无最近预约
          </div>
          <div v-for="reservation in recentReservations" :key="reservation.id" 
               class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors">
            <div>
              <p class="font-semibold text-gray-800">{{ reservation.reservation_code }}</p>
              <p class="text-sm text-gray-600">{{ reservation.table?.name }} · {{ reservation.guest_count }}人</p>
            </div>
            <el-tag :type="getStatusType(reservation.status)">{{ getStatusText(reservation.status) }}</el-tag>
          </div>
        </div>
      </div>

      <!-- 待处理事项 -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">待处理事项</h2>
        <div class="space-y-4">
          <div v-if="pendingTasks.length === 0" class="text-center py-8 text-gray-500">
            暂无待处理事项
          </div>
          <div v-for="task in pendingTasks" :key="task.id"
               class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-orange-50 transition-colors">
            <div class="flex items-center">
              <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3"
                   :class="task.type === 'review' ? 'bg-orange-100' : 'bg-blue-100'">
                <el-icon :class="task.type === 'review' ? 'text-orange-600' : 'text-blue-600'">
                  <component :is="task.type === 'review' ? 'Star' : 'Calendar'" />
                </el-icon>
              </div>
              <div>
                <p class="font-semibold text-gray-800">{{ task.title }}</p>
                <p class="text-sm text-gray-600">{{ task.description }}</p>
              </div>
            </div>
            <el-button size="small" type="primary" @click="handleTask(task)">处理</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Calendar, Star, Clock, Money, Loading } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import { useReservationStore } from '../stores/reservation';
import { adminDashboardApi } from '../api/admin/dashboard';
import type { Reservation } from '../types';

const route = useRoute();
const router = useRouter();
const store = useReservationStore();

const loading = ref(false);

const stats = ref({
  todayReservations: 0,
  reservationsGrowth: 0,
  pendingReviews: 0,
  activeQueue: 0,
  todayRevenue: '0',
  revenueGrowth: 0,
});

const recentReservations = ref<Reservation[]>([]);
const pendingTasks = ref<any[]>([]);

const getStatusType = (status: string) => {
  const types: Record<string, string> = {
    pending: 'warning',
    confirmed: 'success',
    cancelled: 'info',
    completed: '',
    expired: 'danger',
  };
  return types[status] || '';
};

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: '待确认',
    confirmed: '已确认',
    cancelled: '已取消',
    completed: '已完成',
    expired: '已过期',
  };
  return texts[status] || status;
};

const formatRevenue = (revenue: string | number) => {
  const num = typeof revenue === 'string' ? parseFloat(revenue) : revenue;
  return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const handleTask = (task: any) => {
  if (task.type === 'review') {
    router.push('/admin/reviews?status=pending');
  } else if (task.type === 'reservation') {
    router.push('/admin/reservations?status=pending');
  }
};

const fetchStats = async () => {
  loading.value = true;
  try {
    const response = await adminDashboardApi.getStatistics();
    if (response.code === 200 && response.data) {
      const data = response.data;
      stats.value = {
        todayReservations: data.stats.today_reservations,
        reservationsGrowth: data.stats.reservations_growth,
        pendingReviews: data.stats.pending_reviews,
        activeQueue: data.stats.active_queue,
        todayRevenue: data.stats.today_revenue,
        revenueGrowth: data.stats.revenue_growth,
      };
      pendingTasks.value = data.pending_tasks || [];
    } else {
      ElMessage.error(response.message || '获取统计数据失败');
    }
  } catch (error: any) {
    console.error('获取统计数据失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取统计数据失败');
  } finally {
    loading.value = false;
  }
};

const fetchRecentReservations = async () => {
  // 检查是否有token，没有token则不调用API（使用 sessionStorage）
  const token = sessionStorage.getItem('admin_token');
  if (!token) {
    console.warn('没有token，跳过获取预约列表');
    return;
  }
  
  try {
    await store.fetchReservations({ page: 1, page_size: 5 });
    recentReservations.value = store.reservations.slice(0, 5);
  } catch (error) {
    // 静默失败，不显示错误（响应拦截器会处理）
    console.error('获取预约列表失败:', error);
  }
};

onMounted(() => {
  // 使用 useRoute 检查当前路由
  const route = useRoute();
  if (route.path !== '/dashboard' && route.name !== 'Dashboard') {
    return;
  }
  
  fetchStats();
  fetchRecentReservations();
});
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.bg-white {
  animation: fadeIn 0.5s ease-out;
}
</style>

