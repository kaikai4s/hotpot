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
          <h1 class="text-5xl font-bold text-gray-900 mb-4">🍲 套餐活动</h1>
          <p class="text-xl text-gray-600">多菜品组合，超值优惠</p>
        </div>

        <!-- 套餐列表 -->
        <div v-loading="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="groupBuy in groupBuys"
            :key="groupBuy.id"
            class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl cursor-pointer"
            @click="viewDetail(groupBuy.id)"
          >
            <!-- 团购图片 -->
            <div class="h-48 bg-gradient-to-br from-red-200 via-orange-200 to-yellow-200 flex items-center justify-center relative overflow-hidden">
              <img
                v-if="groupBuy.image_url"
                :src="groupBuy.image_url"
                :alt="groupBuy.name"
                class="w-full h-full object-cover"
                @error="handleImageError"
              />
              <span v-else class="text-6xl">🎁</span>
              <!-- 折扣标签 -->
              <div class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                {{ Math.round(((groupBuy.original_price - groupBuy.group_price) / groupBuy.original_price) * 100) }}% OFF
              </div>
            </div>

            <!-- 团购信息 -->
            <div class="p-6">
              <h3 class="text-xl font-bold text-gray-900 mb-2">{{ groupBuy.name }}</h3>
              <p v-if="groupBuy.description" class="text-gray-600 text-sm mb-4 line-clamp-2">{{ groupBuy.description }}</p>
              
              <!-- 包含菜品 -->
              <div v-if="groupBuy.items && groupBuy.items.length > 0" class="mb-4">
                <div class="text-sm text-gray-500 mb-2">包含菜品：</div>
                <div class="flex flex-wrap gap-2">
                  <el-tag
                    v-for="item in groupBuy.items.slice(0, 3)"
                    :key="item.id"
                    size="small"
                    type="info"
                  >
                    {{ item.dish?.name }} x{{ item.quantity }}
                  </el-tag>
                  <el-tag v-if="groupBuy.items.length > 3" size="small" type="info">
                    +{{ groupBuy.items.length - 3 }} 更多
                  </el-tag>
                </div>
              </div>

              <!-- 价格 -->
              <div class="flex items-center gap-3 mb-4">
                <div>
                  <div class="text-sm text-gray-500 line-through">¥{{ groupBuy.original_price }}</div>
                  <div class="text-2xl font-bold text-red-600">¥{{ groupBuy.group_price }}</div>
                </div>
                <div class="text-sm text-green-600 font-semibold">
                  省¥{{ (groupBuy.original_price - groupBuy.group_price).toFixed(2) }}
                </div>
              </div>

              <!-- 库存和销量 -->
              <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                <div>
                  <span v-if="groupBuy.stock > 0">库存：{{ groupBuy.stock - groupBuy.sold_count }}</span>
                  <span v-else>库存充足</span>
                </div>
                <div>已售：{{ groupBuy.sold_count }}</div>
              </div>

              <!-- 时间信息 -->
              <div v-if="groupBuy.end_time" class="text-xs text-gray-500 mb-4">
                活动截止：{{ formatDate(groupBuy.end_time) }}
              </div>

              <!-- 操作按钮 -->
              <el-button
                type="primary"
                class="w-full"
                :disabled="!isAvailable(groupBuy)"
                @click.stop="handlePurchase(groupBuy)"
              >
                {{ isAvailable(groupBuy) ? '立即抢购' : '已结束' }}
              </el-button>
            </div>
          </div>

          <!-- 空状态 -->
          <div v-if="groupBuys.length === 0 && !loading" class="col-span-full text-center py-20">
            <span class="text-6xl mb-4 block">📦</span>
            <p class="text-xl text-gray-600">暂无套餐活动</p>
          </div>
        </div>

        <!-- 分页 -->
        <div v-if="total > 0" class="mt-8 flex justify-center">
          <el-pagination
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :total="total"
            :page-sizes="[12, 24, 48]"
            layout="total, sizes, prev, pager, next"
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </div>
    </div>

    <!-- 购买对话框 -->
    <el-dialog
      v-model="purchaseDialogVisible"
      :title="`购买套餐：${selectedGroupBuy?.name}`"
      width="500px"
      @close="handlePurchaseDialogClose"
    >
      <div v-if="selectedGroupBuy" class="space-y-4">
        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
          <img
            v-if="selectedGroupBuy.image_url"
            :src="selectedGroupBuy.image_url"
            :alt="selectedGroupBuy.name"
            class="w-20 h-20 object-cover rounded-lg"
            @error="handleImageError"
          />
          <div class="flex-1">
            <div class="font-semibold text-lg">{{ selectedGroupBuy.name }}</div>
            <div class="flex items-center gap-2 mt-2">
              <span class="text-sm text-gray-500 line-through">¥{{ selectedGroupBuy.original_price }}</span>
              <span class="text-xl font-bold text-red-600">¥{{ selectedGroupBuy.group_price }}</span>
            </div>
          </div>
        </div>

        <el-form-item label="购买数量">
          <el-input-number
            v-model="purchaseQuantity"
            :min="1"
            :max="getMaxQuantity(selectedGroupBuy)"
            :precision="0"
            style="width: 200px"
          />
          <div class="text-xs text-gray-500 mt-1">
            <span v-if="selectedGroupBuy.limit_per_user > 0">每人限购 {{ selectedGroupBuy.limit_per_user }} 份</span>
            <span v-else>不限制购买数量</span>
          </div>
        </el-form-item>

        <div class="p-4 bg-blue-50 rounded-lg">
          <div class="flex justify-between items-center">
            <span class="font-semibold">总计：</span>
            <span class="text-2xl font-bold text-red-600">¥{{ (selectedGroupBuy.group_price * purchaseQuantity).toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <template #footer>
        <el-button @click="purchaseDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="purchasing" @click="confirmPurchase">确认购买</el-button>
      </template>
    </el-dialog>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import FrontendLayout from './Layout.vue';
import { groupBuyApi, type GroupBuy } from '../../api/frontend/group-buy';

const router = useRouter();

const loading = ref(false);
const purchasing = ref(false);
const groupBuys = ref<GroupBuy[]>([]);
const currentPage = ref(1);
const pageSize = ref(12);
const total = ref(0);

const purchaseDialogVisible = ref(false);
const selectedGroupBuy = ref<GroupBuy | null>(null);
const purchaseQuantity = ref(1);

const formatDate = (date: string) => {
  if (!date) return '';
  return new Date(date).toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const isAvailable = (groupBuy: GroupBuy): boolean => {
  if (!groupBuy.is_active) return false;
  if (groupBuy.status !== 'published' && groupBuy.status !== 'ongoing') return false;
  if (groupBuy.stock > 0 && groupBuy.sold_count >= groupBuy.stock) return false;
  
  const now = new Date();
  if (groupBuy.start_time && new Date(groupBuy.start_time) > now) return false;
  if (groupBuy.end_time && new Date(groupBuy.end_time) < now) return false;
  
  return true;
};

const getMaxQuantity = (groupBuy: GroupBuy): number => {
  if (groupBuy.limit_per_user > 0) {
    return groupBuy.limit_per_user;
  }
  if (groupBuy.stock > 0) {
    return groupBuy.stock - groupBuy.sold_count;
  }
  return 999;
};

const handleImageError = (e: Event) => {
  (e.target as HTMLImageElement).style.display = 'none';
};

const fetchData = async () => {
  loading.value = true;
  try {
    const response = await groupBuyApi.getList({
      per_page: pageSize.value,
      page: currentPage.value,
    });

    if (response.code === 200 && response.data) {
      groupBuys.value = response.data.group_buys;
      total.value = response.data.pagination.total;
    }
    } catch (error: any) {
      console.error('获取套餐列表失败:', error);
      ElMessage.error('获取套餐列表失败');
    } finally {
    loading.value = false;
  }
};

const handleSizeChange = (size: number) => {
  pageSize.value = size;
  currentPage.value = 1;
  fetchData();
};

const handlePageChange = (page: number) => {
  currentPage.value = page;
  fetchData();
};

const viewDetail = (id: number) => {
  router.push(`/frontend/group-buys/${id}`);
};

const handlePurchase = (groupBuy: GroupBuy) => {
  selectedGroupBuy.value = groupBuy;
  purchaseQuantity.value = 1;
  purchaseDialogVisible.value = true;
};

const handlePurchaseDialogClose = () => {
  selectedGroupBuy.value = null;
  purchaseQuantity.value = 1;
};

const confirmPurchase = async () => {
  if (!selectedGroupBuy.value) return;

  purchasing.value = true;
  try {
    const response = await groupBuyApi.purchase(selectedGroupBuy.value.id, {
      quantity: purchaseQuantity.value,
      payment_method: 'mock', // 默认使用模拟支付
    });

    if (response.code === 200 && response.data) {
      ElMessage.success('套餐购买成功，请完成支付');
      purchaseDialogVisible.value = false;
      // 跳转到订单支付页面
      if (response.data.order) {
        router.push(`/frontend/orders/${response.data.order.id}`);
      }
    }
  } catch (error: any) {
    console.error('购买套餐失败:', error);
    ElMessage.error(error.response?.data?.message || '购买失败');
  } finally {
    purchasing.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
:deep(.el-pagination) {
  justify-content: center;
}
</style>

