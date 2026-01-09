# Requirements Document

## Introduction

本功能旨在升级火锅店小程序的会员权益体系，通过生日特权、会员日活动和积分商城丰富化三大模块，提升用户粘性和消费意愿，增强用户对会员身份的认同感和归属感。

## Glossary

- **Member_Privileges_System**: 会员权益系统，管理用户的各类会员特权
- **Birthday_Privilege**: 生日特权模块，处理用户生日相关的优惠和奖励
- **Member_Day**: 会员日模块，管理每月固定会员日的活动和折扣
- **Points_Mall**: 积分商城模块，管理积分兑换的商品和体验
- **User**: 系统用户，拥有会员身份
- **Coupon**: 优惠券，可用于订单抵扣
- **Physical_Product**: 实物商品，可通过积分兑换
- **Experience_Product**: 体验类商品，如VIP包间体验券

## Requirements

### Requirement 1: 生日特权 - 生日信息管理

**User Story:** As a 用户, I want 设置和管理我的生日信息, so that 我可以在生日时享受专属特权。

#### Acceptance Criteria

1. WHEN 用户首次进入会员中心且未设置生日 THEN THE Member_Privileges_System SHALL 提示用户设置生日信息
2. WHEN 用户设置生日日期 THEN THE Member_Privileges_System SHALL 验证日期格式有效性并保存
3. WHEN 用户已设置生日 THEN THE Member_Privileges_System SHALL 限制生日修改次数为每年1次
4. IF 用户尝试在同一年内第二次修改生日 THEN THE Member_Privileges_System SHALL 拒绝修改并提示原因
5. THE Member_Privileges_System SHALL 在用户生日前7天发送生日特权预告通知

### Requirement 2: 生日特权 - 双倍积分

**User Story:** As a 用户, I want 在生日当天消费获得双倍积分, so that 我感受到会员的尊贵待遇。

#### Acceptance Criteria

1. WHEN 用户在生日当天完成订单支付 THEN THE Member_Privileges_System SHALL 按双倍比例计算并发放积分
2. WHEN 计算生日双倍积分 THEN THE Member_Privileges_System SHALL 在原有会员等级倍数基础上再乘以2
3. THE Member_Privileges_System SHALL 在积分流水中标记生日双倍积分来源
4. WHEN 用户查看生日当天的积分记录 THEN THE Member_Privileges_System SHALL 显示"生日双倍"标识

### Requirement 3: 生日特权 - 生日优惠券

**User Story:** As a 用户, I want 在生日时收到专属优惠券, so that 我可以享受生日折扣。

#### Acceptance Criteria

1. WHEN 用户生日当天首次登录 THEN THE Member_Privileges_System SHALL 自动发放生日专属优惠券
2. THE Birthday_Privilege SHALL 根据用户会员等级发放不同面额的优惠券（青铜20元、白银30元、黄金50元、白金100元）
3. WHEN 生日优惠券发放成功 THEN THE Member_Privileges_System SHALL 发送通知告知用户
4. THE Birthday_Privilege SHALL 设置生日优惠券有效期为生日当天起30天内
5. IF 用户当年已领取过生日优惠券 THEN THE Member_Privileges_System SHALL 不重复发放

### Requirement 4: 生日特权 - 免费生日甜品

**User Story:** As a 用户, I want 在生日当天到店用餐时获得免费甜品, so that 我感受到店家的祝福。

#### Acceptance Criteria

1. WHEN 用户在生日当天创建订单 THEN THE Member_Privileges_System SHALL 自动添加生日甜品兑换券到订单
2. THE Birthday_Privilege SHALL 每年每用户限领1份生日甜品
3. WHEN 用户使用生日甜品券 THEN THE Member_Privileges_System SHALL 记录使用状态防止重复使用
4. IF 用户生日当天未到店消费 THEN THE Birthday_Privilege SHALL 保留甜品券有效期至生日后7天

### Requirement 5: 会员日活动 - 会员日设置

**User Story:** As a 管理员, I want 配置每月会员日的日期和活动规则, so that 我可以灵活运营会员日活动。

#### Acceptance Criteria

1. THE Member_Day SHALL 支持配置每月固定会员日日期（默认每月8号）
2. WHEN 管理员修改会员日日期 THEN THE Member_Privileges_System SHALL 立即生效并通知用户
3. THE Member_Day SHALL 支持配置会员日折扣比例（默认8.8折）
4. THE Member_Day SHALL 支持配置会员日是否启用
5. WHEN 会员日日期与节假日冲突 THEN THE Member_Day SHALL 支持临时调整当月会员日

### Requirement 6: 会员日活动 - 会员日折扣

**User Story:** As a 用户, I want 在会员日享受专属折扣, so that 我更愿意在会员日消费。

#### Acceptance Criteria

1. WHEN 用户在会员日下单 THEN THE Member_Privileges_System SHALL 自动应用会员日折扣
2. THE Member_Day SHALL 根据会员等级提供不同折扣（青铜9折、白银8.8折、黄金8.5折、白金8折）
3. WHEN 计算订单金额 THEN THE Member_Privileges_System SHALL 在其他优惠后应用会员日折扣
4. THE Member_Day SHALL 在订单详情中显示会员日折扣金额
5. IF 会员日折扣与其他活动冲突 THEN THE Member_Privileges_System SHALL 取最优惠的折扣应用

### Requirement 7: 会员日活动 - 会员日积分加成

**User Story:** As a 用户, I want 在会员日消费获得额外积分, so that 我更有动力在会员日消费。

#### Acceptance Criteria

1. WHEN 用户在会员日完成订单 THEN THE Member_Privileges_System SHALL 额外赠送50%积分
2. THE Member_Day SHALL 在积分流水中标记会员日加成来源
3. WHEN 会员日与生日重叠 THEN THE Member_Privileges_System SHALL 叠加计算积分（双倍+50%=2.5倍）
4. THE Member_Privileges_System SHALL 在会员日前1天发送活动提醒通知

### Requirement 8: 积分商城 - 实物商品管理

**User Story:** As a 管理员, I want 管理积分商城的实物商品, so that 用户可以用积分兑换周边产品。

#### Acceptance Criteria

1. THE Points_Mall SHALL 支持添加实物商品（名称、描述、图片、所需积分、库存）
2. WHEN 管理员添加实物商品 THEN THE Points_Mall SHALL 验证必填字段完整性
3. THE Points_Mall SHALL 支持设置商品上下架状态
4. THE Points_Mall SHALL 支持设置商品兑换限制（每人限兑数量）
5. WHEN 商品库存为0 THEN THE Points_Mall SHALL 自动将商品状态设为售罄

### Requirement 9: 积分商城 - 体验类商品管理

**User Story:** As a 管理员, I want 管理体验类商品, so that 用户可以兑换VIP包间等特殊体验。

#### Acceptance Criteria

1. THE Points_Mall SHALL 支持添加体验类商品（VIP包间体验、主厨定制服务等）
2. THE Points_Mall SHALL 为体验类商品设置预约时间段
3. WHEN 用户兑换体验类商品 THEN THE Points_Mall SHALL 生成体验预约券
4. THE Points_Mall SHALL 支持设置体验商品的有效期
5. IF 体验预约时间段已满 THEN THE Points_Mall SHALL 提示用户选择其他时间

### Requirement 10: 积分商城 - 商品兑换流程

**User Story:** As a 用户, I want 用积分兑换商城商品, so that 我的积分可以换取实际价值。

#### Acceptance Criteria

1. WHEN 用户浏览积分商城 THEN THE Points_Mall SHALL 显示商品列表及所需积分
2. WHEN 用户选择兑换商品 THEN THE Points_Mall SHALL 检查用户可用积分是否足够
3. IF 用户积分不足 THEN THE Points_Mall SHALL 提示差额并引导用户获取更多积分
4. WHEN 兑换成功 THEN THE Points_Mall SHALL 扣除用户积分并生成兑换记录
5. THE Points_Mall SHALL 为实物商品生成物流信息录入入口
6. WHEN 用户兑换实物商品 THEN THE Points_Mall SHALL 要求用户填写收货地址

### Requirement 11: 积分商城 - 兑换记录管理

**User Story:** As a 用户, I want 查看我的兑换记录和物流状态, so that 我可以追踪兑换商品的状态。

#### Acceptance Criteria

1. THE Points_Mall SHALL 提供用户兑换记录列表页面
2. WHEN 用户查看兑换记录 THEN THE Points_Mall SHALL 显示商品信息、兑换时间、状态
3. THE Points_Mall SHALL 支持实物商品的物流状态更新（待发货、已发货、已签收）
4. WHEN 管理员更新物流信息 THEN THE Points_Mall SHALL 通知用户
5. THE Points_Mall SHALL 支持体验类商品的使用状态更新（待使用、已使用、已过期）

### Requirement 12: 会员权益展示

**User Story:** As a 用户, I want 清晰了解我的会员权益, so that 我知道如何最大化利用会员身份。

#### Acceptance Criteria

1. THE Member_Privileges_System SHALL 在会员中心展示当前等级的所有权益
2. THE Member_Privileges_System SHALL 展示下一等级的权益对比，激励用户升级
3. WHEN 用户有可用的生日特权 THEN THE Member_Privileges_System SHALL 突出显示提醒
4. THE Member_Privileges_System SHALL 显示距离下次会员日的倒计时
5. THE Member_Privileges_System SHALL 展示用户已享受的权益统计（节省金额、获得积分等）
