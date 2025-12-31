/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">积分段位管理</h1>
          <p class="text-gray-600">管理和配置用户积分段位及折扣规则</p>
        </div>
        <div class="flex gap-2">
          <el-button type="warning" size="large" @click="handleUpdateAllLevels" :loading="updatingLevels">
            <el-icon><Refresh /></el-icon>
            批量更新用户段位
          </el-button>
          <el-button type="primary" size="large" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            添加段位
          </el-button>
        </div>
      </div>

      <!-- 搜索栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索段位名称、代码"
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
        :data="levels"
        stripe
        style="width: 100%"
        class="mb-4"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="段位名称" min-width="150">
          <template #default="{ row }">
            <div class="flex items-center gap-2">
              <img
                v-if="row.icon"
                :src="row.icon"
                :alt="row.name"
                class="w-6 h-6 object-contain"
                loading="lazy"
                @error="handleIconError"
              />
              <div
                v-if="row.color"
                class="w-4 h-4 rounded-full flex-shrink-0"
                :style="{ backgroundColor: row.color }"
              ></div>
              <span
                class="font-semibold"
                :style="row.color ? { color: row.color } : {}"
              >{{ row.name }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="code" label="段位代码" width="120" />
        <el-table-column prop="min_points" label="最低积分" width="120" sortable>
          <template #default="{ row }">
            <span class="font-bold text-blue-600">{{ row.min_points }}</span>
          </template>
        </el-table-column>
        <el-table-column label="折扣规则" min-width="200">
          <template #default="{ row }">
            <div v-if="row.discount_type === 'none'" class="text-gray-400">无折扣</div>
            <div v-else-if="row.discount_type === 'percentage'" class="text-purple-600">
              <span class="font-bold">{{ row.discount_value }}%</span>
              <span v-if="row.max_discount_amount" class="text-xs text-gray-500 ml-1">
                (最高¥{{ row.max_discount_amount }})
              </span>
            </div>
            <div v-else-if="row.discount_type === 'fixed'" class="text-green-600">
              <span class="font-bold">¥{{ row.discount_value }}</span>
            </div>
            <div v-if="row.min_order_amount > 0" class="text-xs text-gray-500 mt-1">
              最低订单金额：¥{{ row.min_order_amount }}
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
        <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />
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
      :title="isEdit ? '编辑段位' : '添加段位'"
      width="600px"
      @close="resetForm"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
      >
        <el-form-item label="段位名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入段位名称" />
        </el-form-item>
        <el-form-item label="段位代码" prop="code">
          <el-input
            v-model="form.code"
            placeholder="请输入段位代码（唯一标识，如：bronze）"
            :disabled="isEdit"
          />
          <div class="text-xs text-gray-500 mt-1">代码一旦创建不可修改</div>
        </el-form-item>
        <el-form-item label="最低积分" prop="min_points">
          <el-input-number
            v-model="form.min_points"
            :min="0"
            :precision="0"
            style="width: 100%"
            placeholder="达到该段位所需的最低积分"
          />
        </el-form-item>
        <el-form-item label="折扣类型" prop="discount_type">
          <el-select v-model="form.discount_type" placeholder="选择折扣类型" style="width: 100%">
            <el-option label="无折扣" value="none" />
            <el-option label="百分比折扣" value="percentage" />
            <el-option label="固定金额折扣" value="fixed" />
          </el-select>
        </el-form-item>
        <el-form-item
          v-if="form.discount_type !== 'none'"
          label="折扣值"
          prop="discount_value"
        >
          <el-input-number
            v-model="form.discount_value"
            :min="0"
            :max="form.discount_type === 'percentage' ? 100 : undefined"
            :precision="2"
            style="width: 100%"
            placeholder="请输入折扣值"
          />
          <div class="text-xs text-gray-500 mt-1">
            {{ form.discount_type === 'percentage' ? '百分比（0-100），如：5 表示 95折' : '固定金额（元）' }}
          </div>
        </el-form-item>
        <el-form-item
          v-if="form.discount_type === 'percentage'"
          label="最大折扣金额"
          prop="max_discount_amount"
        >
          <el-input-number
            v-model="form.max_discount_amount"
            :min="0"
            :precision="2"
            style="width: 100%"
            placeholder="可选，限制最大折扣金额"
          />
        </el-form-item>
        <el-form-item label="最低订单金额" prop="min_order_amount">
          <el-input-number
            v-model="form.min_order_amount"
            :min="0"
            :precision="2"
            style="width: 100%"
            placeholder="达到此金额才能享受折扣"
          />
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number
            v-model="form.sort_order"
            :min="0"
            :precision="0"
            style="width: 100%"
            placeholder="数字越小越靠前"
          />
        </el-form-item>
        <el-form-item label="是否启用" prop="is_active">
          <el-switch v-model="form.is_active" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="请输入段位描述"
          />
        </el-form-item>
        <el-form-item label="图标" prop="icon">
          <div class="flex gap-4 items-start">
            <div class="flex-1">
              <el-select
                v-model="form.icon"
                placeholder="选择预设图标或上传自定义图标"
                clearable
                filterable
                style="width: 100%"
                @change="handleIconSelect"
              >
                <el-option-group label="无畏契约段位图标">
                  <el-option
                    v-for="icon in valorantIcons"
                    :key="icon.value"
                    :label="icon.label"
                    :value="icon.value"
                  >
                    <div class="flex items-center gap-2">
                      <img
                        v-if="icon.value"
                        :src="icon.value"
                        :alt="icon.label"
                        class="w-6 h-6 object-contain"
                        loading="lazy"
                        @error="handleIconError"
                        @load="(e) => { (e.target as HTMLImageElement).style.display = 'block'; }"
                      />
                      <span>{{ icon.label }}</span>
                    </div>
                  </el-option>
                </el-option-group>
                <el-option-group v-if="usedIcons.length > 0" label="已使用的图标">
                  <el-option
                    v-for="iconUrl in usedIcons"
                    :key="iconUrl"
                    :label="getIconLabel(iconUrl)"
                    :value="iconUrl"
                  >
                    <div class="flex items-center gap-2">
                      <img
                        :src="iconUrl"
                        alt="已使用图标"
                        class="w-6 h-6 object-contain"
                        loading="lazy"
                        @error="handleIconError"
                        @load="(e) => { (e.target as HTMLImageElement).style.display = 'block'; }"
                      />
                      <span>{{ getIconLabel(iconUrl) }}</span>
                    </div>
                  </el-option>
                </el-option-group>
                <el-option-group label="当前自定义图标">
                  <el-option
                    v-if="form.icon && !valorantIcons.find(i => i.value === form.icon) && !usedIcons.includes(form.icon)"
                    :label="'当前自定义图标'"
                    :value="form.icon"
                  >
                    <div class="flex items-center gap-2">
                      <img
                        :src="form.icon"
                        alt="自定义图标"
                        class="w-6 h-6 object-contain"
                        loading="lazy"
                        @error="handleIconError"
                        @load="(e) => { (e.target as HTMLImageElement).style.display = 'block'; }"
                      />
                      <span>当前自定义图标</span>
                    </div>
                  </el-option>
                </el-option-group>
              </el-select>
            </div>
            <div>
              <el-upload
                :action="uploadAction"
                :headers="getUploadHeaders()"
                :on-success="handleIconUploadSuccess"
                :on-error="handleIconUploadError"
                :before-upload="beforeIconUpload"
                :show-file-list="false"
                accept="image/*"
                name="image"
              >
                <el-button type="primary" :loading="iconUploading">
                  <el-icon><Upload /></el-icon>
                  上传图标
                </el-button>
              </el-upload>
            </div>
          </div>
          <div v-if="form.icon" class="mt-2">
            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded">
              <img
                :src="form.icon"
                alt="图标预览"
                class="w-8 h-8 object-contain"
                loading="lazy"
                @error="handleIconError"
                @load="(e) => { (e.target as HTMLImageElement).style.display = 'block'; }"
              />
              <span class="text-sm text-gray-600">当前图标预览</span>
              <el-button
                type="danger"
                link
                size="small"
                @click="form.icon = null"
              >
                清除
              </el-button>
            </div>
          </div>
        </el-form-item>
        <el-form-item label="颜色" prop="color">
          <div class="flex items-center gap-4">
            <el-color-picker
              v-model="form.color"
              :predefine="predefineColors"
              show-alpha
              color-format="hex"
              @change="handleColorChange"
            />
            <el-input
              v-model="form.color"
              placeholder="#000000"
              style="width: 150px"
              @input="handleColorInput"
              @blur="handleColorBlur"
            >
              <template #prefix>
                <div
                  v-if="form.color"
                  class="w-4 h-4 rounded border border-gray-300 flex-shrink-0"
                  :style="{ backgroundColor: form.color }"
                ></div>
                <span v-else class="text-gray-400">#</span>
              </template>
            </el-input>
            <el-button
              v-if="form.color"
              type="danger"
              link
              size="small"
              @click="handleClearColor"
            >
              清除
            </el-button>
          </div>
          <div v-if="form.color" class="mt-2 text-xs text-gray-500">
            当前颜色: {{ form.color }}
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Search, Upload, Refresh } from '@element-plus/icons-vue';
import { adminPointLevelApi, type PointLevel, type CreatePointLevelRequest } from '../api/admin/point-level';

const loading = ref(false);
const submitting = ref(false);
const updatingLevels = ref(false);
const levels = ref<PointLevel[]>([]);
const searchKeyword = ref('');
const selectedStatus = ref<boolean | null>(null);
const currentPage = ref(1);
const pageSize = ref(15);
const total = ref(0);
const usedIcons = ref<string[]>([]);

const dialogVisible = ref(false);
const isEdit = ref(false);
const formRef = ref();
const form = ref<CreatePointLevelRequest>({
  name: '',
  code: '',
  min_points: 0,
  discount_type: 'none',
  discount_value: 0,
  max_discount_amount: null,
  min_order_amount: 0,
  is_active: true,
  sort_order: 0,
  description: null,
  icon: null,
  color: null,
});

const rules = {
  name: [{ required: true, message: '请输入段位名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入段位代码', trigger: 'blur' }],
  min_points: [{ required: true, message: '请输入最低积分', trigger: 'blur' }],
  discount_type: [{ required: true, message: '请选择折扣类型', trigger: 'change' }],
  discount_value: [
    { required: true, message: '请输入折扣值', trigger: 'blur' },
    {
      validator: (rule: any, value: number, callback: Function) => {
        if (form.value.discount_type === 'percentage' && value > 100) {
          callback(new Error('百分比折扣值不能超过100'));
        } else {
          callback();
        }
      },
      trigger: 'blur',
    },
  ],
};

const fetchData = async () => {
  loading.value = true;
  try {
    const response = await adminPointLevelApi.getList({
      search: searchKeyword.value || undefined,
      is_active: selectedStatus.value !== null ? selectedStatus.value : undefined,
      sort_by: 'sort_order',
      sort_order: 'asc',
      per_page: pageSize.value,
      page: currentPage.value,
    });

    if (response.code === 200 && response.data) {
      // 确保数据正确映射，包括 color 和 icon
      levels.value = response.data.levels.map((level: PointLevel) => ({
        ...level,
        color: level.color || null,
        icon: level.icon || null,
      }));
      total.value = response.data.pagination.total;
      console.log('段位列表数据:', levels.value);
    }
  } catch (error: any) {
    console.error('获取段位列表失败:', error);
    ElMessage.error('获取段位列表失败');
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
  isEdit.value = false;
  resetForm();
  dialogVisible.value = true;
};

const handleEdit = (level: PointLevel) => {
  isEdit.value = true;
  form.value = {
    name: level.name,
    code: level.code,
    min_points: level.min_points,
    discount_type: level.discount_type,
    discount_value: level.discount_value,
    max_discount_amount: level.max_discount_amount,
    min_order_amount: level.min_order_amount,
    is_active: level.is_active,
    sort_order: level.sort_order,
    description: level.description || null,
    icon: level.icon || null,
    color: level.color || null,
  };
  dialogVisible.value = true;
  // 确保颜色选择器正确显示
  nextTick(() => {
    if (form.value.color) {
      // 强制更新颜色选择器
      const colorPicker = document.querySelector('.el-color-picker__trigger');
      if (colorPicker) {
        (colorPicker as HTMLElement).style.backgroundColor = form.value.color;
      }
    }
  });
};

const handleDelete = async (level: PointLevel) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除段位"${level.name}"吗？`,
      '确认删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    );

    await adminPointLevelApi.delete(level.id);
    ElMessage.success('删除成功');
    fetchData();
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除段位失败:', error);
      ElMessage.error(error.response?.data?.message || '删除失败');
    }
  }
};

const handleToggleStatus = async (level: PointLevel) => {
  try {
    await adminPointLevelApi.toggleActive(level.id);
    ElMessage.success('操作成功');
    fetchData();
  } catch (error: any) {
    console.error('切换状态失败:', error);
    ElMessage.error('切换状态失败');
    // 恢复原状态
    level.is_active = !level.is_active;
  }
};

// 无畏契约段位图标列表（使用本地资源）
// 图标文件存储在 public/images/point-levels/ 目录
const valorantIcons = ref([
  { label: '铁牌 (Iron)', value: '/images/point-levels/tier_1.png' },
  { label: '铜牌 (Bronze)', value: '/images/point-levels/tier_2.png' },
  { label: '银牌 (Silver)', value: '/images/point-levels/tier_3.png' },
  { label: '金牌 (Gold)', value: '/images/point-levels/tier_4.png' },
  { label: '白金 (Platinum)', value: '/images/point-levels/tier_5.png' },
  { label: '钻石 (Diamond)', value: '/images/point-levels/tier_6.png' },
  { label: '超凡入圣 (Ascendant)', value: '/images/point-levels/tier_7.png' },
  { label: '不朽 (Immortal)', value: '/images/point-levels/tier_8.png' },
  { label: '神话 (Radiant)', value: '/images/point-levels/tier_9.png' },
]);

// 占位符图标（SVG data URI）
const placeholderIcon = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDJMMTMuMDkgOC4yNkwyMCA5TDEzLjA5IDE1Ljc0TDEyIDIyTDEwLjkxIDE1Ljc0TDQgOUwxMC45MSA4LjI2TDEyIDJaIiBmaWxsPSIjOUI5QkE1Ii8+Cjwvc3ZnPgo=';

// 预设颜色
const predefineColors = ref([
  '#000000', // 黑色
  '#FFFFFF', // 白色
  '#FF0000', // 红色
  '#00FF00', // 绿色
  '#0000FF', // 蓝色
  '#FFFF00', // 黄色
  '#FF00FF', // 紫色
  '#00FFFF', // 青色
  '#FFA500', // 橙色
  '#800080', // 紫色
  '#FFC0CB', // 粉色
  '#A52A2A', // 棕色
  '#808080', // 灰色
  '#FFD700', // 金色
  '#C0C0C0', // 银色
]);

// 图标上传相关
const iconUploading = ref(false);
const uploadAction = ref('/api/admin/v1/upload/image');
const getUploadHeaders = () => {
  const token = sessionStorage.getItem('admin_token');
  return {
    Authorization: token ? `Bearer ${token}` : '',
  };
};

// 图标选择处理
const handleIconSelect = (value: string | null) => {
  if (value) {
    form.value.icon = value;
  }
};

// 图标上传前验证
const beforeIconUpload = (file: File) => {
  // 明确支持的文件类型（与后端保持一致）
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
  const isValidType = allowedTypes.includes(file.type);
  const isLt5M = file.size / 1024 / 1024 < 5;

  if (!isValidType) {
    ElMessage.error('只能上传图片文件（jpg、png、gif、webp、avif）！');
    return false;
  }
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB！');
    return false;
  }

  iconUploading.value = true;
  return true;
};

// 图标上传成功
const handleIconUploadSuccess = async (response: any) => {
  iconUploading.value = false;
  if (response.code === 200 && response.data) {
    form.value.icon = response.data.url;
    ElMessage.success('图标上传成功');
    // 刷新已使用的图标列表
    await fetchUsedIcons();
    await nextTick();
  } else {
    ElMessage.error(response.message || '图标上传失败');
  }
};

// 图标上传失败
const handleIconUploadError = (error: any, file?: any, fileList?: any) => {
  iconUploading.value = false;
  console.error('图标上传失败:', error);
  
  // 尝试从错误响应中提取错误信息
  let errorMessage = '图标上传失败，请重试';
  if (error?.response?.data?.message) {
    errorMessage = error.response.data.message;
  } else if (error?.message) {
    errorMessage = error.message;
  } else if (typeof error === 'string') {
    errorMessage = error;
  }
  
  ElMessage.error(errorMessage);
};

// 图标加载错误处理 - 使用占位符替代隐藏图片
const handleIconError = (event: Event) => {
  const img = event.target as HTMLImageElement;
  // 如果图片加载失败，使用占位符图标
  if (img.src !== placeholderIcon) {
    img.src = placeholderIcon;
    img.alt = '图标加载失败';
    img.onerror = null; // 防止无限循环
  } else {
    // 如果占位符也加载失败，则隐藏图片
    img.style.display = 'none';
  }
};

// 颜色变化处理
const handleColorChange = (value: string | null) => {
  console.log('颜色选择器变化:', value);
  if (value) {
    // 确保颜色格式正确（hex格式）
    let colorValue = value;
    if (!colorValue.startsWith('#')) {
      colorValue = '#' + colorValue;
    }
    // 如果是3位hex，转换为6位
    if (colorValue.length === 4) {
      colorValue = '#' + colorValue[1] + colorValue[1] + colorValue[2] + colorValue[2] + colorValue[3] + colorValue[3];
    }
    form.value.color = colorValue.toUpperCase();
    console.log('设置颜色为:', form.value.color);
  } else {
    form.value.color = null;
  }
};

// 颜色输入处理
const handleColorInput = (value: string) => {
  // 移除 # 符号（如果有）
  let cleanValue = value.replace(/^#/, '');
  
  // 验证颜色格式
  if (cleanValue && /^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(cleanValue)) {
    // 如果是3位hex，转换为6位
    if (cleanValue.length === 3) {
      cleanValue = cleanValue[0] + cleanValue[0] + cleanValue[1] + cleanValue[1] + cleanValue[2] + cleanValue[2];
    }
    form.value.color = '#' + cleanValue.toUpperCase();
  } else if (value === '' || value === '#') {
    form.value.color = null;
  }
};

// 颜色输入失焦处理
const handleColorBlur = () => {
  if (form.value.color && !form.value.color.startsWith('#')) {
    form.value.color = '#' + form.value.color;
  }
};

// 清除颜色
const handleClearColor = () => {
  form.value.color = null;
  nextTick(() => {
    // 强制更新颜色选择器
    const colorPicker = document.querySelector('.el-color-picker__trigger');
    if (colorPicker) {
      (colorPicker as HTMLElement).style.backgroundColor = '';
    }
  });
};

const resetForm = () => {
  formRef.value?.resetFields();
  form.value = {
    name: '',
    code: '',
    min_points: 0,
    discount_type: 'none',
    discount_value: 0,
    max_discount_amount: null,
    min_order_amount: 0,
    is_active: true,
    sort_order: 0,
    description: null,
    icon: null,
    color: null,
  };
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    submitting.value = true;
    try {
      // 准备提交数据，确保 color 和 icon 正确处理
      const submitData = {
        ...form.value,
        // 如果 color 是空字符串，转换为 null
        color: form.value.color && form.value.color.trim() ? form.value.color.trim() : null,
        // 如果 icon 是空字符串，转换为 null
        icon: form.value.icon && form.value.icon.trim() ? form.value.icon.trim() : null,
      };
      
      console.log('提交数据:', submitData);
      
      if (isEdit.value) {
        const levelId = levels.value.find(l => l.code === form.value.code)?.id;
        if (levelId) {
          const response = await adminPointLevelApi.update(levelId, submitData);
          console.log('更新响应:', response);
          ElMessage.success('更新成功');
        }
      } else {
        const response = await adminPointLevelApi.create(submitData);
        console.log('创建响应:', response);
        ElMessage.success('创建成功');
      }
      dialogVisible.value = false;
      // 延迟刷新，确保后端数据已更新
      await nextTick();
      await fetchData();
      // 刷新已使用的图标列表
      await fetchUsedIcons();
    } catch (error: any) {
      console.error('提交失败:', error);
      ElMessage.error(error.response?.data?.message || '提交失败');
    } finally {
      submitting.value = false;
    }
  });
};

// 获取已使用的图标列表
const fetchUsedIcons = async () => {
  try {
    const response = await adminPointLevelApi.getUsedIcons();
    if (response.code === 200 && response.data) {
      usedIcons.value = response.data.icons || [];
    }
  } catch (error: any) {
    console.error('获取已使用图标失败:', error);
  }
};

// 获取图标标签（用于显示）
const getIconLabel = (iconUrl: string): string => {
  // 如果是预设图标，返回对应的标签
  const presetIcon = valorantIcons.value.find(i => i.value === iconUrl);
  if (presetIcon) {
    return presetIcon.label;
  }
  // 否则返回文件名或URL的最后一部分
  const fileName = iconUrl.split('/').pop() || iconUrl;
  return fileName.length > 30 ? fileName.substring(0, 30) + '...' : fileName;
};

// 批量更新所有用户的段位
const handleUpdateAllLevels = async () => {
  try {
    await ElMessageBox.confirm(
      '确定要批量更新所有用户的段位吗？此操作将根据当前积分和段位配置重新计算每个用户的段位。',
      '确认批量更新',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    );

    updatingLevels.value = true;
    const response = await adminPointLevelApi.updateAllUserLevels();
    
    if (response.code === 200) {
      ElMessage.success(response.message || '批量更新成功');
      // 刷新列表数据
      await fetchData();
    } else {
      ElMessage.error(response.message || '批量更新失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('批量更新失败:', error);
      ElMessage.error(error.response?.data?.message || '批量更新失败');
    }
  } finally {
    updatingLevels.value = false;
  }
};

onMounted(() => {
  fetchData();
  fetchUsedIcons();
});
</script>

<style scoped>
:deep(.el-table) {
  font-size: 14px;
}
</style>

