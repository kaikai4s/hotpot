/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">积分规则配置</h1>
          <p class="text-gray-600">管理积分获得、使用和过期规则</p>
        </div>
        <el-button type="primary" size="large" @click="handleCreate">
          <el-icon><Plus /></el-icon>
          新增规则
        </el-button>
      </div>

      <!-- 筛选栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-select v-model="filterType" placeholder="规则类型" clearable style="width: 150px" @change="fetchRules">
          <el-option label="获得规则" value="earn" />
          <el-option label="使用规则" value="use" />
          <el-option label="过期规则" value="expire" />
        </el-select>
        <el-select v-model="filterActive" placeholder="状态" clearable style="width: 150px" @change="fetchRules">
          <el-option label="启用" :value="true" />
          <el-option label="禁用" :value="false" />
        </el-select>
        <el-button @click="resetFilter">重置</el-button>
      </div>

      <!-- 规则列表 -->
      <el-table v-loading="loading" :data="rules" stripe style="width: 100%">
        <el-table-column prop="rule_key" label="规则键" width="150" />
        <el-table-column prop="rule_name" label="规则名称" width="200" />
        <el-table-column prop="rule_type" label="规则类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getRuleTypeTag(row.rule_type)">{{ getRuleTypeText(row.rule_type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="配置内容" min-width="350">
          <template #default="{ row }">
            <div class="config-display">
              <div v-if="row.rule_type === 'earn'" class="space-y-2">
                <!-- 订单获得积分配置 -->
                <div v-if="row.config.source === 'order'" class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                  <div class="flex items-center mb-2">
                    <el-icon class="text-blue-500 mr-1"><ShoppingCart /></el-icon>
                    <span class="font-semibold text-blue-700">订单获得积分</span>
                  </div>
                  <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                      <span class="text-gray-600">基础比例：</span>
                      <span class="font-medium text-gray-800">{{ row.config.base_ratio }} 元 = 1 积分</span>
                    </div>
                    <div v-if="row.config.min_amount !== undefined">
                      <span class="text-gray-600">最低金额：</span>
                      <span class="font-medium text-gray-800">¥{{ row.config.min_amount }}</span>
                    </div>
                    <div v-if="row.config.max_points_per_order" class="col-span-2">
                      <span class="text-gray-600">单笔最高积分：</span>
                      <span class="font-medium text-gray-800">{{ row.config.max_points_per_order }} 积分</span>
                    </div>
                    <div v-if="row.config.level_multiplier" class="col-span-2 mt-2">
                      <div class="text-gray-600 mb-1">会员等级倍数：</div>
                      <div class="flex flex-wrap gap-2">
                        <el-tag v-for="item in getSortedLevelMultipliers(row.config.level_multiplier)" :key="item.level" size="small" type="info">
                          {{ getLevelName(item.level) }}: {{ item.multiplier }}x
                        </el-tag>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- 评价获得积分配置 -->
                <div v-else-if="row.config.source === 'review'" class="bg-green-50 p-3 rounded-lg border border-green-200">
                  <div class="flex items-center mb-2">
                    <el-icon class="text-green-500 mr-1"><Star /></el-icon>
                    <span class="font-semibold text-green-700">评价获得积分</span>
                  </div>
                  <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                      <span class="text-gray-600">基础积分：</span>
                      <span class="font-medium text-gray-800">{{ row.config.base_points }} 积分</span>
                    </div>
                    <div v-if="row.config.with_image_bonus">
                      <span class="text-gray-600">带图奖励：</span>
                      <span class="font-medium text-green-600">+{{ row.config.with_image_bonus }} 积分</span>
                    </div>
                    <div v-if="row.config.first_review_bonus">
                      <span class="text-gray-600">首次评价：</span>
                      <span class="font-medium text-green-600">+{{ row.config.first_review_bonus }} 积分</span>
                    </div>
                  </div>
                </div>
                <!-- 评价采纳获得积分配置 -->
                <div v-else-if="row.config.source === 'review_adoption' || row.rule_key === 'review_adoption'" class="bg-purple-50 p-3 rounded-lg border border-purple-200">
                  <div class="flex items-center mb-2">
                    <el-icon class="text-purple-500 mr-1"><Star /></el-icon>
                    <span class="font-semibold text-purple-700">评价采纳获得积分</span>
                  </div>
                  <div class="text-sm">
                    <div>
                      <span class="text-gray-600">采纳奖励积分：</span>
                      <span class="font-medium text-purple-600">{{ row.config.base_points || 200 }} 积分</span>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">
                      当用户的评价建议被管理员采纳后，将获得此积分奖励
                    </div>
                  </div>
                </div>
              </div>
              <!-- 使用规则配置 -->
              <div v-else-if="row.rule_type === 'use'" class="bg-orange-50 p-3 rounded-lg border border-orange-200">
                <div class="flex items-center mb-2">
                  <el-icon class="text-orange-500 mr-1"><Money /></el-icon>
                  <span class="font-semibold text-orange-700">积分使用规则</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                  <div>
                    <span class="text-gray-600">使用比例：</span>
                    <span class="font-medium text-gray-800">{{ row.config.use_ratio }} 积分 = 1 元</span>
                  </div>
                  <div>
                    <span class="text-gray-600">最低使用：</span>
                    <span class="font-medium text-gray-800">{{ row.config.min_points }} 积分</span>
                  </div>
                  <div class="col-span-2">
                    <span class="text-gray-600">最高抵扣：</span>
                    <span class="font-medium text-gray-800">订单金额的 {{ row.config.max_percent }}%</span>
                  </div>
                </div>
              </div>
              <!-- 过期规则配置 -->
              <div v-else-if="row.rule_type === 'expire'" class="bg-red-50 p-3 rounded-lg border border-red-200">
                <div class="flex items-center mb-2">
                  <el-icon class="text-red-500 mr-1"><Clock /></el-icon>
                  <span class="font-semibold text-red-700">积分过期规则</span>
                </div>
                <div class="text-sm">
                  <span class="text-gray-600">有效期：</span>
                  <span class="font-medium text-gray-800">{{ row.config.expire_days }} 天</span>
                </div>
              </div>
              <!-- 未知配置格式，显示JSON -->
              <div v-else class="bg-gray-50 p-2 rounded">
                <pre class="text-xs overflow-auto max-h-32">{{ JSON.stringify(row.config, null, 2) }}</pre>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="is_active" label="状态" width="80">
          <template #default="{ row }">
            <el-switch v-model="row.is_active" @change="handleToggleActive(row)" />
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" width="80" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 创建/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="800px"
      @close="resetForm"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="formRules"
        label-width="120px"
      >
        <el-form-item label="规则键" prop="rule_key">
          <el-input v-model="form.rule_key" :disabled="isEdit" placeholder="如：order_earn" />
          <div class="text-xs text-gray-500 mt-1">唯一标识，创建后不可修改</div>
        </el-form-item>
        <el-form-item label="规则名称" prop="rule_name">
          <el-input v-model="form.rule_name" placeholder="如：订单完成获得积分" />
        </el-form-item>
        <el-form-item label="规则类型" prop="rule_type">
          <el-select v-model="form.rule_type" placeholder="请选择规则类型" class="w-full">
            <el-option label="获得规则" value="earn" />
            <el-option label="使用规则" value="use" />
            <el-option label="过期规则" value="expire" />
          </el-select>
        </el-form-item>
        <el-form-item label="配置内容" prop="config">
          <el-input
            v-model="configJson"
            type="textarea"
            :rows="10"
            placeholder='请输入JSON格式配置，如：{"base_ratio": 1.0, "level_multiplier": {...}}'
            @blur="validateConfigJson"
          />
          <div class="text-xs text-gray-500 mt-1">请输入有效的JSON格式</div>
        </el-form-item>
        <el-form-item label="是否启用" prop="is_active">
          <el-switch v-model="form.is_active" />
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number v-model="form.sort_order" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, ShoppingCart, Star, Money, Clock } from '@element-plus/icons-vue';
import { adminPointRuleApi, type PointRule } from '../api/point-rule';
import { adminPointLevelApi } from '../api/admin/point-level';

const loading = ref(false);
const saving = ref(false);
const rules = ref<PointRule[]>([]);
const levels = ref<any[]>([]);
const filterType = ref<string>('');
const filterActive = ref<boolean | null>(null);

const dialogVisible = ref(false);
const isEdit = ref(false);
const formRef = ref();
const form = ref<Partial<PointRule>>({
  rule_key: '',
  rule_name: '',
  rule_type: 'earn',
  config: {},
  is_active: true,
  sort_order: 0,
});
const configJson = ref('{}');

const dialogTitle = computed(() => (isEdit.value ? '编辑积分规则' : '新增积分规则'));

const formRules = {
  rule_key: [{ required: true, message: '请输入规则键', trigger: 'blur' }],
  rule_name: [{ required: true, message: '请输入规则名称', trigger: 'blur' }],
  rule_type: [{ required: true, message: '请选择规则类型', trigger: 'change' }],
  config: [{ required: true, message: '请输入配置内容', trigger: 'blur' }],
};

const getRuleTypeText = (type: string) => {
  const map: Record<string, string> = {
    earn: '获得规则',
    use: '使用规则',
    expire: '过期规则',
  };
  return map[type] || type;
};

const getRuleTypeTag = (type: string) => {
  const map: Record<string, string> = {
    earn: 'success',
    use: 'warning',
    expire: 'danger',
  };
  return map[type] || '';
};

const getLevelName = (level: string) => {
  // 先从段位列表中查找
  const levelObj = levels.value.find((l: any) => l.code === level);
  if (levelObj) {
    return levelObj.name;
  }
  
  // 兼容旧代码
  const map: Record<string, string> = {
    bronze: '青铜',
    silver: '白银',
    gold: '黄金',
    platinum: '白金',
  };
  return map[level] || level;
};

const getSortedLevelMultipliers = (levelMultiplier: Record<string, number>) => {
  if (!levelMultiplier) return [];
  return Object.entries(levelMultiplier)
    .map(([level, multiplier]) => ({ level, multiplier }))
    .sort((a, b) => a.multiplier - b.multiplier);
};

const fetchRules = async () => {
  loading.value = true;
  try {
    const response = await adminPointRuleApi.getList({
      rule_type: filterType.value || undefined,
      is_active: filterActive.value !== null ? filterActive.value : undefined,
    });
    if (response.code === 200 && response.data) {
      rules.value = response.data.rules;
    }
  } catch (error: any) {
    console.error('获取规则列表失败:', error);
    ElMessage.error('获取规则列表失败');
  } finally {
    loading.value = false;
  }
};

const fetchLevels = async () => {
  try {
    const response = await adminPointLevelApi.getList({ is_active: true, per_page: 100 });
    if (response.code === 200 && response.data) {
      levels.value = response.data.levels || [];
    }
  } catch (error: any) {
    console.error('获取段位列表失败:', error);
  }
};

const resetFilter = () => {
  filterType.value = '';
  filterActive.value = null;
  fetchRules();
};

const handleCreate = () => {
  isEdit.value = false;
  form.value = {
    rule_key: '',
    rule_name: '',
    rule_type: 'earn',
    config: {},
    is_active: true,
    sort_order: 0,
  };
  configJson.value = '{}';
  dialogVisible.value = true;
};

const handleEdit = (rule: PointRule) => {
  isEdit.value = true;
  form.value = { ...rule };
  configJson.value = JSON.stringify(rule.config, null, 2);
  dialogVisible.value = true;
};

const validateConfigJson = () => {
  try {
    const parsed = JSON.parse(configJson.value);
    form.value.config = parsed;
  } catch (e) {
    ElMessage.warning('JSON格式无效，请检查');
  }
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    try {
      validateConfigJson();
      saving.value = true;

      const payload = {
        ...form.value,
        config: form.value.config!,
      };

      if (isEdit.value) {
        await adminPointRuleApi.update(form.value.id!, payload);
        ElMessage.success('规则更新成功');
      } else {
        await adminPointRuleApi.create(payload);
        ElMessage.success('规则创建成功');
      }

      dialogVisible.value = false;
      fetchRules();
    } catch (error: any) {
      console.error('保存规则失败:', error);
      ElMessage.error(error.response?.data?.message || '保存规则失败');
    } finally {
      saving.value = false;
    }
  });
};

const handleToggleActive = async (rule: PointRule) => {
  try {
    await adminPointRuleApi.update(rule.id, { is_active: rule.is_active });
    ElMessage.success('状态更新成功');
  } catch (error: any) {
    console.error('更新状态失败:', error);
    ElMessage.error('更新状态失败');
    rule.is_active = !rule.is_active; // 回滚
  }
};

const handleDelete = async (rule: PointRule) => {
  try {
    await ElMessageBox.confirm(`确定要删除规则"${rule.rule_name}"吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });

    await adminPointRuleApi.delete(rule.id);
    ElMessage.success('删除成功');
    fetchRules();
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除规则失败:', error);
      ElMessage.error('删除规则失败');
    }
  }
};

const resetForm = () => {
  formRef.value?.resetFields();
  form.value = {
    rule_key: '',
    rule_name: '',
    rule_type: 'earn',
    config: {},
    is_active: true,
    sort_order: 0,
  };
  configJson.value = '{}';
};

onMounted(() => {
  fetchLevels();
  fetchRules();
});
</script>

<style scoped>
pre {
  font-family: 'Courier New', monospace;
  font-size: 12px;
  line-height: 1.4;
}
</style>

