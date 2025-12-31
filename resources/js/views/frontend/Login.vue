<template>
  <div class="min-h-screen bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-red-500 to-orange-500 rounded-full mb-4">
          <span class="text-4xl">🔥</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">用户登录</h1>
        <p class="text-gray-600">请登录您的账号</p>
      </div>

      <el-tabs v-model="activeTab" class="mb-6">
        <el-tab-pane label="微信登录" name="wechat">
          <div class="text-center py-8">
            <div class="mb-6">
              <div class="w-32 h-32 mx-auto bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mb-4 shadow-lg">
                <span class="text-5xl text-white">💬</span>
              </div>
              <p class="text-gray-600 mb-4">使用微信登录</p>
              <el-button 
                type="primary" 
                size="large" 
                @click="handleWechatLogin" 
                :loading="wechatLoading"
                class="w-full"
              >
                <span v-if="!wechatLoading">微信登录</span>
                <span v-else>正在登录...</span>
              </el-button>
            </div>
            <p class="text-sm text-gray-500">或使用手机号登录</p>
          </div>
        </el-tab-pane>
        <el-tab-pane label="手机号登录" name="phone">
          <el-form
            ref="phoneFormRef"
            :model="phoneForm"
            :rules="phoneFormRules"
            @submit.prevent="handlePhoneLogin"
          >
            <el-form-item prop="phone">
              <el-input
                v-model="phoneForm.phone"
                placeholder="请输入手机号"
                size="large"
                prefix-icon="Phone"
                clearable
              />
            </el-form-item>
            <el-form-item prop="code">
              <div class="flex gap-2">
                <el-input
                  v-model="phoneForm.code"
                  placeholder="请输入验证码"
                  size="large"
                  prefix-icon="Message"
                  clearable
                />
                <el-button
                  size="large"
                  :disabled="codeCountdown > 0"
                  @click="sendCode"
                >
                  {{ codeCountdown > 0 ? `${codeCountdown}秒` : '获取验证码' }}
                </el-button>
              </div>
            </el-form-item>
            <el-form-item>
              <el-button
                type="primary"
                size="large"
                class="w-full"
                :loading="phoneLoading"
                @click="handlePhoneLogin"
              >
                登录
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
      </el-tabs>

      <div class="text-center text-sm text-gray-500">
        <p>登录即表示同意《用户协议》和《隐私政策》</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { ElMessage, type FormInstance, type FormRules } from 'element-plus';
import { userAuthApi } from '../../api/auth';

const router = useRouter();
const route = useRoute();
const phoneFormRef = ref<FormInstance | null>(null);
const activeTab = ref('wechat');
const wechatLoading = ref(false);
const phoneLoading = ref(false);
const codeCountdown = ref(0);

const phoneForm = ref({
  phone: '',
  code: '',
});

const phoneFormRules: FormRules = {
  phone: [
    { required: true, message: '请输入手机号', trigger: 'blur' },
    { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号', trigger: 'blur' },
  ],
  code: [
    { required: true, message: '请输入验证码', trigger: 'blur' },
    { pattern: /^\d{6}$/, message: '请输入6位验证码', trigger: 'blur' },
  ],
};

// 微信登录（根据配置决定使用模拟还是真实微信登录）
const handleWechatLogin = async () => {
  wechatLoading.value = true;
  try {
    // 获取微信登录模式配置
    let loginMode = 'mock'; // 默认模拟登录
    try {
      const configResponse = await userAuthApi.getPublicConfig('wechat_login_mode');
      if (configResponse && configResponse.code === 200 && configResponse.data) {
        loginMode = configResponse.data.value || 'mock';
      }
    } catch (error) {
      console.warn('获取登录模式配置失败，使用默认模拟登录:', error);
    }

    if (loginMode === 'real') {
      // 真实微信登录：跳转到微信授权页面
      const appIdResponse = await userAuthApi.getWechatConfig();
      const appId = appIdResponse?.data?.app_id;
      
      if (!appId) {
        ElMessage.error('微信登录配置未完成，请联系管理员');
        wechatLoading.value = false;
        return;
      }
      
      // 构建微信授权URL
      const redirectUri = encodeURIComponent(`${window.location.origin}/frontend/login?redirect=${encodeURIComponent((route.query.redirect as string) || '/')}`);
      const wechatAuthUrl = `https://open.weixin.qq.com/connect/qrconnect?appid=${appId}&redirect_uri=${redirectUri}&response_type=code&scope=snsapi_login&state=wechat_login#wechat_redirect`;
      
      // 跳转到微信授权页面
      window.location.href = wechatAuthUrl;
    } else {
      // 模拟登录：生成模拟code
      const mockCode = 'mock_wechat_code_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      
      // 调用登录接口（后端会识别模拟code并创建模拟用户）
      const response = await userAuthApi.wechatLogin(mockCode);
      
      if (response && response.code === 200 && response.data && response.data.token) {
        // 保存Token和用户信息
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user_info', JSON.stringify(response.data.user));
        
        ElMessage.success('登录成功');
        
        // 跳转到之前想访问的页面，或首页
        const redirect = (route.query.redirect as string) || '/';
        router.push(redirect);
      } else {
        ElMessage.error('登录失败，请重试');
      }
      wechatLoading.value = false;
    }
  } catch (error: any) {
    console.error('微信登录失败:', error);
    const message = error.response?.data?.message || error.message || '登录失败，请重试';
    ElMessage.error(message);
    wechatLoading.value = false;
  }
};

// 获取微信AppID（从后端API获取）
const wechatAppId = ref<string | null>(null);

const getWechatAppId = async (): Promise<string> => {
  if (wechatAppId.value) {
    return wechatAppId.value;
  }
  
  try {
    const response = await userAuthApi.getWechatConfig();
    if (response && response.code === 200 && response.data && response.data.app_id) {
      wechatAppId.value = response.data.app_id;
      return response.data.app_id;
    }
  } catch (error) {
    console.error('获取微信配置失败:', error);
  }
  
  // 如果获取失败，返回空字符串（将无法使用微信登录）
  return '';
};

// 页面加载时检查是否有微信回调code（真实微信登录回调）
onMounted(async () => {
  const urlParams = new URLSearchParams(window.location.search);
  const code = urlParams.get('code');
  const state = urlParams.get('state');
  
  // 如果有code且是微信登录回调，自动处理登录
  if (code && state === 'wechat_login') {
    wechatLoading.value = true;
    try {
      const response = await userAuthApi.wechatLogin(code);
      
      if (response && response.code === 200 && response.data && response.data.token) {
        // 保存Token和用户信息
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user_info', JSON.stringify(response.data.user));
        
        ElMessage.success('登录成功');
        
        // 清除URL中的code参数
        const newUrl = window.location.pathname + (route.query.redirect ? `?redirect=${route.query.redirect}` : '');
        window.history.replaceState({}, '', newUrl);
        
        // 跳转到之前想访问的页面，或首页
        const redirect = (route.query.redirect as string) || '/';
        router.push(redirect);
      } else {
        ElMessage.error('登录失败，请重试');
      }
    } catch (error: any) {
      console.error('微信登录失败:', error);
      const message = error.response?.data?.message || error.message || '登录失败，请重试';
      ElMessage.error(message);
    } finally {
      wechatLoading.value = false;
    }
  }
});

// 发送验证码
const sendCode = async () => {
  if (!phoneForm.value.phone) {
    ElMessage.warning('请先输入手机号');
    return;
  }
  
  if (!/^1[3-9]\d{9}$/.test(phoneForm.value.phone)) {
    ElMessage.warning('请输入正确的手机号');
    return;
  }
  
  try {
    // TODO: 调用发送验证码API
    // await userAuthApi.sendCode(phoneForm.value.phone);
    ElMessage.success('验证码已发送');
    
    // 开始倒计时
    codeCountdown.value = 60;
    const timer = setInterval(() => {
      codeCountdown.value--;
      if (codeCountdown.value <= 0) {
        clearInterval(timer);
      }
    }, 1000);
  } catch (error: any) {
    console.error('发送验证码失败:', error);
    ElMessage.error('发送验证码失败，请重试');
  }
};

// 手机号登录
const handlePhoneLogin = async () => {
  if (!phoneFormRef.value) return;
  
  await phoneFormRef.value.validate(async (valid) => {
    if (!valid) return;
    
    phoneLoading.value = true;
    try {
      // TODO: 调用手机号登录API
      // const response = await userAuthApi.phoneLogin(phoneForm.value.phone, phoneForm.value.code);
      
      // 临时模拟登录成功
      ElMessage.info('手机号登录功能开发中，请使用微信登录');
      
      // 实际代码应该是：
      // if (response && response.code === 200 && response.data && response.data.token) {
      //   localStorage.setItem('token', response.data.token);
      //   localStorage.setItem('user_info', JSON.stringify(response.data.user));
      //   ElMessage.success('登录成功');
      //   const redirect = (route.query.redirect as string) || '/';
      //   router.push(redirect);
      // }
    } catch (error: any) {
      console.error('手机号登录失败:', error);
      const message = error.response?.data?.message || error.message || '登录失败，请重试';
      ElMessage.error(message);
    } finally {
      phoneLoading.value = false;
    }
  });
};
</script>

<style scoped>
/* 样式 */
</style>

