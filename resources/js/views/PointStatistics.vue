/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

<template>
  <div class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">积分统计分析</h1>
          <p class="text-gray-600">查看积分获得、使用和过期统计数据</p>
        </div>
        <el-button type="primary" size="large" @click="fetchReport">
          <el-icon><Refresh /></el-icon>
          刷新数据
        </el-button>
      </div>

      <!-- 日期筛选 -->
      <div class="flex gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          @change="fetchReport"
        />
        <el-button @click="resetDateRange">重置</el-button>
      </div>

      <!-- 汇总统计卡片 -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <el-card shadow="hover">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
              <el-icon class="text-blue-600 text-2xl"><TrendCharts /></el-icon>
            </div>
            <div>
              <p class="text-sm text-gray-600">总获得积分</p>
              <p class="text-2xl font-bold text-blue-600">{{ summary.total_earned.toLocaleString() }}</p>
            </div>
          </div>
        </el-card>
        <el-card shadow="hover">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
              <el-icon class="text-green-600 text-2xl"><ShoppingCart /></el-icon>
            </div>
            <div>
              <p class="text-sm text-gray-600">总兑换积分</p>
              <p class="text-2xl font-bold text-green-600">{{ summary.total_redeemed.toLocaleString() }}</p>
            </div>
          </div>
        </el-card>
        <el-card shadow="hover">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
              <el-icon class="text-red-600 text-2xl"><Clock /></el-icon>
            </div>
            <div>
              <p class="text-sm text-gray-600">总过期积分</p>
              <p class="text-2xl font-bold text-red-600">{{ summary.total_expired.toLocaleString() }}</p>
            </div>
          </div>
        </el-card>
        <el-card shadow="hover">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
              <el-icon class="text-purple-600 text-2xl"><User /></el-icon>
            </div>
            <div>
              <p class="text-sm text-gray-600">活跃用户数</p>
              <p class="text-2xl font-bold text-purple-600">{{ summary.total_active_users.toLocaleString() }}</p>
            </div>
          </div>
        </el-card>
      </div>

      <!-- 平均值统计 -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <el-card shadow="hover">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><TrendCharts /></el-icon>
              <span>日均获得积分</span>
            </div>
          </template>
          <p class="text-3xl font-bold text-blue-600">{{ summary.avg_earned_per_day.toLocaleString() }}</p>
        </el-card>
        <el-card shadow="hover">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><ShoppingCart /></el-icon>
              <span>日均兑换积分</span>
            </div>
          </template>
          <p class="text-3xl font-bold text-green-600">{{ summary.avg_redeemed_per_day.toLocaleString() }}</p>
        </el-card>
      </div>
    </div>

    <!-- 详细统计表格 -->
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h2 class="text-xl font-bold text-gray-800 mb-4">每日统计明细</h2>
      <el-table v-loading="loading" :data="statistics" stripe border style="width: 100%">
        <el-table-column prop="stat_date" label="日期" width="120" />
        <el-table-column prop="total_earned" label="获得积分" width="120">
          <template #default="{ row }">
            <span class="text-blue-600 font-semibold">+{{ row.total_earned.toLocaleString() }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="total_redeemed" label="兑换积分" width="120">
          <template #default="{ row }">
            <span class="text-green-600 font-semibold">-{{ row.total_redeemed.toLocaleString() }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="total_expired" label="过期积分" width="120">
          <template #default="{ row }">
            <span class="text-red-600 font-semibold">-{{ row.total_expired.toLocaleString() }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="active_users" label="活跃用户" width="120" />
      </el-table>
    </div>

    <!-- 用户排行榜 -->
    <div class="bg-white rounded-xl shadow-lg p-6 mt-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">用户积分排行榜</h2>
        <el-input-number v-model="rankingLimit" :min="10" :max="100" :step="10" @change="fetchRanking" />
      </div>
      <el-table v-loading="rankingLoading" :data="ranking" stripe border style="width: 100%">
        <el-table-column type="index" label="排名" width="80">
          <template #default="{ $index }">
            <span v-if="$index < 3" class="text-2xl">{{ ['🥇', '🥈', '🥉'][$index] }}</span>
            <span v-else>{{ $index + 1 }}</span>
          </template>
        </el-table-column>
        <el-table-column label="用户" width="200">
          <template #default="{ row }">
            <div class="flex items-center">
              <el-avatar v-if="row.avatar_url" :src="row.avatar_url" :size="32" class="mr-2" />
              <span>{{ row.nickname || 'N/A' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="total_points" label="总积分" width="120" sortable>
          <template #default="{ row }">
            <span class="font-bold text-blue-600">{{ row.total_points.toLocaleString() }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="available_points" label="可用积分" width="120" />
        <el-table-column prop="level" label="等级" width="100">
          <template #default="{ row }">
            <el-tag :type="getLevelTagType(row.level)">{{ getLevelText(row.level) }}</el-tag>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 趋势图表 -->
    <div class="bg-white rounded-xl shadow-lg p-6 mt-6">
      <h2 class="text-xl font-bold text-gray-800 mb-4">积分趋势分析</h2>
      <div ref="trendChartRef" style="width: 100%; height: 400px;"></div>
    </div>

    <!-- 异常检测 -->
    <div class="bg-white rounded-xl shadow-lg p-6 mt-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">异常检测</h2>
        <el-button type="warning" @click="fetchAnomalies">
          <el-icon><Warning /></el-icon>
          检测异常
        </el-button>
      </div>

      <!-- 异常摘要 -->
      <div v-if="anomalySummary" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <el-card shadow="hover">
          <div class="text-center">
            <p class="text-sm text-gray-600 mb-2">异常总数</p>
            <p class="text-3xl font-bold text-gray-800">{{ anomalySummary.total }}</p>
          </div>
        </el-card>
        <el-card shadow="hover">
          <div class="text-center">
            <p class="text-sm text-gray-600 mb-2">高危异常</p>
            <p class="text-3xl font-bold text-red-600">{{ anomalySummary.high_severity }}</p>
          </div>
        </el-card>
        <el-card shadow="hover">
          <div class="text-center">
            <p class="text-sm text-gray-600 mb-2">中等异常</p>
            <p class="text-3xl font-bold text-orange-600">{{ anomalySummary.medium_severity }}</p>
          </div>
        </el-card>
        <el-card shadow="hover">
          <div class="text-center">
            <p class="text-sm text-gray-600 mb-2">异常类型</p>
            <p class="text-3xl font-bold text-blue-600">{{ Object.keys(anomalySummary.by_type).length }}</p>
          </div>
        </el-card>
      </div>

      <!-- 异常列表 -->
      <el-table v-loading="anomalyLoading" :data="anomalies" stripe border style="width: 100%">
        <el-table-column prop="type" label="异常类型" width="150">
          <template #default="{ row }">
            <el-tag :type="getAnomalyTypeTag(row.type)">{{ getAnomalyTypeText(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="severity" label="严重程度" width="120">
          <template #default="{ row }">
            <el-tag :type="row.severity === 'high' ? 'danger' : 'warning'">
              {{ row.severity === 'high' ? '高危' : '中等' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="message" label="异常描述" min-width="300" show-overflow-tooltip />
        <el-table-column prop="created_at" label="时间" width="180">
          <template #default="{ row }">
            {{ row.created_at ? new Date(row.created_at).toLocaleString() : '-' }}
          </template>
        </el-table-column>
      </el-table>
      <div v-if="anomalies.length === 0 && !anomalyLoading" class="text-center text-gray-500 py-8">
        暂无异常检测结果
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { Refresh, TrendCharts, ShoppingCart, Clock, User, Warning } from '@element-plus/icons-vue';
import { adminPointStatisticsApi, type PointStatistic, type UserRankingItem } from '../api/point-statistics';
import { adminPointAnomalyApi, type PointAnomaly, type AnomalySummary } from '../api/point-anomaly';
import * as echarts from 'echarts';

const loading = ref(false);
const rankingLoading = ref(false);
const anomalyLoading = ref(false);
const statistics = ref<PointStatistic[]>([]);
const ranking = ref<UserRankingItem[]>([]);
const anomalies = ref<PointAnomaly[]>([]);
const anomalySummary = ref<AnomalySummary | null>(null);
const dateRange = ref<[string, string] | null>(null);
const rankingLimit = ref(50);
const trendChartRef = ref<HTMLElement | null>(null);
let trendChart: echarts.ECharts | null = null;

const summary = ref({
  total_earned: 0,
  total_redeemed: 0,
  total_expired: 0,
  total_active_users: 0,
  avg_earned_per_day: 0,
  avg_redeemed_per_day: 0,
});

const getLevelText = (level: string) => {
  const map: Record<string, string> = {
    bronze: '青铜',
    silver: '白银',
    gold: '黄金',
    platinum: '白金',
  };
  return map[level] || level;
};

const getLevelTagType = (level: string) => {
  const map: Record<string, string> = {
    bronze: 'info',
    silver: '',
    gold: 'warning',
    platinum: 'success',
  };
  return map[level] || '';
};

const fetchReport = async () => {
  loading.value = true;
  try {
    const params: any = {};
    if (dateRange.value) {
      params.start_date = dateRange.value[0];
      params.end_date = dateRange.value[1];
    }

    const response = await adminPointStatisticsApi.getReport(params);
    if (response.code === 200 && response.data) {
      statistics.value = response.data.statistics;
      summary.value = response.data.summary;
    }
  } catch (error: any) {
    console.error('获取统计报表失败:', error);
    ElMessage.error('获取统计报表失败');
  } finally {
    loading.value = false;
  }
};

const fetchRanking = async () => {
  rankingLoading.value = true;
  try {
    const response = await adminPointStatisticsApi.getRanking({ limit: rankingLimit.value });
    if (response.code === 200 && response.data) {
      ranking.value = response.data.ranking;
    }
  } catch (error: any) {
    console.error('获取排行榜失败:', error);
    ElMessage.error('获取排行榜失败');
  } finally {
    rankingLoading.value = false;
  }
};

const resetDateRange = () => {
  dateRange.value = null;
  fetchReport();
};

const getAnomalyTypeText = (type: string) => {
  const map: Record<string, string> = {
    large_earn: '大额获得',
    frequent_transactions: '频繁交易',
    abnormal_growth: '异常增长',
    balance_anomaly: '余额异常',
    high_expiration_rate: '高过期率',
  };
  return map[type] || type;
};

const getAnomalyTypeTag = (type: string) => {
  const map: Record<string, string> = {
    large_earn: 'warning',
    frequent_transactions: 'danger',
    abnormal_growth: 'info',
    balance_anomaly: 'danger',
    high_expiration_rate: 'warning',
  };
  return map[type] || '';
};

const fetchAnomalies = async () => {
  anomalyLoading.value = true;
  try {
    const [listResponse, summaryResponse] = await Promise.all([
      adminPointAnomalyApi.getList(),
      adminPointAnomalyApi.getSummary(),
    ]);

    if (listResponse.code === 200 && listResponse.data) {
      anomalies.value = listResponse.data.anomalies;
    }

    if (summaryResponse.code === 200 && summaryResponse.data) {
      anomalySummary.value = summaryResponse.data.summary;
    }
  } catch (error: any) {
    console.error('获取异常检测结果失败:', error);
    ElMessage.error('获取异常检测结果失败');
  } finally {
    anomalyLoading.value = false;
  }
};

const initTrendChart = () => {
  if (!trendChartRef.value) return;

  trendChart = echarts.init(trendChartRef.value);

  const updateChart = () => {
    if (!trendChart) return;

    const dates = statistics.value.map(s => s.stat_date).reverse();
    const earnedData = statistics.value.map(s => s.total_earned).reverse();
    const redeemedData = statistics.value.map(s => s.total_redeemed).reverse();
    const expiredData = statistics.value.map(s => s.total_expired).reverse();

    const option = {
      title: {
        text: '积分趋势分析',
        left: 'center',
      },
      tooltip: {
        trigger: 'axis',
      },
      legend: {
        data: ['获得积分', '兑换积分', '过期积分'],
        bottom: 0,
      },
      grid: {
        left: '3%',
        right: '4%',
        bottom: '10%',
        containLabel: true,
      },
      xAxis: {
        type: 'category',
        boundaryGap: false,
        data: dates,
      },
      yAxis: {
        type: 'value',
      },
      series: [
        {
          name: '获得积分',
          type: 'line',
          data: earnedData,
          itemStyle: { color: '#409EFF' },
          smooth: true,
        },
        {
          name: '兑换积分',
          type: 'line',
          data: redeemedData,
          itemStyle: { color: '#67C23A' },
          smooth: true,
        },
        {
          name: '过期积分',
          type: 'line',
          data: expiredData,
          itemStyle: { color: '#F56C6C' },
          smooth: true,
        },
      ],
    };

    trendChart.setOption(option);
  };

  watch(statistics, updateChart, { deep: true });
  updateChart();
};

onMounted(async () => {
  await fetchReport();
  await fetchRanking();
  await fetchAnomalies();
  await nextTick();
  initTrendChart();
});
</script>

<style scoped>
:deep(.el-card__header) {
  font-weight: 600;
}
</style>

