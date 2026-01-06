<template>
  <FrontendLayout>
    <div class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- 页面标题 -->
      <div class="text-center mb-12">
        <h1 class="text-5xl font-bold text-gray-900 mb-4">💬 菜品评价</h1>
        <p class="text-xl text-gray-600">分享您的用餐体验</p>
      </div>

      <!-- 加载中 -->
      <div v-if="loading" class="text-center py-20">
        <el-icon class="is-loading text-4xl text-red-600"><Loading /></el-icon>
        <p class="mt-4 text-gray-600">加载中...</p>
      </div>

      <!-- 评价表单 -->
      <div v-else-if="order" class="bg-white rounded-2xl shadow-xl p-8 mb-8">
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-gray-900 mb-2">提交评价</h2>
          <p class="text-gray-600">订单号：{{ order.order_no }}</p>
        </div>
        
        <div class="space-y-6">
          <!-- 选择菜品（多选） -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">选择菜品（可多选）</label>
            <el-select 
              v-model="form.dish_ids" 
              placeholder="请选择要评价的菜品，可多选" 
              class="w-full"
              multiple
              collapse-tags
              collapse-tags-tooltip
            >
              <el-option
                v-for="dish in availableDishes"
                :key="dish.id"
                :label="dish.name"
                :value="dish.id"
                :disabled="dish.reviewed"
              >
                <span>{{ dish.name }}</span>
                <el-tag v-if="dish.reviewed" type="success" size="small" class="ml-2">已评价</el-tag>
              </el-option>
            </el-select>
            <p class="text-xs text-gray-500 mt-1">已评价的菜品将不再显示在列表中</p>
          </div>

          <!-- 评分 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">评分</label>
            <div class="flex items-center space-x-4">
              <el-rate v-model="form.rating" :max="5" size="large" />
              <span class="text-lg font-semibold text-gray-700">{{ form.rating }} 星</span>
            </div>
          </div>

          <!-- 评价内容 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">评价内容</label>
            <el-input
              v-model="form.content"
              type="textarea"
              :rows="5"
              placeholder="请分享您的用餐体验..."
              maxlength="500"
              show-word-limit
            />
          </div>

          <!-- 图片上传 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">上传图片（最多3张）</label>
            <el-upload
              v-model:file-list="fileList"
              :http-request="handleUpload"
              :before-upload="beforeUpload"
              list-type="picture-card"
              :limit="3"
              :on-preview="handlePreview"
              :on-remove="handleRemove"
              accept="image/*"
            >
              <el-icon><Plus /></el-icon>
            </el-upload>
          </div>

          <!-- 标签 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">标签（可选）</label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="tag in availableTags"
                :key="tag"
                @click="toggleTag(tag)"
                class="px-4 py-2 rounded-full text-sm transition-all"
                :class="form.tags.includes(tag)
                  ? 'bg-red-500 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
              >
                {{ tag }}
              </button>
            </div>
          </div>

          <!-- 提交按钮 -->
          <el-button
            @click="submitReview"
            :disabled="!canSubmit || loading"
            :loading="loading"
            type="primary"
            size="large"
            class="w-full"
          >
            提交评价
          </el-button>
        </div>
      </div>

      <!-- 我的评价 -->
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">我的评价</h2>
        <div class="space-y-4">
          <div
            v-for="review in myReviews"
            :key="review.id"
            class="border-2 border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-all"
          >
            <div class="flex items-start justify-between mb-3">
              <div class="flex-1">
                <h3 class="font-semibold text-gray-900 mb-2">{{ review.dish?.name }}</h3>
                <el-rate v-model="review.rating" disabled size="small" />
              </div>
              <el-tag
                :type="review.status === 'approved' ? 'success' : review.status === 'rejected' ? 'danger' : 'warning'"
              >
                {{ review.status === 'approved' ? '已通过' : review.status === 'rejected' ? '已拒绝' : '待审核' }}
              </el-tag>
            </div>
            <p class="text-gray-700 mb-2">{{ review.content }}</p>
            <p class="text-sm text-gray-500">{{ formatDateTime(review.created_at) }}</p>
          </div>
        </div>
      </div>
    </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import { Plus, Loading } from '@element-plus/icons-vue';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { orderApi, type Order } from '../../api/order';
import { reviewApi, type Review } from '../../api/review';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const order = ref<Order | null>(null);
const form = ref({
  order_id: 0,
  dish_ids: [] as number[],
  rating: 5,
  content: '',
  images: [] as string[],
  tags: [] as string[],
});

const fileList = ref<any[]>([]);
const dishes = ref<Array<{ id: number; name: string }>>([]);
const myReviews = ref<Review[]>([]);

const availableTags = ['好吃', '分量足', '新鲜', '服务好', '环境好', '性价比高'];

const canSubmit = computed(() => {
  return form.value.dish_ids.length > 0 && form.value.rating > 0 && form.value.content.trim().length > 0;
});

// 计算可选择的菜品列表（排除已评价的）
const availableDishes = computed(() => {
  if (!dishes.value || !order.value) return [];
  
  const reviewedDishIds = new Set(
    myReviews.value.map((review: Review) => review.dish_id)
  );
  
  return dishes.value.map(dish => ({
    ...dish,
    reviewed: reviewedDishIds.has(dish.id),
  }));
});

const toggleTag = (tag: string) => {
  const index = form.value.tags.indexOf(tag);
  if (index > -1) {
    form.value.tags.splice(index, 1);
  } else {
    if (form.value.tags.length < 5) {
      form.value.tags.push(tag);
    } else {
      ElMessage.warning('最多选择5个标签');
    }
  }
};

const handlePreview = (file: any) => {
  console.log('预览图片:', file);
};

const handleRemove = (file: any) => {
  const index = fileList.value.findIndex(f => f.uid === file.uid);
  if (index > -1) {
    fileList.value.splice(index, 1);
    // 同时从 form.value.images 中移除
    if (file.url) {
      const imageIndex = form.value.images.indexOf(file.url);
      if (imageIndex > -1) {
        form.value.images.splice(imageIndex, 1);
      }
    }
  }
};

const beforeUpload = (file: File) => {
  const isValidType = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif'].includes(file.type);
  const isLt5M = file.size / 1024 / 1024 < 5;

  if (!isValidType) {
    ElMessage.error('只能上传图片格式文件（jpg、png、gif、webp、avif）');
    return false;
  }
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB');
    return false;
  }
  return true;
};

const handleUpload = async (options: any) => {
  const { file } = options;
  
  try {
    const formData = new FormData();
    formData.append('image', file);
    
    const token = localStorage.getItem('token');
    
    if (!token) {
      ElMessage.error('请先登录');
      // 移除失败的文件
      const index = fileList.value.findIndex(f => f.uid === file.uid);
      if (index > -1) {
        fileList.value.splice(index, 1);
      }
      return;
    }
    
    // 使用原生 fetch API 上传文件
    const response = await fetch('/api/v1/upload/image', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        // 不要手动设置 Content-Type，让浏览器自动设置（包含 boundary）
      },
      body: formData,
      credentials: 'include',
    });
    
    if (!response.ok) {
      const errorText = await response.text();
      let errorData;
      try {
        errorData = JSON.parse(errorText);
      } catch {
        errorData = { message: errorText || '上传失败' };
      }
      throw new Error(errorData.message || `上传失败: ${response.status}`);
    }
    
    const result = await response.json();
    
    if (result && result.code === 200 && result.data) {
      // 后端现在返回完整的URL，直接使用即可
      const imageUrl = result.data.url;
      
      // 添加到 form.value.images
      if (!form.value.images.includes(imageUrl)) {
        form.value.images.push(imageUrl);
      }
      
      // 更新 fileList 中的 URL
      const fileItem = fileList.value.find(f => f.uid === file.uid);
      if (fileItem) {
        fileItem.url = imageUrl;
        fileItem.status = 'success';
      }
      
      ElMessage.success('图片上传成功');
    } else {
      ElMessage.error(result?.message || '上传失败');
      // 移除失败的文件
      const index = fileList.value.findIndex(f => f.uid === file.uid);
      if (index > -1) {
        fileList.value.splice(index, 1);
      }
    }
  } catch (error: any) {
    console.error('上传失败:', error);
    ElMessage.error(error.message || '图片上传失败，请重试');
    // 移除失败的文件
    const index = fileList.value.findIndex(f => f.uid === file.uid);
    if (index > -1) {
      fileList.value.splice(index, 1);
    }
  }
};

const formatDateTime = (datetime: string) => {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('zh-CN');
};

const submitReview = async () => {
  if (!canSubmit.value) {
    ElMessage.warning('请填写完整信息');
    return;
  }

  if (!form.value.order_id) {
    ElMessage.error('订单ID无效');
    return;
  }

  try {
    loading.value = true;
    
    // 批量提交评价（为每个选中的菜品创建一条评价）
    const promises = form.value.dish_ids.map(dishId => 
      reviewApi.create({
      order_id: form.value.order_id,
        dish_id: dishId,
      rating: form.value.rating,
      content: form.value.content.trim() || undefined,
      images: form.value.images.length > 0 ? form.value.images : undefined,
      tags: form.value.tags.length > 0 ? form.value.tags : undefined,
      })
    );

    const responses = await Promise.all(promises);
    const successCount = responses.filter(r => r.code === 201).length;
    const failCount = responses.length - successCount;
    
    if (successCount > 0) {
      ElMessage.success(`成功提交 ${successCount} 条评价！感谢您的反馈`);
      if (failCount > 0) {
        ElMessage.warning(`${failCount} 条评价提交失败，请重试`);
      }
      
      // 刷新订单详情和评价列表（使用静默模式，避免显示提示）
      await Promise.all([
        fetchOrderDetail(true), // 静默模式
        fetchMyReviews(),
      ]);
      
      // 检查订单状态，如果已完成或不再是待评价状态，跳转到订单详情页
      if (order.value && order.value.status !== 'pending_review') {
        // 延迟一下让用户看到成功消息
        setTimeout(() => {
          router.push(`/frontend/orders/${form.value.order_id}`);
        }, 1000);
        return;
      }
      
      // 如果订单还是待评价状态，检查是否所有菜品都已评价
      if (order.value) {
        const totalItems = order.value.items?.length || 0;
        const reviewedCount = myReviews.value.filter(r => r.order_id === order.value!.id).length;
        
        // 如果所有菜品都已评价，跳转到订单详情页（后端会自动完成订单）
        if (totalItems > 0 && reviewedCount >= totalItems) {
          setTimeout(() => {
            router.push(`/frontend/orders/${form.value.order_id}`);
          }, 1000);
          return;
        }
      }
      
      // 重置表单，准备评价下一个菜品
      form.value = {
        order_id: form.value.order_id,
        dish_ids: [],
        rating: 5,
        content: '',
        images: [],
        tags: [],
      };
      fileList.value = [];
    } else {
      ElMessage.error('所有评价提交失败，请重试');
    }
  } catch (error: any) {
    console.error('提交评价失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '提交失败，请重试');
  } finally {
    loading.value = false;
  }
};

const fetchOrderDetail = async (silent: boolean = false) => {
  const orderId = Number(route.params.orderId);
  if (isNaN(orderId) || orderId <= 0) {
    ElMessage.error('订单ID无效');
    router.push('/frontend/orders');
    return;
  }

  loading.value = true;
  try {
    const response = await orderApi.getDetail(orderId);
    if (response.code === 200 && response.data) {
      order.value = response.data;
      form.value.order_id = order.value.id;
      
      // 提取订单中的菜品列表
      if (order.value.items && order.value.items.length > 0) {
        dishes.value = order.value.items.map(item => ({
          id: item.dish_id,
          name: item.dish?.name || `菜品 ${item.dish_id}`,
        }));
      }
      
      // 获取已评价的菜品列表
      await fetchMyReviews();
      
      // 检查订单状态 - 只有待评价状态的订单才能评价
      if (order.value.status !== 'pending_review') {
        // 如果是静默模式（评价提交后），直接跳转不显示提示
        if (silent) {
          router.push(`/frontend/orders/${orderId}`);
          return;
        }
        ElMessage.warning('只有待评价的订单才能进行评价');
        router.push(`/frontend/orders/${orderId}`);
        return;
      }
    } else {
      ElMessage.error(response.message || '获取订单详情失败');
      router.push('/frontend/orders');
    }
  } catch (error: any) {
    console.error('获取订单详情失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取订单详情失败');
    router.push('/frontend/orders');
  } finally {
    loading.value = false;
  }
};

const fetchMyReviews = async () => {
  if (!order.value) return;
  
  try {
    // 获取该订单的所有评价
    const response = await reviewApi.getList({
      page: 1,
      page_size: 100,
    });
    
    if (response.code === 200 && response.data) {
      // 筛选出当前订单的评价
      myReviews.value = response.data.reviews.filter(
        (review: Review) => review.order_id === order.value!.id
      );
    }
  } catch (error) {
    console.error('获取我的评价失败:', error);
  }
};

onMounted(() => {
  fetchOrderDetail();
});
</script>

<style scoped>
:deep(.el-upload--picture-card) {
  width: 100px;
  height: 100px;
}
</style>

