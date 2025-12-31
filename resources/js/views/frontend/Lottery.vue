/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-orange-50">
    <div class="container mx-auto px-4 py-8">
      <!-- 活动列表 -->
      <div v-if="!selectedActivity" class="space-y-6">
        <!-- 返回按钮 -->
        <div class="mb-4">
          <el-button text @click="goBack" class="mb-2">
            <el-icon><ArrowLeft /></el-icon>
            返回
          </el-button>
        </div>
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">抽奖活动</h1>
        
        <div v-if="loading" class="text-center py-12">
          <el-icon class="is-loading text-4xl text-purple-500"><Loading /></el-icon>
          <p class="mt-4 text-gray-600">加载中...</p>
        </div>

        <div v-else-if="activities.length === 0" class="text-center py-12">
          <p class="text-gray-500">暂无抽奖活动</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="activity in activities"
            :key="activity.id"
            class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all cursor-pointer transform hover:scale-105"
            @click="selectActivity(activity.id)"
          >
            <div v-if="activity.image_url" class="h-48 overflow-hidden relative">
              <img :src="activity.image_url" :alt="activity.name" class="w-full h-full object-cover" />
              <div class="absolute top-2 right-2">
                <el-tag :type="activity.points_cost > 0 ? 'warning' : 'success'" size="small">
                  {{ activity.points_cost > 0 ? `消耗${activity.points_cost}积分` : '免费' }}
                </el-tag>
              </div>
            </div>
            <div v-else class="h-48 bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center relative">
              <span class="text-6xl">🎁</span>
              <div class="absolute top-2 right-2">
                <el-tag :type="activity.points_cost > 0 ? 'warning' : 'success'" size="small">
                  {{ activity.points_cost > 0 ? `消耗${activity.points_cost}积分` : '免费' }}
                </el-tag>
              </div>
            </div>
            <div class="p-6">
              <h3 class="text-xl font-bold text-gray-800 mb-2">{{ activity.name }}</h3>
              <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ activity.description }}</p>
              <div class="flex items-center justify-between">
                <div class="text-xs text-gray-500">
                  <div>奖品数：{{ activity.prizes?.length || 0 }}个</div>
                  <div v-if="activity.daily_limit > 0">每日限{{ activity.daily_limit }}次</div>
                </div>
                <el-button 
                  type="primary" 
                  size="small"
                  :disabled="activity.points_cost > 0 && (!userPoints || userPoints.available_points < activity.points_cost)"
                >
                  立即参与
                </el-button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 抽奖界面 -->
      <div v-else class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-xl p-8">
          <!-- 返回按钮 -->
          <div class="mb-4 flex gap-2">
            <el-button text @click="goBack">
              <el-icon><ArrowLeft /></el-icon>
              返回上一级
            </el-button>
            <el-button text @click="selectedActivity = null">
              <el-icon><ArrowLeft /></el-icon>
              返回活动列表
            </el-button>
          </div>

          <!-- 活动信息 -->
          <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">{{ selectedActivity.name }}</h2>
            <p class="text-gray-600 mb-4">{{ selectedActivity.description }}</p>
            
            <!-- 抽奖信息卡片 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-4">
                <div class="text-sm text-gray-600 mb-1">抽奖类型</div>
                <div class="text-xl font-bold" :class="selectedActivity.points_cost > 0 ? 'text-orange-600' : 'text-green-600'">
                  {{ selectedActivity.points_cost > 0 ? `消耗 ${selectedActivity.points_cost} 积分` : '免费抽奖' }}
                </div>
              </div>
              <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-lg p-4">
                <div class="text-sm text-gray-600 mb-1">今日已抽</div>
                <div class="text-xl font-bold text-blue-600">
                  {{ userStats.today_draw_count }}/{{ selectedActivity.daily_limit || '∞' }}
                </div>
              </div>
              <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-4">
                <div class="text-sm text-gray-600 mb-1">我的积分</div>
                <div class="text-xl font-bold text-green-600">
                  {{ userPoints?.available_points || 0 }}
                </div>
              </div>
            </div>
            
            <!-- 抽奖限制提示 -->
            <div v-if="!userStats.can_draw" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
              <p class="text-sm text-yellow-800">
                <span v-if="selectedActivity.daily_limit > 0 && userStats.today_draw_count >= selectedActivity.daily_limit">
                  今日抽奖次数已达上限
                </span>
                <span v-else-if="selectedActivity.total_limit > 0 && userStats.total_draw_count >= selectedActivity.total_limit">
                  总抽奖次数已达上限
                </span>
                <span v-else-if="selectedActivity.points_cost > 0 && (!userPoints || userPoints.available_points < selectedActivity.points_cost)">
                  积分不足，无法抽奖
                </span>
              </p>
            </div>
          </div>

          <!-- 转盘抽奖 -->
          <div class="relative mb-8">
            <div class="aspect-square max-w-md mx-auto relative">
              <!-- 转盘背景 -->
              <div
                ref="wheelRef"
                class="w-full h-full rounded-full border-8 border-purple-500 relative overflow-hidden transition-transform duration-3000"
                :style="{ transform: `rotate(${rotation}deg)` }"
              >
                <!-- 奖品区域 -->
                <div
                  v-for="(prize, index) in prizes"
                  :key="prize.id"
                  class="absolute"
                  :style="getPrizeStyle(index)"
                >
                  <div class="text-center p-2">
                    <div class="text-xs font-semibold">{{ prize.name }}</div>
                  </div>
                </div>
              </div>

              <!-- 中心按钮 -->
              <div class="absolute inset-0 flex items-center justify-center">
                <button
                  :disabled="!userStats.can_draw || drawing"
                  @click="handleDraw"
                  class="w-24 h-24 rounded-full text-white font-bold text-lg shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex flex-col items-center justify-center"
                  :class="selectedActivity?.points_cost > 0 
                    ? 'bg-gradient-to-br from-orange-500 to-red-500' 
                    : 'bg-gradient-to-br from-purple-500 to-pink-500'"
                >
                  <span>{{ drawing ? '抽奖中...' : '抽奖' }}</span>
                  <span v-if="selectedActivity?.points_cost > 0" class="text-xs mt-1">
                    -{{ selectedActivity.points_cost }}积分
                  </span>
                </button>
              </div>

              <!-- 指针 -->
              <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-2">
                <div class="w-0 h-0 border-l-[20px] border-r-[20px] border-t-[30px] border-l-transparent border-r-transparent border-t-purple-600"></div>
              </div>
            </div>
          </div>

          <!-- 奖品列表 -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div
              v-for="prize in prizes"
              :key="prize.id"
              class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 text-center border-2 transition-all"
              :class="prize.is_available ? 'border-transparent hover:border-purple-300' : 'border-red-300 opacity-60'"
            >
              <div v-if="prize.image_url" class="w-16 h-16 mx-auto mb-2">
                <img :src="prize.image_url" :alt="prize.name" class="w-full h-full object-cover rounded" />
              </div>
              <div v-else class="w-16 h-16 mx-auto mb-2 bg-gradient-to-br from-purple-200 to-pink-200 rounded-lg flex items-center justify-center">
                <span class="text-2xl">
                  <span v-if="prize.prize_type === 'points'">⭐</span>
                  <span v-else-if="prize.prize_type === 'coupon'">🎫</span>
                  <span v-else-if="prize.prize_type === 'dish'">🍲</span>
                  <span v-else>🎁</span>
                </span>
              </div>
              <div class="text-sm font-semibold mb-1">{{ prize.name }}</div>
              <div class="text-xs text-gray-600 mb-1">
                <span v-if="prize.prize_type === 'points'">{{ prize.prize_value }}积分</span>
                <span v-else-if="prize.prize_type === 'coupon'">优惠券</span>
                <span v-else-if="prize.prize_type === 'dish'">菜品兑换</span>
              </div>
              <!-- 剩余库存 -->
              <div v-if="prize.stock > 0 || prize.daily_stock > 0" class="text-xs text-gray-500 mb-1">
                <div v-if="prize.stock > 0">
                  剩余：{{ prize.remaining_stock }}/{{ prize.stock }}
                </div>
                <div v-if="prize.daily_stock > 0" class="mt-0.5">
                  今日：{{ prize.remaining_daily_stock }}/{{ prize.daily_stock }}
                </div>
              </div>
              <!-- 实时概率 -->
              <div 
                class="text-xs font-semibold"
                :class="prize.is_available ? 'text-purple-600' : 'text-gray-400'"
              >
                实时概率: {{ (prize.real_time_probability / 100).toFixed(2) }}%
              </div>
              <!-- 库存已用完提示 -->
              <el-tag v-if="!prize.is_available" type="danger" size="small" class="mt-1">库存已用完</el-tag>
            </div>
          </div>

          <!-- 中奖结果弹窗 -->
          <el-dialog
            v-model="showResultDialog"
            :title="drawResult?.is_winner ? '恭喜中奖！' : '很遗憾'"
            width="400px"
            :close-on-click-modal="false"
          >
            <div class="text-center py-6">
              <div v-if="drawResult?.is_winner && drawResult.prize" class="mb-4">
                <div class="mb-4">
                  <div v-if="drawResult.prize.image_url" class="w-32 h-32 mx-auto mb-4">
                    <img :src="drawResult.prize.image_url" :alt="drawResult.prize.name" class="w-full h-full object-cover rounded-lg" />
                  </div>
                  <div v-else class="w-32 h-32 mx-auto mb-4 bg-gradient-to-br from-purple-200 to-pink-200 rounded-lg flex items-center justify-center">
                    <span class="text-5xl">
                      <span v-if="drawResult.prize.prize_type === 'points'">⭐</span>
                      <span v-else-if="drawResult.prize.prize_type === 'coupon'">🎫</span>
                      <span v-else-if="drawResult.prize.prize_type === 'dish'">🍲</span>
                      <span v-else>🎁</span>
                    </span>
                  </div>
                </div>
                <h3 class="text-2xl font-bold text-purple-600 mb-2">{{ drawResult.prize.name }}</h3>
                <p v-if="drawResult.prize.description" class="text-gray-600 mb-2">{{ drawResult.prize.description }}</p>
                <div class="text-sm text-gray-500">
                  <div v-if="drawResult.prize.prize_type === 'points'">
                    恭喜获得 <span class="text-orange-600 font-bold">{{ drawResult.prize.prize_value }}积分</span>，已发放到您的账户
                  </div>
                  <div v-else-if="drawResult.prize.prize_type === 'coupon'">
                    恭喜获得优惠券，已发放到您的账户，可在"我的优惠券"中查看
                  </div>
                  <div v-else-if="drawResult.prize.prize_type === 'dish'">
                    恭喜获得菜品兑换券，可在订单结算时使用
                  </div>
                </div>
              </div>
              <div v-else class="mb-4">
                <el-icon class="text-6xl text-gray-400"><CircleClose /></el-icon>
                <p class="text-gray-600 mt-4">很遗憾，未中奖</p>
                <p class="text-sm text-gray-500 mt-2">再接再厉！</p>
              </div>
            </div>
            <template #footer>
              <el-button type="primary" @click="showResultDialog = false">确定</el-button>
              <el-button v-if="userStats.can_draw" @click="handleDrawAgain">再抽一次</el-button>
            </template>
          </el-dialog>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import { ArrowLeft, Loading, CircleClose } from '@element-plus/icons-vue';
import { lotteryApi, type LotteryActivity, type DrawResult } from '../../api/lottery';
import { frontendPointsApi, type FrontendMemberPoint } from '../../api/frontend-points';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const activities = ref<LotteryActivity[]>([]);
const selectedActivity = ref<LotteryActivity | null>(null);
const prizes = ref<any[]>([]);
const userStats = ref({
  today_draw_count: 0,
  total_draw_count: 0,
  can_draw: false,
});

const drawing = ref(false);
const rotation = ref(0);
const wheelRef = ref<HTMLElement | null>(null);
const showResultDialog = ref(false);
const drawResult = ref<DrawResult | null>(null);
const userPoints = ref<FrontendMemberPoint | null>(null);

const getPrizeStyle = (index: number) => {
  const total = prizes.value.length;
  const angle = (360 / total) * index;
  const sliceAngle = 360 / total;
  
  return {
    transform: `rotate(${angle}deg)`,
    transformOrigin: 'center center',
    width: '50%',
    height: '50%',
    left: '50%',
    top: '0%',
    clipPath: `polygon(50% 50%, ${50 + 50 * Math.cos((sliceAngle * Math.PI) / 180)}% ${50 + 50 * Math.sin((sliceAngle * Math.PI) / 180)}%, 50% 0%)`,
  };
};

const selectActivity = async (activityId: number) => {
  loading.value = true;
  try {
      const response = await lotteryApi.getActivity(activityId);
      if (response.code === 200 && response.data) {
        selectedActivity.value = response.data.activity;
        prizes.value = response.data.activity.prizes || [];
        userStats.value = response.data.user_stats;
        
        // 使用API返回的积分信息，如果没有则加载
        if (response.data.user_points) {
          userPoints.value = {
            total_points: response.data.user_points.total_points,
            available_points: response.data.user_points.available_points,
            frozen_points: 0,
            level: 'bronze',
            level_text: '青铜会员',
            points_to_next_level: 0,
            expiring_points: [],
            total_expiring: 0,
          };
        } else {
          await loadUserPoints();
        }
      } else {
      ElMessage.error(response.message || '获取活动详情失败');
    }
  } catch (error: any) {
    console.error('获取活动详情失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取活动详情失败');
  } finally {
    loading.value = false;
  }
};

const loadUserPoints = async () => {
  try {
    const response = await frontendPointsApi.getPoints();
    if (response.code === 200 && response.data) {
      userPoints.value = response.data;
    }
  } catch (error) {
    console.error('获取积分信息失败:', error);
  }
};

const handleDraw = async () => {
  if (!selectedActivity.value || !userStats.value.can_draw || drawing.value) {
    return;
  }

  drawing.value = true;
  try {
    const response = await lotteryApi.draw(selectedActivity.value.id);
    if (response.code === 200 && response.data) {
      // 计算转盘旋转角度
      if (response.data.is_winner && response.data.prize) {
        const prizeIndex = prizes.value.findIndex(p => p.id === response.data.prize?.id);
        if (prizeIndex !== -1) {
          const baseRotation = 360 * 5; // 转5圈
          const prizeAngle = (360 / prizes.value.length) * prizeIndex;
          rotation.value = baseRotation + (360 - prizeAngle);
        }
      } else {
        // 未中奖，随机角度
        rotation.value = 360 * 5 + Math.random() * 360;
      }

      // 等待转盘动画完成
      setTimeout(() => {
        drawResult.value = response.data;
        showResultDialog.value = true;
        drawing.value = false;
        
        // 刷新活动信息
        if (selectedActivity.value) {
          selectActivity(selectedActivity.value.id);
        }
      }, 3000);
    } else {
      ElMessage.error(response.message || '抽奖失败');
      drawing.value = false;
    }
  } catch (error: any) {
    console.error('抽奖失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '抽奖失败');
    drawing.value = false;
  }
};

const handleDrawAgain = () => {
  showResultDialog.value = false;
  rotation.value = 0;
  setTimeout(() => {
    handleDraw();
  }, 100);
};

const fetchActivities = async () => {
  loading.value = true;
  try {
    const response = await lotteryApi.getActivities();
    if (response.code === 200 && response.data) {
      activities.value = response.data.activities || [];
      
      // 如果URL中有activity_id参数，自动选择该活动
      const activityId = route.query.activity_id;
      if (activityId) {
        selectActivity(Number(activityId));
      }
    } else {
      ElMessage.error(response.message || '获取活动列表失败');
    }
  } catch (error: any) {
    console.error('获取活动列表失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取活动列表失败');
  } finally {
    loading.value = false;
  }
};

const goBack = () => {
  router.back();
};

onMounted(() => {
  fetchActivities();
  loadUserPoints();
});
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

