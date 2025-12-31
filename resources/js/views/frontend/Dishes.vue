<template>
  <FrontendLayout>
    <div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- 页面标题 -->
      <div class="text-center mb-12">
        <h1 class="text-5xl font-bold text-gray-900 mb-4">🍲 菜品菜单</h1>
        <p class="text-xl text-gray-600">精选美味，任您选择</p>
      </div>

      <!-- 搜索和筛选 -->
      <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
        <div class="flex flex-col md:flex-row gap-4">
          <el-input
            v-model="searchKeyword"
            placeholder="搜索菜品名称"
            class="flex-1"
            clearable
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
          <el-select v-model="selectedCategory" placeholder="选择分类" clearable class="w-48">
            <el-option
              v-for="category in categories"
              :key="category.id"
              :label="category.name"
              :value="category.id"
            />
          </el-select>
          <el-select v-model="sortBy" placeholder="排序方式" class="w-48">
            <el-option label="默认排序" value="default" />
            <el-option label="价格从低到高" value="price_asc" />
            <el-option label="价格从高到低" value="price_desc" />
            <el-option label="评分最高" value="rating_desc" />
            <el-option label="销量最高" value="sales_desc" />
          </el-select>
        </div>
      </div>

      <!-- 菜品网格 -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div
          v-for="dish in dishes"
          :key="dish.id"
          class="bg-white rounded-xl shadow-md overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl cursor-pointer group"
          @click="viewDishDetail(dish)"
        >
          <div class="h-48 bg-gradient-to-br from-red-200 via-orange-200 to-yellow-200 flex items-center justify-center relative overflow-hidden cursor-pointer" @click.stop="previewImage(dish.image_url)">
            <img
              v-if="dish.image_url"
              :src="getImageUrl(dish.image_url)"
              :alt="dish.name"
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
              @error="handleImageError"
            />
            <span v-else class="text-6xl group-hover:scale-110 transition-transform duration-300">🍲</span>
            <div v-if="dish.status === 'sold_out'" class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center">
              <span class="text-white font-bold text-xl">已售罄</span>
            </div>
            <div v-if="dish.status === 'available'" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
              热销
            </div>
          </div>
          <div class="p-5">
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ dish.name }}</h3>
            <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ dish.description || '暂无描述' }}</p>
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center">
                <el-rate :model-value="Number(dish.average_rating) || 0" disabled size="small" />
                <span class="text-xs text-gray-500 ml-2">({{ dish.review_count }})</span>
              </div>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-2xl font-bold text-red-600">¥{{ dish.price }}</span>
              <button
                @click.stop="addToCart(dish)"
                :disabled="dish.status !== 'available'"
                class="bg-gradient-to-r from-red-500 to-orange-500 text-white px-6 py-2 rounded-lg hover:from-red-600 hover:to-orange-600 transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                加入购物车
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- 空状态 -->
      <div v-if="dishes.length === 0 && !loading" class="text-center py-20">
        <span class="text-6xl mb-4 block">🔍</span>
        <p class="text-xl text-gray-600">暂无符合条件的菜品</p>
      </div>
    </div>

    <!-- 菜品详情对话框 -->
    <el-dialog
      v-model="showDetailDialog"
      :title="selectedDish?.name"
      width="600px"
    >
      <div v-if="selectedDish" class="space-y-4">
        <div class="h-64 bg-gradient-to-br from-red-200 via-orange-200 to-yellow-200 flex items-center justify-center rounded-lg overflow-hidden cursor-pointer" @click="previewImage(selectedDish.image_url)">
          <img
            v-if="selectedDish.image_url"
            :src="getImageUrl(selectedDish.image_url)"
            :alt="selectedDish.name"
            class="w-full h-full object-cover"
            @error="handleImageError"
          />
          <span v-else class="text-8xl">🍲</span>
        </div>
        <div>
          <h3 class="text-2xl font-bold mb-2">{{ selectedDish.name }}</h3>
          <p class="text-gray-600 mb-4">{{ selectedDish.description || '暂无描述' }}</p>
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <el-rate :model-value="Number(selectedDish.average_rating) || 0" disabled />
              <span class="text-gray-500 ml-2">({{ selectedDish.review_count }}条评价)</span>
            </div>
            <span class="text-3xl font-bold text-red-600">¥{{ selectedDish.price }}</span>
          </div>
          <div class="flex gap-4">
            <el-button type="primary" size="large" @click="addToCart(selectedDish)">
              加入购物车
            </el-button>
            <el-button size="large" @click="viewReviews(selectedDish.id)">
              查看评价
            </el-button>
          </div>
        </div>
      </div>
    </el-dialog>

    <!-- 图片预览对话框 -->
    <el-dialog
      v-model="showImagePreview"
      width="80%"
      :show-close="true"
      align-center
      class="image-preview-dialog"
    >
      <div class="flex justify-center items-center">
        <img
          :src="previewImageUrl"
          alt="菜品图片预览"
          class="max-w-full max-h-[80vh] object-contain"
          @error="handleImageError"
        />
      </div>
    </el-dialog>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import { Search } from '@element-plus/icons-vue';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { useCartStore } from '../../stores/cart';
import { dishApi, type Dish, type DishCategory } from '../../api/dish';

const router = useRouter();
const cartStore = useCartStore();
const dishes = ref<Dish[]>([]);
const categories = ref<DishCategory[]>([]);
const searchKeyword = ref('');
const selectedCategory = ref<number | null>(null);
const sortBy = ref('default');
const loading = ref(false);
const currentPage = ref(1);
const pageSize = ref(20);
const total = ref(0);
const showDetailDialog = ref(false);
const selectedDish = ref<Dish | null>(null);

// 排序和筛选已在后端API处理，这里不需要前端过滤

const viewDishDetail = (dish: Dish) => {
  selectedDish.value = dish;
  showDetailDialog.value = true;
};

const addToCart = (dish: Dish) => {
  if (dish.status !== 'available') {
    ElMessage.warning('该菜品暂不可用');
    return;
  }
  cartStore.addDish(dish, 1);
  ElMessage.success(`${dish.name} 已加入购物车`);
};

const viewReviews = (dishId: number) => {
  // 关闭详情对话框
  showDetailDialog.value = false;
  // 跳转到菜品评价页面
  router.push(`/frontend/dishes/${dishId}/reviews`).catch((err) => {
    console.error('路由跳转失败:', err);
    ElMessage.error('跳转失败，请重试');
  });
};

// 图片预览
const showImagePreview = ref(false);
const previewImageUrl = ref('');

const previewImage = (imageUrl: string | null | undefined) => {
  if (!imageUrl) {
    ElMessage.warning('该菜品暂无图片');
    return;
  }
  previewImageUrl.value = getImageUrl(imageUrl);
  showImagePreview.value = true;
};

// 处理图片URL，添加时间戳防止缓存
const getImageUrl = (url: string | null | undefined): string => {
  if (!url) return '';
  // 如果URL已经包含查询参数，添加&，否则添加?
  const separator = url.includes('?') ? '&' : '?';
  // 添加时间戳防止缓存，但只使用日期部分，这样同一天内的更新会被缓存
  const timestamp = new Date().toISOString().split('T')[0].replace(/-/g, '');
  return `${url}${separator}_t=${timestamp}`;
};

// 图片加载错误处理
const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement;
  // 如果图片加载失败，隐藏图片，显示默认占位符
  img.style.display = 'none';
};

const fetchDishes = async () => {
  loading.value = true;
  try {
    const response = await dishApi.getList({
      category_id: selectedCategory.value || undefined,
      search: searchKeyword.value || undefined,
      sort: sortBy.value as any,
      per_page: pageSize.value,
      page: currentPage.value,
    });
    if (response.code === 200 && response.data) {
      dishes.value = response.data.dishes || [];
      total.value = response.data.pagination?.total || 0;
    }
  } catch (error: any) {
    console.error('获取菜品列表失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取菜品列表失败');
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const response = await dishApi.getCategories();
    if (response.code === 200 && response.data) {
      categories.value = response.data.categories || [];
    }
  } catch (error: any) {
    console.error('获取分类列表失败:', error);
  }
};

// 监听筛选条件变化
watch([searchKeyword, selectedCategory, sortBy], () => {
  currentPage.value = 1;
  fetchDishes();
});

onMounted(() => {
  fetchDishes();
  fetchCategories();
});
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

:deep(.image-preview-dialog .el-dialog__body) {
  padding: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>

