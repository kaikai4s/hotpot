/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">操作日志</h1>
          <p class="text-gray-600">查看和管理后台所有操作记录</p>
        </div>
        <el-button type="primary" size="large" @click="refreshData">
          <el-icon><Refresh /></el-icon>
          刷新
        </el-button>
      </div>

      <!-- 筛选栏 -->
      <div class="flex flex-wrap gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-select 
          v-model="filters.action" 
          placeholder="操作类型" 
          clearable 
          class="w-48"
          @change="handleFilter"
        >
          <el-option label="创建" value="create" />
          <el-option label="更新" value="update" />
          <el-option label="删除" value="delete" />
          <el-option label="查看" value="view" />
          <el-option label="列表" value="list" />
        </el-select>
        <el-select 
          v-model="filters.model_type" 
          placeholder="操作对象" 
          clearable 
          class="w-48"
          @change="handleFilter"
        >
          <el-option label="预约" value="App\Models\Reservation" />
          <el-option label="评价" value="App\Models\Review" />
          <el-option label="订单" value="App\Models\Order" />
          <el-option label="用户" value="App\Models\User" />
          <el-option label="管理员" value="App\Models\Admin" />
          <el-option label="桌位" value="App\Models\Table" />
          <el-option label="菜品" value="App\Models\Dish" />
        </el-select>
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
        :data="logs"
        stripe
        style="width: 100%"
        class="mb-4"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="admin.name" label="操作人" width="150">
          <template #default="{ row }">
            <div class="flex items-center">
              <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                <span class="text-blue-600 text-xs font-semibold">{{ row.admin?.name?.charAt(0) || 'A' }}</span>
              </div>
              <span>{{ row.admin?.name || row.admin?.username || '未知' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="action" label="操作类型" width="120">
          <template #default="{ row }">
            <el-tag :type="getActionType(row.action)">
              {{ getActionText(row.action) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="model_type" label="操作对象" width="150">
          <template #default="{ row }">
            <span v-if="row.model_type">{{ getModelTypeText(row.model_type) }}</span>
            <span v-else class="text-gray-400">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="model_id" label="对象ID" width="100">
          <template #default="{ row }">
            <span v-if="row.model_id">{{ row.model_id }}</span>
            <span v-else class="text-gray-400">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="200">
          <template #default="{ row }">
            <div class="max-w-xs truncate">{{ row.description || '-' }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="ip_address" label="IP地址" width="140">
          <template #default="{ row }">
            <span v-if="row.ip_address">{{ row.ip_address }}</span>
            <span v-else class="text-gray-400">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="操作时间" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" @click="viewDetail(row)">查看详情</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="pagination?.total_count || 0"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
        class="mt-4"
      />
    </div>

    <!-- 详情对话框 -->
    <el-dialog
      v-model="showDetailDialog"
      title="操作日志详情"
      width="800px"
      destroy-on-close
    >
      <div v-if="selectedLog" class="space-y-4">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="ID">{{ selectedLog.id }}</el-descriptions-item>
          <el-descriptions-item label="操作人">
            {{ selectedLog.admin?.name || selectedLog.admin?.username || '未知' }}
          </el-descriptions-item>
          <el-descriptions-item label="操作类型">
            <el-tag :type="getActionType(selectedLog.action)">
              {{ getActionText(selectedLog.action) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="操作对象">
            {{ getModelTypeText(selectedLog.model_type) || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="对象ID">{{ selectedLog.model_id || '-' }}</el-descriptions-item>
          <el-descriptions-item label="IP地址">{{ selectedLog.ip_address || '-' }}</el-descriptions-item>
          <el-descriptions-item label="操作时间" :span="2">
            {{ formatDateTime(selectedLog.created_at) }}
          </el-descriptions-item>
          <el-descriptions-item label="描述" :span="2">
            {{ selectedLog.description || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="User Agent" :span="2">
            <div class="text-sm text-gray-600 break-all">{{ selectedLog.user_agent || '-' }}</div>
          </el-descriptions-item>
        </el-descriptions>

        <!-- 数据变更 -->
        <div v-if="selectedLog.old_values || selectedLog.new_values" class="mt-4">
          <h3 class="text-lg font-semibold mb-2">数据变更</h3>
          <div class="grid grid-cols-2 gap-4">
            <div v-if="selectedLog.old_values">
              <h4 class="font-semibold text-gray-700 mb-2">变更前</h4>
              <pre class="bg-gray-50 p-3 rounded text-sm overflow-auto max-h-64">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
            </div>
            <div v-if="selectedLog.new_values">
              <h4 class="font-semibold text-gray-700 mb-2">变更后</h4>
              <pre class="bg-gray-50 p-3 rounded text-sm overflow-auto max-h-64">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import { Refresh } from '@element-plus/icons-vue';
import { adminAuditLogApi, type AuditLog, type AuditLogFilters } from '../api/admin/audit-log';

const loading = ref(false);
const logs = ref<AuditLog[]>([]);
const pagination = ref<any>(null);
const currentPage = ref(1);
const pageSize = ref(20);
const dateRange = ref<[string, string] | null>(null);

const filters = ref<AuditLogFilters>({
  action: undefined,
  model_type: undefined,
  date_from: undefined,
  date_to: undefined,
});

const showDetailDialog = ref(false);
const selectedLog = ref<AuditLog | null>(null);

const getActionType = (action: string) => {
  const types: Record<string, string> = {
    create: 'success',
    update: 'warning',
    delete: 'danger',
    view: 'info',
    list: '',
  };
  return types[action] || '';
};

const getActionText = (action: string) => {
  const texts: Record<string, string> = {
    create: '创建',
    update: '更新',
    delete: '删除',
    view: '查看',
    list: '列表',
  };
  return texts[action] || action;
};

const getModelTypeText = (modelType: string) => {
  const texts: Record<string, string> = {
    'App\\Models\\Reservation': '预约',
    'App\\Models\\Review': '评价',
    'App\\Models\\Order': '订单',
    'App\\Models\\User': '用户',
    'App\\Models\\Admin': '管理员',
    'App\\Models\\Table': '桌位',
    'App\\Models\\Dish': '菜品',
    'App\\Models\\Coupon': '优惠券',
    'App\\Models\\PointRule': '积分规则',
  };
  return texts[modelType] || modelType;
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const handleDateRangeChange = (dates: [string, string] | null) => {
  if (dates && dates.length === 2) {
    filters.value.date_from = dates[0];
    filters.value.date_to = dates[1];
  } else {
    filters.value.date_from = undefined;
    filters.value.date_to = undefined;
  }
};

const handleFilter = () => {
  currentPage.value = 1;
  fetchLogs();
};

const resetFilter = () => {
  filters.value = {
    action: undefined,
    model_type: undefined,
    date_from: undefined,
    date_to: undefined,
  };
  dateRange.value = null;
  currentPage.value = 1;
  fetchLogs();
};

const handlePageChange = (page: number) => {
  currentPage.value = page;
  fetchLogs();
};

const handleSizeChange = (size: number) => {
  pageSize.value = size;
  currentPage.value = 1;
  fetchLogs();
};

const viewDetail = async (log: AuditLog) => {
  try {
    const response = await adminAuditLogApi.getLog(log.id);
    if (response.code === 200 && response.data) {
      selectedLog.value = response.data;
      showDetailDialog.value = true;
    } else {
      ElMessage.error(response.message || '获取日志详情失败');
    }
  } catch (error: any) {
    console.error('获取日志详情失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取日志详情失败');
  }
};

const fetchLogs = async () => {
  loading.value = true;
  try {
    const params: AuditLogFilters = {
      page: currentPage.value,
      page_size: pageSize.value,
      ...filters.value,
    };

    const response = await adminAuditLogApi.getLogs(params);
    if (response.code === 200 && response.data) {
      logs.value = response.data.logs || [];
      pagination.value = response.data.pagination || null;
    } else {
      ElMessage.error(response.message || '获取操作日志失败');
      logs.value = [];
      pagination.value = null;
    }
  } catch (error: any) {
    console.error('获取操作日志失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取操作日志失败');
    logs.value = [];
    pagination.value = null;
  } finally {
    loading.value = false;
  }
};

const refreshData = () => {
  fetchLogs();
  ElMessage.success('数据已刷新');
};

onMounted(() => {
  fetchLogs();
});
</script>

<style scoped>
pre {
  font-family: 'Courier New', monospace;
  font-size: 12px;
  line-height: 1.5;
}
</style>

