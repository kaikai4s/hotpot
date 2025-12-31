<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">🎁 优惠活动</h1>
          <p class="text-xl text-gray-600">限时优惠，不容错过</p>
        </div>

        <!-- 活动分类标签 -->
        <div class="flex justify-center mb-8">
          <div class="flex space-x-2 bg-white rounded-full p-2 shadow-md">
          <button
            v-for="category in categories"
            :key="category.value"
            @click="activeCategory = category.value"
            class="px-6 py-2 rounded-full transition-all"
            :class="activeCategory === category.value
              ? 'bg-gradient-to-r from-yellow-400 to-orange-400 text-white font-semibold'
              : 'text-gray-700 hover:bg-gray-100'"
          >
            {{ category.label }}
          </button>
          </div>
        </div>

        <!-- 优惠券列表 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="coupon in filteredCoupons"
            :key="coupon.id"
            class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl"
            :class="coupon.status === 'used' ? 'opacity-60' : ''"
          >
          <!-- 优惠券头部 -->
          <div
            class="h-32 flex items-center justify-center text-white relative overflow-hidden"
            :class="getCouponGradient(coupon.type)"
          >
            <div class="absolute inset-0 opacity-20">
              <div class="absolute top-0 left-0 w-32 h-32 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
              <div class="absolute bottom-0 right-0 w-32 h-32 bg-white rounded-full translate-x-1/2 translate-y-1/2"></div>
            </div>
            <div class="text-center relative z-10">
              <p class="text-sm opacity-90 mb-1">{{ coupon.name }}</p>
              <p class="text-4xl font-bold">{{ coupon.discount }}</p>
              <p class="text-sm opacity-90 mt-1">{{ coupon.description }}</p>
            </div>
          </div>

          <!-- 优惠券详情 -->
          <div class="p-6">
            <div class="space-y-3 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <span class="mr-2">📅</span>
                <span>有效期至：{{ formatDate(coupon.expires_at) }}</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <span class="mr-2">💰</span>
                <span>满{{ coupon.min_amount }}元可用</span>
              </div>
              <div v-if="coupon.points_required" class="flex items-center text-sm text-gray-600">
                <span class="mr-2">⭐</span>
                <span>需要{{ coupon.points_required }}积分</span>
              </div>
            </div>

            <!-- 状态标签 -->
            <div class="mb-4">
              <el-tag
                :type="getStatusTagType(coupon.status)"
                size="small"
              >
                {{ getStatusText(coupon.status) }}
              </el-tag>
            </div>

            <!-- 操作按钮 -->
            <div>
              <el-button
                v-if="coupon.status === 'available'"
                type="primary"
                class="w-full"
                @click="claimCoupon(coupon)"
                :loading="claimingCouponId === coupon.id"
              >
                立即领取
              </el-button>
              <el-button
                v-else-if="coupon.status === 'claimed'"
                type="success"
                class="w-full"
                disabled
              >
                已领取
              </el-button>
              <el-button
                v-else-if="coupon.status === 'used'"
                type="info"
                class="w-full"
                disabled
              >
                已使用
              </el-button>
              <el-button
                v-else-if="coupon.status === 'expired'"
                type="info"
                class="w-full"
                disabled
              >
                已过期
              </el-button>
            </div>
          </div>
          </div>
        </div>

        <!-- 空状态 -->
        <div v-if="filteredCoupons.length === 0" class="text-center py-16">
          <div class="text-6xl mb-4">🎁</div>
          <p class="text-xl text-gray-600">暂无优惠活动</p>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import apiClient from '../../api/client';

const router = useRouter();

const activeCategory = ref('all');
const claimingCouponId = ref<number | null>(null);

const categories = [
  { label: '全部', value: 'all' },
  { label: '新用户', value: 'new_user' },
  { label: '满减', value: 'discount' },
  { label: '积分兑换', value: 'points' },
];

const coupons = ref<any[]>([]);

const filteredCoupons = computed(() => {
  if (activeCategory.value === 'all') {
    return coupons.value;
  }
  return coupons.value.filter(c => c.category === activeCategory.value);
});

const getCouponGradient = (type: string) => {
  const gradients: Record<string, string> = {
    new_user: 'bg-gradient-to-r from-purple-500 to-pink-500',
    discount: 'bg-gradient-to-r from-yellow-400 to-orange-500',
    points: 'bg-gradient-to-r from-blue-400 to-purple-500',
    default: 'bg-gradient-to-r from-red-500 to-orange-500',
  };
  return gradients[type] || gradients.default;
};

const getStatusTagType = (status: string) => {
  const types: Record<string, string> = {
    available: 'success',
    claimed: 'info',
    used: '',
    expired: 'info',
  };
  return types[status] || '';
};

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    available: '可领取',
    claimed: '已领取',
    used: '已使用',
    expired: '已过期',
  };
  return texts[status] || status;
};

const formatDate = (date: string) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('zh-CN');
};

const fetchCoupons = async () => {
  try {
    const response = await apiClient.get('/v1/coupons/all');
    console.log('优惠券API响应:', response);
    if (response.code === 200 && response.data) {
      coupons.value = response.data.coupons || [];
      console.log('优惠券数据已更新,共', coupons.value.length, '张');
    } else {
      console.warn('优惠券API返回异常:', response);
      coupons.value = [];
    }
  } catch (error: any) {
    console.error('获取优惠券列表失败:', error);
    ElMessage.error('获取优惠券列表失败: ' + (error.response?.data?.message || error.message || '未知错误'));
    coupons.value = [];
  }
};

const claimCoupon = async (coupon: any) => {
  if (coupon.points_required && coupon.points_required > 0) {
    // 需要积分兑换
    try {
      await ElMessageBox.confirm(
        `确认使用 ${coupon.points_required} 积分兑换此优惠券吗？`,
        '确认兑换',
        {
          confirmButtonText: '确认',
          cancelButtonText: '取消',
          type: 'info',
        }
      );

      claimingCouponId.value = coupon.id;
      const response = await apiClient.post('/v1/coupons/claim', { coupon_id: coupon.id });
      
      if (response.code === 200) {
        coupon.status = 'claimed';
        ElMessage.success('兑换成功！');
        // 刷新列表
        await fetchCoupons();
      } else {
        ElMessage.error(response.message || '兑换失败');
      }
    } catch (error: any) {
      if (error !== 'cancel') {
        console.error('兑换失败:', error);
        ElMessage.error(error.response?.data?.message || '兑换失败，请重试');
      }
    } finally {
      claimingCouponId.value = null;
    }
  } else {
    // 直接领取
    try {
      claimingCouponId.value = coupon.id;
      const response = await apiClient.post('/v1/coupons/claim', { coupon_id: coupon.id });
      
      if (response.code === 200) {
        coupon.status = 'claimed';
        ElMessage.success('领取成功！');
        // 刷新列表
        await fetchCoupons();
      } else {
        ElMessage.error(response.message || '领取失败');
      }
    } catch (error: any) {
      console.error('领取失败:', error);
      ElMessage.error(error.response?.data?.message || '领取失败，请重试');
    } finally {
      claimingCouponId.value = null;
    }
  }
};

onMounted(async () => {
  // 检查登录状态
  const token = localStorage.getItem('token');
  if (!token) {
    router.push({
      path: '/frontend/login',
      query: { redirect: '/frontend/coupons' },
    });
    return;
  }

  // 已登录，加载数据
  await fetchCoupons();
});
</script>

<style scoped>
/* 样式 */
</style>


