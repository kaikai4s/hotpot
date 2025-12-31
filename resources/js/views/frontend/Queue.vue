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
          <p class="text-gray-600 mb-6">当前状态：{{ getStatusText(queueInfo.status) }}</p>
          
          <!-- 进度条 -->
          <div class="mb-8">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm text-gray-600">前面还有</span>
              <span class="text-2xl font-bold text-red-600">{{ queueInfo.current_position - 1 }}</span>
              <span class="text-sm text-gray-600">桌</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
              <div
                class="bg-gradient-to-r from-green-400 to-blue-500 h-4 rounded-full transition-all duration-500"
                :style="{ width: `${(1 - (queueInfo.current_position - 1) / 10) * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- 预计等待时间 -->
          <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 mb-6">
            <p class="text-gray-600 mb-2">预计等待时间</p>
            <p class="text-4xl font-bold text-blue-600">{{ queueInfo.estimated_wait_time }} 分钟</p>
          </div>

          <!-- 操作按钮 -->
          <div class="flex gap-4 justify-center">
            <button
              v-if="queueInfo.status === 'waiting'"
              @click="cancelQueue"
              class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all"
            >
              取消排队
            </button>
            <button
              @click="refreshQueue"
              class="px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-lg hover:from-blue-600 hover:to-purple-600 transition-all transform hover:scale-105"
            >
              刷新状态
            </button>
          </div>
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
            class="w-full bg-gradient-to-r from-green-500 to-blue-500 text-white py-4 rounded-lg text-lg font-semibold hover:from-green-600 hover:to-blue-600 transition-all transform hover:scale-105 shadow-lg"
          >
            加入排队
          </button>
          </div>
        </div>

        <!-- 排队说明 -->
        <div class="mt-8 bg-white rounded-xl shadow-md p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">📋 排队说明</h3>
          <ul class="space-y-2 text-gray-600">
            <li>• 排队号有效期为2小时</li>
            <li>• 叫号后请在15分钟内到店，否则将重新排队</li>
            <li>• 可通过刷新查看最新排队状态</li>
            <li>• 如需取消排队，请点击取消按钮</li>
          </ul>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';

const form = ref({
  guest_count: 4,
  table_type: 'any',
});

const queueInfo = ref<any>(null);

const tableTypes = [
  { label: '窗边', value: 'window' },
  { label: '角落', value: 'corner' },
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

const joinQueue = async () => {
  try {
    // 模拟加入排队
    queueInfo.value = {
      queue_number: 'A' + Math.floor(Math.random() * 1000),
      current_position: Math.floor(Math.random() * 10) + 1,
      estimated_wait_time: Math.floor(Math.random() * 30) + 15,
      status: 'waiting',
    };
    ElMessage.success('排队成功！');
  } catch (error) {
    ElMessage.error('加入排队失败');
  }
};

const cancelQueue = async () => {
  try {
    await ElMessageBox.confirm('确认取消排队吗？', '提示', {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'warning',
    });
    queueInfo.value = null;
    ElMessage.success('已取消排队');
  } catch {
    // 取消操作
  }
};

const refreshQueue = () => {
  if (queueInfo.value) {
    queueInfo.value.current_position = Math.max(1, queueInfo.value.current_position - 1);
    queueInfo.value.estimated_wait_time = Math.max(5, queueInfo.value.estimated_wait_time - 5);
    ElMessage.success('状态已更新');
  }
};

const router = useRouter();

onMounted(() => {
  // 检查登录状态
  const token = localStorage.getItem('token');
  if (!token) {
    router.push({
      path: '/frontend/login',
      query: { redirect: '/frontend/queue' },
    });
    return;
  }
  
  // 检查是否已有排队
  // TODO: 调用API获取当前排队状态
});
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

