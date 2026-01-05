<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">📅 每日签到</h1>
          <p class="text-xl text-gray-600">连续签到，奖励翻倍</p>
        </div>

        <!-- 签到统计卡片 -->
        <div class="bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 rounded-2xl shadow-xl p-8 mb-8 text-white">
          <div class="text-center">
            <div class="grid grid-cols-3 gap-4 mb-6">
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">累计签到</p>
                <p class="text-3xl font-bold">{{ checkinStat.total_days }}天</p>
              </div>
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">连续签到</p>
                <p class="text-3xl font-bold">{{ checkinStat.current_consecutive_days }}天</p>
              </div>
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">最高连续</p>
                <p class="text-3xl font-bold">{{ checkinStat.max_consecutive_days }}天</p>
              </div>
            </div>
            
            <!-- 签到按钮 -->
            <el-button
              v-if="!checkinStat.is_checked_today"
              type="primary"
              size="large"
              @click="handleCheckin"
              :loading="checkinLoading"
              class="bg-white text-purple-600 hover:bg-gray-100 text-lg px-8 py-6"
            >
              <span class="text-2xl mr-2">✓</span>
              立即签到
            </el-button>
            <div v-else class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
              <p class="text-lg mb-2">今日已签到</p>
              <p class="text-2xl font-bold">+{{ checkinStat.today_reward_points }}积分</p>
            </div>
          </div>
        </div>

        <!-- 签到日历 -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">签到日历</h2>
            <div class="flex items-center gap-4">
              <el-button @click="prevMonth" :icon="ArrowLeft" circle />
              <span class="text-lg font-semibold">{{ currentYear }}年{{ currentMonth }}月</span>
              <el-button @click="nextMonth" :icon="ArrowRight" circle />
            </div>
          </div>
          <div v-loading="calendarLoading" class="grid grid-cols-7 gap-2">
            <div
              v-for="day in ['日', '一', '二', '三', '四', '五', '六']"
              :key="day"
              class="text-center font-semibold text-gray-600 py-2"
            >
              {{ day }}
            </div>
            <div
              v-for="(item, index) in calendar.calendar"
              :key="index"
              class="aspect-square flex flex-col items-center justify-center p-2 rounded-lg border-2 transition-all"
              :class="getDayClass(item)"
            >
              <span class="text-sm font-semibold">{{ item.day }}</span>
              <span v-if="item.is_checked" class="text-xs">✓</span>
              <span v-if="item.reward_points" class="text-xs text-yellow-600">+{{ item.reward_points }}</span>
            </div>
          </div>
        </div>

        <!-- 签到奖励规则 -->
        <div class="bg-white rounded-xl shadow-md p-6">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">奖励规则</h2>
          <div class="space-y-2 text-gray-600">
            <p>• 第1天：10积分</p>
            <p>• 第2-3天：15积分/天</p>
            <p>• 第4-6天：20积分/天</p>
            <p>• 第7天：50积分（连续一周奖励）</p>
            <p>• 第8-13天：25积分/天</p>
            <p>• 第14天：100积分（连续两周奖励）</p>
            <p>• 第15-20天：30积分/天</p>
            <p>• 第21天：200积分（连续三周奖励）</p>
            <p>• 第22-27天：35积分/天</p>
            <p>• 第28天：300积分（连续四周奖励）</p>
            <p>• 第29天及以上：40积分/天</p>
          </div>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import { ArrowLeft, ArrowRight } from '@element-plus/icons-vue';
import FrontendLayout from '@/components/frontend/FrontendLayout.vue';
import { checkinApi, type CheckinStat, type CheckinCalendar } from '@/api/checkin';

const checkinLoading = ref(false);
const calendarLoading = ref(false);
const checkinStat = ref<CheckinStat>({
  total_days: 0,
  current_consecutive_days: 0,
  max_consecutive_days: 0,
  last_checkin_date: null,
  is_checked_today: false,
  today_reward_points: null,
  makeup_count: 0,
});

const currentYear = ref(new Date().getFullYear());
const currentMonth = ref(new Date().getMonth() + 1);
const calendar = ref<CheckinCalendar>({
  year: currentYear.value,
  month: currentMonth.value,
  calendar: [],
  stat: {
    total_days: 0,
    current_consecutive_days: 0,
    max_consecutive_days: 0,
    last_checkin_date: null,
  },
});

const loadCheckinStat = async () => {
  try {
    const response = await checkinApi.getStat();
    if (response.code === 200) {
      checkinStat.value = response.data;
    }
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || '加载失败');
  }
};

const loadCalendar = async () => {
  calendarLoading.value = true;
  try {
    const response = await checkinApi.getCalendar(currentYear.value, currentMonth.value);
    if (response.code === 200) {
      calendar.value = response.data;
    }
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || '加载失败');
  } finally {
    calendarLoading.value = false;
  }
};

const handleCheckin = async () => {
  checkinLoading.value = true;
  try {
    const response = await checkinApi.checkin();
    if (response.code === 200) {
      ElMessage.success(`签到成功！获得${response.data.reward_points}积分`);
      await loadCheckinStat();
      await loadCalendar();
    }
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || '签到失败');
  } finally {
    checkinLoading.value = false;
  }
};

const prevMonth = () => {
  if (currentMonth.value === 1) {
    currentMonth.value = 12;
    currentYear.value--;
  } else {
    currentMonth.value--;
  }
  loadCalendar();
};

const nextMonth = () => {
  if (currentMonth.value === 12) {
    currentMonth.value = 1;
    currentYear.value++;
  } else {
    currentMonth.value++;
  }
  loadCalendar();
};

const getDayClass = (item: any) => {
  if (item.is_today) {
    return 'border-blue-500 bg-blue-50';
  }
  if (item.is_checked) {
    return 'border-green-500 bg-green-50';
  }
  if (item.is_past) {
    return 'border-gray-200 bg-gray-50';
  }
  if (item.is_future) {
    return 'border-gray-100 bg-white';
  }
  return 'border-gray-200 bg-white';
};

onMounted(() => {
  loadCheckinStat();
  loadCalendar();
});
</script>

