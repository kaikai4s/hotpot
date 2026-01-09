/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">兑换记录管理</h1>
          <p class="text-gray-600">管理用户积分兑换记录</p>
        </div>
      </div>

      <!-- 搜索栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-select v-model="selectedStatus" placeholder="兑换状态" clearable style="width: 150px" @change="handleSearch">
          <el-option label="待处理" value="pending" />
          <el-option label="已发货" value="shipped" />
          <el-option label="已完成" value="completed" />
          <el-option label="已取消" value="cancelled" />
        </el-select>
        <el-button type="primary" @click="handleSearch">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>

      <!-- 表格 -->
      <el-table v-loading="loading" :data="redemptions" stripe border style="width: 100%" class="mb-4">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="用户" width="180">
          <template #default="{ row }">
            <div v-if="row.user">
              <div class="font-semibold">{{ row.user.nickname || '未设置' }}</div>
              <div class="text-xs text-gray-500">{{ row.user.phone || '未绑定' }}</div>
            </div>
            <span v-else class="text-gray-400">用户已删除</span>
          </template>
        </el-table-column>
        <el-table-column label="商品" min-width="200">
          <template #default="{ row }">
            <div v-if="row.product" class="flex items-center gap-2">
              <el-image
                v-if="row.product.image_url"
                :src="row.product.image_url"
                fit="cover"
                class="w-10 h-10 rounded"
              />
              <div>
                <div class="font-semibold">{{ row.product.name }}</div>
                <div class="text-xs text-gray-500">
                  <el-tag size="small" :type="row.product.type === 'physical' ? 'primary' : 'success'">
                    {{ row.product.type === 'physical' ? '实物' : '体验' }}
                  </el-tag>
                </div>
              </div>
            </div>
            <span v-else class="text-gray-400">商品已删除</span>
          </template>
        </el-table-column>
        <el-table-column prop="points_used" label="消耗积分" width="100">
          <template #default="{ row }">
            <span class="font-bold text-orange-600">{{ row.points_used }}</span>
          </template>
        </el-table-column>
        <el-table-column label="收货地址" min-width="200">
          <template #default="{ row }">
            <span v-if="row.shipping_address">{{ row.shipping_address }}</span>
            <span v-else class="text-gray-400">{{ row.product?.type === 'experience' ? '体验类无需地址' : '未填写' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="tracking_number" label="物流单号" width="150">
          <template #default="{ row }">
            <span v-if="row.tracking_number">{{ row.tracking_number }}</span>
            <span v-else class="text-gray-400">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="兑换时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <template v-if="row.status === 'pending'">
              <el-button type="primary" link @click="handleShip(row)">发货</el-button>
              <el-button type="danger" link @click="handleCancel(row)">取消</el-button>
            </template>
            <template v-else-if="row.status === 'shipped'">
              <el-button type="success" link @click="handleComplete(row)">完成</el-button>
            </template>
            <span v-else class="text-gray-400">-</span>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </div>

    <!-- 发货对话框 -->
    <el-dialog v-model="shipDialogVisible" title="发货" width="500px">
      <el-form ref="shipFormRef" :model="shipForm" :rules="shipRules" label-width="100px">
        <el-form-item label="物流单号" prop="tracking_number">
          <el-input v-model="shipForm.tracking_number" placeholder="请输入物流单号" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="shipDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleShipSubmit">确认发货</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { adminMallApi, type ProductRedemption } from '../api/admin-mall';

const loading = ref(false);
const saving = ref(false);
const redemptions = ref<ProductRedemption[]>([]);
const selectedStatus = ref('');
const currentPage = ref(1);
const pageSize = ref(15);
const total = ref(0);

const shipDialogVisible = ref(false);
const currentRedemption = ref<ProductRedemption | null>(null);
const shipFormRef = ref();
const shipForm = ref({ tracking_number: '' });
const shipRules = { tracking_number: [{ required: true, message: '请输入物流单号', trigger: 'blur' }] };

const getStatusText = (status: string) => {
  const map: Record<string, string> = { pending: '待处理', shipped: '已发货', completed: '已完成', cancelled: '已取消' };
  return map[status] || status;
};

const getStatusType = (status: string) => {
  const map: Record<string, string> = { pending: 'warning', shipped: 'primary', completed: 'success', cancelled: 'info' };
  return map[status] || '';
};

const formatDate = (date: string) => date ? new Date(date).toLocaleString('zh-CN') : '';

const fetchData = async () => {
  loading.value = true;
  try {
    const response = await adminMallApi.getRedemptions({
      status: selectedStatus.value || undefined,
      per_page: pageSize.value,
      page: currentPage.value,
    });
    if (response.code === 200 && response.data) {
      redemptions.value = response.data.redemptions;
      total.value = response.data.pagination.total;
    }
  } catch (error) {
    console.error('获取兑换记录失败:', error);
    ElMessage.error('获取兑换记录失败');
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => { currentPage.value = 1; fetchData(); };
const resetSearch = () => { selectedStatus.value = ''; handleSearch(); };
const handleSizeChange = () => fetchData();
const handlePageChange = () => fetchData();

const handleShip = (redemption: ProductRedemption) => {
  currentRedemption.value = redemption;
  shipForm.value = { tracking_number: '' };
  shipDialogVisible.value = true;
};

const handleShipSubmit = async () => {
  if (!shipFormRef.value || !currentRedemption.value) return;
  await shipFormRef.value.validate(async (valid: boolean) => {
    if (!valid) return;
    saving.value = true;
    try {
      await adminMallApi.updateRedemptionStatus(currentRedemption.value!.id, 'shipped', shipForm.value.tracking_number);
      ElMessage.success('发货成功');
      shipDialogVisible.value = false;
      fetchData();
    } catch (error: any) {
      ElMessage.error(error.response?.data?.message || '发货失败');
    } finally {
      saving.value = false;
    }
  });
};

const handleComplete = async (redemption: ProductRedemption) => {
  try {
    await ElMessageBox.confirm('确定将此兑换标记为已完成吗？', '提示', { type: 'info' });
    await adminMallApi.updateRedemptionStatus(redemption.id, 'completed');
    ElMessage.success('操作成功');
    fetchData();
  } catch (error: any) {
    if (error !== 'cancel') ElMessage.error('操作失败');
  }
};

const handleCancel = async (redemption: ProductRedemption) => {
  try {
    await ElMessageBox.confirm('确定要取消此兑换吗？积分将退还给用户。', '提示', { type: 'warning' });
    await adminMallApi.updateRedemptionStatus(redemption.id, 'cancelled');
    ElMessage.success('已取消');
    fetchData();
  } catch (error: any) {
    if (error !== 'cancel') ElMessage.error('操作失败');
  }
};

onMounted(() => fetchData());
</script>
