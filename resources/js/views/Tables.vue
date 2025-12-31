<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">桌位管理</h1>
          <p class="text-gray-600">管理和查看餐厅桌位状态，自定义布局位置</p>
        </div>
        <el-button type="primary" size="large" @click="showAddDialog = true">
          <el-icon><Plus /></el-icon>
          添加桌位
        </el-button>
      </div>

      <!-- 标签页切换 -->
      <el-tabs v-model="activeTab" class="mb-6">
        <el-tab-pane label="列表视图" name="list">
          <div class="mt-4">

            <!-- 状态统计 -->
            <div class="grid grid-cols-4 gap-4 mb-6">
              <div class="bg-green-50 rounded-lg p-4 border-l-4 border-green-500">
                <p class="text-sm text-gray-600">可用</p>
                <p class="text-2xl font-bold text-green-600">{{ stats.available }}</p>
              </div>
              <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
                <p class="text-sm text-gray-600">已预约</p>
                <p class="text-2xl font-bold text-blue-600">{{ stats.reserved }}</p>
              </div>
              <div class="bg-orange-50 rounded-lg p-4 border-l-4 border-orange-500">
                <p class="text-sm text-gray-600">使用中</p>
                <p class="text-2xl font-bold text-orange-600">{{ stats.occupied }}</p>
              </div>
              <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-gray-500">
                <p class="text-sm text-gray-600">维护中</p>
                <p class="text-2xl font-bold text-gray-600">{{ stats.maintenance }}</p>
              </div>
            </div>

            <!-- 桌位网格 -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
              <div
                v-for="table in tables"
                :key="table.id"
                class="bg-white rounded-lg p-4 shadow-md transform transition-all duration-300 hover:scale-105 hover:shadow-xl cursor-pointer"
                :class="getTableClass(table.status)"
                @click="viewTable(table)"
              >
                <div class="text-center">
                  <div class="text-3xl mb-2">🪑</div>
                  <h3 class="font-bold text-lg mb-1">{{ table.name }}</h3>
                  <p class="text-sm text-gray-600 mb-2">{{ table.capacity }}人</p>
                  <el-tag :type="getStatusTagType(table.status)" size="small" class="mb-2">
                    {{ getStatusText(table.status) }}
                  </el-tag>
                  <!-- 使用中时显示使用时间 -->
                  <div v-if="table.status === 'occupied' && table.occupied_at" class="mt-2 text-xs text-gray-500">
                    <div>开始：{{ formatTime(table.occupied_at) }}</div>
                    <div class="text-orange-600 font-semibold">已用：{{ getElapsedTime(table.occupied_at) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </el-tab-pane>
        <el-tab-pane label="布局编辑器" name="editor">
          <div class="mt-4">
            <TableLayoutEditor
              :tables="tables"
              @positions-updated="handlePositionsUpdated"
            />
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>

    <!-- 添加桌位对话框 -->
    <el-dialog v-model="showAddDialog" title="添加桌位" width="500px" @close="resetAddForm">
      <el-form :model="addForm" :rules="addFormRules" ref="addFormRef" label-width="100px">
        <el-form-item label="桌位名称" prop="name">
          <el-input v-model="addForm.name" placeholder="请输入桌位名称，如：A06" />
        </el-form-item>
        <el-form-item label="容纳人数" prop="capacity">
          <el-input-number v-model="addForm.capacity" :min="1" :max="20" />
        </el-form-item>
        <el-form-item label="桌位类型" prop="type">
          <el-select v-model="addForm.type" placeholder="选择桌位类型">
            <el-option label="窗边" value="window" />
            <el-option label="角落" value="corner" />
            <el-option label="中央" value="center" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-select v-model="addForm.status" placeholder="选择状态">
            <el-option label="可用" value="available" />
            <el-option label="已预约" value="reserved" />
            <el-option label="使用中" value="occupied" />
            <el-option label="维护中" value="maintenance" />
          </el-select>
        </el-form-item>
        <el-form-item label="X坐标">
          <el-input-number v-model="addForm.position_x" :min="0" :max="800" placeholder="可选，用于布局图定位" />
        </el-form-item>
        <el-form-item label="Y坐标">
          <el-input-number v-model="addForm.position_y" :min="0" :max="500" placeholder="可选，用于布局图定位" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAddDialog = false">取消</el-button>
        <el-button type="primary" @click="handleAddTable" :loading="adding">确定</el-button>
      </template>
    </el-dialog>

    <!-- 桌位详情对话框 -->
    <el-dialog v-model="showTableDialog" title="桌位详情" width="600px" @close="resetTableForm">
      <div v-if="selectedTable" class="space-y-4">
        <!-- 基本信息编辑 -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold text-gray-800">基本信息</h3>
          
          <el-form :model="tableForm" :rules="tableFormRules" ref="tableFormRef" label-width="100px">
            <el-form-item label="桌位名称" prop="name">
              <el-input v-model="tableForm.name" placeholder="请输入桌位名称，如：A06" maxlength="20" show-word-limit />
            </el-form-item>
            <el-form-item label="容纳人数" prop="capacity">
              <el-input-number v-model="tableForm.capacity" :min="1" :max="20" placeholder="1-20人" class="w-full" />
            </el-form-item>
            <el-form-item label="桌位类型">
              <span class="font-semibold">{{ getTypeText(selectedTable.type) }}</span>
              <span class="text-sm text-gray-500 ml-2">（类型请在布局编辑器中修改）</span>
            </el-form-item>
            <el-form-item label="状态">
              <el-select v-model="tableForm.status" style="width: 150px">
                <el-option label="可用" value="available" />
                <el-option label="已预约" value="reserved" />
                <el-option label="使用中" value="occupied" />
                <el-option label="维护中" value="maintenance" />
              </el-select>
            </el-form-item>
            <!-- 使用中时显示使用时间信息 -->
            <el-form-item v-if="selectedTable.status === 'occupied' && selectedTable.occupied_at" label="使用时间">
              <div class="space-y-1">
                <div class="text-sm text-gray-600">开始时间：{{ formatDateTime(selectedTable.occupied_at) }}</div>
                <div class="text-sm text-orange-600 font-semibold">已使用时长：{{ getElapsedTime(selectedTable.occupied_at) }}</div>
              </div>
            </el-form-item>
          </el-form>
          
          <div class="flex justify-between items-center">
            <el-button type="danger" @click="handleDeleteTable" :loading="deletingTable">
              <el-icon><Delete /></el-icon>
              删除桌位
            </el-button>
            <div class="flex gap-2">
              <el-button @click="resetTableForm">取消</el-button>
              <el-button type="primary" @click="saveTableInfo" :loading="savingTableInfo">
                保存修改
              </el-button>
            </div>
          </div>
        </div>
        
        <el-divider />
        
        <div class="space-y-3">
          <h3 class="text-lg font-semibold text-gray-800">默认位置设置</h3>
          <p class="text-sm text-gray-500">设置桌位的默认位置，点击"重置位置"时会恢复到此处设置的位置</p>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-600 mb-1">默认X坐标</label>
              <el-input-number
                v-model="defaultPositionForm.default_position_x"
                :min="0"
                :max="800"
                :precision="0"
                placeholder="0-800"
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-sm text-gray-600 mb-1">默认Y坐标</label>
              <el-input-number
                v-model="defaultPositionForm.default_position_y"
                :min="0"
                :max="500"
                :precision="0"
                placeholder="0-500"
                class="w-full"
              />
            </div>
          </div>
          <div class="flex justify-between items-center">
            <el-button type="danger" @click="clearDefaultPosition" :disabled="!defaultPositionForm.default_position_x && !defaultPositionForm.default_position_y">
              清除默认位置
            </el-button>
            <div class="flex gap-2">
              <el-button @click="resetDefaultPositionForm">取消</el-button>
              <el-button type="primary" @click="saveDefaultPosition" :loading="savingDefaultPosition">
                保存默认位置
              </el-button>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus';
import { Plus, Delete } from '@element-plus/icons-vue';
import type { Table } from '../types';
import { tableApi, type CreateTableRequest } from '../api/table';
import TableLayoutEditor from '../components/TableLayoutEditor.vue';

const tables = ref<Table[]>([]);
const selectedTable = ref<Table | null>(null);
const showTableDialog = ref(false);
const showAddDialog = ref(false);
const activeTab = ref('list');
const adding = ref(false);
const savingDefaultPosition = ref(false);
const savingTableInfo = ref(false);
const deletingTable = ref(false);
const addFormRef = ref<FormInstance | null>(null);
const tableFormRef = ref<FormInstance | null>(null);

const tableForm = ref({
  name: '',
  capacity: 4,
  status: 'available' as 'available' | 'reserved' | 'occupied' | 'maintenance',
});

const tableFormRules: FormRules = {
  name: [
    { required: true, message: '请输入桌位名称', trigger: 'blur' },
    { max: 20, message: '桌位名称不能超过20个字符', trigger: 'blur' },
  ],
  capacity: [
    { required: true, message: '请输入容纳人数', trigger: 'blur' },
    { type: 'number', min: 1, max: 20, message: '容纳人数必须在1-20之间', trigger: 'blur' },
  ],
};

const defaultPositionForm = ref({
  default_position_x: null as number | null,
  default_position_y: null as number | null,
});

const addForm = ref<CreateTableRequest>({
  name: '',
  capacity: 4,
  type: 'center',
  status: 'available',
  position_x: null,
  position_y: null,
});

const addFormRules: FormRules = {
  name: [
    { required: true, message: '请输入桌位名称', trigger: 'blur' },
    { max: 20, message: '桌位名称不能超过20个字符', trigger: 'blur' },
  ],
  capacity: [
    { required: true, message: '请输入容纳人数', trigger: 'blur' },
    { type: 'number', min: 1, max: 20, message: '容纳人数必须在1-20之间', trigger: 'blur' },
  ],
  type: [
    { required: true, message: '请选择桌位类型', trigger: 'change' },
  ],
};

const stats = ref({
  available: 0,
  reserved: 0,
  occupied: 0,
  maintenance: 0,
});

const getTableClass = (status: string) => {
  const classes: Record<string, string> = {
    available: 'border-2 border-green-300',
    reserved: 'border-2 border-blue-300',
    occupied: 'border-2 border-orange-300',
    maintenance: 'border-2 border-gray-300 opacity-60',
  };
  return classes[status] || '';
};

const getStatusTagType = (status: string) => {
  const types: Record<string, string> = {
    available: 'success',
    reserved: 'primary',
    occupied: 'warning',
    maintenance: 'info',
  };
  return types[status] || '';
};

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    available: '可用',
    reserved: '已预约',
    occupied: '使用中',
    maintenance: '维护中',
  };
  return texts[status] || status;
};

const getTypeText = (type: string) => {
  const texts: Record<string, string> = {
    window: '窗边',
    corner: '角落',
    center: '中央',
  };
  return texts[type] || type;
};

// 格式化时间（仅时间部分）
const formatTime = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' });
};

// 格式化日期时间
const formatDateTime = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleString('zh-CN', { 
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
};

// 计算已使用时长
const getElapsedTime = (dateString: string) => {
  const startTime = new Date(dateString).getTime();
  const now = Date.now();
  const diff = now - startTime;
  
  const hours = Math.floor(diff / (1000 * 60 * 60));
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  const seconds = Math.floor((diff % (1000 * 60)) / 1000);
  
  if (hours > 0) {
    return `${hours}小时${minutes}分钟`;
  } else if (minutes > 0) {
    return `${minutes}分钟${seconds}秒`;
  } else {
    return `${seconds}秒`;
  }
};

const viewTable = (table: Table) => {
  selectedTable.value = table;
  // 初始化基本信息表单
  tableForm.value = {
    name: table.name,
    capacity: table.capacity,
    status: table.status,
  };
  // 初始化默认位置表单
  defaultPositionForm.value = {
    default_position_x: table.default_position_x ?? null,
    default_position_y: table.default_position_y ?? null,
  };
  showTableDialog.value = true;
};

const resetTableForm = () => {
  if (selectedTable.value) {
    // 重置基本信息表单
    tableForm.value = {
      name: selectedTable.value.name,
      capacity: selectedTable.value.capacity,
      status: selectedTable.value.status,
    };
    // 重置默认位置表单
    defaultPositionForm.value = {
      default_position_x: selectedTable.value.default_position_x ?? null,
      default_position_y: selectedTable.value.default_position_y ?? null,
    };
  }
  tableFormRef.value?.clearValidate();
};

const resetDefaultPositionForm = () => {
  if (selectedTable.value) {
    defaultPositionForm.value = {
      default_position_x: selectedTable.value.default_position_x ?? null,
      default_position_y: selectedTable.value.default_position_y ?? null,
    };
  }
};

const saveDefaultPosition = async () => {
  if (!selectedTable.value) return;
  
  savingDefaultPosition.value = true;
  try {
    await tableApi.update(selectedTable.value.id, {
      default_position_x: defaultPositionForm.value.default_position_x,
      default_position_y: defaultPositionForm.value.default_position_y,
    });
    ElMessage.success('默认位置已保存');
    await fetchTables();
    // 更新当前选中的桌位数据
    if (selectedTable.value) {
      const updatedTable = tables.value.find(t => t.id === selectedTable.value!.id);
      if (updatedTable) {
        selectedTable.value.default_position_x = updatedTable.default_position_x;
        selectedTable.value.default_position_y = updatedTable.default_position_y;
      }
    }
  } catch (error: any) {
    console.error('保存默认位置失败:', error);
    const message = error.response?.data?.message || error.message || '保存默认位置失败，请重试';
    ElMessage.error(message);
  } finally {
    savingDefaultPosition.value = false;
  }
};

const clearDefaultPosition = async () => {
  if (!selectedTable.value) return;
  
  try {
    await ElMessageBox.confirm('确定要清除该桌位的默认位置吗？清除后重置位置将使用自动布局。', '确认清除', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });
    
    savingDefaultPosition.value = true;
    await tableApi.update(selectedTable.value.id, {
      default_position_x: null,
      default_position_y: null,
    });
    ElMessage.success('默认位置已清除');
    await fetchTables();
    // 更新表单
    defaultPositionForm.value = {
      default_position_x: null,
      default_position_y: null,
    };
    // 更新当前选中的桌位数据
    if (selectedTable.value) {
      selectedTable.value.default_position_x = null;
      selectedTable.value.default_position_y = null;
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('清除默认位置失败:', error);
      const message = error.response?.data?.message || error.message || '清除默认位置失败，请重试';
      ElMessage.error(message);
    }
  } finally {
    savingDefaultPosition.value = false;
  }
};

const saveTableInfo = async () => {
  if (!tableFormRef.value || !selectedTable.value) return;
  
  await tableFormRef.value.validate(async (valid) => {
    if (!valid) return;
    
    savingTableInfo.value = true;
    try {
      await tableApi.update(selectedTable.value!.id, {
        name: tableForm.value.name,
        capacity: tableForm.value.capacity,
        status: tableForm.value.status,
      });
      ElMessage.success('桌位信息已更新');
      await fetchTables();
      // 更新当前选中的桌位数据
      if (selectedTable.value) {
        const updatedTable = tables.value.find(t => t.id === selectedTable.value!.id);
        if (updatedTable) {
          selectedTable.value.name = updatedTable.name;
          selectedTable.value.capacity = updatedTable.capacity;
          selectedTable.value.status = updatedTable.status;
        }
      }
    } catch (error: any) {
      console.error('更新桌位信息失败:', error);
      const message = error.response?.data?.message || error.message || '更新桌位信息失败，请重试';
      ElMessage.error(message);
      // 恢复原数据
      await fetchTables();
      if (selectedTable.value) {
        const table = tables.value.find(t => t.id === selectedTable.value!.id);
        if (table) {
          selectedTable.value = table;
          resetTableForm();
        }
      }
    } finally {
      savingTableInfo.value = false;
    }
  });
};

const handleStatusChange = async (newStatus: string) => {
  if (!selectedTable.value) return;
  
  try {
    await tableApi.update(selectedTable.value.id, { status: newStatus });
    ElMessage.success('状态已更新');
    await fetchTables();
    // 更新当前选中的桌位状态
    if (selectedTable.value) {
      selectedTable.value.status = newStatus as any;
      tableForm.value.status = newStatus as any;
    }
  } catch (error: any) {
    console.error('更新状态失败:', error);
    const message = error.response?.data?.message || error.message || '更新状态失败，请重试';
    ElMessage.error(message);
    // 恢复原状态
    await fetchTables();
    if (selectedTable.value) {
      const table = tables.value.find(t => t.id === selectedTable.value!.id);
      if (table) {
        selectedTable.value.status = table.status;
        tableForm.value.status = table.status;
      }
    }
  }
};

const fetchTables = async () => {
  try {
    const response = await tableApi.getList();
    if (response.code === 200 && response.data) {
      tables.value = response.data.tables;
      
      stats.value = {
        available: tables.value.filter(t => t.status === 'available').length,
        reserved: tables.value.filter(t => t.status === 'reserved').length,
        occupied: tables.value.filter(t => t.status === 'occupied').length,
        maintenance: tables.value.filter(t => t.status === 'maintenance').length,
      };
    }
  } catch (error) {
    console.error('获取桌位列表失败:', error);
    ElMessage.error('获取桌位列表失败');
  }
};

const handlePositionsUpdated = () => {
  // 位置更新后重新获取数据
  fetchTables();
};

const handleDeleteTable = async () => {
  if (!selectedTable.value) return;
  
  try {
    await ElMessageBox.confirm(
      `确定要删除桌位"${selectedTable.value.name}"吗？此操作不可恢复。`,
      '确认删除',
      {
        confirmButtonText: '确定删除',
        cancelButtonText: '取消',
        type: 'warning',
        confirmButtonClass: 'el-button--danger',
      }
    );
    
    deletingTable.value = true;
    try {
      await tableApi.delete(selectedTable.value.id);
      ElMessage.success('桌位删除成功');
      showTableDialog.value = false;
      selectedTable.value = null;
      await fetchTables();
    } catch (error: any) {
      console.error('删除桌位失败:', error);
      const message = error.response?.data?.message || error.message || '删除桌位失败，请重试';
      ElMessage.error(message);
    } finally {
      deletingTable.value = false;
    }
  } catch (error) {
    // 用户取消删除
    if (error !== 'cancel') {
      console.error('删除确认失败:', error);
    }
  }
};

const resetAddForm = () => {
  addForm.value = {
    name: '',
    capacity: 4,
    type: 'center',
    status: 'available',
    position_x: null,
    position_y: null,
  };
  addFormRef.value?.resetFields();
};

const handleAddTable = async () => {
  if (!addFormRef.value) return;
  
  await addFormRef.value.validate(async (valid) => {
    if (!valid) return;
    
    adding.value = true;
    try {
      await tableApi.create(addForm.value);
      ElMessage.success('桌位添加成功');
      showAddDialog.value = false;
      resetAddForm();
      fetchTables();
    } catch (error: any) {
      console.error('添加桌位失败:', error);
      const message = error.response?.data?.message || error.message || '添加桌位失败，请重试';
      ElMessage.error(message);
    } finally {
      adding.value = false;
    }
  });
};

// 定时器：每5秒更新一次已使用时长显示
let elapsedTimeInterval: number | null = null;

onMounted(() => {
  fetchTables();
  
  // 启动定时器，每5秒刷新一次列表（更新已使用时长）
  elapsedTimeInterval = window.setInterval(() => {
    // 只刷新列表，不显示加载状态
    tableApi.getList().then(response => {
      if (response.code === 200 && response.data) {
        tables.value = response.data.tables;
        
        // 更新统计
        stats.value = {
          available: tables.value.filter(t => t.status === 'available').length,
          reserved: tables.value.filter(t => t.status === 'reserved').length,
          occupied: tables.value.filter(t => t.status === 'occupied').length,
          maintenance: tables.value.filter(t => t.status === 'maintenance').length,
        };
        
        // 如果当前有选中的桌位，更新其数据
        if (selectedTable.value) {
          const updatedTable = tables.value.find(t => t.id === selectedTable.value!.id);
          if (updatedTable) {
            selectedTable.value = updatedTable;
          }
        }
      }
    }).catch(() => {
      // 静默失败，不影响用户体验
    });
  }, 5000);
});

onUnmounted(() => {
  // 清理定时器
  if (elapsedTimeInterval !== null) {
    clearInterval(elapsedTimeInterval);
  }
});
</script>

<style scoped>
/* 动画效果 */
.grid > div {
  animation: fadeInUp 0.5s ease-out;
  animation-fill-mode: both;
}

.grid > div:nth-child(1) { animation-delay: 0.1s; }
.grid > div:nth-child(2) { animation-delay: 0.2s; }
.grid > div:nth-child(3) { animation-delay: 0.3s; }
.grid > div:nth-child(4) { animation-delay: 0.4s; }
.grid > div:nth-child(5) { animation-delay: 0.5s; }

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>

