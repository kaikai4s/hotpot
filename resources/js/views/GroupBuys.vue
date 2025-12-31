/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">团购管理</h1>
          <p class="text-gray-600">管理和配置团购活动</p>
        </div>
        <el-button type="primary" size="large" @click="handleAdd">
          <el-icon><Plus /></el-icon>
          添加团购
        </el-button>
      </div>

      <!-- 搜索栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索套餐名称"
          clearable
          class="flex-1"
          @clear="handleSearch"
          @keyup.enter="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="selectedStatus" placeholder="选择状态" clearable style="width: 150px" @change="handleSearch">
          <el-option label="草稿" value="draft" />
          <el-option label="已发布" value="published" />
          <el-option label="进行中" value="ongoing" />
          <el-option label="已结束" value="ended" />
          <el-option label="已取消" value="cancelled" />
        </el-select>
        <el-select v-model="selectedActive" placeholder="启用状态" clearable style="width: 150px" @change="handleSearch">
          <el-option label="启用" :value="true" />
          <el-option label="禁用" :value="false" />
        </el-select>
        <el-button type="primary" @click="handleSearch">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>

      <!-- 表格 -->
      <el-table
        v-loading="loading"
        :data="groupBuys"
        stripe
        style="width: 100%"
        class="mb-4"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="套餐信息" min-width="200">
          <template #default="{ row }">
            <div class="flex items-center gap-3">
              <img
                v-if="row.image_url"
                :src="row.image_url"
                :alt="row.name"
                class="w-16 h-16 object-cover rounded-lg"
                @error="(e) => { (e.target as HTMLImageElement).style.display = 'none'; }"
              />
              <div>
                <div class="font-semibold text-gray-900">{{ row.name }}</div>
                <div v-if="row.description" class="text-xs text-gray-500 mt-1 line-clamp-1">{{ row.description }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="价格" width="150">
          <template #default="{ row }">
            <div>
              <div class="text-sm text-gray-500 line-through">¥{{ row.original_price }}</div>
              <div class="text-lg font-bold text-red-600">¥{{ row.group_price }}</div>
              <div class="text-xs text-green-600">省¥{{ (row.original_price - row.group_price).toFixed(2) }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="库存/销量" width="120">
          <template #default="{ row }">
            <div class="text-sm">
              <div>库存：<span :class="row.stock === 0 ? 'text-gray-400' : 'text-green-600'">{{ row.stock === 0 ? '不限' : row.stock }}</span></div>
              <div>已售：<span class="text-blue-600">{{ row.sold_count }}</span></div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="订单统计" width="150">
          <template #default="{ row }">
            <div class="text-sm">
              <div>总订单：<span class="text-gray-600">{{ row.orders_count || 0 }}</span></div>
              <div>已支付：<span class="text-green-600">{{ row.paid_orders_count || 0 }}</span></div>
              <div>已使用：<span class="text-blue-600">{{ row.used_orders_count || 0 }}</span></div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="时间" width="200">
          <template #default="{ row }">
            <div class="text-sm">
              <div v-if="row.start_time">开始：{{ formatDate(row.start_time) }}</div>
              <div v-if="row.end_time">结束：{{ formatDate(row.end_time) }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTagType(row.status)">{{ getStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="is_active" label="启用" width="100">
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
            <el-button type="info" link @click="handleViewOrders(row)">订单</el-button>
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
      :title="editingId ? '编辑套餐' : '添加套餐'"
      width="900px"
      @close="handleDialogClose"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
      >
        <el-form-item label="套餐名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入套餐名称" />
        </el-form-item>
        <el-form-item label="套餐描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="套餐描述（可选）"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="套餐图片" prop="image_url">
          <el-input v-model="form.image_url" placeholder="图片URL（可选）" />
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="原价" prop="original_price">
              <el-input-number
                v-model="form.original_price"
                :min="0"
                :precision="2"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="套餐价" prop="group_price">
              <el-input-number
                v-model="form.group_price"
                :min="0"
                :precision="2"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="库存" prop="stock">
              <el-input-number
                v-model="form.stock"
                :min="0"
                :precision="0"
                style="width: 100%"
              />
              <div class="text-xs text-gray-500 mt-1">0表示不限制库存</div>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="每人限购" prop="limit_per_user">
              <el-input-number
                v-model="form.limit_per_user"
                :min="0"
                :precision="0"
                style="width: 100%"
              />
              <div class="text-xs text-gray-500 mt-1">0表示不限制</div>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="活动开始时间" prop="start_time">
              <el-date-picker
                v-model="form.start_time"
                type="datetime"
                placeholder="选择开始时间（可选）"
                value-format="YYYY-MM-DD HH:mm:ss"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="活动结束时间" prop="end_time">
              <el-date-picker
                v-model="form.end_time"
                type="datetime"
                placeholder="选择结束时间（可选）"
                value-format="YYYY-MM-DD HH:mm:ss"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="有效期开始" prop="valid_from">
              <el-date-picker
                v-model="form.valid_from"
                type="datetime"
                placeholder="购买后可使用时间（可选）"
                value-format="YYYY-MM-DD HH:mm:ss"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="有效期结束" prop="valid_to">
              <el-date-picker
                v-model="form.valid_to"
                type="datetime"
                placeholder="有效期结束时间（可选）"
                value-format="YYYY-MM-DD HH:mm:ss"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="有效期天数" prop="valid_days">
          <el-input-number
            v-model="form.valid_days"
            :min="0"
            :precision="0"
            style="width: 200px"
          />
          <div class="text-xs text-gray-500 mt-1 ml-2 inline-block">购买后N天内有效（0表示不限制，优先使用valid_to）</div>
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number
            v-model="form.sort_order"
            :min="0"
            :precision="0"
            style="width: 200px"
          />
          <div class="text-xs text-gray-500 mt-1 ml-2 inline-block">数字越小越靠前</div>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-select v-model="form.status" placeholder="请选择状态">
            <el-option label="草稿" value="draft" />
            <el-option label="已发布" value="published" />
            <el-option label="进行中" value="ongoing" />
            <el-option label="已结束" value="ended" />
            <el-option label="已取消" value="cancelled" />
          </el-select>
        </el-form-item>
        <el-form-item label="启用" prop="is_active">
          <el-switch v-model="form.is_active" />
        </el-form-item>
        <el-form-item label="包含菜品" prop="items">
          <div class="w-full">
            <div v-for="(item, index) in form.items" :key="index" class="flex gap-2 mb-2 items-end">
              <el-select
                v-model="item.dish_id"
                placeholder="选择菜品"
                filterable
                style="flex: 1"
                :loading="loadingDishes"
              >
                <el-option
                  v-for="dish in dishes"
                  :key="dish.id"
                  :label="`${dish.name} (¥${dish.price})`"
                  :value="dish.id"
                />
              </el-select>
              <el-input-number
                v-model="item.quantity"
                :min="1"
                :precision="0"
                placeholder="数量"
                style="width: 120px"
              />
              <el-input-number
                v-model="item.sort_order"
                :min="0"
                :precision="0"
                placeholder="排序"
                style="width: 100px"
              />
              <el-button type="danger" @click="removeItem(index)">删除</el-button>
            </div>
            <el-button type="primary" plain @click="addItem">添加菜品</el-button>
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 订单列表对话框 -->
    <el-dialog
      v-model="ordersDialogVisible"
      :title="`${selectedGroupBuy?.name} - 订单列表`"
      width="1000px"
    >
      <el-table
        v-loading="ordersLoading"
        :data="orders"
        stripe
        max-height="500"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="用户" width="200">
          <template #default="{ row }">
            <div>
              <div class="font-semibold">{{ row.user?.nickname || '未设置' }}</div>
              <div class="text-xs text-gray-500">{{ row.user?.phone || '未绑定' }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="quantity" label="数量" width="100" />
        <el-table-column prop="total_price" label="总价" width="120">
          <template #default="{ row }">¥{{ row.total_price }}</template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="120">
          <template #default="{ row }">
            <el-tag :type="getOrderStatusType(row.status)">{{ getOrderStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="paid_at" label="支付时间" width="180">
          <template #default="{ row }">
            {{ row.paid_at ? formatDate(row.paid_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="expires_at" label="过期时间" width="180">
          <template #default="{ row }">
            {{ row.expires_at ? formatDate(row.expires_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180">
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
import { groupBuyApi, type GroupBuy, type GroupBuyItem, type GroupBuyOrder } from '../api/admin/group-buy';

const loading = ref(false);
const saving = ref(false);
const ordersLoading = ref(false);
const loadingDishes = ref(false);
const groupBuys = ref<GroupBuy[]>([]);
const orders = ref<GroupBuyOrder[]>([]);
const dishes = ref<any[]>([]);
const searchKeyword = ref('');
const selectedStatus = ref('');
const selectedActive = ref<boolean | null>(null);
const currentPage = ref(1);
const pageSize = ref(15);
const total = ref(0);

const dialogVisible = ref(false);
const ordersDialogVisible = ref(false);
const editingId = ref<number | null>(null);
const selectedGroupBuy = ref<GroupBuy | null>(null);
const formRef = ref();

const form = ref<Partial<GroupBuy> & { items: GroupBuyItem[] }>({
  name: '',
  description: '',
  image_url: '',
  original_price: 0,
  group_price: 0,
  stock: 0,
  start_time: null,
  end_time: null,
  valid_from: null,
  valid_to: null,
  valid_days: 0,
  limit_per_user: 0,
  is_active: true,
  sort_order: 0,
  status: 'draft',
  items: [],
});

const rules = {
  name: [{ required: true, message: '请输入团购名称', trigger: 'blur' }],
  original_price: [{ required: true, message: '请输入原价', trigger: 'blur' }],
  group_price: [{ required: true, message: '请输入团购价', trigger: 'blur' }],
  items: [
    {
      validator: (rule: any, value: GroupBuyItem[], callback: Function) => {
        if (!value || value.length === 0) {
          callback(new Error('请至少添加一个菜品'));
        } else if (value.some(item => !item.dish_id)) {
          callback(new Error('请选择所有菜品'));
        } else {
          callback();
        }
      },
      trigger: 'change',
    },
  ],
};

const getStatusText = (status: string) => {
  const map: Record<string, string> = {
    draft: '草稿',
    published: '已发布',
    ongoing: '进行中',
    ended: '已结束',
    cancelled: '已取消',
  };
  return map[status] || status;
};

const getStatusTagType = (status: string) => {
  const map: Record<string, string> = {
    draft: 'info',
    published: 'success',
    ongoing: 'warning',
    ended: '',
    cancelled: 'danger',
  };
  return map[status] || '';
};

const getOrderStatusText = (status: string) => {
  const map: Record<string, string> = {
    pending: '待支付',
    paid: '已支付',
    used: '已使用',
    expired: '已过期',
    refunded: '已退款',
  };
  return map[status] || status;
};

const getOrderStatusType = (status: string) => {
  const map: Record<string, string> = {
    pending: 'warning',
    paid: 'success',
    used: 'info',
    expired: 'danger',
    refunded: '',
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
    const response = await groupBuyApi.getList({
      search: searchKeyword.value || undefined,
      status: selectedStatus.value || undefined,
      is_active: selectedActive.value !== null ? selectedActive.value : undefined,
      per_page: pageSize.value,
      page: currentPage.value,
    });

    if (response.code === 200 && response.data) {
      groupBuys.value = response.data.group_buys;
      total.value = response.data.pagination.total;
    }
    } catch (error: any) {
      console.error('获取套餐列表失败:', error);
      ElMessage.error('获取套餐列表失败');
    } finally {
    loading.value = false;
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

const handleSearch = () => {
  currentPage.value = 1;
  fetchData();
};

const resetSearch = () => {
  searchKeyword.value = '';
  selectedStatus.value = '';
  selectedActive.value = null;
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

const addItem = () => {
  form.value.items!.push({
    dish_id: 0,
    quantity: 1,
    sort_order: form.value.items!.length,
  });
};

const removeItem = (index: number) => {
  form.value.items!.splice(index, 1);
};

const handleAdd = () => {
  editingId.value = null;
  form.value = {
    name: '',
    description: '',
    image_url: '',
    original_price: 0,
    group_price: 0,
    stock: 0,
    start_time: null,
    end_time: null,
    valid_from: null,
    valid_to: null,
    valid_days: 0,
    limit_per_user: 0,
    is_active: true,
    sort_order: 0,
    status: 'draft',
    items: [],
  };
  if (dishes.value.length === 0) {
    fetchDishes();
  }
  dialogVisible.value = true;
};

const handleEdit = async (groupBuy: GroupBuy) => {
  editingId.value = groupBuy.id;
  try {
    const response = await groupBuyApi.getDetail(groupBuy.id);
    if (response.code === 200 && response.data) {
      const data = response.data.group_buy;
      form.value = {
        ...data,
        items: data.items || [],
      };
      if (dishes.value.length === 0) {
        await fetchDishes();
      }
      dialogVisible.value = true;
    }
    } catch (error: any) {
      console.error('获取套餐详情失败:', error);
      ElMessage.error('获取套餐详情失败');
    }
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
      const submitData = {
        ...form.value,
        items: form.value.items!.map(item => ({
          dish_id: item.dish_id,
          quantity: item.quantity,
          sort_order: item.sort_order || 0,
        })),
      };

      if (editingId.value) {
        await groupBuyApi.update(editingId.value, submitData);
        ElMessage.success('套餐更新成功');
      } else {
        await groupBuyApi.create(submitData);
        ElMessage.success('套餐创建成功');
      }
      dialogVisible.value = false;
      fetchData();
    } catch (error: any) {
      console.error('保存套餐失败:', error);
      ElMessage.error(error.response?.data?.message || '保存失败');
    } finally {
      saving.value = false;
    }
  });
};

const handleToggleStatus = async (groupBuy: GroupBuy) => {
  try {
    await groupBuyApi.update(groupBuy.id, {
      is_active: groupBuy.is_active,
    });
    ElMessage.success('状态更新成功');
  } catch (error: any) {
    console.error('更新状态失败:', error);
    ElMessage.error('更新状态失败');
    groupBuy.is_active = !groupBuy.is_active;
  }
};

const handleDelete = async (groupBuy: GroupBuy) => {
  try {
    await ElMessageBox.confirm(`确定要删除套餐"${groupBuy.name}"吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });

    await groupBuyApi.delete(groupBuy.id);
    ElMessage.success('删除成功');
    fetchData();
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除套餐失败:', error);
      ElMessage.error('删除失败');
    }
  }
};

const handleViewOrders = async (groupBuy: GroupBuy) => {
  selectedGroupBuy.value = groupBuy;
  ordersDialogVisible.value = true;
  ordersLoading.value = true;

  try {
    const response = await groupBuyApi.getOrders(groupBuy.id);
    if (response.code === 200 && response.data) {
      orders.value = response.data.orders;
    }
  } catch (error: any) {
    console.error('获取订单列表失败:', error);
    ElMessage.error('获取订单列表失败');
  } finally {
    ordersLoading.value = false;
  }
};

onMounted(() => {
  fetchData();
  fetchDishes();
});
</script>

<style scoped>
:deep(.el-table) {
  font-size: 14px;
}
</style>

