<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">🏆 我的成就</h1>
          <p class="text-xl text-gray-600">完成成就，解锁更多奖励</p>
        </div>

        <!-- 成就统计 -->
        <div class="bg-gradient-to-r from-yellow-400 via-orange-400 to-red-400 rounded-2xl shadow-xl p-8 mb-8 text-white">
          <div class="text-center">
            <div class="grid grid-cols-3 gap-4">
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">已完成</p>
                <p class="text-3xl font-bold">{{ statistics.completed }}</p>
              </div>
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">总成就</p>
                <p class="text-3xl font-bold">{{ statistics.total }}</p>
              </div>
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">完成度</p>
                <p class="text-3xl font-bold">{{ statistics.progress }}%</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 成就分类切换 -->
        <div class="flex justify-center gap-4 mb-8">
          <el-button
            :type="activeCategory === '' ? 'primary' : ''"
            @click="activeCategory = ''"
          >
            全部
          </el-button>
          <el-button
            :type="activeCategory === 'consume' ? 'primary' : ''"
            @click="activeCategory = 'consume'"
          >
            消费
          </el-button>
          <el-button
            :type="activeCategory === 'review' ? 'primary' : ''"
            @click="activeCategory = 'review'"
          >
            评价
          </el-button>
          <el-button
            :type="activeCategory === 'invite' ? 'primary' : ''"
            @click="activeCategory = 'invite'"
          >
            邀请
          </el-button>
          <el-button
            :type="activeCategory === 'checkin' ? 'primary' : ''"
            @click="activeCategory = 'checkin'"
          >
            签到
          </el-button>
          <el-button
            :type="activeCategory === 'points' ? 'primary' : ''"
            @click="activeCategory = 'points'"
          >
            积分
          </el-button>
        </div>

        <!-- 成就列表 -->
        <div v-loading="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="achievement in currentAchievements"
            :key="achievement.id"
            class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-all"
            :class="achievement.completed_at ? 'ring-2 ring-yellow-400' : ''"
          >
            <div class="text-center">
              <!-- 成就图标 -->
              <div class="text-6xl mb-4">
                {{ achievement.achievement_template.icon || '🏆' }}
              </div>
              
              <!-- 成就名称 -->
              <h3 class="text-xl font-bold text-gray-900 mb-2">
                {{ achievement.achievement_template.name }}
              </h3>
              
              <!-- 成就描述 -->
              <p class="text-gray-600 mb-4 text-sm">
                {{ achievement.achievement_template?.description || '' }}
              </p>

              <!-- 进度条 -->
              <div class="mb-4">
                <div class="flex justify-between text-xs text-gray-600 mb-2">
                  <span>进度</span>
                  <span>{{ achievement.progress.current }} / {{ achievement.progress.target }}</span>
                </div>
                <el-progress
                  :percentage="getProgressPercentage(achievement)"
                  :color="achievement.completed_at ? '#67c23a' : '#409eff'"
                />
              </div>

              <!-- 奖励信息 -->
              <div class="flex items-center justify-center gap-4 mb-4">
                <div v-if="achievement.achievement_template?.reward_points > 0" class="flex items-center gap-2">
                  <span class="text-yellow-500">💰</span>
                  <span class="text-gray-700 font-semibold">{{ achievement.achievement_template.reward_points }}积分</span>
                </div>
                <div v-if="achievement.achievement_template?.reward_coupon_id" class="flex items-center gap-2">
                  <span class="text-red-500">🎫</span>
                  <span class="text-gray-700 font-semibold">优惠券</span>
                </div>
              </div>

              <!-- 完成状态 -->
              <div v-if="achievement.completed_at" class="mt-4">
                <el-tag type="success" size="large">✓ 已完成</el-tag>
                <p class="text-xs text-gray-500 mt-2">
                  完成时间：{{ formatDate(achievement.completed_at) }}
                </p>
                <el-button
                  v-if="achievement.achievement_template?.name !== userInfo?.equipped_title"
                  type="primary"
                  size="small"
                  class="mt-2"
                  @click="equipTitle(achievement.id)"
                >
                  佩戴称号
                </el-button>
                <el-button
                  v-else
                  type="warning"
                  size="small"
                  class="mt-2"
                  @click="unequipTitle"
                >
                  卸下称号
                </el-button>
              </div>
            </div>
          </div>

          <div v-if="currentAchievements.length === 0 && !loading" class="col-span-full text-center py-12 text-gray-500">
            <p class="text-xl mb-2">暂无{{ activeCategory ? getCategoryName(activeCategory) : '' }}成就</p>
            <p class="text-sm">快去完成成就吧！</p>
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
import { achievementApi, type UserAchievement } from '@/api/achievement';
import { userAuthApi, type UserInfo } from '@/api/auth';

const loading = ref(false);
const activeCategory = ref<string>('');
const achievements = ref<UserAchievement[]>([]);
const userInfo = ref<UserInfo | null>(null);
const statistics = ref({
  completed: 0,
  total: 0,
  progress: 0,
});

const currentAchievements = computed(() => {
  if (!activeCategory.value) {
    // 如果没有分类，需要处理分组的数据结构
    if (achievements.value.length > 0 && typeof achievements.value[0] === 'object' && !achievements.value[0].id) {
      // 如果是按分类分组的对象，合并所有分类
      return Object.values(achievements.value as any).flat();
    }
    return achievements.value;
  }
  return achievements.value.filter(
    (a) => a.achievement_template?.category === activeCategory.value
  );
});

const loadAchievements = async () => {
  loading.value = true;
  try {
    const response = await achievementApi.getAchievements(activeCategory.value || undefined);
    if (response.code === 200) {
      if (Array.isArray(response.data.achievements)) {
        achievements.value = response.data.achievements;
      } else {
        // 如果是按分类分组的对象，合并所有分类
        const allAchievements: UserAchievement[] = [];
        Object.values(response.data.achievements).forEach((categoryAchievements: any) => {
          if (Array.isArray(categoryAchievements)) {
            allAchievements.push(...categoryAchievements);
          }
        });
        achievements.value = allAchievements;
      }
      statistics.value = response.data.statistics;
    }
  } catch (error: any) {
    console.error('加载成就失败:', error);
    ElMessage.error(error.response?.data?.message || '加载失败');
  } finally {
    loading.value = false;
  }
};

const getProgressPercentage = (achievement: UserAchievement) => {
  if (achievement.progress.target === 0) return 0;
  const percentage = (achievement.progress.current / achievement.progress.target) * 100;
  return Math.min(percentage, 100);
};

const getCategoryName = (category: string) => {
  const categoryMap: Record<string, string> = {
    consume: '消费',
    review: '评价',
    invite: '邀请',
    checkin: '签到',
    points: '积分',
  };
  return categoryMap[category] || category;
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('zh-CN');
};

const equipTitle = async (achievementId: number) => {
  try {
    const response = await achievementApi.equipTitle(achievementId);
    if (response.code === 200) {
      ElMessage.success('称号佩戴成功！');
      await loadUserInfo();
    }
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || '佩戴失败');
  }
};

const unequipTitle = async () => {
  try {
    const response = await achievementApi.unequipTitle();
    if (response.code === 200) {
      ElMessage.success('称号已卸下');
      await loadUserInfo();
    }
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || '卸下失败');
  }
};

const loadUserInfo = async () => {
  try {
    const response = await userAuthApi.me();
    if (response.code === 200 && response.data.user) {
      userInfo.value = response.data.user;
    }
  } catch (error) {
    console.error('加载用户信息失败:', error);
  }
};

onMounted(() => {
  loadAchievements();
  loadUserInfo();
});
</script>

