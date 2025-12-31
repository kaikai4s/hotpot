<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">定金管理</h1>
          <p class="text-gray-600">管理和查看所有预约定金记录</p>
        </div>
        <el-button type="primary" size="large" @click="fetchData">
          <el-icon><Refresh /></el-icon>
          刷新
        </el-button>
      </div>

      <!-- 统计卡片 -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border-l-4 border-blue-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 mb-1">待返还定金</p>
              <p class="text-2xl font-bold text-blue-600">¥{{ statistics.total_amount.toFixed(2) }}</p>
            </div>
            <el-icon class="text-4xl text-blue-500"><Money /></el-icon>
          </div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border-l-4 border-green-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 mb-1">已返还定金</p>
              <p class="text-2xl font-bold text-green-600">¥{{ statistics.refunded_amount.toFixed(2) }}</p>
            </div>
            <el-icon class="text-4xl text-green-500"><CircleCheck /></el-icon>
          </div>
        </div>
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border-l-4 border-red-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 mb-1">已没收定金</p>
              <p class="text-2xl font-bold text-red-600">¥{{ statistics.forfeited_amount.toFixed(2) }}</p>
            </div>
            <el-icon class="text-4xl text-red-500"><Warning /></el-icon>
          </div>
        </div>
      </div>

      <!-- 筛选栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-select 
          v-model="filters.deposit_status" 
          placeholder="定金状态" 
          clearable 
          class="w-48"
          @change="handleFilter"
        >
          <el-option label="未支付" value="unpaid" />
          <el-option label="已支付" value="paid" />
          <el-option label="已返还" value="refunded" />
          <el-option label="已没收" value="forfeited" />
        </el-select>
        <el-input
          v-model="filters.reservation_code"
          placeholder="预约编号"
          class="w-48"
          clearable
          @clear="handleFilter"
          @keyup.enter="handleFilter"
        />
        <el-date-picker
          v-model="filters.date_from"
          type="date"
          placeholder="开始日期"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          class="w-48"
          @change="handleFilter"
        />
        <el-date-picker
          v-model="filters.date_to"
          type="date"
          placeholder="结束日期"
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
        v-loading="loading"
        :data="reservations"
        stripe
        style="width: 100%"
        class="mb-4"
      >
        <el-table-column prop="reservation_code" label="预约编号" width="180" />
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
        <el-table-column prop="deposit_amount" label="定金金额" width="120" align="right">
          <template #default="{ row }">
            <span class="font-semibold text-red-600">¥{{ row.deposit_amount }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="deposit_status" label="定金状态" width="120">
          <template #default="{ row }">
            <el-tag :type="getDepositStatusType(row.deposit_status)" effect="dark">
              {{ getDepositStatusText(row.deposit_status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="deposit_paid_at" label="支付时间" width="180">
          <template #default="{ row }">
            {{ row.deposit_paid_at ? formatDateTime(row.deposit_paid_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="deposit_refunded_at" label="返还时间" width="180">
          <template #default="{ row }">
            {{ row.deposit_refunded_at ? formatDateTime(row.deposit_refunded_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="arrived_at" label="到达时间" width="180">
          <template #default="{ row }">
            {{ row.arrived_at ? formatDateTime(row.arrived_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="order.order_no" label="关联订单" width="180">
          <template #default="{ row }">
            <el-tag v-if="row.order" type="success" size="small">{{ row.order.order_no }}</el-tag>
            <span v-else class="text-gray-400">-</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button 
              v-if="row.deposit_status === 'paid' && !row.deposit_refunded_at"
              link 
              type="primary" 
              size="small" 
              @click="handleRefund(row)"
            >
              <el-icon><Money /></el-icon>
              返还定金
            </el-button>
            <span v-else class="text-gray-400 text-sm">-</span>
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
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </div>

    <!-- 返还定金对话框 -->
    <el-dialog
      v-model="refundDialogVisible"
      title="返还定金"
      width="500px"
    >
      <el-form :model="refundForm" label-width="100px">
        <el-form-item label="预约编号">
          <el-input v-model="refundForm.reservation_code" disabled />
        </el-form-item>
        <el-form-item label="定金金额">
          <el-input v-model="refundForm.deposit_amount" disabled>
            <template #prefix>¥</template>
          </el-input>
        </el-form-item>
        <el-form-item label="返还原因">
          <el-input
            v-model="refundForm.reason"
            type="textarea"
            :rows="3"
            placeholder="请输入返还原因（选填）"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="refundDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="refunding" @click="confirmRefund">确认返还</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Refresh, Money, CircleCheck, Warning } from '@element-plus/icons-vue';
import { adminDepositApi } from '../api/admin/deposit';
import type { Reservation } from '../types';

const loading = ref(false);
const refunding = ref(false);
const reservations = ref<Reservation[]>([]);
const statistics = ref({
  total_amount: 0,
  refunded_amount: 0,
  forfeited_amount: 0,
});
const pagination = ref({
  current_page: 1,
  page_size: 20,
  total_count: 0,
});
const filters = ref({
  deposit_status: '',
  reservation_code: '',
  date_from: '',
  date_to: '',
});
const refundDialogVisible = ref(false);
const refundForm = ref({
  reservation_id: 0,
  reservation_code: '',
  deposit_amount: 0,
  reason: '',
});

const formatDate = (date: string) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('zh-CN');
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '-';
  return new Date(datetime).toLocaleString('zh-CN');
};

const getDepositStatusText = (status: string) => {
  const texts: Record<string, string> = {
    unpaid: '未支付',
    paid: '已支付',
    refunded: '已返还',
    forfeited: '已没收',
  };
  return texts[status] || status;
};

const getDepositStatusType = (status: string) => {
  const types: Record<string, string> = {
    unpaid: 'info',
    paid: 'warning',
    refunded: 'success',
    forfeited: 'danger',
  };
  return types[status] || '';
};

const fetchData = async () => {
  loading.value = true;
  try {
    const params: any = {
      page: pagination.value.current_page,
      page_size: pagination.value.page_size,
    };

    if (filters.value.deposit_status) {
      params.deposit_status = filters.value.deposit_status;
    }
    if (filters.value.reservation_code) {
      params.reservation_code = filters.value.reservation_code;
    }
    if (filters.value.date_from) {
      params.date_from = filters.value.date_from;
    }
    if (filters.value.date_to) {
      params.date_to = filters.value.date_to;
    }

    const response = await adminDepositApi.getDeposits(params);
    
    console.log('定金列表API响应:', response);
    
    if (response && response.code === 200 && response.data) {
      reservations.value = response.data.reservations || [];
      pagination.value = {
        current_page: response.data.pagination.current_page,
        page_size: response.data.pagination.page_size,
        total_count: response.data.pagination.total_count,
      };
      statistics.value = response.data.statistics || {
        total_amount: 0,
        refunded_amount: 0,
        forfeited_amount: 0,
      };
    } else {
      console.error('API响应格式错误:', response);
      ElMessage.error(response?.message || '获取定金列表失败');
    }
  } catch (error: any) {
    console.error('获取定金列表失败:', error);
    console.error('错误详情:', error.response?.data || error.message);
    ElMessage.error(error.response?.data?.message || error.message || '获取定金列表失败');
  } finally {
    loading.value = false;
  }
};

const handleFilter = () => {
  pagination.value.current_page = 1;
  fetchData();
};

const resetFilter = () => {
  filters.value = {
    deposit_status: '',
    reservation_code: '',
    date_from: '',
    date_to: '',
  };
  handleFilter();
};

const handleSizeChange = (size: number) => {
  pagination.value.page_size = size;
  pagination.value.current_page = 1;
  fetchData();
};

const handlePageChange = (page: number) => {
  pagination.value.current_page = page;
  fetchData();
};

const handleRefund = (reservation: Reservation) => {
  refundForm.value = {
    reservation_id: reservation.id,
    reservation_code: reservation.reservation_code,
    deposit_amount: reservation.deposit_amount || 0,
    reason: '',
  };
  refundDialogVisible.value = true;
};

const confirmRefund = async () => {
  if (!refundForm.value.reservation_id) {
    ElMessage.error('预约信息无效');
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确认要返还预约 ${refundForm.value.reservation_code} 的定金 ¥${refundForm.value.deposit_amount} 吗？`,
      '确认返还',
      {
        confirmButtonText: '确认返还',
        cancelButtonText: '取消',
        type: 'warning',
      }
    );

    refunding.value = true;
    const response = await adminDepositApi.refundDeposit(
      refundForm.value.reservation_id,
      refundForm.value.reason || undefined
    );

    if (response.code === 200) {
      ElMessage.success('定金返还成功');
      refundDialogVisible.value = false;
      await fetchData();
    } else {
      ElMessage.error(response.message || '返还定金失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('返还定金失败:', error);
      ElMessage.error(error.message || '返还定金失败');
    }
  } finally {
    refunding.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

