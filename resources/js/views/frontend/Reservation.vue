<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
          <h1 class="text-5xl font-bold text-gray-900 mb-4">📅 在线预约</h1>
          <p class="text-xl text-gray-600">选择您心仪的桌位和时间</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- 左侧：预约表单 -->
          <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
              <h2 class="text-2xl font-bold text-gray-900 mb-6">预约信息</h2>
              
              <!-- 日期选择 -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">选择日期</label>
                <el-date-picker
                  v-model="form.date"
                  type="date"
                  placeholder="选择预约日期"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  class="w-full"
                  :disabled-date="disabledDate"
                  @change="handleDateOrTimeChange"
                />
              </div>

              <!-- 时间段选择 -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">选择时间段</label>
                <div class="grid grid-cols-3 gap-3">
                  <button
                    v-for="slot in timeSlots"
                    :key="slot"
                    @click="form.time_slot = slot; handleDateOrTimeChange()"
                    class="py-3 px-4 rounded-lg border-2 transition-all"
                    :class="form.time_slot === slot 
                      ? 'border-red-500 bg-red-50 text-red-600 font-semibold' 
                      : 'border-gray-200 hover:border-red-300 text-gray-700'"
                  >
                    {{ slot }}
                  </button>
                </div>
              </div>

              <!-- 人数选择 -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">用餐人数</label>
                <div class="flex items-center space-x-4">
                  <button
                    @click="form.guest_count = Math.max(1, form.guest_count - 1); handleDateOrTimeChange()"
                    class="w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center"
                  >
                    -
                  </button>
                  <span class="text-2xl font-bold text-gray-900 w-12 text-center">{{ form.guest_count }}</span>
                  <button
                    @click="form.guest_count = Math.min(20, form.guest_count + 1); handleDateOrTimeChange()"
                    class="w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center"
                  >
                    +
                  </button>
                  <span class="text-gray-600 ml-4">人</span>
                </div>
              </div>

              <!-- 联系人信息 -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">联系人姓名</label>
                <el-input v-model="form.contact_name" placeholder="请输入联系人姓名" />
              </div>

              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">联系电话</label>
                <el-input v-model="form.contact_phone" placeholder="请输入联系电话" />
              </div>

              <!-- 特殊需求 -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">特殊需求（选填）</label>
                <el-input
                  v-model="form.special_requests"
                  type="textarea"
                  :rows="3"
                  placeholder="如有特殊需求，请在此填写"
                />
              </div>

              <!-- 定金说明 -->
              <div v-if="depositEnabled && depositAmount > 0" class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                <div class="flex items-start">
                  <el-icon class="text-blue-500 mt-1 mr-2 text-xl"><InfoFilled /></el-icon>
                  <div class="flex-1">
                    <h4 class="font-semibold text-blue-900 mb-2">预约定金说明</h4>
                    <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                      <li>预约需要支付定金：<span class="font-bold text-red-600">¥{{ depositAmount }}</span></li>
                      <li>预约开始前{{ cancelHoursLimit }}小时内不可取消预约</li>
                      <li>在预约开始前{{ cancelHoursLimit }}小时外取消预约，定金将自动原路返还</li>
                      <li>超过预约时间{{ timeoutMinutes }}分钟未到达，定金不予退还</li>
                      <li>用餐完成后，定金将自动原路返还</li>
                    </ul>
                  </div>
                </div>
              </div>

              <button
                @click="submitReservation"
                :disabled="!canSubmit"
                class="w-full bg-gradient-to-r from-red-500 to-orange-500 text-white py-4 rounded-lg text-lg font-semibold hover:from-red-600 hover:to-orange-600 transition-all transform hover:scale-105 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
              >
                提交预约
              </button>
            </div>

            <!-- 桌位布局图 -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">桌位布局图</h2>
                <div class="flex space-x-2">
                  <button
                    v-for="type in tableTypes"
                    :key="type.value"
                    @click="selectedTableType = type.value"
                    class="px-4 py-2 rounded-lg text-sm transition-all"
                    :class="selectedTableType === type.value
                      ? 'bg-red-500 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                  >
                    {{ type.label }}
                  </button>
                </div>
              </div>
              
              <TableLayout
                v-if="tables.length > 0"
                :tables="filteredTablesForLayout"
                :selected-table-id="selectedTable?.id"
                :areas="areas"
                @table-selected="selectTable"
              />
              <div v-else class="text-center py-12 text-gray-500">
                <p class="text-lg mb-2">请先选择日期、时间段和用餐人数</p>
                <p class="text-sm">系统将为您显示可用的桌位</p>
              </div>
            </div>
          </div>

          <!-- 右侧：桌位列表（备用） -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-xl p-8 sticky top-24">
              <h2 class="text-2xl font-bold text-gray-900 mb-6">桌位列表</h2>
              
              <!-- 可用桌位列表 -->
              <div class="space-y-3 max-h-96 overflow-y-auto">
                <div
                  v-for="table in availableTables"
                  :key="table.id"
                  @click="selectTable(table)"
                  class="p-4 rounded-lg border-2 cursor-pointer transition-all hover:shadow-md"
                  :class="selectedTable?.id === table.id
                    ? 'border-red-500 bg-red-50'
                    : 'border-gray-200 hover:border-red-300'"
                >
                  <div class="flex justify-between items-center">
                    <div>
                      <h3 class="font-bold text-gray-900">{{ table.name }}</h3>
                      <p class="text-sm text-gray-600">{{ table.capacity }}人 · {{ getTypeText(table.type) }}</p>
                    </div>
                    <div class="w-3 h-3 rounded-full"
                         :class="table.status === 'available' ? 'bg-green-500' : 'bg-gray-400'">
                    </div>
                  </div>
                </div>
                <div v-if="availableTables.length === 0" class="text-center py-8 text-gray-500 text-sm">
                  暂无可用桌位
                </div>
              </div>

              <!-- 选中桌位信息 -->
              <div v-if="selectedTable" class="mt-6 p-4 bg-gradient-to-r from-red-50 to-orange-50 rounded-lg">
                <h3 class="font-bold text-gray-900 mb-2">已选择：{{ selectedTable.name }}</h3>
                <p class="text-sm text-gray-600">可容纳 {{ selectedTable.capacity }} 人</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import { InfoFilled } from '@element-plus/icons-vue';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import TableLayout from '../../components/TableLayout.vue';
import { reservationApi } from '../../api/reservation';
import { frontendConfigApi } from '../../api/frontend-config';
import type { Table } from '../../types';
import type { RestaurantArea } from '../../api/area';

const router = useRouter();

const form = ref({
  date: '',
  time_slot: '',
  guest_count: 4,
  contact_name: '',
  contact_phone: '',
  special_requests: '',
});

const selectedTable = ref<Table | null>(null);
const selectedTableType = ref('all');
const tables = ref<Table[]>([]);
const areas = ref<RestaurantArea[]>([]);
const loading = ref(false);

// 定金配置
const depositEnabled = ref(false);
const depositAmount = ref(0);
const timeoutMinutes = ref(30);
const cancelHoursLimit = ref(1);

const timeSlots = ['11:00', '12:00', '13:00', '17:00', '18:00', '19:00', '20:00', '21:00'];

const tableTypes = [
  { label: '全部', value: 'all' },
  { label: '窗边', value: 'window' },
  { label: '角落', value: 'corner' },
  { label: '中央', value: 'center' },
];

const disabledDate = (date: Date) => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return date < today;
};

const getTypeText = (type: string) => {
  const texts: Record<string, string> = {
    window: '窗边',
    corner: '角落',
    center: '中央',
  };
  return texts[type] || type;
};

// 处理位置数据：统一使用position_x和position_y
const normalizeTablePosition = (table: Table): Table => {
  if (table.position && (table.position.x !== undefined || table.position.y !== undefined)) {
    return {
      ...table,
      position_x: table.position.x ?? table.position_x,
      position_y: table.position.y ?? table.position_y,
    };
  }
  return table;
};

const availableTables = computed(() => {
  let filtered = tables.value.filter(t => t.status === 'available' && t.capacity >= form.value.guest_count);
  if (selectedTableType.value !== 'all') {
    filtered = filtered.filter(t => t.type === selectedTableType.value);
  }
  return filtered;
});

// 用于布局图的表格（包含所有状态，但根据筛选条件高亮）
const filteredTablesForLayout = computed(() => {
  let filtered = tables.value;
  if (selectedTableType.value !== 'all') {
    filtered = filtered.filter(t => t.type === selectedTableType.value);
  }
  // 统一位置数据格式
  return filtered.map(normalizeTablePosition);
});

const canSubmit = computed(() => {
  return form.value.date && 
         form.value.time_slot && 
         form.value.contact_name && 
         form.value.contact_phone && 
         selectedTable.value;
});

const selectTable = (table: Table) => {
  if (table.status !== 'available') {
    ElMessage.warning('该桌位当前不可用，请选择其他桌位');
    return;
  }
  selectedTable.value = normalizeTablePosition(table);
  form.value.guest_count = Math.min(form.value.guest_count, table.capacity);
};

const handleDateOrTimeChange = async () => {
  if (form.value.date && form.value.time_slot) {
    await fetchTables();
  }
};

const fetchTables = async () => {
  if (!form.value.date || !form.value.time_slot) {
    tables.value = [];
    selectedTable.value = null;
    return;
  }

  loading.value = true;
  try {
    const response = await reservationApi.getAvailableTables({
      date: form.value.date,
      time_slot: form.value.time_slot,
      guest_count: form.value.guest_count,
      duration: 120,
    });

    // 保存区域配置
    if (response.data && response.data.areas) {
      areas.value = response.data.areas;
    } else {
      areas.value = [];
    }

    // 处理返回的数据，统一位置格式
    tables.value = (response.data?.tables || []).map((table: any) => {
      const normalized: Table = {
        id: table.id,
        name: table.name,
        capacity: table.capacity,
        type: table.type,
        status: table.status,
        position_x: table.position?.x ?? table.position_x,
        position_y: table.position?.y ?? table.position_y,
      };
      return normalized;
    });

    // 如果当前选中的桌位不在新列表中，清除选择
    if (selectedTable.value && !tables.value.find(t => t.id === selectedTable.value!.id)) {
      selectedTable.value = null;
    }
  } catch (error: any) {
    console.error('获取桌位列表失败:', error);
    ElMessage.error(error.response?.data?.message || '获取桌位列表失败，请稍后重试');
    tables.value = [];
    areas.value = [];
  } finally {
    loading.value = false;
  }
};

// 加载配置
const loadConfig = async () => {
  try {
    const [depositEnabledRes, depositAmountRes, timeoutRes, cancelLimitRes] = await Promise.all([
      frontendConfigApi.getPublicConfig('reservation_deposit_enabled'),
      frontendConfigApi.getPublicConfig('reservation_deposit_amount'),
      frontendConfigApi.getPublicConfig('reservation_timeout_minutes'),
      frontendConfigApi.getPublicConfig('reservation_cancel_hours_limit'),
    ]);

    depositEnabled.value = depositEnabledRes.data?.value === '1' || depositEnabledRes.data?.value === true || depositEnabledRes.data?.value === 1;
    depositAmount.value = Number(depositAmountRes.data?.value || 0);
    timeoutMinutes.value = Number(timeoutRes.data?.value || 30);
    cancelHoursLimit.value = Number(cancelLimitRes.data?.value || 1);
  } catch (error) {
    console.error('加载配置失败:', error);
    // 使用默认值
    depositEnabled.value = true;
    depositAmount.value = 50;
    timeoutMinutes.value = 30;
    cancelHoursLimit.value = 1;
  }
};

const submitReservation = async () => {
  if (!canSubmit.value) {
    ElMessage.warning('请填写完整信息并选择桌位');
    return;
  }

  if (!selectedTable.value) {
    ElMessage.warning('请先选择桌位');
    return;
  }

  // 如果启用了定金，提示用户
  let confirmMessage = '确认提交预约吗？';
  if (depositEnabled.value && depositAmount.value > 0) {
    confirmMessage = `确认提交预约吗？\n\n预约需要支付定金 ¥${depositAmount.value}，预约开始前${cancelHoursLimit.value}小时内不可取消预约。`;
  }

  try {
    await ElMessageBox.confirm(confirmMessage, '提示', {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'info',
    });

    loading.value = true;
    
    // 调用实际API提交预约
    const response = await reservationApi.create({
      table_id: selectedTable.value.id,
      date: form.value.date,
      time_slot: form.value.time_slot,
      guest_count: form.value.guest_count,
      contact_name: form.value.contact_name,
      contact_phone: form.value.contact_phone,
      special_requests: form.value.special_requests || undefined,
    });

    if (response.code === 201 && response.data) {
      const depositAmount = response.data.deposit_amount || 0;
      if (depositAmount > 0) {
        ElMessage.success(`预约提交成功！预约编号：${response.data.reservation_code}，请支付定金 ¥${depositAmount}`);
        // 跳转到预约详情页面支付定金
        router.push(`/frontend/reservations/${response.data.reservation_id}`);
      } else {
        ElMessage.success(`预约提交成功！预约编号：${response.data.reservation_code}，我们会在15分钟内与您确认`);
      }
      
      // 重置表单
      form.value = {
        date: '',
        time_slot: '',
        guest_count: 4,
        contact_name: '',
        contact_phone: '',
        special_requests: '',
      };
      selectedTable.value = null;
      tables.value = [];
      areas.value = [];
    } else {
      ElMessage.error(response.message || '预约提交失败，请重试');
    }
  } catch (error: any) {
    // 用户取消操作时不显示错误
    if (error !== 'cancel' && error !== 'close') {
      console.error('提交预约失败:', error);
      const message = error.response?.data?.message || error.message || '预约提交失败，请重试';
      ElMessage.error(message);
    }
  } finally {
    loading.value = false;
  }
};

// 监听人数变化，重新获取桌位
watch(() => form.value.guest_count, () => {
  if (form.value.date && form.value.time_slot) {
    fetchTables();
  }
});

onMounted(async () => {
  // 加载配置
  await loadConfig();
  
  // 设置默认日期为明天
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  form.value.date = tomorrow.toISOString().slice(0, 10);
  // 设置默认时间段
  form.value.time_slot = '18:00';
  // 自动加载桌位数据
  fetchTables();
});
</script>

<style scoped>
/* 自定义滚动条 */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>

