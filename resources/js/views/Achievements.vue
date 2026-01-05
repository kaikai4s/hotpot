/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">🏆 成就管理</h1>
          <p class="text-gray-600">管理和配置用户成就系统</p>
        </div>
        <el-button type="primary" size="large" @click="handleAdd">
          <el-icon><Plus /></el-icon>
          添加成就
        </el-button>
      </div>

      <!-- 搜索栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索成就名称"
          clearable
          class="flex-1"
          @clear="handleSearch"
          @keyup.enter="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="selectedCategory" placeholder="选择分类" clearable style="width: 150px" @change="handleSearch">
          <el-option label="消费" value="consume" />
          <el-option label="评价" value="review" />
          <el-option label="邀请" value="invite" />
          <el-option label="签到" value="checkin" />
          <el-option label="积分" value="points" />
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
        :data="templates"
        stripe
        style="width: 100%"
        class="mb-4"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="成就图标" width="100">
          <template #default="{ row }">
            <span class="text-3xl">{{ row.icon || '🏆' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="成就名称" min-width="150" />
        <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />
        <el-table-column prop="category" label="分类" width="100">
          <template #default="{ row }">
            <el-tag :type="getCategoryTagType(row.category)">{{ getCategoryText(row.category) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="目标值" min-width="150">
          <template #default="{ row }">
            <div class="text-sm">
              <div v-if="row.target_value?.count">
                数量：<span class="font-bold">{{ row.target_value.count }}</span>
              </div>
              <div v-if="row.target_value?.amount">
                金额：<span class="font-bold">¥{{ row.target_value.amount }}</span>
              </div>
              <div v-if="row.target_value?.days">
                天数：<span class="font-bold">{{ row.target_value.days }}</span>
              </div>
              <div v-if="row.target_value?.consecutive_days">
                连续天数：<span class="font-bold">{{ row.target_value.consecutive_days }}</span>
              </div>
              <div v-if="row.target_value?.total_points">
                总积分：<span class="font-bold">{{ row.target_value.total_points }}</span>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="奖励" width="150">
          <template #default="{ row }">
            <div class="text-sm">
              <div v-if="row.reward_points > 0" class="text-yellow-600">
                💰 {{ row.reward_points }}积分
              </div>
              <div v-if="row.reward_coupon_id" class="text-red-600">
                🎫 优惠券
              </div>
              <div v-if="row.reward_points === 0 && !row.reward_coupon_id" class="text-gray-400">
                无奖励
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" width="100" sortable />
        <el-table-column prop="is_active" label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.is_active"
              @change="handleToggleStatus(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
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
      :title="editingId ? '编辑成就' : '添加成就'"
      width="700px"
      @close="handleDialogClose"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
      >
        <el-form-item label="成就名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入成就名称" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="请输入成就描述"
          />
        </el-form-item>
        <el-form-item label="图标" prop="icon">
          <el-input v-model="form.icon" placeholder="请输入图标（emoji或图标代码）" />
        </el-form-item>
        <el-form-item label="分类" prop="category">
          <el-select v-model="form.category" placeholder="请选择分类" @change="handleCategoryChange">
            <el-option label="消费" value="consume" />
            <el-option label="评价" value="review" />
            <el-option label="邀请" value="invite" />
            <el-option label="签到" value="checkin" />
            <el-option label="积分" value="points" />
          </el-select>
        </el-form-item>
        <el-form-item label="目标值" prop="target_value">
          <div class="space-y-2">
            <el-select v-model="targetType" placeholder="选择目标类型" @change="handleTargetTypeChange">
              <el-option label="数量" value="count" />
              <el-option label="金额" value="amount" />
              <el-option label="天数" value="days" />
              <el-option label="连续天数" value="consecutive_days" />
              <el-option label="总积分" value="total_points" />
            </el-select>
            <el-input-number
              v-if="targetType"
              v-model="targetValue"
              :min="1"
              :placeholder="`请输入${getTargetTypeLabel(targetType)}`"
              class="w-full mt-2"
              @change="updateTargetValue"
            />
          </div>
        </el-form-item>
        <el-form-item label="奖励积分" prop="reward_points">
          <el-input-number
            v-model="form.reward_points"
            :min="0"
            placeholder="请输入奖励积分"
            class="w-full"
          />
        </el-form-item>
        <el-form-item label="奖励优惠券" prop="reward_coupon_id">
          <el-select
            v-model="form.reward_coupon_id"
            placeholder="选择优惠券（可选）"
            clearable
            filterable
            class="w-full"
          >
            <el-option
              v-for="coupon in availableCoupons"
              :key="coupon.id"
              :label="coupon.name"
              :value="coupon.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number
            v-model="form.sort_order"
            :min="0"
            placeholder="排序值（数字越小越靠前）"
            class="w-full"
          />
        </el-form-item>
        <el-form-item label="状态" prop="is_active">
          <el-switch v-model="form.is_active" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Search } from '@element-plus/icons-vue';
import { achievementTemplateApi, type AchievementTemplate } from '../api/admin/achievement';
import { adminCouponApi } from '../api/coupon';
import type { FormInstance, FormRules } from 'element-plus';

const loading = ref(false);
const saving = ref(false);
const templates = ref<AchievementTemplate[]>([]);
const availableCoupons = ref<any[]>([]);
const dialogVisible = ref(false);
const editingId = ref<number | null>(null);
const formRef = ref<FormInstance | null>(null);
const searchKeyword = ref('');
const selectedCategory = ref<string>('');
const selectedStatus = ref<boolean | null>(null);
const currentPage = ref(1);
const pageSize = ref(20);
const total = ref(0);
const targetType = ref<string>('');
const targetValue = ref<number>(0);

const form = ref<Partial<AchievementTemplate>>({
  name: '',
  description: '',
  icon: '',
  category: 'consume',
  target_value: {},
  reward_points: 0,
  reward_coupon_id: null,
  is_active: true,
  sort_order: 0,
});

const rules: FormRules = {
  name: [{ required: true, message: '请输入成就名称', trigger: 'blur' }],
  category: [{ required: true, message: '请选择分类', trigger: 'change' }],
  target_value: [{ required: true, message: '请设置目标值', trigger: 'change' }],
  reward_points: [{ required: true, message: '请输入奖励积分', trigger: 'blur' }],
};

const getCategoryText = (category: string) => {
  const map: Record<string, string> = {
    consume: '消费',
    review: '评价',
    invite: '邀请',
    checkin: '签到',
    points: '积分',
  };
  return map[category] || category;
};

const getCategoryTagType = (category: string) => {
  const map: Record<string, string> = {
    consume: 'success',
    review: 'warning',
    invite: 'info',
    checkin: 'primary',
    points: 'danger',
  };
  return map[category] || '';
};

const getTargetTypeLabel = (type: string) => {
  const map: Record<string, string> = {
    count: '数量',
    amount: '金额（元）',
    days: '天数',
    consecutive_days: '连续天数',
    total_points: '总积分',
  };
  return map[type] || '';
};

const handleTargetTypeChange = () => {
  targetValue.value = 0;
  updateTargetValue();
};

const updateTargetValue = () => {
  if (targetType.value && targetValue.value > 0) {
    form.value.target_value = {
      [targetType.value]: targetType.value === 'amount' ? targetValue.value * 100 : targetValue.value,
    };
  }
};

const handleCategoryChange = () => {
  // 重置目标值
  targetType.value = '';
  targetValue.value = 0;
  form.value.target_value = {};
};

const loadTemplates = async () => {
  loading.value = true;
  try {
    const params: any = {
      page: currentPage.value,
      page_size: pageSize.value,
    };
    if (selectedCategory.value) {
      params.category = selectedCategory.value;
    }
    if (selectedStatus.value !== null) {
      params.is_active = selectedStatus.value;
    }

    const response = await achievementTemplateApi.getList(params);
    if (response.code === 200 && response.data) {
      templates.value = response.data.templates;
      total.value = response.data.pagination.total;
    }
  } catch (error: any) {
    console.error('加载成就列表失败:', error);
    ElMessage.error(error.response?.data?.message || '加载失败');
  } finally {
    loading.value = false;
  }
};

const loadCoupons = async () => {
  try {
    const response = await adminCouponApi.getList({ is_active: true });
    if (response.code === 200 && response.data) {
      availableCoupons.value = response.data.coupons;
    }
  } catch (error) {
    console.error('加载优惠券列表失败:', error);
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  loadTemplates();
};

const resetSearch = () => {
  searchKeyword.value = '';
  selectedCategory.value = '';
  selectedStatus.value = null;
  handleSearch();
};

const handleAdd = () => {
  editingId.value = null;
  form.value = {
    name: '',
    description: '',
    icon: '',
    category: 'consume',
    target_value: {},
    reward_points: 0,
    reward_coupon_id: null,
    is_active: true,
    sort_order: 0,
  };
  targetType.value = '';
  targetValue.value = 0;
  dialogVisible.value = true;
};

const handleEdit = (row: AchievementTemplate) => {
  editingId.value = row.id;
  form.value = { ...row };
  
  // 解析目标值
  if (form.value.target_value) {
    if (form.value.target_value.count) {
      targetType.value = 'count';
      targetValue.value = form.value.target_value.count;
    } else if (form.value.target_value.amount) {
      targetType.value = 'amount';
      targetValue.value = form.value.target_value.amount / 100; // 转换为元
    } else if (form.value.target_value.days) {
      targetType.value = 'days';
      targetValue.value = form.value.target_value.days;
    } else if (form.value.target_value.consecutive_days) {
      targetType.value = 'consecutive_days';
      targetValue.value = form.value.target_value.consecutive_days;
    } else if (form.value.target_value.total_points) {
      targetType.value = 'total_points';
      targetValue.value = form.value.target_value.total_points;
    }
  }
  
  dialogVisible.value = true;
};

const handleSave = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid) => {
    if (!valid) return;

    // 确保目标值已设置
    if (!form.value.target_value || Object.keys(form.value.target_value).length === 0) {
      ElMessage.warning('请设置目标值');
      return;
    }

    saving.value = true;
    try {
      if (editingId.value) {
        await achievementTemplateApi.update(editingId.value, form.value);
        ElMessage.success('更新成功');
      } else {
        await achievementTemplateApi.create(form.value);
        ElMessage.success('创建成功');
      }
      dialogVisible.value = false;
      await loadTemplates();
    } catch (error: any) {
      console.error('保存失败:', error);
      ElMessage.error(error.response?.data?.message || '保存失败');
    } finally {
      saving.value = false;
    }
  });
};

const handleToggleStatus = async (row: AchievementTemplate) => {
  try {
    await achievementTemplateApi.update(row.id, { is_active: row.is_active });
    ElMessage.success('状态更新成功');
  } catch (error: any) {
    console.error('更新状态失败:', error);
    ElMessage.error(error.response?.data?.message || '更新失败');
    // 恢复原状态
    row.is_active = !row.is_active;
  }
};

const handleDelete = async (row: AchievementTemplate) => {
  try {
    await ElMessageBox.confirm(`确定要删除成就"${row.name}"吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });

    await achievementTemplateApi.delete(row.id);
    ElMessage.success('删除成功');
    await loadTemplates();
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除失败:', error);
      ElMessage.error(error.response?.data?.message || '删除失败');
    }
  }
};

const handleDialogClose = () => {
  formRef.value?.resetFields();
  editingId.value = null;
  targetType.value = '';
  targetValue.value = 0;
};

const handleSizeChange = () => {
  loadTemplates();
};

const handlePageChange = () => {
  loadTemplates();
};

onMounted(() => {
  loadTemplates();
  loadCoupons();
});
</script>

<style scoped>
:deep(.el-form-item__label) {
  font-weight: 500;
}
</style>

