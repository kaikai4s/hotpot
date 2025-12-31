<template>
  <div class="min-h-screen bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50">
    <!-- 模拟微信小程序界面 -->
    <div class="max-w-md mx-auto bg-white shadow-2xl min-h-screen">
      <!-- 小程序顶部栏 -->
      <div class="bg-gradient-to-r from-red-500 to-orange-500 text-white p-4 text-center">
        <h1 class="text-xl font-bold">🔥 火锅店小程序</h1>
        <p class="text-sm opacity-90 mt-1">美味火锅，随时预约</p>
      </div>

      <!-- 轮播图 -->
      <div class="relative h-48 bg-gradient-to-r from-red-400 to-orange-400 overflow-hidden">
        <div class="absolute inset-0 flex items-center justify-center text-white">
          <div class="text-center">
            <div class="text-4xl mb-2">🔥</div>
            <div class="text-2xl font-bold">精选火锅套餐</div>
            <div class="text-sm mt-2">限时优惠，立即预约</div>
          </div>
        </div>
        <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 flex gap-1">
          <div class="w-2 h-2 rounded-full bg-white"></div>
          <div class="w-2 h-2 rounded-full bg-white opacity-50"></div>
          <div class="w-2 h-2 rounded-full bg-white opacity-50"></div>
        </div>
      </div>

      <!-- 快捷功能 -->
      <div class="p-4 bg-white">
        <div class="grid grid-cols-4 gap-4">
          <div class="text-center" @click="navigateTo('reservation')">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2 transform transition-transform hover:scale-110">
              <span class="text-2xl">📅</span>
            </div>
            <p class="text-xs text-gray-600">预约</p>
          </div>
          <div class="text-center" @click="navigateTo('queue')">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2 transform transition-transform hover:scale-110">
              <span class="text-2xl">🎫</span>
            </div>
            <p class="text-xs text-gray-600">排队</p>
          </div>
          <div class="text-center" @click="navigateTo('dishes')">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-2 transform transition-transform hover:scale-110">
              <span class="text-2xl">🍲</span>
            </div>
            <p class="text-xs text-gray-600">菜品</p>
          </div>
          <div class="text-center" @click="navigateTo('points')">
            <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2 transform transition-transform hover:scale-110">
              <span class="text-2xl">⭐</span>
            </div>
            <p class="text-xs text-gray-600">积分</p>
          </div>
        </div>
      </div>

      <!-- 推荐菜品 -->
      <div class="p-4 bg-gray-50">
        <h2 class="text-lg font-bold text-gray-800 mb-4">🔥 推荐菜品</h2>
        <div class="space-y-3">
          <div 
            v-for="dish in dishes" 
            :key="dish.id"
            class="bg-white rounded-lg p-4 shadow-md flex items-center gap-4 transform transition-all hover:scale-105 hover:shadow-lg"
            @click="viewDish(dish)"
          >
            <div class="w-20 h-20 bg-gradient-to-br from-red-200 to-orange-200 rounded-lg flex items-center justify-center">
              <span class="text-3xl">🍲</span>
            </div>
            <div class="flex-1">
              <h3 class="font-bold text-gray-800">{{ dish.name }}</h3>
              <p class="text-xs text-gray-500 mt-1">{{ dish.description }}</p>
              <div class="flex items-center mt-2 gap-2">
                <el-rate v-model="dish.average_rating" disabled size="small" />
                <span class="text-xs text-gray-600">¥{{ dish.price }}</span>
              </div>
            </div>
            <el-button type="primary" size="small" circle>
              <span>+</span>
            </el-button>
          </div>
        </div>
      </div>

      <!-- 底部导航 -->
      <div class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white border-t border-gray-200">
        <div class="grid grid-cols-4 gap-2 p-2">
          <div class="text-center py-2" :class="currentTab === 'home' ? 'text-red-500' : 'text-gray-500'">
            <div class="text-xl mb-1">🏠</div>
            <div class="text-xs">首页</div>
          </div>
          <div class="text-center py-2" :class="currentTab === 'dishes' ? 'text-red-500' : 'text-gray-500'">
            <div class="text-xl mb-1">🍲</div>
            <div class="text-xs">菜品</div>
          </div>
          <div class="text-center py-2" :class="currentTab === 'reservation' ? 'text-red-500' : 'text-gray-500'">
            <div class="text-xl mb-1">📅</div>
            <div class="text-xs">预约</div>
          </div>
          <div class="text-center py-2" :class="currentTab === 'me' ? 'text-red-500' : 'text-gray-500'">
            <div class="text-xl mb-1">👤</div>
            <div class="text-xs">我的</div>
          </div>
        </div>
      </div>

      <!-- 内容区域底部padding，避免被导航栏遮挡 -->
      <div class="pb-20"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import type { Dish } from '../types';

const currentTab = ref('home');
const dishes = ref<Dish[]>([]);

const navigateTo = (page: string) => {
  currentTab.value = page;
  ElMessage.info(`跳转到${page}页面`);
};

const viewDish = (dish: Dish) => {
  ElMessage.success(`查看菜品：${dish.name}`);
};

const fetchDishes = async () => {
  // 模拟数据
  dishes.value = [
    {
      id: 1,
      name: '麻辣锅底',
      description: '经典麻辣口味',
      price: 58.00,
      average_rating: 4.5,
      review_count: 120,
      sales_count: 500,
    },
    {
      id: 2,
      name: '精品肥牛',
      description: '新鲜肥牛片',
      price: 68.00,
      average_rating: 4.8,
      review_count: 89,
      sales_count: 320,
    },
    {
      id: 3,
      name: '鲜虾',
      description: '新鲜大虾',
      price: 88.00,
      average_rating: 4.9,
      review_count: 56,
      sales_count: 180,
    },
  ];
};

onMounted(() => {
  fetchDishes();
});
</script>

<style scoped>
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.space-y-3 > * {
  animation: slideIn 0.3s ease-out;
  animation-fill-mode: both;
}

.space-y-3 > *:nth-child(1) { animation-delay: 0.1s; }
.space-y-3 > *:nth-child(2) { animation-delay: 0.2s; }
.space-y-3 > *:nth-child(3) { animation-delay: 0.3s; }
</style>

