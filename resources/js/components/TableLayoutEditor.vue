/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="table-layout-editor">
    <!-- 工具栏 -->
    <div class="mb-4 flex justify-between items-center flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <h3 class="text-lg font-bold text-gray-800">餐厅布局编辑器</h3>
        <el-radio-group v-model="editMode" size="small">
          <el-radio-button label="tables">编辑桌位</el-radio-button>
          <el-radio-button label="areas">编辑区域</el-radio-button>
        </el-radio-group>
      </div>
      <div class="flex items-center gap-2">
        <template v-if="editMode === 'tables'">
          <el-button type="primary" size="small" @click="savePositions" :loading="saving">
            保存位置
          </el-button>
          <el-button size="small" @click="resetPositions">重置</el-button>
        </template>
        <template v-else>
          <el-button type="success" size="small" @click="showAddAreaDialog = true">
            <el-icon><Plus /></el-icon>
            添加区域
          </el-button>
          <el-button type="primary" size="small" @click="saveAreas" :loading="savingAreas">
            保存区域
          </el-button>
          <el-button size="small" @click="resetAreas">重置区域</el-button>
        </template>
      </div>
      <div class="text-sm text-gray-600">
        <span v-if="editMode === 'tables'">提示：拖拽桌位圆圈来调整位置</span>
        <span v-else>提示：拖拽区域边界线来调整范围，点击区域可编辑</span>
      </div>
    </div>

    <!-- 餐厅平面图 -->
    <div class="relative bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-6 shadow-lg border-2 border-amber-200 w-full">
      <svg 
        ref="svgElement"
        :width="floorPlanWidth" 
        :height="floorPlanHeight" 
        :viewBox="`0 0 ${floorPlanWidth} ${floorPlanHeight}`"
        preserveAspectRatio="xMidYMid meet"
        class="w-full h-auto block"
        style="background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%); max-width: 100%;"
      >
          <!-- 定义渐变和图案 -->
          <defs>
            <!-- 地板纹理 -->
            <pattern id="editorFloorPattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
              <rect width="40" height="40" fill="#fef3c7" opacity="0.3"/>
              <path d="M 0 0 L 40 40 M 40 0 L 0 40" stroke="#fbbf24" stroke-width="0.5" opacity="0.2"/>
            </pattern>
            <!-- 窗户渐变 -->
            <linearGradient id="editorWindowGradient" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" style="stop-color:#93c5fd;stop-opacity:0.6" />
              <stop offset="100%" style="stop-color:#60a5fa;stop-opacity:0.8" />
            </linearGradient>
          </defs>

          <!-- 地板 -->
          <rect width="100%" height="100%" fill="url(#editorFloorPattern)" />

          <!-- 外墙 -->
          <rect x="0" y="0" :width="floorPlanWidth" :height="floorPlanHeight" fill="none" stroke="#92400e" stroke-width="8" rx="4"/>
          
          <!-- 自定义区域背景 -->
          <g v-for="area in editableAreas.filter(a => a && a.is_active)" :key="area.id">
            <rect
              v-if="area.boundaries && area.boundaries.x !== undefined && area.boundaries.y !== undefined && area.boundaries.width !== undefined && area.boundaries.height !== undefined"
              :x="area.boundaries.x"
              :y="area.boundaries.y"
              :width="area.boundaries.width"
              :height="area.boundaries.height"
              :fill="area.color || '#93c5fd'"
              :opacity="0.15"
              class="cursor-pointer"
              @click="editArea(area)"
            />
          </g>

          <!-- 可拖拽的区域边界线（垂直线） -->
          <g v-for="(boundary, index) in verticalBoundaries" :key="'v-' + index">
            <line
              :x1="boundary.x"
              :y1="0"
              :x2="boundary.x"
              :y2="floorPlanHeight"
              stroke="#92400e"
              :stroke-width="editMode === 'areas' ? 8 : 6"
              stroke-dasharray="5,5"
              :opacity="editMode === 'areas' ? 0.8 : 0.5"
              class="cursor-col-resize"
              :class="{ 'hover:stroke-blue-500': editMode === 'areas' }"
              @mousedown="startDragBoundary('vertical', boundary, $event)"
            />
            <!-- 拖拽手柄 -->
            <circle
              v-if="editMode === 'areas'"
              :cx="boundary.x"
              :cy="floorPlanHeight / 2"
              r="8"
              fill="#92400e"
              stroke="#fff"
              stroke-width="2"
              class="cursor-col-resize"
            />
          </g>

          <!-- 可拖拽的区域边界线（水平线） -->
          <g v-for="(boundary, index) in horizontalBoundaries" :key="'h-' + index">
            <line
              :x1="0"
              :y1="boundary.y"
              :x2="floorPlanWidth"
              :y2="boundary.y"
              stroke="#92400e"
              :stroke-width="editMode === 'areas' ? 8 : 6"
              stroke-dasharray="5,5"
              :opacity="editMode === 'areas' ? 0.8 : 0.5"
              class="cursor-row-resize"
              :class="{ 'hover:stroke-blue-500': editMode === 'areas' }"
              @mousedown="startDragBoundary('horizontal', boundary, $event)"
            />
            <!-- 拖拽手柄 -->
            <circle
              v-if="editMode === 'areas'"
              :cx="floorPlanWidth / 2"
              :cy="boundary.y"
              r="8"
              fill="#92400e"
              stroke="#fff"
              stroke-width="2"
              class="cursor-row-resize"
            />
          </g>

          <!-- 窗户装饰（左侧墙壁上的窗户） -->
          <g v-for="i in 6" :key="'window-' + i">
            <rect x="5" :y="60 + (i - 1) * 70" width="15" height="50" fill="#93c5fd" opacity="0.6" rx="1"/>
            <line x1="12.5" :y1="60 + (i - 1) * 70" :x2="12.5" :y2="110 + (i - 1) * 70" stroke="#1e40af" stroke-width="1.5"/>
            <line x1="5" :y1="85 + (i - 1) * 70" x2="20" :y2="85 + (i - 1) * 70" stroke="#1e40af" stroke-width="1.5"/>
          </g>

          <!-- 入口门（底部中央） -->
          <rect :x="floorPlanWidth / 2 - 60" :y="floorPlanHeight - 25" width="120" height="25" fill="#78350f" rx="3"/>
          <rect :x="floorPlanWidth / 2 - 55" :y="floorPlanHeight - 20" width="110" height="20" fill="#92400e" rx="2"/>
          <text :x="floorPlanWidth / 2" :y="floorPlanHeight - 8" text-anchor="middle" fill="#fbbf24" font-size="11" font-weight="bold">🚪 入口</text>

          <!-- 过道（中央横向） -->
          <rect x="200" :y="floorPlanHeight / 2 - 30" width="400" height="60" fill="#fef3c7" opacity="0.5" rx="4"/>
          <text :x="floorPlanWidth / 2" :y="floorPlanHeight / 2 + 5" text-anchor="middle" fill="#92400e" font-size="11" opacity="0.6">主过道</text>

          <!-- 区域标签 -->
          <g v-for="area in editableAreas.filter(a => a && a.is_active)" :key="'label-' + area.id">
            <text
              v-if="area.boundaries && area.boundaries.x !== undefined && area.boundaries.y !== undefined"
              :x="area.boundaries.x + (area.boundaries.width || 0) / 2"
              :y="(area.boundaries.y || 0) + 20"
              text-anchor="middle"
              :fill="area.color || '#78350f'"
              font-size="14"
              font-weight="bold"
              class="cursor-pointer"
              @click="editArea(area)"
            >
              {{ area.name }}
            </text>
          </g>

          <!-- 可拖拽的桌位 -->
          <g v-for="table in positionedTables" :key="table.id" v-if="editMode === 'tables'">
            <!-- 桌位圆圈 - 可拖拽 -->
            <circle
              :cx="table.x"
              :cy="table.y"
              :r="table.radius"
              :fill="getTableColor(table.status)"
              stroke="#78350f"
              stroke-width="2"
              class="cursor-move transition-all"
              :opacity="table.status === 'maintenance' ? 0.5 : 1"
              style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"
              @mousedown="startDrag(table, $event)"
            />
            
            <!-- 桌位名称 -->
            <text
              :x="table.x"
              :y="table.y - table.radius - 5"
              text-anchor="middle"
              fill="#78350f"
              font-size="12"
              font-weight="bold"
              class="pointer-events-none"
            >
              {{ table.name }}
            </text>
            
            <!-- 桌位人数 -->
            <text
              :x="table.x"
              :y="table.y + 4"
              text-anchor="middle"
              fill="#78350f"
              font-size="10"
              class="pointer-events-none"
            >
              {{ table.capacity }}人
            </text>

            <!-- 坐标显示（编辑模式） -->
            <text
              :x="table.x"
              :y="table.y + table.radius + 15"
              text-anchor="middle"
              fill="#92400e"
              font-size="9"
              class="pointer-events-none"
            >
              ({{ Math.round(table.x) }}, {{ Math.round(table.y) }})
            </text>

            <!-- 悬停提示 -->
            <title>{{ table.name }} - {{ table.capacity }}人 - {{ getStatusText(table.status) }} - {{ getTypeText(table.type) }}</title>
          </g>
        </svg>
    </div>

    <!-- 添加/编辑区域对话框 -->
    <el-dialog
      v-model="showAddAreaDialog"
      :title="editingArea ? '编辑区域' : '添加区域'"
      width="500px"
    >
      <el-form :model="areaForm" label-width="100px">
        <el-form-item label="区域名称">
          <el-input v-model="areaForm.name" placeholder="请输入区域名称" />
        </el-form-item>
        <el-form-item label="区域类型">
          <el-select v-model="areaForm.type" placeholder="选择区域类型">
            <el-option label="窗边" value="window" />
            <el-option label="角落" value="corner" />
            <el-option label="中央" value="center" />
            <el-option label="自定义" value="custom" />
          </el-select>
        </el-form-item>
        <el-form-item label="位置 X">
          <el-input-number v-model="areaForm.boundaries.x" :min="0" :max="floorPlanWidth" />
        </el-form-item>
        <el-form-item label="位置 Y">
          <el-input-number v-model="areaForm.boundaries.y" :min="0" :max="floorPlanHeight" />
        </el-form-item>
        <el-form-item label="宽度">
          <el-input-number v-model="areaForm.boundaries.width" :min="50" :max="floorPlanWidth" />
        </el-form-item>
        <el-form-item label="高度">
          <el-input-number v-model="areaForm.boundaries.height" :min="50" :max="floorPlanHeight" />
        </el-form-item>
        <el-form-item label="颜色">
          <el-color-picker v-model="areaForm.color" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAddAreaDialog = false">取消</el-button>
        <el-button type="danger" @click="deleteArea" v-if="editingArea">删除</el-button>
        <el-button type="primary" @click="saveArea">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus } from '@element-plus/icons-vue';
import type { Table } from '../types';
import { tableApi } from '../api/table';
import { areaApi, type RestaurantArea } from '../api/area';

interface Props {
  tables: Table[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'positions-updated': [];
}>();

// 使用响应式数组，允许修改
const editableTables = ref<Table[]>([]);

// 监听props变化，创建可编辑副本
// 只在数据真正变化时更新，避免保存后刷新时覆盖本地编辑
watch(() => props.tables, (newTables) => {
  // 如果 editableTables 为空或者是第一次加载，直接更新
  if (editableTables.value.length === 0) {
    editableTables.value = newTables.map(t => ({ ...t }));
    return;
  }
  
  // 否则，只更新那些在 props 中但不在 editableTables 中的新桌位
  // 或者更新那些在 props 中位置已改变但不在编辑中的桌位
  const existingIds = new Set(editableTables.value.map(t => t.id));
  const newIds = new Set(newTables.map(t => t.id));
  
  // 添加新桌位
  newTables.forEach(newTable => {
    if (!existingIds.has(newTable.id)) {
      editableTables.value.push({ ...newTable });
    }
  });
  
  // 更新已存在桌位的位置（如果 props 中的位置与 editableTables 中的不同，且 editableTables 中没有手动设置的位置）
  // 注意：保存过程中不要更新，避免覆盖正在保存的数据
  if (!saving.value) {
    editableTables.value.forEach(editableTable => {
      const newTable = newTables.find(t => t.id === editableTable.id);
      if (newTable) {
        // 如果 editableTables 中没有手动设置位置，则使用 props 中的位置
        if ((editableTable.position_x === null || editableTable.position_x === undefined) &&
            (newTable.position_x !== null && newTable.position_x !== undefined)) {
          editableTable.position_x = newTable.position_x;
          editableTable.position_y = newTable.position_y;
        }
        // 更新其他字段（除了位置）
        editableTable.name = newTable.name;
        editableTable.capacity = newTable.capacity;
        editableTable.status = newTable.status;
        // 类型只在没有手动设置位置时更新
        if (editableTable.position_x === null || editableTable.position_x === undefined) {
          editableTable.type = newTable.type;
        }
      }
    });
  }
  
  // 移除已删除的桌位
  editableTables.value = editableTables.value.filter(t => newIds.has(t.id));
}, { immediate: true, deep: false });

// 平面图尺寸
const floorPlanWidth = 800;
const floorPlanHeight = 500;

const editMode = ref<'tables' | 'areas'>('tables');
const saving = ref(false);
const savingAreas = ref(false);
const dragging = ref(false);
const draggedTable = ref<Table | null>(null);
const dragOffset = ref({ x: 0, y: 0 });
const svgElement = ref<SVGSVGElement | null>(null);

// 区域相关
const areas = ref<RestaurantArea[]>([]);
const editableAreas = ref<RestaurantArea[]>([]);
const showAddAreaDialog = ref(false);
const editingArea = ref<RestaurantArea | null>(null);
const areaForm = ref({
  name: '',
  type: 'custom',
  boundaries: {
    x: 0,
    y: 0,
    width: 200,
    height: 500,
  },
  color: '#93c5fd',
});

// 边界线拖拽
const draggingBoundary = ref<{ type: 'vertical' | 'horizontal'; index: number; area?: RestaurantArea } | null>(null);

// 桌位半径（根据容量调整）
const getTableRadius = (capacity: number): number => {
  return capacity <= 4 ? 20 : capacity <= 6 ? 24 : 28;
};

// 计算桌位的实际位置
const positionedTables = computed(() => {
  return editableTables.value.map(table => {
    let x = table.position_x ?? 0;
    let y = table.position_y ?? 0;
    
    // 如果没有坐标，根据原始类型自动布局（使用原始类型避免拖拽时影响其他桌位）
    if (!table.position_x || !table.position_y) {
      // 获取原始类型（从 props.tables）
      const originalTable = props.tables.find(t => t.id === table.id);
      const originalType = originalTable?.type || table.type;
      
      if (originalType === 'window') {
        // 使用原始类型计算索引，避免拖拽时影响其他桌位
        const windowIndex = props.tables.filter(t => t.type === 'window').findIndex(t => t.id === table.id);
        x = 100;
        y = 100 + windowIndex * 70;
      } else if (originalType === 'corner') {
        const cornerIndex = props.tables.filter(t => t.type === 'corner').findIndex(t => t.id === table.id);
        if (cornerIndex < 2) {
          x = 300;
          y = cornerIndex === 0 ? 100 : floorPlanHeight - 100;
        } else if (cornerIndex < 4) {
          x = 550;
          y = cornerIndex === 2 ? 100 : floorPlanHeight - 100;
        } else {
          x = 400;
          y = floorPlanHeight / 2;
        }
      } else {
        const centerIndex = props.tables.filter(t => t.type === 'center').findIndex(t => t.id === table.id);
        const cols = 5;
        const row = Math.floor(centerIndex / cols);
        const col = centerIndex % cols;
        x = 650 + col * 30;
        y = 100 + row * 80;
      }
    }
    
    return {
      ...table,
      x,
      y,
      radius: getTableRadius(table.capacity),
    };
  });
});

const getTableColor = (status: string): string => {
  switch (status) {
    case 'available':
      return '#10b981';
    case 'reserved':
      return '#3b82f6';
    case 'occupied':
      return '#f97316';
    case 'maintenance':
      return '#9ca3af';
    default:
      return '#6b7280';
  }
};

const getStatusText = (status: string): string => {
  const texts: Record<string, string> = {
    available: '可用',
    reserved: '已预约',
    occupied: '使用中',
    maintenance: '维护中',
  };
  return texts[status] || status;
};

const getTypeText = (type: string): string => {
  const texts: Record<string, string> = {
    window: '窗边',
    corner: '角落',
    center: '中央',
    custom: '自定义',
  };
  return texts[type] || type;
};

// 计算垂直边界线（从区域边界提取）
const verticalBoundaries = computed(() => {
  const boundaries: Array<{ x: number; area?: RestaurantArea }> = [];
  const xSet = new Set<number>();
  
  editableAreas.value.filter(a => a && a.is_active).forEach(area => {
    if (!area.boundaries || !area.boundaries.x) return;
    const x = area.boundaries.x;
    if (!xSet.has(x)) {
      xSet.add(x);
      boundaries.push({ x, area });
    }
    const rightX = (area.boundaries.x || 0) + (area.boundaries.width || 0);
    if (rightX < floorPlanWidth && !xSet.has(rightX)) {
      xSet.add(rightX);
      boundaries.push({ x: rightX, area });
    }
  });
  
  return boundaries.sort((a, b) => a.x - b.x);
});

// 计算水平边界线（从区域边界提取）
const horizontalBoundaries = computed(() => {
  const boundaries: Array<{ y: number; area?: RestaurantArea }> = [];
  const ySet = new Set<number>();
  
  editableAreas.value.filter(a => a && a.is_active).forEach(area => {
    if (!area.boundaries || !area.boundaries.y) return;
    const y = area.boundaries.y;
    if (!ySet.has(y)) {
      ySet.add(y);
      boundaries.push({ y, area });
    }
    const bottomY = (area.boundaries.y || 0) + (area.boundaries.height || 0);
    if (bottomY < floorPlanHeight && !ySet.has(bottomY)) {
      ySet.add(bottomY);
      boundaries.push({ y: bottomY, area });
    }
  });
  
  return boundaries.sort((a, b) => a.y - b.y);
});

// 边界线拖拽
const startDragBoundary = (type: 'vertical' | 'horizontal', boundary: any, event: MouseEvent) => {
  if (editMode.value !== 'areas') return;
  
  event.preventDefault();
  event.stopPropagation();
  
  draggingBoundary.value = {
    type,
    index: type === 'vertical' 
      ? verticalBoundaries.value.findIndex(b => b.x === boundary.x)
      : horizontalBoundaries.value.findIndex(b => b.y === boundary.y),
    area: boundary.area,
  };
};

// 区域编辑
const editArea = (area: RestaurantArea) => {
  editingArea.value = area;
  areaForm.value = {
    name: area.name,
    type: area.type,
    boundaries: { ...area.boundaries },
    color: area.color || '#93c5fd',
  };
  showAddAreaDialog.value = true;
};

// 保存区域
const saveArea = async () => {
  if (!areaForm.value.name) {
    ElMessage.warning('请输入区域名称');
    return;
  }
  
  try {
    if (editingArea.value) {
      await areaApi.update(editingArea.value.id, areaForm.value);
      ElMessage.success('区域更新成功');
    } else {
      await areaApi.create(areaForm.value);
      ElMessage.success('区域创建成功');
    }
    showAddAreaDialog.value = false;
    editingArea.value = null;
    await fetchAreas();
  } catch (error) {
    console.error('保存区域失败:', error);
    ElMessage.error('保存区域失败，请重试');
  }
};

// 删除区域
const deleteArea = async () => {
  if (!editingArea.value) return;
  
  try {
    await ElMessageBox.confirm('确定要删除这个区域吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });
    
    await areaApi.delete(editingArea.value.id);
    ElMessage.success('区域删除成功');
    showAddAreaDialog.value = false;
    editingArea.value = null;
    await fetchAreas();
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除区域失败:', error);
      ElMessage.error('删除区域失败，请重试');
    }
  }
};

// 保存所有区域
const saveAreas = async () => {
  savingAreas.value = true;
  try {
    const updates = editableAreas.value.map(area => ({
      id: area.id,
      name: area.name,
      type: area.type,
      boundaries: area.boundaries,
      color: area.color,
      sort_order: area.sort_order,
      is_active: area.is_active,
    }));
    
    await areaApi.updateBatch({ areas: updates });
    ElMessage.success('区域配置已保存');
    await fetchAreas();
  } catch (error) {
    console.error('保存区域失败:', error);
    ElMessage.error('保存区域失败，请重试');
  } finally {
    savingAreas.value = false;
  }
};

// 重置区域
const resetAreas = () => {
  editableAreas.value = areas.value.map(a => ({ ...a }));
  ElMessage.info('区域已重置');
};

// 获取区域列表
const fetchAreas = async () => {
  try {
    const response = await areaApi.getList();
    if (response.code === 200 && response.data && response.data.areas) {
      areas.value = response.data.areas.filter(a => a != null);
      editableAreas.value = areas.value.map(a => ({ ...a }));
    } else {
      areas.value = [];
      editableAreas.value = [];
    }
  } catch (error) {
    console.error('获取区域列表失败:', error);
    areas.value = [];
    editableAreas.value = [];
  }
};

// 初始化默认区域（如果没有区域数据）
const initDefaultAreas = async () => {
  if (areas.value.length === 0) {
    const defaultAreas = [
      {
        name: '窗边区域',
        type: 'window',
        boundaries: { x: 0, y: 0, width: 200, height: floorPlanHeight },
        color: '#93c5fd',
        sort_order: 1,
        is_active: true,
      },
      {
        name: '角落区域',
        type: 'corner',
        boundaries: { x: 200, y: 0, width: 400, height: floorPlanHeight },
        color: '#fbbf24',
        sort_order: 2,
        is_active: true,
      },
      {
        name: '中央区域',
        type: 'center',
        boundaries: { x: 600, y: 0, width: 200, height: floorPlanHeight },
        color: '#f97316',
        sort_order: 3,
        is_active: true,
      },
    ];
    
    try {
      for (const area of defaultAreas) {
        await areaApi.create(area);
      }
      await fetchAreas();
    } catch (error) {
      console.error('初始化默认区域失败:', error);
    }
  }
};

// 获取SVG坐标
const getSVGPoint = (event: MouseEvent): { x: number; y: number } | null => {
  if (!svgElement.value) return null;
  
  const svg = svgElement.value;
  const rect = svg.getBoundingClientRect();
  
  // 计算相对于SVG容器的坐标
  const x = event.clientX - rect.left;
  const y = event.clientY - rect.top;
  
  // 如果SVG有viewBox，需要考虑缩放
  if (svg.viewBox.baseVal.width > 0 && svg.viewBox.baseVal.height > 0) {
    const scaleX = svg.viewBox.baseVal.width / rect.width;
    const scaleY = svg.viewBox.baseVal.height / rect.height;
    return {
      x: x * scaleX,
      y: y * scaleY,
    };
  }
  
  // 没有viewBox，直接使用像素坐标
  return { x, y };
};

// 拖拽功能
const startDrag = (table: Table, event: MouseEvent) => {
  event.preventDefault();
  event.stopPropagation();
  
  dragging.value = true;
  draggedTable.value = table;
  
  const svgPoint = getSVGPoint(event);
  if (!svgPoint) return;
  
  const tableData = positionedTables.value.find(t => t.id === table.id);
  if (tableData) {
    // 计算鼠标点击位置相对于桌位中心的偏移
    dragOffset.value = {
      x: svgPoint.x - tableData.x,
      y: svgPoint.y - tableData.y,
    };
  }
};

const onMouseMove = (event: MouseEvent) => {
  const svgPoint = getSVGPoint(event);
  if (!svgPoint) return;
  
  // 桌位拖拽
  if (dragging.value && draggedTable.value && editMode.value === 'tables') {
    event.preventDefault();
    
    // 计算新位置（鼠标位置减去偏移量）
    const newX = Math.max(0, Math.min(floorPlanWidth, svgPoint.x - dragOffset.value.x));
    const newY = Math.max(0, Math.min(floorPlanHeight, svgPoint.y - dragOffset.value.y));
    
    // 更新桌位位置
    const tableIndex = editableTables.value.findIndex(t => t.id === draggedTable.value!.id);
    if (tableIndex !== -1) {
      editableTables.value[tableIndex].position_x = Math.round(newX);
      editableTables.value[tableIndex].position_y = Math.round(newY);
      // 实时更新类型（根据新位置）
      const newType = getTableTypeByPosition(newX, newY);
      editableTables.value[tableIndex].type = newType;
    }
    return;
  }
  
  // 边界线拖拽
  if (draggingBoundary.value && editMode.value === 'areas') {
    event.preventDefault();
    
    const boundary = draggingBoundary.value;
    
    if (boundary.type === 'vertical') {
      const newX = Math.max(0, Math.min(floorPlanWidth, svgPoint.x));
      const boundaryLine = verticalBoundaries.value[boundary.index];
      
      // 更新相关区域的边界
      if (boundaryLine.area) {
        const areaIndex = editableAreas.value.findIndex(a => a.id === boundaryLine.area!.id);
        if (areaIndex !== -1) {
          const area = editableAreas.value[areaIndex];
          const oldX = area.boundaries.x || 0;
          const diff = newX - oldX;
          
          // 调整区域位置和宽度
          if (boundaryLine.x === oldX) {
            // 左边界
            editableAreas.value[areaIndex].boundaries.x = newX;
            editableAreas.value[areaIndex].boundaries.width = (area.boundaries.width || 0) - diff;
          } else {
            // 右边界
            editableAreas.value[areaIndex].boundaries.width = (area.boundaries.width || 0) + diff;
          }
        }
      }
    } else {
      const newY = Math.max(0, Math.min(floorPlanHeight, svgPoint.y));
      const boundaryLine = horizontalBoundaries.value[boundary.index];
      
      // 更新相关区域的边界
      if (boundaryLine.area) {
        const areaIndex = editableAreas.value.findIndex(a => a.id === boundaryLine.area!.id);
        if (areaIndex !== -1) {
          const area = editableAreas.value[areaIndex];
          const oldY = area.boundaries.y || 0;
          const diff = newY - oldY;
          
          // 调整区域位置和高度
          if (boundaryLine.y === oldY) {
            // 上边界
            editableAreas.value[areaIndex].boundaries.y = newY;
            editableAreas.value[areaIndex].boundaries.height = (area.boundaries.height || 0) - diff;
          } else {
            // 下边界
            editableAreas.value[areaIndex].boundaries.height = (area.boundaries.height || 0) + diff;
          }
        }
      }
    }
  }
};

const onMouseUp = (event: MouseEvent) => {
  if (dragging.value) {
    event.preventDefault();
    dragging.value = false;
    draggedTable.value = null;
  }
  
  if (draggingBoundary.value) {
    event.preventDefault();
    draggingBoundary.value = null;
  }
};

// 根据位置判断桌位所在的区域类型
const getTableTypeByPosition = (x: number, y: number): 'window' | 'corner' | 'center' => {
  // 查找包含该位置的区域
  for (const area of editableAreas.value.filter(a => a && a.is_active)) {
    if (!area.boundaries) continue;
    
    const bx = area.boundaries.x || 0;
    const by = area.boundaries.y || 0;
    const bw = area.boundaries.width || 0;
    const bh = area.boundaries.height || 0;
    
    // 检查点是否在区域内
    if (x >= bx && x <= bx + bw && y >= by && y <= by + bh) {
      // 根据区域类型返回对应的桌位类型
      if (area.type === 'window') return 'window';
      if (area.type === 'corner') return 'corner';
      if (area.type === 'center') return 'center';
    }
  }
  
  // 如果没有匹配的区域，根据位置判断
  // 左侧区域（0-200）通常是窗边
  if (x < 200) return 'window';
  // 中间区域（200-600）通常是角落
  if (x >= 200 && x < 600) return 'corner';
  // 右侧区域（600+）通常是中央
  return 'center';
};

const savePositions = async () => {
  // 防止重复点击
  if (saving.value) {
    ElMessage.warning('正在保存中，请稍候...');
    return;
  }
  
  saving.value = true;
  try {
    // 收集所有需要更新的桌位（包括有位置和重置后位置为 null 的）
    // 使用快照避免在保存过程中 editableTables 被修改
    const tablesSnapshot = editableTables.value.map(t => ({ ...t }));
    
    const updates = tablesSnapshot.map(table => {
      // 获取原始数据
      const originalTable = props.tables.find(t => t.id === table.id);
      if (!originalTable) {
        return {
          id: table.id,
          position_x: table.position_x ?? null,
          position_y: table.position_y ?? null,
          type: table.type,
        };
      }
      
      // 检查位置是否有变化
      const positionChanged = 
        (originalTable.position_x !== table.position_x) ||
        (originalTable.position_y !== table.position_y);
      
      let tableType = table.type;
      
      // 只有在位置有变化时，才根据位置重新判断类型
      if (positionChanged) {
        if (table.position_x !== null && table.position_x !== undefined &&
            table.position_y !== null && table.position_y !== undefined) {
          // 位置有变化，根据新位置判断类型
          tableType = getTableTypeByPosition(table.position_x, table.position_y);
        } else {
          // 位置被重置为 null，根据默认布局位置所在的区域判断类型
          tableType = getTypeByDefaultPosition(table);
        }
      }
      // 如果位置没有变化，保持原有类型不变
      
      return {
        id: table.id,
        position_x: table.position_x ?? null,
        position_y: table.position_y ?? null,
        type: tableType, // 同时更新类型
      };
    });
    
    // 检查是否有需要更新的桌位（位置或类型有变化）
    const hasChanges = updates.some(u => {
      const originalTable = props.tables.find(t => t.id === u.id);
      if (!originalTable) return false;
      
      // 检查位置是否有变化
      const positionChanged = 
        (originalTable.position_x !== u.position_x) ||
        (originalTable.position_y !== u.position_y);
      
      // 检查类型是否有变化
      const typeChanged = originalTable.type !== u.type;
      
      return positionChanged || typeChanged;
    });
    
    if (!hasChanges) {
      ElMessage.info('没有需要保存的更改');
      return;
    }
    
    // 先更新位置（包括 null 值，用于重置位置）
    await tableApi.updatePositions(updates.map(u => ({
      id: u.id,
      position_x: u.position_x,
      position_y: u.position_y,
    })));
    
    // 然后更新类型（如果有变化）- 与原始 props 数据比较
    const typeUpdates = updates.filter(u => {
      const originalTable = props.tables.find(t => t.id === u.id);
      return originalTable && originalTable.type !== u.type;
    });
    
    if (typeUpdates.length > 0) {
      await Promise.all(typeUpdates.map(u => 
        tableApi.update(u.id, { type: u.type })
      ));
    }
    
    ElMessage.success('桌位位置和类型已保存');
    emit('positions-updated');
  } catch (error) {
    console.error('保存位置失败:', error);
    ElMessage.error('保存位置失败，请重试');
  } finally {
    saving.value = false;
  }
};

// 根据默认布局位置判断类型
const getTypeByDefaultPosition = (table: Table): 'window' | 'corner' | 'center' => {
  // 计算默认布局位置（使用原始类型避免影响其他桌位）
  let defaultX = 0;
  
  // 获取原始类型（从 props.tables）
  const originalTable = props.tables.find(t => t.id === table.id);
  const originalType = originalTable?.type || table.type;
  
  if (originalType === 'window') {
    // 使用原始类型计算索引，避免拖拽时影响其他桌位
    const windowIndex = props.tables.filter(t => t.type === 'window').findIndex(t => t.id === table.id);
    defaultX = 100;
  } else if (originalType === 'corner') {
    const cornerIndex = props.tables.filter(t => t.type === 'corner').findIndex(t => t.id === table.id);
    if (cornerIndex < 2) {
      defaultX = 300;
    } else if (cornerIndex < 4) {
      defaultX = 550;
    } else {
      defaultX = 400;
    }
  } else {
    const centerIndex = props.tables.filter(t => t.type === 'center').findIndex(t => t.id === table.id);
    const cols = 5;
    const col = centerIndex % cols;
    defaultX = 650 + col * 30;
  }
  
  // 根据默认位置判断类型
  return getTableTypeByPosition(defaultX, floorPlanHeight / 2);
};

const resetPositions = () => {
  editableTables.value.forEach(table => {
    // 获取原始数据以获取默认位置
    const originalTable = props.tables.find(t => t.id === table.id);
    
    // 如果有默认位置，重置到默认位置；否则重置为 null
    if (originalTable?.default_position_x !== null && originalTable?.default_position_x !== undefined &&
        originalTable?.default_position_y !== null && originalTable?.default_position_y !== undefined) {
      table.position_x = originalTable.default_position_x;
      table.position_y = originalTable.default_position_y;
      // 根据默认位置判断类型
      table.type = getTableTypeByPosition(originalTable.default_position_x, originalTable.default_position_y);
    } else {
      table.position_x = null;
      table.position_y = null;
      // 重置位置时，根据默认布局位置所在的区域判断类型
      table.type = getTypeByDefaultPosition(table);
    }
  });
  ElMessage.info('位置已重置到默认位置');
};

onMounted(() => {
  document.addEventListener('mousemove', onMouseMove, { passive: false });
  document.addEventListener('mouseup', onMouseUp, { passive: false });
  fetchAreas().then(() => {
    initDefaultAreas();
  });
});

onUnmounted(() => {
  document.removeEventListener('mousemove', onMouseMove);
  document.removeEventListener('mouseup', onMouseUp);
});
</script>

<style scoped>
.table-layout-editor {
  width: 100%;
}

.cursor-move {
  cursor: move;
}

.cursor-move:active {
  cursor: grabbing;
}

.cursor-col-resize {
  cursor: col-resize;
}

.cursor-row-resize {
  cursor: row-resize;
}
</style>

