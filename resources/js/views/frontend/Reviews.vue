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
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-200 to-orange-200 flex items-center justify-center">
                  <span class="text-xl">{{ review.user?.nickname?.charAt(0) || 'U' }}</span>
                </div>
                <div>
                  <p class="font-semibold text-gray-900">{{ review.user?.nickname || '匿名用户' }}</p>
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
              <div v-if="review.dish" class="text-sm text-gray-500">
                评价菜品：{{ review.dish.name }}
              </div>
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
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import { Loading, ChatDotRound } from '@element-plus/icons-vue';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { reviewApi, type Review, type ReviewFilters } from '../../api/review';

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

onMounted(() => {
  fetchReviews();
});
</script>

<style scoped>
/* 可以添加一些自定义样式 */
</style>

