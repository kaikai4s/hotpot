/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';

// 使用懒加载，避免在登录页面时加载后台组件
const Dashboard = () => import('../views/Dashboard.vue');
const Reservations = () => import('../views/Reservations.vue');
const Reviews = () => import('../views/Reviews.vue');
const Dishes = () => import('../views/Dishes.vue');
const Tables = () => import('../views/Tables.vue');
const Miniapp = () => import('../views/Miniapp.vue');
const AdminProfile = () => import('../views/AdminProfile.vue');
const Users = () => import('../views/Users.vue');
const Roles = () => import('../views/Roles.vue');
const Settings = () => import('../views/Settings.vue');
const Admins = () => import('../views/Admins.vue');
const Points = () => import('../views/Points.vue');
const PointLevels = () => import('../views/PointLevels.vue');
const PointRules = () => import('../views/PointRules.vue');
const PointStatistics = () => import('../views/PointStatistics.vue');
const Coupons = () => import('../views/Coupons.vue');
const Orders = () => import('../views/Orders.vue');
const Deposits = () => import('../views/Deposits.vue');
const AuditLogs = () => import('../views/AuditLogs.vue');
const Lottery = () => import('../views/Lottery.vue');

// 前台页面
const FrontendHome = () => import('../views/frontend/Home.vue');
const FrontendReservation = () => import('../views/frontend/Reservation.vue');
const FrontendReservationDetail = () => import('../views/frontend/ReservationDetail.vue');
const FrontendDishes = () => import('../views/frontend/Dishes.vue');
const FrontendCart = () => import('../views/frontend/Cart.vue');
const FrontendCheckout = () => import('../views/frontend/Checkout.vue');
const FrontendOrders = () => import('../views/frontend/Orders.vue');
const FrontendOrderDetail = () => import('../views/frontend/OrderDetail.vue');
const FrontendQueue = () => import('../views/frontend/Queue.vue');
const FrontendProfile = () => import('../views/frontend/Profile.vue');

const routes = [
  // 登录页面（后台登录）
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: Login,
    meta: { requiresAuth: false },
  },
  // 管理后台路由（统一以 /admin/ 开头）
  {
    path: '/admin',
    redirect: '/admin/dashboard',
  },
  {
    path: '/admin/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/reservations',
    name: 'Reservations',
    component: Reservations,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/deposits',
    name: 'Deposits',
    component: Deposits,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/reviews',
    name: 'Reviews',
    component: Reviews,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/dishes',
    name: 'Dishes',
    component: Dishes,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/tables',
    name: 'Tables',
    component: Tables,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/miniapp',
    name: 'Miniapp',
    component: Miniapp,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/profile',
    name: 'AdminProfile',
    component: AdminProfile,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/users',
    name: 'Users',
    component: Users,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/points',
    name: 'Points',
    component: Points,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/point-levels',
    name: 'PointLevels',
    component: PointLevels,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/point-rules',
    name: 'PointRules',
    component: PointRules,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/point-statistics',
    name: 'PointStatistics',
    component: PointStatistics,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/coupons',
    name: 'Coupons',
    component: Coupons,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/lottery',
    name: 'Lottery',
    component: Lottery,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/orders',
    name: 'Orders',
    component: Orders,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/audit-logs',
    name: 'AuditLogs',
    component: AuditLogs,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/roles',
    name: 'Roles',
    component: Roles,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/admins',
    name: 'Admins',
    component: Admins,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/settings',
    name: 'Settings',
    component: Settings,
    meta: { requiresAuth: true },
  },
  // 兼容旧路由，重定向到新路径
  {
    path: '/login',
    redirect: '/admin/login',
  },
  {
    path: '/dashboard',
    redirect: '/admin/dashboard',
  },
  {
    path: '/reservations',
    redirect: '/admin/reservations',
  },
  {
    path: '/reviews',
    redirect: '/admin/reviews',
  },
  {
    path: '/dishes',
    redirect: '/admin/dishes',
  },
  {
    path: '/tables',
    redirect: '/admin/tables',
  },
  {
    path: '/miniapp',
    redirect: '/admin/miniapp',
  },
  // 前台登录页面
  {
    path: '/frontend/login',
    name: 'FrontendLogin',
    component: () => import('../views/frontend/Login.vue'),
    meta: { requiresAuth: false },
  },
  // 前台路由
  {
    path: '/',
    name: 'FrontendHome',
    component: FrontendHome,
  },
  {
    path: '/frontend',
    redirect: '/',
  },
  {
    path: '/frontend/reservation',
    name: 'FrontendReservation',
    component: FrontendReservation,
    meta: { requiresAuth: true }, // 需要登录
  },
  {
    path: '/frontend/reservations/:reservationId',
    name: 'FrontendReservationDetail',
    component: FrontendReservationDetail,
    meta: { requiresAuth: true }, // 需要登录
  },
  {
    path: '/frontend/dishes',
    name: 'FrontendDishes',
    component: FrontendDishes,
  },
  {
    path: '/frontend/cart',
    name: 'FrontendCart',
    component: FrontendCart,
  },
  {
    path: '/frontend/checkout/:orderId',
    name: 'FrontendCheckout',
    component: FrontendCheckout,
    meta: { requiresAuth: true }, // 需要登录
  },
  {
    path: '/frontend/orders',
    name: 'FrontendOrders',
    component: FrontendOrders,
    meta: { requiresAuth: true }, // 需要登录
  },
  {
    path: '/frontend/orders/:orderId',
    name: 'FrontendOrderDetail',
    component: FrontendOrderDetail,
    meta: { requiresAuth: true }, // 需要登录
  },
  {
    path: '/frontend/queue',
    name: 'FrontendQueue',
    component: FrontendQueue,
    meta: { requiresAuth: true }, // 需要登录
  },
  {
    path: '/frontend/points',
    name: 'FrontendPoints',
    component: () => import('../views/frontend/Points.vue'),
    meta: { requiresAuth: true }, // 需要登录
  },
  {
    path: '/frontend/coupons',
    name: 'FrontendCoupons',
    component: () => import('../views/frontend/Coupons.vue'),
    meta: { requiresAuth: true }, // 需要登录
  },
  {
    path: '/frontend/profile',
    name: 'FrontendProfile',
    component: FrontendProfile,
    meta: { requiresAuth: true }, // 需要登录
  },
  {
    path: '/frontend/review/:orderId',
    name: 'FrontendReview',
    component: () => import('../views/frontend/Review.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/frontend/reviews',
    name: 'FrontendReviews',
    component: () => import('../views/frontend/Reviews.vue'),
  },
  {
    path: '/frontend/reviews/tracking',
    name: 'FrontendTrackingReviews',
    component: () => import('../views/frontend/TrackingReviews.vue'),
  },
  {
    path: '/frontend/lottery',
    name: 'FrontendLottery',
    component: () => import('../views/frontend/Lottery.vue'),
  },
  {
    path: '/frontend/dishes/:dishId/reviews',
    name: 'FrontendDishReviews',
    component: () => import('../views/frontend/DishReviews.vue'),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// 路由守卫：检查登录状态
router.beforeEach((to, from, next) => {
  // 后台登录页面：如果已登录，跳转到仪表盘
  if (to.path === '/admin/login') {
    // 后台使用 sessionStorage，确保前后台完全隔离
    const token = sessionStorage.getItem('admin_token');
    if (token) {
      // 已登录，跳转到仪表盘
      next('/admin/dashboard');
    } else {
      // 未登录，允许访问登录页
      next();
    }
    return;
  }

  // 检查是否是后台路由（统一以 /admin/ 开头，但排除 /admin/login）
  const isAdminRoute = to.path.startsWith('/admin/') && to.path !== '/admin/login';

  if (isAdminRoute) {
    // 后台使用 sessionStorage，确保前后台完全隔离
    const token = sessionStorage.getItem('admin_token');
    if (!token) {
      // 没有 token，跳转到后台登录页
      next('/admin/login');
    } else {
      // 有 token，允许访问（后端会验证 token 有效性）
      // 如果 token 无效，API 会返回 401，响应拦截器会处理
      next();
    }
  } else {
    // 前台路由
    // 检查是否需要登录（预约和我的页面）
    const requiresAuth = to.meta.requiresAuth === true;
    const isFrontendLogin = to.path === '/frontend/login';
    
    if (requiresAuth && !isFrontendLogin) {
      const token = localStorage.getItem('token');
      if (!token) {
        // 未登录，跳转到前台登录页，并保存当前路径用于登录后跳转
        next({
          path: '/frontend/login',
          query: { redirect: to.fullPath },
        });
      } else {
        // 已登录，允许访问
        next();
      }
    } else if (isFrontendLogin) {
      // 前台登录页：如果已登录，跳转到首页或redirect页面
      const token = localStorage.getItem('token');
      if (token) {
        const redirect = (to.query.redirect as string) || '/';
        next(redirect);
      } else {
        next();
      }
    } else {
      // 不需要登录的前台路由，直接允许访问
      next();
    }
  }
});

export default router;
