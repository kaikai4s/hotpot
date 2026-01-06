/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <FrontendLayout>
    <!-- 轮播图 -->
    <BannerCarousel v-if="banners.length > 0" :banners="banners" :autoplay="true" :interval="5000" />
    <!-- 默认轮播图（当没有配置时显示） -->
    <section v-else class="relative h-96 overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-r from-red-600 via-orange-500 to-yellow-500">
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="text-center text-white">
            <h1 class="text-5xl font-bold mb-4 animate-fade-in">🔥 精选火锅套餐</h1>
            <p class="text-xl mb-8">限时优惠，立即预约享受美味</p>
            <router-link to="/frontend/reservation" class="bg-white text-red-600 px-8 py-3 rounded-full text-lg font-semibold hover:bg-gray-100 transition-all transform hover:scale-110 shadow-xl inline-block">
              立即预约
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- 快捷功能 -->
    <section class="py-12 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <router-link to="/frontend/reservation" class="text-center group cursor-pointer transform transition-all hover:scale-110 block">
            <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:shadow-xl">
              <span class="text-3xl">📅</span>
            </div>
            <h3 class="font-semibold text-gray-800">在线预约</h3>
            <p class="text-sm text-gray-600 mt-1">轻松预约桌位</p>
          </router-link>
          <router-link to="/frontend/queue" class="text-center group cursor-pointer transform transition-all hover:scale-110 block">
            <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:shadow-xl">
              <span class="text-3xl">🎫</span>
            </div>
            <h3 class="font-semibold text-gray-800">排队叫号</h3>
            <p class="text-sm text-gray-600 mt-1">实时查看排队</p>
          </router-link>
          <router-link to="/frontend/points" class="text-center group cursor-pointer transform transition-all hover:scale-110 block">
            <div class="w-20 h-20 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:shadow-xl">
              <div class="text-3xl">⭐</div>
            </div>
            <h3 class="font-semibold text-gray-800">会员积分</h3>
            <p class="text-sm text-gray-600 mt-1">消费赚积分</p>
          </router-link>
          <router-link to="/frontend/lottery" class="text-center group cursor-pointer transform transition-all hover:scale-110 block">
            <div class="w-20 h-20 bg-gradient-to-br from-pink-400 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:shadow-xl">
              <span class="text-3xl">🎁</span>
            </div>
            <h3 class="font-semibold text-gray-800">幸运抽奖</h3>
            <p class="text-sm text-gray-600 mt-1">赢取优惠券</p>
          </router-link>
          <router-link to="/frontend/coupons" class="text-center group cursor-pointer transform transition-all hover:scale-110 block">
            <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg group-hover:shadow-xl">
              <span class="text-3xl">🎁</span>
            </div>
            <h3 class="font-semibold text-gray-800">优惠活动</h3>
            <p class="text-sm text-gray-600 mt-1">限时优惠</p>
          </router-link>
        </div>
      </div>
    </section>

    <!-- 推荐菜品 -->
    <section id="dishes" class="py-16 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">🔥 推荐菜品</h2>
          <p class="text-gray-600">精选美味，不容错过</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            v-for="dish in dishes"
            :key="dish.id"
            class="bg-white rounded-xl shadow-md overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl cursor-pointer"
            @click="viewDish(dish)"
          >
            <div class="h-48 bg-gradient-to-br from-red-200 via-orange-200 to-yellow-200 flex items-center justify-center relative overflow-hidden cursor-pointer" @click.stop="previewImage(dish.image_url)">
              <img
                v-if="dish.image_url"
                :src="getImageUrl(dish.image_url)"
                :alt="dish.name"
                class="w-full h-full object-cover"
                @error="handleImageError"
              />
              <span v-else class="text-6xl">🍲</span>
              <div v-if="dish.status === 'sold_out'" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <span class="text-white font-bold text-xl">已售罄</span>
              </div>
            </div>
            <div class="p-5">
              <h3 class="text-xl font-bold text-gray-900 mb-2">{{ dish.name }}</h3>
              <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ dish.description || '暂无描述' }}</p>
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                  <el-rate :model-value="Number(dish.average_rating) || 0" disabled size="small" />
                  <span class="text-xs text-gray-500 ml-2">({{ dish.review_count }})</span>
                </div>
                <span class="text-2xl font-bold text-red-600">¥{{ dish.price }}</span>
              </div>
              <router-link to="/frontend/dishes" class="w-full bg-gradient-to-r from-red-500 to-orange-500 text-white py-2 rounded-lg hover:from-red-600 hover:to-orange-600 transition-all transform hover:scale-105 block text-center">
                立即下单
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 用户评价 -->
    <section id="reviews" class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">💬 用户评价</h2>
          <p class="text-gray-600">真实评价，值得信赖</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div
            v-for="review in reviews"
            :key="review.id"
            class="bg-gray-50 rounded-xl p-6 shadow-md hover:shadow-lg transition-all"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center mr-3">
                <span class="text-white font-bold">{{ review.user?.nickname?.charAt(0) || 'U' }}</span>
              </div>
              <div>
                <h4 class="font-semibold text-gray-900">{{ review.user?.nickname || '匿名用户' }}</h4>
                <el-rate v-model="review.rating" disabled size="small" />
              </div>
            </div>
            <p class="text-gray-700 mb-2">{{ review.content || '很好！' }}</p>
            <p class="text-sm text-gray-500">{{ formatDate(review.created_at) }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 底部 -->
    <footer class="bg-gray-900 text-white py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div>
            <h3 class="text-xl font-bold mb-4">关于我们</h3>
            <p class="text-gray-400">专业火锅店，提供优质服务</p>
          </div>
          <div>
            <h3 class="text-xl font-bold mb-4">联系方式</h3>
            <p class="text-gray-400">电话：400-123-4567</p>
            <p class="text-gray-400">地址：XX市XX区XX路XX号</p>
          </div>
          <div>
            <h3 class="text-xl font-bold mb-4">营业时间</h3>
            <p class="text-gray-400">周一至周日</p>
            <p class="text-gray-400">11:00 - 22:00</p>
          </div>
          <div>
            <h3 class="text-xl font-bold mb-4">关注我们</h3>
            <div class="flex space-x-4">
              <span class="text-2xl">📱</span>
              <span class="text-2xl">💬</span>
            </div>
          </div>
        </div>
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
          <p>&copy; 2026 火锅店. All rights reserved.</p>
        </div>
      </div>
    </footer>

    <!-- 图片预览对话框 -->
    <el-dialog
      v-model="showImagePreview"
      width="80%"
      :show-close="true"
      align-center
      class="image-preview-dialog"
    >
      <div class="flex justify-center items-center">
        <img
          :src="previewImageUrl"
          alt="菜品图片预览"
          class="max-w-full max-h-[80vh] object-contain"
          @error="handleImageError"
        />
      </div>
    </el-dialog>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import BannerCarousel from '../../components/frontend/BannerCarousel.vue';
import { bannerApi } from '../../api/banner';
import { dishApi, type Dish } from '../../api/dish';
import { reviewApi } from '../../api/review';
import type { Review } from '../../types';
import type { Banner } from '../../api/banner';

const dishes = ref<Dish[]>([]);
const reviews = ref<Review[]>([]);
const banners = ref<Banner[]>([]);

const formatDate = (date: string) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('zh-CN');
};

const viewDish = (dish: Dish) => {
  ElMessage.info(`查看菜品：${dish.name}`);
};

// 图片预览
const showImagePreview = ref(false);
const previewImageUrl = ref('');

const previewImage = (imageUrl: string | null | undefined) => {
  if (!imageUrl) {
    ElMessage.warning('该菜品暂无图片');
    return;
  }
  previewImageUrl.value = getImageUrl(imageUrl);
  showImagePreview.value = true;
};

// 处理图片URL，添加时间戳防止缓存
const getImageUrl = (url: string | null | undefined): string => {
  if (!url) return '';
  // 如果URL已经包含查询参数，添加&，否则添加?
  const separator = url.includes('?') ? '&' : '?';
  // 添加时间戳防止缓存，但只使用日期部分，这样同一天内的更新会被缓存
  const timestamp = new Date().toISOString().split('T')[0].replace(/-/g, '');
  return `${url}${separator}_t=${timestamp}`;
};

// 图片加载错误处理
const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement;
  // 如果图片加载失败，隐藏图片，显示默认占位符
  img.style.display = 'none';
};

const fetchDishes = async () => {
  try {
    const response = await dishApi.getList({ per_page: 8, sort: 'sales_desc' });
    if (response.code === 200 && response.data) {
      dishes.value = response.data.dishes || [];
    }
  } catch (error: any) {
    console.error('获取菜品列表失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取菜品列表失败');
  }
};

const fetchReviews = async () => {
  try {
    const response = await reviewApi.getFeaturedReviews();
    if (response && response.code === 200 && response.data) {
      reviews.value = response.data.reviews || [];
    } else {
      // 如果API返回失败，使用空数组，不显示评价
      reviews.value = [];
    }
  } catch (error) {
    console.error('获取首页评价失败:', error);
    // 静默失败，不影响页面显示
    reviews.value = [];
  }
};

const fetchBanners = async () => {
  try {
    const response = await bannerApi.getList();
    if (response && response.code === 200 && response.data) {
      banners.value = response.data.banners || [];
    }
  } catch (error) {
    console.error('获取轮播图失败:', error);
    // 静默失败，不影响页面显示
  }
};

onMounted(() => {
  fetchBanners();
  fetchDishes();
  fetchReviews();
});
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 1s ease-out;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

:deep(.image-preview-dialog .el-dialog__body) {
  padding: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
