<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">🛒 积分商城</h1>
          <p class="text-xl text-gray-600">积分换好礼，惊喜不断</p>
        </div>

        <!-- 积分余额 -->
        <div class="bg-gradient-to-r from-green-400 to-blue-500 rounded-2xl shadow-xl p-6 mb-8 text-white">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-lg opacity-90">我的可用积分</p>
              <p class="text-4xl font-bold">{{ availablePoints.toLocaleString() }}</p>
            </div>
            <router-link
              to="/frontend/points"
              class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg transition-all"
            >
              查看积分明细 →
            </router-link>
          </div>
        </div>

        <!-- 筛选 -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-8">
          <div class="flex flex-wrap items-center gap-4">
            <span class="text-gray-700 font-medium">商品类型：</span>
            <el-radio-group v-model="filterType" @change="fetchProducts">
              <el-radio-button label="">全部</el-radio-button>
              <el-radio-button label="physical">实物商品</el-radio-button>
              <el-radio-button label="experience">体验服务</el-radio-button>
            </el-radio-group>
          </div>
        </div>

        <!-- 商品列表 -->
        <div v-loading="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="product in products"
            :key="product.id"
            class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
            @click="showProductDetail(product)"
          >
            <div class="aspect-video bg-gray-100 relative">
              <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                class="w-full h-full object-cover"
                loading="lazy"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-6xl">
                {{ product.type === 'physical' ? '📦' : '🎁' }}
              </div>
              <span
                v-if="product.stock === 0"
                class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-sm"
              >
                已售罄
              </span>
              <span
                v-else-if="product.stock <= 10"
                class="absolute top-2 right-2 bg-orange-500 text-white px-2 py-1 rounded text-sm"
              >
                仅剩{{ product.stock }}件
              </span>
            </div>
            <div class="p-4">
              <h3 class="font-bold text-lg text-gray-900 mb-2">{{ product.name }}</h3>
              <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ product.description || '暂无描述' }}</p>
              <div class="flex items-center justify-between">
                <span class="text-orange-600 font-bold text-xl">{{ product.points_required.toLocaleString() }} 积分</span>
                <el-button
                  type="primary"
                  size="small"
                  :disabled="product.stock === 0 || availablePoints < product.points_required"
                  @click.stop="openRedeemDialog(product)"
                >
                  {{ product.stock === 0 ? '已售罄' : availablePoints >= product.points_required ? '立即兑换' : '积分不足' }}
                </el-button>
              </div>
            </div>
          </div>
        </div>

        <!-- 空状态 -->
        <div v-if="!loading && products.length === 0" class="text-center py-16">
          <span class="text-6xl block mb-4">🛍️</span>
          <p class="text-gray-500 text-lg">暂无可兑换商品</p>
        </div>

        <!-- 分页 -->
        <div v-if="pagination.total > pagination.per_page" class="mt-8 flex justify-center">
          <el-pagination
            v-model:current-page="pagination.current_page"
            :page-size="pagination.per_page"
            :total="pagination.total"
            layout="prev, pager, next"
            @current-change="fetchProducts"
          />
        </div>
      </div>
    </div>

    <!-- 兑换对话框 -->
    <el-dialog v-model="redeemDialogVisible" title="确认兑换" width="500px">
      <div v-if="selectedProduct">
        <div class="flex items-start gap-4 mb-6">
          <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
            <img
              v-if="selectedProduct.image_url"
              :src="selectedProduct.image_url"
              :alt="selectedProduct.name"
              class="w-full h-full object-cover rounded-lg"
            />
            <span v-else class="text-4xl">{{ selectedProduct.type === 'physical' ? '📦' : '🎁' }}</span>
          </div>
          <div class="flex-1">
            <h3 class="font-bold text-lg">{{ selectedProduct.name }}</h3>
            <p class="text-orange-600 font-bold mt-2">{{ selectedProduct.points_required.toLocaleString() }} 积分</p>
          </div>
        </div>

        <!-- 实物商品需要填写地址 -->
        <div v-if="selectedProduct.type === 'physical'" class="space-y-4">
          <p class="text-gray-700 font-medium">收货信息</p>
          <el-form :model="shippingForm" label-width="80px">
            <el-form-item label="收货人" required>
              <el-input v-model="shippingForm.name" placeholder="请输入收货人姓名" />
            </el-form-item>
            <el-form-item label="联系电话" required>
              <el-input v-model="shippingForm.phone" placeholder="请输入联系电话" />
            </el-form-item>
            <el-form-item label="收货地址" required>
              <el-input
                v-model="shippingForm.address"
                type="textarea"
                :rows="2"
                placeholder="请输入详细收货地址"
              />
            </el-form-item>
          </el-form>
        </div>

        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">当前积分</span>
            <span>{{ availablePoints.toLocaleString() }}</span>
          </div>
          <div class="flex justify-between text-sm mt-2">
            <span class="text-gray-600">消耗积分</span>
            <span class="text-red-600">-{{ selectedProduct.points_required.toLocaleString() }}</span>
          </div>
          <div class="flex justify-between font-bold mt-2 pt-2 border-t">
            <span>兑换后积分</span>
            <span>{{ (availablePoints - selectedProduct.points_required).toLocaleString() }}</span>
          </div>
        </div>
      </div>
      <template #footer>
        <el-button @click="redeemDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="redeeming" @click="confirmRedeem">
          确认兑换
        </el-button>
      </template>
    </el-dialog>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { pointsMallApi, type MallProduct, type ShippingAddress } from '../../api/member-privilege';
import { frontendPointsApi } from '../../api/frontend-points';

const router = useRouter();

const loading = ref(false);
const redeeming = ref(false);
const availablePoints = ref(0);
const filterType = ref('');
const products = ref<MallProduct[]>([]);
const selectedProduct = ref<MallProduct | null>(null);
const redeemDialogVisible = ref(false);

const pagination = reactive({
  current_page: 1,
  per_page: 12,
  total: 0,
});

const shippingForm = reactive<ShippingAddress>({
  name: '',
  phone: '',
  address: '',
});

const fetchPoints = async () => {
  try {
    const response = await frontendPointsApi.getPoints();
    if (response.code === 200 && response.data) {
      availablePoints.value = response.data.available_points;
    }
  } catch (error) {
    console.error('获取积分失败:', error);
  }
};

const fetchProducts = async () => {
  loading.value = true;
  try {
    const response = await pointsMallApi.getProducts({
      type: filterType.value as 'physical' | 'experience' | undefined,
      per_page: pagination.per_page,
    });
    if (response.code === 200 && response.data) {
      products.value = response.data.products;
      pagination.total = response.data.pagination.total;
      pagination.current_page = response.data.pagination.current_page;
    }
  } catch (error) {
    console.error('获取商品列表失败:', error);
    ElMessage.error('获取商品列表失败');
  } finally {
    loading.value = false;
  }
};

const showProductDetail = (product: MallProduct) => {
  router.push(`/frontend/mall/${product.id}`);
};

const openRedeemDialog = (product: MallProduct) => {
  selectedProduct.value = product;
  shippingForm.name = '';
  shippingForm.phone = '';
  shippingForm.address = '';
  redeemDialogVisible.value = true;
};

const confirmRedeem = async () => {
  if (!selectedProduct.value) return;

  // 验证实物商品地址
  if (selectedProduct.value.type === 'physical') {
    if (!shippingForm.name || !shippingForm.phone || !shippingForm.address) {
      ElMessage.warning('请填写完整的收货信息');
      return;
    }
  }

  try {
    await ElMessageBox.confirm(
      `确认使用 ${selectedProduct.value.points_required.toLocaleString()} 积分兑换 ${selectedProduct.value.name} 吗？`,
      '确认兑换',
      {
        confirmButtonText: '确认',
        cancelButtonText: '取消',
        type: 'warning',
      }
    );

    redeeming.value = true;
    const response = await pointsMallApi.redeemProduct(
      selectedProduct.value.id,
      selectedProduct.value.type === 'physical' ? shippingForm : undefined
    );

    if (response.code === 200) {
      ElMessage.success('兑换成功！');
      redeemDialogVisible.value = false;
      await fetchPoints();
      await fetchProducts();
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || '兑换失败');
    }
  } finally {
    redeeming.value = false;
  }
};

onMounted(async () => {
  const token = localStorage.getItem('token');
  if (!token) {
    router.push({
      path: '/frontend/login',
      query: { redirect: '/frontend/mall' },
    });
    return;
  }

  await Promise.all([fetchPoints(), fetchProducts()]);
});
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
