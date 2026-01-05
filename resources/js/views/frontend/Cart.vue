/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">🛒 购物车</h1>
          <p class="text-xl text-gray-600">确认您的订单</p>
        </div>

        <!-- 购物车内容 -->
        <div v-if="cartStore.items.length > 0" class="bg-white rounded-2xl shadow-xl p-6 mb-6">
          <!-- 购物车列表 -->
          <div class="space-y-4 mb-6">
            <div
              v-for="(item, index) in cartStore.items"
              :key="`${item.type}-${item.type === 'dish' ? item.dish?.id : item.combo?.id}`"
              class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
            >
              <!-- 菜品/套餐图片 -->
              <div class="w-24 h-24 bg-gradient-to-br from-red-200 via-orange-200 to-yellow-200 rounded-lg flex items-center justify-center flex-shrink-0">
                <span class="text-4xl">{{ item.type === 'combo' ? '🍱' : '🍲' }}</span>
              </div>
              
              <!-- 菜品/套餐信息 -->
              <div class="flex-1">
                <h3 class="text-xl font-bold text-gray-900 mb-1">
                  {{ item.type === 'dish' ? item.dish?.name : item.combo?.name }}
                </h3>
                <p class="text-gray-600 text-sm mb-2">
                  {{ item.type === 'dish' ? (item.dish?.description || '暂无描述') : (item.combo?.description || '暂无描述') }}
                </p>
                <div class="flex items-center gap-4">
                  <span class="text-2xl font-bold text-red-600">
                    ¥{{ item.type === 'dish' ? item.dish?.price : parseFloat(item.combo?.price || '0') }}
                  </span>
                  <span class="text-gray-500">x {{ item.quantity }}</span>
                  <span class="text-lg font-semibold text-gray-900">
                    小计: ¥{{ (item.type === 'dish' 
                      ? (item.dish?.price || 0) * item.quantity 
                      : parseFloat(item.combo?.price || '0') * item.quantity).toFixed(2) }}
                  </span>
                </div>
              </div>

              <!-- 操作按钮 -->
              <div class="flex items-center gap-2">
                <el-button
                  :icon="Minus"
                  circle
                  size="small"
                  @click="item.type === 'dish' ? decreaseQuantity(item.dish!.id) : decreaseComboQuantity(item.combo!.id)"
                />
                <span class="w-12 text-center font-semibold">{{ item.quantity }}</span>
                <el-button
                  :icon="Plus"
                  circle
                  size="small"
                  @click="item.type === 'dish' ? increaseQuantity(item.dish!.id) : increaseComboQuantity(item.combo!.id)"
                />
                <el-button
                  type="danger"
                  :icon="Delete"
                  circle
                  size="small"
                  @click="item.type === 'dish' ? removeItem(item.dish!.id) : removeComboItem(item.combo!.id)"
                />
              </div>
            </div>
          </div>

          <!-- 购物车汇总 -->
          <div class="border-t border-gray-200 pt-6">
            <!-- 桌位选择 -->
            <div class="mb-6">
              <label class="block text-lg font-semibold text-gray-900 mb-3">
                🪑 选择桌位 <span class="text-red-500">*</span>
              </label>
              <div v-loading="loadingTables" class="space-y-3">
                <el-select
                  v-model="selectedTableId"
                  placeholder="请选择您的桌位"
                  size="large"
                  class="w-full"
                  filterable
                  @change="handleTableChange"
                >
                  <el-option
                    v-for="table in availableTables"
                    :key="table.id"
                    :label="getTableLabel(table)"
                    :value="table.id"
                    :disabled="isTableDisabled(table)"
                  >
                    <div class="flex items-center justify-between">
                      <span>{{ table.name }}</span>
                      <span class="text-sm text-gray-500">
                        {{ table.capacity }}人座 · {{ getTableTypeName(table.type) }}
                        <el-tag
                          v-if="table.status === 'reserved'"
                          type="warning"
                          size="small"
                          class="ml-2"
                        >
                          已预约
                        </el-tag>
                        <el-tag
                          v-if="table.status === 'occupied' && (table.occupied_by_user_id === userInfo?.id || table.occupied_by_user?.id === userInfo?.id)"
                          type="success"
                          size="small"
                          class="ml-2"
                        >
                          我的桌位
                        </el-tag>
                        <el-tag
                          v-if="table.team_code"
                          type="info"
                          size="small"
                          class="ml-2"
                        >
                          团队：{{ table.team_code }}
                        </el-tag>
                      </span>
                    </div>
                  </el-option>
                </el-select>
                
                <!-- 显示当前选中桌位的团队码 -->
                <div v-if="selectedTable && selectedTable.team_code" class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-semibold text-blue-900">团队点餐码</p>
                      <p class="text-lg font-bold text-blue-600 mt-1">{{ selectedTable.team_code }}</p>
                      <p class="text-xs text-blue-600 mt-1">分享此码给朋友，让他们加入团队一起点餐</p>
                    </div>
                    <el-button
                      type="primary"
                      size="small"
                      @click="copyTeamCode"
                    >
                      <el-icon><DocumentCopy /></el-icon>
                      复制
                    </el-button>
                  </div>
                </div>
                
                <!-- 加入团队 -->
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                  <div class="flex items-center gap-2 mb-2">
                    <el-icon class="text-gray-600"><UserFilled /></el-icon>
                    <span class="text-sm font-semibold text-gray-700">加入团队点餐</span>
                  </div>
                  <div class="flex gap-2">
                    <el-input
                      v-model="teamCodeInput"
                      placeholder="输入团队码（如：TEAM123456）"
                      size="small"
                      maxlength="10"
                      @keyup.enter="handleJoinTeam"
                    />
                    <el-button
                      type="primary"
                      size="small"
                      @click="handleJoinTeam"
                      :loading="joiningTeam"
                    >
                      加入
                    </el-button>
                  </div>
                </div>
                
                <p class="text-sm text-gray-500">
                  💡 提示：选择桌位后，菜品将送到您指定的位置。首次选择桌位会生成团队码，可分享给朋友一起点餐。
                </p>
              </div>
            </div>

            <div class="flex justify-between items-center mb-4">
              <span class="text-lg text-gray-600">商品总数：</span>
              <span class="text-xl font-bold text-gray-900">{{ cartStore.totalQuantity }} 件</span>
            </div>
            <div class="flex justify-between items-center mb-6">
              <span class="text-lg text-gray-600">合计金额：</span>
              <span class="text-3xl font-bold text-red-600">¥{{ cartStore.totalAmount.toFixed(2) }}</span>
            </div>
            <div class="flex gap-4">
              <el-button size="large" @click="clearCart">清空购物车</el-button>
              <el-button
                type="primary"
                size="large"
                class="flex-1"
                :disabled="!selectedTableId"
                @click="checkout"
              >
                去结算
              </el-button>
            </div>
          </div>
        </div>

        <!-- 空购物车 -->
        <div v-else class="bg-white rounded-2xl shadow-xl p-12 text-center">
          <span class="text-8xl mb-6 block">🛒</span>
          <h2 class="text-3xl font-bold text-gray-900 mb-4">购物车是空的</h2>
          <p class="text-gray-600 mb-8">快去挑选您喜欢的菜品吧！</p>
          <el-button type="primary" size="large" @click="goToDishes">
            去选购
          </el-button>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Minus, Delete, DocumentCopy, UserFilled } from '@element-plus/icons-vue';
import { useRouter } from 'vue-router';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { useCartStore } from '../../stores/cart';
import { orderApi } from '../../api/order';
import { frontendTableApi, type Table } from '../../api/frontend-table';
import { userAuthApi, type UserInfo } from '../../api/auth';

const router = useRouter();
const cartStore = useCartStore();
const selectedTableId = ref<number | null>(null);
const availableTables = ref<Table[]>([]);
const loadingTables = ref(false);
const userInfo = ref<UserInfo | null>(null);
const teamCodeInput = ref('');
const joiningTeam = ref(false);

// 计算当前选中的桌位
const selectedTable = computed(() => {
  if (!selectedTableId.value) return null;
  return availableTables.value.find(t => t.id === selectedTableId.value) || null;
});

const increaseQuantity = (dishId: number) => {
  const item = cartStore.items.find(item => item.type === 'dish' && item.dish?.id === dishId);
  if (item) {
    cartStore.updateDishQuantity(dishId, item.quantity + 1);
  }
};

const decreaseQuantity = (dishId: number) => {
  const item = cartStore.items.find(item => item.type === 'dish' && item.dish?.id === dishId);
  if (item && item.quantity > 1) {
    cartStore.updateDishQuantity(dishId, item.quantity - 1);
  } else if (item && item.quantity === 1) {
    // 如果数量为1，点击减号直接移除
    removeItem(dishId);
  }
};

const increaseComboQuantity = (comboId: number) => {
  const item = cartStore.items.find(item => item.type === 'combo' && item.combo?.id === comboId);
  if (item) {
    cartStore.updateComboQuantity(comboId, item.quantity + 1);
  }
};

const decreaseComboQuantity = (comboId: number) => {
  const item = cartStore.items.find(item => item.type === 'combo' && item.combo?.id === comboId);
  if (item && item.quantity > 1) {
    cartStore.updateComboQuantity(comboId, item.quantity - 1);
  } else if (item && item.quantity === 1) {
    // 如果数量为1，点击减号直接移除
    removeComboItem(comboId);
  }
};

const removeItem = async (dishId: number) => {
  try {
    await ElMessageBox.confirm('确定要移除这个商品吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });
    cartStore.removeDish(dishId);
    ElMessage.success('已移除');
  } catch {
    // 用户取消
  }
};

const removeComboItem = async (comboId: number) => {
  try {
    await ElMessageBox.confirm('确定要移除这个商品吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });
    cartStore.removeCombo(comboId);
    ElMessage.success('已移除');
  } catch {
    // 用户取消
  }
};

const clearCart = async () => {
  try {
    await ElMessageBox.confirm('确定要清空购物车吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });
    cartStore.clearCart();
    ElMessage.success('购物车已清空');
  } catch {
    // 用户取消
  }
};

const loadAvailableTables = async () => {
  loadingTables.value = true;
  try {
    // 确保用户信息已加载（如果未登录会静默失败，但不影响桌位加载）
    if (!userInfo.value) {
      // 如果用户信息为空，先尝试从缓存加载
      const userInfoStr = localStorage.getItem('user_info');
      if (userInfoStr) {
        try {
          userInfo.value = JSON.parse(userInfoStr);
        } catch (e) {
          console.error('解析user_info失败:', e);
        }
      }
      
      // 如果缓存也没有，尝试从API获取（静默失败）
      if (!userInfo.value) {
        try {
          const userResponse = await userAuthApi.me();
          if (userResponse && userResponse.code === 200 && userResponse.data) {
            userInfo.value = userResponse.data;
            localStorage.setItem('user_info', JSON.stringify(userResponse.data));
          }
        } catch (e) {
          // 静默失败，不影响桌位加载
          console.error('获取用户信息失败:', e);
        }
      }
    }
    
    const response = await frontendTableApi.getAvailableTables();
    if (response.code === 200 && response.data) {
      availableTables.value = response.data.tables;
    }
  } catch (error: any) {
    console.error('加载可用桌位失败:', error);
    ElMessage.error(error.response?.data?.message || '加载桌位列表失败');
  } finally {
    loadingTables.value = false;
  }
};

const getTableTypeName = (type: string) => {
  const typeMap: Record<string, string> = {
    window: '靠窗',
    corner: '角落',
    center: '中央',
  };
  return typeMap[type] || type;
};

// 判断是否是当前用户的桌位
const isMyTable = (table: Table) => {
  if (!userInfo.value?.id) {
    // 如果用户信息未加载，尝试从缓存获取
    const userInfoStr = localStorage.getItem('user_info');
    if (userInfoStr) {
      try {
        const cachedUserInfo = JSON.parse(userInfoStr);
        if (cachedUserInfo?.id) {
          // 使用缓存的用户ID进行判断
          return table.occupied_by_user_id === cachedUserInfo.id || 
                 table.occupied_by_user?.id === cachedUserInfo.id;
        }
      } catch (e) {
        console.error('解析缓存的user_info失败:', e);
      }
    }
    return false;
  }
  // 优先使用 occupied_by_user_id，如果没有则使用 occupied_by_user?.id
  const userId = userInfo.value.id;
  return table.occupied_by_user_id === userId || 
         table.occupied_by_user?.id === userId;
};

// 获取桌位标签
const getTableLabel = (table: Table) => {
  let label = `${table.name} (${table.capacity}人座, ${getTableTypeName(table.type)})`;
  if (table.status === 'occupied' && isMyTable(table)) {
    label += ' - 我的桌位';
  }
  if (table.team_code) {
    label += ` - 团队: ${table.team_code}`;
  }
  return label;
};

// 判断桌位是否禁用
const isTableDisabled = (table: Table) => {
  // available 和 reserved 状态可用
  if (table.status === 'available' || table.status === 'reserved') {
    return false;
  }
  // occupied 状态：如果是当前用户使用的，可用（用于加菜）
  if (table.status === 'occupied' && isMyTable(table)) {
    return false;
  }
  // 其他情况禁用
  return true;
};

// 处理桌位变更
const handleTableChange = (tableId: number | null) => {
  if (!tableId) return;
  const table = availableTables.value.find(t => t.id === tableId);
  if (table && table.team_code) {
    // 如果桌位有团队码，显示提示
    ElMessage.success(`已选择桌位 ${table.name}，团队码：${table.team_code}`);
  }
};

// 复制团队码
const copyTeamCode = async () => {
  if (!selectedTable.value?.team_code) return;
  
  try {
    await navigator.clipboard.writeText(selectedTable.value.team_code);
    ElMessage.success('团队码已复制到剪贴板');
  } catch (error) {
    // 降级方案
    const textArea = document.createElement('textarea');
    textArea.value = selectedTable.value.team_code;
    document.body.appendChild(textArea);
    textArea.select();
    try {
      document.execCommand('copy');
      ElMessage.success('团队码已复制到剪贴板');
    } catch (err) {
      ElMessage.error('复制失败，请手动复制');
    }
    document.body.removeChild(textArea);
  }
};

// 加入团队
const handleJoinTeam = async () => {
  if (!teamCodeInput.value || teamCodeInput.value.trim().length !== 10) {
    ElMessage.warning('请输入有效的团队码（10位字符）');
    return;
  }
  
  joiningTeam.value = true;
  try {
    const response = await frontendTableApi.joinTeam({
      team_code: teamCodeInput.value.trim().toUpperCase(),
    });
    
    if (response.code === 200 && response.data) {
      const table = response.data.table;
      // 更新桌位列表
      await loadAvailableTables();
      // 自动选择该桌位
      selectedTableId.value = table.id;
      teamCodeInput.value = '';
      ElMessage.success(`成功加入团队！桌位：${table.name}`);
    } else {
      ElMessage.error(response.message || '加入团队失败');
    }
  } catch (error: any) {
    console.error('加入团队失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '加入团队失败');
  } finally {
    joiningTeam.value = false;
  }
};

// 加载用户信息
const loadUserInfo = async () => {
  try {
    const userInfoStr = localStorage.getItem('user_info');
    if (userInfoStr) {
      try {
        userInfo.value = JSON.parse(userInfoStr);
      } catch (e) {
        console.error('解析user_info失败:', e);
      }
    }
    
    // 从服务器获取最新信息
    const response = await userAuthApi.me();
    if (response && response.code === 200 && response.data) {
      userInfo.value = response.data;
      localStorage.setItem('user_info', JSON.stringify(response.data));
    }
  } catch (error: any) {
    console.error('获取用户信息失败:', error);
    // 静默失败，不影响页面显示
  }
};

const checkout = async () => {
  if (cartStore.items.length === 0) {
    ElMessage.warning('购物车是空的');
    return;
  }

  if (!selectedTableId.value) {
    ElMessage.warning('请先选择桌位');
    return;
  }

  try {
    // 构建订单项
    const items = cartStore.items.map(item => {
      if (item.type === 'combo' && item.combo) {
        return {
          type: 'combo',
          combo_id: item.combo.id,
          quantity: item.quantity,
        };
      } else if (item.type === 'dish' && item.dish) {
        return {
          type: 'dish',
          dish_id: item.dish.id,
          quantity: item.quantity,
        };
      }
      return null;
    }).filter(Boolean);

    // 获取选中桌位的团队码（如果有）
    const selectedTable = availableTables.value.find(t => t.id === selectedTableId.value);
    const teamCode = selectedTable?.team_code || undefined;
    
    // 创建订单（包含桌位ID和团队码）
    const response = await orderApi.create({
      items,
      table_id: selectedTableId.value,
      team_code: teamCode,
    });

    if (response.code === 200 && response.data) {
      const order = response.data;
      // 清空购物车
      cartStore.clearCart();
      // 重置桌位选择
      selectedTableId.value = null;
      // 跳转到结算页面
      router.push(`/frontend/checkout/${order.id}`);
    } else {
      ElMessage.error(response.message || '创建订单失败');
    }
  } catch (error: any) {
    console.error('创建订单失败:', error);
    // apiClient 的响应拦截器已经处理了响应，error.response?.data 可能包含错误信息
    // 优先使用 error.message（响应拦截器已设置），其次使用 error.response?.data?.message
    const errorMessage = error.message || error.response?.data?.message || '创建订单失败，请稍后重试';
    ElMessage.error(errorMessage);
    
    // 如果是401错误（未登录），响应拦截器会处理跳转，这里不需要额外处理
  }
};

onMounted(async () => {
  await loadUserInfo();
  await loadAvailableTables();
});

const goToDishes = () => {
  router.push('/frontend/dishes');
};
</script>

<style scoped>
</style>

