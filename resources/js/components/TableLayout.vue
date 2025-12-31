/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="table-layout-container">
    <!-- 图例 -->
    <div class="mb-4 flex flex-wrap gap-4 text-sm">
      <div class="flex items-center gap-2">
        <div class="w-4 h-4 rounded bg-green-500"></div>
        <span>可用</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="w-4 h-4 rounded bg-blue-500"></div>
        <span>已预约</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="w-4 h-4 rounded bg-orange-500"></div>
        <span>使用中</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="w-4 h-4 rounded bg-gray-400"></div>
        <span>维护中</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="w-4 h-4 rounded border-2 border-red-500 bg-red-50"></div>
        <span>已选择</span>
      </div>
    </div>

    <!-- 餐厅平面图 -->
    <div class="relative bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-6 shadow-lg border-2 border-amber-200 w-full">
      <svg 
        :width="floorPlanWidth" 
        :height="floorPlanHeight" 
        :viewBox="`0 0 ${floorPlanWidth} ${floorPlanHeight}`"
        preserveAspectRatio="xMidYMid meet"
        class="w-full h-auto"
        style="background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%); max-width: 100%;"
      >
          <!-- 定义渐变和图案 -->
          <defs>
            <!-- 地板纹理 -->
            <pattern id="floorPattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
              <rect width="40" height="40" fill="#fef3c7" opacity="0.3"/>
              <path d="M 0 0 L 40 40 M 40 0 L 0 40" stroke="#fbbf24" stroke-width="0.5" opacity="0.2"/>
            </pattern>
            <!-- 窗户渐变 -->
            <linearGradient id="windowGradient" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" style="stop-color:#93c5fd;stop-opacity:0.6" />
              <stop offset="100%" style="stop-color:#60a5fa;stop-opacity:0.8" />
            </linearGradient>
          </defs>

          <!-- 地板 -->
          <rect width="100%" height="100%" fill="url(#floorPattern)" />

          <!-- 外墙 -->
          <rect x="0" y="0" :width="floorPlanWidth" :height="floorPlanHeight" fill="none" stroke="#92400e" stroke-width="8" rx="4"/>
          
          <!-- 自定义区域背景 -->
          <g v-for="area in (areas || []).filter(a => a && a.is_active !== false)" :key="area.id">
            <rect
              v-if="area.boundaries && area.boundaries.x !== undefined && area.boundaries.y !== undefined && area.boundaries.width !== undefined && area.boundaries.height !== undefined"
              :x="area.boundaries.x"
              :y="area.boundaries.y"
              :width="area.boundaries.width"
              :height="area.boundaries.height"
              :fill="area.color || '#93c5fd'"
              :opacity="0.15"
            />
          </g>

          <!-- 区域边界线（从区域配置计算） -->
          <g v-for="(boundary, index) in verticalBoundaries" :key="'v-' + index">
            <line
              :x1="boundary.x"
              :y1="0"
              :x2="boundary.x"
              :y2="floorPlanHeight"
              stroke="#92400e"
              stroke-width="6"
              stroke-dasharray="5,5"
              opacity="0.5"
            />
          </g>

          <g v-for="(boundary, index) in horizontalBoundaries" :key="'h-' + index">
            <line
              :x1="0"
              :y1="boundary.y"
              :x2="floorPlanWidth"
              :y2="boundary.y"
              stroke="#92400e"
              stroke-width="6"
              stroke-dasharray="5,5"
              opacity="0.5"
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
          <g v-for="area in areas.filter(a => a && a.is_active !== false)" :key="'label-' + area.id">
            <text
              v-if="area.boundaries && area.boundaries.x !== undefined && area.boundaries.y !== undefined"
              :x="area.boundaries.x + (area.boundaries.width || 0) / 2"
              :y="(area.boundaries.y || 0) + 20"
              text-anchor="middle"
              :fill="area.color || '#78350f'"
              font-size="14"
              font-weight="bold"
            >
              {{ area.name }}
            </text>
          </g>

          <!-- 桌位 -->
          <g v-for="table in positionedTables" :key="table.id">
            <!-- 桌位圆圈 -->
            <circle
              :cx="table.x"
              :cy="table.y"
              :r="table.radius"
              :fill="getTableColor(table.status)"
              :stroke="selectedTableId === table.id ? '#dc2626' : '#78350f'"
              :stroke-width="selectedTableId === table.id ? 4 : 2"
              class="cursor-pointer transition-all"
              :class="{ 'animate-pulse': selectedTableId === table.id }"
              :opacity="table.status === 'maintenance' ? 0.5 : 1"
              @click="handleTableClick(table)"
              style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"
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

            <!-- 选中指示器 -->
            <circle
              v-if="selectedTableId === table.id"
              :cx="table.x + table.radius - 5"
              :cy="table.y - table.radius + 5"
              r="8"
              fill="#dc2626"
              stroke="#fff"
              stroke-width="2"
            />
            <text
              v-if="selectedTableId === table.id"
              :x="table.x + table.radius - 5"
              :y="table.y - table.radius + 8"
              text-anchor="middle"
              fill="#fff"
              font-size="10"
              font-weight="bold"
            >
              ✓
            </text>

            <!-- 悬停提示 -->
            <title>{{ table.name }} - {{ table.capacity }}人 - {{ getStatusText(table.status) }} - {{ getTypeText(table.type) }}</title>
          </g>

          <!-- 桌位图标（可选，使用emoji） -->
          <g v-for="table in positionedTables" :key="'icon-' + table.id" class="pointer-events-none">
            <text
              :x="table.x"
              :y="table.y + 2"
              text-anchor="middle"
              font-size="16"
              opacity="0.3"
            >
              🪑
            </text>
          </g>
        </svg>
    </div>

    <!-- 选中桌位信息 -->
    <div v-if="selectedTable" class="mt-4 p-4 bg-gradient-to-r from-red-50 to-orange-50 rounded-lg shadow-md">
      <h3 class="font-bold text-gray-900 mb-1">已选择：{{ selectedTable.name }}</h3>
      <p class="text-sm text-gray-600">
        {{ selectedTable.capacity }}人 · {{ getTypeText(selectedTable.type) }} · {{ getStatusText(selectedTable.status) }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Table } from '../types';
import type { RestaurantArea } from '../api/area';

interface Props {
  tables: Table[];
  selectedTableId?: number | null;
  areas?: RestaurantArea[];
}

const props = withDefaults(defineProps<Props>(), {
  areas: () => [],
});

const areas = computed(() => props.areas || []);

const emit = defineEmits<{
  'table-selected': [table: Table];
}>();

// 平面图尺寸
const floorPlanWidth = 800;
const floorPlanHeight = 500;

// 桌位半径（根据容量调整）
const getTableRadius = (capacity: number): number => {
  return capacity <= 4 ? 20 : capacity <= 6 ? 24 : 28;
};

// 计算桌位的实际位置（基于类型和数据库中的坐标）
const positionedTables = computed(() => {
  return props.tables.map(table => {
    let x = 0;
    let y = 0;
    
    // 如果有数据库坐标，直接使用（坐标已经在编辑器中设置为平面图坐标）
    if (table.position_x !== undefined && table.position_y !== undefined && table.position_x !== null && table.position_y !== null) {
      x = table.position_x;
      y = table.position_y;
    } else {
      // 如果没有坐标，根据类型自动布局
      if (table.type === 'window') {
        // 窗边桌位：左侧区域，靠近窗户
        const windowIndex = props.tables.filter(t => t.type === 'window').findIndex(t => t.id === table.id);
        x = 100;
        y = 100 + windowIndex * 70;
      } else if (table.type === 'corner') {
        // 角落桌位：中间区域，靠近角落
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
        // 中央桌位：右侧区域，中央排列
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

const selectedTable = computed(() => {
  return props.tables.find(t => t.id === props.selectedTableId) || null;
});

// 计算垂直边界线
const verticalBoundaries = computed(() => {
  const boundaries: Array<{ x: number }> = [];
  const xSet = new Set<number>();
  
  (props.areas || []).filter(a => a && a.is_active !== false).forEach(area => {
    if (!area.boundaries || !area.boundaries.x) return;
    const x = area.boundaries.x;
    if (!xSet.has(x)) {
      xSet.add(x);
      boundaries.push({ x });
    }
    const rightX = (area.boundaries.x || 0) + (area.boundaries.width || 0);
    if (rightX < floorPlanWidth && !xSet.has(rightX)) {
      xSet.add(rightX);
      boundaries.push({ x: rightX });
    }
  });
  
  return boundaries.sort((a, b) => a.x - b.x);
});

// 计算水平边界线
const horizontalBoundaries = computed(() => {
  const boundaries: Array<{ y: number }> = [];
  const ySet = new Set<number>();
  
  (props.areas || []).filter(a => a && a.is_active !== false).forEach(area => {
    if (!area.boundaries || !area.boundaries.y) return;
    const y = area.boundaries.y;
    if (!ySet.has(y)) {
      ySet.add(y);
      boundaries.push({ y });
    }
    const bottomY = (area.boundaries.y || 0) + (area.boundaries.height || 0);
    if (bottomY < floorPlanHeight && !ySet.has(bottomY)) {
      ySet.add(bottomY);
      boundaries.push({ y: bottomY });
    }
  });
  
  return boundaries.sort((a, b) => a.y - b.y);
});

const getTableColor = (status: string): string => {
  switch (status) {
    case 'available':
      return '#10b981'; // 绿色
    case 'reserved':
      return '#3b82f6'; // 蓝色
    case 'occupied':
      return '#f97316'; // 橙色
    case 'maintenance':
      return '#9ca3af'; // 灰色
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
  };
  return texts[type] || type;
};

const handleTableClick = (table: Table) => {
  if (table.status === 'maintenance') {
    return; // 维护中的桌位不可选择
  }
  emit('table-selected', table);
};
</script>

<style scoped>
.table-layout-container {
  width: 100%;
}

/* 平滑滚动 */
.overflow-auto {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f1f5f9;
}

.overflow-auto::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}

.overflow-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.overflow-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* 确保SVG完整显示 */
svg {
  display: block;
  max-width: 100%;
  height: auto;
}
</style>

