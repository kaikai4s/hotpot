/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">角色权限管理</h1>
          <p class="text-gray-600">管理系统角色和权限分配</p>
        </div>
        <el-button type="primary" size="large" @click="showAddDialog = true">
          <el-icon><Plus /></el-icon>
          添加角色
        </el-button>
      </div>

      <!-- 角色列表 -->
      <el-table
        v-loading="loading"
        :data="roles"
        stripe
        style="width: 100%"
        class="mb-4"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="display_name" label="角色名称" width="150" />
        <el-table-column prop="name" label="标识" width="150" />
        <el-table-column prop="description" label="描述" min-width="200" />
        <el-table-column label="权限数量" width="120">
          <template #default="{ row }">
            <el-tag>{{ row.permissions?.length || 0 }} 个</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="系统角色" width="120">
          <template #default="{ row }">
            <el-tag v-if="row.is_system" type="info">是</el-tag>
            <el-tag v-else type="success">否</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button
              v-if="!row.is_system"
              type="danger"
              link
              @click="handleDelete(row)"
            >
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 添加/编辑角色对话框 -->
    <el-dialog
      v-model="editDialogVisible"
      :title="editingRole ? '编辑角色' : '添加角色'"
      width="700px"
      @close="resetEditForm"
    >
      <el-form :model="editForm" :rules="editFormRules" ref="editFormRef" label-width="100px">
        <el-form-item label="角色名称" prop="display_name">
          <el-input
            v-model="editForm.display_name"
            placeholder="如：客服管理员"
            :disabled="editingRole?.is_system"
          />
          <el-alert
            v-if="editingRole?.is_system"
            type="info"
            :closable="false"
            show-icon
            class="mt-2"
          >
            系统角色的名称不能修改
          </el-alert>
        </el-form-item>
        <el-form-item label="标识" prop="name">
          <el-input
            v-model="editForm.name"
            placeholder="如：customer_service"
            :disabled="!!editingRole"
          />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="editForm.description"
            type="textarea"
            :rows="3"
            placeholder="角色描述"
            :disabled="editingRole?.is_system"
          />
          <el-alert
            v-if="editingRole?.is_system"
            type="info"
            :closable="false"
            show-icon
            class="mt-2"
          >
            系统角色的描述不能修改
          </el-alert>
        </el-form-item>
        <el-form-item label="权限分配">
          <div class="w-full border rounded-lg p-4 max-h-96 overflow-y-auto">
            <div v-for="group in groupedPermissions" :key="group.group" class="mb-4">
              <h4 class="font-semibold mb-2 text-gray-700">{{ group.group }}</h4>
              <el-checkbox-group v-model="editForm.permission_ids" class="flex flex-col gap-2">
                <el-checkbox
                  v-for="permission in group.permissions"
                  :key="permission.id"
                  :label="permission.id"
                >
                  {{ permission.display_name }}
                  <span class="text-gray-400 text-xs ml-2">({{ permission.name }})</span>
                </el-checkbox>
              </el-checkbox-group>
            </div>
          </div>
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
import { ref, onMounted, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus } from '@element-plus/icons-vue';
import { roleApi, type Role, type CreateRoleData } from '../api/role';
import { permissionApi, type Permission } from '../api/permission';
import type { FormInstance, FormRules } from 'element-plus';

const loading = ref(false);
const saving = ref(false);
const roles = ref<Role[]>([]);
const permissions = ref<Permission[]>([]);
const editDialogVisible = ref(false);
const showAddDialog = ref(false);
const editingRole = ref<Role | null>(null);
const editFormRef = ref<FormInstance | null>(null);

const editForm = ref<CreateRoleData>({
  name: '',
  display_name: '',
  description: '',
  permission_ids: [],
});

const editFormRules: FormRules = {
  name: [
    { required: true, message: '请输入角色标识', trigger: 'blur' },
    { pattern: /^[a-z_]+$/, message: '角色标识只能包含小写字母和下划线', trigger: 'blur' },
  ],
  display_name: [
    { required: true, message: '请输入角色名称', trigger: 'blur' },
  ],
};

const groupedPermissions = computed(() => {
  const groups: Record<string, Permission[]> = {};
  permissions.value.forEach((permission) => {
    if (!groups[permission.group]) {
      groups[permission.group] = [];
    }
    groups[permission.group].push(permission);
  });
  return Object.keys(groups).map((group) => ({
    group,
    permissions: groups[group],
  }));
});

const fetchRoles = async () => {
  loading.value = true;
  try {
    const response = await roleApi.getList();
    if (response.code === 200 && response.data) {
      roles.value = response.data.roles;
    }
  } catch (error: any) {
    console.error('获取角色列表失败:', error);
    ElMessage.error('获取角色列表失败');
  } finally {
    loading.value = false;
  }
};

const fetchPermissions = async () => {
  try {
    const response = await permissionApi.getList();
    if (response.code === 200 && response.data) {
      permissions.value = response.data.permissions;
    }
  } catch (error: any) {
    console.error('获取权限列表失败:', error);
    ElMessage.error('获取权限列表失败');
  }
};

const handleEdit = (role: Role) => {
  editingRole.value = role;
  editForm.value = {
    name: role.name,
    display_name: role.display_name,
    description: role.description || '',
    permission_ids: role.permissions?.map((p) => p.id) || [],
  };
  editDialogVisible.value = true;
};

const handleSave = async () => {
  if (!editFormRef.value) return;

  await editFormRef.value.validate(async (valid) => {
    if (!valid) return;

    saving.value = true;
    try {
      if (editingRole.value) {
        // 如果是系统角色，只发送权限信息，不发送基本信息
        const updateData = editingRole.value.is_system
          ? { permission_ids: editForm.value.permission_ids }
          : editForm.value;
        
        await roleApi.update(editingRole.value.id, updateData);
        ElMessage.success('角色更新成功');
      } else {
        await roleApi.create(editForm.value);
        ElMessage.success('角色创建成功');
      }
      editDialogVisible.value = false;
      fetchRoles();
    } catch (error: any) {
      console.error('保存角色失败:', error);
      const errorMessage = error.response?.data?.message || error.response?.data?.errors?.role?.[0] || '保存角色失败';
      ElMessage.error(errorMessage);
    } finally {
      saving.value = false;
    }
  });
};

const handleDelete = async (role: Role) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除角色 "${role.display_name}" 吗？`,
      '确认删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    );

    await roleApi.delete(role.id);
    ElMessage.success('角色删除成功');
    fetchRoles();
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('删除角色失败:', error);
      ElMessage.error(error.response?.data?.message || '删除角色失败');
    }
  }
};

const resetEditForm = () => {
  editingRole.value = null;
  editForm.value = {
    name: '',
    display_name: '',
    description: '',
    permission_ids: [],
  };
  editFormRef.value?.resetFields();
};

onMounted(() => {
  fetchRoles();
  fetchPermissions();
});
</script>

<style scoped>
:deep(.el-table) {
  font-size: 14px;
}
</style>

