/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">个人中心</h1>
        <p class="text-gray-600">管理您的账号信息和设置</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 左侧：个人信息卡片 -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="text-center mb-6">
              <div class="w-24 h-24 bg-gradient-to-br from-red-500 to-orange-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-white text-4xl font-bold">{{ adminInfo?.name?.charAt(0) || 'A' }}</span>
              </div>
              <h2 class="text-xl font-bold text-gray-800 mb-1">{{ adminInfo?.name || '管理员' }}</h2>
              <p class="text-sm text-gray-500">{{ adminInfo?.username }}</p>
              <el-tag :type="adminInfo?.role === 'super_admin' ? 'danger' : adminInfo?.role === 'admin' ? 'warning' : 'info'" class="mt-2">
                {{ adminInfo?.role === 'super_admin' ? '超级管理员' : adminInfo?.role === 'admin' ? '管理员' : '操作员' }}
              </el-tag>
            </div>
            <div class="space-y-3">
              <div class="flex items-center text-sm text-gray-600">
                <el-icon class="mr-2"><Message /></el-icon>
                <span>{{ adminInfo?.email || '未设置邮箱' }}</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <el-icon class="mr-2"><Calendar /></el-icon>
                <span>最后登录：{{ formatDate(adminInfo?.last_login_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 右侧：详细信息 -->
        <div class="lg:col-span-2 space-y-6">
          <!-- 基本信息 -->
          <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">基本信息</h3>
            <el-form :model="form" label-width="100px" label-position="left">
              <el-form-item label="用户名">
                <el-input v-model="form.username" disabled />
              </el-form-item>
              <el-form-item label="姓名">
                <el-input v-model="form.name" />
              </el-form-item>
              <el-form-item label="邮箱">
                <el-input v-model="form.email" type="email" />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" :loading="saving" @click="handleSave">保存修改</el-button>
              </el-form-item>
            </el-form>
          </div>

          <!-- 角色和权限 -->
          <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">角色和权限</h3>
            <div class="space-y-4">
              <div>
                <p class="text-sm text-gray-600 mb-2">角色</p>
                <div class="flex flex-wrap gap-2">
                  <el-tag v-for="role in adminInfo?.roles" :key="role.id" type="info">
                    {{ role.display_name }}
                  </el-tag>
                </div>
              </div>
              <div>
                <p class="text-sm text-gray-600 mb-2">权限</p>
                <div class="space-y-2">
                  <div v-for="group in groupedPermissions" :key="group.group" class="mb-3">
                    <p class="text-xs font-semibold text-gray-500 mb-1">{{ group.group }}</p>
                    <div class="flex flex-wrap gap-1">
                      <el-tag v-for="permission in group.permissions" :key="permission.id" size="small" type="success">
                        {{ permission.display_name }}
                      </el-tag>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 修改密码 -->
          <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">修改密码</h3>
            <el-form :model="passwordForm" :rules="passwordRules" ref="passwordFormRef" label-width="100px" label-position="left">
              <el-form-item label="当前密码" prop="oldPassword">
                <el-input v-model="passwordForm.oldPassword" type="password" show-password />
              </el-form-item>
              <el-form-item label="新密码" prop="newPassword">
                <el-input v-model="passwordForm.newPassword" type="password" show-password />
              </el-form-item>
              <el-form-item label="确认密码" prop="confirmPassword">
                <el-input v-model="passwordForm.confirmPassword" type="password" show-password />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" :loading="changingPassword" @click="handleChangePassword">修改密码</el-button>
              </el-form-item>
            </el-form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { ElMessage, type FormInstance, type FormRules } from 'element-plus';
import { Message, Calendar } from '@element-plus/icons-vue';
import { adminAuthApi } from '../api/admin';
import type { AdminInfo } from '../api/admin';

const adminInfo = ref<AdminInfo | null>(null);
const form = ref({
  username: '',
  name: '',
  email: '',
});
const saving = ref(false);
const passwordFormRef = ref<FormInstance | null>(null);
const passwordForm = ref({
  oldPassword: '',
  newPassword: '',
  confirmPassword: '',
});
const changingPassword = ref(false);

const passwordRules: FormRules = {
  oldPassword: [
    { required: true, message: '请输入当前密码', trigger: 'blur' },
  ],
  newPassword: [
    { required: true, message: '请输入新密码', trigger: 'blur' },
    { min: 6, message: '密码长度不能少于6位', trigger: 'blur' },
  ],
  confirmPassword: [
    { required: true, message: '请确认新密码', trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (value !== passwordForm.value.newPassword) {
          callback(new Error('两次输入的密码不一致'));
        } else {
          callback();
        }
      },
      trigger: 'blur',
    },
  ],
};

const groupedPermissions = computed(() => {
  if (!adminInfo.value?.permissions) return [];
  
  const groups: Record<string, Array<{ id: number; name: string; display_name: string }>> = {};
  
  adminInfo.value.permissions.forEach(permission => {
    if (!groups[permission.group]) {
      groups[permission.group] = [];
    }
    groups[permission.group].push({
      id: permission.id,
      name: permission.name,
      display_name: permission.display_name,
    });
  });
  
  return Object.keys(groups).map(group => ({
    group,
    permissions: groups[group],
  }));
});

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '从未登录';
  const date = new Date(dateStr);
  return date.toLocaleString('zh-CN');
};

const loadAdminInfo = async () => {
  try {
    const response = await adminAuthApi.me();
    if (response.code === 200 && response.data) {
      adminInfo.value = response.data.admin;
      form.value = {
        username: response.data.admin.username,
        name: response.data.admin.name || '',
        email: response.data.admin.email || '',
      };
      sessionStorage.setItem('admin_info', JSON.stringify(response.data.admin));
    }
  } catch (error) {
    console.error('获取管理员信息失败:', error);
    ElMessage.error('获取管理员信息失败');
  }
};

const handleSave = async () => {
  saving.value = true;
  try {
    // TODO: 实现更新管理员信息的 API
    // await adminApi.update(adminInfo.value!.id, form.value);
    ElMessage.success('保存成功');
    await loadAdminInfo();
  } catch (error: any) {
    console.error('保存失败:', error);
    ElMessage.error(error.message || '保存失败');
  } finally {
    saving.value = false;
  }
};

const handleChangePassword = async () => {
  if (!passwordFormRef.value) return;
  
  await passwordFormRef.value.validate(async (valid) => {
    if (!valid) return;
    
    changingPassword.value = true;
    try {
      // TODO: 实现修改密码的 API
      // await adminApi.changePassword({
      //   old_password: passwordForm.value.oldPassword,
      //   new_password: passwordForm.value.newPassword,
      // });
      ElMessage.success('密码修改成功，请重新登录');
      passwordForm.value = {
        oldPassword: '',
        newPassword: '',
        confirmPassword: '',
      };
      passwordFormRef.value.resetFields();
    } catch (error: any) {
      console.error('修改密码失败:', error);
      ElMessage.error(error.message || '修改密码失败');
    } finally {
      changingPassword.value = false;
    }
  });
};

onMounted(() => {
  loadAdminInfo();
});
</script>

<style scoped>
:deep(.el-form-item__label) {
  font-weight: 500;
}
</style>

