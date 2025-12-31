<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">预约管理</h1>
          <p class="text-gray-600">管理和查看所有预约记录</p>
          <div class="mt-2">
            <el-tag :type="depositEnabled ? 'success' : 'info'" size="small">
              {{ depositEnabled ? '✓ 定金功能已启用' : '○ 定金功能未启用' }}
            </el-tag>
          </div>
        </div>
        <el-button type="primary" size="large" @click="refreshData">
          <el-icon><Refresh /></el-icon>
          刷新
        </el-button>
      </div>

      <!-- 筛选栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-select 
          v-model="filters.status" 
          placeholder="状态筛选" 
          clearable 
          class="w-48"
          @change="handleFilter"
        >
          <el-option label="待确认" value="pending" />
          <el-option label="已确认" value="confirmed" />
          <el-option label="已取消" value="cancelled" />
          <el-option label="已完成" value="completed" />
          <el-option label="已过期" value="expired" />
        </el-select>
        <el-date-picker
          v-model="filters.date"
          type="date"
          placeholder="选择日期"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          class="w-48"
          @change="handleFilter"
        />
        <el-button type="primary" @click="handleFilter">搜索</el-button>
        <el-button @click="resetFilter">重置</el-button>
      </div>

      <!-- 表格 -->
      <el-table
        v-loading="store.loading"
        :data="store.reservations"
        stripe
        style="width: 100%"
        class="mb-4"
        :row-class-name="tableRowClassName"
      >
        <el-table-column prop="reservation_code" label="预约编码" width="180" />
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
        <el-table-column prop="table.name" label="桌位" width="100">
          <template #default="{ row }">
            <el-tag type="info">{{ row.table?.name }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="date" label="日期" width="120">
          <template #default="{ row }">
            {{ formatDate(row.date) }}
          </template>
        </el-table-column>
        <el-table-column prop="time_slot" label="时间段" width="100" />
        <el-table-column prop="guest_count" label="人数" width="80">
          <template #default="{ row }">
            <el-tag>{{ row.guest_count }}人</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="contact_name" label="联系人" width="120" />
        <el-table-column prop="contact_phone" label="联系电话" width="130" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" effect="dark">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="viewDetail(row)">
              <el-icon><View /></el-icon>
              查看
            </el-button>
            <el-button 
              v-if="row.status === 'pending'" 
              link 
              type="success" 
              size="small" 
              @click="confirmReservation(row.id)"
            >
              确认
            </el-button>
            <el-button 
              v-if="row.status !== 'cancelled' && row.status !== 'completed'" 
              link 
              type="danger" 
              size="small" 
              @click="cancelReservation(row.id)"
            >
              取消
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-if="store.pagination"
        v-model:current-page="currentPage"
        :page-size="store.pagination.page_size"
        :total="store.pagination.total_count"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
        @size-change="handleSizeChange"
        class="mt-4"
      />
    </div>

    <!-- 预约详情对话框 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="预约详情"
      width="800px"
      :close-on-click-modal="false"
    >
      <div v-if="selectedReservation" class="space-y-4">
        <!-- 基本信息 -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <h3 class="text-lg font-semibold text-gray-900 mb-3">基本信息</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <span class="text-gray-600">预约编码：</span>
              <span class="font-semibold text-gray-900">{{ selectedReservation.reservation_code }}</span>
            </div>
            <div>
              <span class="text-gray-600">状态：</span>
              <el-tag :type="getStatusType(selectedReservation.status)" effect="dark">
                {{ getStatusText(selectedReservation.status) }}
              </el-tag>
            </div>
            <div>
              <span class="text-gray-600">用户：</span>
              <span class="font-semibold text-gray-900">{{ selectedReservation.user?.nickname || '未知' }}</span>
            </div>
            <div>
              <span class="text-gray-600">桌位：</span>
              <el-tag type="info">{{ selectedReservation.table?.name }}</el-tag>
            </div>
            <div>
              <span class="text-gray-600">预约日期：</span>
              <span class="font-semibold text-gray-900">{{ formatDate(selectedReservation.date) }}</span>
            </div>
            <div>
              <span class="text-gray-600">时间段：</span>
              <span class="font-semibold text-gray-900">{{ selectedReservation.time_slot }}</span>
            </div>
            <div>
              <span class="text-gray-600">用餐人数：</span>
              <span class="font-semibold text-gray-900">{{ selectedReservation.guest_count }}人</span>
            </div>
            <div>
              <span class="text-gray-600">联系人：</span>
              <span class="font-semibold text-gray-900">{{ selectedReservation.contact_name }}</span>
            </div>
            <div>
              <span class="text-gray-600">联系电话：</span>
              <span class="font-semibold text-gray-900">{{ selectedReservation.contact_phone }}</span>
            </div>
            <div v-if="selectedReservation.special_requests" class="col-span-2">
              <span class="text-gray-600">特殊需求：</span>
              <p class="text-gray-900 mt-1">{{ selectedReservation.special_requests }}</p>
            </div>
          </div>
        </div>

        <!-- 定金信息 -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <h3 class="text-lg font-semibold text-gray-900 mb-3">定金信息</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <span class="text-gray-600">定金金额：</span>
              <span class="font-semibold text-red-600">¥{{ selectedReservation.deposit_amount || 0 }}</span>
            </div>
            <div>
              <span class="text-gray-600">定金状态：</span>
              <el-tag :type="getDepositStatusType(selectedReservation.deposit_status)" effect="dark">
                {{ getDepositStatusText(selectedReservation.deposit_status) }}
              </el-tag>
            </div>
            <div v-if="selectedReservation.deposit_paid_at">
              <span class="text-gray-600">支付时间：</span>
              <span class="font-semibold text-gray-900">{{ formatDateTime(selectedReservation.deposit_paid_at) }}</span>
            </div>
            <div v-if="selectedReservation.deposit_refunded_at">
              <span class="text-gray-600">退还时间：</span>
              <span class="font-semibold text-gray-900">{{ formatDateTime(selectedReservation.deposit_refunded_at) }}</span>
            </div>
            <div v-if="selectedReservation.deposit_transaction_id">
              <span class="text-gray-600">交易ID：</span>
              <span class="font-semibold text-gray-900">{{ selectedReservation.deposit_transaction_id }}</span>
            </div>
          </div>
        </div>

        <!-- 时间信息 -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <h3 class="text-lg font-semibold text-gray-900 mb-3">时间信息</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <span class="text-gray-600">创建时间：</span>
              <span class="font-semibold text-gray-900">{{ formatDateTime(selectedReservation.created_at) }}</span>
            </div>
            <div v-if="selectedReservation.expires_at">
              <span class="text-gray-600">过期时间：</span>
              <span class="font-semibold text-gray-900">{{ formatDateTime(selectedReservation.expires_at) }}</span>
            </div>
            <div v-if="selectedReservation.confirmed_at">
              <span class="text-gray-600">确认时间：</span>
              <span class="font-semibold text-gray-900">{{ formatDateTime(selectedReservation.confirmed_at) }}</span>
            </div>
            <div v-if="selectedReservation.arrived_at">
              <span class="text-gray-600">到达时间：</span>
              <span class="font-semibold text-green-600">{{ formatDateTime(selectedReservation.arrived_at) }}</span>
            </div>
            <div v-if="selectedReservation.cancelled_at">
              <span class="text-gray-600">取消时间：</span>
              <span class="font-semibold text-gray-900">{{ formatDateTime(selectedReservation.cancelled_at) }}</span>
            </div>
          </div>
        </div>

        <!-- 关联订单 -->
        <div v-if="selectedReservation.order" class="bg-gray-50 p-4 rounded-lg">
          <h3 class="text-lg font-semibold text-gray-900 mb-3">关联订单</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <span class="text-gray-600">订单号：</span>
              <span class="font-semibold text-gray-900">{{ selectedReservation.order.order_no }}</span>
            </div>
            <div>
              <span class="text-gray-600">订单状态：</span>
              <el-tag>{{ selectedReservation.order.status }}</el-tag>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Refresh, View } from '@element-plus/icons-vue';
import { useReservationStore } from '../stores/reservation';
import { adminReservationApi } from '../api/admin/reservation';
import { configApi } from '../api/config';
import type { Reservation } from '../types';

const route = useRoute();

const store = useReservationStore();

const filters = ref({
  status: '',
  date: '',
});

const currentPage = ref(1);
const detailDialogVisible = ref(false);
const selectedReservation = ref<Reservation | null>(null);
const loadingDetail = ref(false);
const depositEnabled = ref<boolean>(true);

const getStatusType = (status: string) => {
  const types: Record<string, string> = {
    pending: 'warning',
    confirmed: 'success',
    cancelled: 'info',
    completed: '',
    expired: 'danger',
  };
  return types[status] || '';
};

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: '待确认',
    confirmed: '已确认',
    cancelled: '已取消',
    completed: '已完成',
    expired: '已过期',
  };
  return texts[status] || status;
};

const getDepositStatusText = (status?: string) => {
  const texts: Record<string, string> = {
    unpaid: '未支付',
    paid: '已支付',
    refunded: '已退还',
    forfeited: '已没收',
  };
  return texts[status || 'unpaid'] || '未知';
};

const getDepositStatusType = (status?: string) => {
  const types: Record<string, string> = {
    unpaid: 'warning',
    paid: 'success',
    refunded: 'info',
    forfeited: 'danger',
  };
  return types[status || 'unpaid'] || '';
};

const formatDate = (date: string) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('zh-CN');
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const tableRowClassName = ({ row }: { row: Reservation }) => {
  if (row.status === 'pending') return 'warning-row';
  if (row.status === 'confirmed') return 'success-row';
  return '';
};

const handleFilter = () => {
  currentPage.value = 1;
  fetchData();
};

const resetFilter = () => {
  filters.value = { status: '', date: '' };
  currentPage.value = 1;
  fetchData();
};

const handlePageChange = (page: number) => {
  currentPage.value = page;
  fetchData();
};

const handleSizeChange = (size: number) => {
  if (store.pagination) {
    store.pagination.page_size = size;
  }
  fetchData();
};

const refreshData = () => {
  fetchData();
  ElMessage.success('数据已刷新');
};

const viewDetail = async (reservation: Reservation) => {
  detailDialogVisible.value = true;
  loadingDetail.value = true;
  selectedReservation.value = reservation;

  try {
    // 重新获取完整的预约详情
    const response = await adminReservationApi.getDetail(reservation.id);
    if (response.code === 200 && response.data) {
      selectedReservation.value = response.data;
    }
  } catch (error: any) {
    console.error('获取预约详情失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取预约详情失败');
  } finally {
    loadingDetail.value = false;
  }
};

const confirmReservation = async (id: number) => {
  try {
    // 根据定金启用状态决定提示消息
    const confirmMessage = depositEnabled.value
      ? '确认要确认此预约吗？只有已支付定金的预约才能确认。'
      : '确认要确认此预约吗？';
    
    await ElMessageBox.confirm(confirmMessage, '提示', {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'warning',
    });

    const response = await adminReservationApi.confirm(id);
    if (response.code === 200) {
      ElMessage.success('预约已确认');
      await fetchData();
    } else {
      ElMessage.error(response.message || '确认失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('确认预约失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '确认失败');
    }
  }
};

const cancelReservation = async (id: number) => {
  try {
    await ElMessageBox.prompt('请输入取消原因（可选）', '取消预约', {
      confirmButtonText: '确认取消',
      cancelButtonText: '取消',
      type: 'warning',
      inputPlaceholder: '请输入取消原因',
    }).then(async ({ value }) => {
      const response = await adminReservationApi.cancel(id, value || undefined);
      if (response.code === 200) {
        ElMessage.success('预约已取消');
        await fetchData();
        if (detailDialogVisible.value && selectedReservation.value?.id === id) {
          detailDialogVisible.value = false;
        }
      } else {
        ElMessage.error(response.message || '取消失败');
      }
    });
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('取消预约失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '取消失败');
    }
  }
};

const loadDepositConfig = async () => {
  try {
    const response = await configApi.getDetail('reservation_deposit_enabled');
    if (response.code === 200 && response.data?.config) {
      depositEnabled.value = Boolean(response.data.config.value);
    }
  } catch (error: any) {
    console.error('获取定金配置失败:', error);
    // 默认启用定金功能
    depositEnabled.value = true;
  }
};

const fetchData = () => {
  // 检查是否有token，没有token则不调用API（使用 sessionStorage）
  const token = sessionStorage.getItem('admin_token');
  if (!token) {
    console.warn('没有token，跳过获取预约列表');
    return;
  }
  
  store.fetchReservations({
    status: filters.value.status || undefined,
    date: filters.value.date || undefined,
    page: currentPage.value,
    page_size: store.pagination?.page_size || 20,
  });
};

onMounted(() => {
  // 使用 useRoute 检查当前路由
  if (route.path !== '/reservations' && route.name !== 'Reservations') {
    return;
  }
  
  loadDepositConfig();
  fetchData();
});
</script>

<style scoped>
:deep(.warning-row) {
  background-color: #fef0f0;
}

:deep(.success-row) {
  background-color: #f0f9ff;
}

:deep(.el-table) {
  border-radius: 8px;
  overflow: hidden;
}

:deep(.el-table th) {
  background-color: #f8fafc;
  font-weight: 600;
}
</style>
