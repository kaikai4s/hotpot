<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">🎁 邀请好友</h1>
          <p class="text-xl text-gray-600">邀请好友注册，双方都有好礼</p>
        </div>

        <!-- 邀请码卡片 -->
        <div class="bg-gradient-to-r from-purple-400 via-pink-400 to-red-400 rounded-2xl shadow-xl p-8 mb-8 text-white">
          <div class="text-center">
            <p class="text-lg mb-4 opacity-90">我的邀请码</p>
            <div class="flex items-center justify-center gap-4 mb-6">
              <p class="text-4xl font-bold font-mono">{{ invitationInfo.invite_code }}</p>
              <el-button
                type="primary"
                :icon="CopyDocument"
                @click="copyInviteCode"
                class="bg-white text-purple-600 hover:bg-gray-100"
              >
                复制
              </el-button>
              <el-button
                type="success"
                :icon="Share"
                @click="shareInviteCode"
                class="bg-white text-green-600 hover:bg-gray-100"
              >
                分享
              </el-button>
            </div>
            <div class="mt-4 text-center">
              <p class="text-sm opacity-90 mb-2">分享链接（点击复制）</p>
              <div class="flex items-center justify-center gap-2">
                <el-input
                  :value="inviteLink"
                  readonly
                  class="max-w-md"
                >
                  <template #append>
                    <el-button @click="copyInviteLink">复制链接</el-button>
                  </template>
                </el-input>
              </div>
            </div>
            <div class="grid grid-cols-3 gap-4 mt-6">
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">总邀请数</p>
                <p class="text-2xl font-bold">{{ invitationInfo.total_invites }}</p>
              </div>
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">成功邀请</p>
                <p class="text-2xl font-bold">{{ invitationInfo.successful_invites }}</p>
              </div>
              <div class="bg-white bg-opacity-20 rounded-lg p-4 backdrop-blur-sm">
                <p class="text-sm opacity-90 mb-1">累计奖励</p>
                <p class="text-2xl font-bold">{{ invitationInfo.total_rewards_points }}</p>
                <p class="text-xs opacity-75 mt-1">积分</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 使用说明 -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl shadow-md p-6 mb-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">📖 如何使用邀请码</h2>
          <div class="space-y-3 text-gray-700">
            <div class="flex items-start gap-3">
              <span class="text-xl font-bold text-blue-600">1.</span>
              <div>
                <p class="font-semibold">复制您的邀请码</p>
                <p class="text-sm text-gray-600">点击上方"复制"按钮，邀请码会自动复制到剪贴板</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <span class="text-xl font-bold text-blue-600">2.</span>
              <div>
                <p class="font-semibold">分享给好友</p>
                <p class="text-sm text-gray-600">通过微信、短信或其他方式将邀请码发送给好友</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <span class="text-xl font-bold text-blue-600">3.</span>
              <div>
                <p class="font-semibold">好友注册时输入邀请码</p>
                <p class="text-sm text-gray-600">好友在登录页面输入您的邀请码后完成注册，双方即可获得奖励</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <span class="text-xl font-bold text-blue-600">4.</span>
              <div>
                <p class="font-semibold">获得奖励</p>
                <p class="text-sm text-gray-600">好友注册后您会立即获得新人奖励，好友首次消费后您会获得邀请奖励</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 奖励说明 -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">🎁 奖励规则</h2>
          <div class="space-y-4">
            <div class="flex items-start gap-4 p-4 bg-green-50 rounded-lg">
              <div class="text-2xl">🎉</div>
              <div>
                <p class="font-semibold text-gray-900">新人礼包</p>
                <p class="text-gray-600">被邀请人注册即可获得100积分和10元优惠券</p>
              </div>
            </div>
            <div class="flex items-start gap-4 p-4 bg-blue-50 rounded-lg">
              <div class="text-2xl">💰</div>
              <div>
                <p class="font-semibold text-gray-900">邀请奖励</p>
                <p class="text-gray-600">被邀请人首次消费后，邀请人可获得200积分</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 好友列表 -->
        <div class="bg-white rounded-xl shadow-md p-6">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">我的好友</h2>
          <div v-loading="loading" class="space-y-4">
            <div
              v-for="friend in (invitationInfo.friends || [])"
              :key="friend.id || Math.random()"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-all"
            >
              <div class="flex items-center gap-4">
                <img
                  :src="friend.avatar_url || '/default-avatar.png'"
                  :alt="friend.nickname"
                  class="w-12 h-12 rounded-full object-cover"
                />
                <div>
                  <p class="font-semibold text-gray-900">
                    <span v-if="friend.equipped_title" class="text-yellow-600 font-bold mr-1">[{{ friend.equipped_title }}]</span>
                    {{ friend.nickname }}
                    <span v-if="friend.level" class="text-purple-600 ml-1 text-sm">[{{ friend.level.name }}]</span>
                  </p>
                  <div class="flex items-center gap-4 mt-1">
                    <span
                      class="text-xs px-2 py-1 rounded"
                      :class="getStatusClass(friend.status)"
                    >
                      {{ getStatusText(friend.status) }}
                    </span>
                    <span v-if="friend.registered_at" class="text-xs text-gray-500">
                      注册时间：{{ formatDate(friend.registered_at) }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="text-right">
                <p v-if="friend.reward_issued" class="text-sm text-green-600 font-semibold">
                  ✓ 奖励已发放
                </p>
                <p v-else-if="friend.status === 'completed'" class="text-sm text-orange-600">
                  待发放奖励
                </p>
              </div>
            </div>
            <div v-if="invitationInfo.friends && invitationInfo.friends.length === 0 && !loading" class="text-center py-8 text-gray-500">
              还没有邀请好友，快去邀请吧！
            </div>
          </div>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { ElMessage } from 'element-plus';
import { CopyDocument, Share } from '@element-plus/icons-vue';
import FrontendLayout from '@/components/frontend/FrontendLayout.vue';
import { invitationApi, type InvitationInfo } from '@/api/invitation';

const loading = ref(false);
const invitationInfo = ref<InvitationInfo>({
  invite_code: '',
  total_invites: 0,
  successful_invites: 0,
  total_rewards_points: 0,
  friends: [],
});

const loadInvitationInfo = async () => {
  loading.value = true;
  try {
    const response = await invitationApi.getMyInvitation();
    if (response.code === 200 && response.data) {
      invitationInfo.value = {
        invite_code: response.data.invite_code || '',
        total_invites: response.data.total_invites ?? 0,
        successful_invites: response.data.successful_invites ?? 0,
        total_rewards_points: response.data.total_rewards_points ?? 0,
        friends: Array.isArray(response.data.friends) ? response.data.friends : [],
      };
    }
  } catch (error: any) {
    console.error('加载邀请信息失败:', error);
    ElMessage.error(error.response?.data?.message || '加载失败');
  } finally {
    loading.value = false;
  }
};

const inviteLink = computed(() => {
  const code = invitationInfo.value.invite_code;
  if (!code) return '';
  const baseUrl = window.location.origin;
  return `${baseUrl}/frontend/login?invite_code=${code}`;
});

const copyInviteCode = () => {
  const code = invitationInfo.value.invite_code;
  if (!code) {
    ElMessage.warning('邀请码加载中，请稍候...');
    return;
  }
  navigator.clipboard.writeText(code).then(() => {
    ElMessage.success('邀请码已复制到剪贴板');
  }).catch(() => {
    ElMessage.error('复制失败，请手动复制');
  });
};

const copyInviteLink = () => {
  const link = inviteLink.value;
  if (!link) {
    ElMessage.warning('邀请链接生成中，请稍候...');
    return;
  }
  navigator.clipboard.writeText(link).then(() => {
    ElMessage.success('邀请链接已复制到剪贴板');
  }).catch(() => {
    ElMessage.error('复制失败，请手动复制');
  });
};

const shareInviteCode = async () => {
  const code = invitationInfo.value.invite_code;
  if (!code) {
    ElMessage.warning('邀请码加载中，请稍候...');
    return;
  }

  try {
    // 尝试使用Web Share API（如果支持）
    if (navigator.share) {
      await navigator.share({
        title: '邀请您加入火锅店小程序',
        text: `使用我的邀请码 ${code} 注册，双方都有好礼！`,
        url: inviteLink.value,
      });
      ElMessage.success('分享成功');
    } else {
      // 如果不支持Web Share API，复制链接
      copyInviteLink();
    }
  } catch (error: any) {
    if (error.name !== 'AbortError') {
      console.error('分享失败:', error);
      // 分享失败时，至少复制链接
      copyInviteLink();
    }
  }
};

const getStatusText = (status: string) => {
  const statusMap: Record<string, string> = {
    pending: '待注册',
    registered: '已注册',
    completed: '已消费',
  };
  return statusMap[status] || status;
};

const getStatusClass = (status: string) => {
  const classMap: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-600',
    registered: 'bg-blue-100 text-blue-600',
    completed: 'bg-green-100 text-green-600',
  };
  return classMap[status] || 'bg-gray-100 text-gray-600';
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('zh-CN');
};

onMounted(() => {
  loadInvitationInfo();
});
</script>

