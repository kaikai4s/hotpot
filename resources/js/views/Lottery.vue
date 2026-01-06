/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">抽奖活动管理</h1>
          <p class="text-gray-600">管理和配置抽奖活动及奖品</p>
        </div>
        <el-button type="primary" size="large" @click="handleAdd">
          <el-icon><Plus /></el-icon>
          添加活动
        </el-button>
      </div>

      <!-- 搜索栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索活动名称"
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
          <el-option label="启用" :value="true" />
          <el-option label="禁用" :value="false" />
        </el-select>
        <el-button type="primary" @click="handleSearch">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>

      <!-- 表格 -->
      <el-table
        v-loading="loading"
        :data="activities"
        stripe
        border
        style="width: 100%"
        class="mb-4"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="活动名称" min-width="200" />
        <el-table-column label="活动时间" width="300">
          <template #default="{ row }">
            <div class="text-sm">
              <div>开始：{{ formatDateTime(row.start_time) }}</div>
              <div>结束：{{ formatDateTime(row.end_time) }}</div>
              <div class="mt-1">
                <el-tag
                  v-if="isActivityExpired(row)"
                  type="info"
                  size="small"
                >
                  已过期
                </el-tag>
                <el-tag
                  v-else-if="isActivityNotStarted(row)"
                  type="warning"
                  size="small"
                >
                  未开始
                </el-tag>
                <el-tag
                  v-else
                  type="success"
                  size="small"
                >
                  进行中
                </el-tag>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="限制" width="200">
          <template #default="{ row }">
            <div class="text-sm">
              <div>每日：{{ row.daily_limit || '∞' }}次</div>
              <div>总计：{{ row.total_limit || '∞' }}次</div>
              <div>消耗：{{ row.points_cost || 0 }}积分</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="奖品数量" width="120">
          <template #default="{ row }">
            {{ row.prizes?.length || 0 }} 个
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
            <el-button type="info" link @click="handleManagePrizes(row)">管理奖品</el-button>
            <el-button type="warning" link @click="handleViewRecords(row)">抽奖记录</el-button>
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

    <!-- 活动编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="editingId ? '编辑活动' : '添加活动'"
      width="700px"
      @close="handleDialogClose"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
      >
        <el-form-item label="活动名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入活动名称" />
        </el-form-item>
        <el-form-item label="活动描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="活动描述（可选）"
            maxlength="1000"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="活动图片" prop="image_url">
          <el-input v-model="form.image_url" placeholder="图片URL（可选）" />
        </el-form-item>
        <el-form-item label="开始时间" prop="start_time">
          <el-date-picker
            v-model="form.start_time"
            type="datetime"
            placeholder="选择开始时间"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="结束时间" prop="end_time">
          <el-date-picker
            v-model="form.end_time"
            type="datetime"
            placeholder="选择结束时间"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="每日限制" prop="daily_limit">
          <el-input-number
            v-model="form.daily_limit"
            :min="0"
            :max="999"
            style="width: 100%"
          />
          <div class="text-xs text-gray-500 mt-1">0表示不限制</div>
        </el-form-item>
        <el-form-item label="总限制" prop="total_limit">
          <el-input-number
            v-model="form.total_limit"
            :min="0"
            :max="9999"
            style="width: 100%"
          />
          <div class="text-xs text-gray-500 mt-1">0表示不限制</div>
        </el-form-item>
        <el-form-item label="消耗积分" prop="points_cost">
          <el-input-number
            v-model="form.points_cost"
            :min="0"
            :max="9999"
            style="width: 100%"
          />
          <div class="text-xs text-gray-500 mt-1">每次抽奖消耗的积分，0表示免费</div>
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number
            v-model="form.sort_order"
            :min="0"
            :max="999"
            style="width: 100%"
          />
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

    <!-- 奖品管理对话框 -->
    <el-dialog
      v-model="prizeDialogVisible"
      title="管理奖品"
      width="900px"
      @close="handlePrizeDialogClose"
    >
      <div class="mb-4">
        <el-button type="primary" @click="handleAddPrize">
          <el-icon><Plus /></el-icon>
          添加奖品
        </el-button>
      </div>

      <el-table :data="prizes" stripe border style="width: 100%" class="mb-4">
        <el-table-column prop="name" label="奖品名称" width="150" />
        <el-table-column prop="prize_type" label="类型" width="120">
          <template #default="{ row }">
            <el-tag>{{ getPrizeTypeText(row.prize_type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="奖品内容" width="250">
          <template #default="{ row }">
            <div v-if="row.prize_type === 'points'" class="flex items-center gap-2">
              <span class="text-xl">⭐</span>
              <span class="font-semibold text-orange-600">{{ row.prize_value }}积分</span>
            </div>
            <div v-else-if="row.prize_type === 'coupon'" class="flex items-center gap-2">
              <span class="text-xl">🎫</span>
              <div>
                <div class="font-semibold">{{ row.coupon?.name || `优惠券ID: ${row.prize_id}` }}</div>
                <div v-if="row.coupon" class="text-xs text-gray-500">
                  {{ getCouponTypeText(row.coupon.type) }} - ¥{{ row.coupon.value }}
                </div>
              </div>
            </div>
            <div v-else-if="row.prize_type === 'dish'" class="flex items-center gap-2">
              <span class="text-xl">🍲</span>
              <div>
                <div class="font-semibold">{{ row.dish?.name || `菜品ID: ${row.prize_id}` }}</div>
                <div v-if="row.dish" class="text-xs text-gray-500">¥{{ row.dish.price }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="probability" label="概率" width="100">
          <template #default="{ row }">
            {{ (row.probability / 100).toFixed(2) }}%
          </template>
        </el-table-column>
        <el-table-column label="库存" width="200">
          <template #default="{ row }">
            <div class="text-sm">
              <div class="mb-1">
                <span>总库存：</span>
                <span v-if="row.stock === 0">∞</span>
                <span v-else>
                  <span :class="row.remaining_stock === 0 ? 'text-red-600 font-bold' : ''">
                    {{ row.remaining_stock }}/{{ row.stock }}
                  </span>
                  <el-tag v-if="row.remaining_stock === 0" type="danger" size="small" class="ml-1">已用完</el-tag>
                </span>
              </div>
              <div>
                <span>每日：</span>
                <span v-if="row.daily_stock === 0">∞</span>
                <span v-else>
                  <span :class="row.remaining_daily_stock === 0 ? 'text-red-600 font-bold' : ''">
                    {{ row.remaining_daily_stock }}/{{ row.daily_stock }}
                  </span>
                  <el-tag v-if="row.remaining_daily_stock === 0" type="warning" size="small" class="ml-1">今日已用完</el-tag>
                </span>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="is_active" label="状态" width="100">
          <template #default="{ row }">
            <el-switch v-model="row.is_active" @change="handleTogglePrizeStatus(row)" />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEditPrize(row)">编辑</el-button>
            <el-button type="danger" link @click="handleDeletePrize(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 添加/编辑奖品对话框 -->
      <el-dialog
        v-model="prizeFormDialogVisible"
        :title="editingPrizeId ? '编辑奖品' : '添加奖品'"
        width="600px"
        append-to-body
      >
        <el-form
          ref="prizeFormRef"
          :model="prizeForm"
          :rules="prizeRules"
          label-width="120px"
        >
          <el-form-item label="奖品名称" prop="name">
            <el-input v-model="prizeForm.name" placeholder="请输入奖品名称" />
          </el-form-item>
          <el-form-item label="奖品描述" prop="description">
            <el-input
              v-model="prizeForm.description"
              type="textarea"
              :rows="2"
              placeholder="奖品描述（可选）"
              maxlength="500"
            />
          </el-form-item>
          <el-form-item label="奖品图片" prop="image_url">
            <el-input v-model="prizeForm.image_url" placeholder="图片URL（可选）" />
          </el-form-item>
          <el-form-item label="奖品类型" prop="prize_type">
            <el-select v-model="prizeForm.prize_type" placeholder="请选择奖品类型" @change="handlePrizeTypeChange">
              <el-option label="优惠券" value="coupon" />
              <el-option label="积分" value="points" />
              <el-option label="菜品" value="dish" />
            </el-select>
          </el-form-item>
          <el-form-item v-if="prizeForm.prize_type === 'coupon'" label="选择优惠券" prop="prize_id">
            <el-select
              v-model="prizeForm.prize_id"
              placeholder="请选择优惠券"
              filterable
              style="width: 100%"
              :loading="loadingCoupons"
            >
              <el-option
                v-for="coupon in availableCoupons"
                :key="coupon.id"
                :value="coupon.id"
              >
                <div class="flex items-center justify-between">
                  <span>{{ coupon.name }}</span>
                  <span class="text-xs text-gray-500 ml-2">
                    {{ getCouponTypeText(coupon.type) }} - ¥{{ coupon.value }}
                    <span v-if="coupon.min_amount > 0">(满¥{{ coupon.min_amount }})</span>
                  </span>
                </div>
              </el-option>
            </el-select>
            <div class="text-xs text-gray-500 mt-1">选择已创建的优惠券作为奖品</div>
          </el-form-item>
          <el-form-item v-else-if="prizeForm.prize_type === 'dish'" label="选择菜品" prop="prize_id">
            <el-select
              v-model="prizeForm.prize_id"
              placeholder="请选择菜品"
              filterable
              style="width: 100%"
              :loading="loadingDishes"
            >
              <el-option
                v-for="dish in availableDishes"
                :key="dish.id"
                :value="dish.id"
              >
                <div class="flex items-center justify-between">
                  <span>{{ dish.name }}</span>
                  <span class="text-xs text-gray-500 ml-2">
                    ¥{{ dish.price }}
                    <el-tag :type="dish.status === 'available' ? 'success' : dish.status === 'sold_out' ? 'warning' : 'info'" size="small" class="ml-1">
                      {{ dish.status === 'available' ? '在售' : dish.status === 'sold_out' ? '售罄' : '下架' }}
                    </el-tag>
                  </span>
                </div>
              </el-option>
            </el-select>
            <div class="text-xs text-gray-500 mt-1">选择后台菜品管理中的菜品作为奖品</div>
          </el-form-item>
          <el-form-item v-else-if="prizeForm.prize_type === 'points'" label="积分数量" prop="prize_value">
            <el-input-number
              v-model="prizeForm.prize_value"
              :min="1"
              :max="99999"
              style="width: 100%"
            />
          </el-form-item>
          <el-form-item label="中奖概率" prop="probability">
            <el-input-number
              v-model="prizeForm.probability"
              :min="1"
              :max="10000"
              style="width: 100%"
            />
            <div class="text-xs text-gray-500 mt-1">万分之几（如：100表示1%）</div>
          </el-form-item>
          <el-form-item label="总库存" prop="stock">
            <el-input-number
              v-model="prizeForm.stock"
              :min="0"
              :max="99999"
              style="width: 100%"
            />
            <div class="text-xs text-gray-500 mt-1">0表示不限制</div>
          </el-form-item>
          <el-form-item label="每日库存" prop="daily_stock">
            <el-input-number
              v-model="prizeForm.daily_stock"
              :min="0"
              :max="99999"
              style="width: 100%"
            />
            <div class="text-xs text-gray-500 mt-1">0表示不限制</div>
          </el-form-item>
          <el-form-item label="排序" prop="sort_order">
            <el-input-number
              v-model="prizeForm.sort_order"
              :min="0"
              :max="999"
              style="width: 100%"
            />
          </el-form-item>
          <el-form-item label="状态" prop="is_active">
            <el-switch v-model="prizeForm.is_active" />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="prizeFormDialogVisible = false">取消</el-button>
          <el-button type="primary" :loading="savingPrize" @click="handleSubmitPrize">确定</el-button>
        </template>
      </el-dialog>
    </el-dialog>

    <!-- 抽奖记录对话框 -->
    <el-dialog
      v-model="recordsDialogVisible"
      title="抽奖记录"
      width="1000px"
    >
      <el-table
        v-loading="recordsLoading"
        :data="records"
        stripe
        border
        max-height="500"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="用户" width="150">
          <template #default="{ row }">
            {{ row.user?.nickname || '未知' }}
          </template>
        </el-table-column>
        <el-table-column label="奖品" width="200">
          <template #default="{ row }">
            <div v-if="row.is_winner && row.prize">
              <el-tag type="success">{{ row.prize.name }}</el-tag>
            </div>
            <el-tag v-else type="info">未中奖</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="抽奖时间" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
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
import adminApiClient from '../api/admin-client';
import type { ApiResponse } from '../types';

interface LotteryActivity {
  id: number;
  name: string;
  description: string | null;
  image_url: string | null;
  start_time: string;
  end_time: string;
  daily_limit: number;
  total_limit: number;
  points_cost: number;
  is_active: boolean;
  sort_order: number;
  prizes?: any[];
}

interface LotteryPrize {
  id: number;
  name: string;
  description: string | null;
  image_url: string | null;
  prize_type: 'coupon' | 'points' | 'dish';
  prize_id: number | null;
  prize_value: number | null;
  probability: number;
  stock: number;
  daily_stock: number;
  sort_order: number;
  is_active: boolean;
  // 剩余库存信息
  remaining_stock?: number | null;
  used_stock?: number;
  remaining_daily_stock?: number | null;
  used_daily_stock?: number;
  is_available?: boolean;
  // 实时概率（基于可用奖品的总概率）
  real_time_probability?: number;
}

const loading = ref(false);
const saving = ref(false);
const savingPrize = ref(false);
const recordsLoading = ref(false);
const loadingCoupons = ref(false);
const loadingDishes = ref(false);
const activities = ref<LotteryActivity[]>([]);
const prizes = ref<LotteryPrize[]>([]);
const records = ref<any[]>([]);
const availableCoupons = ref<any[]>([]);
const availableDishes = ref<any[]>([]);
const searchKeyword = ref('');
const selectedStatus = ref<boolean | null>(null);
const currentPage = ref(1);
const pageSize = ref(15);
const total = ref(0);

const dialogVisible = ref(false);
const prizeDialogVisible = ref(false);
const prizeFormDialogVisible = ref(false);
const recordsDialogVisible = ref(false);
const editingId = ref<number | null>(null);
const editingPrizeId = ref<number | null>(null);
const currentActivityId = ref<number | null>(null);
const formRef = ref();
const prizeFormRef = ref();

const form = ref<Partial<LotteryActivity>>({
  name: '',
  description: '',
  image_url: '',
  start_time: '',
  end_time: '',
  daily_limit: 0,
  total_limit: 0,
  points_cost: 0,
  is_active: true,
  sort_order: 0,
});

const prizeForm = ref<Partial<LotteryPrize>>({
  name: '',
  description: '',
  image_url: '',
  prize_type: 'coupon',
  prize_id: null,
  prize_value: null,
  probability: 100,
  stock: 0,
  daily_stock: 0,
  sort_order: 0,
  is_active: true,
});

const rules = {
  name: [{ required: true, message: '请输入活动名称', trigger: 'blur' }],
  start_time: [{ required: true, message: '请选择开始时间', trigger: 'change' }],
  end_time: [{ required: true, message: '请选择结束时间', trigger: 'change' }],
};

const prizeRules = {
  name: [{ required: true, message: '请输入奖品名称', trigger: 'blur' }],
  prize_type: [{ required: true, message: '请选择奖品类型', trigger: 'change' }],
  probability: [{ required: true, message: '请输入中奖概率', trigger: 'blur' }],
};

const getPrizeTypeText = (type: string) => {
  const map: Record<string, string> = {
    coupon: '优惠券',
    points: '积分',
    dish: '菜品',
  };
  return map[type] || type;
};

const getCouponTypeText = (type: string) => {
  const map: Record<string, string> = {
    fixed_amount: '固定金额',
    percentage: '百分比折扣',
    dish_exchange: '兑换菜品',
    points: '积分券',
    discount: '折扣券',
    cash: '现金券',
  };
  return map[type] || type;
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const isActivityExpired = (activity: LotteryActivity): boolean => {
  if (!activity.end_time) return false;
  return new Date(activity.end_time) < new Date();
};

const isActivityNotStarted = (activity: LotteryActivity): boolean => {
  if (!activity.start_time) return false;
  return new Date(activity.start_time) > new Date();
};

const handlePrizeTypeChange = () => {
  prizeForm.value.prize_id = null;
  prizeForm.value.prize_value = null;
  
  if (prizeForm.value.prize_type === 'coupon' && availableCoupons.value.length === 0) {
    fetchCoupons();
  } else if (prizeForm.value.prize_type === 'dish' && availableDishes.value.length === 0) {
    fetchDishes();
  }
};

const fetchCoupons = async () => {
  loadingCoupons.value = true;
  try {
    const response = await adminApiClient.get('/admin/v1/coupons', { params: { is_active: true, per_page: 1000 } });
    if (response.code === 200 && response.data) {
      // 移除库存限制，允许选择所有优惠券作为奖品
      availableCoupons.value = response.data.coupons || [];
    }
  } catch (error) {
    console.error('获取优惠券列表失败:', error);
    ElMessage.error('获取优惠券列表失败');
  } finally {
    loadingCoupons.value = false;
  }
};

const fetchDishes = async () => {
  loadingDishes.value = true;
  try {
    // 获取后台菜品管理中的所有菜品（不限制状态），用于抽奖奖品选择
    const response = await adminApiClient.get('/admin/v1/dishes', { params: { per_page: 1000 } });
    if (response.code === 200 && response.data) {
      availableDishes.value = response.data.dishes || [];
    }
  } catch (error) {
    console.error('获取菜品列表失败:', error);
    ElMessage.error('获取菜品列表失败');
  } finally {
    loadingDishes.value = false;
  }
};

const handleAdd = () => {
  editingId.value = null;
  form.value = {
    name: '',
    description: '',
    image_url: '',
    start_time: '',
    end_time: '',
    daily_limit: 0,
    total_limit: 0,
    points_cost: 0,
    is_active: true,
    sort_order: 0,
  };
  dialogVisible.value = true;
};

const handleEdit = (activity: LotteryActivity) => {
  editingId.value = activity.id;
  form.value = { ...activity };
  dialogVisible.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  
  await formRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    saving.value = true;
    try {
      let response: ApiResponse<any>;
      if (editingId.value) {
        response = await adminApiClient.put(`/admin/v1/lottery/activities/${editingId.value}`, form.value);
      } else {
        response = await adminApiClient.post('/admin/v1/lottery/activities', form.value);
      }

      if (response.code === 200 || response.code === 201) {
        ElMessage.success(editingId.value ? '活动更新成功' : '活动创建成功');
        dialogVisible.value = false;
        fetchData();
      } else {
        ElMessage.error(response.message || '操作失败');
      }
    } catch (error: any) {
      console.error('操作失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '操作失败');
    } finally {
      saving.value = false;
    }
  });
};

const handleDelete = async (activity: LotteryActivity) => {
  try {
    await ElMessageBox.confirm('确定要删除该活动吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });

    const response = await adminApiClient.delete(`/admin/v1/lottery/activities/${activity.id}`);
    if (response.code === 200) {
      ElMessage.success('删除成功');
      fetchData();
    } else {
      ElMessage.error(response.message || '删除失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '删除失败');
    }
  }
};

const handleToggleStatus = async (activity: LotteryActivity) => {
  try {
    const response = await adminApiClient.put(`/admin/v1/lottery/activities/${activity.id}`, {
      is_active: activity.is_active,
    });
    if (response.code !== 200) {
      activity.is_active = !activity.is_active;
      ElMessage.error(response.message || '更新失败');
    }
  } catch (error: any) {
    activity.is_active = !activity.is_active;
    ElMessage.error(error.response?.data?.message || error.message || '更新失败');
  }
};

const handleManagePrizes = async (activity: LotteryActivity) => {
  currentActivityId.value = activity.id;
  prizeDialogVisible.value = true;
  await fetchPrizes(activity.id);
};

const fetchPrizes = async (activityId: number) => {
  try {
    const response = await adminApiClient.get(`/admin/v1/lottery/activities/${activityId}/prizes`);
    if (response.code === 200 && response.data) {
      prizes.value = response.data.prizes || [];
    }
  } catch (error) {
    console.error('获取奖品列表失败:', error);
  }
};

const handleAddPrize = () => {
  editingPrizeId.value = null;
  prizeForm.value = {
    name: '',
    description: '',
    image_url: '',
    prize_type: 'coupon',
    prize_id: null,
    prize_value: null,
    probability: 100,
    stock: 0,
    daily_stock: 0,
    sort_order: 0,
    is_active: true,
  };
  prizeFormDialogVisible.value = true;
  
  // 打开对话框时自动加载数据
  if (prizeForm.value.prize_type === 'coupon') {
    fetchCoupons();
  } else if (prizeForm.value.prize_type === 'dish') {
    fetchDishes();
  }
};

const handleEditPrize = (prize: LotteryPrize) => {
  editingPrizeId.value = prize.id;
  prizeForm.value = { ...prize };
  
  if (prize.prize_type === 'coupon' && availableCoupons.value.length === 0) {
    fetchCoupons();
  } else if (prize.prize_type === 'dish' && availableDishes.value.length === 0) {
    fetchDishes();
  }
  
  prizeFormDialogVisible.value = true;
};

const handleSubmitPrize = async () => {
  if (!prizeFormRef.value || !currentActivityId.value) return;
  
  await prizeFormRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    savingPrize.value = true;
    try {
      let response: ApiResponse<any>;
      if (editingPrizeId.value) {
        response = await adminApiClient.put(
          `/admin/v1/lottery/activities/${currentActivityId.value}/prizes/${editingPrizeId.value}`,
          prizeForm.value
        );
      } else {
        response = await adminApiClient.post(
          `/admin/v1/lottery/activities/${currentActivityId.value}/prizes`,
          prizeForm.value
        );
      }

      if (response.code === 200 || response.code === 201) {
        ElMessage.success(editingPrizeId.value ? '奖品更新成功' : '奖品创建成功');
        prizeFormDialogVisible.value = false;
        await fetchPrizes(currentActivityId.value!);
      } else {
        ElMessage.error(response.message || '操作失败');
      }
    } catch (error: any) {
      console.error('操作失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '操作失败');
    } finally {
      savingPrize.value = false;
    }
  });
};

const handleDeletePrize = async (prize: LotteryPrize) => {
  if (!currentActivityId.value) return;
  
  try {
    await ElMessageBox.confirm('确定要删除该奖品吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });

    const response = await adminApiClient.delete(
      `/admin/v1/lottery/activities/${currentActivityId.value}/prizes/${prize.id}`
    );
    if (response.code === 200) {
      ElMessage.success('删除成功');
      await fetchPrizes(currentActivityId.value);
    } else {
      ElMessage.error(response.message || '删除失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '删除失败');
    }
  }
};

const handleTogglePrizeStatus = async (prize: LotteryPrize) => {
  if (!currentActivityId.value) return;
  
  try {
    const response = await adminApiClient.put(
      `/admin/v1/lottery/activities/${currentActivityId.value}/prizes/${prize.id}`,
      { is_active: prize.is_active }
    );
    if (response.code !== 200) {
      prize.is_active = !prize.is_active;
      ElMessage.error(response.message || '更新失败');
    }
  } catch (error: any) {
    prize.is_active = !prize.is_active;
    ElMessage.error(error.response?.data?.message || error.message || '更新失败');
  }
};

const handleViewRecords = async (activity: LotteryActivity) => {
  recordsDialogVisible.value = true;
  recordsLoading.value = true;
  try {
    const response = await adminApiClient.get(`/admin/v1/lottery/activities/${activity.id}/records`, {
      params: { page_size: 50 },
    });
    if (response.code === 200 && response.data) {
      records.value = response.data.records || [];
    }
  } catch (error) {
    console.error('获取抽奖记录失败:', error);
  } finally {
    recordsLoading.value = false;
  }
};

const handleDialogClose = () => {
  formRef.value?.resetFields();
  editingId.value = null;
};

const handlePrizeDialogClose = () => {
  currentActivityId.value = null;
  prizes.value = [];
};

const handleSearch = () => {
  currentPage.value = 1;
  fetchData();
};

const resetSearch = () => {
  searchKeyword.value = '';
  selectedStatus.value = null;
  currentPage.value = 1;
  fetchData();
};

const handleSizeChange = () => {
  currentPage.value = 1;
  fetchData();
};

const handlePageChange = () => {
  fetchData();
};

const fetchData = async () => {
  loading.value = true;
  try {
    const response = await adminApiClient.get('/admin/v1/lottery/activities', {
      params: {
        search: searchKeyword.value || undefined,
        is_active: selectedStatus.value !== null ? selectedStatus.value : undefined,
        page_size: pageSize.value,
        page: currentPage.value,
      },
    });

    if (response.code === 200 && response.data) {
      activities.value = response.data.activities || [];
      total.value = response.data.pagination?.total || 0;
    } else {
      ElMessage.error(response.message || '获取活动列表失败');
    }
  } catch (error: any) {
    console.error('获取活动列表失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取活动列表失败');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

