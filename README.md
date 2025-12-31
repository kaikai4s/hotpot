# 火锅店小程序系统

## 项目结构

- `app/` - Laravel应用代码
- `database/migrations/` - 数据库迁移文件
- `resources/js/` - 前端代码（Vue3 + TypeScript）
- `routes/` - 路由配置

## 安装步骤

1. 安装PHP依赖
```bash
composer install
```

2. 安装前端依赖
```bash
npm install
```

3. 配置环境变量
```bash
cp .env.example .env
php artisan key:generate
```

4. 运行数据库迁移
```bash
php artisan migrate
```

5. 启动开发服务器
```bash
# 后端
php artisan serve

# 前端（新终端）
npm run dev
```

## API文档

### 小程序API (需要认证)
- `POST /api/v1/auth/wechat-login` - 微信登录
- `GET /api/v1/reservations/tables` - 获取可用桌位
- `POST /api/v1/reservations` - 创建预约
- `PUT /api/v1/reservations/{id}/confirm` - 确认预约
- `PUT /api/v1/reservations/{id}/cancel` - 取消预约
- `POST /api/v1/reviews` - 提交评价
- `GET /api/v1/dishes/{id}/reviews` - 获取菜品评价
- `POST /api/v1/queue/join` - 加入排队
- `GET /api/v1/queue/{id}` - 查询排队状态
- `GET /api/v1/members/points` - 获取积分信息
- `POST /api/v1/members/points/redeem` - 积分兑换

### 管理后台API (需要认证)
- `GET /api/admin/v1/reservations` - 获取预约列表
- `PUT /api/admin/v1/reviews/{id}/approve` - 审核评价

