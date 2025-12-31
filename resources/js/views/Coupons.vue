/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">优惠活动管理</h1>
          <p class="text-gray-600">管理和配置优惠券活动</p>
        </div>
        <el-button type="primary" size="large" @click="handleAdd">
          <el-icon><Plus /></el-icon>
          添加优惠券
        </el-button>
      </div>

      <!-- 搜索栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索优惠券名称"
          clearable
          class="flex-1"
          @clear="handleSearch"
          @keyup.enter="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="selectedType" placeholder="选择类型" clearable style="width: 150px" @change="handleSearch">
          <el-option label="固定金额" value="fixed_amount" />
          <el-option label="百分比折扣" value="percentage" />
          <el-option label="兑换菜品" value="dish_exchange" />
          <el-option label="新用户专享" value="new_user" />
          <el-option label="积分券" value="points" />
          <el-option label="折扣券(旧)" value="discount" />
          <el-option label="现金券(旧)" value="cash" />
        </el-select>
        <el-select v-model="selectedStatus" placeholder="选择状态" clearable style="width: 150px" @change="handleSearch">
          <el-option label="启用" :value="true" />
          <el-option label="禁用" :value="false" />
        </el-select>
        <el-button type="primary" @click="handleSearch">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>

      <!-- 表格 -->
      <el-table
        v-loading="loading"
        :data="coupons"
        stripe
        style="width: 100%"
        class="mb-4"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="优惠券名称" min-width="150" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.type)">{{ getTypeText(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="面额/折扣" width="150">
          <template #default="{ row }">
            <div v-if="row.type === 'percentage'" class="font-bold text-purple-600">{{ row.value }}%</div>
            <div v-else-if="row.type === 'dish_exchange'" class="font-bold text-blue-600">
              {{ row.dish?.name || '菜品' }} (¥{{ row.dish?.price || row.value }})
            </div>
            <div v-else-if="row.type === 'discount'" class="font-bold text-red-600">{{ row.value }}折</div>
            <div v-else-if="row.type === 'new_user'" class="font-bold text-pink-600">¥{{ row.value }}</div>
            <div v-else class="font-bold text-green-600">¥{{ row.value }}</div>
            <div v-if="row.min_amount > 0" class="text-xs text-gray-500 mt-1">最低消费¥{{ row.min_amount }}</div>
            <div v-if="row.is_new_user_only" class="text-xs text-pink-500 mt-1">仅限新用户</div>
          </template>
        </el-table-column>
        <el-table-column prop="points_required" label="所需积分" width="120" />
        <el-table-column prop="stock" label="库存" width="100" />
        <el-table-column label="使用情况" width="150">
          <template #default="{ row }">
            <div class="text-sm">
              <div>已用：<span class="text-red-600">{{ row.used_count || 0 }}</span></div>
              <div>未用：<span class="text-green-600">{{ row.unused_count || 0 }}</span></div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="有效期" width="200">
          <template #default="{ row }">
            <div class="text-sm">
              <div v-if="row.valid_from">开始：{{ formatDate(row.valid_from) }}</div>
              <div v-if="row.valid_to">结束：{{ formatDate(row.valid_to) }}</div>
              <div v-if="!row.valid_from && !row.valid_to" class="text-gray-400">永久有效</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="is_active" label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.is_active"
              @change="handleToggleStatus(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="250" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="info" link @click="handleViewUsage(row)">使用记录</el-button>
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
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

    <!-- 添加/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="editingId ? '编辑优惠券' : '添加优惠券'"
      width="600px"
      @close="handleDialogClose"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
      >
        <el-form-item label="优惠券名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入优惠券名称" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-select v-model="form.type" placeholder="请选择类型" @change="handleTypeChange">
            <el-option label="固定金额" value="fixed_amount" />
            <el-option label="百分比折扣" value="percentage" />
            <el-option label="兑换菜品" value="dish_exchange" />
            <el-option label="新用户专享" value="new_user" />
            <el-option label="积分券" value="points" />
            <el-option label="折扣券(旧)" value="discount" />
            <el-option label="现金券(旧)" value="cash" />
          </el-select>
        </el-form-item>
        <el-form-item v-if="form.type === 'dish_exchange'" label="选择菜品" prop="dish_id">
          <el-select
            v-model="form.dish_id"
            placeholder="请选择可兑换的菜品"
            filterable
            style="width: 100%"
            :loading="loadingDishes"
          >
            <el-option
              v-for="dish in dishes"
              :key="dish.id"
              :label="`${dish.name} (¥${dish.price})`"
              :value="dish.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item v-else label="面额/折扣" prop="value">
          <el-input-number
            v-model="form.value"
            :min="0"
            :max="form.type === 'percentage' ? 100 : form.type === 'discount' ? 10 : 99999"
            :precision="form.type === 'percentage' || form.type === 'discount' ? 1 : 2"
            style="width: 100%"
          />
          <div class="text-xs text-gray-500 mt-1">
            <span v-if="form.type === 'percentage'">请输入折扣百分比（如：10表示10%折扣）</span>
            <span v-else-if="form.type === 'discount'">请输入折扣值（如：8.5表示8.5折）</span>
            <span v-else>请输入金额</span>
          </div>
        </el-form-item>
        <el-form-item v-if="form.type !== 'dish_exchange'" label="最低使用金额" prop="min_amount">
          <el-input-number
            v-model="form.min_amount"
            :min="0"
            :max="99999"
            :precision="2"
            style="width: 100%"
          />
          <div class="text-xs text-gray-500 mt-1">订单金额需达到此金额才能使用优惠券（0表示不限制）</div>
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="优惠券描述（可选）"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="使用说明" prop="usage_instructions">
          <el-input
            v-model="form.usage_instructions"
            type="textarea"
            :rows="3"
            placeholder="使用说明（可选）"
            maxlength="1000"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="图片URL" prop="image_url">
          <el-input v-model="form.image_url" placeholder="优惠券图片URL（可选）" />
        </el-form-item>
        <el-form-item label="所需积分" prop="points_required">
          <el-input-number
            v-model="form.points_required"
            :min="0"
            :max="999999"
            :precision="0"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="库存" prop="stock">
          <el-input-number
            v-model="form.stock"
            :min="0"
            :max="999999"
            :precision="0"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="开始时间" prop="valid_from">
          <el-date-picker
            v-model="form.valid_from"
            type="datetime"
            placeholder="选择开始时间（可选）"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="结束时间" prop="valid_to">
          <el-date-picker
            v-model="form.valid_to"
            type="datetime"
            placeholder="选择结束时间（可选）"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="仅限新用户" prop="is_new_user_only">
          <el-switch v-model="form.is_new_user_only" />
          <span class="text-sm text-gray-500 ml-2">开启后，只有新用户（未下过单）可以领取</span>
        </el-form-item>
        <el-form-item label="状态" prop="is_active">
          <el-switch v-model="form.is_active" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 使用记录对话框 -->
    <el-dialog
      v-model="usageDialogVisible"
      :title="`${selectedCoupon?.name} - 使用记录`"
      width="900px"
    >
      <el-table
        v-loading="usageLoading"
        :data="usageRecords"
        stripe
        max-height="500"
      >
        <el-table-column prop="code" label="优惠券码" width="150" />
        <el-table-column label="用户" width="200">
          <template #default="{ row }">
            <div>
              <div class="font-semibold">{{ row.user?.nickname || '未设置' }}</div>
              <div class="text-xs text-gray-500">{{ row.user?.phone || '未绑定' }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="expires_at" label="过期时间" width="180">
          <template #default="{ row }">
            {{ row.expires_at ? formatDate(row.expires_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="used_at" label="使用时间" width="180">
          <template #default="{ row }">
            {{ row.used_at ? formatDate(row.used_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="领取时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
      </el-table>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Search } from '@element-plus/icons-vue';
import { adminCouponApi, type Coupon, type UserCoupon } from '../api/coupon';

const loading = ref(false);
const saving = ref(false);
const usageLoading = ref(false);
const loadingDishes = ref(false);
const coupons = ref<Coupon[]>([]);
const usageRecords = ref<UserCoupon[]>([]);
const dishes = ref<any[]>([]);
const searchKeyword = ref('');
const selectedType = ref('');
const selectedStatus = ref<boolean | null>(null);
const currentPage = ref(1);
const pageSize = ref(15);
const total = ref(0);

const dialogVisible = ref(false);
const usageDialogVisible = ref(false);
const editingId = ref<number | null>(null);
const selectedCoupon = ref<Coupon | null>(null);
const formRef = ref();

const form = ref<Partial<Coupon>>({
  name: '',
  type: 'fixed_amount',
  value: 0,
  dish_id: null,
  min_amount: 0,
  points_required: 0,
  stock: 0,
  valid_from: null,
  valid_to: null,
  is_active: true,
  description: '',
  usage_instructions: '',
  image_url: '',
});

const rules = {
  name: [{ required: true, message: '请输入优惠券名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }],
  dish_id: [
    {
      validator: (rule: any, value: any, callback: Function) => {
        if (form.value.type === 'dish_exchange' && !value) {
          callback(new Error('请选择可兑换的菜品'));
        } else {
          callback();
        }
      },
      trigger: 'change',
    },
  ],
  value: [
    {
      validator: (rule: any, value: number, callback: Function) => {
        if (form.value.type === 'dish_exchange') {
          callback(); // 兑换菜品类型不需要value
        } else if (form.value.type === 'percentage' && (value <= 0 || value > 100)) {
          callback(new Error('折扣百分比应在0-100之间'));
        } else if (form.value.type === 'discount' && (value <= 0 || value > 10)) {
          callback(new Error('折扣值应在0-10之间'));
        } else if (!form.value.type || value <= 0) {
          callback(new Error('金额必须大于0'));
        } else {
          callback();
        }
      },
      trigger: 'blur',
    },
  ],
  points_required: [{ required: true, message: '请输入所需积分', trigger: 'blur' }],
  stock: [{ required: true, message: '请输入库存', trigger: 'blur' }],
};

const getTypeText = (type: string) => {
  const map: Record<string, string> = {
    fixed_amount: '固定金额',
    percentage: '百分比折扣',
    dish_exchange: '兑换菜品',
    new_user: '新用户专享',
    points: '积分券',
    discount: '折扣券(旧)',
    cash: '现金券(旧)',
  };
  return map[type] || type;
};

const getTypeTagType = (type: string) => {
  const map: Record<string, string> = {
    fixed_amount: 'success',
    percentage: 'warning',
    dish_exchange: 'info',
    new_user: 'danger',
    points: 'primary',
    discount: 'warning',
    cash: 'success',
  };
  return map[type] || '';
};

const handleTypeChange = () => {
  // 切换类型时重置相关字段
  if (form.value.type !== 'dish_exchange') {
    form.value.dish_id = null;
  }
  if (form.value.type === 'dish_exchange' && dishes.value.length === 0) {
    fetchDishes();
  }
};

const fetchDishes = async () => {
  if (loadingDishes.value) return;
  loadingDishes.value = true;
  try {
    const { adminApiClient } = await import('../api/admin-client');
    const response = await adminApiClient.get('/admin/v1/dishes', { params: { status: 'available', per_page: 1000 } });
    if (response.code === 200 && response.data) {
      dishes.value = response.data.dishes || [];
    }
  } catch (error) {
    console.error('获取菜品列表失败:', error);
  } finally {
    loadingDishes.value = false;
  }
};

const getStatusText = (status: string) => {
  const map: Record<string, string> = {
    unused: '未使用',
    used: '已使用',
    expired: '已过期',
  };
  return map[status] || status;
};

const getStatusType = (status: string) => {
  const map: Record<string, string> = {
    unused: 'success',
    used: 'info',
    expired: 'danger',
  };
  return map[status] || '';
};

const formatDate = (date: string) => {
  if (!date) return '';
  return new Date(date).toLocaleString('zh-CN');
};

const fetchData = async () => {
  loading.value = true;
  try {
    const response = await adminCouponApi.getList({
      search: searchKeyword.value || undefined,
      type: selectedType.value || undefined,
      is_active: selectedStatus.value !== null ? selectedStatus.value : undefined,
      per_page: pageSize.value,
    });

    if (response.code === 200 && response.data) {
      coupons.value = response.data.coupons;
      total.value = response.data.pagination.total;
    }
  } catch (error: any) {
    console.error('获取优惠券列表失败:', error);
    ElMessage.error('获取优惠券列表失败');
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  fetchData();
};

const resetSearch = () => {
  searchKeyword.value = '';
  selectedType.value = '';
  selectedStatus.value = null;
  handleSearch();
};

const handleSizeChange = (size: number) => {
  pageSize.value = size;
  fetchData();
};

const handlePageChange = (page: number) => {
  currentPage.value = page;
  fetchData();
};

const handleAdd = () => {
  editingId.value = null;
  form.value = {
    name: '',
    type: 'fixed_amount',
    value: 0,
    dish_id: null,
    min_amount: 0,
    points_required: 0,
    stock: 0,
    valid_from: null,
    valid_to: null,
    is_active: true,
    is_new_user_only: false,
    description: '',
    usage_instructions: '',
    image_url: '',
  };
  dialogVisible.value = true;
};

const handleEdit = (coupon: Coupon) => {
  editingId.value = coupon.id;
  form.value = { ...coupon };
  dialogVisible.value = true;
};

const handleDialogClose = () => {
  editingId.value = null;
  formRef.value?.resetFields();
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    saving.value = true;
    try {
      if (editingId.value) {
        await adminCouponApi.update(editingId.value, form.value);
        ElMessage.success('更新成功');
      } else {
        await adminCouponApi.create(form.value as any);
        ElMessage.success('创建成功');
      }
      dialogVisible.value = false;
      fetchData();
    } catch (error: any) {
      console.error('保存优惠券失败:', error);
      ElMessage.error(error.response?.data?.message || '保存失败');
    } finally {
      saving.value = false;
    }
  });
};

const handleToggleStatus = async (coupon: Coupon) => {
  try {
    await adminCouponApi.update(coupon.id, {
      is_active: coupon.is_active,
    });
    ElMessage.success('状态更新成功');
  } catch (error: any) {
    console.error('更新状态失败:', error);
    ElMessage.error('更新状态失败');
    coupon.is_active = !coupon.is_active;
  }
};

const handleDelete = async (coupon: Coupon) => {
  try {
    await ElMessageBox.confirm(`确定要删除优惠券"${coupon.name}"吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });

    await adminCouponApi.delete(coupon.id);
    ElMessage.success('删除成功');
    fetchData();
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除优惠券失败:', error);
      ElMessage.error('删除失败');
    }
  }
};

const handleViewUsage = async (coupon: Coupon) => {
  selectedCoupon.value = coupon;
  usageDialogVisible.value = true;
  usageLoading.value = true;

  try {
    const response = await adminCouponApi.getUsage(coupon.id);
    if (response.code === 200 && response.data) {
      usageRecords.value = response.data.usage;
    }
  } catch (error: any) {
    console.error('获取使用记录失败:', error);
    ElMessage.error('获取使用记录失败');
  } finally {
    usageLoading.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
:deep(.el-table) {
  font-size: 14px;
}
</style>

