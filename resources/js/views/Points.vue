/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">积分管理</h1>
          <p class="text-gray-600">管理和查看用户积分信息</p>
        </div>
        <el-button type="primary" size="large" @click="fetchData">
          <el-icon><Refresh /></el-icon>
          刷新
        </el-button>
      </div>

      <!-- 搜索栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索用户昵称、手机号"
          clearable
          class="flex-1"
          @clear="handleSearch"
          @keyup.enter="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="selectedLevel" placeholder="选择等级" clearable style="width: 150px" @change="handleSearch">
          <el-option
            v-for="level in availableLevels"
            :key="level.code"
            :label="level.name"
            :value="level.code"
          />
        </el-select>
        <el-button type="primary" @click="handleSearch">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>

      <!-- 表格 -->
      <el-table
        v-loading="loading"
        :data="points"
        stripe
        style="width: 100%"
        class="mb-4"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="用户" width="200">
          <template #default="{ row }">
            <div class="flex items-center">
              <el-avatar v-if="row.user?.avatar_url" :src="row.user.avatar_url" :size="32" class="mr-2" />
              <div>
                <div class="font-semibold">{{ row.user?.nickname || '未设置' }}</div>
                <div class="text-xs text-gray-500">{{ row.user?.phone || '未绑定' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="total_points" label="总积分" width="120" sortable>
          <template #default="{ row }">
            <span class="font-bold text-blue-600">{{ row.total_points }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="available_points" label="可用积分" width="120" sortable>
          <template #default="{ row }">
            <span class="font-bold text-green-600">{{ row.available_points }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="frozen_points" label="冻结积分" width="120">
          <template #default="{ row }">
            <span class="text-orange-600">{{ row.frozen_points }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="level" label="等级" width="120">
          <template #default="{ row }">
            <el-tag :type="getLevelType(row.level)">{{ getLevelText(row.level) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="updated_at" label="更新时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.updated_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleViewDetail(row)">详情</el-button>
            <el-button type="success" link @click="handleAdjust(row)">调整积分</el-button>
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

    <!-- 详情对话框 -->
    <el-dialog
      v-model="detailDialogVisible"
      :title="`${selectedPoint?.user?.nickname || '用户'} - 积分详情`"
      width="800px"
    >
      <div v-if="selectedPoint" class="space-y-6">
        <!-- 积分概览 -->
        <div class="grid grid-cols-4 gap-4">
          <div class="bg-blue-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-blue-600">{{ selectedPoint.total_points }}</div>
            <div class="text-sm text-gray-600 mt-1">总积分</div>
          </div>
          <div class="bg-green-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-green-600">{{ selectedPoint.available_points }}</div>
            <div class="text-sm text-gray-600 mt-1">可用积分</div>
          </div>
          <div class="bg-orange-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-orange-600">{{ selectedPoint.frozen_points }}</div>
            <div class="text-sm text-gray-600 mt-1">冻结积分</div>
          </div>
          <div class="bg-purple-50 p-4 rounded-lg text-center">
            <div class="text-lg font-bold text-purple-600">{{ getLevelText(selectedPoint.level) }}</div>
            <div class="text-sm text-gray-600 mt-1">会员等级</div>
          </div>
        </div>

        <!-- 交易记录 -->
        <div>
          <h3 class="text-lg font-semibold mb-4">积分交易记录</h3>
          <el-table :data="transactions" stripe max-height="400">
            <el-table-column prop="type" label="类型" width="100">
              <template #default="{ row }">
                <el-tag :type="row.points > 0 ? 'success' : 'danger'">
                  {{ getTypeText(row.type) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="points" label="积分变动" width="120">
              <template #default="{ row }">
                <span :class="row.points > 0 ? 'text-green-600' : 'text-red-600'">
                  {{ row.points > 0 ? '+' : '' }}{{ row.points }}
                </span>
              </template>
            </el-table-column>
            <el-table-column prop="balance_after" label="变动后余额" width="120" />
            <el-table-column prop="description" label="描述" min-width="200" />
            <el-table-column prop="created_at" label="时间" width="180">
              <template #default="{ row }">
                {{ formatDate(row.created_at) }}
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>
    </el-dialog>

    <!-- 调整积分对话框 -->
    <el-dialog
      v-model="adjustDialogVisible"
      title="调整积分"
      width="500px"
      @close="resetAdjustForm"
    >
      <el-form
        ref="adjustFormRef"
        :model="adjustForm"
        :rules="adjustRules"
        label-width="120px"
      >
        <el-form-item label="用户">
          <el-input :value="selectedPoint?.user?.nickname || ''" disabled />
        </el-form-item>
        <el-form-item label="当前可用积分">
          <el-input :value="selectedPoint?.available_points || 0" disabled />
        </el-form-item>
        <el-form-item label="调整类型" prop="type">
          <el-select v-model="adjustForm.type" placeholder="选择调整类型">
            <el-option label="增加积分" value="earn" />
            <el-option label="扣除积分" value="redeem" />
            <el-option label="手动调整" value="adjust" />
          </el-select>
        </el-form-item>
        <el-form-item label="积分数量" prop="points">
          <el-input-number
            v-model="adjustForm.points"
            :min="adjustForm.type === 'redeem' ? -999999 : 1"
            :max="adjustForm.type === 'earn' ? 999999 : 999999"
            :precision="0"
            style="width: 100%"
          />
          <div class="text-xs text-gray-500 mt-1">
            {{ adjustForm.type === 'redeem' ? '请输入负数或正数（负数表示扣除）' : '请输入正数' }}
          </div>
        </el-form-item>
        <el-form-item label="说明" prop="description">
          <el-input
            v-model="adjustForm.description"
            type="textarea"
            :rows="3"
            placeholder="请输入调整原因或说明"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="adjustDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="adjusting" @click="handleSubmitAdjust">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Refresh, Search } from '@element-plus/icons-vue';
import { adminPointsApi, type MemberPoint, type PointTransaction } from '../api/points';
import { adminPointLevelApi, type PointLevel } from '../api/admin/point-level';

const loading = ref(false);
const adjusting = ref(false);
const points = ref<MemberPoint[]>([]);
const transactions = ref<PointTransaction[]>([]);
const availableLevels = ref<PointLevel[]>([]);
const searchKeyword = ref('');
const selectedLevel = ref('');
const currentPage = ref(1);
const pageSize = ref(15);
const total = ref(0);

const detailDialogVisible = ref(false);
const adjustDialogVisible = ref(false);
const selectedPoint = ref<MemberPoint | null>(null);
const adjustFormRef = ref();

const adjustForm = ref({
  type: 'adjust' as 'earn' | 'redeem' | 'adjust',
  points: 0,
  description: '',
});

const adjustRules = {
  type: [{ required: true, message: '请选择调整类型', trigger: 'change' }],
  points: [
    { required: true, message: '请输入积分数量', trigger: 'blur' },
    {
      validator: (rule: any, value: number, callback: Function) => {
        if (adjustForm.value.type === 'redeem' && value > 0) {
          callback(new Error('扣除积分请输入负数'));
        } else if (adjustForm.value.type === 'earn' && value <= 0) {
          callback(new Error('增加积分请输入正数'));
        } else if (value === 0) {
          callback(new Error('积分数量不能为0'));
        } else {
          callback();
        }
      },
      trigger: 'blur',
    },
  ],
  description: [{ required: true, message: '请输入调整说明', trigger: 'blur' }],
};

const getLevelText = (level: string) => {
  const levelObj = availableLevels.value.find(l => l.code === level);
  return levelObj ? levelObj.name : level;
};

const getLevelType = (level: string) => {
  const levelObj = availableLevels.value.find(l => l.code === level);
  if (!levelObj) return '';
  // 根据段位名称或颜色返回类型
  if (levelObj.color) {
    // 可以根据颜色映射到不同的 tag 类型
    return 'success';
  }
  return '';
};

const getTypeText = (type: string) => {
  const map: Record<string, string> = {
    earn: '获得',
    redeem: '兑换',
    expire: '过期',
    adjust: '调整',
  };
  return map[type] || type;
};

const formatDate = (date: string) => {
  if (!date) return '';
  return new Date(date).toLocaleString('zh-CN');
};

const fetchData = async () => {
  loading.value = true;
  try {
    const response = await adminPointsApi.getList({
      search: searchKeyword.value || undefined,
      level: selectedLevel.value || undefined,
      sort_by: 'total_points',
      sort_order: 'desc',
      per_page: pageSize.value,
      page: currentPage.value,
    });

    if (response.code === 200 && response.data) {
      points.value = response.data.points;
      total.value = response.data.pagination.total;
    }
  } catch (error: any) {
    console.error('获取积分列表失败:', error);
    ElMessage.error('获取积分列表失败');
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
  selectedLevel.value = '';
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

const handleViewDetail = async (point: MemberPoint) => {
  selectedPoint.value = point;
  detailDialogVisible.value = true;

  try {
    const response = await adminPointsApi.getTransactions(point.user_id);
    if (response.code === 200 && response.data) {
      transactions.value = response.data.transactions;
    }
  } catch (error: any) {
    console.error('获取交易记录失败:', error);
    ElMessage.error('获取交易记录失败');
  }
};

const handleAdjust = (point: MemberPoint) => {
  selectedPoint.value = point;
  adjustForm.value = {
    type: 'adjust',
    points: 0,
    description: '',
  };
  adjustDialogVisible.value = true;
};

const resetAdjustForm = () => {
  adjustFormRef.value?.resetFields();
  adjustForm.value = {
    type: 'adjust',
    points: 0,
    description: '',
  };
};

const handleSubmitAdjust = async () => {
  if (!adjustFormRef.value || !selectedPoint.value) return;

  await adjustFormRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    adjusting.value = true;
    try {
      // 如果是扣除类型，确保points是负数
      let pointsValue = adjustForm.value.points;
      if (adjustForm.value.type === 'redeem' && pointsValue > 0) {
        pointsValue = -pointsValue;
      }

      await adminPointsApi.adjustPoints(selectedPoint.value.user_id, {
        type: adjustForm.value.type,
        points: pointsValue,
        description: adjustForm.value.description,
      });

      ElMessage.success('积分调整成功');
      adjustDialogVisible.value = false;
      fetchData();
    } catch (error: any) {
      console.error('调整积分失败:', error);
      ElMessage.error(error.response?.data?.message || '调整积分失败');
    } finally {
      adjusting.value = false;
    }
  });
};

const fetchLevels = async () => {
  try {
    const response = await adminPointLevelApi.getActiveLevels();
    if (response.code === 200 && response.data) {
      availableLevels.value = response.data.levels;
    }
  } catch (error: any) {
    console.error('获取段位列表失败:', error);
  }
};

onMounted(() => {
  fetchLevels();
  fetchData();
});
</script>

<style scoped>
:deep(.el-table) {
  font-size: 14px;
}
</style>

