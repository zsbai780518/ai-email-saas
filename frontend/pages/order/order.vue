<template>
  <view class="container">
    <!-- VIP 状态卡片 -->
    <view class="vip-card" :class="'vip-' + (user.user_level || 1)">
      <view class="vip-header">
        <text class="vip-title">{{ vipInfo.title }}</text>
        <text class="vip-subtitle">{{ vipInfo.subtitle }}</text>
      </view>
      <view class="vip-expire" v-if="user.vip_expire_at">
        <text>有效期至：{{ user.vip_expire_at.substring(0, 10) }}</text>
      </view>
    </view>
    
    <!-- 套餐列表 -->
    <view class="section">
      <view class="section-title">可选套餐</view>
      
      <view class="plan-list">
        <view 
          class="plan-item" 
          v-for="plan in planList" 
          :key="plan.id"
          :class="{ selected: selectedPlanId === plan.id }"
          @click="selectPlan(plan.id)"
        >
          <view class="plan-header">
            <text class="plan-name">{{ plan.plan_name }}</text>
            <text class="plan-tag" v-if="plan.plan_code === 'enterprise'">企业版</text>
          </view>
          
          <view class="plan-price">
            <text class="price-monthly">¥{{ plan.price_monthly }}/月</text>
            <text class="price-yearly">¥{{ plan.price_yearly }}/年</text>
          </view>
          
          <view class="plan-features">
            <view class="feature-item" v-for="(feature, index) in parseFeatures(plan.features)" :key="index">
              <text class="feature-icon">✓</text>
              <text class="feature-text">{{ feature }}</text>
            </view>
          </view>
        </view>
      </view>
    </view>
    
    <!-- 计费周期选择 -->
    <view class="section">
      <view class="section-title">计费周期</view>
      <view class="cycle-selector">
        <view 
          class="cycle-item" 
          :class="{ active: billingCycle === 1 }"
          @click="billingCycle = 1"
        >
          <text>月度</text>
        </view>
        <view 
          class="cycle-item" 
          :class="{ active: billingCycle === 2 }"
          @click="billingCycle = 2"
        >
          <text>年度</text>
          <text class="discount-tag">省 17%</text>
        </view>
      </view>
    </view>
    
    <!-- 订单摘要 -->
    <view class="section" v-if="selectedPlan">
      <view class="section-title">订单摘要</view>
      <view class="order-summary">
        <view class="summary-row">
          <text class="summary-label">套餐</text>
          <text class="summary-value">{{ selectedPlan.plan_name }}</text>
        </view>
        <view class="summary-row">
          <text class="summary-label">周期</text>
          <text class="summary-value">{{ billingCycle === 1 ? '月度' : '年度' }}</text>
        </view>
        <view class="summary-row total">
          <text class="summary-label">总计</text>
          <text class="summary-value total-price">
            ¥{{ billingCycle === 1 ? selectedPlan.price_monthly : selectedPlan.price_yearly }}
          </text>
        </view>
      </view>
    </view>
    
    <!-- 支付方式 -->
    <view class="section">
      <view class="section-title">支付方式</view>
      <view class="payment-methods">
        <view 
          class="payment-item" 
          :class="{ selected: paymentMethod === 'wechat' }"
          @click="paymentMethod = 'wechat'"
        >
          <text class="payment-icon">💳</text>
          <text class="payment-name">微信支付</text>
        </view>
        <view 
          class="payment-item" 
          :class="{ selected: paymentMethod === 'alipay' }"
          @click="paymentMethod = 'alipay'"
        >
          <text class="payment-icon">💰</text>
          <text class="payment-name">支付宝</text>
        </view>
      </view>
    </view>
    
    <!-- 购买按钮 -->
    <view class="buy-btn-wrapper">
      <button class="buy-btn" @click="handleBuy">
        立即开通 ¥{{ selectedPlan && billingCycle === 1 ? selectedPlan.price_monthly : (selectedPlan ? selectedPlan.price_yearly : 0) }}
      </button>
    </view>
  </view>
</template>

<script>
import { orderApi, authApi } from '@/api/index.js';

export default {
  data() {
    return {
      user: {},
      planList: [],
      selectedPlanId: 2, // 默认个人 VIP
      billingCycle: 2, // 默认年度
      paymentMethod: 'wechat',
    };
  },
  computed: {
    selectedPlan() {
      return this.planList.find(p => p.id === this.selectedPlanId);
    },
    vipInfo() {
      const map = {
        1: { title: '免费版', subtitle: '基础邮箱功能' },
        2: { title: '个人 VIP', subtitle: '自定义域名 + 高阶 AI' },
        3: { title: '企业 VIP', subtitle: '多域名 + 团队管理' },
      };
      return map[this.user.user_level] || map[1];
    },
  },
  onLoad() {
    this.loadUserInfo();
    this.loadPlans();
  },
  methods: {
    async loadUserInfo() {
      try {
        const res = await authApi.userInfo();
        this.user = res.user;
      } catch (error) {
        console.error('获取用户信息失败:', error);
      }
    },
    
    async loadPlans() {
      try {
        const res = await orderApi.plans();
        this.planList = res.plans || [];
      } catch (error) {
        console.error('获取套餐失败:', error);
      }
    },
    
    selectPlan(id) {
      this.selectedPlanId = id;
    },
    
    async handleBuy() {
      if (!this.selectedPlan) {
        uni.showToast({ title: '请选择套餐', icon: 'none' });
        return;
      }
      
      try {
        // 创建订单
        const orderRes = await orderApi.create({
          plan_id: this.selectedPlanId,
          billing_cycle: this.billingCycle,
        });
        
        // 发起支付
        const payRes = await orderApi.pay({
          order_id: orderRes.order.id,
          payment_method: this.paymentMethod,
        });
        
        uni.showModal({
          title: '支付提示',
          content: '请前往支付页面完成支付（演示环境，模拟支付成功）',
          success: async (res) => {
            if (res.confirm) {
              // 模拟支付回调
              await orderApi.callback({
                order_id: orderRes.order.id,
                transaction_id: 'SIM_' + Date.now(),
                payment_method: this.paymentMethod,
              });
              
              uni.showToast({ title: '支付成功', icon: 'success' });
              
              // 刷新用户信息
              setTimeout(() => {
                this.loadUserInfo();
              }, 1000);
            }
          }
        });
        
      } catch (error) {
        console.error('购买失败:', error);
        uni.showToast({ title: error.message || '购买失败', icon: 'none' });
      }
    },
    
    parseFeatures(featuresStr) {
      if (!featuresStr) return [];
      try {
        return typeof featuresStr === 'string' ? JSON.parse(featuresStr) : featuresStr;
      } catch (e) {
        return [];
      }
    },
  },
};
</script>

<style>
.container {
  padding: 30rpx;
  min-height: 100vh;
  background: #f5f5f5;
}

.vip-card {
  border-radius: 20rpx;
  padding: 40rpx;
  margin-bottom: 30rpx;
  color: #ffffff;
}

.vip-1 {
  background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
}

.vip-2 {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.vip-3 {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.vip-title {
  display: block;
  font-size: 40rpx;
  font-weight: bold;
  margin-bottom: 10rpx;
}

.vip-subtitle {
  display: block;
  font-size: 26rpx;
  opacity: 0.9;
}

.vip-expire {
  margin-top: 20rpx;
  font-size: 24rpx;
  opacity: 0.8;
}

.section {
  background: #ffffff;
  border-radius: 20rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.section-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333333;
  margin-bottom: 20rpx;
}

.plan-list {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.plan-item {
  border: 2rpx solid #e0e0e0;
  border-radius: 15rpx;
  padding: 25rpx;
  transition: all 0.3s;
}

.plan-item.selected {
  border-color: #667eea;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.plan-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15rpx;
}

.plan-name {
  font-size: 32rpx;
  font-weight: bold;
  color: #333333;
}

.plan-tag {
  background: #f5576c;
  color: #ffffff;
  padding: 6rpx 16rpx;
  border-radius: 20rpx;
  font-size: 22rpx;
}

.plan-price {
  margin-bottom: 20rpx;
}

.price-monthly {
  display: block;
  font-size: 28rpx;
  color: #667eea;
  margin-bottom: 5rpx;
}

.price-yearly {
  display: block;
  font-size: 24rpx;
  color: #999999;
}

.plan-features {
  display: flex;
  flex-direction: column;
  gap: 10rpx;
}

.feature-item {
  display: flex;
  align-items: center;
}

.feature-icon {
  color: #52c41a;
  font-size: 28rpx;
  margin-right: 10rpx;
}

.feature-text {
  font-size: 26rpx;
  color: #666666;
}

.cycle-selector {
  display: flex;
  gap: 20rpx;
}

.cycle-item {
  flex: 1;
  text-align: center;
  padding: 20rpx;
  border: 2rpx solid #e0e0e0;
  border-radius: 10rpx;
  font-size: 28rpx;
  color: #666666;
  position: relative;
}

.cycle-item.active {
  border-color: #667eea;
  color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.discount-tag {
  display: block;
  font-size: 20rpx;
  color: #f5576c;
  margin-top: 5rpx;
}

.order-summary {
  display: flex;
  flex-direction: column;
  gap: 15rpx;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 28rpx;
}

.summary-label {
  color: #666666;
}

.summary-value {
  color: #333333;
}

.summary-row.total {
  border-top: 1rpx solid #e0e0e0;
  padding-top: 15rpx;
  margin-top: 10rpx;
}

.total-price {
  font-size: 36rpx;
  font-weight: bold;
  color: #f5576c;
}

.payment-methods {
  display: flex;
  gap: 20rpx;
}

.payment-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 30rpx;
  border: 2rpx solid #e0e0e0;
  border-radius: 15rpx;
}

.payment-item.selected {
  border-color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.payment-icon {
  font-size: 48rpx;
  margin-bottom: 10rpx;
}

.payment-name {
  font-size: 26rpx;
  color: #666666;
}

.buy-btn-wrapper {
  position: fixed;
  bottom: 30rpx;
  left: 30rpx;
  right: 30rpx;
}

.buy-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #ffffff;
  border: none;
  border-radius: 50rpx;
  height: 100rpx;
  line-height: 100rpx;
  font-size: 32rpx;
  box-shadow: 0 10rpx 30rpx rgba(102, 126, 234, 0.4);
}
</style>
