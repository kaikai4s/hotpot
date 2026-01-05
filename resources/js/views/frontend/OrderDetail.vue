/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 返回按钮 -->
        <el-button
          type="text"
          @click="goBack"
          class="mb-4"
        >
          <el-icon><ArrowLeft /></el-icon>
          返回订单列表
        </el-button>

        <!-- 加载中 -->
        <div v-if="loading" class="text-center py-20">
          <el-icon class="is-loading text-4xl text-red-600"><Loading /></el-icon>
          <p class="mt-4 text-gray-600">加载中...</p>
        </div>

        <!-- 订单详情 -->
        <div v-else-if="order" class="space-y-6">
          <!-- 订单信息卡片 -->
          <div class="bg-white rounded-2xl shadow-xl p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">订单信息</h2>
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-gray-600">订单号：</span>
                <span class="font-semibold text-gray-900">{{ order.order_no }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">订单状态：</span>
                <el-tag :type="getStatusTag(order.status)">{{ getStatusText(order.status) }}</el-tag>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">订单金额：</span>
                <span class="text-2xl font-bold text-red-600">¥{{ order.total_amount }}</span>
              </div>
              <div v-if="order.table" class="flex justify-between">
                <span class="text-gray-600">桌位：</span>
                <el-tag type="info">{{ order.table.name }}</el-tag>
              </div>
              <div v-if="order.table?.team_code" class="flex justify-between">
                <span class="text-gray-600">团队码：</span>
                <el-tag type="success" size="default">
                  <el-icon class="mr-1"><UserFilled /></el-icon>
                  {{ order.table.team_code }}
                </el-tag>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">创建时间：</span>
                <span class="text-gray-900">{{ formatDateTime(order.created_at) }}</span>
              </div>
              <div v-if="order.paid_at" class="flex justify-between">
                <span class="text-gray-600">支付时间：</span>
                <span class="text-gray-900">{{ formatDateTime(order.paid_at) }}</span>
              </div>
              <div v-if="order.completed_at" class="flex justify-between">
                <span class="text-gray-600">完成时间：</span>
                <span class="text-gray-900">{{ formatDateTime(order.completed_at) }}</span>
              </div>
              <div v-if="order.payment_method" class="flex justify-between">
                <span class="text-gray-600">支付方式：</span>
                <el-tag v-if="order.payment_method === 'wechat'" type="success">微信支付</el-tag>
                <el-tag v-else-if="order.payment_method === 'mock'" type="info">模拟支付</el-tag>
              </div>
            </div>
          </div>

          <!-- 商品清单 -->
          <div class="bg-white rounded-2xl shadow-xl p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">商品清单</h2>
            <div class="space-y-3">
              <div
                v-for="item in order.items"
                :key="item.id"
                class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg"
              >
                <div class="w-16 h-16 bg-gradient-to-br from-red-200 via-orange-200 to-yellow-200 rounded-lg flex items-center justify-center flex-shrink-0">
                  <span class="text-3xl">🍲</span>
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
            <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between items-center">
              <span class="text-lg font-semibold text-gray-900">合计：</span>
              <span class="text-2xl font-bold text-red-600">¥{{ order.total_amount }}</span>
            </div>
          </div>

          <!-- 操作按钮 -->
          <div class="flex gap-4 justify-center flex-wrap">
            <!-- 待评价状态：显示评价和跳过按钮 -->
            <el-button
              v-if="order.status === 'pending_review' || String(order.status) === 'pending_review'"
              type="primary"
              size="large"
              @click="goToReview"
            >
              <el-icon><Edit /></el-icon>
              去评价
            </el-button>
            <el-button
              v-if="order.status === 'pending_review' || String(order.status) === 'pending_review'"
              type="default"
              size="large"
              @click="skipReview"
            >
              跳过评价
            </el-button>
            <!-- 已完成状态：显示查看评价按钮 -->
            <el-button
              v-else-if="order.status === 'completed'"
              type="primary"
              size="large"
              @click="goToMyReviews"
            >
              <el-icon><View /></el-icon>
              查看我的评价
            </el-button>
            <!-- 待支付状态：显示支付和取消按钮 -->
            <template v-else-if="order.status === 'pending'">
              <el-button
                type="danger"
                size="large"
                @click="cancelOrder(order.id)"
              >
                取消订单
              </el-button>
              <el-button
                type="primary"
                size="large"
                @click="goToCheckout(order.id)"
              >
                去支付
              </el-button>
            </template>
            <!-- 已支付状态（旧数据兼容）：显示查看详情 -->
            <el-button
              v-else-if="order.status === 'paid'"
              type="info"
              size="large"
              disabled
            >
              已支付
            </el-button>
            <el-button
              type="default"
              size="large"
              @click="goBack"
            >
              返回订单列表
            </el-button>
          </div>
        </div>

        <!-- 订单不存在 -->
        <div v-else class="text-center py-20">
          <div class="text-6xl mb-4">❌</div>
          <h3 class="text-2xl font-bold text-gray-900 mb-2">订单不存在</h3>
          <p class="text-gray-600 mb-6">该订单可能已被删除或不存在</p>
          <el-button type="primary" size="large" @click="goBack">
            返回订单列表
          </el-button>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Loading, ArrowLeft, Edit, View, UserFilled } from '@element-plus/icons-vue';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { orderApi, type Order } from '../../api/order';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const order = ref<Order | null>(null);

const getStatusTag = (status: string) => {
  const tags: Record<string, string> = {
    pending: 'warning',
    paid: 'success',
    pending_review: 'warning',
    completed: '',
    cancelled: 'info',
  };
  return tags[status] || '';
};

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: '待支付',
    paid: '已支付',
    pending_review: '待评价',
    completed: '已完成',
    cancelled: '已取消',
  };
  return texts[status] || status;
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const fetchOrderDetail = async () => {
  const orderId = Number(route.params.orderId);
  if (!orderId || isNaN(orderId)) {
    ElMessage.error('订单ID无效');
    router.push('/frontend/orders');
    return;
  }

  loading.value = true;
  try {
    const response = await orderApi.getDetail(orderId);
    if (response.code === 200 && response.data) {
      order.value = response.data;
    } else {
      ElMessage.error(response.message || '获取订单详情失败');
    }
  } catch (error: any) {
    console.error('获取订单详情失败:', error);
    ElMessage.error(error.message || '获取订单详情失败');
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
      await fetchOrderDetail();
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

const goToCheckout = (orderId: number) => {
  router.push(`/frontend/checkout/${orderId}`);
};

const goBack = () => {
  router.push('/frontend/orders');
};

const goToReview = () => {
  if (order.value) {
    router.push(`/frontend/review/${order.value.id}`);
  }
};

const skipReview = async () => {
  if (!order.value) return;

  try {
    await ElMessageBox.confirm('确定要跳过评价吗？跳过后将无法再评价此订单。', '提示', {
      confirmButtonText: '确定跳过',
      cancelButtonText: '取消',
      type: 'warning',
    });

    const response = await orderApi.skipReview(order.value.id);
    if (response.code === 200) {
      ElMessage.success('订单已完成');
      await fetchOrderDetail();
    } else {
      ElMessage.error(response.message || '操作失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('跳过评价失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '操作失败');
    }
  }
};

const goToMyReviews = () => {
  router.push('/frontend/profile?tab=reviews');
};

onMounted(() => {
  fetchOrderDetail();
});
</script>

<style scoped>
</style>

