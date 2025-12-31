<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">⭐ 会员积分</h1>
          <p class="text-xl text-gray-600">消费赚积分，积分换好礼</p>
        </div>

        <!-- 积分总览卡片 -->
        <div class="bg-gradient-to-r from-yellow-400 via-orange-400 to-red-400 rounded-2xl shadow-xl p-8 mb-8 text-white">
        <div class="text-center">
          <p class="text-lg mb-2 opacity-90">当前积分</p>
          <p class="text-6xl font-bold mb-4">{{ points.total_points.toLocaleString() }}</p>
          <div class="grid grid-cols-3 gap-4 mt-6">
            <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
              <p class="text-sm opacity-90 mb-1">可用积分</p>
              <p class="text-2xl font-bold">{{ points.available_points.toLocaleString() }}</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
              <p class="text-sm opacity-90 mb-1">冻结积分</p>
              <p class="text-2xl font-bold">{{ points.frozen_points.toLocaleString() }}</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
              <p class="text-sm opacity-90 mb-1">会员等级</p>
              <div class="flex items-center justify-center gap-2">
                <img
                  v-if="points.level_info?.icon"
                  :src="points.level_info.icon"
                  :alt="levelDisplay"
                  class="w-6 h-6 object-contain"
                  loading="lazy"
                  @error="(e) => { (e.target as HTMLImageElement).style.display = 'none'; }"
                />
                <div
                  v-if="points.level_info?.color && !points.level_info?.icon"
                  class="w-3 h-3 rounded-full"
                  :style="{ backgroundColor: points.level_info.color }"
                ></div>
                <p
                  class="text-xl font-bold"
                  :style="points.level_info?.color ? { color: points.level_info.color } : { color: '#FFFFFF' }"
                >{{ levelDisplay }}</p>
              </div>
              <p v-if="points.next_level_info" class="text-xs opacity-75 mt-1">
                距离{{ points.next_level_info.name }}还需{{ points.points_to_next_level }}积分
              </p>
            </div>
          </div>
          <!-- 即将过期积分提醒 -->
          <div v-if="points.total_expiring > 0" class="mt-4 bg-red-500 bg-opacity-30 rounded-lg p-3 backdrop-blur-sm">
            <p class="text-sm text-white">
              ⚠️ 您有 <span class="font-bold">{{ points.total_expiring.toLocaleString() }}</span> 积分即将在30天内过期，请及时使用
            </p>
          </div>
        </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- 左侧：积分规则和兑换 -->
          <div class="lg:col-span-2 space-y-6">
          <!-- 积分明细 -->
          <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">积分明细</h2>
            <div v-loading="loading" class="space-y-4">
              <div
                v-for="transaction in pointTransactions"
                :key="transaction.id"
                class="flex justify-between items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-all"
              >
                <div class="flex-1">
                  <p class="font-semibold text-gray-900">{{ transaction.description || getTransactionTypeText(transaction.type) }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <p class="text-sm text-gray-500">{{ formatDateTime(transaction.created_at) }}</p>
                    <span v-if="transaction.expire_at" class="text-xs text-orange-600">
                      ({{ getDaysUntilExpire(transaction.expire_at) }}天后过期)
                    </span>
                  </div>
                </div>
                <div class="text-right ml-4">
                  <span
                    class="text-xl font-bold block"
                    :class="transaction.points > 0 ? 'text-green-600' : 'text-red-600'"
                  >
                    {{ transaction.points > 0 ? '+' : '' }}{{ transaction.points.toLocaleString() }}
                  </span>
                  <p class="text-xs text-gray-500 mt-1">余额: {{ transaction.balance_after.toLocaleString() }}</p>
                </div>
              </div>
              <div v-if="pointTransactions.length === 0 && !loading" class="text-center py-8 text-gray-500">
                暂无积分记录
              </div>
            </div>
          </div>

          <!-- 积分规则 -->
          <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">积分规则</h2>
            <div class="space-y-4">
              <div class="flex items-start">
                <span class="text-2xl mr-3">💰</span>
                <div>
                  <h3 class="font-semibold text-gray-900 mb-1">消费获得积分</h3>
                  <p class="text-gray-600">每消费1元获得1积分</p>
                </div>
              </div>
              <div class="flex items-start">
                <span class="text-2xl mr-3">💬</span>
                <div>
                  <h3 class="font-semibold text-gray-900 mb-1">评价获得积分</h3>
                  <p class="text-gray-600">完成订单评价可获得50积分</p>
                </div>
              </div>
              <div class="flex items-start">
                <span class="text-2xl mr-3">🎁</span>
                <div>
                  <h3 class="font-semibold text-gray-900 mb-1">积分兑换</h3>
                  <p class="text-gray-600">100积分可兑换1元优惠券</p>
                </div>
              </div>
              <div class="flex items-start">
                <span class="text-2xl mr-3">⭐</span>
                <div>
                  <h3 class="font-semibold text-gray-900 mb-1">会员等级</h3>
                  <p class="text-gray-600">积分累计达到一定数量可升级会员等级，享受更多优惠</p>
                </div>
              </div>
            </div>
          </div>
          </div>

          <!-- 右侧：积分兑换 -->
          <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">积分兑换</h2>
            <div class="space-y-4">
              <div
                v-for="coupon in availableCoupons"
                :key="coupon.id"
                class="border-2 border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-all cursor-pointer"
                @click="redeemCoupon(coupon)"
              >
                <div class="flex justify-between items-start mb-2">
                  <div class="flex-1">
                    <h3 class="font-bold text-gray-900">{{ coupon.name }}</h3>
                    <p class="text-sm text-gray-600 mt-1">
                      {{ getCouponDescription(coupon) }}
                    </p>
                    <p v-if="coupon.valid_to" class="text-xs text-gray-500 mt-1">
                      有效期至：{{ formatDate(coupon.valid_to) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                      库存：{{ coupon.stock }} 张
                    </p>
                  </div>
                  <span class="text-purple-600 font-bold ml-2">-{{ coupon.points_required.toLocaleString() }}积分</span>
                </div>
                <div class="mt-3">
                  <el-button
                    type="primary"
                    size="small"
                    :disabled="points.available_points < coupon.points_required || coupon.stock <= 0"
                    class="w-full"
                  >
                    {{ coupon.stock <= 0 ? '已售罄' : points.available_points >= coupon.points_required ? '立即兑换' : '积分不足' }}
                  </el-button>
                </div>
              </div>
              <div v-if="availableCoupons.length === 0" class="text-center py-8 text-gray-500 text-sm">
                暂无可兑换优惠券
              </div>
            </div>
          </div>
          </div>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { userAuthApi } from '../../api/auth';
import { frontendPointsApi, frontendCouponApi, type FrontendMemberPoint, type FrontendPointTransaction, type FrontendCoupon } from '../../api/frontend-points';

const router = useRouter();

const loading = ref(false);
const points = ref<FrontendMemberPoint>({
  total_points: 0,
  available_points: 0,
  frozen_points: 0,
  level: '',
  level_text: '',
  level_info: null,
  next_level_info: null,
  points_to_next_level: 0,
  expiring_points: [],
  total_expiring: 0,
});

const pointTransactions = ref<FrontendPointTransaction[]>([]);
const availableCoupons = ref<FrontendCoupon[]>([]);

const levelDisplay = computed(() => {
  // 优先使用后台返回的段位名称
  if (points.value.level_info?.name) {
    return points.value.level_info.name;
  }
  // 其次使用 level_text
  if (points.value.level_text) {
    return points.value.level_text;
  }
  // 最后使用 level 代码
  return points.value.level || '未知';
});

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const formatDate = (date: string) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('zh-CN');
};

const getCouponDescription = (coupon: FrontendCoupon): string => {
  if (coupon.type === 'discount' && coupon.value) {
    return `满${coupon.value}元可用`;
  } else if (coupon.type === 'cash' && coupon.value) {
    return `价值${coupon.value}元`;
  } else if (coupon.type === 'points') {
    return '积分优惠券';
  }
  return '优惠券';
};

const fetchPoints = async () => {
  try {
    const response = await frontendPointsApi.getPoints();
    console.log('积分API响应:', response);
    if (response.code === 200 && response.data) {
      points.value = response.data;
      console.log('积分数据已更新:', points.value);
    } else {
      console.warn('积分API返回异常:', response);
      ElMessage.warning('获取积分信息异常');
    }
  } catch (error: any) {
    console.error('获取积分信息失败:', error);
    console.error('错误详情:', error.response?.data || error.message);
    if (error.response?.status === 401) {
      router.push({
        path: '/frontend/login',
        query: { redirect: '/frontend/points' },
      });
    } else {
      ElMessage.error('获取积分信息失败: ' + (error.response?.data?.message || error.message || '未知错误'));
    }
  }
};

const fetchPointTransactions = async () => {
  try {
    const response = await frontendPointsApi.getTransactions({ per_page: 50 });
    console.log('积分明细API响应:', response);
    if (response.code === 200 && response.data) {
      pointTransactions.value = response.data.transactions;
      console.log('积分明细数据已更新，共', pointTransactions.value.length, '条');
    } else {
      console.warn('积分明细API返回异常:', response);
    }
  } catch (error: any) {
    console.error('获取积分明细失败:', error);
    console.error('错误详情:', error.response?.data || error.message);
    ElMessage.error('获取积分明细失败: ' + (error.response?.data?.message || error.message || '未知错误'));
  }
};

const fetchAvailableCoupons = async () => {
  try {
    const response = await frontendCouponApi.getAvailableCoupons();
    console.log('优惠券API响应:', response);
    if (response.code === 200 && response.data) {
      availableCoupons.value = response.data.coupons;
      console.log('优惠券数据已更新，共', availableCoupons.value.length, '张');
    } else {
      console.warn('优惠券API返回异常:', response);
    }
  } catch (error: any) {
    console.error('获取可兑换优惠券失败:', error);
    console.error('错误详情:', error.response?.data || error.message);
    ElMessage.error('获取可兑换优惠券失败: ' + (error.response?.data?.message || error.message || '未知错误'));
  }
};

const getTransactionTypeText = (type: string) => {
  const map: Record<string, string> = {
    earn: '获得积分',
    redeem: '兑换优惠券',
    expire: '积分过期',
    adjust: '积分调整',
  };
  return map[type] || type;
};

const getDaysUntilExpire = (expireAt: string | null): number => {
  if (!expireAt) return 0;
  const expireDate = new Date(expireAt);
  const now = new Date();
  const diffTime = expireDate.getTime() - now.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays > 0 ? diffDays : 0;
};

const redeemCoupon = async (coupon: FrontendCoupon) => {
  if (points.value.available_points < coupon.points_required) {
    ElMessage.warning('积分不足，无法兑换');
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确认使用 ${coupon.points_required.toLocaleString()} 积分兑换 ${coupon.name} 吗？`,
      '确认兑换',
      {
        confirmButtonText: '确认',
        cancelButtonText: '取消',
        type: 'info',
      }
    );

    // 生成幂等性键
    const idempotencyKey = `redeem_${coupon.id}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    
    const response = await frontendPointsApi.redeemCoupon({
      reward_id: coupon.id,
      idempotency_key: idempotencyKey,
    });

    if (response.code === 200) {
      ElMessage.success('兑换成功！');
      // 刷新数据
      await Promise.all([
        fetchPoints(),
        fetchPointTransactions(),
        fetchAvailableCoupons(),
      ]);
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('兑换失败:', error);
      const errorMessage = error.response?.data?.message || '兑换失败，请重试';
      ElMessage.error(errorMessage);
    }
  }
};

onMounted(async () => {
  // 检查登录状态
  const token = localStorage.getItem('token');
  if (!token) {
    console.warn('未登录，跳转到登录页');
    router.push({
      path: '/frontend/login',
      query: { redirect: '/frontend/points' },
    });
    return;
  }

  console.log('开始加载积分数据，token:', token.substring(0, 20) + '...');

  // 已登录，加载数据
  loading.value = true;
  try {
    await Promise.all([
      fetchPoints(),
      fetchPointTransactions(),
      fetchAvailableCoupons(),
    ]);
    console.log('所有数据加载完成');
  } catch (error) {
    console.error('加载数据时发生错误:', error);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
/* 样式 */
</style>


