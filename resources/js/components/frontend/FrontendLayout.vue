/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="min-h-screen bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50">
    <!-- 顶部导航栏 -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center">
            <!-- 返回按钮（非首页显示） -->
            <el-button
              v-if="showBackButton"
              text
              @click="handleBack"
              class="mr-4"
            >
              <el-icon><ArrowLeft /></el-icon>
              返回
            </el-button>
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/" class="flex items-center">
                <span class="text-3xl mr-2">🔥</span>
                <span class="text-2xl font-bold text-red-600">火锅店</span>
              </router-link>
            </div>
          </div>
          <div class="hidden md:block">
            <div class="ml-10 flex items-baseline space-x-8">
              <router-link to="/" class="text-gray-900 hover:text-red-600 px-3 py-2 text-sm font-medium transition-colors">首页</router-link>
              <router-link to="/frontend/dishes" class="text-gray-600 hover:text-red-600 px-3 py-2 text-sm font-medium transition-colors">菜品</router-link>
              <router-link to="/frontend/reservation" class="text-gray-600 hover:text-red-600 px-3 py-2 text-sm font-medium transition-colors">预约</router-link>
              <el-dropdown @command="handleReviewCommand" trigger="hover">
                <span class="text-gray-600 hover:text-red-600 px-3 py-2 text-sm font-medium transition-colors cursor-pointer">
                  评价
                  <el-icon class="ml-1"><ArrowDown /></el-icon>
                </span>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="all">所有评价</el-dropdown-item>
                    <el-dropdown-item command="tracking">追踪优化的评价</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
              <router-link to="/frontend/profile" class="text-gray-600 hover:text-red-600 px-3 py-2 text-sm font-medium transition-colors">我的</router-link>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <!-- 购物车图标 -->
            <div
              @click="goToCart"
              class="relative p-2 text-gray-600 hover:text-red-600 transition-colors cursor-pointer z-10"
            >
              <el-icon class="text-2xl">
                <ShoppingCart />
              </el-icon>
              <span
                v-if="cartStore.totalQuantity > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"
              >
                {{ cartStore.totalQuantity > 99 ? '99+' : cartStore.totalQuantity }}
              </span>
            </div>
            <!-- 用户信息下拉菜单 -->
            <el-dropdown v-if="isLoggedIn" @command="handleCommand" trigger="click">
              <div class="flex items-center cursor-pointer hover:bg-gray-50 px-3 py-2 rounded-lg transition-colors">
                <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-orange-500 rounded-full flex items-center justify-center mr-2">
                  <img v-if="userInfo?.avatar_url" :src="userInfo.avatar_url" alt="头像" class="w-full h-full rounded-full object-cover" />
                  <span v-else class="text-white text-sm font-bold">{{ userInfo?.nickname?.charAt(0) || 'U' }}</span>
                </div>
                <span class="text-sm font-medium text-gray-800 hidden md:block">{{ userInfo?.nickname || '用户' }}</span>
                <el-icon class="text-gray-500 ml-2"><ArrowDown /></el-icon>
              </div>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="profile">
                    <el-icon><User /></el-icon>
                    <span class="ml-2">个人中心</span>
                  </el-dropdown-item>
                  <el-dropdown-item divided command="logout">
                    <el-icon><SwitchButton /></el-icon>
                    <span class="ml-2">退出登录</span>
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
            <!-- 未登录时显示登录按钮 -->
            <router-link
              v-else
              to="/frontend/login"
              class="text-gray-600 hover:text-red-600 px-4 py-2 text-sm font-medium transition-colors"
            >
              登录
            </router-link>
            <router-link to="/frontend/reservation" class="bg-red-600 text-white px-6 py-2 rounded-full hover:bg-red-700 transition-all transform hover:scale-105 shadow-lg">
              立即预约
            </router-link>
          </div>
        </div>
      </div>
    </nav>

    <!-- 内容区域 -->
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { ArrowLeft, ArrowDown, User, SwitchButton, ShoppingCart } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { userAuthApi } from '../../api/auth';
import { useCartStore } from '../../stores/cart';
import type { UserInfo } from '../../api/auth';

const router = useRouter();
const route = useRoute();
const cartStore = useCartStore();
const userInfo = ref<UserInfo | null>(null);
const isLoggedIn = computed(() => {
  const token = localStorage.getItem('token');
  return !!token;
});

const showBackButton = computed(() => {
  // 首页不显示返回按钮
  return route.path !== '/' && route.path !== '/frontend/login';
});

const loadUserInfo = async () => {
  if (!isLoggedIn.value) return;
  
  try {
    // 先尝试从缓存加载
    const userInfoStr = localStorage.getItem('user_info');
    if (userInfoStr) {
      try {
        userInfo.value = JSON.parse(userInfoStr);
      } catch (e) {
        console.error('解析user_info失败:', e);
      }
    }
    
    // 从服务器获取最新信息（静默失败）
    try {
      const response = await userAuthApi.me();
      if (response && response.code === 200 && response.data) {
        userInfo.value = response.data;
        localStorage.setItem('user_info', JSON.stringify(response.data));
      }
    } catch (error) {
      // 静默失败，不影响页面显示
      console.error('获取用户信息失败:', error);
    }
  } catch (error) {
    console.error('loadUserInfo异常:', error);
  }
};

const handleBack = () => {
  if (window.history.length > 1) {
    router.back();
  } else {
    router.push('/');
  }
};

const goToCart = () => {
  router.push('/frontend/cart');
};

const handleReviewCommand = (command: string) => {
  if (command === 'all') {
    router.push('/frontend/reviews');
  } else if (command === 'tracking') {
    router.push('/frontend/reviews/tracking');
  }
};

const handleCommand = async (command: string) => {
  if (command === 'profile') {
    router.push('/frontend/profile');
  } else if (command === 'logout') {
    try {
      await ElMessageBox.confirm('确定要退出登录吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      });
      
      try {
        await userAuthApi.logout();
      } catch (error) {
        // 即使退出 API 失败，也清除本地 token
        console.error('退出登录 API 失败:', error);
      }
      
      // 清除前端登录信息
      localStorage.removeItem('token');
      localStorage.removeItem('user_info');
      userInfo.value = null;
      
      ElMessage.success('已退出登录');
      
      // 跳转到首页
      router.push('/');
    } catch (error) {
      // 用户取消
    }
  }
};

onMounted(() => {
  loadUserInfo();
});
</script>

<style scoped>
:deep(.el-dropdown-menu__item) {
  display: flex;
  align-items: center;
}
</style>

