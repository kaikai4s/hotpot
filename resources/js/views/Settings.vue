/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">配置管理</h1>
        <p class="text-gray-600">管理系统配置参数</p>
      </div>

      <!-- 标签页 -->
      <el-tabs v-model="activeTab" @tab-change="handleTabChange">
        <!-- 轮播图管理 -->
        <el-tab-pane label="轮播图管理" name="banners">
          <BannerManagement />
        </el-tab-pane>
        
        <!-- 配置管理 -->
        <el-tab-pane
          v-for="group in configGroups"
          :key="group"
          :label="getGroupLabel(group)"
          :name="group"
        >
          <div class="mt-4">
            <el-form
              v-for="config in getConfigsByGroup(group)"
              :key="config.id"
              :model="config"
              label-width="200px"
              class="mb-6 p-4 bg-gray-50 rounded-lg"
            >
              <el-form-item :label="config.label">
                <div class="w-full">
                  <!-- 字符串类型 -->
                  <el-input
                    v-if="config.type === 'string'"
                    v-model="config.value"
                    :placeholder="config.description"
                    class="w-full"
                  />
                  <!-- 整数类型 -->
                  <el-input-number
                    v-else-if="config.type === 'integer'"
                    v-model.number="config.value"
                    class="w-full"
                  />
                  <!-- 布尔类型 -->
                  <el-switch
                    v-else-if="config.type === 'boolean'"
                    v-model="config.value"
                  />
                  <!-- JSON类型 -->
                  <el-input
                    v-else-if="config.type === 'json'"
                    :model-value="typeof config.value === 'string' ? config.value : JSON.stringify(config.value, null, 2)"
                    @update:model-value="config.value = $event"
                    type="textarea"
                    :rows="4"
                    placeholder="请输入JSON格式"
                    class="w-full"
                  />
                  <p v-if="config.description" class="text-xs text-gray-500 mt-1">
                    {{ config.description }}
                  </p>
                </div>
              </el-form-item>
            </el-form>

            <div class="flex justify-end mt-6">
              <el-button type="primary" :loading="saving" @click="handleSaveGroup(group)">
                保存{{ getGroupLabel(group) }}配置
              </el-button>
            </div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { ElMessage } from 'element-plus';
import { configApi, type Configuration } from '../api/config';
import BannerManagement from '../components/admin/BannerManagement.vue';

const loading = ref(false);
const saving = ref(false);
const configs = ref<Configuration[]>([]);
const activeTab = ref('banners');

const configGroups = computed(() => {
  const groups = new Set<string>();
  configs.value.forEach((config) => {
    groups.add(config.group);
  });
  return Array.from(groups);
});

const getGroupLabel = (group: string) => {
  const labels: Record<string, string> = {
    wechat: '微信配置',
    site: '网站配置',
    general: '通用配置',
    payment: '支付配置',
    reservation: '预约配置',
    points: '积分配置',
  };
  return labels[group] || group;
};

const getConfigsByGroup = (group: string) => {
  return configs.value.filter((config) => config.group === group);
};

const fetchConfigs = async () => {
  loading.value = true;
  try {
    const response = await configApi.getList();
    if (response.code === 200 && response.data) {
      configs.value = response.data.configs;
    }
  } catch (error: any) {
    console.error('获取配置列表失败:', error);
    ElMessage.error('获取配置列表失败');
  } finally {
    loading.value = false;
  }
};

const handleTabChange = () => {
  // 标签切换时不需要特殊处理
};

const handleSaveGroup = async (group: string) => {
  const groupConfigs = getConfigsByGroup(group);
  if (groupConfigs.length === 0) {
    ElMessage.warning('该分组没有配置项');
    return;
  }

  saving.value = true;
  try {
    const updateData = groupConfigs.map((config) => ({
      key: config.key,
      value: config.value,
    }));

    await configApi.batchUpdate(updateData);
    ElMessage.success('配置保存成功');
  } catch (error: any) {
    console.error('保存配置失败:', error);
    ElMessage.error(error.response?.data?.message || '保存配置失败');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchConfigs();
});
</script>

<style scoped>
:deep(.el-form-item__label) {
  font-weight: 500;
}
</style>
