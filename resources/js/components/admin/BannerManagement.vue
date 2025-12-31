/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="mt-4">
    <!-- 操作栏 -->
    <div class="mb-4 flex justify-between items-center">
      <div>
        <el-button type="primary" @click="handleAdd">
          <el-icon><Plus /></el-icon>
          添加轮播图
        </el-button>
        <el-button type="success" :loading="savingOrder" @click="handleSaveOrder">
          保存排序
        </el-button>
      </div>
      <div>
        <el-switch
          v-model="showOnlyActive"
          active-text="仅显示启用"
          inactive-text="显示全部"
          @change="fetchBanners"
        />
      </div>
    </div>

    <!-- 轮播图列表 -->
    <el-table
      v-loading="loading"
      :data="banners"
      stripe
      border
      row-key="id"
      @row-drag-end="handleDragEnd"
    >
      <el-table-column type="index" label="排序" width="80" align="center">
        <template #default="{ $index }">
          <el-icon class="cursor-move"><Rank /></el-icon>
          {{ $index + 1 }}
        </template>
      </el-table-column>
      
      <el-table-column label="预览" width="150" align="center">
        <template #default="{ row }">
          <img
            :src="row.image_url"
            :alt="row.title"
            class="w-32 h-20 object-cover rounded cursor-pointer"
            @click="previewImage(row.image_url)"
          />
        </template>
      </el-table-column>

      <el-table-column prop="title" label="标题" min-width="150" />
      
      <el-table-column prop="description" label="描述" min-width="200" show-overflow-tooltip />
      
      <el-table-column label="链接" width="120" align="center">
        <template #default="{ row }">
          <el-tag v-if="row.link_type === 'none'" type="info">无链接</el-tag>
          <el-tag v-else-if="row.link_type === 'internal'" type="success">内部链接</el-tag>
          <el-tag v-else type="warning">外部链接</el-tag>
        </template>
      </el-table-column>

      <el-table-column prop="sort_order" label="排序值" width="100" align="center" />

      <el-table-column label="状态" width="100" align="center">
        <template #default="{ row }">
          <el-switch
            v-model="row.is_active"
            @change="handleToggleStatus(row)"
          />
        </template>
      </el-table-column>

      <el-table-column label="有效期" width="200">
        <template #default="{ row }">
          <div v-if="row.start_at || row.end_at" class="text-xs">
            <div v-if="row.start_at">开始：{{ formatDate(row.start_at) }}</div>
            <div v-if="row.end_at">结束：{{ formatDate(row.end_at) }}</div>
          </div>
          <span v-else class="text-gray-400">永久有效</span>
        </template>
      </el-table-column>

      <el-table-column label="操作" width="200" fixed="right" align="center">
        <template #default="{ row }">
          <el-button type="primary" size="small" @click="handleEdit(row)">
            编辑
          </el-button>
          <el-button type="danger" size="small" @click="handleDelete(row)">
            删除
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 添加/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="800px"
      @close="handleDialogClose"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
      >
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入标题" />
        </el-form-item>

        <el-form-item label="描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="请输入描述（可选）"
          />
        </el-form-item>

        <el-form-item label="图片" prop="image_url">
          <div class="w-full">
            <!-- 图片上传 -->
            <el-upload
              class="upload-demo"
              :http-request="handleCustomUpload"
              :before-upload="beforeUpload"
              :show-file-list="false"
              accept="image/*"
            >
              <el-button type="primary" :loading="uploading">
                <el-icon><Upload /></el-icon>
                {{ uploading ? '上传中...' : '选择图片' }}
              </el-button>
              <template #tip>
                <div class="el-upload__tip text-gray-500 text-xs mt-1">
                  支持 jpg、png、gif、webp 格式，文件大小不超过 5MB
                </div>
              </template>
            </el-upload>
            
            <!-- 或者手动输入URL -->
            <div class="mt-3">
              <el-input
                v-model="form.image_url"
                placeholder="或直接输入图片URL"
                class="w-full"
              >
                <template #append>
                  <el-button @click="form.image_url = ''">清除</el-button>
                </template>
              </el-input>
            </div>
            
                <!-- 图片预览 -->
                <div v-if="form.image_url" class="mt-3">
                  <img
                    :src="form.image_url"
                    alt="预览"
                    class="w-full h-48 object-cover rounded border border-gray-200"
                  />
              <div class="mt-2 flex justify-end">
                <el-button size="small" type="danger" @click="form.image_url = ''">
                  删除图片
                </el-button>
              </div>
            </div>
          </div>
        </el-form-item>

        <el-form-item label="链接类型" prop="link_type">
          <el-select v-model="form.link_type" placeholder="请选择链接类型">
            <el-option label="无链接" value="none" />
            <el-option label="内部链接" value="internal" />
            <el-option label="外部链接" value="external" />
          </el-select>
        </el-form-item>

        <el-form-item
          v-if="form.link_type !== 'none'"
          label="链接URL"
          prop="link_url"
        >
          <el-input
            v-model="form.link_url"
            placeholder="请输入链接URL"
          />
        </el-form-item>

        <el-form-item label="排序值" prop="sort_order">
          <el-input-number
            v-model="form.sort_order"
            :min="0"
            :max="9999"
          />
        </el-form-item>

        <el-form-item label="状态" prop="is_active">
          <el-switch v-model="form.is_active" />
        </el-form-item>

        <el-form-item label="开始时间" prop="start_at">
          <el-date-picker
            v-model="form.start_at"
            type="datetime"
            placeholder="选择开始时间（可选）"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="结束时间" prop="end_at">
          <el-date-picker
            v-model="form.end_at"
            type="datetime"
            placeholder="选择结束时间（可选）"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>

    <!-- 图片预览对话框 -->
    <el-dialog v-model="previewVisible" title="图片预览" width="800px">
      <img :src="previewImageUrl" alt="预览" class="w-full object-cover rounded" />
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Rank, Upload } from '@element-plus/icons-vue';
import { adminBannerApi, type Banner } from '../../api/banner';
import adminApiClient from '../../api/admin-client';

const loading = ref(false);
const saving = ref(false);
const savingOrder = ref(false);
const uploading = ref(false);
const banners = ref<Banner[]>([]);
const showOnlyActive = ref(false);
const dialogVisible = ref(false);
const previewVisible = ref(false);
const previewImageUrl = ref('');
const formRef = ref();
const editingId = ref<number | null>(null);

const dialogTitle = computed(() => {
  return editingId.value ? '编辑轮播图' : '添加轮播图';
});

const form = ref<Partial<Banner>>({
  title: '',
  description: '',
  image_url: '',
  link_url: '',
  link_type: 'none',
  sort_order: 0,
  is_active: true,
  start_at: undefined,
  end_at: undefined,
});

const rules = {
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
  image_url: [
    { required: true, message: '请输入图片URL', trigger: 'blur' },
    { type: 'url', message: '请输入有效的URL', trigger: 'blur' },
  ],
  link_url: [
    {
      validator: (rule: any, value: string, callback: Function) => {
        if (form.value.link_type !== 'none' && !value) {
          callback(new Error('请输入链接URL'));
        } else {
          callback();
        }
      },
      trigger: 'blur',
    },
  ],
};

const formatDate = (date: string) => {
  if (!date) return '';
  return new Date(date).toLocaleString('zh-CN');
};

const fetchBanners = async () => {
  loading.value = true;
  try {
    const response = await adminBannerApi.getList({
      is_active: showOnlyActive.value ? true : undefined,
    });
    if (response.code === 200 && response.data) {
      banners.value = response.data.banners || [];
    }
  } catch (error: any) {
    console.error('获取轮播图列表失败:', error);
    ElMessage.error('获取轮播图列表失败');
  } finally {
    loading.value = false;
  }
};

const handleAdd = () => {
  editingId.value = null;
  form.value = {
    title: '',
    description: '',
    image_url: '',
    link_url: '',
    link_type: 'none',
    sort_order: banners.value.length > 0 ? Math.max(...banners.value.map(b => b.sort_order)) + 1 : 0,
    is_active: true,
    start_at: undefined,
    end_at: undefined,
  };
  dialogVisible.value = true;
};

const handleEdit = (banner: Banner) => {
  editingId.value = banner.id;
  form.value = { ...banner };
  dialogVisible.value = true;
};

const handleDelete = async (banner: Banner) => {
  try {
    await ElMessageBox.confirm(`确定要删除轮播图"${banner.title}"吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });

    await adminBannerApi.delete(banner.id);
    ElMessage.success('删除成功');
    fetchBanners();
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除轮播图失败:', error);
      ElMessage.error('删除轮播图失败');
    }
  }
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    saving.value = true;
    try {
      if (editingId.value) {
        await adminBannerApi.update(editingId.value, form.value);
        ElMessage.success('更新成功');
      } else {
        await adminBannerApi.create(form.value);
        ElMessage.success('创建成功');
      }
      dialogVisible.value = false;
      fetchBanners();
    } catch (error: any) {
      console.error('保存轮播图失败:', error);
      ElMessage.error(error.response?.data?.message || '保存轮播图失败');
    } finally {
      saving.value = false;
    }
  });
};

const handleDialogClose = () => {
  editingId.value = null;
  formRef.value?.resetFields();
};

const handleToggleStatus = async (banner: Banner) => {
  try {
    await adminBannerApi.update(banner.id, {
      is_active: banner.is_active,
    });
    ElMessage.success('状态更新成功');
  } catch (error: any) {
    console.error('更新状态失败:', error);
    ElMessage.error('更新状态失败');
    // 恢复原状态
    banner.is_active = !banner.is_active;
  }
};

const handleDragEnd = () => {
  // 拖拽结束后更新排序值
  banners.value.forEach((banner, index) => {
    banner.sort_order = index;
  });
};

const handleSaveOrder = async () => {
  savingOrder.value = true;
  try {
    const orderData = banners.value.map((banner, index) => ({
      id: banner.id,
      sort_order: index,
    }));
    await adminBannerApi.updateOrder(orderData);
    ElMessage.success('排序保存成功');
  } catch (error: any) {
    console.error('保存排序失败:', error);
    ElMessage.error('保存排序失败');
  } finally {
    savingOrder.value = false;
  }
};

const previewImage = (url: string) => {
  previewImageUrl.value = url;
  previewVisible.value = true;
};

// 图片上传相关
const beforeUpload = (file: File) => {
  const isValidType = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif'].includes(file.type);
  const isLt5M = file.size / 1024 / 1024 < 5;

  if (!isValidType) {
    ElMessage.error('只能上传图片格式文件（jpg、png、gif、webp、avif）');
    return false;
  }
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB');
    return false;
  }
  return true;
};

const handleCustomUpload = async (options: any) => {
  const { file } = options;
  uploading.value = true;
  
  try {
    const formData = new FormData();
    formData.append('image', file);
    
    const token = sessionStorage.getItem('admin_token');
    
    if (!token) {
      ElMessage.error('请先登录');
      uploading.value = false;
      return;
    }
    
    console.log('开始上传图片，文件大小:', file.size, 'bytes');
    console.log('文件类型:', file.type);
    console.log('文件名:', file.name);
    console.log('FormData 内容:', formData);
    
    // 验证 FormData 中是否有文件
    if (!formData.has('image')) {
      ElMessage.error('文件准备失败，请重试');
      uploading.value = false;
      return;
    }
    
    // 使用原生 fetch API，避免 axios 处理 FormData 的问题
    const response = await fetch('/api/admin/v1/upload/image', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        // 不要手动设置 Content-Type，让浏览器自动设置（包含 boundary）
        // 如果手动设置会覆盖 boundary，导致文件无法正确解析
      },
      body: formData,
      credentials: 'include', // 包含 cookies（如果需要）
    });
    
    console.log('上传响应状态:', response.status);
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('上传失败响应:', errorText);
      let errorData;
      try {
        errorData = JSON.parse(errorText);
      } catch {
        errorData = { message: errorText || '上传失败' };
      }
      throw new Error(errorData.message || `上传失败: ${response.status}`);
    }
    
    const result = await response.json();
    console.log('上传成功响应:', result);
    
    if (result && result.code === 200 && result.data) {
      // 构建完整的URL
      // 后端现在返回完整的URL，直接使用即可
      form.value.image_url = result.data.url;
      ElMessage.success('图片上传成功');
    } else {
      ElMessage.error(result?.message || '上传失败');
    }
  } catch (error: any) {
    console.error('上传失败:', error);
    const errorMessage = error.message || '图片上传失败，请重试';
    ElMessage.error(errorMessage);
  } finally {
    uploading.value = false;
  }
};

onMounted(() => {
  fetchBanners();
});
</script>

<style scoped>
:deep(.el-table) {
  .el-table__row {
    cursor: move;
  }
}
</style>

