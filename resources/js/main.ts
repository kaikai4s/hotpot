/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import * as ElementPlusIconsVue from '@element-plus/icons-vue';
import App from './App.vue';
import router from './router';
import '../css/app.css'; // 导入 Tailwind CSS

const app = createApp(App);
const pinia = createPinia();

// 注册所有图标
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component);
}

app.use(pinia);
app.use(router);
app.use(ElementPlus);

// 动态设置页面标题和描述
router.beforeEach((to, from, next) => {
  // 获取或创建 meta description 标签
  let metaDescription = document.querySelector('meta[name="description"]');
  if (!metaDescription) {
    metaDescription = document.createElement('meta');
    metaDescription.setAttribute('name', 'description');
    document.head.appendChild(metaDescription);
  }
  
  // 根据路由设置页面标题和描述
  if (to.path.startsWith('/admin/') && to.path !== '/admin/login') {
    // 后台路由
    const titles: Record<string, string> = {
      '/admin/dashboard': '仪表盘 - 火锅店管理后台',
      '/admin/reservations': '预约管理 - 火锅店管理后台',
      '/admin/reviews': '评价管理 - 火锅店管理后台',
      '/admin/dishes': '菜品管理 - 火锅店管理后台',
      '/admin/tables': '桌位管理 - 火锅店管理后台',
      '/admin/miniapp': '小程序管理 - 火锅店管理后台',
      '/admin/profile': '个人中心 - 火锅店管理后台',
    };
    const descriptions: Record<string, string> = {
      '/admin/dashboard': '火锅店管理后台 - 仪表盘',
      '/admin/reservations': '火锅店管理后台 - 预约管理',
      '/admin/reviews': '火锅店管理后台 - 评价管理',
      '/admin/dishes': '火锅店管理后台 - 菜品管理',
      '/admin/tables': '火锅店管理后台 - 桌位管理',
      '/admin/miniapp': '火锅店管理后台 - 小程序管理',
      '/admin/profile': '火锅店管理后台 - 个人中心',
    };
    document.title = titles[to.path] || '火锅店管理后台';
    metaDescription.setAttribute('content', descriptions[to.path] || '火锅店管理后台系统');
  } else if (to.path === '/admin/login') {
    // 后台登录页
    document.title = '管理员登录 - 火锅店管理后台';
    metaDescription.setAttribute('content', '火锅店管理后台 - 管理员登录');
  } else {
    // 前台路由
    const titles: Record<string, string> = {
      '/': '首页 - 火锅店',
      '/frontend/reservation': '在线预约 - 火锅店',
      '/frontend/dishes': '菜品展示 - 火锅店',
      '/frontend/queue': '排队叫号 - 火锅店',
      '/frontend/points': '会员积分 - 火锅店',
      '/frontend/coupons': '优惠活动 - 火锅店',
      '/frontend/profile': '我的 - 火锅店',
      '/frontend/review': '评价 - 火锅店',
      '/frontend/login': '用户登录 - 火锅店',
    };
    const descriptions: Record<string, string> = {
      '/': '火锅店在线预约系统 - 首页',
      '/frontend/reservation': '火锅店在线预约系统 - 在线预约',
      '/frontend/dishes': '火锅店在线预约系统 - 菜品展示',
      '/frontend/queue': '火锅店在线预约系统 - 排队叫号',
      '/frontend/points': '火锅店在线预约系统 - 会员积分',
      '/frontend/coupons': '火锅店在线预约系统 - 优惠活动',
      '/frontend/profile': '火锅店在线预约系统 - 我的',
      '/frontend/review': '火锅店在线预约系统 - 评价',
      '/frontend/login': '火锅店在线预约系统 - 用户登录',
    };
    document.title = titles[to.path] || '火锅店';
    metaDescription.setAttribute('content', descriptions[to.path] || '火锅店在线预约系统');
  }
  next();
});

app.mount('#app');

