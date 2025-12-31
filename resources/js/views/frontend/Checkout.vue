/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-8">
          <h1 class="text-4xl font-bold text-gray-900 mb-2">💰 结算</h1>
          <p class="text-gray-600">确认订单信息并完成支付</p>
        </div>

        <div v-if="loading" class="text-center py-20">
          <el-icon class="is-loading text-4xl text-red-600"><Loading /></el-icon>
          <p class="mt-4 text-gray-600">加载中...</p>
        </div>

        <div v-else-if="order" class="space-y-6">
          <!-- 订单信息 -->
          <div class="bg-white rounded-2xl shadow-xl p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">订单信息</h2>
            <div class="space-y-3 mb-6">
              <div class="flex justify-between">
                <span class="text-gray-600">订单号：</span>
                <span class="font-semibold text-gray-900">{{ order.order_no }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">订单金额：</span>
                <span class="text-xl font-bold text-gray-900">¥{{ order.total_amount }}</span>
              </div>
              <div v-if="order.deposit_discount && parseFloat(order.deposit_discount) > 0" class="flex justify-between text-green-600">
                <span class="text-gray-600">定金抵扣：</span>
                <span class="font-semibold">-¥{{ order.deposit_discount }}</span>
              </div>
              <div v-if="order.points_discount && parseFloat(order.points_discount) > 0" class="flex justify-between text-green-600">
                <span class="text-gray-600">积分抵扣：</span>
                <span class="font-semibold">-¥{{ order.points_discount }}</span>
              </div>
              <div v-if="order.coupon_discount && parseFloat(order.coupon_discount) > 0" class="flex justify-between text-green-600">
                <span class="text-gray-600">优惠券抵扣：</span>
                <span class="font-semibold">-¥{{ order.coupon_discount }}</span>
              </div>
              <div v-if="order.final_amount" class="flex justify-between border-t border-gray-200 pt-2 mt-2">
                <span class="text-gray-600 font-semibold">最终支付：</span>
                <span class="text-2xl font-bold text-red-600">¥{{ order.final_amount }}</span>
              </div>
              <div v-else class="flex justify-between border-t border-gray-200 pt-2 mt-2">
                <span class="text-gray-600 font-semibold">最终支付：</span>
                <span class="text-2xl font-bold text-red-600">¥{{ order.total_amount }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">订单状态：</span>
                <el-tag :type="getStatusTag(order.status)">{{ getStatusText(order.status) }}</el-tag>
              </div>
            </div>

            <!-- 订单商品列表 -->
            <div class="border-t border-gray-200 pt-4">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">商品清单</h3>
              <div class="space-y-3">
                <div
                  v-for="item in order.items"
                  :key="item.id"
                  class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg"
                >
                  <div class="w-16 h-16 bg-gradient-to-br from-red-200 via-orange-200 to-yellow-200 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-3xl">🍲</span>
                  </div>
                  <div class="flex-1">
                    <h4 class="font-semibold text-gray-900">{{ item.dish?.name }}</h4>
                    <p class="text-sm text-gray-600">¥{{ item.price }} x {{ item.quantity }}</p>
                  </div>
                  <span class="text-lg font-semibold text-gray-900">¥{{ item.subtotal }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 抵扣选项（如果订单未支付） -->
          <div v-if="order.status === 'pending'" class="bg-white rounded-2xl shadow-xl p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">抵扣选项</h2>
            
            <!-- 定金抵扣 -->
            <div v-if="availableReservation && availableReservation.deposit_status === 'paid'" class="mb-4">
              <el-checkbox v-model="useDeposit" @change="calculateFinalAmount">
                <div>
                  <div class="font-semibold text-gray-900">使用预约定金抵扣</div>
                  <div class="text-sm text-gray-600">可用定金：¥{{ availableReservation.deposit_amount }}</div>
                </div>
              </el-checkbox>
            </div>

            <!-- 优惠券选择 -->
            <div class="mb-4">
              <div class="font-semibold text-gray-900 mb-2">使用优惠券</div>
              <el-select
                v-model="selectedCouponId"
                placeholder="请选择优惠券"
                clearable
                style="width: 100%"
                @change="calculateFinalAmount"
                :loading="loadingCoupons"
              >
                <el-option
                  v-for="coupon in availableUserCoupons"
                  :key="coupon.id"
                  :label="getCouponLabel(coupon)"
                  :value="coupon.id"
                />
              </el-select>
              <div v-if="selectedCouponId && selectedCoupon" class="mt-2 text-sm text-gray-600">
                {{ selectedCoupon.coupon?.description || selectedCoupon.coupon?.usage_instructions || '' }}
              </div>
            </div>

            <!-- 积分抵扣 -->
            <div class="mb-4">
              <el-checkbox v-model="usePoints" @change="calculateFinalAmount">
                <div>
                  <div class="font-semibold text-gray-900">使用积分抵扣</div>
                  <div class="text-sm text-gray-600">可用积分：{{ userPoints?.available_points || 0 }}</div>
                </div>
              </el-checkbox>
              <div v-if="usePoints" class="mt-3 ml-6">
                <el-input-number
                  v-model="pointsToUse"
                  :min="0"
                  :max="maxPointsToUse"
                  :step="pointsToMoneyRate"
                  @change="calculateFinalAmount"
                  class="w-full"
                >
                  <template #prefix>积分：</template>
                </el-input-number>
                <p class="text-sm text-gray-500 mt-2">
                  可抵扣金额：¥{{ pointsDiscount.toFixed(2) }}（{{ pointsToMoneyRate }}积分=1元）
                </p>
              </div>
            </div>
          </div>

          <!-- 支付方式选择（如果订单未支付） -->
          <div v-if="order.status === 'pending'" class="bg-white rounded-2xl shadow-xl p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">选择支付方式</h2>
            <div v-loading="paymentMethodsLoading" class="space-y-3">
              <el-radio-group v-model="selectedPaymentMethod" class="w-full">
                <div
                  v-for="method in paymentMethods"
                  :key="method.code"
                  class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all"
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
          </div>

          <!-- 支付按钮 -->
          <div v-if="order.status === 'pending'" class="flex gap-4">
            <el-button size="large" @click="goBack">返回购物车</el-button>
            <el-button
              type="danger"
              size="large"
              @click="cancelOrder"
            >
              取消订单
            </el-button>
            <el-button
              type="primary"
              size="large"
              class="flex-1"
              :loading="paying"
              :disabled="!selectedPaymentMethod"
              @click="handlePay"
            >
              立即支付 ¥{{ finalPaymentAmount }}
            </el-button>
          </div>

          <!-- 支付成功提示（待评价状态） -->
          <div v-else-if="order.status === 'pending_review'" class="bg-green-50 border-2 border-green-200 rounded-2xl p-8 text-center">
            <el-icon class="text-6xl text-green-500 mb-4"><CircleCheckFilled /></el-icon>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">支付成功！</h2>
            <p class="text-gray-600 mb-4">订单号：{{ order.order_no }}</p>
            <p class="text-lg text-orange-600 mb-6">请对本次用餐进行评价，或选择跳过评价</p>
            <div class="flex gap-4 justify-center">
              <el-button @click="viewOrder">查看订单详情</el-button>
              <el-button type="primary" @click="goToReview">
                <el-icon><Edit /></el-icon>
                去评价
              </el-button>
              <el-button type="default" @click="skipReview">跳过评价</el-button>
            </div>
          </div>

          <!-- 支付成功提示（已支付状态，旧数据兼容） -->
          <div v-else-if="order.status === 'paid'" class="bg-green-50 border-2 border-green-200 rounded-2xl p-8 text-center">
            <el-icon class="text-6xl text-green-500 mb-4"><CircleCheckFilled /></el-icon>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">支付成功！</h2>
            <p class="text-gray-600 mb-6">订单号：{{ order.order_no }}</p>
            <div class="flex gap-4 justify-center">
              <el-button @click="viewOrder">查看订单</el-button>
              <el-button type="primary" @click="goToHome">返回首页</el-button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Loading, CircleCheckFilled, Edit } from '@element-plus/icons-vue';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { orderApi, type Order, type PaymentMethod } from '../../api/order';
import { reservationApi, type Reservation } from '../../api/reservation';
import { frontendPointsApi, type FrontendMemberPoint } from '../../api/frontend-points';
import { frontendConfigApi } from '../../api/frontend-config';

const router = useRouter();
const route = useRoute();
const loading = ref(false);
const paying = ref(false);
const order = ref<Order | null>(null);
const paymentMethods = ref<PaymentMethod[]>([]);
const paymentMethodsLoading = ref(false);
const selectedPaymentMethod = ref<string>('');
const useDeposit = ref(false);
const usePoints = ref(false);
const pointsToUse = ref(0);
const userPoints = ref<FrontendMemberPoint | null>(null);
const availableReservation = ref<Reservation | null>(null);
const pointsToMoneyRate = ref(100); // 默认100积分=1元
const loadingCoupons = ref(false);
const selectedCouponId = ref<number | null>(null);
const availableUserCoupons = ref<any[]>([]);
const pointsDiscount = computed(() => {
  if (!usePoints.value || pointsToUse.value <= 0) {
    return 0;
  }
  return pointsToUse.value / pointsToMoneyRate.value;
});

const maxPointsToUse = computed(() => {
  if (!userPoints.value) return 0;
  return userPoints.value.available_points;
});

const selectedCoupon = computed(() => {
  if (!selectedCouponId.value) return null;
  return availableUserCoupons.value.find(c => c.id === selectedCouponId.value) || null;
});

const finalPaymentAmount = computed(() => {
  if (!order.value) return '0.00';
  
  // 使用订单的最终金额（已包含所有折扣）
  return order.value.final_amount ? parseFloat(order.value.final_amount).toFixed(2) : parseFloat(order.value.total_amount || '0').toFixed(2);
});

const getCouponLabel = (userCoupon: any) => {
  const coupon = userCoupon.coupon;
  if (!coupon) return '';
  
  let label = coupon.name;
  if (coupon.type === 'fixed_amount') {
    label += ` (¥${coupon.value})`;
  } else if (coupon.type === 'percentage') {
    label += ` (${coupon.value}%折扣)`;
  } else if (coupon.type === 'dish_exchange' && coupon.dish) {
    label += ` (兑换${coupon.dish.name})`;
  }
  if (coupon.min_amount > 0) {
    label += ` - 满¥${coupon.min_amount}可用`;
  }
  return label;
};

const getStatusText = (status: string) => {
  const map: Record<string, string> = {
    pending: '待支付',
    paid: '已支付',
    pending_review: '待评价',
    completed: '已完成',
    cancelled: '已取消',
  };
  return map[status] || status;
};

const getStatusTag = (status: string) => {
  const map: Record<string, string> = {
    pending: 'warning',
    paid: 'success',
    pending_review: 'warning',
    completed: 'success',
    cancelled: 'info',
  };
  return map[status] || '';
};

const loadOrder = async () => {
  const orderId = route.params.orderId as string;
  if (!orderId) {
    ElMessage.error('订单ID不存在');
    router.push('/frontend/cart');
    return;
  }

  loading.value = true;
  try {
    const response = await orderApi.getDetail(Number(orderId));
    if (response.code === 200 && response.data) {
      order.value = response.data;
      
      // 如果订单有关联预约，加载预约信息
      if (order.value.reservation_id) {
        await loadReservation(order.value.reservation_id);
      } else {
        // 如果没有关联预约，尝试查找用户的可用预约（已确认且已到达）
        await findAvailableReservation();
      }
      
      // 加载用户积分信息
      await loadUserPoints();
      
      // 加载积分抵扣比例配置
      await loadPointsRate();
      
      // 加载可用优惠券
      await loadAvailableCoupons();
      
      // 如果订单已有抵扣信息，同步到UI
      if (order.value.deposit_discount && parseFloat(order.value.deposit_discount) > 0) {
        useDeposit.value = true;
      }
      if (order.value.points_used && order.value.points_used > 0) {
        usePoints.value = true;
        pointsToUse.value = order.value.points_used;
      }
      if (order.value.user_coupon_id) {
        selectedCouponId.value = order.value.user_coupon_id;
      }
      
      if (order.value.status === 'pending') {
        await loadPaymentMethods();
      }
    } else {
      ElMessage.error(response.message || '获取订单失败');
      router.push('/frontend/cart');
    }
  } catch (error: any) {
    console.error('获取订单失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取订单失败');
    router.push('/frontend/cart');
  } finally {
    loading.value = false;
  }
};

const loadReservation = async (reservationId: number) => {
  try {
    const response = await reservationApi.getDetail(reservationId);
    if (response.code === 200 && response.data) {
      availableReservation.value = response.data;
      // 如果预约已确认且已到达，且定金已支付，默认选中定金抵扣
      if (availableReservation.value.status === 'confirmed' 
          && availableReservation.value.arrived_at 
          && availableReservation.value.deposit_status === 'paid') {
        useDeposit.value = true;
      }
    }
  } catch (error: any) {
    console.error('获取预约详情失败:', error);
  }
};

const loadUserPoints = async () => {
  try {
    const response = await frontendPointsApi.getPoints();
    if (response.code === 200 && response.data) {
      userPoints.value = response.data;
    }
  } catch (error: any) {
    console.error('获取积分信息失败:', error);
  }
};

const loadPointsRate = async () => {
  try {
    const response = await frontendConfigApi.getPublicConfig('points_to_money_rate');
    if (response.code === 200 && response.data) {
      pointsToMoneyRate.value = parseInt(response.data.value) || 100;
    }
  } catch (error: any) {
    console.error('获取积分抵扣比例失败:', error);
  }
};

const findAvailableReservation = async () => {
  try {
    // 查找用户已确认且已到达的预约（定金已支付）
    const response = await reservationApi.getList({ status: 'confirmed', page: 1, page_size: 10 });
    if (response.code === 200 && response.data) {
      const available = response.data.reservations.find((r: Reservation) => 
        r.arrived_at && r.deposit_status === 'paid' && !r.order_id
      );
      if (available) {
        availableReservation.value = available;
      }
    }
  } catch (error: any) {
    console.error('查找可用预约失败:', error);
  }
};

const loadAvailableCoupons = async () => {
  if (!order.value) return;
  
  loadingCoupons.value = true;
  try {
    const { couponApi } = await import('../../api/frontend-coupon');
    const response = await couponApi.getUserCoupons({
      min_amount: parseFloat(order.value.total_amount || '0'),
    });
    if (response.code === 200 && response.data) {
      availableUserCoupons.value = response.data.coupons || [];
    }
  } catch (error) {
    console.error('获取可用优惠券失败:', error);
  } finally {
    loadingCoupons.value = false;
  }
};

const calculateFinalAmount = async () => {
  if (!order.value || order.value.status !== 'pending') {
    return;
  }

  // 更新订单以应用抵扣选项
  try {
    const updatePayload: any = {};
    
    if (availableReservation.value) {
      updatePayload.reservation_id = availableReservation.value.id;
      updatePayload.use_deposit = useDeposit.value;
    }
    
    if (usePoints.value && pointsToUse.value > 0) {
      updatePayload.use_points = true;
      updatePayload.points_used = pointsToUse.value;
    } else {
      updatePayload.use_points = false;
      updatePayload.points_used = 0;
    }

    if (selectedCouponId.value) {
      updatePayload.user_coupon_id = selectedCouponId.value;
    } else {
      updatePayload.user_coupon_id = null;
    }

    const response = await orderApi.update(order.value.id, updatePayload);
    if (response.code === 200 && response.data) {
      order.value = response.data;
    }
  } catch (error: any) {
    console.error('更新订单失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '更新订单失败');
  }
};

const loadPaymentMethods = async () => {
  paymentMethodsLoading.value = true;
  try {
    const response = await orderApi.getPaymentMethods();
    if (response.code === 200 && response.data) {
      paymentMethods.value = response.data.methods;
      // 设置默认支付方式
      const defaultMethod = response.data.methods.find(m => m.is_default);
      if (defaultMethod) {
        selectedPaymentMethod.value = defaultMethod.code;
      } else if (paymentMethods.value.length > 0) {
        selectedPaymentMethod.value = paymentMethods.value[0].code;
      }
    }
  } catch (error: any) {
    console.error('获取支付方式失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取支付方式失败');
  } finally {
    paymentMethodsLoading.value = false;
  }
};

const handlePay = async () => {
  if (!order.value || !selectedPaymentMethod.value) {
    ElMessage.warning('请选择支付方式');
    return;
  }

  // 先更新订单以应用抵扣选项
  await calculateFinalAmount();

  paying.value = true;
  try {
    const response = await orderApi.pay(order.value.id, {
      payment_method: selectedPaymentMethod.value as 'wechat' | 'mock',
    });

    if (response.code === 200) {
      ElMessage.success('支付成功！');
      // 重新加载订单信息
      await loadOrder();
    } else {
      ElMessage.error(response.message || '支付失败');
    }
  } catch (error: any) {
    console.error('支付失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '支付失败');
  } finally {
    paying.value = false;
  }
};

const goBack = () => {
  router.push('/frontend/cart');
};

const viewOrder = () => {
  if (order.value) {
    router.push(`/frontend/orders/${order.value.id}`);
  }
};

const cancelOrder = async () => {
  if (!order.value) return;

  try {
    await ElMessageBox.confirm('确认取消此订单吗？', '提示', {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'warning',
    });

    const response = await orderApi.cancel(order.value.id);
    if (response.code === 200) {
      ElMessage.success('订单已取消');
      router.push('/frontend/orders');
    } else {
      ElMessage.error(response.message || '取消订单失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('取消订单失败:', error);
      ElMessage.error(error.message || '取消订单失败');
    }
  }
};

const goToHome = () => {
  router.push('/');
};

const goToReview = () => {
  if (order.value) {
    router.push(`/frontend/review/${order.value.id}`);
  }
};

const skipReview = async () => {
  if (!order.value) return;

  try {
    await ElMessageBox.confirm('确定要跳过评价吗？跳过后将无法再评价此订单。', '提示', {
      confirmButtonText: '确定跳过',
      cancelButtonText: '取消',
      type: 'warning',
    });

    const response = await orderApi.skipReview(order.value.id);
    if (response.code === 200) {
      ElMessage.success('订单已完成');
      await loadOrder();
    } else {
      ElMessage.error(response.message || '操作失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('跳过评价失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '操作失败');
    }
  }
};

onMounted(() => {
  loadOrder();
});
</script>

<style scoped>
</style>

