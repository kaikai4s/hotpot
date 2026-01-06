<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">🎫 排队叫号</h1>
          <p class="text-xl text-gray-600">实时查看排队状态</p>
        </div>

        <!-- 排队状态卡片 -->
        <div v-if="queueInfo" class="bg-white rounded-2xl shadow-xl p-8 mb-8">
          <div class="text-center">
          <div class="text-6xl mb-4">🎫</div>
          <h2 class="text-3xl font-bold text-gray-900 mb-2">排队号：{{ queueInfo.queue_number }}</h2>
          <p class="mb-6">
            <span class="text-gray-600">当前状态：</span>
            <span :class="['font-semibold', getStatusColor(queueInfo.status)]">
              {{ getStatusText(queueInfo.status) }}
            </span>
          </p>
          
          <!-- 已叫号提示 -->
          <div v-if="queueInfo.status === 'called'" class="mb-6 p-4 bg-yellow-50 border-2 border-yellow-400 rounded-lg">
            <p class="text-yellow-800 font-bold text-lg">🔔 您已被叫号，请尽快到店！</p>
            <p class="text-yellow-700 text-sm mt-1">请在{{ calledTimeoutMinutes }}分钟内到店，否则将重新排队</p>
          </div>
          
          <!-- 进度条 -->
          <div v-if="queueInfo.status === 'waiting'" class="mb-8">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm text-gray-600">前面还有</span>
              <span class="text-2xl font-bold text-red-600">{{ queueInfo.ahead_count }}</span>
              <span class="text-sm text-gray-600">桌</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
              <div
                class="bg-gradient-to-r from-green-400 to-blue-500 h-4 rounded-full transition-all duration-500"
                :style="{ width: `${Math.max(10, (1 - queueInfo.ahead_count / Math.max(queueInfo.current_position, 10)) * 100)}%` }"
              ></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">您当前排在第 {{ queueInfo.current_position }} 位</p>
          </div>

          <!-- 预计等待时间 -->
          <div v-if="queueInfo.status === 'waiting'" class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 mb-6">
            <p class="text-gray-600 mb-2">预计等待时间</p>
            <p class="text-4xl font-bold text-blue-600">{{ queueInfo.estimated_wait_time }} 分钟</p>
          </div>
          
          <!-- 排队信息 -->
          <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-600">用餐人数：</span>
                <span class="font-semibold">{{ queueInfo.guest_count }} 人</span>
              </div>
              <div>
                <span class="text-gray-600">桌位偏好：</span>
                <span class="font-semibold">{{ queueInfo.table_type ? getTableTypeLabel(queueInfo.table_type) : '不限' }}</span>
              </div>
              <div>
                <span class="text-gray-600">加入时间：</span>
                <span class="font-semibold">{{ formatTime(queueInfo.joined_at) }}</span>
              </div>
              <div v-if="queueInfo.called_at">
                <span class="text-gray-600">叫号时间：</span>
                <span class="font-semibold text-blue-600">{{ formatTime(queueInfo.called_at) }}</span>
              </div>
            </div>
          </div>

          <!-- 操作按钮 -->
          <div class="flex gap-4 justify-center">
            <button
              v-if="queueInfo.status === 'waiting' || queueInfo.status === 'called'"
              @click="cancelQueue"
              :disabled="loading"
              class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all disabled:opacity-50"
            >
              取消排队
            </button>
            <button
              @click="refreshQueue"
              :disabled="loading"
              class="px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-lg hover:from-blue-600 hover:to-purple-600 transition-all transform hover:scale-105 disabled:opacity-50"
            >
              <span v-if="loading">刷新中...</span>
              <span v-else>刷新状态</span>
            </button>
          </div>
          
          <!-- 自动刷新提示 -->
          <p v-if="autoRefreshTimer !== null" class="text-xs text-gray-500 mt-4">
            ⏱️ 状态将每30秒自动刷新
          </p>
          </div>
        </div>

        <!-- 加入排队表单 -->
        <div v-else class="bg-white rounded-2xl shadow-xl p-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">加入排队</h2>
          
          <div class="space-y-6">
          <!-- 用餐人数 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">用餐人数</label>
            <div class="flex items-center space-x-4">
              <button
                @click="form.guest_count = Math.max(1, form.guest_count - 1)"
                class="w-12 h-12 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center text-xl"
              >
                -
              </button>
              <span class="text-3xl font-bold text-gray-900 w-16 text-center">{{ form.guest_count }}</span>
              <button
                @click="form.guest_count = Math.min(20, form.guest_count + 1)"
                class="w-12 h-12 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center text-xl"
              >
                +
              </button>
              <span class="text-gray-600 text-lg">人</span>
            </div>
          </div>

          <!-- 桌位类型偏好 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">桌位类型偏好</label>
            <div class="grid grid-cols-3 gap-3">
              <button
                v-for="type in tableTypes"
                :key="type.value"
                @click="form.table_type = type.value"
                class="py-4 px-6 rounded-lg border-2 transition-all"
                :class="form.table_type === type.value
                  ? 'border-blue-500 bg-blue-50 text-blue-600 font-semibold'
                  : 'border-gray-200 hover:border-blue-300 text-gray-700'"
              >
                {{ type.label }}
              </button>
            </div>
          </div>

          <!-- 提交按钮 -->
          <button
            @click="joinQueue"
            :disabled="loading"
            class="w-full bg-gradient-to-r from-green-500 to-blue-500 text-white py-4 rounded-lg text-lg font-semibold hover:from-green-600 hover:to-blue-600 transition-all transform hover:scale-105 shadow-lg disabled:opacity-50"
          >
            <span v-if="loading">加入中...</span>
            <span v-else>加入排队</span>
          </button>
          </div>
        </div>

        <!-- 排队说明 -->
        <div class="mt-8 bg-white rounded-xl shadow-md p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">📋 排队说明</h3>
          <ul class="space-y-2 text-gray-600">
            <li>• 排队号有效期为2小时</li>
            <li>• 叫号后请在{{ calledTimeoutMinutes }}分钟内到店，否则将重新排队</li>
            <li>• 可通过刷新查看最新排队状态</li>
            <li>• 如需取消排队，请点击取消按钮</li>
          </ul>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { queueApi, type QueueInfo } from '../../api/queue';
import { frontendConfigApi } from '../../api/frontend-config';

const form = ref({
  guest_count: 4,
  table_type: 'any',
});

const queueInfo = ref<QueueInfo | null>(null);
const loading = ref(false);
const autoRefreshTimer = ref<number | null>(null);
const calledTimeoutMinutes = ref(15); // 叫号后预留时间（分钟），从配置获取

const tableTypes = [
  { label: '窗边', value: 'window' },
  { label: '角落', value: 'corner' },
  { label: '中央', value: 'center' },
  { label: '任意', value: 'any' },
];

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    waiting: '等待中',
    called: '已叫号',
    seated: '已入座',
    cancelled: '已取消',
  };
  return texts[status] || status;
};

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    waiting: 'text-yellow-600',
    called: 'text-blue-600',
    seated: 'text-green-600',
    cancelled: 'text-gray-600',
  };
  return colors[status] || 'text-gray-600';
};

const loadMyQueue = async () => {
  try {
    const response = await queueApi.getMyQueue();
    // apiClient响应拦截器返回的是response.data，所以直接使用response.code
    if (response.code === 200) {
      queueInfo.value = response.data || null;
      // 如果有排队且状态是等待中或已叫号，启动自动刷新
      if (queueInfo.value && (queueInfo.value.status === 'waiting' || queueInfo.value.status === 'called')) {
        startAutoRefresh();
      } else {
        stopAutoRefresh();
      }
    }
  } catch (error: any) {
    console.error('获取排队状态失败:', error);
    // 401错误是正常情况（用户未登录），不显示错误消息
    // 其他错误才显示错误消息
    if (error.response?.status !== 401 && error.response?.status !== undefined) {
      ElMessage.error(error.response?.data?.message || error.message || '获取排队状态失败');
    }
  }
};

const joinQueue = async () => {
  if (loading.value) return;
  
  loading.value = true;
  try {
    const response = await queueApi.join({
      guest_count: form.value.guest_count,
      table_type: form.value.table_type === 'any' ? null : form.value.table_type,
    });
    
    // apiClient响应拦截器返回的是response.data，所以直接使用response.code
    if (response.code === 201 || response.code === 200) {
      queueInfo.value = response.data;
      ElMessage.success(`排队成功！您的排队号是：${queueInfo.value.queue_number}`);
      startAutoRefresh();
    }
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || '加入排队失败';
    ElMessage.error(errorMessage);
    
    // 如果是已在队列中，刷新状态
    if (error.response?.status === 429) {
      await loadMyQueue();
    }
  } finally {
    loading.value = false;
  }
};

const cancelQueue = async () => {
  if (!queueInfo.value) return;
  
  try {
    await ElMessageBox.confirm('确认取消排队吗？', '提示', {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'warning',
    });
    
    const response = await queueApi.cancel(queueInfo.value.queue_id);
    // apiClient响应拦截器返回的是response.data，所以直接使用response.code
    if (response.code === 200) {
      queueInfo.value = null;
      stopAutoRefresh();
      ElMessage.success('已取消排队');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || '取消失败');
    }
  }
};

const refreshQueue = async () => {
  if (!queueInfo.value) {
    await loadMyQueue();
    return;
  }
  
  try {
    // 使用getMyQueue获取完整信息，而不是getStatus
    const response = await queueApi.getMyQueue();
    // apiClient响应拦截器返回的是response.data，所以直接使用response.code
    if (response.code === 200) {
      const newStatus = response.data;
      // 如果排队不存在或状态变为已入座或已取消，停止自动刷新
      if (!newStatus || newStatus.status === 'seated' || newStatus.status === 'cancelled') {
        stopAutoRefresh();
      }
      // 更新排队信息（保留所有字段）
      if (newStatus) {
        queueInfo.value = newStatus;
        // 如果状态是等待中或已叫号，确保自动刷新在运行
        if (newStatus.status === 'waiting' || newStatus.status === 'called') {
          startAutoRefresh();
        }
        ElMessage.success('状态已更新');
      } else {
        // 排队不存在，清空状态
        queueInfo.value = null;
        ElMessage.info('您的排队已结束');
      }
    }
  } catch (error: any) {
    // 如果排队不存在，清空状态
    if (error.response?.status === 404) {
      queueInfo.value = null;
      stopAutoRefresh();
      ElMessage.info('您的排队已结束');
    } else {
      ElMessage.error(error.response?.data?.message || error.message || '刷新失败');
    }
  }
};

const startAutoRefresh = () => {
  stopAutoRefresh(); // 先清除之前的定时器
  // 每30秒自动刷新一次
  autoRefreshTimer.value = window.setInterval(() => {
    if (queueInfo.value) {
      refreshQueue();
    }
  }, 30000);
};

const stopAutoRefresh = () => {
  if (autoRefreshTimer.value !== null) {
    clearInterval(autoRefreshTimer.value);
    autoRefreshTimer.value = null;
  }
};

const router = useRouter();

// 加载配置
const loadConfig = async () => {
  try {
    const response = await frontendConfigApi.getPublicConfig('queue_called_timeout_minutes');
    if (response.code === 200 && response.data?.value) {
      calledTimeoutMinutes.value = parseInt(response.data.value, 10) || 15;
    }
  } catch (error) {
    console.warn('加载叫号预留时间配置失败，使用默认值15分钟', error);
  }
};

onMounted(async () => {
  // 先加载配置
  await loadConfig();
  
  // 检查登录状态
  const token = localStorage.getItem('token');
  if (!token) {
    router.push({
      path: '/frontend/login',
      query: { redirect: '/frontend/queue' },
    });
    return;
  }
  
  // 加载当前用户的排队状态
  await loadMyQueue();
});

onUnmounted(() => {
  stopAutoRefresh();
});

const getTableTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    window: '窗边',
    corner: '角落',
    center: '中央',
    any: '任意',
  };
  return labels[type] || type;
};

const formatTime = (dateTime: string | undefined) => {
  if (!dateTime) return '-';
  const date = new Date(dateTime);
  return date.toLocaleString('zh-CN', {
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

<style scoped>
/* 动画效果 */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

