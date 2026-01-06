<template>
  <FrontendLayout>
    <div class="py-12">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- 用户信息卡片 -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
          <div class="flex items-center space-x-6">
            <div class="relative">
              <div class="w-24 h-24 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center">
                <img v-if="userInfo?.avatar_url" :src="userInfo.avatar_url" alt="头像" class="w-full h-full rounded-full object-cover" />
                <span v-else class="text-4xl text-white font-bold">{{ userInfo?.nickname?.charAt(0) || 'U' }}</span>
              </div>
              <!-- 性别图标显示在头像右下角 -->
              <div v-if="userInfo?.gender !== null && userInfo?.gender !== undefined" class="absolute -bottom-1 -right-1 bg-white rounded-full p-1 shadow-md">
                <span class="text-2xl">{{ getGenderIcon(userInfo.gender) }}</span>
              </div>
            </div>
            <div class="flex-1">
              <h2 class="text-3xl font-bold text-gray-900 mb-2">
                <span v-if="userInfo?.equipped_title" class="text-yellow-600 font-bold mr-2">[{{ userInfo.equipped_title }}]</span>
                {{ userInfo?.nickname || '用户' }}
                <span
                  v-if="userInfo?.level"
                  class="ml-2 text-2xl font-bold"
                  :style="userInfo.level.color ? { color: userInfo.level.color } : { color: '#9333ea' }"
                >[{{ userInfo.level.name }}]</span>
              </h2>
              <p v-if="userInfo?.phone" class="text-gray-600">手机号：{{ userInfo.phone }}</p>
              <p v-else class="text-gray-500">未绑定手机号</p>
            </div>
            <div class="flex gap-3">
              <el-button type="primary" size="large" @click="handleEditProfile">编辑资料</el-button>
              <el-button type="danger" size="large" @click="handleLogout">退出登录</el-button>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- 左侧：功能菜单 -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md p-6">
              <h3 class="text-xl font-bold text-gray-900 mb-4">我的</h3>
              <div class="space-y-2">
                <template v-for="menu in menus" :key="menu.key">
                  <router-link
                    v-if="menu.route"
                    :to="menu.route"
                    class="w-full text-left px-4 py-3 rounded-lg transition-all block text-gray-700 hover:bg-gray-100"
                  >
                    <span class="mr-2">{{ menu.icon }}</span>
                    {{ menu.label }}
                  </router-link>
                  <button
                    v-else
                    @click="handleTabChange(menu.key)"
                    class="w-full text-left px-4 py-3 rounded-lg transition-all"
                    :class="activeTab === menu.key
                      ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white'
                      : 'text-gray-700 hover:bg-gray-100'"
                  >
                    <span class="mr-2">{{ menu.icon }}</span>
                    {{ menu.label }}
                  </button>
                </template>
              </div>
            </div>
          </div>

          <!-- 右侧：内容区域 -->
          <div class="lg:col-span-2">
            <!-- 我的订单 -->
            <div v-if="activeTab === 'orders'" class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">我的订单</h3>
            <div class="space-y-4">
              <div
                v-for="order in orders"
                :key="order.id"
                class="border-2 border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-all"
              >
                <div class="flex justify-between items-start mb-3">
                  <div>
                    <p class="font-semibold text-gray-900">订单号：{{ order.order_no }}</p>
                    <p class="text-sm text-gray-600">{{ formatDate(order.created_at) }}</p>
                  </div>
                  <el-tag :type="getOrderStatusType(order.status)">
                    {{ getOrderStatusText(order.status) }}
                  </el-tag>
                </div>
                <div class="flex justify-between items-center">
                  <p class="text-gray-700">共 {{ order.items_count }} 件商品</p>
                  <p class="text-xl font-bold text-red-600">¥{{ order.total_amount }}</p>
                </div>
                <div class="mt-3 flex gap-2">
                  <el-button size="small" @click="viewOrder(order)">查看详情</el-button>
                  <el-button v-if="order.status === 'pending_review'" size="small" type="primary" @click="reviewOrder(order)">
                    去评价
                  </el-button>
                </div>
              </div>
            </div>
            </div>

            <!-- 我的预约 -->
            <div v-if="activeTab === 'reservations'" class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">我的预约</h3>
            <div class="space-y-4">
              <div
                v-for="reservation in reservations"
                :key="reservation.id"
                class="border-2 border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-all"
              >
                <div class="flex justify-between items-start mb-3">
                  <div>
                    <p class="font-semibold text-gray-900">{{ reservation.reservation_code }}</p>
                    <p class="text-sm text-gray-600">{{ reservation.table?.name }} · {{ reservation.guest_count }}人</p>
                    <p class="text-sm text-gray-600">{{ formatReservationDateTime(reservation.date, reservation.time_slot) }}</p>
                  </div>
                  <el-tag :type="getReservationStatusType(reservation.status)">
                    {{ getReservationStatusText(reservation.status) }}
                  </el-tag>
                </div>
                <div class="mt-3 flex gap-2">
                  <el-button size="small" @click="viewReservation(reservation)">查看详情</el-button>
                  <el-button
                    v-if="canCancelReservation(reservation)"
                    size="small"
                    type="danger"
                    @click="cancelReservation(reservation.id)"
                  >
                    取消预约
                  </el-button>
                </div>
              </div>
            </div>
            </div>

            <!-- 我的积分 -->
            <div v-if="activeTab === 'points'" class="bg-white rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-2xl font-bold text-gray-900">我的积分</h3>
              <el-button type="primary" size="small" @click="refreshPoints" :loading="pointsLoading">
                <el-icon><Refresh /></el-icon>
                刷新
              </el-button>
            </div>
            <div v-loading="pointsLoading" class="bg-gradient-to-r from-yellow-400 to-orange-400 rounded-xl p-8 text-center mb-6">
              <p class="text-gray-700 mb-2">当前积分</p>
              <p class="text-5xl font-bold text-white">{{ points.total_points.toLocaleString() }}</p>
            </div>
            <div class="grid grid-cols-3 gap-4 mb-6">
              <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-gray-600 text-sm mb-1">可用积分</p>
                <p class="text-2xl font-bold text-gray-900">{{ points.available_points.toLocaleString() }}</p>
              </div>
              <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-gray-600 text-sm mb-1">冻结积分</p>
                <p class="text-2xl font-bold text-gray-900">{{ points.frozen_points.toLocaleString() }}</p>
              </div>
              <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-gray-600 text-sm mb-1">会员等级</p>
                <div class="flex items-center justify-center gap-2">
                  <img
                    v-if="points.level_info?.icon"
                    :src="points.level_info.icon"
                    :alt="levelDisplay"
                    class="w-6 h-6 object-contain"
                    loading="lazy"
                    @error="(e) => { (e.target as HTMLImageElement).style.display = 'none'; }"
                  />
                  <div
                    v-if="points.level_info?.color && !points.level_info?.icon"
                    class="w-3 h-3 rounded-full"
                    :style="{ backgroundColor: points.level_info.color }"
                  ></div>
                  <p
                    class="text-xl font-bold"
                    :style="points.level_info?.color ? { color: points.level_info.color } : {}"
                  >
                    {{ levelDisplay }}
                  </p>
                </div>
                <p v-if="points.next_level_info" class="text-xs text-gray-500 mt-1">
                  距离{{ points.next_level_info.name }}还需{{ points.points_to_next_level }}积分
                </p>
              </div>
            </div>
            <!-- 段位权益说明 -->
            <div v-if="points.level_info" class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 mb-6 border-2 border-purple-200">
              <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <span>🎯</span>
                <span>{{ points.level_info.name }}权益</span>
              </h4>
              
              <!-- 段位描述 -->
              <div v-if="points.level_info.description" class="mb-4">
                <p class="text-sm text-gray-700 leading-relaxed">{{ points.level_info.description }}</p>
              </div>

              <!-- 积分获取倍数 -->
              <div v-if="points.level_info.multiplier" class="mb-4 p-4 bg-white rounded-lg border-l-4 border-yellow-400">
                <div class="flex items-center gap-2 mb-2">
                  <span class="text-xl">⚡</span>
                  <span class="font-bold text-gray-900">积分获取倍数</span>
                </div>
                <p class="text-sm text-gray-700 mb-2">
                  当前段位享受 <span class="font-bold text-yellow-600">{{ points.level_info.multiplier }}倍</span> 积分获取加成
                </p>
                <div class="mt-2 text-xs text-gray-600 space-y-1">
                  <p v-if="points.rules_info?.order_earn">
                    • 订单积分：{{ (1 / points.rules_info.order_earn.base_ratio).toFixed(2) }}元 = 1积分（基础），实际获得：1元 × {{ points.rules_info.order_earn.base_ratio }} × {{ points.level_info.multiplier }}倍 = {{ formatDecimal(points.rules_info.order_earn.base_ratio * points.level_info.multiplier) }}积分
                  </p>
                  <p v-if="points.rules_info?.review_earn">
                    • 评价积分：基础{{ points.rules_info.review_earn.base_points }}积分 × {{ points.level_info.multiplier }}倍 = {{ Math.floor(points.rules_info.review_earn.base_points * points.level_info.multiplier) }}积分
                    <span v-if="points.rules_info.review_earn.with_image_bonus">（带图+{{ Math.floor(points.rules_info.review_earn.with_image_bonus * points.level_info.multiplier) }}积分）</span>
                  </p>
                  <p v-if="points.rules_info?.review_adoption">
                    • 评价采纳：{{ points.rules_info.review_adoption.base_points }}积分 × {{ points.level_info.multiplier }}倍 = {{ Math.floor(points.rules_info.review_adoption.base_points * points.level_info.multiplier) }}积分
                  </p>
                </div>
              </div>

              <!-- 段位折扣 -->
              <div v-if="points.level_info.discount_type && points.level_info.discount_type !== 'none'" class="mb-4 p-4 bg-white rounded-lg border-l-4 border-green-400">
                <div class="flex items-center gap-2 mb-2">
                  <span class="text-xl">💰</span>
                  <span class="font-bold text-gray-900">段位折扣</span>
                </div>
                <p class="text-sm text-gray-700">
                  <span v-if="points.level_info.discount_type === 'percentage'">
                    订单金额满 <span class="font-bold text-green-600">¥{{ points.level_info.min_order_amount }}</span> 可享受 <span class="font-bold text-green-600">{{ points.level_info.discount_value }}%</span> 折扣
                    <span v-if="points.level_info.max_discount_amount">（最高优惠¥{{ points.level_info.max_discount_amount }}）</span>
                  </span>
                  <span v-else-if="points.level_info.discount_type === 'fixed'">
                    订单金额满 <span class="font-bold text-green-600">¥{{ points.level_info.min_order_amount }}</span> 可享受 <span class="font-bold text-green-600">¥{{ points.level_info.discount_value }}</span> 优惠
                  </span>
                </p>
              </div>

              <!-- 积分使用规则 -->
              <div v-if="points.rules_info?.point_use" class="mb-4 p-4 bg-white rounded-lg border-l-4 border-blue-400">
                <div class="flex items-center gap-2 mb-2">
                  <span class="text-xl">💳</span>
                  <span class="font-bold text-gray-900">积分使用规则</span>
                </div>
                <div class="text-sm text-gray-700 space-y-1">
                  <p>• {{ points.rules_info.point_use.use_ratio }}积分 = 1元</p>
                  <p>• 最低使用{{ points.rules_info.point_use.min_points }}积分</p>
                  <p>• 单次订单最多抵扣{{ points.rules_info.point_use.max_percent }}%</p>
                </div>
              </div>

              <!-- 积分有效期 -->
              <div v-if="points.rules_info?.point_expire" class="p-4 bg-white rounded-lg border-l-4 border-orange-400">
                <div class="flex items-center gap-2 mb-2">
                  <span class="text-xl">⏰</span>
                  <span class="font-bold text-gray-900">积分有效期</span>
                </div>
                <p class="text-sm text-gray-700">
                  积分有效期为 <span class="font-bold text-orange-600">{{ points.rules_info.point_expire.expire_days }}天</span>，过期后自动失效
                </p>
              </div>
            </div>

            <h4 class="text-lg font-bold text-gray-900 mb-4">积分明细</h4>
            <div class="space-y-3">
              <div
                v-for="transaction in pointTransactions"
                :key="transaction.id"
                class="flex justify-between items-center p-3 bg-gray-50 rounded-lg"
              >
                <div class="flex-1">
                  <p class="font-semibold text-gray-900">{{ transaction.description || getTransactionTypeText(transaction.type) }}</p>
                  <p class="text-sm text-gray-600">{{ formatDateTime(transaction.created_at) }}</p>
                </div>
                <div class="text-right ml-4">
                  <span
                    class="text-lg font-bold block"
                    :class="transaction.points > 0 ? 'text-green-600' : 'text-red-600'"
                  >
                    {{ transaction.points > 0 ? '+' : '' }}{{ transaction.points.toLocaleString() }}
                  </span>
                  <p class="text-xs text-gray-500 mt-1">余额: {{ transaction.balance_after.toLocaleString() }}</p>
                </div>
              </div>
              <div v-if="pointTransactions.length === 0 && !loading" class="text-center py-8 text-gray-500">
                暂无积分记录
              </div>
            </div>
            </div>

            <!-- 我的优惠券 -->
            <div v-if="activeTab === 'coupons'" class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">我的优惠券</h3>
            <div v-loading="couponsLoading" class="space-y-4">
              <div
                v-for="userCoupon in userCoupons"
                :key="userCoupon.id"
                class="border-2 border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-all"
              >
                <div class="flex justify-between items-start mb-3">
                  <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 text-lg mb-2">{{ userCoupon.coupon?.name }}</h4>
                    <p class="text-sm text-gray-600 mb-2">{{ userCoupon.coupon?.description }}</p>
                    <div class="flex flex-wrap gap-2 mb-2">
                      <el-tag v-if="userCoupon.coupon?.type === 'fixed_amount'" type="success">
                        固定金额：¥{{ userCoupon.coupon.value }}
                      </el-tag>
                      <el-tag v-else-if="userCoupon.coupon?.type === 'percentage'" type="warning">
                        折扣：{{ userCoupon.coupon.value }}%
                      </el-tag>
                      <el-tag v-else-if="userCoupon.coupon?.type === 'dish_exchange'" type="info">
                        菜品兑换
                      </el-tag>
                      <el-tag v-if="userCoupon.coupon?.min_amount > 0" type="info" size="small">
                        满¥{{ userCoupon.coupon.min_amount }}可用
                      </el-tag>
                    </div>
                    <p class="text-xs text-gray-500">
                      优惠券码：<span class="font-mono">{{ userCoupon.code }}</span>
                    </p>
                    <p class="text-xs text-gray-500">
                      有效期至：{{ userCoupon.expires_at ? formatDateTime(userCoupon.expires_at) : '永久有效' }}
                    </p>
                    <p v-if="userCoupon.obtained_from" class="text-xs text-gray-500">
                      获得方式：{{ userCoupon.obtained_from === 'lottery' ? '抽奖获得' : userCoupon.obtained_from === 'exchange' ? '积分兑换' : '其他' }}
                    </p>
                  </div>
                  <el-tag
                    :type="userCoupon.status === 'unused' ? 'success' : userCoupon.status === 'used' ? 'info' : 'danger'"
                    size="large"
                  >
                    {{ userCoupon.status === 'unused' ? '未使用' : userCoupon.status === 'used' ? '已使用' : '已过期' }}
                  </el-tag>
                </div>
                <div v-if="userCoupon.coupon?.usage_instructions" class="mt-3 p-3 bg-gray-50 rounded text-sm text-gray-600">
                  <p class="font-semibold mb-1">使用说明：</p>
                  <p>{{ userCoupon.coupon.usage_instructions }}</p>
                </div>
              </div>
              <div v-if="userCoupons.length === 0 && !couponsLoading" class="text-center py-8 text-gray-500">
                暂无优惠券
              </div>
            </div>
            </div>

            <!-- 我的评价 -->
            <div v-if="activeTab === 'reviews'" class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">我的评价</h3>
            <div class="space-y-4">
              <div
                v-for="review in myReviews"
                :key="review.id"
                class="border-2 border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-all"
              >
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center">
                    <h4 class="font-semibold text-gray-900 mr-4">{{ review.dish?.name }}</h4>
                    <el-rate v-model="review.rating" disabled size="small" />
                  </div>
                  <div class="flex items-center gap-2">
                    <el-tag v-if="review.is_adopted" type="success" effect="dark">已采纳</el-tag>
                    <el-tag
                      :type="review.status === 'approved' ? 'success' : review.status === 'rejected' ? 'danger' : 'warning'"
                    >
                      {{ review.status === 'approved' ? '已通过' : review.status === 'rejected' ? '已拒绝' : '待审核' }}
                    </el-tag>
                  </div>
                </div>
                <p class="text-gray-700 mb-2">{{ review.content }}</p>
                <p class="text-sm text-gray-500 mb-2">{{ formatDateTime(review.created_at) }}</p>
                
                <!-- 管理员回复 -->
                <div v-if="review.admin_reply" class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-3 rounded">
                  <div class="flex items-start gap-2">
                    <el-icon class="text-blue-500 mt-1"><ChatDotRound /></el-icon>
                    <div class="flex-1">
                      <p class="font-semibold text-blue-900 mb-1">管理员回复：</p>
                      <p class="text-blue-800">{{ review.admin_reply }}</p>
                      <p v-if="review.admin_replied_at" class="text-xs text-blue-600 mt-1">
                        {{ formatDateTime(review.admin_replied_at) }}
                      </p>
                    </div>
                  </div>
                </div>
                
                <!-- 采纳信息 -->
                <div v-if="review.is_adopted && review.adopted_at" class="bg-green-50 border-l-4 border-green-500 p-4 mb-3 rounded">
                  <div class="flex items-start gap-2">
                    <el-icon class="text-green-500 mt-1"><Check /></el-icon>
                    <div class="flex-1">
                      <p class="font-semibold text-green-900 mb-1">评价建议已被采纳</p>
                      <p class="text-green-800 text-sm">
                        采纳时间：{{ formatDateTime(review.adopted_at) }}
                        <span v-if="review.adopter"> | 采纳人：{{ review.adopter.name || review.adopter.username }}</span>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 编辑资料对话框 -->
      <el-dialog
        v-model="editDialogVisible"
        title="编辑资料"
        width="600px"
        :close-on-click-modal="false"
      >
        <el-form
          ref="editFormRef"
          :model="editForm"
          label-width="100px"
          label-position="left"
        >
          <el-form-item label="昵称" prop="nickname">
            <el-input v-model="editForm.nickname" placeholder="请输入昵称" maxlength="64" />
          </el-form-item>
          
          <el-form-item label="头像" prop="avatar_url">
            <div class="flex items-center gap-4">
              <el-upload
                class="avatar-uploader"
                :action="''"
                :show-file-list="false"
                :before-upload="beforeAvatarUpload"
                :http-request="handleAvatarUpload"
                accept="image/*"
              >
                <img 
                  v-if="editForm.avatar_url && editForm.avatar_url.trim()" 
                  :src="editForm.avatar_url" 
                  class="avatar"
                  @error="handleImageError"
                  @load="handleImageLoad"
                  :key="editForm.avatar_url"
                />
                <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
              </el-upload>
              <div class="flex-1">
                <el-input 
                  v-model="editForm.avatar_url" 
                  placeholder="或输入头像URL" 
                  class="mb-2"
                />
                <p class="text-xs text-gray-500">支持上传图片或输入图片URL</p>
              </div>
            </div>
          </el-form-item>
          
          <el-form-item label="性别" prop="gender">
            <el-radio-group v-model="editForm.gender">
              <el-radio :label="0">未知</el-radio>
              <el-radio :label="1">男</el-radio>
              <el-radio :label="2">女</el-radio>
            </el-radio-group>
          </el-form-item>
          
          <el-form-item label="手机号">
            <div class="flex gap-2">
              <el-input
                v-model="editForm.phone"
                placeholder="请输入手机号"
                :disabled="!isChangingPhone && !!userInfo?.phone"
                maxlength="11"
              />
              <el-button
                v-if="isChangingPhone || !userInfo?.phone"
                @click="sendPhoneCode"
                :disabled="phoneCodeCountdown > 0 || !editForm.phone || !/^1[3-9]\d{9}$/.test(editForm.phone)"
              >
                {{ phoneCodeCountdown > 0 ? `${phoneCodeCountdown}秒后重试` : '发送验证码' }}
              </el-button>
              <el-button
                v-else
                @click="isChangingPhone = true"
              >
                修改
              </el-button>
            </div>
            <el-input
              v-if="isChangingPhone || !userInfo?.phone"
              v-model="editForm.phone_verification_code"
              placeholder="请输入验证码"
              class="mt-2"
              maxlength="6"
            />
          </el-form-item>
          
          <el-form-item label="密码">
            <div class="flex gap-2">
              <el-button
                v-if="!isChangingPassword"
                @click="isChangingPassword = true"
              >
                {{ userInfo?.has_password ? '修改密码' : '设置密码' }}
              </el-button>
              <el-button
                v-else
                @click="isChangingPassword = false; editForm.password = ''; editForm.password_confirmation = ''; editForm.current_password = '';"
              >
                取消{{ userInfo?.has_password ? '修改' : '设置' }}
              </el-button>
            </div>
            <template v-if="isChangingPassword">
              <el-input
                v-if="userInfo?.has_password"
                v-model="editForm.current_password"
                type="password"
                placeholder="请输入当前密码"
                class="mt-2"
                show-password
              />
              <el-input
                v-model="editForm.password"
                type="password"
                :placeholder="userInfo?.has_password ? '请输入新密码（至少6位）' : '请输入密码（至少6位）'"
                class="mt-2"
                show-password
              />
              <el-input
                v-model="editForm.password_confirmation"
                type="password"
                placeholder="请确认密码"
                class="mt-2"
                show-password
              />
            </template>
          </el-form-item>
        </el-form>
        
        <template #footer>
          <el-button @click="editDialogVisible = false">取消</el-button>
          <el-button type="primary" @click="handleSaveProfile">保存</el-button>
        </template>
      </el-dialog>
    </div>
  </FrontendLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Refresh, ChatDotRound, Check, Plus } from '@element-plus/icons-vue';
import FrontendLayout from '../../components/frontend/FrontendLayout.vue';
import { userAuthApi } from '../../api/auth';
import { frontendPointsApi, type FrontendMemberPoint, type FrontendPointTransaction } from '../../api/frontend-points';
import { orderApi } from '../../api/order';
import { couponApi } from '../../api/frontend-coupon';
import { updateProfile } from '../../api/user';
import { uploadImage } from '../../api/upload';
import type { Reservation, Review, UserInfo } from '../../types';

const route = useRoute();
const router = useRouter();
const activeTab = ref<string>((route.query.tab as string) || 'orders');

const menus = [
  { key: 'orders', label: '我的订单', icon: '📦' },
  { key: 'reservations', label: '我的预约', icon: '📅' },
  { key: 'coupons', label: '我的优惠券', icon: '🎫' },
  { key: 'points', label: '我的积分', icon: '⭐' },
  { key: 'reviews', label: '我的评价', icon: '💬' },
  { key: 'invitation', label: '邀请好友', icon: '🎁', route: '/frontend/invitation' },
  { key: 'tasks', label: '我的任务', icon: '📋', route: '/frontend/tasks' },
  { key: 'checkin', label: '每日签到', icon: '📅', route: '/frontend/checkin' },
  { key: 'achievements', label: '我的成就', icon: '🏆', route: '/frontend/achievements' },
];

const loading = ref(false);
const pointsLoading = ref(false);
const couponsLoading = ref(false);
const orders = ref<any[]>([]);
const reservations = ref<Reservation[]>([]);
const myReviews = ref<Review[]>([]);
const userCoupons = ref<any[]>([]);
const points = ref<FrontendMemberPoint>({
  total_points: 0,
  available_points: 0,
  frozen_points: 0,
  level: '',
  level_text: '',
  level_info: null,
  next_level_info: null,
  points_to_next_level: 0,
  expiring_points: [],
  total_expiring: 0,
});

const pointTransactions = ref<FrontendPointTransaction[]>([]);

const levelDisplay = computed(() => {
  // 优先使用后台返回的段位名称
  if (points.value.level_info?.name) {
    return points.value.level_info.name;
  }
  // 其次使用 level_text
  if (points.value.level_text) {
    return points.value.level_text;
  }
  // 最后使用 level 代码
  return points.value.level || '未知';
});
const userInfo = ref<UserInfo | null>(null);

// 处理标签页切换
const handleTabChange = (tabKey: string) => {
  activeTab.value = tabKey;
  // 更新 URL 查询参数，但不触发页面刷新
  router.replace({
    path: route.path,
    query: { ...route.query, tab: tabKey },
  });
};

// 监听路由查询参数变化，更新 activeTab
watch(() => route.query.tab, (newTab) => {
  if (newTab && typeof newTab === 'string' && menus.some(m => m.key === newTab)) {
    activeTab.value = newTab;
  }
}, { immediate: true });

const formatDate = (date: string | Date) => {
  if (!date) return '';
  try {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    if (isNaN(dateObj.getTime())) return '';
    return dateObj.toLocaleDateString('zh-CN');
  } catch (error) {
    console.error('日期格式化失败:', error);
    return '';
  }
};

const formatDateTime = (datetime: string | Date) => {
  if (!datetime) return '';
  try {
    const dateObj = typeof datetime === 'string' ? new Date(datetime) : datetime;
    if (isNaN(dateObj.getTime())) return '';
    return dateObj.toLocaleString('zh-CN');
  } catch (error) {
    console.error('日期时间格式化失败:', error);
    return '';
  }
};

const formatReservationDateTime = (date: string | Date | null, timeSlot: string | null) => {
  if (!date || !timeSlot) return '';
  try {
    // 处理日期：确保只取日期部分（YYYY-MM-DD）
    let dateStr = '';
    if (typeof date === 'string') {
      // 如果包含时间部分，只取日期部分
      dateStr = date.split(' ')[0].split('T')[0];
    } else if (date instanceof Date) {
      dateStr = date.toISOString().split('T')[0];
    } else {
      return '';
    }
    
    // 处理时间：确保只取时间部分（HH:MM 或 HH:MM:SS）
    let timeStr = '';
    if (typeof timeSlot === 'string') {
      // 移除日期部分，只保留时间部分
      timeStr = timeSlot.split(' ').pop() || timeSlot;
      // 只取前5个字符（HH:MM）或前8个字符（HH:MM:SS）
      if (timeStr.length > 8) {
        timeStr = timeStr.substring(0, 8);
      }
    } else {
      return '';
    }
    
    return `${dateStr} ${timeStr}`;
  } catch (error) {
    console.error('预约日期时间格式化失败:', error, { date, timeSlot });
    return '';
  }
};

const canCancelReservation = (reservation: Reservation) => {
  // 只有待确认或已确认状态的预约才能取消
  return reservation.status === 'pending' || reservation.status === 'confirmed';
};

const getOrderStatusType = (status: string) => {
  const types: Record<string, string> = {
    pending: 'warning',
    paid: 'primary',
    pending_review: 'warning',
    completed: 'success',
    cancelled: 'info',
  };
  return types[status] || '';
};

const getOrderStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: '待支付',
    paid: '已支付',
    pending_review: '待评价',
    completed: '已完成',
    cancelled: '已取消',
  };
  return texts[status] || status;
};

const getReservationStatusType = (status: string) => {
  const types: Record<string, string> = {
    pending: 'warning',
    confirmed: 'success',
    cancelled: 'info',
    completed: '',
    expired: 'danger',
  };
  return types[status] || '';
};

const getReservationStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: '待确认',
    confirmed: '已确认',
    cancelled: '已取消',
    completed: '已完成',
    expired: '已过期',
  };
  return texts[status] || status;
};

const viewOrder = (order: any) => {
  if (!order || !order.id) {
    ElMessage.error('订单信息无效');
    return;
  }
  router.push(`/frontend/orders/${order.id}`).catch((err) => {
    console.error('路由跳转失败:', err);
    ElMessage.error('跳转失败，请重试');
  });
};

const reviewOrder = (order: any) => {
  if (order && order.id) {
    router.push(`/frontend/review/${order.id}`);
  } else {
    ElMessage.error('订单信息无效，无法评价');
  }
};

const viewReservation = (reservation: Reservation) => {
  router.push(`/frontend/reservations/${reservation.id}`);
};

const cancelReservation = async (id: number) => {
  try {
    await ElMessageBox.confirm('确认取消预约吗？', '提示', {
      confirmButtonText: '确认',
      cancelButtonText: '取消',
      type: 'warning',
    });

    // 调用 API 取消预约
    const { reservationApi } = await import('../../api/reservation');
    const response = await reservationApi.cancel(id);
    
    if (response.code === 200) {
      ElMessage.success('预约已取消');
      
      // 立即更新本地状态，避免等待API响应
      const reservationIndex = reservations.value.findIndex(r => r.id === id);
      if (reservationIndex !== -1) {
        // 更新预约状态为已取消
        reservations.value[reservationIndex] = {
          ...reservations.value[reservationIndex],
          status: 'cancelled',
          cancelled_at: response.data?.cancelled_at || new Date().toISOString(),
        };
      }
      
      // 刷新预约列表以获取最新数据
      await fetchReservations();
    } else {
      ElMessage.error(response.message || '取消预约失败');
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      console.error('取消预约失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '取消预约失败');
    }
  }
};

const fetchOrders = async () => {
  try {
    const response: any = await orderApi.getList();
    if (response.code === 200 && response.data) {
      const orderList = response.data.data || [];
      // 转换数据格式以匹配页面显示需求
      orders.value = orderList.map((order: any) => ({
        ...order,
        items_count: order.items?.length || 0,
      }));
    } else {
      ElMessage.error(response.message || '获取订单列表失败');
      orders.value = [];
    }
  } catch (error: any) {
    console.error('获取订单列表失败:', error);
    if (error.response?.status !== 401) {
      ElMessage.error(error.message || '获取订单列表失败');
    }
    orders.value = [];
  }
};

const fetchReservations = async () => {
  try {
    const { reservationApi } = await import('../../api/reservation');
    const response = await reservationApi.getList({ page: 1, page_size: 50 });
    if (response.code === 200 && response.data) {
      reservations.value = response.data.reservations || [];
    } else {
      ElMessage.error(response.message || '获取预约列表失败');
      reservations.value = [];
    }
  } catch (error: any) {
    console.error('获取预约列表失败:', error);
    ElMessage.error(error.message || '获取预约列表失败');
    reservations.value = [];
  }
};

const fetchReviews = async () => {
  try {
    const { reviewApi } = await import('../../api/review');
    // 获取当前用户的评价
    const response: any = await reviewApi.getList({ my_reviews: true, page: 1, page_size: 50 });
    // apiClient 的响应拦截器已经提取了 data，所以 response 就是 { code, message, data }
    if (response && response.code === 200 && response.data) {
      myReviews.value = response.data.reviews || [];
    } else {
      ElMessage.error(response?.message || '获取评价列表失败');
      myReviews.value = [];
    }
  } catch (error: any) {
    console.error('获取评价列表失败:', error);
    ElMessage.error(error.message || '获取评价列表失败');
    myReviews.value = [];
  }
};

const fetchPoints = async (showLoading = false) => {
  if (showLoading) {
    pointsLoading.value = true;
  }
  try {
    // 获取当前用户信息用于调试
    const userInfoStr = localStorage.getItem('user_info');
    const currentUser = userInfoStr ? JSON.parse(userInfoStr) : null;
    console.log('Profile页面 - 当前登录用户:', currentUser);
    
    const response = await frontendPointsApi.getPoints();
    console.log('Profile页面 - 积分API响应:', response);
    
    if (response && response.code === 200 && response.data) {
      points.value = {
        total_points: response.data.total_points ?? 0,
        available_points: response.data.available_points ?? 0,
        frozen_points: response.data.frozen_points ?? 0,
        level: response.data.level ?? 'bronze',
        level_text: response.data.level_text ?? '',
        level_info: response.data.level_info ?? null,
        next_level_info: response.data.next_level_info ?? null,
        points_to_next_level: response.data.points_to_next_level ?? 0,
        expiring_points: response.data.expiring_points ?? [],
        total_expiring: response.data.total_expiring ?? 0,
        rules_info: response.data.rules_info ?? null,
      };
      console.log('Profile页面 - 积分数据已更新:', points.value);
    } else {
      console.warn('Profile页面 - 积分API返回异常:', response);
      ElMessage.warning('获取积分信息失败，请稍后重试');
    }
  } catch (error: any) {
    console.error('Profile页面 - 获取积分信息失败:', error);
    console.error('Profile页面 - 错误详情:', error.response?.data || error.message);
    if (error.response?.status !== 401) {
      // 401错误不显示提示，因为会跳转登录页
      ElMessage.error('获取积分信息失败: ' + (error.response?.data?.message || error.message || '网络错误'));
    }
  } finally {
    // 确保无论成功或失败都重置加载状态
    if (showLoading) {
      pointsLoading.value = false;
    }
  }
};

const fetchPointTransactions = async (showLoading = false) => {
  if (showLoading) {
    pointsLoading.value = true;
  }
  try {
    const response = await frontendPointsApi.getTransactions({ per_page: 20 });
    console.log('Profile页面 - 积分明细API响应:', response);
    if (response && response.code === 200 && response.data) {
      pointTransactions.value = response.data.transactions || [];
      console.log('Profile页面 - 积分明细数据已更新，共', pointTransactions.value.length, '条');
    } else {
      console.warn('Profile页面 - 积分明细API返回异常:', response);
      pointTransactions.value = [];
    }
  } catch (error: any) {
    console.error('Profile页面 - 获取积分明细失败:', error);
    pointTransactions.value = [];
    if (error.response?.status !== 401) {
      ElMessage.error('获取积分明细失败: ' + (error.response?.data?.message || error.message || '网络错误'));
    }
  } finally {
    // 确保无论成功或失败都重置加载状态
    if (showLoading) {
      pointsLoading.value = false;
    }
  }
};

// 手动刷新积分数据
const refreshPoints = async () => {
  pointsLoading.value = true;
  try {
    await Promise.all([
      fetchPoints(false),
      fetchPointTransactions(false),
    ]);
    ElMessage.success('积分数据已刷新');
  } catch (error) {
    console.error('刷新积分数据失败:', error);
  } finally {
    pointsLoading.value = false;
  }
};

const getTransactionTypeText = (type: string) => {
  const map: Record<string, string> = {
    earn: '获得积分',
    redeem: '兑换优惠券',
    expire: '积分过期',
    adjust: '积分调整',
  };
  return map[type] || type;
};

const formatDecimal = (value: number): string => {
  return value.toFixed(2);
};

// 获取性别图标（可爱的符号）
const getGenderIcon = (gender: number | null | undefined): string => {
  if (gender === null || gender === undefined) {
    return '🤔'; // 未知性别 - 更可爱的思考表情
  }
  switch (gender) {
    case 1:
      return '🤵'; // 男性 - 更可爱的绅士图标
    case 2:
      return '👸'; // 女性 - 更可爱的公主图标
    default:
      return '🤔'; // 未知 - 更可爱的思考表情
  }
};

const fetchUserInfo = async () => {
  try {
    // 先尝试从缓存加载
    const userInfoStr = localStorage.getItem('user_info');
    if (userInfoStr) {
      try {
        userInfo.value = JSON.parse(userInfoStr);
      } catch (e) {
        console.error('解析user_info失败:', e);
      }
    }
    
    // 从服务器获取最新信息
    const response = await userAuthApi.me();
    if (response && response.code === 200 && response.data) {
      userInfo.value = response.data.user;
      localStorage.setItem('user_info', JSON.stringify(response.data.user));
    }
  } catch (error: any) {
    console.error('获取用户信息失败:', error);
    if (error.response?.status === 401) {
      // token无效，清除缓存
      localStorage.removeItem('token');
      localStorage.removeItem('user_info');
      userInfo.value = null;
      router.push({
        path: '/frontend/login',
        query: { redirect: '/frontend/profile' },
      });
    }
  }
};

// 编辑资料对话框
const editDialogVisible = ref(false);
const editForm = ref({
  nickname: '',
  avatar_url: '',
  phone: '',
  phone_verification_code: '',
  gender: null as 0 | 1 | 2 | null,
  password: '',
  password_confirmation: '',
  current_password: '',
});
const editFormRef = ref();
const phoneCodeCountdown = ref(0);
const isChangingPhone = ref(false);
const isChangingPassword = ref(false);

const handleEditProfile = () => {
  editForm.value = {
    nickname: userInfo.value?.nickname || '',
    avatar_url: userInfo.value?.avatar_url || '',
    phone: userInfo.value?.phone || '',
    phone_verification_code: '',
    gender: (userInfo.value?.gender as 0 | 1 | 2) || null,
    password: '',
    password_confirmation: '',
    current_password: '',
  };
  isChangingPhone.value = false;
  isChangingPassword.value = false;
  editDialogVisible.value = true;
};

const sendPhoneCode = async () => {
  if (!editForm.value.phone || !/^1[3-9]\d{9}$/.test(editForm.value.phone)) {
    ElMessage.error('请输入正确的手机号');
    return;
  }

  try {
    const response = await userAuthApi.sendPhoneCode({
      phone: editForm.value.phone,
      type: 'register',
    });
    
    if (response.code === 200) {
      ElMessage.success('验证码已发送' + (response.data?.code ? `，验证码：${response.data.code}` : ''));
      
      // 开始倒计时
      phoneCodeCountdown.value = 60;
      const timer = setInterval(() => {
        phoneCodeCountdown.value--;
        if (phoneCodeCountdown.value <= 0) {
          clearInterval(timer);
        }
      }, 1000);
    } else {
      ElMessage.error(response.message || '发送验证码失败');
    }
  } catch (error: any) {
    console.error('发送验证码失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '发送验证码失败');
  }
};

const handleSaveProfile = async () => {
  if (!editFormRef.value) return;
  
  await editFormRef.value.validate(async (valid: boolean) => {
    if (!valid) return;

    try {
      const updateParams: any = {};
      
      // 更新昵称
      if (editForm.value.nickname !== userInfo.value?.nickname) {
        updateParams.nickname = editForm.value.nickname;
      }
      
      // 更新头像
      if (editForm.value.avatar_url !== userInfo.value?.avatar_url) {
        updateParams.avatar_url = editForm.value.avatar_url;
      }
      
      // 更新性别
      if (editForm.value.gender !== userInfo.value?.gender) {
        updateParams.gender = editForm.value.gender;
      }
      
      // 更新手机号（包括首次绑定和修改）
      // 条件：1) 正在修改手机号 或 2) 用户没有手机号但输入了新手机号
      const hasPhoneChanged = editForm.value.phone && editForm.value.phone !== (userInfo.value?.phone || '');
      if ((isChangingPhone.value || !userInfo.value?.phone) && hasPhoneChanged) {
        if (!editForm.value.phone_verification_code) {
          ElMessage.error('请输入验证码');
          return;
        }
        updateParams.phone = editForm.value.phone;
        updateParams.phone_verification_code = editForm.value.phone_verification_code;
      }
      
      // 更新密码
      if (isChangingPassword.value && editForm.value.password) {
        // 如果用户已经设置过密码，需要当前密码；如果没有设置过密码，可以直接设置
        if (userInfo.value?.has_password && !editForm.value.current_password) {
          ElMessage.error('请输入当前密码');
          return;
        }
        updateParams.password = editForm.value.password;
        updateParams.password_confirmation = editForm.value.password_confirmation;
        // 只有在用户已设置密码时才需要当前密码
        if (userInfo.value?.has_password) {
          updateParams.current_password = editForm.value.current_password;
        }
      }
      
      if (Object.keys(updateParams).length === 0) {
        ElMessage.info('没有需要更新的内容');
        return;
      }
      
      const updatedUser = await updateProfile(updateParams);
      
      // 更新本地用户信息（包括has_password字段）
      userInfo.value = {
        ...userInfo.value!,
        ...updatedUser,
        has_password: updatedUser.has_password ?? userInfo.value?.has_password,
      };
      localStorage.setItem('user_info', JSON.stringify(userInfo.value));
      
      ElMessage.success('资料更新成功');
      editDialogVisible.value = false;
    } catch (error: any) {
      console.error('更新资料失败:', error);
      ElMessage.error(error.response?.data?.message || error.message || '更新资料失败');
    }
  });
};

// 头像上传相关
const beforeAvatarUpload = (file: File) => {
  const isValidType = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif'].includes(file.type);
  const isLt5M = file.size / 1024 / 1024 < 5;

  if (!isValidType) {
    ElMessage.error('只能上传图片格式文件（jpg、png、gif、webp、avif）');
    return false;
  }
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB');
    return false;
  }
  return true;
};

const handleImageLoad = () => {
  console.log('图片加载成功:', editForm.value.avatar_url);
};

const handleImageError = (event: any) => {
  const failedUrl = editForm.value.avatar_url;
  console.error('图片加载失败:', failedUrl);
  
  // 【修复】如果图片加载失败，尝试使用后端8000端口直接访问
  // 这样可以绕过可能的代理问题
  if (failedUrl && failedUrl.startsWith('/storage/')) {
    const backendUrl = `http://localhost:8000${failedUrl}`;
    console.log('尝试使用后端URL加载:', backendUrl);
    
    // 创建一个新的Image对象测试URL是否可访问
    const testImg = new Image();
    testImg.onload = () => {
      // 如果后端URL可以访问，更新为后端URL
      editForm.value.avatar_url = backendUrl;
      console.log('使用后端URL成功加载图片');
    };
    testImg.onerror = () => {
      // 如果后端URL也无法访问，显示警告但不清空URL（让用户看到URL）
      console.error('后端URL也无法访问:', backendUrl);
      ElMessage.warning('图片加载失败，请检查URL是否正确或文件是否存在');
      // 不清空URL，让用户可以手动修改
    };
    testImg.src = backendUrl;
  } else {
    ElMessage.warning('图片加载失败，请检查URL是否正确');
  }
};

const handleAvatarUpload = async (options: any) => {
  const { file } = options;
  
  try {
    ElMessage.info('正在上传头像...');
    const result = await uploadImage(file);
    console.log('上传结果:', result);
    
    // 【修复】处理URL格式
    // 后端返回的是相对路径（如：/storage/uploads/images/xxx.png）
    // 在开发环境中，优先使用相对路径通过Vite代理访问
    // 如果代理失败，自动回退到后端8000端口直接访问
    let avatarUrl = result.url;
    
    if (avatarUrl) {
      // 如果已经是完整URL，检查端口号
      if (avatarUrl.startsWith('http://') || avatarUrl.startsWith('https://')) {
        const urlObj = new URL(avatarUrl);
        // 如果URL端口是8000但前端不是，转换为相对路径使用代理
        if (urlObj.port === '8000' && window.location.port !== '8000') {
          avatarUrl = urlObj.pathname + (urlObj.search || '');
        }
      } else {
        // 相对路径，确保以/开头
        if (!avatarUrl.startsWith('/')) {
          avatarUrl = '/' + avatarUrl;
        }
      }
    }
    
    // 【修复】先设置为相对路径，让Vite代理处理
    // 如果加载失败，handleImageError会自动尝试后端URL
    editForm.value.avatar_url = avatarUrl;
    
    // 强制触发视图更新
    await new Promise(resolve => setTimeout(resolve, 100));
    
    console.log('设置头像URL:', avatarUrl);
    
    // 【修复】预加载图片，检查是否可以正常访问
    // 如果相对路径无法加载，立即切换到后端URL
    const testImg = new Image();
    testImg.onload = () => {
      console.log('图片预加载成功，预览应能正常显示');
    };
    testImg.onerror = () => {
      console.warn('图片预加载失败，将尝试后端URL');
      // 如果相对路径无法加载，尝试使用后端URL
      if (avatarUrl && avatarUrl.startsWith('/storage/')) {
        const backendUrl = `http://localhost:8000${avatarUrl}`;
        editForm.value.avatar_url = backendUrl;
        console.log('切换到后端URL:', backendUrl);
      }
    };
    testImg.src = avatarUrl;
    
    ElMessage.success('头像上传成功');
  } catch (error: any) {
    console.error('头像上传失败:', error);
    ElMessage.error(error.message || '头像上传失败，请重试');
  }
};

const handleLogout = async () => {
  try {
    await ElMessageBox.confirm('确定要退出登录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    });
    
    try {
      await userAuthApi.logout();
    } catch (error) {
      // 即使退出 API 失败，也清除本地 token
      console.error('退出登录 API 失败:', error);
    }
    
    // 清除前端登录信息
    localStorage.removeItem('token');
    localStorage.removeItem('user_info');
    userInfo.value = null;
    
    ElMessage.success('已退出登录');
    
    // 跳转到首页
    router.push('/');
  } catch (error) {
    // 用户取消操作
  }
};

// 获取用户优惠券
const fetchUserCoupons = async () => {
  couponsLoading.value = true;
  try {
    const response = await couponApi.getUserCoupons();
    if (response.code === 200 && response.data) {
      userCoupons.value = response.data.coupons || [];
    }
  } catch (error: any) {
    console.error('获取优惠券列表失败:', error);
    ElMessage.error(error.response?.data?.message || error.message || '获取优惠券列表失败');
  } finally {
    couponsLoading.value = false;
  }
};

// 监听标签页切换，当切换到积分、优惠券或评价标签时自动刷新数据
watch(activeTab, async (newTab) => {
  if (newTab === 'coupons') {
    // 切换到优惠券标签时，刷新优惠券数据
    fetchUserCoupons();
  } else if (newTab === 'points') {
    // 切换到积分标签时，刷新积分数据
    console.log('切换到积分标签，刷新积分数据');
    pointsLoading.value = true; // 统一设置加载状态
    try {
      await Promise.all([
        fetchPoints(false), // 不单独设置加载状态，统一管理
        fetchPointTransactions(false), // 不单独设置加载状态，统一管理
      ]);
    } catch (error) {
      console.error('刷新积分数据失败:', error);
    } finally {
      // 确保加载状态被重置
      pointsLoading.value = false;
    }
  } else if (newTab === 'reviews') {
    // 切换到评价标签时，刷新评价数据
    console.log('切换到评价标签，刷新评价数据');
    try {
      await fetchReviews();
    } catch (error) {
      console.error('刷新评价数据失败:', error);
    }
  }
});

onMounted(async () => {
  // 检查登录状态
  const token = localStorage.getItem('token');
  if (!token) {
    // 未登录，跳转到登录页（路由守卫会处理，这里只是双重保险）
    router.push({
      path: '/frontend/login',
      query: { redirect: '/frontend/profile' },
    });
    return;
  }
  
  // 已登录，加载数据
  loading.value = true;
  try {
    await Promise.all([
      fetchUserInfo(),
      fetchPoints(),
      fetchPointTransactions(),
    ]);
    // 其他数据（订单、预约、评价）暂时使用模拟数据
    fetchOrders();
    fetchReservations();
    fetchReviews();
  } catch (error) {
    console.error('加载数据时发生错误:', error);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.avatar-uploader {
  display: inline-block;
}

.avatar-uploader :deep(.el-upload) {
  border: 1px dashed var(--el-border-color);
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: var(--el-transition-duration-fast);
}

.avatar-uploader :deep(.el-upload:hover) {
  border-color: var(--el-color-primary);
}

.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 100px;
  height: 100px;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar {
  width: 100px;
  height: 100px;
  display: block;
  object-fit: cover;
  border-radius: 6px;
}
</style>

