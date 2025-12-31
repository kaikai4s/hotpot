<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">菜品管理</h1>
          <p class="text-gray-600">管理餐厅菜品信息</p>
        </div>
        <el-button type="primary" size="large" @click="handleAdd">
          <el-icon><Plus /></el-icon>
          添加菜品
        </el-button>
      </div>

      <!-- 搜索和筛选 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索菜品名称"
          class="w-64"
          clearable
          @input="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="categoryFilter" placeholder="分类筛选" clearable class="w-48" @change="handleSearch">
          <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
        </el-select>
        <el-select v-model="statusFilter" placeholder="状态筛选" clearable class="w-48" @change="handleSearch">
          <el-option label="在售" value="available" />
          <el-option label="售罄" value="sold_out" />
          <el-option label="下架" value="disabled" />
        </el-select>
      </div>

      <!-- 加载状态 -->
      <div v-if="loading" class="text-center py-12">
        <el-icon class="is-loading text-4xl text-blue-500"><Loading /></el-icon>
        <p class="mt-4 text-gray-600">加载中...</p>
      </div>

      <!-- 菜品网格 -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div
          v-for="dish in dishes"
          :key="dish.id"
          class="bg-white rounded-xl shadow-md overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl"
        >
          <div class="h-48 bg-gradient-to-br from-red-200 via-orange-200 to-yellow-200 flex items-center justify-center overflow-hidden">
            <img
              v-if="dish.image_url"
              :src="dish.image_url"
              :alt="dish.name"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-6xl">🍲</span>
          </div>
          <div class="p-4">
            <div class="flex justify-between items-start mb-2">
              <h3 class="text-lg font-bold text-gray-800">{{ dish.name }}</h3>
              <el-tag :type="dish.status === 'available' ? 'success' : dish.status === 'sold_out' ? 'warning' : 'info'">
                {{ dish.status === 'available' ? '在售' : dish.status === 'sold_out' ? '售罄' : '下架' }}
              </el-tag>
            </div>
            <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ dish.description || '暂无描述' }}</p>
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <el-rate v-model="dish.average_rating" disabled size="small" />
                <span class="text-xs text-gray-500">({{ dish.review_count }})</span>
              </div>
              <span class="text-xl font-bold text-red-600">¥{{ dish.price }}</span>
            </div>
            <div class="flex gap-2">
              <el-button type="primary" size="small" @click="editDish(dish)">编辑</el-button>
              <el-button type="danger" size="small" @click="deleteDish(dish.id)">删除</el-button>
            </div>
          </div>
        </div>
      </div>

      <!-- 分页 -->
      <div v-if="!loading && pagination.total > 0" class="mt-6 flex justify-center">
        <el-pagination
          v-model:current-page="pagination.current_page"
          v-model:page-size="pagination.per_page"
          :total="pagination.total"
          :page-sizes="[15, 30, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSearch"
          @current-change="handleSearch"
        />
      </div>
    </div>

    <!-- 添加/编辑对话框 -->
    <el-dialog
      v-model="showAddDialog"
      :title="editingDish ? '编辑菜品' : '添加菜品'"
      width="600px"
      @close="handleDialogClose"
    >
      <el-form ref="dishFormRef" :model="dishForm" :rules="dishRules" label-width="100px">
        <el-form-item label="菜品名称" prop="name">
          <el-input v-model="dishForm.name" placeholder="请输入菜品名称" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input v-model="dishForm.description" type="textarea" :rows="3" placeholder="请输入描述" />
        </el-form-item>
        <el-form-item label="价格" prop="price">
          <el-input-number v-model="dishForm.price" :min="0" :precision="2" style="width: 100%" />
        </el-form-item>
        <el-form-item label="分类" prop="category_id">
          <el-select v-model="dishForm.category_id" placeholder="请选择分类" style="width: 100%">
            <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="菜品图片">
          <el-upload
            class="avatar-uploader"
            :http-request="handleCustomUpload"
            :show-file-list="false"
            :before-upload="beforeUpload"
          >
            <img v-if="dishForm.image_url" :src="dishForm.image_url" class="avatar" @error="handleImageError" @load="handleImageLoad" />
            <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
          </el-upload>
          <div class="text-xs text-gray-500 mt-2">支持 JPG、PNG、GIF、WEBP 格式，最大 5MB</div>
          <div v-if="dishForm.image_url" class="text-xs text-blue-500 mt-1">
            当前图片: {{ dishForm.image_url }}
          </div>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-select v-model="dishForm.status" placeholder="请选择状态" style="width: 100%">
            <el-option label="在售" value="available" />
            <el-option label="售罄" value="sold_out" />
            <el-option label="下架" value="disabled" />
          </el-select>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="dishForm.sort_order" :min="0" style="width: 100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAddDialog = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="saveDish">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, nextTick } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Search, Loading } from '@element-plus/icons-vue';
import adminApiClient from '../api/admin-client';
import type { Dish } from '../types';

const loading = ref(false);
const saving = ref(false);
const uploading = ref(false);
const dishes = ref<Dish[]>([]);
const categories = ref<any[]>([]);
const searchKeyword = ref('');
const categoryFilter = ref('');
const statusFilter = ref('');
const showAddDialog = ref(false);
const editingDish = ref<Dish | null>(null);
const dishFormRef = ref();

const pagination = ref({
  total: 0,
  current_page: 1,
  per_page: 15,
  last_page: 1,
});

const dishForm = ref({
  name: '',
  description: '',
  price: 0,
  image_url: '',
  category_id: null,
  status: 'available',
  sort_order: 0,
});

const dishRules = {
  name: [{ required: true, message: '请输入菜品名称', trigger: 'blur' }],
  price: [{ required: true, message: '请输入价格', trigger: 'blur' }],
  category_id: [{ required: true, message: '请选择分类', trigger: 'change' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }],
};

// 图片上传
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
    
    const response = await fetch('/api/admin/v1/upload/image', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
      },
      body: formData,
      credentials: 'include',
    });
    
    if (!response.ok) {
      const errorText = await response.text();
      let errorData;
      try {
        errorData = JSON.parse(errorText);
      } catch {
        errorData = { message: '上传失败' };
      }
      ElMessage.error(errorData.message || '图片上传失败');
      uploading.value = false;
      return;
    }
    
    const result = await response.json();
    if (result.code === 200 && result.data) {
      // 后端现在返回完整的URL，直接使用即可
      const imageUrl = result.data.url;
      
      // 确保响应式更新 - 使用 Vue 3 的响应式方式
      dishForm.value = {
        ...dishForm.value,
        image_url: imageUrl,
      };
      
      // 等待 DOM 更新
      await nextTick();
      
      // 调试信息
      console.log('图片上传成功，URL:', imageUrl);
      console.log('dishForm.image_url:', dishForm.value.image_url);
      console.log('图片元素:', document.querySelector('.avatar-uploader img'));
      
      ElMessage.success('图片上传成功');
    } else {
      ElMessage.error(result.message || '图片上传失败');
    }
  } catch (error: any) {
    console.error('图片上传失败:', error);
    ElMessage.error('图片上传失败，请重试');
  } finally {
    uploading.value = false;
  }
};

const fetchDishes = async () => {
  loading.value = true;
  try {
    const params: any = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
    };

    if (searchKeyword.value) {
      params.search = searchKeyword.value;
    }
    if (categoryFilter.value) {
      params.category_id = categoryFilter.value;
    }
    if (statusFilter.value) {
      params.status = statusFilter.value;
    }

    const response = await adminApiClient.get('/admin/v1/dishes', { params });
    if (response.code === 200 && response.data) {
      dishes.value = response.data.dishes || [];
      pagination.value = {
        total: response.data.pagination?.total || 0,
        current_page: response.data.pagination?.current_page || 1,
        per_page: response.data.pagination?.per_page || 15,
        last_page: response.data.pagination?.last_page || 1,
      };
    }
  } catch (error: any) {
    console.error('获取菜品列表失败:', error);
    ElMessage.error(error.response?.data?.message || '获取菜品列表失败');
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const response = await adminApiClient.get('/admin/v1/dishes/categories');
    if (response.code === 200 && response.data) {
      categories.value = response.data.categories || [];
    }
  } catch (error: any) {
    console.error('获取分类列表失败:', error);
  }
};

const handleSearch = () => {
  pagination.value.current_page = 1;
  fetchDishes();
};

const handleAdd = () => {
  editingDish.value = null;
  dishForm.value = {
    name: '',
    description: '',
    price: 0,
    image_url: '',
    category_id: null,
    status: 'available',
    sort_order: 0,
  };
  showAddDialog.value = true;
};

const editDish = (dish: Dish) => {
  editingDish.value = dish;
  dishForm.value = {
    name: dish.name,
    description: dish.description || '',
    price: dish.price,
    image_url: dish.image_url || '',
    category_id: dish.category_id,
    status: dish.status,
    sort_order: dish.sort_order || 0,
  };
  showAddDialog.value = true;
};

const deleteDish = async (id: number) => {
  try {
    await ElMessageBox.confirm('确认删除此菜品吗？', '提示', {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'warning',
    });

    const response = await adminApiClient.delete(`/admin/v1/dishes/${id}`);
    if (response.code === 200) {
      ElMessage.success('删除成功');
      fetchDishes();
    } else {
      ElMessage.error(response.message || '删除失败');
    }
  } catch (error: any) {
    if (error.response?.status !== 400) {
      console.error('删除菜品失败:', error);
      ElMessage.error(error.response?.data?.message || '删除失败');
    }
  }
};

const saveDish = async () => {
  if (!dishFormRef.value) return;

  await dishFormRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    saving.value = true;
    try {
      const data = { ...dishForm.value };
      
      if (editingDish.value) {
        // 更新
        const response = await adminApiClient.put(`/admin/v1/dishes/${editingDish.value.id}`, data);
        if (response.code === 200) {
          ElMessage.success('更新成功');
          showAddDialog.value = false;
          fetchDishes();
        } else {
          ElMessage.error(response.message || '更新失败');
        }
      } else {
        // 创建
        const response = await adminApiClient.post('/admin/v1/dishes', data);
        if (response.code === 201 || response.code === 200) {
          ElMessage.success('创建成功');
          showAddDialog.value = false;
          fetchDishes();
        } else {
          ElMessage.error(response.message || '创建失败');
        }
      }
    } catch (error: any) {
      console.error('保存菜品失败:', error);
      ElMessage.error(error.response?.data?.message || '保存失败');
    } finally {
      saving.value = false;
    }
  });
};

const handleDialogClose = () => {
  editingDish.value = null;
  dishFormRef.value?.resetFields();
};

// 图片上传
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

// 图片加载成功
const handleImageLoad = () => {
  console.log('图片加载成功');
};

// 图片加载失败
const handleImageError = (event: Event) => {
  console.error('图片加载失败:', event);
  const img = event.target as HTMLImageElement;
  console.error('失败的图片URL:', img.src);
  ElMessage.error('图片加载失败，请检查图片URL是否正确');
};

onMounted(() => {
  fetchDishes();
  fetchCategories();
});
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.avatar-uploader {
  :deep(.el-upload) {
    border: 1px dashed var(--el-border-color);
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: var(--el-transition-duration-fast);
  }

  :deep(.el-upload:hover) {
    border-color: var(--el-color-primary);
  }

  .avatar-uploader-icon {
    font-size: 28px;
    color: #8c939d;
    width: 178px;
    height: 178px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .avatar {
    width: 178px;
    height: 178px;
    display: block;
    object-fit: cover;
    border-radius: 6px;
  }
}

/* 确保图片预览区域可见 */
.avatar-uploader img.avatar {
  max-width: 100%;
  max-height: 100%;
}
</style>

