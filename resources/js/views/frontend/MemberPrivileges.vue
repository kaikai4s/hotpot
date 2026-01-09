<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">👑 会员权益中心</h1>
          <p class="text-xl text-gray-600">专属特权，尊享体验</p>
        </div>

        <!-- 权益概览卡片 -->
        <div class="bg-gradient-to-r from-purple-500 via-pink-500 to-red-500 rounded-2xl shadow-xl p-8 mb-8 text-white">
          <div class="text-center">
            <p class="text-lg mb-2 opacity-90">当前会员等级</p>
            <p class="text-4xl font-bold mb-4">{{ privileges.level_name || '普通会员' }}</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">积分倍数</p>
                <p class="text-2xl font-bold">{{ privileges.points_multiplier }}x</p>
              </div>
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">会员日倒计时</p>
                <p class="text-2xl font-bold">{{ memberDayCountdown }}</p>
              </div>
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">已节省金额</p>
                <p class="text-2xl font-bold">¥{{ stats.total_saved_amount }}</p>
              </div>
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">额外积分</p>
                <p class="text-2xl font-bold">+{{ stats.total_bonus_points }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- 生日特权 -->
          <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
              <span class="text-3xl mr-3">🎂</span>
              生日特权
            </h2>
            
            <!-- 生日设置 -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
              <div class="flex items-center justify-between mb-3">
                <span class="text-gray-700 font-medium">我的生日</span>
                <span v-if="!birthdayInfo.can_modify" class="text-xs text-orange-600">
                  今年已修改，明年可再次修改
                </span>
              </div>
              <div class="flex items-center gap-3">
                <el-date-picker
                  v-model="birthdayDate"
                  type="date"
                  placeholder="选择生日"
                  :disabled="!birthdayInfo.can_modify && birthdayInfo.birthday"
                  :disabled-date="disabledDate"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  class="flex-1"
                />
                <el-button
                  type="primary"
                  :disabled="!birthdayInfo.can_modify && birthdayInfo.birthday"
                  @click="saveBirthday"
                >
                  {{ birthdayInfo.birthday ? '修改' : '设置' }}
                </el-button>
              </div>
            </div>

            <!-- 生日特权列表 -->
            <div class="space-y-4">
              <div class="flex items-center p-3 border rounded-lg" :class="birthdayPrivileges.has_coupon_this_year ? 'border-green-300 bg-green-50' : 'border-gray-200'">
                <span class="text-2xl mr-3">🎁</span>
                <div class="flex-1">
                  <p class="font-semibold">生日专属优惠券</p>
                  <p class="text-sm text-gray-600">价值 ¥{{ birthdayPrivileges.coupon_amount }} 元</p>
                </div>
                <span v-if="birthdayPrivileges.has_coupon_this_year" class="text-green-600 text-sm">已领取</span>
                <span v-else class="text-gray-500 text-sm">生日当天自动发放</span>
              </div>

              <div class="flex items-center p-3 border rounded-lg" :class="birthdayPrivileges.has_dessert_this_year ? 'border-green-300 bg-green-50' : 'border-gray-200'">
                <span class="text-2xl mr-3">🍰</span>
                <div class="flex-1">
                  <p class="font-semibold">免费生日甜品</p>
                  <p class="text-sm text-gray-600">生日当天消费自动获得</p>
                </div>
                <span v-if="birthdayPrivileges.has_dessert_this_year" class="text-green-600 text-sm">已领取</span>
                <span v-else class="text-gray-500 text-sm">生日消费时发放</span>
              </div>

              <div class="flex items-center p-3 border rounded-lg" :class="birthdayPrivileges.is_birthday_today ? 'border-yellow-300 bg-yellow-50' : 'border-gray-200'">
                <span class="text-2xl mr-3">✨</span>
                <div class="flex-1">
                  <p class="font-semibold">生日双倍积分</p>
                  <p class="text-sm text-gray-600">生日当天消费积分翻倍</p>
                </div>
                <span v-if="birthdayPrivileges.is_birthday_today" class="text-yellow-600 text-sm font-bold">今日生效</span>
                <span v-else class="text-gray-500 text-sm">生日当天生效</span>
              </div>
            </div>
          </div>

          <!-- 会员日特权 -->
          <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
              <span class="text-3xl mr-3">🎉</span>
              会员日特权
            </h2>

            <!-- 会员日信息 -->
            <div class="mb-6 p-4 bg-gradient-to-r from-orange-100 to-yellow-100 rounded-lg">
              <div class="text-center">
                <p class="text-gray-700 mb-2">每月 {{ memberDay.day_of_month }} 号是会员日</p>
                <p v-if="memberDay.is_member_day_today" class="text-2xl font-bold text-orange-600">
                  🎊 今天是会员日！
                </p>
                <p v-else class="text-lg text-gray-600">
                  距离下次会员日还有 <span class="font-bold text-orange-600">{{ memberDay.days_until_member_day }}</span> 天
                </p>
                <p class="text-sm text-gray-500 mt-2">下次会员日：{{ memberDay.next_member_day }}</p>
              </div>
            </div>

            <!-- 会员日特权列表 -->
            <div class="space-y-4">
              <div class="flex items-center p-3 border rounded-lg" :class="memberDay.is_member_day_today ? 'border-orange-300 bg-orange-50' : 'border-gray-200'">
                <span class="text-2xl mr-3">💰</span>
                <div class="flex-1">
                  <p class="font-semibold">会员日专属折扣</p>
                  <p class="text-sm text-gray-600">全场消费享 {{ (memberDay.discount * 100).toFixed(0) }}% 折扣</p>
                </div>
                <span v-if="memberDay.is_member_day_today" class="text-orange-600 text-sm font-bold">今日生效</span>
              </div>

              <div class="flex items-center p-3 border rounded-lg" :class="memberDay.is_member_day_today ? 'border-orange-300 bg-orange-50' : 'border-gray-200'">
                <span class="text-2xl mr-3">⭐</span>
                <div class="flex-1">
                  <p class="font-semibold">积分加成</p>
                  <p class="text-sm text-gray-600">消费积分额外 +{{ (memberDay.points_bonus_rate * 100).toFixed(0) }}%</p>
                </div>
                <span v-if="memberDay.is_member_day_today" class="text-orange-600 text-sm font-bold">今日生效</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 快捷入口 -->
        <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            to="/frontend/mall"
            class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition-shadow"
          >
            <span class="text-4xl block mb-3">🛒</span>
            <p class="font-semibold text-gray-900">积分商城</p>
            <p class="text-sm text-gray-500">积分换好礼</p>
          </router-link>

          <router-link
            to="/frontend/points"
            class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition-shadow"
          >
            <span class="text-4xl block mb-3">💎</span>
            <p class="font-semibold text-gray-900">我的积分</p>
            <p class="text-sm text-gray-500">查看积分明细</p>
          </router-link>

          <router-link
            to="/frontend/coupons"
            class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition-shadow"
          >
            <span class="text-4xl block mb-3">🎫</span>
            <p class="font-semibold text-gray-900">我的优惠券</p>
            <p class="text-sm text-gray-500">查看可用券</p>
          </router-link>

          <router-link
            to="/frontend/redemptions"
            class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition-shadow"
          >
            <span class="text-4xl block mb-3">📦</span>
            <p class="font-semibold text-gray-900">兑换记录</p>
            <p class="text-sm text-gray-500">查看兑换状态</p>
          </router-link>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import {
  memberPrivilegeApi,
  type PrivilegeOverview,
  type BirthdayInfo,
  type BirthdayPrivileges,
  type MemberDayInfo,
  type PrivilegeStats,
} from '../../api/member-privilege';

const router = useRouter();

const loading = ref(false);
const birthdayDate = ref<string>('');

const privileges = ref<PrivilegeOverview>({
  level: '',
  level_name: '',
  level_privileges: [],
  next_level_privileges: null,
  birthday_info: null,
  member_day_info: {
    is_enabled: false,
    is_member_day_today: false,
    day_of_month: 8,
    next_member_day: '',
    days_until_member_day: 0,
    discount: 0.88,
    points_bonus_rate: 0.5,
  },
  points_multiplier: 1,
});

const birthdayInfo = ref<BirthdayInfo>({
  birthday: null,
  can_modify: true,
  last_modified_year: null,
  is_birthday_today: false,
});

const birthdayPrivileges = ref<BirthdayPrivileges>({
  is_birthday_today: false,
  is_in_birthday_period: false,
  has_coupon_this_year: false,
  has_dessert_this_year: false,
  coupon_amount: 20,
  available_dessert_voucher: null,
  points_multiplier: 1,
});

const memberDay = ref<MemberDayInfo>({
  is_enabled: true,
  is_member_day_today: false,
  day_of_month: 8,
  next_member_day: '',
  days_until_member_day: 0,
  discount: 0.88,
  points_bonus_rate: 0.5,
});

const stats = ref<PrivilegeStats>({
  total_saved_amount: 0,
  total_bonus_points: 0,
  birthday_coupons_used: 0,
  member_day_orders: 0,
});

const memberDayCountdown = computed(() => {
  if (memberDay.value.is_member_day_today) {
    return '今天！';
  }
  return `${memberDay.value.days_until_member_day}天`;
});

const disabledDate = (date: Date) => {
  return date > new Date();
};

const fetchPrivileges = async () => {
  try {
    const response = await memberPrivilegeApi.getPrivileges();
    if (response.code === 200 && response.data) {
      privileges.value = response.data;
    }
  } catch (error: any) {
    console.error('获取权益信息失败:', error);
  }
};

const fetchBirthdayInfo = async () => {
  try {
    const response = await memberPrivilegeApi.getBirthday();
    if (response.code === 200 && response.data) {
      birthdayInfo.value = response.data;
      if (response.data.birthday) {
        birthdayDate.value = response.data.birthday;
      }
    }
  } catch (error: any) {
    console.error('获取生日信息失败:', error);
  }
};

const fetchBirthdayPrivileges = async () => {
  try {
    const response = await memberPrivilegeApi.getBirthdayPrivileges();
    if (response.code === 200 && response.data) {
      birthdayPrivileges.value = response.data;
    }
  } catch (error: any) {
    console.error('获取生日特权失败:', error);
  }
};

const fetchMemberDay = async () => {
  try {
    const response = await memberPrivilegeApi.getMemberDay();
    if (response.code === 200 && response.data) {
      memberDay.value = response.data;
    }
  } catch (error: any) {
    console.error('获取会员日信息失败:', error);
  }
};

const fetchStats = async () => {
  try {
    const response = await memberPrivilegeApi.getPrivilegeStats();
    if (response.code === 200 && response.data) {
      stats.value = response.data;
    }
  } catch (error: any) {
    console.error('获取权益统计失败:', error);
  }
};

const saveBirthday = async () => {
  if (!birthdayDate.value) {
    ElMessage.warning('请选择生日日期');
    return;
  }

  try {
    const response = await memberPrivilegeApi.setBirthday(birthdayDate.value);
    if (response.code === 200) {
      ElMessage.success('生日设置成功');
      await fetchBirthdayInfo();
      await fetchBirthdayPrivileges();
    }
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || '设置失败');
  }
};

onMounted(async () => {
  const token = localStorage.getItem('token');
  if (!token) {
    router.push({
      path: '/frontend/login',
      query: { redirect: '/frontend/privileges' },
    });
    return;
  }

  loading.value = true;
  try {
    await Promise.all([
      fetchPrivileges(),
      fetchBirthdayInfo(),
      fetchBirthdayPrivileges(),
      fetchMemberDay(),
      fetchStats(),
    ]);
  } finally {
    loading.value = false;
  }
});
</script>
