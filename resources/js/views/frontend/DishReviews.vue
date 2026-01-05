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
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-200 to-orange-200 flex items-center justify-center">
                  <span class="text-xl">{{ review.user?.nickname?.charAt(0) || 'U' }}</span>
                </div>
                <div>
                  <p class="font-semibold text-gray-900">
                    <span v-if="review.user?.equipped_title" class="text-yellow-600 font-bold mr-1">[{{ review.user.equipped_title }}]</span>
                    {{ review.user?.nickname || '匿名用户' }}
                    <span v-if="review.user?.level" class="text-purple-600 ml-1 text-sm">[{{ review.user.level.name }}]</span>
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
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import { ArrowLeft, Loading } from '@element-plus/icons-vue';
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

