<template>
  <div class="min-h-screen bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-red-500 to-orange-500 rounded-full mb-4">
          <span class="text-4xl">🔥</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">火锅店管理系统</h1>
        <p class="text-gray-600">请登录您的账号</p>
      </div>

      <el-form
        ref="loginFormRef"
        :model="loginForm"
        :rules="loginRules"
        @submit.prevent="handleLogin"
      >
        <el-form-item prop="username">
          <el-input
            v-model="loginForm.username"
            placeholder="请输入用户名"
            size="large"
            prefix-icon="User"
            clearable
          />
        </el-form-item>
        <el-form-item prop="password">
          <el-input
            v-model="loginForm.password"
            type="password"
            placeholder="请输入密码"
            size="large"
            prefix-icon="Lock"
            show-password
            @keyup.enter="handleLogin"
          />
        </el-form-item>
        <el-form-item>
          <el-button
            type="primary"
            size="large"
            class="w-full"
            :loading="loading"
            @click="handleLogin"
          >
            登录
          </el-button>
        </el-form-item>
      </el-form>

      <div class="mt-6 text-center text-sm text-gray-500">
        <p>测试账号：</p>
        <p>超级管理员: admin / admin123</p>
        <p>操作员: operator / operator123</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, type FormInstance, type FormRules } from 'element-plus';
import { adminAuthApi } from '../api/admin';

const router = useRouter();
const loginFormRef = ref<FormInstance | null>(null);
const loading = ref(false);

const loginForm = ref({
  username: '',
  password: '',
});

const loginRules: FormRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码长度不能少于6位', trigger: 'blur' },
  ],
};

const handleLogin = async () => {
  if (!loginFormRef.value) return;

  await loginFormRef.value.validate(async (valid) => {
    if (!valid) return;

    loading.value = true;
    try {
      const response = await adminAuthApi.login({
        username: loginForm.value.username,
        password: loginForm.value.password,
      });

      console.log('Login response:', response);
      console.log('Response code:', response?.code);
      console.log('Response data:', response?.data);
      console.log('Has token:', !!response?.data?.token);

      if (response && response.code === 200 && response.data && response.data.token) {
        // 保存Token和用户信息（使用 sessionStorage，确保前后台完全隔离）
        // sessionStorage 在标签页关闭时自动清除，更安全
        sessionStorage.setItem('admin_token', response.data.token);
        sessionStorage.setItem('admin_info', JSON.stringify(response.data.admin));
        
        // 设置标记，表示刚登录
        sessionStorage.setItem('just_logged_in', 'true');

        console.log('Token saved:', sessionStorage.getItem('admin_token')?.substring(0, 20) + '...');
        console.log('Admin info saved:', !!sessionStorage.getItem('admin_info'));

            ElMessage.success('登录成功');
            // 使用 window.location 进行跳转，避免路由守卫问题
            setTimeout(() => {
              window.location.href = '/admin/dashboard';
            }, 300);
      } else {
        console.error('Invalid login response:', response);
        ElMessage.error('登录失败，请重试');
      }
    } catch (error: any) {
      console.error('登录失败:', error);
      
      // 清除可能存在的无效 token（使用 sessionStorage）
      sessionStorage.removeItem('admin_token');
      sessionStorage.removeItem('admin_info');
      
      // 处理错误消息
      let message = '登录失败，请检查用户名和密码';
      if (error.response) {
        const { status, data } = error.response;
        if (status === 401) {
          // 401 错误可能是用户名或密码错误
          if (data?.message) {
            message = data.message;
          } else if (data?.errors) {
            // Laravel 验证错误格式
            const firstError = Object.values(data.errors)[0];
            message = Array.isArray(firstError) ? firstError[0] : firstError;
          }
        } else if (status === 422) {
          // 422 验证错误
          if (data?.errors) {
            const firstError = Object.values(data.errors)[0];
            message = Array.isArray(firstError) ? firstError[0] : firstError;
          } else if (data?.message) {
            message = data.message;
          }
        } else if (data?.message) {
          message = data.message;
        }
      } else if (error.message) {
        message = error.message;
      }
      
      ElMessage.error(message);
    } finally {
      loading.value = false;
    }
  });
};
</script>

<style scoped>
/* 样式 */
</style>

