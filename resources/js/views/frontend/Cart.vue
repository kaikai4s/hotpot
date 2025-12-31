/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">🛒 购物车</h1>
          <p class="text-xl text-gray-600">确认您的订单</p>
        </div>

        <!-- 购物车内容 -->
        <div v-if="cartStore.items.length > 0" class="bg-white rounded-2xl shadow-xl p-6 mb-6">
          <!-- 购物车列表 -->
          <div class="space-y-4 mb-6">
            <div
              v-for="item in cartStore.items"
              :key="item.dish.id"
              class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
            >
              <!-- 菜品图片 -->
              <div class="w-24 h-24 bg-gradient-to-br from-red-200 via-orange-200 to-yellow-200 rounded-lg flex items-center justify-center flex-shrink-0">
                <span class="text-4xl">🍲</span>
              </div>
              
              <!-- 菜品信息 -->
              <div class="flex-1">
                <h3 class="text-xl font-bold text-gray-900 mb-1">{{ item.dish.name }}</h3>
                <p class="text-gray-600 text-sm mb-2">{{ item.dish.description || '暂无描述' }}</p>
                <div class="flex items-center gap-4">
                  <span class="text-2xl font-bold text-red-600">¥{{ item.dish.price }}</span>
                  <span class="text-gray-500">x {{ item.quantity }}</span>
                  <span class="text-lg font-semibold text-gray-900">
                    小计: ¥{{ (item.dish.price * item.quantity).toFixed(2) }}
                  </span>
                </div>
              </div>

              <!-- 操作按钮 -->
              <div class="flex items-center gap-2">
                <el-button
                  :icon="Minus"
                  circle
                  size="small"
                  @click="decreaseQuantity(item.dish.id)"
                />
                <span class="w-12 text-center font-semibold">{{ item.quantity }}</span>
                <el-button
                  :icon="Plus"
                  circle
                  size="small"
                  @click="increaseQuantity(item.dish.id)"
                />
                <el-button
                  type="danger"
                  :icon="Delete"
                  circle
                  size="small"
                  @click="removeItem(item.dish.id)"
                />
              </div>
            </div>
          </div>

          <!-- 购物车汇总 -->
          <div class="border-t border-gray-200 pt-6">
            <div class="flex justify-between items-center mb-4">
              <span class="text-lg text-gray-600">商品总数：</span>
              <span class="text-xl font-bold text-gray-900">{{ cartStore.totalQuantity }} 件</span>
            </div>
            <div class="flex justify-between items-center mb-6">
              <span class="text-lg text-gray-600">合计金额：</span>
              <span class="text-3xl font-bold text-red-600">¥{{ cartStore.totalAmount.toFixed(2) }}</span>
            </div>
            <div class="flex gap-4">
              <el-button size="large" @click="clearCart">清空购物车</el-button>
              <el-button
                type="primary"
                size="large"
                class="flex-1"
                @click="checkout"
              >
                去结算
              </el-button>
            </div>
          </div>
        </div>

        <!-- 空购物车 -->
        <div v-else class="bg-white rounded-2xl shadow-xl p-12 text-center">
          <span class="text-8xl mb-6 block">🛒</span>
          <h2 class="text-3xl font-bold text-gray-900 mb-4">购物车是空的</h2>
          <p class="text-gray-600 mb-8">快去挑选您喜欢的菜品吧！</p>
          <el-button type="primary" size="large" @click="goToDishes">
            去选购
          </el-button>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Minus, Delete } from '@element-plus/icons-vue';
import { useRouter } from 'vue-router';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { useCartStore } from '../../stores/cart';
import { orderApi } from '../../api/order';

const router = useRouter();
const cartStore = useCartStore();

const increaseQuantity = (dishId: number) => {
  const item = cartStore.items.find(item => item.dish.id === dishId);
  if (item) {
    cartStore.updateQuantity(dishId, item.quantity + 1);
  }
};

const decreaseQuantity = (dishId: number) => {
  const item = cartStore.items.find(item => item.dish.id === dishId);
  if (item && item.quantity > 1) {
    cartStore.updateQuantity(dishId, item.quantity - 1);
  }
};

const removeItem = async (dishId: number) => {
  try {
    await ElMessageBox.confirm('确定要移除这个商品吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });
    cartStore.removeItem(dishId);
    ElMessage.success('已移除');
  } catch {
    // 用户取消
  }
};

const clearCart = async () => {
  try {
    await ElMessageBox.confirm('确定要清空购物车吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });
    cartStore.clearCart();
    ElMessage.success('购物车已清空');
  } catch {
    // 用户取消
  }
};

const checkout = async () => {
  if (cartStore.items.length === 0) {
    ElMessage.warning('购物车是空的');
    return;
  }

  try {
    // 构建订单项
    const items = cartStore.items.map(item => {
      if (item.type === 'combo' && item.combo) {
        return {
          type: 'combo',
          combo_id: item.combo.id,
          quantity: item.quantity,
        };
      } else if (item.type === 'dish' && item.dish) {
        return {
          type: 'dish',
          dish_id: item.dish.id,
          quantity: item.quantity,
        };
      }
      return null;
    }).filter(Boolean);

    // 创建订单
    const response = await orderApi.create({ items });

    if (response.code === 200 && response.data) {
      const order = response.data;
      // 清空购物车
      cartStore.clearCart();
      // 跳转到结算页面
      router.push(`/frontend/checkout/${order.id}`);
    } else {
      ElMessage.error(response.message || '创建订单失败');
    }
  } catch (error: any) {
    console.error('创建订单失败:', error);
    // apiClient 的响应拦截器已经处理了响应，error.response?.data 可能包含错误信息
    // 优先使用 error.message（响应拦截器已设置），其次使用 error.response?.data?.message
    const errorMessage = error.message || error.response?.data?.message || '创建订单失败，请稍后重试';
    ElMessage.error(errorMessage);
    
    // 如果是401错误（未登录），响应拦截器会处理跳转，这里不需要额外处理
  }
};

const goToDishes = () => {
  router.push('/frontend/dishes');
};
</script>

<style scoped>
</style>

