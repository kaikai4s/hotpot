/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
          <h1 class="text-4xl font-bold text-gray-900 mb-2">所有评价</h1>
          <p class="text-gray-600">查看所有用户的评价和建议</p>
        </div>

        <!-- 筛选栏 -->
        <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
          <div class="flex flex-wrap gap-4">
            <el-select
              v-model="filters.tracking_status"
              placeholder="筛选追踪状态"
              clearable
              class="w-48"
              @change="handleFilter"
            >
              <el-option label="全部" value="" />
              <el-option label="待处理" value="pending" />
              <el-option label="进行中" value="in_progress" />
              <el-option label="已完成" value="completed" />
            </el-select>
            <el-switch
              v-model="showAdoptedOnly"
              active-text="仅显示已采纳"
              @change="handleFilter"
            />
            <el-button type="primary" @click="handleFilter">搜索</el-button>
            <el-button @click="resetFilter">重置</el-button>
            <el-button type="success" @click="goToTrackingReviews">
              查看追踪优化的评价
            </el-button>
          </div>
        </div>

        <!-- 评价列表 -->
        <div v-if="loading" class="text-center py-20">
          <el-icon class="is-loading text-4xl text-red-600"><Loading /></el-icon>
          <p class="mt-4 text-gray-600">加载中...</p>
        </div>

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
                <el-tag v-if="review.is_adopted" type="success" effect="dark">已采纳</el-tag>
                <el-tag v-if="review.tracking_status !== 'pending'" :type="getTrackingStatusType(review.tracking_status)">
                  {{ getTrackingStatusText(review.tracking_status) }}
                </el-tag>
              </div>
            </div>

            <!-- 评价内容 -->
            <div class="mb-4">
              <p class="text-gray-700 mb-2">{{ review.content || '暂无评价内容' }}</p>
              <div v-if="review.dish" class="text-sm text-gray-500 mb-2">
                评价菜品：{{ review.dish.name }}
              </div>
              <el-button 
                type="primary" 
                size="small" 
                link
                @click="viewReviewDetail(review.id)"
              >
                查看详情
              </el-button>
            </div>

            <!-- 管理员回复 -->
            <div v-if="review.admin_reply" class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded">
              <div class="flex items-start gap-2">
                <el-icon class="text-blue-500 mt-1"><ChatDotRound /></el-icon>
                <div class="flex-1">
                  <p class="font-semibold text-blue-900 mb-1">管理员回复：</p>
                  <p class="text-blue-800">{{ review.admin_reply }}</p>
                  <p v-if="review.admin_replied_at" class="text-xs text-blue-600 mt-1">
                    {{ formatDateTime(review.admin_replied_at) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- 追踪更新 -->
            <div v-if="review.tracking_updates && review.tracking_updates.length > 0" class="mt-4">
              <p class="font-semibold text-gray-900 mb-2">追踪更新：</p>
              <div class="space-y-2">
                <div
                  v-for="(update, index) in review.tracking_updates"
                  :key="index"
                  class="bg-gray-50 p-3 rounded-lg text-sm"
                >
                  <p class="text-gray-700">{{ update.message }}</p>
                  <p class="text-xs text-gray-500 mt-1">{{ formatDateTime(update.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-20 text-gray-500">
          <p class="mb-4">暂无评价数据</p>
        </div>

        <!-- 分页 -->
        <el-pagination
          v-if="pagination && pagination.total_count > 0"
          v-model:current-page="currentPage"
          :page-size="pagination.page_size"
          :total="pagination.total_count"
          layout="total, prev, pager, next"
          @current-change="handlePageChange"
          class="mt-6 justify-center"
        />
      </div>
    </div>

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

    <!-- 图片预览对话框 -->
    <el-dialog
      v-model="showImagePreview"
      width="80%"
      :show-close="true"
      align-center
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import { Loading, ChatDotRound } from '@element-plus/icons-vue';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { reviewApi, type Review, type ReviewFilters } from '../../api/review';

const showImagePreview = ref(false);
const previewImageUrl = ref('');

const previewImage = (imageUrl: string) => {
  previewImageUrl.value = imageUrl;
  showImagePreview.value = true;
};

const router = useRouter();

const loading = ref(false);
const reviews = ref<Review[]>([]);
const pagination = ref<{
  current_page: number;
  total_pages: number;
  total_count: number;
  page_size: number;
} | null>(null);

const filters = ref<ReviewFilters>({
  tracking_status: '',
  is_adopted: undefined,
});

const showAdoptedOnly = ref(false);
const currentPage = ref(1);

const getTrackingStatusType = (status: string) => {
  const types: Record<string, string> = {
    in_progress: 'warning',
    completed: 'success',
    cancelled: 'info',
  };
  return types[status] || '';
};

const getTrackingStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: '待处理',
    in_progress: '优化中',
    completed: '已完成',
    cancelled: '已取消',
  };
  return texts[status] || status;
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const handleFilter = () => {
  currentPage.value = 1;
  fetchReviews();
};

const resetFilter = () => {
  filters.value = {
    tracking_status: '',
    is_adopted: undefined,
  };
  showAdoptedOnly.value = false;
  currentPage.value = 1;
  fetchReviews();
};

const handlePageChange = (page: number) => {
  currentPage.value = page;
  fetchReviews();
};

const fetchReviews = async () => {
  loading.value = true;
  try {
    const params: ReviewFilters = {
      page: currentPage.value,
      page_size: pagination.value?.page_size || 20,
    };

    if (filters.value.tracking_status) {
      params.tracking_status = filters.value.tracking_status;
    }
    if (showAdoptedOnly.value) {
      params.is_adopted = true;
    }

    const response = await reviewApi.getList(params);

    if (response.code === 200 && response.data) {
      reviews.value = response.data.reviews || [];
      pagination.value = response.data.pagination || null;
    } else {
      ElMessage.error(response.message || '获取评价列表失败');
      reviews.value = [];
      pagination.value = null;
    }
  } catch (error: any) {
    console.error('获取评价列表失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取评价列表失败');
    reviews.value = [];
    pagination.value = null;
  } finally {
    loading.value = false;
  }
};

const goToTrackingReviews = () => {
  router.push('/frontend/reviews/tracking');
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

onMounted(() => {
  fetchReviews();
});
</script>

<style scoped>
/* 可以添加一些自定义样式 */
</style>

