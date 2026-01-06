/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 返回按钮 -->
        <div class="mb-6">
          <el-button @click="goBack" :icon="ArrowLeft">返回</el-button>
        </div>

        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">💬 菜品评价</h1>
          <p v-if="dish" class="text-xl text-gray-600">{{ dish.name }}</p>
        </div>

        <!-- 评价统计 -->
        <div v-if="summary" class="bg-white rounded-2xl shadow-xl p-6 mb-8">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
              <div class="text-4xl font-bold text-red-600 mb-2">{{ summary.average_rating.toFixed(1) }}</div>
              <div class="flex justify-center mb-2">
                <el-rate :model-value="summary.average_rating" disabled show-score text-color="#ff9900" />
              </div>
              <p class="text-gray-600">平均评分</p>
            </div>
            <div class="text-center">
              <div class="text-4xl font-bold text-gray-900 mb-2">{{ summary.total_reviews }}</div>
              <p class="text-gray-600">总评价数</p>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-900 mb-2">评分分布</div>
              <div class="space-y-1">
                <div v-for="(count, rating) in summary.rating_distribution" :key="rating" class="flex items-center justify-between text-sm">
                  <span>{{ rating }}星</span>
                  <div class="flex-1 mx-2 bg-gray-200 rounded-full h-2">
                    <div
                      class="bg-red-500 h-2 rounded-full"
                      :style="{ width: `${(count / summary.total_reviews) * 100}%` }"
                    ></div>
                  </div>
                  <span class="text-gray-600">{{ count }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 排序和筛选 -->
        <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
          <div class="flex flex-col md:flex-row gap-4">
            <el-select v-model="sortBy" placeholder="排序方式" class="w-48" @change="handleSortChange">
              <el-option label="最新评价" value="latest" />
              <el-option label="评分从高到低" value="rating_desc" />
              <el-option label="评分从低到高" value="rating_asc" />
            </el-select>
          </div>
        </div>

        <!-- 加载状态 -->
        <div v-if="loading" class="text-center py-20">
          <el-icon class="is-loading text-4xl text-red-600"><Loading /></el-icon>
          <p class="mt-4 text-gray-600">加载中...</p>
        </div>

        <!-- 评价列表 -->
        <div v-else-if="reviews.length > 0" class="space-y-4">
          <div
            v-for="review in reviews"
            :key="review.id"
            class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow"
          >
            <!-- 评价头部 -->
            <div class="flex justify-between items-start mb-4">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-200 to-orange-200 flex items-center justify-center overflow-hidden">
                  <img 
                    v-if="review.user?.avatar_url" 
                    :src="review.user.avatar_url" 
                    :alt="review.user?.nickname || '用户'" 
                    class="w-full h-full object-cover"
                  />
                  <span v-else class="text-xl">{{ review.user?.nickname?.charAt(0) || 'U' }}</span>
                </div>
                <div>
                  <p class="font-semibold text-gray-900">
                    <span v-if="review.user?.equipped_title" class="text-yellow-600 font-bold mr-1">[{{ review.user.equipped_title }}]</span>
                    {{ review.user?.nickname || '匿名用户' }}
                    <span
                      v-if="review.user?.level"
                      class="ml-1 text-sm font-bold"
                      :style="review.user.level.color ? { color: review.user.level.color } : { color: '#9333ea' }"
                    >[{{ review.user.level.name }}]</span>
                  </p>
                  <p class="text-sm text-gray-500">{{ formatDateTime(review.created_at) }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <el-rate v-model="review.rating" disabled show-score text-color="#ff9900" />
              </div>
            </div>

            <!-- 评价内容 -->
            <div class="mb-4">
              <p class="text-gray-700 mb-2">{{ review.content || '暂无评价内容' }}</p>
              <el-button 
                type="primary" 
                size="small" 
                link
                @click="viewReviewDetail(review.id)"
              >
                查看详情
              </el-button>
            </div>

            <!-- 评价图片 -->
            <div v-if="review.images && review.images.length > 0" class="mb-4">
              <div class="grid grid-cols-3 gap-2">
                <img
                  v-for="(image, index) in review.images"
                  :key="index"
                  :src="image"
                  :alt="`评价图片${index + 1}`"
                  class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
                  @click="previewImage(image)"
                />
              </div>
            </div>

            <!-- 评价标签 -->
            <div v-if="review.tags && review.tags.length > 0" class="flex flex-wrap gap-2 mb-4">
              <el-tag v-for="tag in review.tags" :key="tag" size="small">{{ tag }}</el-tag>
            </div>

            <!-- 管理员回复 -->
            <div v-if="review.admin_reply" class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
              <div class="flex items-start gap-2">
                <span class="text-blue-600 font-semibold">管理员回复：</span>
                <p class="text-gray-700 flex-1">{{ review.admin_reply }}</p>
              </div>
              <p v-if="review.admin_replied_at" class="text-sm text-gray-500 mt-2">
                {{ formatDateTime(review.admin_replied_at) }}
              </p>
            </div>
          </div>
        </div>

        <!-- 空状态 -->
        <div v-else class="text-center py-20">
          <span class="text-6xl mb-4 block">💬</span>
          <p class="text-xl text-gray-600">暂无评价</p>
        </div>

        <!-- 分页 -->
        <div v-if="pagination && pagination.total_count > 0" class="mt-6 flex justify-center">
          <el-pagination
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :total="pagination.total_count"
            :page-sizes="[10, 20, 50]"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handlePageChange"
            @current-change="handlePageChange"
          />
        </div>
      </div>
    </div>

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
          alt="评价图片预览"
          class="max-w-full max-h-[80vh] object-contain"
        />
      </div>
    </el-dialog>

    <!-- 评价详情对话框 -->
    <el-dialog
      v-model="showDetailDialog"
      title="评价详情"
      width="800px"
      destroy-on-close
    >
      <div v-if="selectedReview" class="space-y-4">
        <!-- 用户信息 -->
        <div class="flex items-center gap-3 pb-4 border-b">
          <div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-200 to-orange-200 flex items-center justify-center overflow-hidden">
            <img 
              v-if="selectedReview.user?.avatar_url" 
              :src="selectedReview.user.avatar_url" 
              :alt="selectedReview.user?.nickname || '用户'" 
              class="w-full h-full object-cover"
            />
            <span v-else class="text-2xl">{{ selectedReview.user?.nickname?.charAt(0) || 'U' }}</span>
          </div>
          <div>
            <p class="font-semibold text-gray-900">
              <span v-if="selectedReview.user?.equipped_title" class="text-yellow-600 font-bold mr-1">[{{ selectedReview.user.equipped_title }}]</span>
              {{ selectedReview.user?.nickname || '匿名用户' }}
              <span
                v-if="selectedReview.user?.level"
                class="ml-1 text-sm font-bold"
                :style="selectedReview.user.level.color ? { color: selectedReview.user.level.color } : { color: '#9333ea' }"
              >[{{ selectedReview.user.level.name }}]</span>
            </p>
            <p class="text-sm text-gray-500">{{ formatDateTime(selectedReview.created_at) }}</p>
          </div>
        </div>

        <!-- 订单菜品信息 -->
        <div v-if="orderDishes.length > 0">
          <h3 class="font-semibold text-gray-900 mb-2">订单菜品：</h3>
          <div class="grid grid-cols-2 gap-2">
            <div
              v-for="dish in orderDishes"
              :key="dish.dish_id"
              class="p-2 rounded"
              :class="dish.is_reviewed ? 'bg-green-50 border border-green-200' : 'bg-gray-50'"
            >
              <div class="flex items-center justify-between">
                <span class="text-sm">{{ dish.dish_name }}</span>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-gray-500">×{{ dish.quantity }}</span>
                  <el-tag v-if="dish.is_reviewed" type="success" size="small">已评价</el-tag>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 评价内容 -->
        <div>
          <h3 class="font-semibold text-gray-900 mb-2">评价内容：</h3>
          <div class="p-3 bg-gray-50 rounded">{{ selectedReview.content || '暂无评价内容' }}</div>
        </div>

        <!-- 评分 -->
        <div>
          <h3 class="font-semibold text-gray-900 mb-2">评分：</h3>
          <el-rate v-model="selectedReview.rating" disabled show-score text-color="#ff9900" />
        </div>

        <!-- 评价图片 -->
        <div v-if="selectedReview.images && selectedReview.images.length > 0">
          <h3 class="font-semibold text-gray-900 mb-2">评价图片：</h3>
          <div class="grid grid-cols-3 gap-2">
            <img
              v-for="(image, index) in selectedReview.images"
              :key="index"
              :src="image"
              :alt="`评价图片${index + 1}`"
              class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
              @click="previewImage(image)"
            />
          </div>
        </div>

        <!-- 标签 -->
        <div v-if="selectedReview.tags && selectedReview.tags.length > 0">
          <h3 class="font-semibold text-gray-900 mb-2">标签：</h3>
          <div class="flex flex-wrap gap-2">
            <el-tag v-for="tag in selectedReview.tags" :key="tag" size="small">{{ tag }}</el-tag>
          </div>
        </div>

        <!-- 管理员回复 -->
        <div v-if="selectedReview.admin_reply" class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
          <div class="flex items-start gap-2">
            <el-icon class="text-blue-500 mt-1"><ChatDotRound /></el-icon>
            <div class="flex-1">
              <p class="font-semibold text-blue-900 mb-1">管理员回复：</p>
              <p class="text-blue-800">{{ selectedReview.admin_reply }}</p>
              <p v-if="selectedReview.admin_replied_at" class="text-xs text-blue-600 mt-1">
                {{ formatDateTime(selectedReview.admin_replied_at) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import { ArrowLeft, Loading, ChatDotRound } from '@element-plus/icons-vue';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { reviewApi, type Review } from '../../api/review';
import { dishApi, type Dish } from '../../api/dish';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const dish = ref<Dish | null>(null);
const reviews = ref<Review[]>([]);
const summary = ref<{
  average_rating: number;
  total_reviews: number;
  rating_distribution: Record<string, number>;
} | null>(null);
const pagination = ref<{
  current_page: number;
  total_pages: number;
  total_count: number;
  page_size: number;
} | null>(null);
const currentPage = ref(1);
const pageSize = ref(20);
const sortBy = ref('latest');

// 图片预览
const showImagePreview = ref(false);
const previewImageUrl = ref('');

const previewImage = (imageUrl: string) => {
  previewImageUrl.value = imageUrl;
  showImagePreview.value = true;
};

// 评价详情对话框
const showDetailDialog = ref(false);
const selectedReview = ref<any>(null);
const orderDishes = ref<Array<{ dish_id: number; dish_name: string; quantity: number; is_reviewed: boolean }>>([]);

const viewReviewDetail = async (reviewId: number) => {
  try {
    const response = await reviewApi.getDetail(reviewId);
    if (response.code === 200 && response.data) {
      selectedReview.value = response.data.review;
      orderDishes.value = response.data.order_dishes || [];
      showDetailDialog.value = true;
    } else {
      ElMessage.error(response.message || '获取评价详情失败');
    }
  } catch (error: any) {
    console.error('获取评价详情失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取评价详情失败');
  }
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const goBack = () => {
  router.back();
};

const handleSortChange = () => {
  currentPage.value = 1;
  fetchReviews();
};

const handlePageChange = () => {
  fetchReviews();
};

const fetchDish = async () => {
  const dishId = Number(route.params.dishId);
  if (isNaN(dishId) || dishId <= 0) {
    ElMessage.error('菜品ID无效');
    router.push('/frontend/dishes');
    return;
  }

  try {
    const response = await dishApi.getDetail(dishId);
    if (response.code === 200 && response.data) {
      dish.value = response.data.dish;
    } else {
      ElMessage.error(response.message || '获取菜品信息失败');
      router.push('/frontend/dishes');
    }
  } catch (error: any) {
    console.error('获取菜品信息失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取菜品信息失败');
    router.push('/frontend/dishes');
  }
};

const fetchReviews = async () => {
  const dishId = Number(route.params.dishId);
  if (isNaN(dishId) || dishId <= 0) {
    return;
  }

  loading.value = true;
  try {
    const response = await reviewApi.getDishReviews(dishId, {
      page: currentPage.value,
      page_size: pageSize.value,
      sort: sortBy.value,
    });

    if (response.code === 200 && response.data) {
      reviews.value = response.data.reviews || [];
      pagination.value = response.data.pagination || null;
      summary.value = response.data.summary || null;
    } else {
      ElMessage.error(response.message || '获取评价列表失败');
      reviews.value = [];
      pagination.value = null;
      summary.value = null;
    }
  } catch (error: any) {
    console.error('获取评价列表失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取评价列表失败');
    reviews.value = [];
    pagination.value = null;
    summary.value = null;
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchDish();
  fetchReviews();
});
</script>

<style scoped>
:deep(.image-preview-dialog .el-dialog__body) {
  padding: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>

