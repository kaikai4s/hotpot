/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">管理员管理</h1>
          <p class="text-gray-600">管理系统管理员账号和角色分配</p>
        </div>
        <el-button type="primary" size="large" @click="showAddDialog = true">
          <el-icon><Plus /></el-icon>
          添加管理员
        </el-button>
      </div>

      <!-- 搜索和筛选 -->
      <div class="mb-4 flex gap-4">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索用户名、姓名或邮箱"
          style="width: 300px"
          clearable
          @clear="handleSearch"
          @keyup.enter="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select
          v-model="filterRole"
          placeholder="筛选角色"
          style="width: 150px"
          clearable
          @change="handleSearch"
        >
          <el-option label="超级管理员" value="super_admin" />
          <el-option label="管理员" value="admin" />
          <el-option label="操作员" value="operator" />
        </el-select>
        <el-button type="primary" @click="handleSearch">
          <el-icon><Search /></el-icon>
          搜索
        </el-button>
      </div>

      <!-- 管理员列表 -->
      <el-table
        v-loading="loading"
        :data="admins"
        stripe
        style="width: 100%"
        class="mb-4"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="name" label="姓名" width="120" />
        <el-table-column prop="email" label="邮箱" width="200" />
        <el-table-column label="角色" width="120">
          <template #default="{ row }">
            <el-tag v-if="row.role === 'super_admin'" type="danger">超级管理员</el-tag>
            <el-tag v-else-if="row.role === 'admin'" type="warning">管理员</el-tag>
            <el-tag v-else type="info">操作员</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="分配的角色" min-width="200">
          <template #default="{ row }">
            <el-tag
              v-for="role in row.roles"
              :key="role.id"
              class="mr-2"
              size="small"
            >
              {{ role.display_name }}
            </el-tag>
            <span v-if="!row.roles || row.roles.length === 0" class="text-gray-400">未分配</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.is_active" type="success">启用</el-tag>
            <el-tag v-else type="danger">禁用</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="最后登录" width="180">
          <template #default="{ row }">
            {{ row.last_login_at ? formatDate(row.last_login_at) : '从未登录' }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button
              type="danger"
              link
              @click="handleDelete(row)"
              :disabled="row.id === currentAdminId"
            >
              删除
            </el-button>
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
        @size-change="handleSearch"
        @current-change="handleSearch"
      />
    </div>

    <!-- 添加/编辑管理员对话框 -->
    <el-dialog
      v-model="editDialogVisible"
      :title="editingAdmin ? '编辑管理员' : '添加管理员'"
      width="600px"
      @close="resetEditForm"
    >
      <el-form :model="editForm" :rules="editFormRules" ref="editFormRef" label-width="100px">
        <el-form-item label="用户名" prop="username">
          <el-input
            v-model="editForm.username"
            placeholder="请输入用户名"
            :disabled="!!editingAdmin"
          />
        </el-form-item>
        <el-form-item label="姓名" prop="name">
          <el-input v-model="editForm.name" placeholder="请输入姓名" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="editForm.email" placeholder="请输入邮箱（可选）" />
        </el-form-item>
        <el-form-item
          label="密码"
          prop="password"
          :rules="editingAdmin ? [] : editFormRules.password"
        >
          <el-input
            v-model="editForm.password"
            type="password"
            :placeholder="editingAdmin ? '留空则不修改密码' : '请输入密码（至少6位）'"
            show-password
          />
        </el-form-item>
        <el-form-item label="角色类型" prop="role">
          <el-select v-model="editForm.role" placeholder="请选择角色类型">
            <el-option label="超级管理员" value="super_admin" />
            <el-option label="管理员" value="admin" />
            <el-option label="操作员" value="operator" />
          </el-select>
        </el-form-item>
        <el-form-item label="分配角色">
          <el-checkbox-group v-model="editForm.role_ids" class="flex flex-col gap-2">
            <el-checkbox
              v-for="role in roles"
              :key="role.id"
              :label="role.id"
            >
              {{ role.display_name }}
              <span class="text-gray-400 text-xs ml-2">({{ role.name }})</span>
            </el-checkbox>
          </el-checkbox-group>
          <p class="text-xs text-gray-500 mt-2">
            提示：角色类型用于快速分配默认角色，也可以手动选择多个角色
          </p>
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="editForm.is_active" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Search } from '@element-plus/icons-vue';
import { adminApi } from '../api/admin';
import { roleApi, type Role } from '../api/role';
import type { FormInstance, FormRules } from 'element-plus';

interface Admin {
  id: number;
  username: string;
  name: string;
  email: string | null;
  role: 'super_admin' | 'admin' | 'operator';
  roles?: Role[];
  is_active: boolean;
  last_login_at: string | null;
}

const loading = ref(false);
const saving = ref(false);
const admins = ref<Admin[]>([]);
const roles = ref<Role[]>([]);
const editDialogVisible = ref(false);
const showAddDialog = ref(false);
const editingAdmin = ref<Admin | null>(null);
const editFormRef = ref<FormInstance | null>(null);
const searchKeyword = ref('');
const filterRole = ref('');
const currentPage = ref(1);
const pageSize = ref(10);
const total = ref(0);
const currentAdminId = ref<number | null>(null);

const editForm = ref({
  username: '',
  name: '',
  email: '',
  password: '',
  role: 'operator' as 'super_admin' | 'admin' | 'operator',
  role_ids: [] as number[],
  is_active: true,
});

const editFormRules: FormRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 3, max: 64, message: '用户名长度在3到64个字符', trigger: 'blur' },
  ],
  name: [
    { required: true, message: '请输入姓名', trigger: 'blur' },
  ],
  email: [
    { type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur' },
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码长度至少6个字符', trigger: 'blur' },
  ],
  role: [
    { required: true, message: '请选择角色类型', trigger: 'change' },
  ],
};

watch(showAddDialog, (val) => {
  if (val) {
    editDialogVisible.value = true;
    showAddDialog.value = false;
  }
});

const fetchAdmins = async () => {
  loading.value = true;
  try {
    const response = await adminApi.getList({
      page: currentPage.value,
      page_size: pageSize.value,
      search: searchKeyword.value || undefined,
      role: filterRole.value || undefined,
    });
    if (response.code === 200 && response.data) {
      admins.value = response.data.admins;
      total.value = response.data.pagination.total;
    }
  } catch (error: any) {
    console.error('获取管理员列表失败:', error);
    ElMessage.error('获取管理员列表失败');
  } finally {
    loading.value = false;
  }
};

const fetchRoles = async () => {
  try {
    const response = await roleApi.getList();
    if (response.code === 200 && response.data) {
      roles.value = response.data.roles;
    }
  } catch (error: any) {
    console.error('获取角色列表失败:', error);
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  fetchAdmins();
};

const handleEdit = (admin: Admin) => {
  editingAdmin.value = admin;
  editForm.value = {
    username: admin.username,
    name: admin.name,
    email: admin.email || '',
    password: '',
    role: admin.role,
    role_ids: admin.roles?.map((r) => r.id) || [],
    is_active: admin.is_active,
  };
  editDialogVisible.value = true;
};

const handleSave = async () => {
  if (!editFormRef.value) return;

  await editFormRef.value.validate(async (valid) => {
    if (!valid) return;

    saving.value = true;
    try {
      const formData = { ...editForm.value };
      // 如果是编辑且密码为空，不发送密码字段
      if (editingAdmin.value && !formData.password) {
        delete formData.password;
      }

      if (editingAdmin.value) {
        await adminApi.update(editingAdmin.value.id, formData);
        ElMessage.success('管理员信息更新成功');
      } else {
        await adminApi.create(formData);
        ElMessage.success('管理员创建成功');
      }
      editDialogVisible.value = false;
      fetchAdmins();
    } catch (error: any) {
      console.error('保存管理员失败:', error);
      ElMessage.error(error.response?.data?.message || '保存管理员失败');
    } finally {
      saving.value = false;
    }
  });
};

const handleDelete = async (admin: Admin) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除管理员 "${admin.name}" (${admin.username}) 吗？`,
      '确认删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    );

    await adminApi.delete(admin.id);
    ElMessage.success('管理员删除成功');
    fetchAdmins();
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除管理员失败:', error);
      ElMessage.error(error.response?.data?.message || '删除管理员失败');
    }
  }
};

const resetEditForm = () => {
  editingAdmin.value = null;
  editForm.value = {
    username: '',
    name: '',
    email: '',
    password: '',
    role: 'operator',
    role_ids: [],
    is_active: true,
  };
  editFormRef.value?.resetFields();
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleString('zh-CN');
};

onMounted(async () => {
  // 获取当前登录管理员ID
  const adminInfoStr = sessionStorage.getItem('admin_info');
  if (adminInfoStr) {
    try {
      const adminInfo = JSON.parse(adminInfoStr);
      currentAdminId.value = adminInfo.id;
    } catch (e) {
      console.error('解析admin_info失败:', e);
    }
  }

  await fetchRoles();
  await fetchAdmins();
});
</script>

<style scoped>
:deep(.el-table) {
  font-size: 14px;
}
</style>

