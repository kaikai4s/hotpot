<template>
  <!-- 登录页面：单独显示，不包含侧边栏 -->
  <router-view v-if="route.path === '/admin/login'" />
  
  <!-- 管理后台布局 -->
  <el-container v-else-if="isAdminRoute" class="h-screen">
    <el-aside width="240px" class="bg-gradient-to-b from-gray-800 to-gray-900 text-white shadow-xl">
      <div class="p-6">
        <div class="flex items-center mb-8">
          <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-orange-500 rounded-lg flex items-center justify-center mr-3">
            <span class="text-2xl">🔥</span>
          </div>
          <div>
            <h2 class="text-xl font-bold">火锅店管理</h2>
            <p class="text-xs text-gray-400">Management System</p>
          </div>
        </div>
        <el-menu
          :default-active="activeMenu"
          class="bg-transparent border-0"
          text-color="#fff"
          active-text-color="#ff6b6b"
          background-color="transparent"
          router
        >
          <el-menu-item index="/admin/dashboard" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
            <el-icon><DataAnalysis /></el-icon>
            <span>仪表盘</span>
          </el-menu-item>
          <el-menu-item index="/admin/reservations" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
            <el-icon><Calendar /></el-icon>
            <span>预约管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/deposits" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
            <el-icon><Money /></el-icon>
            <span>定金管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/reviews" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
            <el-icon><Star /></el-icon>
            <span>评价管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/dishes" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
            <el-icon><Food /></el-icon>
            <span>菜品管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/tables" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
            <el-icon><Grid /></el-icon>
            <span>桌位管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/orders" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
            <el-icon><ShoppingBag /></el-icon>
            <span>订单管理</span>
          </el-menu-item>
              <el-menu-item index="/admin/admins" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
                <el-icon><UserFilled /></el-icon>
                <span>管理员管理</span>
              </el-menu-item>
              <el-menu-item index="/admin/users" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
                <el-icon><User /></el-icon>
                <span>用户管理</span>
              </el-menu-item>
              <el-sub-menu index="points-menu" class="sub-menu-custom">
                <template #title>
                  <el-icon><Star /></el-icon>
                  <span>积分系统</span>
                </template>
                <el-menu-item index="/admin/points" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
                  <span>积分管理</span>
                </el-menu-item>
                <el-menu-item index="/admin/point-levels" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
                  <span>段位管理</span>
                </el-menu-item>
                <el-menu-item index="/admin/point-rules" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
                  <span>规则配置</span>
                </el-menu-item>
                <el-menu-item index="/admin/point-statistics" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
                  <span>统计分析</span>
                </el-menu-item>
              </el-sub-menu>
              <el-sub-menu index="coupons-menu" class="sub-menu-custom">
                <template #title>
                  <el-icon><Ticket /></el-icon>
                  <span>优惠活动</span>
                </template>
                <el-menu-item index="/admin/coupons" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
                  <span>优惠券管理</span>
                </el-menu-item>
                <el-menu-item index="/admin/lottery" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
                  <span>抽奖活动</span>
                </el-menu-item>
              </el-sub-menu>
              <el-menu-item index="/admin/roles" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
                <el-icon><Lock /></el-icon>
                <span>角色权限</span>
              </el-menu-item>
              <!-- 操作日志仅超级管理员可见 -->
              <el-menu-item 
                v-if="adminInfo?.role === 'super_admin' || hasPermission('audit_logs.view')"
                index="/admin/audit-logs" 
                class="mb-2 rounded-lg hover:bg-gray-700 transition-all"
              >
                <el-icon><Document /></el-icon>
                <span>操作日志</span>
              </el-menu-item>
          <el-menu-item index="/admin/settings" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
            <el-icon><Setting /></el-icon>
            <span>配置管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/profile" class="mb-2 rounded-lg hover:bg-gray-700 transition-all">
            <el-icon><User /></el-icon>
            <span>个人中心</span>
          </el-menu-item>
        </el-menu>
      </div>
    </el-aside>
    <el-container>
      <!-- 顶部栏 -->
      <el-header class="bg-white shadow-sm border-b border-gray-200 flex items-center justify-between px-6 h-16">
        <div class="flex items-center">
          <h3 class="text-lg font-semibold text-gray-800">{{ pageTitle }}</h3>
        </div>
        <div class="flex items-center">
          <el-dropdown @command="handleCommand" trigger="click">
            <div class="flex items-center cursor-pointer hover:bg-gray-50 px-3 py-2 rounded-lg transition-colors">
              <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-orange-500 rounded-full flex items-center justify-center mr-2">
                <span class="text-white text-sm font-bold">{{ adminInfo?.name?.charAt(0) || 'A' }}</span>
              </div>
              <div class="text-right mr-2">
                <p class="text-sm font-medium text-gray-800">{{ adminInfo?.name || '管理员' }}</p>
                <p class="text-xs text-gray-500">{{ adminInfo?.role === 'super_admin' ? '超级管理员' : adminInfo?.role === 'admin' ? '管理员' : '操作员' }}</p>
              </div>
              <el-icon class="text-gray-500"><ArrowDown /></el-icon>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="profile">
                  <el-icon><User /></el-icon>
                  <span class="ml-2">个人中心</span>
                </el-dropdown-item>
                <el-dropdown-item divided command="logout">
                  <el-icon><SwitchButton /></el-icon>
                  <span class="ml-2">退出登录</span>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </el-header>
      <el-main class="bg-gray-50 overflow-auto">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
  
  <!-- 前台布局 -->
  <router-view v-else />
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Calendar, Star, DataAnalysis, Food, Grid, ArrowDown, User, SwitchButton, UserFilled, Lock, Setting, Ticket, ShoppingBag, Money, Document } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { adminAuthApi } from './api/admin';
import type { AdminInfo } from './api/admin';

const route = useRoute();
const router = useRouter();
const activeMenu = computed(() => route.path);
const adminInfo = ref<AdminInfo | null>(null);

const isAdminRoute = computed(() => {
  // 后台路由统一以 /admin/ 开头（但排除 /admin/login）
  return route.path.startsWith('/admin/') && route.path !== '/admin/login';
});

// 检查是否有指定权限
const hasPermission = (permissionName: string): boolean => {
  if (!adminInfo.value) {
    return false;
  }
  
  // 超级管理员拥有所有权限
  if (adminInfo.value.role === 'super_admin') {
    return true;
  }
  
  // 检查权限列表
  if (adminInfo.value.permissions) {
    return adminInfo.value.permissions.some(p => p.name === permissionName);
  }
  
  return false;
};

const pageTitle = computed(() => {
      const titles: Record<string, string> = {
        '/admin/dashboard': '仪表盘',
        '/admin/reservations': '预约管理',
        '/admin/reviews': '评价管理',
        '/admin/dishes': '菜品管理',
        '/admin/tables': '桌位管理',
        '/admin/users': '用户管理',
        '/admin/admins': '管理员管理',
        '/admin/points': '积分管理',
        '/admin/point-rules': '积分规则配置',
        '/admin/point-statistics': '积分统计分析',
        '/admin/coupons': '优惠券管理',
        '/admin/lottery': '抽奖活动',
        '/admin/roles': '角色权限',
        '/admin/audit-logs': '操作日志',
        '/admin/settings': '配置管理',
        '/admin/miniapp': '小程序管理',
        '/admin/profile': '个人中心',
      };
  return titles[route.path] || '管理后台';
});

const loadAdminInfo = async () => {
  try {
    // 先尝试从缓存加载（使用 sessionStorage）
    const adminInfoStr = sessionStorage.getItem('admin_info');
    if (adminInfoStr) {
      try {
        adminInfo.value = JSON.parse(adminInfoStr);
      } catch (e) {
        console.error('解析admin_info失败:', e);
      }
    }
    
    // 检查是否有token，如果没有token就不调用API（使用 sessionStorage）
    const token = sessionStorage.getItem('admin_token');
    if (!token) {
      console.warn('没有token，跳过获取管理员信息');
      return;
    }
    
    // 然后从服务器获取最新信息（静默失败，不影响页面显示）
    try {
      const response = await adminAuthApi.me();
      if (response.code === 200 && response.data) {
        adminInfo.value = response.data.admin;
        sessionStorage.setItem('admin_info', JSON.stringify(response.data.admin));
      }
    } catch (error: any) {
      // 如果获取失败（如401），不跳转，只记录错误
      // 响应拦截器会处理跳转逻辑
      console.error('获取管理员信息失败:', error);
      // 如果是401错误，说明token无效，但不要在这里跳转，让响应拦截器处理
      if (error.response?.status === 401) {
        // 清除无效的缓存
        adminInfo.value = null;
        // 不在这里清除token，让响应拦截器统一处理
      }
    }
  } catch (error) {
    console.error('loadAdminInfo异常:', error);
  }
};

const handleCommand = async (command: string) => {
  if (command === 'profile') {
    router.push('/admin/profile');
  } else if (command === 'logout') {
    try {
      await ElMessageBox.confirm('确定要退出登录吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      });
      
      try {
        await adminAuthApi.logout();
      } catch (error) {
        // 即使退出 API 失败，也清除本地 token
        console.error('退出登录 API 失败:', error);
      }
      
      // 清除 sessionStorage 中的后台登录信息
      sessionStorage.removeItem('admin_token');
      sessionStorage.removeItem('admin_info');
      ElMessage.success('已退出登录');
      router.push('/admin/login');
    } catch (error) {
      // 用户取消
    }
  }
};

// 监听路由变化，确保前后台完全独立
router.afterEach((to) => {
  const isAdmin = to.path.startsWith('/admin/') && to.path !== '/admin/login';
  
  if (isAdmin) {
    // 后台路由：加载管理员信息
    setTimeout(() => {
      loadAdminInfo();
    }, 100);
  } else {
    // 前台路由：清除后台信息引用，确保不会显示后台登录信息
    // 注意：后台使用 sessionStorage 存储 token，前台使用 localStorage
    // 两者完全隔离，前台无法清除后台的登录状态
    adminInfo.value = null;
  }
});

onMounted(() => {
  // 只在后台路由时加载管理员信息，前台路由完全不加载
  if (isAdminRoute.value) {
    // 使用 setTimeout 延迟加载，确保 token 已经设置
    setTimeout(() => {
      loadAdminInfo();
    }, 100);
  } else {
    // 前台路由：确保不加载后台信息，并清除可能存在的后台信息引用
    adminInfo.value = null;
  }
});
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

:deep(.el-menu-item) {
  margin-bottom: 4px;
}

:deep(.el-menu-item.is-active) {
  background: linear-gradient(90deg, rgba(255, 107, 107, 0.2) 0%, transparent 100%) !important;
  border-left: 3px solid #ff6b6b;
}

/* 子菜单项样式 */
:deep(.el-sub-menu .el-menu-item) {
  color: #fff !important;
  background-color: transparent !important;
}

:deep(.el-sub-menu .el-menu-item:hover) {
  background-color: rgba(255, 255, 255, 0.1) !important;
  color: #fff !important;
}

:deep(.el-sub-menu .el-menu-item.is-active) {
  background: linear-gradient(90deg, rgba(255, 107, 107, 0.2) 0%, transparent 100%) !important;
  color: #ff6b6b !important;
  border-left: 3px solid #ff6b6b;
}

/* 子菜单容器样式 */
:deep(.el-sub-menu .el-menu) {
  background-color: rgba(0, 0, 0, 0.2) !important;
}

/* 内联子菜单样式 - 使用更具体的选择器确保覆盖 */
.el-sub-menu .el-menu.el-menu--inline,
.el-sub-menu.sub-menu-custom .el-menu.el-menu--inline,
:deep(.el-sub-menu .el-menu.el-menu--inline),
:deep(.el-sub-menu .el-menu.el-menu--inline[role="menu"]),
:deep(.sub-menu-custom .el-menu.el-menu--inline) {
  background: unset !important;
  background-color: transparent !important;
  --el-menu-bg-color: transparent !important;
  color: #fff !important;
  padding-left: 0 !important;
}

/* 内联子菜单项样式 */
.el-sub-menu .el-menu.el-menu--inline .el-menu-item,
.el-sub-menu.sub-menu-custom .el-menu.el-menu--inline .el-menu-item,
:deep(.el-sub-menu .el-menu.el-menu--inline .el-menu-item),
:deep(.sub-menu-custom .el-menu.el-menu--inline .el-menu-item) {
  color: #fff !important;
  background-color: transparent !important;
  padding-left: 40px !important;
}

:deep(.el-sub-menu .el-menu.el-menu--inline .el-menu-item span) {
  color: #fff !important;
}

:deep(.el-sub-menu .el-menu.el-menu--inline .el-menu-item:hover) {
  background-color: rgba(255, 255, 255, 0.1) !important;
  color: #fff !important;
}

:deep(.el-sub-menu .el-menu.el-menu--inline .el-menu-item:hover span) {
  color: #fff !important;
}

:deep(.el-sub-menu .el-menu.el-menu--inline .el-menu-item.is-active) {
  background: linear-gradient(90deg, rgba(255, 107, 107, 0.2) 0%, transparent 100%) !important;
  color: #ff6b6b !important;
  border-left: 3px solid #ff6b6b;
}

:deep(.el-sub-menu .el-menu.el-menu--inline .el-menu-item.is-active span) {
  color: #ff6b6b !important;
}

/* 子菜单标题样式 */
:deep(.el-sub-menu__title) {
  color: #fff !important;
}

:deep(.el-sub-menu__title:hover) {
  background-color: rgba(255, 255, 255, 0.1) !important;
  color: #fff !important;
}
</style>
