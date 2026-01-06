/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">排队管理</h1>
          <p class="text-gray-600">管理和查看所有排队记录</p>
        </div>
        <div class="flex gap-2">
          <el-button 
            type="success" 
            size="large" 
            @click="handleCallNext"
            :disabled="statistics.waiting_count === 0"
          >
            <el-icon><Bell /></el-icon>
            叫号下一个
          </el-button>
          <el-button 
            v-if="selectedQueues.length > 0" 
            type="danger" 
            size="large" 
            @click="handleBatchDelete"
          >
            <el-icon><Delete /></el-icon>
            批量删除 ({{ selectedQueues.length }})
          </el-button>
          <el-button type="primary" size="large" @click="refreshData">
            <el-icon><Refresh /></el-icon>
            刷新
          </el-button>
        </div>
      </div>

      <!-- 统计信息 -->
      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <div class="text-blue-600 text-sm font-medium mb-1">等待中</div>
          <div class="text-2xl font-bold text-blue-800">{{ statistics.waiting_count }}</div>
        </div>
        <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
          <div class="text-orange-600 text-sm font-medium mb-1">已叫号</div>
          <div class="text-2xl font-bold text-orange-800">{{ statistics.called_count }}</div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
          <div class="text-green-600 text-sm font-medium mb-1">今日排队</div>
          <div class="text-2xl font-bold text-green-800">{{ statistics.today_count }}</div>
        </div>
      </div>

      <!-- 筛选栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg flex-wrap">
        <el-select 
          v-model="filters.status" 
          placeholder="状态筛选" 
          clearable 
          class="w-48"
          @change="handleFilter"
        >
          <el-option label="等待中" value="waiting" />
          <el-option label="已叫号" value="called" />
          <el-option label="已入座" value="seated" />
          <el-option label="已取消" value="cancelled" />
        </el-select>
        <el-input
          v-model="filters.queue_number"
          placeholder="排队号"
          class="w-48"
          clearable
          @clear="handleFilter"
          @keyup.enter="handleFilter"
        />
        <el-input
          v-model="filters.user_nickname"
          placeholder="用户昵称"
          class="w-48"
          clearable
          @clear="handleFilter"
          @keyup.enter="handleFilter"
        />
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          class="w-64"
          @change="handleDateRangeChange"
        />
        <el-button type="primary" @click="handleFilter">搜索</el-button>
        <el-button @click="resetFilter">重置</el-button>
      </div>

      <!-- 表格 -->
      <el-table
        v-loading="loading"
        :data="queues"
        stripe
        style="width: 100%"
        class="mb-4"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column prop="queue_number" label="排队号" width="120" />
        <el-table-column prop="user.nickname" label="用户" width="120">
          <template #default="{ row }">
            <div class="flex items-center">
              <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                <span class="text-blue-600 text-xs font-semibold">{{ row.user?.nickname?.charAt(0) || 'U' }}</span>
              </div>
              <span>{{ row.user?.nickname || '未知用户' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="guest_count" label="用餐人数" width="100" />
        <el-table-column prop="table_type" label="桌位类型" width="120">
          <template #default="{ row }">
            <el-tag v-if="row.table_type" type="info">{{ getTableTypeLabel(row.table_type) }}</el-tag>
            <span v-else class="text-gray-400">不限</span>
          </template>
        </el-table-column>
        <el-table-column prop="position" label="位置" width="80" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="joined_at" label="加入时间" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.joined_at) }}
          </template>
        </el-table-column>
        <el-table-column prop="called_at" label="叫号时间" width="180">
          <template #default="{ row }">
            <div v-if="row.called_at">
              <div>{{ formatDateTime(row.called_at) }}</div>
              <div v-if="row.status === 'called' && row.is_timeout" class="text-red-600 text-xs mt-1">
                ⚠️ 已超时
              </div>
              <div v-else-if="row.status === 'called' && row.remaining_minutes !== null && row.remaining_minutes >= 0" class="text-orange-600 text-xs mt-1">
                ⏰ 剩余{{ row.remaining_minutes }}分钟
              </div>
            </div>
            <span v-else class="text-gray-400">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="seated_at" label="入座时间" width="180">
          <template #default="{ row }">
            <span v-if="row.seated_at">{{ formatDateTime(row.seated_at) }}</span>
            <span v-else class="text-gray-400">-</span>
          </template>
        </el-table-column>
        <el-table-column label="超时状态" width="120">
          <template #default="{ row }">
            <el-tag v-if="row.status === 'called' && row.is_timeout" type="danger" effect="dark">
              ⚠️ 已超时
            </el-tag>
            <el-tag v-else-if="row.status === 'called' && row.remaining_minutes !== null && row.remaining_minutes >= 0" type="warning">
              ⏰ 剩余{{ row.remaining_minutes }}分钟
            </el-tag>
            <span v-else class="text-gray-400">-</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button 
              v-if="row.status === 'waiting'" 
              type="primary" 
              size="small" 
              @click="handleAdjustPosition(row)"
            >
              调整位置
            </el-button>
            <el-button 
              v-if="row.status === 'called'" 
              type="success" 
              size="small" 
              @click="handleMarkSeated(row)"
            >
              标记入座
            </el-button>
            <el-button 
              v-if="row.status === 'waiting' || row.status === 'called'" 
              type="warning" 
              size="small" 
              @click="handleCancel(row)"
            >
              取消
            </el-button>
            <el-button 
              type="info" 
              size="small" 
              @click="handleViewDetail(row)"
            >
              详情
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="pagination.current_page"
        v-model:page-size="pagination.page_size"
        :total="pagination.total_count"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handlePageSizeChange"
        @current-change="handlePageChange"
      />
    </div>

    <!-- 详情对话框 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="排队详情"
      width="600px"
    >
      <el-descriptions v-if="currentQueue" :column="2" border>
        <el-descriptions-item label="排队号">{{ currentQueue.queue_number }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="getStatusType(currentQueue.status)">
            {{ getStatusLabel(currentQueue.status) }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="用户昵称">{{ currentQueue.user?.nickname || '未知用户' }}</el-descriptions-item>
        <el-descriptions-item label="用户手机">{{ currentQueue.user?.phone || '-' }}</el-descriptions-item>
        <el-descriptions-item label="用餐人数">{{ currentQueue.guest_count }} 人</el-descriptions-item>
        <el-descriptions-item label="桌位类型">
          <el-tag v-if="currentQueue.table_type" type="info">{{ getTableTypeLabel(currentQueue.table_type) }}</el-tag>
          <span v-else class="text-gray-400">不限</span>
        </el-descriptions-item>
        <el-descriptions-item label="当前位置">{{ currentQueue.position }}</el-descriptions-item>
        <el-descriptions-item label="加入时间">{{ formatDateTime(currentQueue.joined_at) }}</el-descriptions-item>
        <el-descriptions-item v-if="currentQueue.called_at" label="叫号时间">
          <div>{{ formatDateTime(currentQueue.called_at) }}</div>
          <div v-if="currentQueue.is_timeout" class="text-red-600 text-sm mt-1">
            ⚠️ 已超时（超时时间：{{ currentQueue.timeout_at ? formatDateTime(currentQueue.timeout_at) : '-' }}）
          </div>
          <div v-else-if="currentQueue.remaining_minutes !== null && currentQueue.remaining_minutes >= 0" class="text-orange-600 text-sm mt-1">
            ⏰ 剩余{{ currentQueue.remaining_minutes }}分钟
          </div>
        </el-descriptions-item>
        <el-descriptions-item v-if="currentQueue.seated_at" label="入座时间">{{ formatDateTime(currentQueue.seated_at) }}</el-descriptions-item>
        <el-descriptions-item v-if="currentQueue.status === 'called'" label="超时状态">
          <el-tag v-if="currentQueue.is_timeout" type="danger" effect="dark">
            ⚠️ 已超时
          </el-tag>
          <el-tag v-else-if="currentQueue.remaining_minutes !== null && currentQueue.remaining_minutes >= 0" type="warning">
            ⏰ 剩余{{ currentQueue.remaining_minutes }}分钟
          </el-tag>
          <span v-else class="text-gray-400">-</span>
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <!-- 调整位置对话框 -->
    <el-dialog
      v-model="adjustPositionDialogVisible"
      title="调整排队位置"
      width="400px"
    >
      <el-form :model="adjustPositionForm" label-width="100px">
        <el-form-item label="新位置">
          <el-input-number
            v-model="adjustPositionForm.position"
            :min="1"
            :max="999"
            style="width: 100%"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="adjustPositionDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmAdjustPosition">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Refresh, Delete, Bell } from '@element-plus/icons-vue';
import { adminQueueApi, type Queue, type GetQueuesParams } from '../api/admin/queue';

const loading = ref(false);
const queues = ref<Queue[]>([]);
const selectedQueues = ref<Queue[]>([]);
const statistics = ref({
  waiting_count: 0,
  called_count: 0,
  today_count: 0,
});
const pagination = reactive({
  current_page: 1,
  page_size: 20,
  total_count: 0,
});

const filters = reactive<GetQueuesParams>({
  status: undefined,
  queue_number: undefined,
  user_nickname: undefined,
  date_from: undefined,
  date_to: undefined,
});

const dateRange = ref<[string, string] | null>(null);

const detailDialogVisible = ref(false);
const currentQueue = ref<Queue | null>(null);

const adjustPositionDialogVisible = ref(false);
const adjustPositionForm = reactive({
  queueId: 0,
  position: 1,
});

const fetchData = async () => {
  loading.value = true;
  try {
    const params: GetQueuesParams = {
      page: pagination.current_page,
      page_size: pagination.page_size,
      ...filters,
    };
    const response = await adminQueueApi.getList(params);
    // adminApiClient响应拦截器返回的是response.data，所以直接使用response.code
    if (response.code === 200 && response.data) {
      queues.value = response.data.queues || [];
      pagination.total_count = response.data.pagination?.total_count || 0;
      statistics.value = response.data.statistics || {
        waiting_count: 0,
        called_count: 0,
        today_count: 0,
      };
    }
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || error.message || '获取排队列表失败');
  } finally {
    loading.value = false;
  }
};

const refreshData = () => {
  pagination.current_page = 1;
  fetchData();
};

const handleFilter = () => {
  pagination.current_page = 1;
  fetchData();
};

const resetFilter = () => {
  filters.status = undefined;
  filters.queue_number = undefined;
  filters.user_nickname = undefined;
  filters.date_from = undefined;
  filters.date_to = undefined;
  dateRange.value = null;
  handleFilter();
};

const handleDateRangeChange = (dates: [string, string] | null) => {
  if (dates) {
    filters.date_from = dates[0];
    filters.date_to = dates[1];
  } else {
    filters.date_from = undefined;
    filters.date_to = undefined;
  }
  handleFilter();
};

const handleSelectionChange = (selection: Queue[]) => {
  selectedQueues.value = selection;
};

const handleCallNext = async () => {
  try {
    await ElMessageBox.confirm('确定要叫下一个号吗？', '提示', {
      type: 'warning',
    });
    const response = await adminQueueApi.callNext();
    // adminApiClient响应拦截器返回的是response.data，所以直接使用response.code
    if (response.code === 200 && response.data) {
      ElMessage.success(`叫号成功：${response.data.queue?.queue_number || '未知'}`);
      refreshData();
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || error.message || '叫号失败');
    }
  }
};

const handleMarkSeated = async (queue: Queue) => {
  try {
    await ElMessageBox.confirm(`确定要将排队号 ${queue.queue_number} 标记为已入座吗？`, '提示', {
      type: 'warning',
    });
    const response = await adminQueueApi.markSeated(queue.id);
    // adminApiClient响应拦截器返回的是response.data，所以直接使用response.code
    if (response.code === 200) {
      ElMessage.success('标记成功');
      refreshData();
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || error.message || '标记失败');
    }
  }
};

const handleCancel = async (queue: Queue) => {
  try {
    await ElMessageBox.confirm(`确定要取消排队号 ${queue.queue_number} 吗？`, '提示', {
      type: 'warning',
    });
    const response = await adminQueueApi.cancel(queue.id);
    // adminApiClient响应拦截器返回的是response.data，所以直接使用response.code
    if (response.code === 200) {
      ElMessage.success('取消成功');
      refreshData();
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || error.message || '取消失败');
    }
  }
};

const handleAdjustPosition = (queue: Queue) => {
  adjustPositionForm.queueId = queue.id;
  adjustPositionForm.position = queue.position;
  adjustPositionDialogVisible.value = true;
};

const confirmAdjustPosition = async () => {
  try {
    const response = await adminQueueApi.adjustPosition(
      adjustPositionForm.queueId,
      adjustPositionForm.position
    );
    // adminApiClient响应拦截器返回的是response.data，所以直接使用response.code
    if (response.code === 200) {
      ElMessage.success('调整成功');
      adjustPositionDialogVisible.value = false;
      refreshData();
    }
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || error.message || '调整失败');
  }
};

const handleViewDetail = async (queue: Queue) => {
  try {
    const response = await adminQueueApi.getDetail(queue.id);
    // adminApiClient响应拦截器返回的是response.data，所以直接使用response.code
    if (response.code === 200 && response.data) {
      currentQueue.value = response.data;
      detailDialogVisible.value = true;
    }
  } catch (error: any) {
    ElMessage.error(error.response?.data?.message || error.message || '获取详情失败');
  }
};

const handleBatchDelete = async () => {
  if (selectedQueues.value.length === 0) {
    ElMessage.warning('请选择要删除的排队记录');
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${selectedQueues.value.length} 条排队记录吗？`,
      '提示',
      {
        type: 'warning',
      }
    );
    const ids = selectedQueues.value.map((q) => q.id);
    const response = await adminQueueApi.batchDelete(ids);
    // adminApiClient响应拦截器返回的是response.data，所以直接使用response.code
    if (response.code === 200 && response.data) {
      ElMessage.success(`成功删除 ${response.data.deleted_count || 0} 条记录`);
      selectedQueues.value = [];
      refreshData();
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || error.message || '删除失败');
    }
  }
};

const handlePageChange = (page: number) => {
  pagination.current_page = page;
  fetchData();
};

const handlePageSizeChange = (size: number) => {
  pagination.page_size = size;
  pagination.current_page = 1;
  fetchData();
};

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    waiting: '等待中',
    called: '已叫号',
    seated: '已入座',
    cancelled: '已取消',
  };
  return labels[status] || status;
};

const getStatusType = (status: string) => {
  const types: Record<string, string> = {
    waiting: 'warning',
    called: 'info',
    seated: 'success',
    cancelled: 'danger',
  };
  return types[status] || '';
};

const getTableTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    window: '靠窗',
    corner: '角落',
    center: '中央',
    any: '不限',
  };
  return labels[type] || type;
};

const formatDateTime = (dateTime: string) => {
  if (!dateTime) return '-';
  const date = new Date(dateTime);
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
.el-table {
  font-size: 14px;
}
</style>

