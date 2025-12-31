/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 返回按钮 -->
        <div class="mb-6">
          <el-button @click="goBack" :icon="ArrowLeft">返回</el-button>
        </div>

        <div v-if="loading" class="text-center py-20">
          <el-icon class="is-loading text-4xl text-red-600"><Loading /></el-icon>
          <p class="mt-4 text-gray-600">加载中...</p>
        </div>

        <div v-else-if="reservation" class="space-y-6">
          <!-- 预约信息卡片 -->
          <div class="bg-white rounded-2xl shadow-xl p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">预约详情</h2>
            
            <div class="space-y-4">
              <div class="flex justify-between items-center">
                <span class="text-gray-600">预约编号：</span>
                <span class="font-semibold text-gray-900">{{ reservation.reservation_code }}</span>
              </div>
              
              <div class="flex justify-between items-center">
                <span class="text-gray-600">预约状态：</span>
                <el-tag :type="getStatusTag(reservation.status)">{{ getStatusText(reservation.status) }}</el-tag>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-gray-600">桌位：</span>
                <span class="font-semibold text-gray-900">{{ reservation.table?.name }}</span>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-gray-600">预约日期：</span>
                <span class="font-semibold text-gray-900">{{ formatDate(reservation.date) }}</span>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-gray-600">时间段：</span>
                <span class="font-semibold text-gray-900">{{ reservation.time_slot }}</span>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-gray-600">用餐人数：</span>
                <span class="font-semibold text-gray-900">{{ reservation.guest_count }}人</span>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-gray-600">联系人：</span>
                <span class="font-semibold text-gray-900">{{ reservation.contact_name }}</span>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-gray-600">联系电话：</span>
                <span class="font-semibold text-gray-900">{{ reservation.contact_phone }}</span>
              </div>

              <div v-if="reservation.special_requests" class="flex justify-between items-start">
                <span class="text-gray-600">特殊需求：</span>
                <span class="font-semibold text-gray-900 text-right">{{ reservation.special_requests }}</span>
              </div>
            </div>
          </div>

          <!-- 定金信息卡片 -->
          <div class="bg-white rounded-2xl shadow-xl p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">定金信息</h2>
            
            <div class="space-y-4">
              <div class="flex justify-between items-center">
                <span class="text-gray-600">定金金额：</span>
                <span class="text-2xl font-bold text-red-600">¥{{ reservation.deposit_amount || 0 }}</span>
              </div>

              <div class="flex justify-between items-center">
                <span class="text-gray-600">定金状态：</span>
                <el-tag :type="getDepositStatusTag(reservation.deposit_status)">
                  {{ getDepositStatusText(reservation.deposit_status) }}
                </el-tag>
              </div>

              <div v-if="reservation.deposit_paid_at" class="flex justify-between items-center">
                <span class="text-gray-600">支付时间：</span>
                <span class="font-semibold text-gray-900">{{ formatDateTime(reservation.deposit_paid_at) }}</span>
              </div>

              <!-- 过期提示 -->
              <div v-if="isExpired || reservation.status === 'expired'" class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-start">
                  <el-icon class="text-red-500 mt-1 mr-2"><Warning /></el-icon>
                  <div>
                    <p class="font-semibold text-red-900">预约已过期失效</p>
                    <p class="text-sm text-red-700 mt-1">
                      超过预约时间{{ timeoutMinutes }}分钟后，定金不予退还
                    </p>
                    <p v-if="reservation.deposit_status === 'forfeited'" class="text-sm text-red-800 mt-2 font-semibold">
                      定金已被没收，无法退还
                    </p>
                  </div>
                </div>
              </div>

              <!-- 支付定金按钮 -->
              <div v-if="reservation.deposit_status === 'unpaid' && reservation.status !== 'cancelled' && reservation.status !== 'expired'">
                <el-button
                  type="primary"
                  size="large"
                  class="w-full"
                  :loading="paying"
                  @click="showPaymentDialog = true"
                >
                  支付定金 ¥{{ reservation.deposit_amount || 0 }}
                </el-button>
              </div>
            </div>
          </div>

          <!-- 操作按钮 -->
          <div class="flex gap-4">
            <el-button
              v-if="reservation.status === 'pending' || reservation.status === 'confirmed'"
              size="large"
              @click="handleCancel"
            >
              取消预约
            </el-button>
            <el-button
              v-if="reservation.status === 'confirmed' && !reservation.arrived_at"
              type="success"
              size="large"
              @click="handleArrive"
            >
              确认到达
            </el-button>
          </div>
        </div>

        <!-- 支付对话框 -->
        <el-dialog
          v-model="showPaymentDialog"
          title="支付预约定金"
          width="400px"
          :close-on-click-modal="false"
        >
          <div class="space-y-4">
            <div class="text-center">
              <p class="text-2xl font-bold text-gray-900 mb-2">¥{{ reservation?.deposit_amount || 0 }}</p>
              <p class="text-sm text-gray-600">请选择支付方式</p>
            </div>

            <el-radio-group v-model="selectedPaymentMethod" class="w-full">
              <div
                v-for="method in paymentMethods"
                :key="method.code"
                class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all mb-3"
                :class="selectedPaymentMethod === method.code ? 'border-red-500 bg-red-50' : 'border-gray-200 hover:border-gray-300'"
                @click="selectedPaymentMethod = method.code"
              >
                <el-radio :label="method.code" class="mr-4">
                  <div>
                    <div class="font-semibold text-gray-900">{{ method.name }}</div>
                    <div class="text-sm text-gray-600">{{ method.description }}</div>
                  </div>
                </el-radio>
              </div>
            </el-radio-group>
          </div>

          <template #footer>
            <div class="flex gap-3">
              <el-button @click="showPaymentDialog = false">取消</el-button>
              <el-button
                type="primary"
                :loading="paying"
                :disabled="!selectedPaymentMethod"
                @click="handlePayDeposit"
              >
                确认支付
              </el-button>
            </div>
          </template>
        </el-dialog>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Loading, ArrowLeft, Warning } from '@element-plus/icons-vue';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { reservationApi } from '../../api/reservation';
import { frontendConfigApi } from '../../api/frontend-config';
import type { Reservation } from '../../types';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const paying = ref(false);
const reservation = ref<Reservation | null>(null);
const showPaymentDialog = ref(false);
const selectedPaymentMethod = ref<'wechat' | 'mock'>('mock');
const timeoutMinutes = ref(30); // 从配置获取
const cancelHoursLimit = ref(1); // 从配置获取

const paymentMethods = [
  {
    code: 'mock',
    name: '模拟支付',
    description: '用于测试的模拟支付方式',
  },
  {
    code: 'wechat',
    name: '微信支付',
    description: '使用微信支付',
  },
];

const isExpired = computed(() => {
  if (!reservation.value || !reservation.value.date || !reservation.value.time_slot) {
    return false;
  }

  // 确保 date 只取日期部分（YYYY-MM-DD），time_slot 只取时间部分（HH:MM）
  const dateStr = typeof reservation.value.date === 'string' 
    ? reservation.value.date.split(' ')[0] 
    : new Date(reservation.value.date).toISOString().split('T')[0];
  const timeStr = reservation.value.time_slot.split(' ')[0].substring(0, 5); // 只取 HH:MM 部分
  
  const reservationDateTime = new Date(`${dateStr} ${timeStr}`);
  if (isNaN(reservationDateTime.getTime())) {
    return false; // 如果日期无效，返回 false
  }
  
  const expiredTime = new Date(reservationDateTime.getTime() + timeoutMinutes.value * 60 * 1000);
  
  return new Date() > expiredTime && reservation.value.status !== 'completed';
});

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

const getStatusTag = (status: string) => {
  const types: Record<string, string> = {
    pending: 'warning',
    confirmed: 'success',
    cancelled: 'info',
    completed: 'success',
    expired: 'danger',
  };
  return types[status] || '';
};

const getDepositStatusText = (status?: string) => {
  const texts: Record<string, string> = {
    unpaid: '未支付',
    paid: '已支付',
    refunded: '已退还',
    forfeited: '已没收',
  };
  return texts[status || 'unpaid'] || '未知';
};

const getDepositStatusTag = (status?: string) => {
  const types: Record<string, string> = {
    unpaid: 'warning',
    paid: 'success',
    refunded: 'info',
    forfeited: 'danger',
  };
  return types[status || 'unpaid'] || '';
};

const formatDate = (date: string) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('zh-CN');
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const goBack = () => {
  router.back();
};

const fetchReservationDetail = async () => {
  const reservationId = Number(route.params.reservationId);
  if (isNaN(reservationId) || reservationId <= 0) {
    ElMessage.error('预约ID无效');
    router.push('/frontend/profile?tab=reservations');
    return;
  }

  loading.value = true;
  try {
    const response = await reservationApi.getDetail(reservationId);
    if (response.code === 200 && response.data) {
      reservation.value = response.data;
    } else {
      ElMessage.error(response.message || '获取预约详情失败');
      router.push('/frontend/profile?tab=reservations');
    }
  } catch (error: any) {
    console.error('获取预约详情失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取预约详情失败');
    router.push('/frontend/profile?tab=reservations');
  } finally {
    loading.value = false;
  }
};

const handlePayDeposit = async () => {
  if (!reservation.value || !selectedPaymentMethod.value) {
    return;
  }

  paying.value = true;
  try {
    const response = await reservationApi.payDeposit(reservation.value.id, {
      payment_method: selectedPaymentMethod.value,
    });

    if (response.code === 200) {
      ElMessage.success('定金支付成功');
      showPaymentDialog.value = false;
      await fetchReservationDetail();
    } else {
      ElMessage.error(response.message || '支付失败');
    }
  } catch (error: any) {
    console.error('支付定金失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '支付失败');
  } finally {
    paying.value = false;
  }
};

const handleCancel = async () => {
  if (!reservation.value) return;

  try {
    // 检查是否在取消时间限制内
    if (reservation.value.date && reservation.value.time_slot) {
      // 确保 date 只取日期部分（YYYY-MM-DD），time_slot 只取时间部分（HH:MM）
      const dateStr = typeof reservation.value.date === 'string' 
        ? reservation.value.date.split(' ')[0] 
        : new Date(reservation.value.date).toISOString().split('T')[0];
      const timeStr = reservation.value.time_slot.split(' ')[0].substring(0, 5); // 只取 HH:MM 部分
      
      const reservationDateTime = new Date(`${dateStr} ${timeStr}`);
      if (isNaN(reservationDateTime.getTime())) {
        console.error('无效的预约日期时间:', dateStr, timeStr);
        ElMessage.error('预约日期时间格式错误');
        return;
      }
      
      const cancelDeadline = new Date(reservationDateTime.getTime() - cancelHoursLimit.value * 60 * 60 * 1000);
      
      if (new Date() >= cancelDeadline) {
        ElMessage.warning(`预约开始前${cancelHoursLimit.value}小时内不可取消预约`);
        return;
      }
    }

    let confirmMessage = '确定要取消预约吗？';
    if (reservation.value.deposit_status === 'paid' && !reservation.value.deposit_refunded_at) {
      confirmMessage = `确定要取消预约吗？\n\n已支付的定金 ¥${reservation.value.deposit_amount} 将自动原路返还。`;
    }

    await ElMessageBox.confirm(confirmMessage, '提示', {
      confirmButtonText: '确定取消',
      cancelButtonText: '取消',
      type: 'warning',
    });

    loading.value = true;
    const response = await reservationApi.cancel(reservation.value.id);
    
    if (response.code === 200) {
      ElMessage.success('预约已取消' + (reservation.value.deposit_status === 'paid' ? '，定金将自动返还' : ''));
      await fetchReservationDetail();
    } else {
      ElMessage.error(response.message || '取消预约失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('取消预约失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '取消预约失败');
    }
  } finally {
    loading.value = false;
  }
};

const handleArrive = async () => {
  if (!reservation.value) return;

  try {
    await ElMessageBox.confirm('确认已到达餐厅吗？', '提示', {
      confirmButtonText: '确认到达',
      cancelButtonText: '取消',
      type: 'info',
    });

    const response = await reservationApi.arrive(reservation.value.id);
    if (response.code === 200) {
      ElMessage.success('已确认到达');
      await fetchReservationDetail();
    } else {
      ElMessage.error(response.message || '确认到达失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('确认到达失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '确认到达失败');
    }
  }
};

const loadTimeoutConfig = async () => {
  try {
    const [timeoutRes, cancelLimitRes] = await Promise.all([
      frontendConfigApi.getPublicConfig('reservation_timeout_minutes'),
      frontendConfigApi.getPublicConfig('reservation_cancel_hours_limit'),
    ]);
    
    if (timeoutRes.code === 200 && timeoutRes.data) {
      timeoutMinutes.value = parseInt(timeoutRes.data.value) || 30;
    }
    
    if (cancelLimitRes.code === 200 && cancelLimitRes.data) {
      cancelHoursLimit.value = parseInt(cancelLimitRes.data.value) || 1;
    }
  } catch (error: any) {
    console.error('获取配置失败:', error);
  }
};

onMounted(() => {
  fetchReservationDetail();
  loadTimeoutConfig();
});
</script>

