<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">📋 我的任务</h1>
          <p class="text-xl text-gray-600">完成任务，获得丰厚奖励</p>
        </div>

        <!-- 任务类型切换 -->
        <div class="flex justify-center gap-4 mb-8">
          <el-button
            :type="activeTab === 'daily' ? 'primary' : ''"
            @click="activeTab = 'daily'"
          >
            每日任务
          </el-button>
          <el-button
            :type="activeTab === 'weekly' ? 'primary' : ''"
            @click="activeTab = 'weekly'"
          >
            每周任务
          </el-button>
          <el-button
            :type="activeTab === 'achievement' ? 'primary' : ''"
            @click="activeTab = 'achievement'"
          >
            成就任务
          </el-button>
        </div>

        <!-- 任务列表 -->
        <div v-loading="loading" class="space-y-4">
          <div
            v-for="task in currentTasks"
            :key="task.id"
            class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-all"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="text-xl font-bold text-gray-900">{{ task.task_template.name }}</h3>
                  <span
                    class="text-xs px-2 py-1 rounded"
                    :class="getStatusClass(task.status)"
                  >
                    {{ getStatusText(task.status) }}
                  </span>
                </div>
                <p class="text-gray-600 mb-4">{{ task.task_template.description }}</p>
                
                <!-- 进度条 -->
                <div class="mb-4">
                  <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>进度</span>
                    <span>{{ task.progress.current }} / {{ task.progress.target }}</span>
                  </div>
                  <el-progress
                    :percentage="getProgressPercentage(task)"
                    :color="getProgressColor(task.status)"
                  />
                </div>

                <!-- 奖励信息 -->
                <div class="flex items-center gap-4">
                  <div v-if="task.task_template.reward_points > 0" class="flex items-center gap-2">
                    <span class="text-yellow-500">💰</span>
                    <span class="text-gray-700">{{ task.task_template.reward_points }}积分</span>
                  </div>
                  <div v-if="task.task_template.reward_coupon_id" class="flex items-center gap-2">
                    <span class="text-red-500">🎫</span>
                    <span class="text-gray-700">优惠券</span>
                  </div>
                </div>

                <!-- 过期时间 -->
                <div v-if="task.expires_at && task.status !== 'completed'" class="mt-4 text-sm text-gray-500">
                  过期时间：{{ formatDateTime(task.expires_at) }}
                </div>
              </div>

              <!-- 操作按钮 -->
              <div class="ml-4">
                <el-button
                  v-if="task.status === 'completed' && !task.reward_issued"
                  type="success"
                  disabled
                >
                  已完成
                </el-button>
                <el-button
                  v-else-if="task.task_template.category === 'sign' && task.status !== 'completed'"
                  type="primary"
                  @click="completeTask(task.id)"
                >
                  立即完成
                </el-button>
                <el-button
                  v-else-if="task.status === 'completed'"
                  type="success"
                  disabled
                >
                  ✓ 已完成
                </el-button>
              </div>
            </div>
          </div>

          <div v-if="currentTasks.length === 0 && !loading" class="text-center py-12 text-gray-500">
            <p class="text-xl mb-2">暂无{{ getTabName(activeTab) }}</p>
            <p class="text-sm">快去完成任务吧！</p>
          </div>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import FrontendLayout from '@/components/frontend/FrontendLayout.vue';
import { taskApi, type UserTask } from '@/api/task';

const loading = ref(false);
const activeTab = ref<'daily' | 'weekly' | 'achievement'>('daily');
const tasks = ref<Record<string, UserTask[]>>({
  daily: [],
  weekly: [],
  achievement: [],
});

const currentTasks = computed(() => {
  return tasks.value[activeTab.value] || [];
});

const loadTasks = async () => {
  loading.value = true;
  try {
    const response = await taskApi.getTasks();
    if (response.code === 200) {
      tasks.value = response.data.tasks as Record<string, UserTask[]>;
    }
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || '加载失败');
  } finally {
    loading.value = false;
  }
};

const completeTask = async (taskId: number) => {
  try {
    const response = await taskApi.completeTask(taskId);
    if (response.code === 200) {
      ElMessage.success('任务完成！');
      await loadTasks();
    }
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || '操作失败');
  }
};

const getStatusText = (status: string) => {
  const statusMap: Record<string, string> = {
    pending: '待开始',
    in_progress: '进行中',
    completed: '已完成',
    expired: '已过期',
  };
  return statusMap[status] || status;
};

const getStatusClass = (status: string) => {
  const classMap: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-600',
    in_progress: 'bg-blue-100 text-blue-600',
    completed: 'bg-green-100 text-green-600',
    expired: 'bg-red-100 text-red-600',
  };
  return classMap[status] || 'bg-gray-100 text-gray-600';
};

const getProgressPercentage = (task: UserTask) => {
  if (task.progress.target === 0) return 0;
  const percentage = (task.progress.current / task.progress.target) * 100;
  return Math.min(percentage, 100);
};

const getProgressColor = (status: string) => {
  if (status === 'completed') return '#67c23a';
  if (status === 'expired') return '#f56c6c';
  return '#409eff';
};

const getTabName = (tab: string) => {
  const tabMap: Record<string, string> = {
    daily: '每日任务',
    weekly: '每周任务',
    achievement: '成就任务',
  };
  return tabMap[tab] || '';
};

const formatDateTime = (date: string) => {
  return new Date(date).toLocaleString('zh-CN');
};

onMounted(() => {
  loadTasks();
});
</script>

