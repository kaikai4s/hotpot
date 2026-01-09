/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">积分商城管理</h1>
          <p class="text-gray-600">管理积分商城商品</p>
        </div>
        <el-button type="primary" size="large" @click="handleAdd">
          <el-icon><Plus /></el-icon>
          添加商品
        </el-button>
      </div>

      <!-- 搜索栏 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索商品名称"
          clearable
          class="flex-1"
          @clear="handleSearch"
          @keyup.enter="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="selectedType" placeholder="商品类型" clearable style="width: 150px" @change="handleSearch">
          <el-option label="实物商品" value="physical" />
          <el-option label="体验商品" value="experience" />
        </el-select>
        <el-select v-model="selectedStatus" placeholder="商品状态" clearable style="width: 150px" @change="handleSearch">
          <el-option label="上架中" value="active" />
          <el-option label="已下架" value="inactive" />
          <el-option label="已售罄" value="sold_out" />
        </el-select>
        <el-button type="primary" @click="handleSearch">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>

      <!-- 表格 -->
      <el-table v-loading="loading" :data="products" stripe border style="width: 100%" class="mb-4">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="商品图片" width="100">
          <template #default="{ row }">
            <el-image
              v-if="row.image_url"
              :src="row.image_url"
              :preview-src-list="[row.image_url]"
              fit="cover"
              class="w-16 h-16 rounded"
            />
            <span v-else class="text-gray-400">无图片</span>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="商品名称" min-width="150" />
        <el-table-column prop="type" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.type === 'physical' ? 'primary' : 'success'">
              {{ row.type === 'physical' ? '实物' : '体验' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="points_required" label="所需积分" width="120">
          <template #default="{ row }">
            <span class="font-bold text-orange-600">{{ row.points_required }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="stock" label="库存" width="100">
          <template #default="{ row }">
            <span :class="row.stock <= 10 ? 'text-red-600 font-bold' : ''">{{ row.stock }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="per_user_limit" label="限购" width="80">
          <template #default="{ row }">
            {{ row.per_user_limit || '不限' }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button
              v-if="row.status !== 'active'"
              type="success"
              link
              @click="handleUpdateStatus(row, 'active')"
            >上架</el-button>
            <el-button
              v-if="row.status === 'active'"
              type="warning"
              link
              @click="handleUpdateStatus(row, 'inactive')"
            >下架</el-button>
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
      :title="editingId ? '编辑商品' : '添加商品'"
      width="600px"
      @close="handleDialogClose"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="商品名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入商品名称" />
        </el-form-item>
        <el-form-item label="商品类型" prop="type">
          <el-select v-model="form.type" placeholder="请选择类型">
            <el-option label="实物商品" value="physical" />
            <el-option label="体验商品" value="experience" />
          </el-select>
        </el-form-item>
        <el-form-item label="商品描述" prop="description">
          <el-input v-model="form.description" type="textarea" :rows="3" placeholder="商品描述（可选）" />
        </el-form-item>
        <el-form-item label="图片URL" prop="image_url">
          <el-input v-model="form.image_url" placeholder="商品图片URL（可选）" />
        </el-form-item>
        <el-form-item label="所需积分" prop="points_required">
          <el-input-number v-model="form.points_required" :min="1" :max="999999" style="width: 100%" />
        </el-form-item>
        <el-form-item label="库存数量" prop="stock">
          <el-input-number v-model="form.stock" :min="0" :max="999999" style="width: 100%" />
        </el-form-item>
        <el-form-item label="每人限购" prop="per_user_limit">
          <el-input-number v-model="form.per_user_limit" :min="0" :max="999" style="width: 100%" />
          <div class="text-xs text-gray-500 mt-1">0表示不限购</div>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-select v-model="form.status" placeholder="请选择状态">
            <el-option label="上架中" value="active" />
            <el-option label="已下架" value="inactive" />
          </el-select>
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
import { ref, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Search } from '@element-plus/icons-vue';
import { adminMallApi, type MallProduct } from '../api/admin-mall';

const loading = ref(false);
const saving = ref(false);
const products = ref<MallProduct[]>([]);
const searchKeyword = ref('');
const selectedType = ref('');
const selectedStatus = ref('');
const currentPage = ref(1);
const pageSize = ref(15);
const total = ref(0);

const dialogVisible = ref(false);
const editingId = ref<number | null>(null);
const formRef = ref();

const form = ref<Partial<MallProduct>>({
  name: '',
  type: 'physical',
  description: '',
  image_url: '',
  points_required: 100,
  stock: 0,
  per_user_limit: 0,
  status: 'active',
});

const rules = {
  name: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择商品类型', trigger: 'change' }],
  points_required: [{ required: true, message: '请输入所需积分', trigger: 'blur' }],
  stock: [{ required: true, message: '请输入库存数量', trigger: 'blur' }],
};

const getStatusText = (status: string) => {
  const map: Record<string, string> = { active: '上架中', inactive: '已下架', sold_out: '已售罄' };
  return map[status] || status;
};

const getStatusType = (status: string) => {
  const map: Record<string, string> = { active: 'success', inactive: 'info', sold_out: 'danger' };
  return map[status] || '';
};

const fetchData = async () => {
  loading.value = true;
  try {
    const response = await adminMallApi.getProducts({
      keyword: searchKeyword.value || undefined,
      type: selectedType.value || undefined,
      status: selectedStatus.value || undefined,
      per_page: pageSize.value,
      page: currentPage.value,
    });
    if (response.code === 200 && response.data) {
      products.value = response.data.products;
      total.value = response.data.pagination.total;
    }
  } catch (error) {
    console.error('获取商品列表失败:', error);
    ElMessage.error('获取商品列表失败');
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => { currentPage.value = 1; fetchData(); };
const resetSearch = () => { searchKeyword.value = ''; selectedType.value = ''; selectedStatus.value = ''; handleSearch(); };
const handleSizeChange = () => fetchData();
const handlePageChange = () => fetchData();

const handleAdd = () => {
  editingId.value = null;
  form.value = { name: '', type: 'physical', description: '', image_url: '', points_required: 100, stock: 0, per_user_limit: 0, status: 'active' };
  dialogVisible.value = true;
};

const handleEdit = (product: MallProduct) => {
  editingId.value = product.id;
  form.value = { ...product };
  dialogVisible.value = true;
};

const handleDialogClose = () => { editingId.value = null; formRef.value?.resetFields(); };

const handleSubmit = async () => {
  if (!formRef.value) return;
  await formRef.value.validate(async (valid: boolean) => {
    if (!valid) return;
    saving.value = true;
    try {
      if (editingId.value) {
        await adminMallApi.updateProduct(editingId.value, form.value);
        ElMessage.success('更新成功');
      } else {
        await adminMallApi.createProduct(form.value);
        ElMessage.success('创建成功');
      }
      dialogVisible.value = false;
      fetchData();
    } catch (error: any) {
      ElMessage.error(error.response?.data?.message || '保存失败');
    } finally {
      saving.value = false;
    }
  });
};

const handleUpdateStatus = async (product: MallProduct, status: string) => {
  try {
    await adminMallApi.updateProductStatus(product.id, status);
    ElMessage.success('状态更新成功');
    fetchData();
  } catch (error) {
    ElMessage.error('状态更新失败');
  }
};

const handleDelete = async (product: MallProduct) => {
  try {
    await ElMessageBox.confirm(`确定要删除商品"${product.name}"吗？`, '提示', { type: 'warning' });
    await adminMallApi.deleteProduct(product.id);
    ElMessage.success('删除成功');
    fetchData();
  } catch (error: any) {
    if (error !== 'cancel') ElMessage.error('删除失败');
  }
};

onMounted(() => fetchData());
</script>
