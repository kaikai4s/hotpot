/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">评价管理</h1>
          <p class="text-gray-600">审核和管理用户评价，回复差评并追踪优化</p>
        </div>
        <el-button type="primary" size="large" @click="refreshData">
          <el-icon><Refresh /></el-icon>
          刷新
        </el-button>
      </div>

      <!-- 筛选栏 -->
      <div class="flex flex-wrap gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-select 
          v-model="filters.status" 
          placeholder="状态筛选" 
          clearable 
          class="w-48"
          @change="handleFilter"
        >
          <el-option label="待审核" value="pending" />
          <el-option label="已通过" value="approved" />
          <el-option label="已拒绝" value="rejected" />
        </el-select>
        <el-select 
          v-model="filters.tracking_status" 
          placeholder="追踪状态" 
          clearable 
          class="w-48"
          @change="handleFilter"
        >
          <el-option label="待处理" value="pending" />
          <el-option label="进行中" value="in_progress" />
          <el-option label="已完成" value="completed" />
          <el-option label="已取消" value="cancelled" />
        </el-select>
        <el-select 
          v-model="filters.rating" 
          placeholder="评分筛选" 
          clearable 
          class="w-48"
          @change="handleFilter"
        >
          <el-option label="1星" :value="1" />
          <el-option label="2星" :value="2" />
          <el-option label="3星" :value="3" />
          <el-option label="4星" :value="4" />
          <el-option label="5星" :value="5" />
        </el-select>
        <el-switch
          v-model="showAdoptedOnly"
          active-text="仅显示已采纳"
          @change="handleFilter"
        />
        <el-button type="primary" @click="handleFilter">搜索</el-button>
        <el-button @click="resetFilter">重置</el-button>
      </div>

      <!-- 表格 -->
      <el-table
        v-loading="loading"
        :data="reviews"
        stripe
        style="width: 100%"
        class="mb-4"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="user.nickname" label="用户" width="120">
          <template #default="{ row }">
            <div class="flex items-center">
              <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center mr-2">
                <span class="text-purple-600 text-xs font-semibold">{{ row.user?.nickname?.charAt(0) || 'U' }}</span>
              </div>
              <span>{{ row.user?.nickname || '未知用户' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="dish.name" label="菜品" width="150">
          <template #default="{ row }">
            <el-tag type="info">{{ row.dish?.name }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="rating" label="评分" width="120">
          <template #default="{ row }">
            <el-rate v-model="row.rating" disabled show-score text-color="#ff9900" />
          </template>
        </el-table-column>
        <el-table-column prop="content" label="评价内容" min-width="200">
          <template #default="{ row }">
            <div class="max-w-xs truncate">{{ row.content || '无内容' }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="审核状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 'approved' ? 'success' : row.status === 'rejected' ? 'danger' : 'warning'" effect="dark">
              {{ row.status === 'approved' ? '已通过' : row.status === 'rejected' ? '已拒绝' : '待审核' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="采纳/追踪" width="140">
          <template #default="{ row }">
            <el-tag v-if="row.is_adopted" type="success" effect="dark" class="mb-1">已采纳</el-tag>
            <el-tag v-if="row.tracking_status !== 'pending'" :type="getTrackingStatusType(row.tracking_status)" class="block">
              {{ getTrackingStatusText(row.tracking_status) }}
            </el-tag>
            <span v-else class="text-gray-400 text-xs">未采纳</span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button
              v-if="row.status === 'pending'"
              type="success"
              size="small"
              @click="handleApprove(row.id, 'approve')"
            >
              <el-icon><Check /></el-icon>
              通过
            </el-button>
            <el-button
              v-if="row.status === 'pending'"
              type="danger"
              size="small"
              @click="handleApprove(row.id, 'reject')"
            >
              <el-icon><Close /></el-icon>
              拒绝
            </el-button>
            <el-button
              v-if="row.status === 'approved' && !row.admin_reply"
              type="primary"
              size="small"
              @click="showReplyDialog(row)"
            >
              <el-icon><ChatDotRound /></el-icon>
              回复
            </el-button>
            <el-button
              v-if="row.status === 'approved' && !row.is_adopted"
              type="warning"
              size="small"
              @click="handleAdopt(row.id)"
            >
              <el-icon><Star /></el-icon>
              采纳
            </el-button>
            <el-button
              v-if="row.is_adopted"
              type="info"
              size="small"
              @click="showTrackingDialog(row)"
            >
              <el-icon><Clock /></el-icon>
              追踪
            </el-button>
            <el-button
              link
              type="primary"
              size="small"
              @click="viewDetail(row)"
            >
              <el-icon><View /></el-icon>
              详情
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-if="pagination"
        v-model:current-page="currentPage"
        :page-size="pagination.page_size"
        :total="pagination.total_count"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
        @size-change="handleSizeChange"
        class="mt-4"
      />
    </div>

    <!-- 回复对话框 -->
    <el-dialog
      v-model="showReplyDialogVisible"
      title="回复评价"
      width="600px"
      destroy-on-close
    >
      <el-form :model="replyForm" label-width="100px">
        <el-form-item label="评价内容">
          <div class="p-3 bg-gray-50 rounded">{{ selectedReview?.content || '无内容' }}</div>
        </el-form-item>
        <el-form-item label="回复内容" required>
          <el-input
            v-model="replyForm.reply"
            type="textarea"
            :rows="5"
            placeholder="请输入回复内容..."
            maxlength="1000"
            show-word-limit
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showReplyDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleReply" :loading="replyLoading">确认回复</el-button>
      </template>
    </el-dialog>

    <!-- 追踪对话框 -->
    <el-dialog
      v-model="showTrackingDialogVisible"
      title="追踪优化"
      width="700px"
      destroy-on-close
    >
      <div v-if="selectedReview" class="space-y-4">
        <div>
          <h3 class="font-semibold mb-2">评价内容：</h3>
          <div class="p-3 bg-gray-50 rounded">{{ selectedReview.content || '无内容' }}</div>
        </div>
        <div v-if="selectedReview.admin_reply">
          <h3 class="font-semibold mb-2">管理员回复：</h3>
          <div class="p-3 bg-blue-50 rounded">{{ selectedReview.admin_reply }}</div>
        </div>
        <div>
          <h3 class="font-semibold mb-2">当前状态：</h3>
          <el-tag :type="getTrackingStatusType(selectedReview.tracking_status)">
            {{ getTrackingStatusText(selectedReview.tracking_status) }}
          </el-tag>
        </div>
        <div v-if="selectedReview.tracking_updates && selectedReview.tracking_updates.length > 0">
          <h3 class="font-semibold mb-2">追踪记录：</h3>
          <div class="space-y-2 max-h-60 overflow-y-auto">
            <div
              v-for="(update, index) in selectedReview.tracking_updates"
              :key="index"
              class="p-3 bg-gray-50 rounded text-sm"
            >
              <p class="text-gray-800">{{ update.message }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatDateTime(update.created_at) }}</p>
            </div>
          </div>
        </div>
        <el-divider />
        <el-form :model="trackingForm" label-width="100px">
          <el-form-item label="更新状态">
            <el-select v-model="trackingForm.status" class="w-full">
              <el-option label="待处理" value="pending" />
              <el-option label="进行中" value="in_progress" />
              <el-option label="已完成" value="completed" />
              <el-option label="已取消" value="cancelled" />
            </el-select>
          </el-form-item>
          <el-form-item label="更新说明">
            <el-input
              v-model="trackingForm.message"
              type="textarea"
              :rows="3"
              placeholder="请输入更新说明..."
              maxlength="500"
              show-word-limit
            />
          </el-form-item>
        </el-form>
      </div>
      <template #footer>
        <el-button @click="showTrackingDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleTrackingUpdate" :loading="trackingLoading">更新</el-button>
      </template>
    </el-dialog>

    <!-- 详情对话框 -->
    <el-dialog
      v-model="showDetailDialog"
      title="评价详情"
      width="800px"
      destroy-on-close
    >
      <div v-if="selectedReview" class="space-y-4">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="评价ID">{{ selectedReview.id }}</el-descriptions-item>
          <el-descriptions-item label="用户">{{ selectedReview.user?.nickname || '未知用户' }}</el-descriptions-item>
          <el-descriptions-item label="菜品">{{ selectedReview.dish?.name }}</el-descriptions-item>
          <el-descriptions-item label="评分">
            <el-rate v-model="selectedReview.rating" disabled show-score text-color="#ff9900" />
          </el-descriptions-item>
          <el-descriptions-item label="审核状态">
            <el-tag :type="selectedReview.status === 'approved' ? 'success' : selectedReview.status === 'rejected' ? 'danger' : 'warning'">
              {{ selectedReview.status === 'approved' ? '已通过' : selectedReview.status === 'rejected' ? '已拒绝' : '待审核' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="是否采纳">
            <el-tag v-if="selectedReview.is_adopted" type="success">已采纳</el-tag>
            <span v-else>未采纳</span>
          </el-descriptions-item>
          <el-descriptions-item label="追踪状态" v-if="selectedReview.is_adopted">
            <el-tag :type="getTrackingStatusType(selectedReview.tracking_status)">
              {{ getTrackingStatusText(selectedReview.tracking_status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="创建时间">{{ formatDateTime(selectedReview.created_at) }}</el-descriptions-item>
        </el-descriptions>
        <div>
          <h3 class="font-semibold mb-2">评价内容：</h3>
          <div class="p-3 bg-gray-50 rounded">{{ selectedReview.content || '无内容' }}</div>
        </div>
        <div v-if="selectedReview.admin_reply">
          <h3 class="font-semibold mb-2">管理员回复：</h3>
          <div class="p-3 bg-blue-50 rounded">{{ selectedReview.admin_reply }}</div>
        </div>
        <div v-if="selectedReview.tracking_updates && selectedReview.tracking_updates.length > 0">
          <h3 class="font-semibold mb-2">追踪记录：</h3>
          <div class="space-y-2">
            <div
              v-for="(update, index) in selectedReview.tracking_updates"
              :key="index"
              class="p-3 bg-gray-50 rounded"
            >
              <p class="text-gray-800">{{ update.message }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatDateTime(update.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Refresh, Check, Close, View, ChatDotRound, Star, Clock } from '@element-plus/icons-vue';
import { adminReviewApi, type Review } from '../api/admin/review';
import type { ReviewFilters } from '../api/review';

const reviews = ref<Review[]>([]);
const loading = ref(false);
const pagination = ref<{
  current_page: number;
  total_pages: number;
  total_count: number;
  page_size: number;
} | null>(null);

const filters = ref<ReviewFilters & { rating?: number }>({
  status: '',
  tracking_status: '',
  rating: undefined,
});

const showAdoptedOnly = ref(false);
const currentPage = ref(1);
const showReplyDialogVisible = ref(false);
const showTrackingDialogVisible = ref(false);
const showDetailDialog = ref(false);
const selectedReview = ref<Review | null>(null);
const replyForm = ref({ reply: '' });
const replyLoading = ref(false);
const trackingForm = ref({
  status: 'pending' as 'pending' | 'in_progress' | 'completed' | 'cancelled',
  message: '',
});
const trackingLoading = ref(false);

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
    status: '',
    tracking_status: '',
    rating: undefined,
  };
  showAdoptedOnly.value = false;
  currentPage.value = 1;
  fetchReviews();
};

const handlePageChange = (page: number) => {
  currentPage.value = page;
  fetchReviews();
};

const handleSizeChange = (size: number) => {
  if (pagination.value) {
    pagination.value.page_size = size;
  }
  fetchReviews();
};

const refreshData = () => {
  fetchReviews();
  ElMessage.success('数据已刷新');
};

const fetchReviews = async () => {
  loading.value = true;
  try {
    const params: ReviewFilters & { rating?: number } = {
      page: currentPage.value,
      page_size: pagination.value?.page_size || 20,
    };

    if (filters.value.status) {
      params.status = filters.value.status;
    }
    if (filters.value.tracking_status) {
      params.tracking_status = filters.value.tracking_status;
    }
    if (filters.value.rating) {
      params.rating = filters.value.rating;
    }
    if (showAdoptedOnly.value) {
      params.is_adopted = true;
    }

    const response = await adminReviewApi.getReviews(params);

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

const handleApprove = async (reviewId: number, action: 'approve' | 'reject') => {
  try {
    await ElMessageBox.confirm(
      action === 'approve' ? '确认通过此评价吗？' : '确认拒绝此评价吗？',
      '提示',
      {
        confirmButtonText: '确认',
        cancelButtonText: '取消',
        type: action === 'approve' ? 'success' : 'warning',
      }
    );
    
    const response = await adminReviewApi.approveReview(reviewId, action);
    if (response.code === 200) {
      ElMessage.success(action === 'approve' ? '审核通过' : '已拒绝');
      fetchReviews();
    } else {
      ElMessage.error(response.message || '操作失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('操作失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '操作失败');
    }
  }
};

const showReplyDialog = (review: Review) => {
  selectedReview.value = review;
  replyForm.value.reply = '';
  showReplyDialogVisible.value = true;
};

const handleReply = async () => {
  if (!selectedReview.value || !replyForm.value.reply.trim()) {
    ElMessage.warning('请输入回复内容');
    return;
  }

  replyLoading.value = true;
  try {
    const response = await adminReviewApi.replyReview(selectedReview.value.id, replyForm.value.reply);
    if (response.code === 200) {
      ElMessage.success('回复成功');
      showReplyDialogVisible.value = false;
      fetchReviews();
    } else {
      ElMessage.error(response.message || '回复失败');
    }
  } catch (error: any) {
    console.error('回复失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '回复失败');
  } finally {
    replyLoading.value = false;
  }
};

const handleAdopt = async (reviewId: number) => {
  try {
    await ElMessageBox.confirm(
      '确认采纳此评价建议吗？采纳后用户将获得积分奖励，并开始追踪优化。',
      '提示',
      {
        confirmButtonText: '确认采纳',
        cancelButtonText: '取消',
        type: 'warning',
      }
    );
    
    const response = await adminReviewApi.adoptReview(reviewId);
    if (response.code === 200) {
      ElMessage.success('评价建议已采纳，用户将获得积分奖励');
      fetchReviews();
    } else {
      ElMessage.error(response.message || '采纳失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('采纳失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '采纳失败');
    }
  }
};

const showTrackingDialog = async (review: Review) => {
  selectedReview.value = review;
  trackingForm.value.status = review.tracking_status || 'pending';
  trackingForm.value.message = '';
  
  // 获取最新详情
  try {
    const response = await adminReviewApi.getReview(review.id);
    if (response.code === 200 && response.data) {
      selectedReview.value = response.data;
    }
  } catch (error) {
    console.error('获取评价详情失败:', error);
  }
  
  showTrackingDialogVisible.value = true;
};

const handleTrackingUpdate = async () => {
  if (!selectedReview.value || !trackingForm.value.message.trim()) {
    ElMessage.warning('请输入更新说明');
    return;
  }

  trackingLoading.value = true;
  try {
    const response = await adminReviewApi.updateTrackingStatus(
      selectedReview.value.id,
      trackingForm.value.status,
      trackingForm.value.message
    );
    if (response.code === 200) {
      ElMessage.success('追踪状态更新成功');
      showTrackingDialogVisible.value = false;
      fetchReviews();
    } else {
      ElMessage.error(response.message || '更新失败');
    }
  } catch (error: any) {
    console.error('更新失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '更新失败');
  } finally {
    trackingLoading.value = false;
  }
};

const viewDetail = async (review: Review) => {
  showDetailDialog.value = true;
  selectedReview.value = null;
  
  try {
    const response = await adminReviewApi.getReview(review.id);
    if (response.code === 200 && response.data) {
      selectedReview.value = response.data;
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
:deep(.el-table) {
  border-radius: 8px;
  overflow: hidden;
}

:deep(.el-table th) {
  background-color: #f8fafc;
  font-weight: 600;
}
</style>
