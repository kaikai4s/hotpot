/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-8">
          <h1 class="text-4xl font-bold text-gray-900 mb-2">📦 我的订单</h1>
          <p class="text-gray-600">查看和管理您的所有订单</p>
        </div>

        <!-- 状态筛选 -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
          <div class="flex gap-3 flex-wrap">
            <el-button
              :type="filters.status === '' ? 'primary' : ''"
              @click="handleStatusFilter('')"
            >
              全部
            </el-button>
            <el-button
              :type="filters.status === 'pending' ? 'primary' : ''"
              @click="handleStatusFilter('pending')"
            >
              待支付
            </el-button>
            <el-button
              :type="filters.status === 'paid' ? 'primary' : ''"
              @click="handleStatusFilter('paid')"
            >
              已支付
            </el-button>
            <el-button
              :type="filters.status === 'completed' ? 'primary' : ''"
              @click="handleStatusFilter('completed')"
            >
              已完成
            </el-button>
            <el-button
              :type="filters.status === 'cancelled' ? 'primary' : ''"
              @click="handleStatusFilter('cancelled')"
            >
              已取消
            </el-button>
          </div>
        </div>

        <!-- 加载中 -->
        <div v-if="loading" class="text-center py-20">
          <el-icon class="is-loading text-4xl text-red-600"><Loading /></el-icon>
          <p class="mt-4 text-gray-600">加载中...</p>
        </div>

        <!-- 订单列表 -->
        <div v-else-if="orders.length > 0" class="space-y-4">
          <div
            v-for="order in orders"
            :key="order.id"
            class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow"
          >
            <div class="flex justify-between items-start mb-4">
              <div>
                <div class="flex items-center gap-3 mb-2">
                  <span class="text-lg font-semibold text-gray-900">订单号：{{ order.order_no }}</span>
                  <el-tag :type="getStatusTag(order.status)">{{ getStatusText(order.status) }}</el-tag>
                </div>
                <p class="text-sm text-gray-500">创建时间：{{ formatDateTime(order.created_at) }}</p>
              </div>
              <div class="text-right">
                <div class="text-2xl font-bold text-red-600 mb-2">¥{{ order.total_amount }}</div>
                <div class="flex gap-2">
                  <el-button
                    v-if="order.status === 'pending'"
                    type="danger"
                    size="small"
                    @click="cancelOrder(order.id)"
                  >
                    取消订单
                  </el-button>
                  <el-button
                    type="primary"
                    size="small"
                    @click="viewOrderDetail(order.id)"
                  >
                    查看详情
                  </el-button>
                </div>
              </div>
            </div>

            <!-- 订单商品列表 -->
            <div class="border-t border-gray-200 pt-4">
              <div class="space-y-2">
                <div
                  v-for="item in order.items"
                  :key="item.id"
                  class="flex items-center gap-4"
                >
                  <div class="w-16 h-16 bg-gradient-to-br from-red-200 via-orange-200 to-yellow-200 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-2xl">🍲</span>
                  </div>
                  <div class="flex-1">
                    <h4 class="font-semibold text-gray-900">{{ item.dish?.name || '未知菜品' }}</h4>
                    <p class="text-sm text-gray-500">¥{{ item.price }} × {{ item.quantity }}</p>
                  </div>
                  <div class="text-right">
                    <p class="font-semibold text-gray-900">¥{{ item.subtotal }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 空状态 -->
        <div v-else class="text-center py-20">
          <div class="text-6xl mb-4">📭</div>
          <h3 class="text-2xl font-bold text-gray-900 mb-2">暂无订单</h3>
          <p class="text-gray-600 mb-6">快去选购您喜欢的菜品吧！</p>
          <el-button type="primary" size="large" @click="goToDishes">
            去点餐
          </el-button>
        </div>

        <!-- 分页 -->
        <el-pagination
          v-if="pagination && pagination.total_count > 0"
          v-model:current-page="currentPage"
          :page-size="pagination.page_size || 20"
          :total="pagination.total_count"
          layout="total, prev, pager, next"
          @current-change="handlePageChange"
          class="mt-6 flex justify-center"
        />
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Loading } from '@element-plus/icons-vue';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { orderApi, type Order } from '../../api/order';

const router = useRouter();

const loading = ref(false);
const orders = ref<Order[]>([]);
const pagination = ref<{
  current_page: number;
  total_pages: number;
  total_count: number;
  page_size: number;
} | null>(null);

const filters = ref({
  status: '',
});

const currentPage = ref(1);

const getStatusTag = (status: string) => {
  const tags: Record<string, string> = {
    pending: 'warning',
    paid: 'success',
    completed: '',
    cancelled: 'info',
  };
  return tags[status] || '';
};

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: '待支付',
    paid: '已支付',
    completed: '已完成',
    cancelled: '已取消',
  };
  return texts[status] || status;
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const handleStatusFilter = (status: string) => {
  filters.value.status = status;
  currentPage.value = 1;
  fetchOrders();
};

const handlePageChange = (page: number) => {
  currentPage.value = page;
  fetchOrders();
};

const fetchOrders = async () => {
  loading.value = true;
  try {
    const response = await orderApi.getList({
      status: filters.value.status || undefined,
    });

    if (response.code === 200 && response.data) {
      orders.value = response.data.data || [];
      pagination.value = {
        current_page: response.data.current_page || 1,
        total_pages: response.data.total_pages || 1,
        total_count: response.data.total_count || 0,
        page_size: response.data.page_size || 20,
      };
    } else {
      ElMessage.error(response.message || '获取订单列表失败');
    }
  } catch (error: any) {
    console.error('获取订单列表失败:', error);
    ElMessage.error(error.message || '获取订单列表失败');
  } finally {
    loading.value = false;
  }
};

const cancelOrder = async (orderId: number) => {
  try {
    await ElMessageBox.confirm('确认取消此订单吗？', '提示', {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'warning',
    });

    const response = await orderApi.cancel(orderId);
    if (response.code === 200) {
      ElMessage.success('订单已取消');
      fetchOrders();
    } else {
      ElMessage.error(response.message || '取消订单失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('取消订单失败:', error);
      ElMessage.error(error.message || '取消订单失败');
    }
  }
};

const viewOrderDetail = (orderId: number) => {
  if (!orderId) {
    ElMessage.error('订单ID无效');
    return;
  }
  console.log('跳转到订单详情:', orderId);
  router.push(`/frontend/orders/${orderId}`).catch((err) => {
    console.error('路由跳转失败:', err);
    ElMessage.error('跳转失败，请重试');
  });
};

const goToDishes = () => {
  router.push('/frontend/dishes');
};

onMounted(() => {
  fetchOrders();
});
</script>

<style scoped>
</style>

