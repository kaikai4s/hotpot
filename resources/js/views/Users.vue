/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- 统计卡片 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <el-card shadow="hover" class="bg-gradient-to-br from-blue-500 to-blue-600 text-white">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm opacity-90 mb-1">总用户数</div>
            <div class="text-3xl font-bold">{{ statistics.total_users || 0 }}</div>
          </div>
          <el-icon class="text-4xl opacity-50"><UserIcon /></el-icon>
        </div>
      </el-card>
      <el-card shadow="hover" class="bg-gradient-to-br from-green-500 to-green-600 text-white">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm opacity-90 mb-1">今日新增</div>
            <div class="text-3xl font-bold">{{ statistics.today_users || 0 }}</div>
          </div>
          <el-icon class="text-4xl opacity-50"><UserFilled /></el-icon>
        </div>
      </el-card>
      <el-card shadow="hover" class="bg-gradient-to-br from-purple-500 to-purple-600 text-white">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm opacity-90 mb-1">本月新增</div>
            <div class="text-3xl font-bold">{{ statistics.this_month_users || 0 }}</div>
          </div>
          <el-icon class="text-4xl opacity-50"><Star /></el-icon>
        </div>
      </el-card>
      <el-card shadow="hover" class="bg-gradient-to-br from-orange-500 to-orange-600 text-white">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm opacity-90 mb-1">有订单用户</div>
            <div class="text-3xl font-bold">{{ statistics.users_with_orders || 0 }}</div>
          </div>
          <el-icon class="text-4xl opacity-50"><ShoppingBag /></el-icon>
        </div>
      </el-card>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">用户管理</h1>
          <p class="text-gray-600">管理和查看所有前台用户</p>
        </div>
        <div class="flex gap-2">
          <el-button type="warning" :disabled="selectedUsers.length === 0" @click="handleBatchDelete">
            <el-icon><Delete /></el-icon>
            批量删除 ({{ selectedUsers.length }})
          </el-button>
          <el-button type="primary" @click="showAdvancedFilter = !showAdvancedFilter">
            <el-icon><Filter /></el-icon>
            高级筛选
          </el-button>
          <el-button type="primary" @click="fetchData">
            <el-icon><Refresh /></el-icon>
            刷新
          </el-button>
        </div>
      </div>

      <!-- 高级筛选 -->
      <el-collapse-transition>
        <div v-show="showAdvancedFilter" class="mb-6 p-4 bg-gray-50 rounded-lg">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <el-date-picker
              v-model="filterDateRange"
              type="daterange"
              range-separator="至"
              start-placeholder="注册开始日期"
              end-placeholder="注册结束日期"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              class="w-full"
            />
            <el-select v-model="filterGender" placeholder="性别筛选" clearable class="w-full">
              <el-option label="全部" value="" />
              <el-option label="男" :value="1" />
              <el-option label="女" :value="2" />
              <el-option label="未知" :value="0" />
            </el-select>
            <el-select v-model="filterHasPhone" placeholder="手机号筛选" clearable class="w-full">
              <el-option label="全部" value="" />
              <el-option label="已绑定" value="1" />
              <el-option label="未绑定" value="0" />
            </el-select>
            <el-input-number
              v-model="filterMinPoints"
              placeholder="最低积分"
              :min="0"
              class="w-full"
            />
            <el-input-number
              v-model="filterMaxPoints"
              placeholder="最高积分"
              :min="0"
              class="w-full"
            />
            <el-input-number
              v-model="filterMinOrders"
              placeholder="最少订单数"
              :min="0"
              class="w-full"
            />
            <el-input-number
              v-model="filterMaxOrders"
              placeholder="最多订单数"
              :min="0"
              class="w-full"
            />
          </div>
          <div class="mt-4 flex gap-2">
            <el-button type="primary" @click="handleAdvancedSearch">应用筛选</el-button>
            <el-button @click="resetAdvancedFilter">重置</el-button>
          </div>
        </div>
      </el-collapse-transition>

      <!-- 搜索栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索昵称、手机号、OpenID"
          clearable
          class="flex-1"
          @clear="handleSearch"
          @keyup.enter="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="sortBy" placeholder="排序方式" style="width: 150px" @change="handleSearch">
          <el-option label="注册时间" value="created_at" />
          <el-option label="积分" value="total_points" />
          <el-option label="订单数" value="orders_count" />
          <el-option label="评价数" value="reviews_count" />
        </el-select>
        <el-select v-model="sortOrder" placeholder="排序" style="width: 100px" @change="handleSearch">
          <el-option label="降序" value="desc" />
          <el-option label="升序" value="asc" />
        </el-select>
        <el-button type="primary" @click="handleSearch">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>

      <!-- 表格 -->
      <el-table
        v-loading="loading"
        :data="users"
        stripe
        style="width: 100%"
        class="mb-4"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="nickname" label="用户信息" width="200">
          <template #default="{ row }">
            <div class="flex items-center">
              <el-avatar v-if="row.avatar_url" :src="row.avatar_url" :size="40" class="mr-2" />
              <div>
                <div class="font-medium">{{ row.nickname || '未设置' }}</div>
                <div class="text-xs text-gray-500">{{ row.phone || '未绑定手机' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="统计信息" width="280">
          <template #default="{ row }">
            <div v-if="row.statistics" class="text-sm">
              <div class="flex items-center gap-4">
                <div>
                  <span class="text-gray-500">积分:</span>
                  <el-tag type="warning" size="small" class="ml-1">{{ row.statistics.total_points }}</el-tag>
                </div>
                <div>
                  <span class="text-gray-500">段位:</span>
                  <el-tag type="success" size="small" class="ml-1">{{ getLevelName(row.statistics.level) }}</el-tag>
                </div>
              </div>
              <div class="flex items-center gap-4 mt-1">
                <div>
                  <span class="text-gray-500">订单:</span>
                  <span class="font-medium">{{ row.statistics.orders_count }}</span>
                </div>
                <div>
                  <span class="text-gray-500">评价:</span>
                  <span class="font-medium">{{ row.statistics.reviews_count }}</span>
                </div>
                <div>
                  <span class="text-gray-500">消费:</span>
                  <span class="font-medium text-green-600">¥{{ row.statistics.total_spent?.toFixed(2) || '0.00' }}</span>
                </div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="gender" label="性别" width="80">
          <template #default="{ row }">
            <el-tag v-if="row.gender === 1" type="primary">男</el-tag>
            <el-tag v-else-if="row.gender === 2" type="danger">女</el-tag>
            <el-tag v-else type="info">未知</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.is_active"
              :loading="updatingStatus[row.id]"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="注册时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleViewDetail(row)">详情</el-button>
            <el-button type="info" link @click="handleViewOrders(row)">订单</el-button>
            <el-button type="success" link @click="handleViewPoints(row)">积分</el-button>
            <el-button type="warning" link @click="handleEdit(row)">编辑</el-button>
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

    <!-- 编辑对话框 -->
    <el-dialog
      v-model="editDialogVisible"
      title="编辑用户"
      width="600px"
      @close="resetEditForm"
    >
      <el-form :model="editForm" :rules="editFormRules" ref="editFormRef" label-width="100px">
        <el-form-item label="昵称" prop="nickname">
          <el-input v-model="editForm.nickname" />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="editForm.phone" />
        </el-form-item>
        <el-form-item label="性别" prop="gender">
          <el-radio-group v-model="editForm.gender">
            <el-radio :label="0">未知</el-radio>
            <el-radio :label="1">男</el-radio>
            <el-radio :label="2">女</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="editForm.is_active" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="editForm.remark"
            type="textarea"
            :rows="3"
            placeholder="请输入备注信息"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>

    <!-- 用户详情对话框 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="用户详情"
      width="900px"
      @close="resetDetail"
    >
      <div v-if="userDetail" v-loading="detailLoading">
        <!-- 基本信息 -->
        <el-card class="mb-4">
          <template #header>
            <div class="flex items-center justify-between">
              <span class="font-bold">基本信息</span>
              <el-button type="primary" link @click="handleEdit(userDetail)">编辑</el-button>
            </div>
          </template>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <div class="text-gray-500 mb-1">用户ID</div>
              <div class="font-medium">{{ userDetail.id }}</div>
            </div>
            <div>
              <div class="text-gray-500 mb-1">昵称</div>
              <div class="font-medium">{{ userDetail.nickname || '未设置' }}</div>
            </div>
            <div>
              <div class="text-gray-500 mb-1">手机号</div>
              <div class="font-medium">{{ userDetail.phone || '未绑定' }}</div>
            </div>
            <div>
              <div class="text-gray-500 mb-1">性别</div>
              <div>
                <el-tag v-if="userDetail.gender === 1" type="primary">男</el-tag>
                <el-tag v-else-if="userDetail.gender === 2" type="danger">女</el-tag>
                <el-tag v-else type="info">未知</el-tag>
              </div>
            </div>
            <div>
              <div class="text-gray-500 mb-1">注册时间</div>
              <div class="font-medium">{{ formatDate(userDetail.created_at) }}</div>
            </div>
            <div>
              <div class="text-gray-500 mb-1">状态</div>
              <div>
                <el-tag v-if="userDetail.is_active" type="success">启用</el-tag>
                <el-tag v-else type="danger">禁用</el-tag>
              </div>
            </div>
          </div>
          <div v-if="userDetail.remark" class="mt-4">
            <div class="text-gray-500 mb-1">备注</div>
            <div class="text-sm">{{ userDetail.remark }}</div>
          </div>
        </el-card>

        <!-- 统计数据 -->
        <el-card class="mb-4">
          <template #header>
            <span class="font-bold">统计数据</span>
          </template>
          <div v-if="userDetail.statistics" class="grid grid-cols-3 gap-4">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
              <div class="text-2xl font-bold text-blue-600">{{ userDetail.statistics.total_points }}</div>
              <div class="text-sm text-gray-600 mt-1">总积分</div>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
              <div class="text-2xl font-bold text-green-600">{{ userDetail.statistics.orders_count }}</div>
              <div class="text-sm text-gray-600 mt-1">订单数</div>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg">
              <div class="text-2xl font-bold text-purple-600">¥{{ userDetail.statistics.orders_total_amount?.toFixed(2) || '0.00' }}</div>
              <div class="text-sm text-gray-600 mt-1">总消费</div>
            </div>
            <div class="text-center p-4 bg-orange-50 rounded-lg">
              <div class="text-2xl font-bold text-orange-600">{{ userDetail.statistics.reviews_count }}</div>
              <div class="text-sm text-gray-600 mt-1">评价数</div>
            </div>
            <div class="text-center p-4 bg-pink-50 rounded-lg">
              <div class="text-2xl font-bold text-pink-600">{{ userDetail.statistics.coupons_count }}</div>
              <div class="text-sm text-gray-600 mt-1">优惠券</div>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
              <div class="text-2xl font-bold text-yellow-600">{{ userDetail.statistics.reservations_count }}</div>
              <div class="text-sm text-gray-600 mt-1">预约数</div>
            </div>
          </div>
        </el-card>

        <!-- 最近订单 -->
        <el-card v-if="userDetail.orders && userDetail.orders.length > 0">
          <template #header>
            <span class="font-bold">最近订单</span>
          </template>
          <el-table :data="userDetail.orders" size="small">
            <el-table-column prop="order_no" label="订单号" />
            <el-table-column prop="total_amount" label="金额">
              <template #default="{ row }">¥{{ row.total_amount?.toFixed(2) }}</template>
            </el-table-column>
            <el-table-column prop="status" label="状态" />
            <el-table-column prop="created_at" label="时间">
              <template #default="{ row }">{{ formatDate(row.created_at) }}</template>
            </el-table-column>
          </el-table>
        </el-card>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Refresh, Search, Filter, Delete, User as UserIcon, UserFilled, Star, ShoppingBag } from '@element-plus/icons-vue';
import { userApi } from '../api/user';
import type { User, UserDetail, UserStatistics } from '../api/user';
import type { FormInstance, FormRules } from 'element-plus';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(false);
const detailLoading = ref(false);
const saving = ref(false);
const users = ref<User[]>([]);
const selectedUsers = ref<User[]>([]);
const total = ref(0);
const currentPage = ref(1);
const pageSize = ref(15);
const searchKeyword = ref('');
const editDialogVisible = ref(false);
const detailDialogVisible = ref(false);
const showAdvancedFilter = ref(false);
const editFormRef = ref<FormInstance | null>(null);
const editingUserId = ref<number | null>(null);
const userDetail = ref<UserDetail | null>(null);
const updatingStatus = reactive<Record<number, boolean>>({});

// 统计信息
const statistics = ref<UserStatistics>({
  total_users: 0,
  today_users: 0,
  this_month_users: 0,
  users_with_phone: 0,
  users_with_orders: 0,
  users_with_points: 0,
});

// 排序和筛选
const sortBy = ref('created_at');
const sortOrder = ref<'asc' | 'desc'>('desc');
const filterDateRange = ref<[string, string] | null>(null);
const filterGender = ref<number | ''>('');
const filterHasPhone = ref<'0' | '1' | ''>('');
const filterMinPoints = ref<number | null>(null);
const filterMaxPoints = ref<number | null>(null);
const filterMinOrders = ref<number | null>(null);
const filterMaxOrders = ref<number | null>(null);

const editForm = ref<Partial<User>>({
  nickname: '',
  phone: '',
  gender: 0,
  is_active: true,
  remark: '',
});

const editFormRules: FormRules = {
  nickname: [
    { max: 64, message: '昵称长度不能超过64个字符', trigger: 'blur' },
  ],
  phone: [
    { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号', trigger: 'blur' },
  ],
};

// 获取段位名称
const getLevelName = (levelCode: string): string => {
  const levelMap: Record<string, string> = {
    'heitie': '黑铁',
    'heitie2': '黑铁二',
    'heitie3': '黑铁三',
    'qingtong1': '青铜一',
    'qingtong2': '青铜二',
    'qingtong3': '青铜三',
    'baiyin1': '白银一',
    'baiyin2': '白银二',
    'baiyin3': '白银三',
    'huangjin1': '黄金一',
    'huangjin2': '黄金二',
    'huangjin3': '黄金三',
    'baijin1': '白金一',
    'baijin2': '白金二',
    'baijin3': '白金三',
    'zuanshi1': '钻石一',
    'zuanshi2': '钻石二',
    'zuanshi3': '钻石三',
    'chaofan1': '超凡一',
    'chaofan2': '超凡二',
    'chaofan3': '超凡三',
    'shenhua1': '神话一',
    'shenhua2': '神话二',
    'shenhua3': '神话三',
    'funeng': '赋能',
  };
  return levelMap[levelCode] || levelCode;
};

// 获取统计数据
const fetchStatistics = async () => {
  try {
    const response = await userApi.getStatistics();
    if (response.code === 200 && response.data) {
      statistics.value = response.data.statistics;
    }
  } catch (error) {
    console.error('获取统计数据失败:', error);
  }
};

// 获取用户列表
const fetchData = async () => {
  loading.value = true;
  try {
    const params: any = {
      search: searchKeyword.value || undefined,
      page: currentPage.value,
      per_page: pageSize.value,
      sort_by: sortBy.value,
      sort_order: sortOrder.value,
    };

    // 高级筛选
    if (filterDateRange.value) {
      params.created_from = filterDateRange.value[0];
      params.created_to = filterDateRange.value[1];
    }
    if (filterGender.value !== '') {
      params.gender = filterGender.value;
    }
    if (filterHasPhone.value !== '') {
      params.has_phone = filterHasPhone.value;
    }
    if (filterMinPoints !== null) {
      params.min_points = filterMinPoints.value;
    }
    if (filterMaxPoints !== null) {
      params.max_points = filterMaxPoints.value;
    }
    if (filterMinOrders !== null) {
      params.min_orders = filterMinOrders.value;
    }
    if (filterMaxOrders !== null) {
      params.max_orders = filterMaxOrders.value;
    }

    const response = await userApi.getList(params);

    if (response.code === 200 && response.data) {
      users.value = response.data.users;
      total.value = response.data.pagination.total;
    }
  } catch (error: any) {
    console.error('获取用户列表失败:', error);
    ElMessage.error('获取用户列表失败');
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  fetchData();
};

const handleAdvancedSearch = () => {
  currentPage.value = 1;
  fetchData();
};

const resetSearch = () => {
  searchKeyword.value = '';
  currentPage.value = 1;
  fetchData();
};

const resetAdvancedFilter = () => {
  filterDateRange.value = null;
  filterGender.value = '';
  filterHasPhone.value = '';
  filterMinPoints.value = null;
  filterMaxPoints.value = null;
  filterMinOrders.value = null;
  filterMaxOrders.value = null;
  handleAdvancedSearch();
};

const handleSizeChange = () => {
  fetchData();
};

const handlePageChange = () => {
  fetchData();
};

const handleSelectionChange = (selection: User[]) => {
  selectedUsers.value = selection;
};

// 查看详情
const handleViewDetail = async (user: User) => {
  detailDialogVisible.value = true;
  detailLoading.value = true;
  try {
    const response = await userApi.getDetail(user.id);
    if (response.code === 200 && response.data) {
      userDetail.value = response.data.user as UserDetail;
    }
  } catch (error: any) {
    console.error('获取用户详情失败:', error);
    ElMessage.error('获取用户详情失败');
  } finally {
    detailLoading.value = false;
  }
};

// 查看订单
const handleViewOrders = (user: User) => {
  router.push({ path: '/admin/orders', query: { user_id: user.id } });
};

// 查看积分
const handleViewPoints = (user: User) => {
  router.push({ path: '/admin/points', query: { user_id: user.id } });
};

// 状态切换
const handleStatusChange = async (user: User) => {
  updatingStatus[user.id] = true;
  try {
    await userApi.update(user.id, { is_active: user.is_active });
    ElMessage.success(`用户已${user.is_active ? '启用' : '禁用'}`);
  } catch (error: any) {
    user.is_active = !user.is_active; // 回滚
    console.error('更新用户状态失败:', error);
    ElMessage.error(error.response?.data?.message || '更新用户状态失败');
  } finally {
    updatingStatus[user.id] = false;
  }
};

const handleEdit = (user: User) => {
  editingUserId.value = user.id;
  editForm.value = {
    nickname: user.nickname || '',
    phone: user.phone || '',
    gender: user.gender ?? 0,
    is_active: user.is_active ?? true,
    remark: user.remark || '',
  };
  editDialogVisible.value = true;
  detailDialogVisible.value = false;
};

const handleSave = async () => {
  if (!editFormRef.value || !editingUserId.value) return;

  await editFormRef.value.validate(async (valid) => {
    if (!valid) return;

    saving.value = true;
    try {
      await userApi.update(editingUserId.value, editForm.value);
      ElMessage.success('用户信息更新成功');
      editDialogVisible.value = false;
      fetchData();
    } catch (error: any) {
      console.error('更新用户失败:', error);
      ElMessage.error(error.response?.data?.message || '更新用户失败');
    } finally {
      saving.value = false;
    }
  });
};

const handleDelete = async (user: User) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除用户 "${user.nickname || user.openid}" 吗？`,
      '确认删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    );

    await userApi.delete(user.id);
    ElMessage.success('用户删除成功');
    fetchData();
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除用户失败:', error);
      ElMessage.error(error.response?.data?.message || '删除用户失败');
    }
  }
};

const handleBatchDelete = async () => {
  if (selectedUsers.value.length === 0) return;

  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${selectedUsers.value.length} 个用户吗？`,
      '确认批量删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    );

    const ids = selectedUsers.value.map(u => u.id);
    const response = await userApi.batchDelete(ids);
    if (response.code === 200) {
      ElMessage.success(response.message || '批量删除成功');
      selectedUsers.value = [];
      fetchData();
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('批量删除失败:', error);
      ElMessage.error(error.response?.data?.message || '批量删除失败');
    }
  }
};

const resetEditForm = () => {
  editingUserId.value = null;
  editForm.value = {
    nickname: '',
    phone: '',
    gender: 0,
    is_active: true,
    remark: '',
  };
};

const resetDetail = () => {
  userDetail.value = null;
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('zh-CN');
};

onMounted(() => {
  fetchStatistics();
  fetchData();
});
</script>

<style scoped>
:deep(.el-table) {
  font-size: 14px;
}

:deep(.el-card__header) {
  padding: 16px 20px;
  font-weight: 600;
}
</style>
