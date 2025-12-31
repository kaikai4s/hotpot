/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">订单管理</h1>
          <p class="text-gray-600">管理和查看所有订单记录</p>
        </div>
        <el-button type="primary" size="large" @click="refreshData">
          <el-icon><Refresh /></el-icon>
          刷新
        </el-button>
      </div>

      <!-- 筛选栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg flex-wrap">
        <el-select 
          v-model="filters.status" 
          placeholder="订单状态" 
          clearable 
          class="w-48"
          @change="handleFilter"
        >
          <el-option label="待支付" value="pending" />
          <el-option label="已支付" value="paid" />
          <el-option label="待评价" value="pending_review" />
          <el-option label="已完成" value="completed" />
          <el-option label="已取消" value="cancelled" />
        </el-select>
        <el-select 
          v-model="filters.payment_method" 
          placeholder="支付方式" 
          clearable 
          class="w-48"
          @change="handleFilter"
        >
          <el-option label="微信支付" value="wechat" />
          <el-option label="模拟支付" value="mock" />
        </el-select>
        <el-input
          v-model="filters.order_no"
          placeholder="订单号"
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
        :data="orders"
        stripe
        style="width: 100%"
        class="mb-4"
        :row-class-name="tableRowClassName"
      >
        <el-table-column prop="order_no" label="订单号" width="180" />
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
            <el-tag v-if="row.table" type="info">{{ row.table.name }}</el-tag>
            <span v-else class="text-gray-400">未指定</span>
          </template>
        </el-table-column>
        <el-table-column label="商品数量" width="100">
          <template #default="{ row }">
            <span>{{ row.items?.length || 0 }} 件</span>
          </template>
        </el-table-column>
        <el-table-column prop="total_amount" label="订单金额" width="120">
          <template #default="{ row }">
            <span class="text-red-600 font-semibold">¥{{ row.total_amount }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="payment_method" label="支付方式" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.payment_method === 'wechat'" type="success">微信支付</el-tag>
            <el-tag v-else-if="row.payment_method === 'mock'" type="info">模拟支付</el-tag>
            <span v-else class="text-gray-400">未支付</span>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="订单状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" effect="dark">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="paid_at" label="支付时间" width="180">
          <template #default="{ row }">
            {{ row.paid_at ? formatDateTime(row.paid_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="viewDetail(row)">
              <el-icon><View /></el-icon>
              详情
            </el-button>
            <el-button 
              v-if="row.status === 'paid' || row.status === 'pending_review'" 
              link 
              type="success" 
              size="small" 
              @click="completeOrder(row.id)"
            >
              完成
            </el-button>
            <el-button 
              v-if="row.status !== 'cancelled' && row.status !== 'completed'" 
              link 
              type="danger" 
              size="small" 
              @click="cancelOrder(row.id)"
            >
              取消
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

    <!-- 订单详情对话框 -->
    <el-dialog
      v-model="showDetailDialog"
      title="订单详情"
      width="800px"
      :close-on-click-modal="false"
    >
      <div v-if="selectedOrder" class="space-y-4">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="订单号">{{ selectedOrder.order_no }}</el-descriptions-item>
          <el-descriptions-item label="订单状态">
            <el-tag :type="getStatusType(selectedOrder.status)" effect="dark">
              {{ getStatusText(selectedOrder.status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="用户">{{ selectedOrder.user?.nickname || '未知用户' }}</el-descriptions-item>
          <el-descriptions-item label="联系电话">{{ selectedOrder.user?.phone || '-' }}</el-descriptions-item>
          <el-descriptions-item label="桌位">
            <el-tag v-if="selectedOrder.table" type="info">{{ selectedOrder.table.name }}</el-tag>
            <span v-else>未指定</span>
          </el-descriptions-item>
          <el-descriptions-item label="支付方式">
            <el-tag v-if="selectedOrder.payment_method === 'wechat'" type="success">微信支付</el-tag>
            <el-tag v-else-if="selectedOrder.payment_method === 'mock'" type="info">模拟支付</el-tag>
            <span v-else>未支付</span>
          </el-descriptions-item>
          <el-descriptions-item label="订单金额">
            <span class="text-red-600 font-bold text-lg">¥{{ selectedOrder.total_amount }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="支付时间">
            {{ selectedOrder.paid_at ? formatDateTime(selectedOrder.paid_at) : '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="创建时间">{{ formatDateTime(selectedOrder.created_at) }}</el-descriptions-item>
          <el-descriptions-item label="完成时间">
            {{ selectedOrder.completed_at ? formatDateTime(selectedOrder.completed_at) : '-' }}
          </el-descriptions-item>
        </el-descriptions>

        <div class="mt-4">
          <h3 class="text-lg font-semibold mb-3">商品清单</h3>
          <el-table :data="selectedOrder.items" border>
            <el-table-column prop="dish.name" label="菜品名称" />
            <el-table-column prop="quantity" label="数量" width="80" />
            <el-table-column prop="price" label="单价" width="100">
              <template #default="{ row }">
                ¥{{ row.price }}
              </template>
            </el-table-column>
            <el-table-column prop="subtotal" label="小计" width="100">
              <template #default="{ row }">
                <span class="text-red-600 font-semibold">¥{{ row.subtotal }}</span>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Refresh, View } from '@element-plus/icons-vue';
import { adminOrderApi, type Order } from '../api/admin/order';

const loading = ref(false);
const orders = ref<Order[]>([]);
const pagination = ref<{
  current_page: number;
  total_pages: number;
  total_count: number;
  page_size: number;
} | null>(null);

const filters = ref({
  status: '',
  payment_method: '',
  order_no: '',
  date_from: '',
  date_to: '',
});

const dateRange = ref<[string, string] | null>(null);
const currentPage = ref(1);
const showDetailDialog = ref(false);
const selectedOrder = ref<Order | null>(null);

const getStatusType = (status: string) => {
  const types: Record<string, string> = {
    pending: 'warning',
    paid: 'success',
    pending_review: 'primary',
    completed: '',
    cancelled: 'info',
  };
  return types[status] || '';
};

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: '待支付',
    paid: '已支付',
    pending_review: '待评价',
    completed: '已完成',
    cancelled: '已取消',
  };
  return texts[status] || status;
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const tableRowClassName = ({ row }: { row: Order }) => {
  if (row.status === 'pending') return 'warning-row';
  if (row.status === 'paid') return 'success-row';
  return '';
};

const handleDateRangeChange = (dates: [string, string] | null) => {
  if (dates) {
    filters.value.date_from = dates[0];
    filters.value.date_to = dates[1];
  } else {
    filters.value.date_from = '';
    filters.value.date_to = '';
  }
  handleFilter();
};

const handleFilter = () => {
  currentPage.value = 1;
  fetchData();
};

const resetFilter = () => {
  filters.value = {
    status: '',
    payment_method: '',
    order_no: '',
    date_from: '',
    date_to: '',
  };
  dateRange.value = null;
  currentPage.value = 1;
  fetchData();
};

const handlePageChange = (page: number) => {
  currentPage.value = page;
  fetchData();
};

const handleSizeChange = (size: number) => {
  if (pagination.value) {
    pagination.value.page_size = size;
  }
  fetchData();
};

const refreshData = () => {
  fetchData();
  ElMessage.success('数据已刷新');
};

const fetchData = async () => {
  loading.value = true;
  try {
    // 过滤掉空字符串的筛选条件
    const params: any = {
      page: currentPage.value,
      page_size: pagination.value?.page_size || 20,
    };
    
    if (filters.value.status) {
      params.status = filters.value.status;
    }
    if (filters.value.payment_method) {
      params.payment_method = filters.value.payment_method;
    }
    if (filters.value.order_no) {
      params.order_no = filters.value.order_no;
    }
    if (filters.value.date_from) {
      params.date_from = filters.value.date_from;
    }
    if (filters.value.date_to) {
      params.date_to = filters.value.date_to;
    }
    if (filters.value.user_id) {
      params.user_id = filters.value.user_id;
    }

    const response = await adminOrderApi.getOrders(params);

    if (response.code === 200 && response.data) {
      orders.value = response.data.orders || [];
      pagination.value = response.data.pagination || null;
    } else {
      ElMessage.error(response.message || '获取订单列表失败');
      orders.value = [];
      pagination.value = null;
    }
  } catch (error: any) {
    console.error('获取订单列表失败:', error);
    console.error('错误详情:', error.response?.data || error.message);
    ElMessage.error(error.response?.data?.message || error.message || '获取订单列表失败');
    orders.value = [];
    pagination.value = null;
  } finally {
    loading.value = false;
  }
};

const viewDetail = async (order: Order) => {
  try {
    const response = await adminOrderApi.getOrder(order.id);
    if (response.code === 200 && response.data) {
      selectedOrder.value = response.data;
      showDetailDialog.value = true;
    } else {
      ElMessage.error(response.message || '获取订单详情失败');
    }
  } catch (error: any) {
    console.error('获取订单详情失败:', error);
    ElMessage.error(error.message || '获取订单详情失败');
  }
};

const completeOrder = async (id: number) => {
  try {
    await ElMessageBox.confirm('确认完成此订单吗？', '提示', {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'warning',
    });

    const response = await adminOrderApi.complete(id);
    if (response.code === 200) {
      ElMessage.success('订单已完成');
      fetchData();
    } else {
      ElMessage.error(response.message || '完成订单失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('完成订单失败:', error);
      ElMessage.error(error.message || '完成订单失败');
    }
  }
};

const cancelOrder = async (id: number) => {
  try {
    await ElMessageBox.confirm('确认取消此订单吗？', '提示', {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'warning',
    });

    const response = await adminOrderApi.cancel(id);
    if (response.code === 200) {
      ElMessage.success('订单已取消');
      fetchData();
    } else {
      ElMessage.error(response.message || '取消订单失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('取消订单失败:', error);
      ElMessage.error(error.message || '取消订单失败');
    }
  }
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
.warning-row {
  background-color: #fef0e6;
}

.success-row {
  background-color: #f0f9ff;
}
</style>

