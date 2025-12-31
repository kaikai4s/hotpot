/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

# 火锅店小程序系统 - 技术设计文档 (TSD)

## 1. 背景与目标 (Background & Goals)

### 1.1 业务与技术痛点

**业务痛点：**
- 传统电话预约方式效率低下，高峰期占线率高，客户体验差
- 客户无法提前了解菜品信息和评价，影响点餐决策
- 缺乏客户数据沉淀，无法进行精准营销和客户关系管理
- 排队等待体验差，客户流失率高
- 无法实时掌握餐厅运营数据，影响经营决策

**技术痛点：**
- 需要同时支持微信小程序前端和Web管理后台
- 预约系统需要处理高并发场景（用餐高峰期）
- 评价系统需要防止刷评和恶意评价
- 需要与微信支付、微信登录等第三方服务集成
- 数据安全与用户隐私保护要求

### 1.2 本需求明确解决的问题

1. **在线预约系统**：实现桌位可视化选择、时间段预约、预约管理
2. **菜品评价系统**：支持菜品评分、文字评价、图片上传、评价展示
3. **管理后台系统**：提供预约管理、评价审核、数据统计、菜品管理等功能
4. **创新功能**：
   - 排队叫号系统（实时队列管理）
   - 会员积分系统（消费积分、积分兑换）
   - 智能推荐系统（基于评价和消费习惯）
   - 优惠券/活动推送（精准营销）
   - 桌位可视化选择（地图式选座）

### 1.3 架构目标

**可扩展性：**
- 支持多门店扩展（未来可能连锁经营）
- 支持多租户架构（为其他餐厅提供SaaS服务）
- 微服务化设计，各模块可独立扩展

**可维护性：**
- 前后端分离，职责清晰
- RESTful API设计，接口标准化
- 完善的日志和监控体系

**可审计性：**
- 所有关键操作记录审计日志
- 数据变更可追溯
- 支持合规性审计

**非功能性需求（NFR）：**
- 响应时间：API响应时间 < 200ms（P95）
- 可用性：99.9%可用性目标
- 并发能力：支持1000+并发用户
- 数据一致性：强一致性要求（预约、订单）

### 1.4 Out of Scope（本期不解决但已识别的问题）

- 多门店管理（本期仅支持单门店）
- 外卖配送功能
- 实时视频监控集成
- 人脸识别会员系统
- 供应链管理系统
- 财务系统深度集成
- 第三方外卖平台对接（美团、饿了么）

---

## 2. 核心接口定义 (API / Service Contracts)

### 2.1 预约服务接口

#### 2.1.1 获取可用桌位列表
**接口名称：** `GET /api/v1/reservations/tables`

**职责：** 根据日期、时间段、人数返回可用桌位列表

**输入参数：**
- `date` (string, 必填): 预约日期，格式 YYYY-MM-DD
- `time_slot` (string, 必填): 时间段，格式 HH:mm（如 "18:00"）
- `guest_count` (integer, 必填): 用餐人数，范围 1-20
- `duration` (integer, 可选): 预计用餐时长（分钟），默认120

**输出结构：**
```json
{
  "code": 200,
  "message": "success",
  "data": {
    "tables": [
      {
        "id": 1,
        "name": "A01",
        "capacity": 4,
        "type": "window", // window, corner, center
        "status": "available", // available, reserved, occupied
        "position": {
          "x": 10,
          "y": 20
        }
      }
    ],
    "available_count": 5,
    "total_count": 20
  }
}
```

**失败响应：**
- `400`: 参数校验失败
- `500`: 系统错误

**调用方：** 微信小程序前端
**被调用方：** 预约服务模块

**幂等性：** 查询接口，天然幂等
**副作用：** 无

---

#### 2.1.2 创建预约
**接口名称：** `POST /api/v1/reservations`

**职责：** 创建新的预约记录

**输入参数：**
- `table_id` (integer, 必填): 桌位ID
- `date` (string, 必填): 预约日期
- `time_slot` (string, 必填): 时间段
- `guest_count` (integer, 必填): 用餐人数
- `contact_name` (string, 必填): 联系人姓名
- `contact_phone` (string, 必填): 联系电话
- `special_requests` (string, 可选): 特殊需求
- `idempotency_key` (string, 必填): 幂等性密钥，客户端生成UUID

**输出结构：**
```json
{
  "code": 201,
  "message": "预约成功",
  "data": {
    "reservation_id": 12345,
    "reservation_code": "RES2026010112345",
    "status": "pending", // pending, confirmed, cancelled, completed
    "table_name": "A01",
    "date": "2026-01-01",
    "time_slot": "18:00",
    "expires_at": "2026-01-01 17:50:00" // 15分钟内需确认
  }
}
```

**失败响应：**
- `400`: 参数错误、桌位不可用、时间冲突
- `409`: 重复预约（基于idempotency_key）
- `429`: 请求过于频繁（限流）
- `500`: 系统错误

**调用方：** 微信小程序前端
**被调用方：** 预约服务模块

**幂等性：** 通过 `idempotency_key` 保证，相同key在15分钟内返回相同结果
**副作用：** 创建预约记录，占用桌位资源，发送通知

**失败时责任归属：** 
- 参数错误：客户端责任
- 资源冲突：服务端责任，返回具体冲突原因
- 系统错误：服务端责任，记录错误日志

---

#### 2.1.3 确认预约
**接口名称：** `PUT /api/v1/reservations/{reservation_id}/confirm`

**职责：** 确认预约（通常在15分钟内需要确认）

**输入参数：**
- `reservation_id` (integer, 路径参数): 预约ID
- `verification_code` (string, 可选): 验证码（如需要）

**输出结构：**
```json
{
  "code": 200,
  "message": "预约已确认",
  "data": {
    "reservation_id": 12345,
    "status": "confirmed",
    "confirmed_at": "2026-01-01 10:30:00"
  }
}
```

**失败响应：**
- `404`: 预约不存在
- `409`: 预约已过期或状态不允许确认
- `500`: 系统错误

**幂等性：** 多次确认返回相同结果（状态已确认）

---

#### 2.1.4 取消预约
**接口名称：** `PUT /api/v1/reservations/{reservation_id}/cancel`

**职责：** 取消预约

**输入参数：**
- `reservation_id` (integer, 路径参数): 预约ID
- `reason` (string, 可选): 取消原因

**输出结构：**
```json
{
  "code": 200,
  "message": "预约已取消",
  "data": {
    "reservation_id": 12345,
    "status": "cancelled",
    "cancelled_at": "2026-01-01 10:35:00"
  }
}
```

**失败响应：**
- `404`: 预约不存在
- `409`: 预约状态不允许取消（如已完成）
- `500`: 系统错误

---

### 2.2 评价服务接口

#### 2.2.1 提交菜品评价
**接口名称：** `POST /api/v1/reviews`

**职责：** 提交菜品评价

**输入参数：**
- `order_id` (integer, 必填): 订单ID（验证是否已消费）
- `dish_id` (integer, 必填): 菜品ID
- `rating` (integer, 必填): 评分，范围1-5
- `content` (string, 可选): 评价内容，最大500字
- `images` (array, 可选): 图片URL数组，最多3张
- `tags` (array, 可选): 标签数组，如 ["好吃", "分量足"]

**输出结构：**
```json
{
  "code": 201,
  "message": "评价提交成功",
  "data": {
    "review_id": 789,
    "status": "pending", // pending, approved, rejected
    "submitted_at": "2026-01-01 20:30:00"
  }
}
```

**失败响应：**
- `400`: 参数错误、评分范围错误
- `403`: 未消费该菜品，无权评价
- `409`: 已评价过该菜品
- `429`: 提交过于频繁（防刷评）
- `500`: 系统错误

**调用方：** 微信小程序前端
**被调用方：** 评价服务模块

**幂等性：** 同一用户对同一菜品只能评价一次（基于order_id + dish_id）
**副作用：** 创建评价记录，触发审核流程，更新菜品评分

**防刷评策略：**
- 基于订单验证（必须消费过才能评价）
- 同一订单同一菜品只能评价一次
- 频率限制：同一用户1小时内最多提交5条评价
- 内容审核：敏感词过滤，图片审核

---

#### 2.2.2 获取菜品评价列表
**接口名称：** `GET /api/v1/dishes/{dish_id}/reviews`

**职责：** 获取指定菜品的评价列表

**输入参数：**
- `dish_id` (integer, 路径参数): 菜品ID
- `page` (integer, 可选): 页码，默认1
- `page_size` (integer, 可选): 每页数量，默认10，最大50
- `sort` (string, 可选): 排序方式，可选值：latest（最新）、rating_desc（评分降序）、rating_asc（评分升序）

**输出结构：**
```json
{
  "code": 200,
  "message": "success",
  "data": {
    "reviews": [
      {
        "id": 789,
        "user_nickname": "用户***",
        "avatar": "https://...",
        "rating": 5,
        "content": "很好吃，推荐！",
        "images": ["https://..."],
        "tags": ["好吃", "分量足"],
        "created_at": "2026-01-01 20:30:00",
        "helpful_count": 5
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 10,
      "total_count": 95,
      "page_size": 10
    },
    "summary": {
      "average_rating": 4.5,
      "total_reviews": 95,
      "rating_distribution": {
        "5": 60,
        "4": 25,
        "3": 8,
        "2": 1,
        "1": 1
      }
    }
  }
}
```

**失败响应：**
- `404`: 菜品不存在
- `500`: 系统错误

**幂等性：** 查询接口，天然幂等

---

### 2.3 排队叫号服务接口

#### 2.3.1 加入排队
**接口名称：** `POST /api/v1/queue/join`

**职责：** 用户加入排队队列

**输入参数：**
- `guest_count` (integer, 必填): 用餐人数
- `table_type` (string, 可选): 桌位类型偏好，可选值：window, corner, center, any

**输出结构：**
```json
{
  "code": 201,
  "message": "排队成功",
  "data": {
    "queue_id": 456,
    "queue_number": "A123",
    "current_position": 5,
    "estimated_wait_time": 30, // 分钟
    "status": "waiting" // waiting, called, cancelled, seated
  }
}
```

**失败响应：**
- `400`: 参数错误
- `429`: 已在队列中
- `500`: 系统错误

**幂等性：** 同一用户只能有一个有效排队记录

---

#### 2.3.2 查询排队状态
**接口名称：** `GET /api/v1/queue/{queue_id}`

**职责：** 查询排队状态和位置

**输出结构：**
```json
{
  "code": 200,
  "message": "success",
  "data": {
    "queue_id": 456,
    "queue_number": "A123",
    "current_position": 3,
    "ahead_count": 2,
    "estimated_wait_time": 15,
    "status": "waiting"
  }
}
```

---

### 2.4 会员积分服务接口

#### 2.4.1 获取积分信息
**接口名称：** `GET /api/v1/members/points`

**职责：** 获取当前用户积分信息

**输出结构：**
```json
{
  "code": 200,
  "message": "success",
  "data": {
    "total_points": 1500,
    "available_points": 1200,
    "frozen_points": 300,
    "level": "gold", // bronze, silver, gold, platinum
    "points_to_next_level": 500
  }
}
```

---

#### 2.4.2 积分兑换
**接口名称：** `POST /api/v1/members/points/redeem`

**职责：** 使用积分兑换优惠券或商品

**输入参数：**
- `reward_id` (integer, 必填): 奖励ID
- `idempotency_key` (string, 必填): 幂等性密钥

**输出结构：**
```json
{
  "code": 200,
  "message": "兑换成功",
  "data": {
    "coupon_id": 789,
    "points_used": 500,
    "remaining_points": 700
  }
}
```

**幂等性：** 通过idempotency_key保证

---

### 2.5 管理后台接口（示例）

#### 2.5.1 获取预约列表
**接口名称：** `GET /api/admin/v1/reservations`

**职责：** 管理员查看预约列表

**认证：** Bearer Token（JWT）

**输入参数：**
- `status` (string, 可选): 状态筛选
- `date` (string, 可选): 日期筛选
- `page` (integer, 可选): 页码
- `page_size` (integer, 可选): 每页数量

**输出结构：**
```json
{
  "code": 200,
  "data": {
    "reservations": [...],
    "pagination": {...}
  }
}
```

---

#### 2.5.2 审核评价
**接口名称：** `PUT /api/admin/v1/reviews/{review_id}/approve`

**职责：** 管理员审核评价

**输入参数：**
- `review_id` (integer, 路径参数)
- `action` (string, 必填): approve 或 reject
- `reason` (string, 可选): 拒绝原因

**输出结构：**
```json
{
  "code": 200,
  "message": "审核完成",
  "data": {
    "review_id": 789,
    "status": "approved"
  }
}
```

---

## 3. 数据库 / 数据结构设计 (Schema Design)

### 3.1 核心表结构

#### 3.1.1 用户表 (users)
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    openid VARCHAR(64) UNIQUE NOT NULL COMMENT '微信OpenID',
    unionid VARCHAR(64) NULL COMMENT '微信UnionID',
    nickname VARCHAR(64) NULL COMMENT '昵称',
    avatar_url VARCHAR(255) NULL COMMENT '头像URL',
    phone VARCHAR(20) NULL COMMENT '手机号',
    gender TINYINT NULL COMMENT '性别：0未知，1男，2女',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_openid (openid),
    INDEX idx_unionid (unionid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**约束说明：**
- `openid` 唯一索引，用于微信登录识别
- `unionid` 可为空，用于多应用统一用户识别

**并发假设：**
- 微信登录时可能出现并发创建，使用 `INSERT ... ON DUPLICATE KEY UPDATE` 或分布式锁

---

#### 3.1.2 桌位表 (tables)
```sql
CREATE TABLE tables (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL COMMENT '桌位名称，如A01',
    capacity INT UNSIGNED NOT NULL COMMENT '容纳人数',
    type ENUM('window', 'corner', 'center') NOT NULL COMMENT '桌位类型',
    position_x INT UNSIGNED NULL COMMENT 'X坐标（用于可视化）',
    position_y INT UNSIGNED NULL COMMENT 'Y坐标（用于可视化）',
    status ENUM('available', 'reserved', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**约束说明：**
- `name` 唯一性约束（业务层保证）
- `status` 状态机：available → reserved → occupied → available

**并发假设：**
- 预约时可能出现并发占用，使用乐观锁（version字段）或悲观锁（SELECT FOR UPDATE）

---

#### 3.1.3 预约表 (reservations)
```sql
CREATE TABLE reservations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    reservation_code VARCHAR(32) UNIQUE NOT NULL COMMENT '预约编码',
    user_id BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    table_id INT UNSIGNED NOT NULL COMMENT '桌位ID',
    date DATE NOT NULL COMMENT '预约日期',
    time_slot TIME NOT NULL COMMENT '时间段',
    guest_count INT UNSIGNED NOT NULL COMMENT '用餐人数',
    contact_name VARCHAR(64) NOT NULL COMMENT '联系人姓名',
    contact_phone VARCHAR(20) NOT NULL COMMENT '联系电话',
    special_requests TEXT NULL COMMENT '特殊需求',
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'expired') DEFAULT 'pending',
    idempotency_key VARCHAR(64) NULL COMMENT '幂等性密钥',
    expires_at TIMESTAMP NULL COMMENT '过期时间（pending状态）',
    confirmed_at TIMESTAMP NULL COMMENT '确认时间',
    cancelled_at TIMESTAMP NULL COMMENT '取消时间',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_table_id (table_id),
    INDEX idx_date_time (date, time_slot),
    INDEX idx_status (status),
    INDEX idx_idempotency_key (idempotency_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**约束说明：**
- `reservation_code` 唯一索引，格式：RES + YYYYMMDD + 序号
- `idempotency_key` 唯一索引，用于幂等性控制
- 外键约束保证数据完整性

**并发假设：**
- 同一桌位同一时间段只能有一个confirmed状态的预约
- 使用数据库唯一索引或应用层分布式锁保证

**数据增长预期：**
- 单日预约量：100-500条
- 年增长：约10万条记录
- 建议：按年分表或归档历史数据

**向后兼容性：**
- 新增字段使用 `NULL` 默认值
- 状态枚举值只能新增，不能删除

---

#### 3.1.4 菜品表 (dishes)
```sql
CREATE TABLE dishes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL COMMENT '菜品名称',
    description TEXT NULL COMMENT '菜品描述',
    price DECIMAL(10, 2) NOT NULL COMMENT '价格',
    image_url VARCHAR(255) NULL COMMENT '主图URL',
    category_id INT UNSIGNED NOT NULL COMMENT '分类ID',
    status ENUM('available', 'sold_out', 'disabled') DEFAULT 'available',
    average_rating DECIMAL(3, 2) DEFAULT 0.00 COMMENT '平均评分',
    review_count INT UNSIGNED DEFAULT 0 COMMENT '评价数量',
    sales_count INT UNSIGNED DEFAULT 0 COMMENT '销量',
    sort_order INT UNSIGNED DEFAULT 0 COMMENT '排序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_id (category_id),
    INDEX idx_status (status),
    INDEX idx_average_rating (average_rating),
    FOREIGN KEY (category_id) REFERENCES dish_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**约束说明：**
- `average_rating` 和 `review_count` 通过触发器或应用层维护，保证一致性

**并发假设：**
- 评价提交时更新 `average_rating`，使用乐观锁或CAS操作

---

#### 3.1.5 评价表 (reviews)
```sql
CREATE TABLE reviews (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    order_id BIGINT UNSIGNED NOT NULL COMMENT '订单ID',
    dish_id INT UNSIGNED NOT NULL COMMENT '菜品ID',
    rating TINYINT UNSIGNED NOT NULL COMMENT '评分：1-5',
    content TEXT NULL COMMENT '评价内容',
    images JSON NULL COMMENT '图片URL数组',
    tags JSON NULL COMMENT '标签数组',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    helpful_count INT UNSIGNED DEFAULT 0 COMMENT '有用数',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL COMMENT '审核时间',
    INDEX idx_user_id (user_id),
    INDEX idx_dish_id (dish_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_rating (rating),
    UNIQUE KEY uk_user_order_dish (user_id, order_id, dish_id) COMMENT '同一用户同一订单同一菜品只能评价一次',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**约束说明：**
- `uk_user_order_dish` 唯一索引保证同一用户对同一订单的同一菜品只能评价一次
- `images` 和 `tags` 使用JSON类型存储数组

**并发假设：**
- 评价提交时检查唯一索引，冲突返回409错误

**数据增长预期：**
- 单日评价量：50-200条
- 年增长：约5万条记录

---

#### 3.1.6 排队表 (queue)
```sql
CREATE TABLE queue (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    queue_number VARCHAR(20) UNIQUE NOT NULL COMMENT '排队号',
    user_id BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    guest_count INT UNSIGNED NOT NULL COMMENT '用餐人数',
    table_type VARCHAR(20) NULL COMMENT '桌位类型偏好',
    position INT UNSIGNED NOT NULL COMMENT '当前位置',
    status ENUM('waiting', 'called', 'cancelled', 'seated') DEFAULT 'waiting',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '加入时间',
    called_at TIMESTAMP NULL COMMENT '叫号时间',
    seated_at TIMESTAMP NULL COMMENT '入座时间',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_position (position),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**约束说明：**
- `queue_number` 唯一索引，格式：A/B/C + 序号
- `position` 用于排序，越小越靠前

**并发假设：**
- 加入排队时分配position，使用事务和锁保证原子性

---

#### 3.1.7 会员积分表 (member_points)
```sql
CREATE TABLE member_points (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    total_points INT UNSIGNED DEFAULT 0 COMMENT '总积分',
    available_points INT UNSIGNED DEFAULT 0 COMMENT '可用积分',
    frozen_points INT UNSIGNED DEFAULT 0 COMMENT '冻结积分',
    level ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_id (user_id),
    INDEX idx_level (level),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**约束说明：**
- `total_points = available_points + frozen_points`（应用层保证）

---

#### 3.1.8 积分流水表 (point_transactions)
```sql
CREATE TABLE point_transactions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    type ENUM('earn', 'redeem', 'expire', 'adjust') NOT NULL COMMENT '类型',
    points INT NOT NULL COMMENT '积分变动（正数为增加，负数为减少）',
    balance_after INT UNSIGNED NOT NULL COMMENT '变动后余额',
    source_type VARCHAR(50) NULL COMMENT '来源类型：order, review, redeem等',
    source_id BIGINT UNSIGNED NULL COMMENT '来源ID',
    description VARCHAR(255) NULL COMMENT '描述',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**约束说明：**
- 积分变动必须记录流水，用于审计和对账

---

### 3.2 数据一致性假设

**强一致性要求：**
- 预约创建：桌位状态变更与预约记录创建必须在同一事务中
- 积分变动：积分余额更新与流水记录必须在同一事务中

**最终一致性允许：**
- 菜品评分更新：评价提交后异步更新菜品平均评分
- 统计数据：日/周/月统计数据可延迟计算

**并发控制策略：**
- 数据库事务 + 行锁（SELECT FOR UPDATE）
- 分布式锁（Redis）用于跨服务操作
- 乐观锁（version字段）用于高并发读多写少场景

---

### 3.3 数据迁移风险

**向后兼容性：**
- 新增字段使用 `NULL` 默认值或默认值
- 枚举值只能新增，不能删除
- 表结构变更使用迁移脚本，支持回滚

**数据迁移场景：**
- 历史预约数据归档（按年）
- 评价数据归档（超过1年的数据）
- 积分流水数据归档（超过2年的数据）

---

## 4. 前端与交互入口

### 4.1 微信小程序前端

**技术栈：**
- 框架：微信小程序原生框架 + WeUI组件库
- 状态管理：全局状态管理（App.globalData）
- 网络请求：wx.request封装
- 动画：CSS3动画 + 微信小程序动画API

**功能入口：**
1. **首页**：菜品展示、轮播图、推荐菜品
2. **预约入口**：首页Banner、底部导航"预约"
3. **评价入口**：菜品详情页、订单详情页、"我的"页面
4. **排队入口**：首页快捷入口、预约页面
5. **会员中心**：底部导航"我的"

**与后端契约边界：**
- 所有数据通过RESTful API获取
- 图片上传使用OSS（阿里云/腾讯云）
- 支付使用微信支付API
- 登录使用微信登录API

**性能与渲染假设：**
- 首屏加载时间 < 2秒
- 图片懒加载
- 列表数据分页加载
- 关键数据本地缓存（如用户信息）

**动画效果：**
- 页面切换动画（滑动、淡入淡出）
- 列表项加载动画（骨架屏）
- 按钮点击反馈动画
- 评价提交成功动画（弹窗 + 粒子效果）
- 排队位置变化动画（数字滚动）

---

### 4.2 Web管理后台

**技术栈：**
- 框架：Laravel + Vue.js 3 + Element Plus
- 构建工具：Vite
- 状态管理：Pinia
- 路由：Vue Router

**功能入口：**
1. **仪表盘**：数据概览、今日预约、待审核评价
2. **预约管理**：预约列表、预约详情、预约统计
3. **评价管理**：评价列表、评价审核、评价统计
4. **菜品管理**：菜品列表、菜品编辑、分类管理
5. **会员管理**：会员列表、积分管理
6. **系统设置**：桌位管理、参数配置

**与后端契约边界：**
- 通过RESTful API与Laravel后端通信
- 文件上传使用Laravel文件存储
- 认证使用JWT Token

**性能与渲染假设：**
- 首屏加载时间 < 1.5秒
- 表格数据虚拟滚动（大数据量）
- 图表使用ECharts
- 实时数据更新使用WebSocket（可选）

---

## 5. 核心业务流程 (Business Flow)

### 5.1 预约流程

**正常流程：**
1. 用户打开小程序，进入预约页面
2. 选择日期、时间段、用餐人数
3. 系统返回可用桌位列表（可视化展示）
4. 用户选择桌位，填写联系人信息
5. 提交预约请求（携带idempotency_key）
6. 系统检查：
   - 桌位是否可用
   - 时间段是否冲突
   - 幂等性检查
7. 创建预约记录（状态：pending）
8. 占用桌位资源（状态：reserved）
9. 发送确认通知（微信模板消息）
10. 用户15分钟内确认预约
11. 预约状态变更为confirmed
12. 预约日期前1小时发送提醒通知

**异常流程：**
- **桌位不可用**：返回400错误，提示用户选择其他桌位
- **时间冲突**：返回400错误，提示用户选择其他时间段
- **重复提交**：基于idempotency_key返回409错误，返回已有预约信息
- **15分钟内未确认**：定时任务将状态变更为expired，释放桌位资源
- **用户取消**：状态变更为cancelled，释放桌位资源，发送取消通知

**外部系统交互：**
- 微信模板消息服务（预约确认、提醒、取消通知）
- 短信服务（可选，用于重要通知）

**关键状态变更点：**
- pending → confirmed（用户确认）
- pending → expired（超时未确认）
- confirmed → cancelled（用户取消）
- confirmed → completed（用餐完成）

---

### 5.2 评价流程

**正常流程：**
1. 用户完成订单后，进入订单详情页
2. 点击"评价"按钮，选择菜品
3. 填写评分、评价内容、上传图片（可选）
4. 提交评价请求
5. 系统检查：
   - 订单是否存在且已完成
   - 是否已评价过该菜品
   - 频率限制检查
6. 创建评价记录（状态：pending）
7. 内容审核（敏感词过滤、图片审核）
8. 管理员审核（可选，根据配置）
9. 审核通过后，状态变更为approved
10. 更新菜品平均评分和评价数量
11. 奖励积分（如配置）
12. 通知商家（新评价通知）

**异常流程：**
- **未消费**：返回403错误，提示用户需消费后才能评价
- **已评价**：返回409错误，提示用户已评价过
- **频率限制**：返回429错误，提示用户稍后再试
- **内容违规**：自动拒绝，状态变更为rejected，通知用户
- **审核拒绝**：状态变更为rejected，通知用户拒绝原因

**外部系统交互：**
- 内容审核服务（腾讯云内容安全 / 阿里云内容安全）
- 图片审核服务（OSS图片审核）

**关键状态变更点：**
- pending → approved（审核通过）
- pending → rejected（审核拒绝）

---

### 5.3 排队叫号流程

**正常流程：**
1. 用户进入排队页面
2. 选择用餐人数、桌位类型偏好
3. 提交排队请求
4. 系统检查用户是否已在队列中
5. 分配排队号（A/B/C + 序号）
6. 计算当前位置和预计等待时间
7. 创建排队记录（状态：waiting）
8. 实时更新排队位置（WebSocket推送或轮询）
9. 有桌位可用时，系统自动叫号
10. 发送叫号通知（微信模板消息）
11. 用户到店，状态变更为seated
12. 分配桌位，更新桌位状态

**异常流程：**
- **已在队列中**：返回429错误，返回当前排队信息
- **用户取消排队**：状态变更为cancelled，从队列中移除
- **超时未到店**：叫号后15分钟未到店，自动取消，重新叫下一位

**外部系统交互：**
- 微信模板消息服务（叫号通知）

**关键状态变更点：**
- waiting → called（叫号）
- called → seated（入座）
- waiting/called → cancelled（取消）

---

### 5.4 积分流程

**正常流程：**
1. 用户完成订单，系统计算积分
2. 创建积分流水记录（type: earn）
3. 更新用户积分余额
4. 检查积分等级，如达到升级条件则升级
5. 用户查看积分商城，选择兑换商品
6. 提交兑换请求（携带idempotency_key）
7. 系统检查：
   - 积分是否足够
   - 商品库存是否充足
   - 幂等性检查
8. 冻结积分（frozen_points增加）
9. 创建积分流水记录（type: redeem）
10. 生成优惠券或商品券
11. 通知用户兑换成功

**异常流程：**
- **积分不足**：返回400错误，提示用户
- **库存不足**：返回400错误，提示用户
- **重复兑换**：基于idempotency_key返回409错误

**关键状态变更点：**
- 积分变动必须记录流水
- 积分等级根据总积分自动计算

---

## 6. 边缘情况与一致性问题 (Edge Cases & Consistency)

### 6.1 并发问题

**场景1：同一桌位同一时间段并发预约**
- **问题**：两个用户同时预约同一桌位
- **解决方案**：
  - 使用数据库唯一索引（table_id + date + time_slot + status='confirmed'）
  - 或使用分布式锁（Redis）锁定桌位资源
  - 返回409冲突错误给后提交的用户

**场景2：评价提交并发**
- **问题**：同一用户对同一菜品多次提交评价
- **解决方案**：
  - 数据库唯一索引（user_id + order_id + dish_id）
  - 应用层检查，返回409错误

**场景3：积分变动并发**
- **问题**：订单完成和积分兑换同时发生
- **解决方案**：
  - 使用数据库事务 + 行锁
  - 或使用分布式锁（Redis）锁定用户积分

---

### 6.2 幂等性与去重策略

**预约创建：**
- 使用 `idempotency_key`（客户端生成UUID）
- 服务端缓存15分钟，相同key返回相同结果
- 数据库唯一索引保证

**积分兑换：**
- 使用 `idempotency_key`
- 服务端缓存1小时
- 数据库唯一索引（user_id + reward_id + idempotency_key）

**评价提交：**
- 基于业务唯一性（user_id + order_id + dish_id）
- 数据库唯一索引保证

---

### 6.3 数据竞争与唯一性冲突

**排队号分配：**
- 使用数据库自增ID + 前缀生成排队号
- 或使用Redis原子操作生成序号
- 保证唯一性

**预约编码生成：**
- 格式：RES + YYYYMMDD + 6位序号
- 使用数据库自增ID或Redis原子操作
- 数据库唯一索引保证

---

### 6.4 异常中断后的恢复假设

**预约创建中断：**
- 如果已创建预约记录但未占用桌位：定时任务清理pending状态超过15分钟的记录
- 如果已占用桌位但未创建记录：定时任务释放超时未确认的桌位

**评价提交中断：**
- 如果已创建评价记录但未更新菜品评分：定时任务补偿更新
- 如果已更新评分但未创建记录：基于订单数据重新计算评分

**积分变动中断：**
- 使用事务保证原子性
- 如果事务失败，回滚所有操作
- 提供对账接口，检查积分余额与流水一致性

---

## 7. 非功能性需求（Non-Functional Requirements）

### 7.1 性能（Performance）

**API响应时间：**
- P50（中位数）：< 100ms
- P95：< 200ms
- P99：< 500ms

**数据库查询：**
- 单表查询：< 50ms
- 联表查询：< 100ms
- 复杂统计查询：< 500ms（可异步）

**前端性能：**
- 小程序首屏加载：< 2秒
- Web后台首屏加载：< 1.5秒
- 图片加载：使用CDN，支持WebP格式

**优化策略：**
- 数据库索引优化
- Redis缓存热点数据（菜品列表、评价列表）
- 静态资源CDN加速
- 数据库读写分离（如需要）

---

### 7.2 可用性（Availability）

**目标：** 99.9%可用性（年停机时间 < 8.76小时）

**策略：**
- 服务高可用部署（多实例）
- 数据库主从复制（读写分离）
- Redis集群（缓存高可用）
- 负载均衡（Nginx）
- 健康检查与自动故障转移
- 监控告警（Prometheus + Grafana）

**降级策略：**
- 缓存降级：缓存不可用时直接查询数据库
- 功能降级：非核心功能可暂时关闭
- 限流降级：高并发时限制非核心接口

---

### 7.3 可扩展性（Scalability）

**水平扩展：**
- 应用服务器无状态设计，支持水平扩展
- 数据库分库分表（按年分表，如需要）
- Redis集群扩展
- 负载均衡自动扩容

**垂直扩展：**
- 数据库服务器可升级配置
- 应用服务器可升级配置

**扩展指标：**
- 支持1000+并发用户
- 支持10万+日活用户
- 数据库支持1000万+记录

---

### 7.4 可维护性（Maintainability）

**代码质量：**
- 遵循PSR-12编码规范
- 代码覆盖率 > 70%
- 代码审查流程
- 技术文档完善

**部署与运维：**
- Docker容器化部署
- CI/CD自动化部署
- 日志集中管理（ELK Stack）
- 监控告警体系

**文档：**
- API文档（Swagger/OpenAPI）
- 数据库设计文档
- 部署运维文档
- 故障处理手册

---

### 7.5 可测试性（Testability）

**单元测试：**
- 业务逻辑单元测试覆盖率 > 70%
- 使用PHPUnit进行测试
- Mock外部依赖

**集成测试：**
- API接口集成测试
- 数据库操作集成测试
- 使用Laravel测试框架

**端到端测试：**
- 关键业务流程E2E测试
- 使用自动化测试工具

**测试环境：**
- 开发环境（Dev）
- 测试环境（Test）
- 预发布环境（Staging）
- 生产环境（Prod）

---

## 8. 信息安全、合规与审计 (Security, Compliance & Audit)

### 8.1 认证 / 授权边界

**微信小程序认证：**
- 使用微信登录API获取openid
- 服务端验证openid有效性
- 生成JWT Token返回客户端
- Token有效期：7天（可配置）
- Token刷新机制

**Web管理后台认证：**
- 用户名密码登录
- 生成JWT Token
- Token有效期：2小时
- 支持记住我功能（延长Token有效期）

**授权：**
- 基于角色的访问控制（RBAC）
- 管理员角色：super_admin, admin, operator
- 权限定义在 `acl.xml`（Laravel）
- API接口使用中间件检查权限

---

### 8.2 敏感数据处理原则

**用户隐私数据：**
- 手机号：加密存储（使用Laravel加密）
- 微信OpenID：哈希存储（可选）
- 评价内容：敏感词过滤
- 图片：内容审核

**数据脱敏：**
- 日志中不记录完整手机号（只显示前3位和后4位）
- 评价列表显示用户昵称（脱敏处理）
- 管理后台显示完整信息（需权限）

**数据加密：**
- 传输加密：HTTPS（TLS 1.2+）
- 存储加密：敏感字段使用Laravel加密
- 数据库连接加密

---

### 8.3 日志与审计要求

**业务审计日志：**
- 预约创建、确认、取消
- 评价提交、审核
- 积分变动（积分流水表）
- 管理员操作（登录、权限变更、数据修改）

**日志内容：**
- 操作时间
- 操作人（用户ID）
- 操作类型
- 操作对象（ID）
- 操作结果（成功/失败）
- IP地址
- User-Agent

**日志存储：**
- 数据库存储（audit_logs表）
- 文件日志（Laravel日志）
- 集中日志系统（ELK Stack）

**日志保留：**
- 业务日志：保留1年
- 审计日志：保留3年
- 访问日志：保留6个月

**可追溯性：**
- 所有关键操作可追溯到具体用户和时间
- 支持日志查询和导出
- 支持日志分析（异常行为检测）

---

### 8.4 第三方依赖信任假设

**微信服务：**
- 微信登录API：信任微信官方服务
- 微信支付API：信任微信支付服务
- 微信模板消息：信任微信消息服务

**云服务：**
- OSS存储服务：信任云服务商
- CDN服务：信任CDN服务商
- 内容审核服务：信任第三方审核服务

**风险缓解：**
- 第三方服务故障时的降级策略
- 数据备份（防止第三方数据丢失）
- 服务监控（监控第三方服务可用性）

---

### 8.5 合规性考虑

**用户隐私保护：**
- 遵循《个人信息保护法》
- 用户数据收集需获得同意
- 用户有权删除个人数据
- 数据跨境传输需合规（待确认）

**数据删除：**
- 用户注销：删除用户数据（保留必要的审计日志）
- 评价删除：软删除（标记删除，保留审计）
- 数据归档：超过保留期的数据归档

**用户同意：**
- 隐私政策同意
- 数据使用同意
- 营销推送同意（可选）

**GDPR合规（如适用）：**
- 数据可携带性（导出用户数据）
- 被遗忘权（删除用户数据）
- 数据泄露通知（72小时内）

---

## 9. 日志、监控与可观测性 (Observability)

### 9.1 关键业务事件记录

**预约事件：**
- 预约创建、确认、取消、完成
- 预约超时、桌位释放
- 预约统计（日/周/月）

**评价事件：**
- 评价提交、审核通过、审核拒绝
- 评价统计（菜品评分变化）
- 异常评价检测（刷评、恶意评价）

**积分事件：**
- 积分获得、消费、过期
- 积分等级变更
- 积分异常（负数、不一致）

**用户事件：**
- 用户注册、登录
- 用户行为（页面访问、功能使用）

---

### 9.2 成功 / 失败行为可观测性

**成功指标：**
- API成功率（> 99%）
- 预约成功率
- 评价提交成功率
- 支付成功率

**失败指标：**
- API错误率（< 1%）
- 4xx错误（客户端错误）
- 5xx错误（服务端错误）
- 超时错误

**监控告警：**
- API错误率 > 1%：告警
- 5xx错误 > 10次/分钟：告警
- 数据库连接失败：告警
- Redis连接失败：告警
- 磁盘空间 < 20%：告警

---

### 9.3 安全审计或风控分析能力

**异常行为检测：**
- 频繁登录失败（暴力破解检测）
- 异常IP访问（异地登录检测）
- 异常评价行为（刷评检测）
- 异常积分变动（积分异常检测）

**风控规则：**
- 同一IP 1小时内登录失败 > 5次：锁定账户1小时
- 同一用户1小时内提交评价 > 10条：限制提交
- 积分变动异常：人工审核

**审计报告：**
- 日/周/月安全审计报告
- 异常行为报告
- 数据访问报告

---

### 9.4 不记录内容与原因说明

**不记录内容：**
- 用户密码（哈希后也不记录）
- 支付密码
- 完整银行卡号
- 完整身份证号

**原因：**
- 安全合规要求
- 减少数据泄露风险
- 符合隐私保护法规

---

## 10. 验收标准 (Acceptance Criteria)

### 10.1 功能正确性

**预约功能：**
- Given: 用户选择可用桌位和时间段
- When: 提交预约请求
- Then: 成功创建预约，桌位状态变更为reserved，收到确认通知

**评价功能：**
- Given: 用户已完成订单
- When: 提交菜品评价
- Then: 成功创建评价记录，菜品评分更新，获得积分奖励（如配置）

**排队功能：**
- Given: 用户加入排队
- When: 有桌位可用
- Then: 收到叫号通知，排队状态更新

**积分功能：**
- Given: 用户完成订单
- When: 系统计算积分
- Then: 积分余额增加，记录积分流水

---

### 10.2 数据一致性

**预约一致性：**
- 同一桌位同一时间段只能有一个confirmed状态的预约
- 预约状态变更必须记录日志

**评价一致性：**
- 同一用户对同一订单的同一菜品只能评价一次
- 菜品评分 = SUM(评分) / COUNT(评价数)

**积分一致性：**
- 积分余额 = SUM(积分流水)
- 积分变动必须记录流水

---

### 10.3 性能 / 安全底线

**性能底线：**
- API响应时间 P95 < 200ms
- 数据库查询 < 100ms
- 小程序首屏加载 < 2秒

**安全底线：**
- 所有API必须认证
- 敏感数据加密存储
- 输入验证和输出转义
- SQL注入防护
- XSS防护

---

### 10.4 可测试性声明

**单元测试：**
- 业务逻辑单元测试覆盖率 > 70%
- 所有测试用例通过

**集成测试：**
- API接口集成测试通过
- 数据库操作测试通过

**端到端测试：**
- 关键业务流程E2E测试通过

---

## 11. 风险、限制与技术决策 (Risks & Trade-offs)

### 11.1 已识别风险

**高风险：**
1. **高并发预约冲突**：用餐高峰期可能出现大量并发预约请求
   - **影响**：数据不一致、用户体验差
   - **缓解**：分布式锁、数据库唯一索引、限流

2. **第三方服务依赖**：微信服务、支付服务、内容审核服务
   - **影响**：服务不可用时功能受影响
   - **缓解**：降级策略、服务监控、备用方案

3. **数据安全风险**：用户隐私数据泄露
   - **影响**：合规风险、用户信任
   - **缓解**：数据加密、访问控制、安全审计

**中风险：**
1. **数据库性能瓶颈**：数据量增长后查询性能下降
   - **影响**：响应时间变慢
   - **缓解**：索引优化、读写分离、分库分表

2. **评价刷评风险**：恶意用户刷好评或差评
   - **影响**：评价可信度下降
   - **缓解**：订单验证、频率限制、内容审核、人工审核

**低风险：**
1. **功能复杂度**：功能较多，开发周期可能延长
   - **影响**：项目延期
   - **缓解**：分阶段开发、优先级排序

---

### 11.2 架构取舍及原因

**取舍1：单体应用 vs 微服务**
- **选择**：初期采用单体应用（Laravel），未来可拆分为微服务
- **原因**：
  - 开发效率高，部署简单
  - 团队规模小，微服务复杂度高
  - 未来可根据需要拆分

**取舍2：同步更新 vs 异步更新**
- **选择**：关键数据同步更新（预约、积分），非关键数据异步更新（评分统计）
- **原因**：
  - 保证数据一致性
  - 提升用户体验
  - 降低系统复杂度

**取舍3：数据库事务 vs 最终一致性**
- **选择**：关键操作使用事务（预约创建、积分变动），非关键操作最终一致性（评分更新）
- **原因**：
  - 保证数据一致性
  - 性能考虑
  - 用户体验平衡

**取舍4：实时推送 vs 轮询**
- **选择**：关键通知实时推送（微信模板消息），非关键数据轮询（排队位置）
- **原因**：
  - 成本考虑（实时推送需要WebSocket）
  - 用户体验平衡
  - 系统复杂度

---

### 11.3 延后处理的问题与演进方向

**延后处理的问题：**
1. **多门店支持**：当前仅支持单门店，未来需要支持多门店
2. **外卖功能**：当前仅支持堂食，未来可能需要外卖功能
3. **实时视频监控**：未来可能需要集成监控系统
4. **AI推荐**：当前使用简单推荐算法，未来可使用机器学习

**演进方向：**
1. **微服务化**：将预约、评价、积分等服务拆分为独立服务
2. **大数据分析**：用户行为分析、菜品推荐、精准营销
3. **智能化**：AI客服、智能推荐、预测分析
4. **SaaS化**：为其他餐厅提供SaaS服务

---

## 12. 附录

### 12.1 术语表

- **OpenID**：微信用户唯一标识
- **UnionID**：微信多应用统一用户标识
- **幂等性**：同一操作执行多次结果相同
- **idempotency_key**：幂等性密钥，用于保证请求幂等性
- **P95/P99**：响应时间百分位数，P95表示95%的请求响应时间小于该值

### 12.2 参考文档

- [Laravel官方文档](https://laravel.com/docs)
- [微信小程序开发文档](https://developers.weixin.qq.com/miniprogram/dev/framework/)
- [微信支付开发文档](https://pay.weixin.qq.com/wiki/doc/apiv3/index.shtml)
- [RESTful API设计规范](https://restfulapi.net/)

### 12.3 版本历史

- **v1.0** (2026-01-01): 初始版本

---

**文档状态：** 待评审  
**最后更新：** 2026-01-01  
**文档所有者：** eBrook Group 架构团队

