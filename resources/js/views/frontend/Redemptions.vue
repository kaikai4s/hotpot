<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">📦 兑换记录</h1>
          <p class="text-xl text-gray-600">查看您的积分兑换历史</p>
        </div>

        <!-- 筛选 -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-8">
          <div class="flex flex-wrap items-center gap-4">
            <span class="text-gray-700 font-medium">状态筛选：</span>
            <el-radio-group v-model="filterStatus" @change="fetchRedemptions">
              <el-radio-button label="">全部</el-radio-button>
              <el-radio-button label="pending">待发货</el-radio-button>
              <el-radio-button label="shipped">已发货</el-radio-button>
              <el-radio-button label="completed">已完成</el-radio-button>
              <el-radio-button label="cancelled">已取消</el-radio-button>
            </el-radio-group>
          </div>
        </div>

        <!-- 兑换记录列表 -->
        <div v-loading="loading" class="space-y-4">
          <div
            v-for="redemption in redemptions"
            :key="redemption.id"
            class="bg-white rounded-xl shadow-md p-6"
          >
            <div class="flex items-start gap-4">
              <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <img
                  v-if="redemption.product?.image_url"
                  :src="redemption.product.image_url"
                  :alt="redemption.product?.name"
                  class="w-full h-full object-cover rounded-lg"
                />
                <span v-else class="text-3xl">{{ redemption.product?.type === 'physical' ? '📦' : '🎁' }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div>
                    <h3 class="font-bold text-lg text-gray-900">{{ redemption.product?.name || '商品已下架' }}</h3>
                    <p class="text-orange-600 font-medium mt-1">-{{ redemption.points_used.toLocaleString() }} 积分</p>
                  </div>
                  <span
                    class="px-3 py-1 rounded-full text-sm font-medium"
                    :class="getStatusClass(redemption.status)"
                  >
                    {{ getStatusText(redemption.status) }}
                  </span>
                </div>
                
                <p class="text-gray-500 text-sm mt-2">
                  兑换时间：{{ formatDateTime(redemption.created_at) }}
                </p>

                <!-- 物流信息 -->
                <div v-if="redemption.tracking_number" class="mt-3 p-3 bg-blue-50 rounded-lg">
                  <p class="text-sm text-blue-800">
                    <span class="font-medium">物流单号：</span>{{ redemption.tracking_number }}
                  </p>
                </div>

                <!-- 收货地址 -->
                <div v-if="redemption.shipping_address" class="mt-3 p-3 bg-gray-50 rounded-lg">
                  <p class="text-sm text-gray-700">
                    <span class="font-medium">收货人：</span>{{ redemption.shipping_address.name }}
                  </p>
                  <p class="text-sm text-gray-700">
                    <span class="font-medium">电话：</span>{{ redemption.shipping_address.phone }}
                  </p>
                  <p class="text-sm text-gray-700">
                    <span class="font-medium">地址：</span>{{ redemption.shipping_address.address }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 空状态 -->
        <div v-if="!loading && redemptions.length === 0" class="text-center py-16">
          <span class="text-6xl block mb-4">📭</span>
          <p class="text-gray-500 text-lg mb-4">暂无兑换记录</p>
          <router-link to="/frontend/mall">
            <el-button type="primary">去积分商城看看</el-button>
          </router-link>
        </div>

        <!-- 分页 -->
        <div v-if="pagination.total > pagination.per_page" class="mt-8 flex justify-center">
          <el-pagination
            v-model:current-page="pagination.current_page"
            :page-size="pagination.per_page"
            :total="pagination.total"
            layout="prev, pager, next"
            @current-change="fetchRedemptions"
          />
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { pointsMallApi, type ProductRedemption } from '../../api/member-privilege';

const router = useRouter();

const loading = ref(false);
const filterStatus = ref('');
const redemptions = ref<ProductRedemption[]>([]);

const pagination = reactive({
  current_page: 1,
  per_page: 20,
  total: 0,
});

const getStatusText = (status: string) => {
  const map: Record<string, string> = {
    pending: '待发货',
    shipped: '已发货',
    completed: '已完成',
    cancelled: '已取消',
  };
  return map[status] || status;
};

const getStatusClass = (status: string) => {
  const map: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    shipped: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-gray-100 text-gray-800',
  };
  return map[status] || 'bg-gray-100 text-gray-800';
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const fetchRedemptions = async () => {
  loading.value = true;
  try {
    const response = await pointsMallApi.getRedemptions({
      status: filterStatus.value || undefined,
      per_page: pagination.per_page,
    });
    if (response.code === 200 && response.data) {
      redemptions.value = response.data.redemptions;
      pagination.total = response.data.pagination.total;
      pagination.current_page = response.data.pagination.current_page;
    }
  } catch (error) {
    console.error('获取兑换记录失败:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  const token = localStorage.getItem('token');
  if (!token) {
    router.push({
      path: '/frontend/login',
      query: { redirect: '/frontend/redemptions' },
    });
    return;
  }

  await fetchRedemptions();
});
</script>
