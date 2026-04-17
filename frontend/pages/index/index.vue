<template>
  <view class="container">
    <!-- 用户信息卡片 -->
    <view class="user-card">
      <view class="user-info">
        <text class="username">{{ user.username || '用户' }}</text>
        <text class="user-email">{{ user.email }}</text>
      </view>
      <view class="vip-tag" v-if="user.user_level >= 2">VIP</view>
    </view>
    
    <!-- 功能入口 -->
    <view class="grid-menu">
      <view class="menu-item" @click="goToMailbox">
        <view class="menu-icon">📧</view>
        <text class="menu-text">邮箱管理</text>
      </view>
      
      <view class="menu-item" @click="goToDomain">
        <view class="menu-icon">🌐</view>
        <text class="menu-text">域名管理</text>
      </view>
      
      <view class="menu-item" @click="goToOrder">
        <view class="menu-icon">👑</view>
        <text class="menu-text">会员中心</text>
      </view>
      
      <view class="menu-item" @click="goToSettings">
        <view class="menu-icon">⚙️</view>
        <text class="menu-text">系统设置</text>
      </view>
    </view>
    
    <!-- 存储使用情况 -->
    <view class="storage-card">
      <view class="card-title">存储空间</view>
      <view class="storage-info">
        <text class="storage-used">{{ formatSize(user.storage_used || 0) }}</text>
        <text class="storage-total"> / {{ formatSize(user.storage_quota || 0) }}</text>
      </view>
      <view class="storage-bar">
        <view 
          class="storage-progress" 
          :style="{ width: storagePercent + '%' }"
        ></view>
      </view>
    </view>
    
    <!-- AI 配额 -->
    <view class="ai-card">
      <view class="card-title">AI 功能配额</view>
      <view class="ai-info">
        <text class="ai-used">{{ user.ai_used_today || 0 }}</text>
        <text class="ai-total"> / {{ user.ai_quota_daily || 0 }} 次/日</text>
      </view>
    </view>
  </view>
</template>

<script>
import { authApi } from '@/api/index.js';

export default {
  data() {
    return {
      user: {
        username: '',
        email: '',
        user_level: 1,
        storage_used: 0,
        storage_quota: 0,
        ai_used_today: 0,
        ai_quota_daily: 0,
      },
    };
  },
  computed: {
    storagePercent() {
      if (!this.user.storage_quota) return 0;
      return Math.min(100, (this.user.storage_used / this.user.storage_quota) * 100);
    },
  },
  onLoad() {
    this.loadUserInfo();
  },
  onShow() {
    this.loadUserInfo();
  },
  methods: {
    async loadUserInfo() {
      try {
        const userStr = uni.getStorageSync('user');
        if (userStr) {
          this.user = JSON.parse(userStr);
        }
        
        const res = await authApi.userInfo();
        this.user = res.user;
        uni.setStorageSync('user', JSON.stringify(res.user));
      } catch (error) {
        console.error('获取用户信息失败:', error);
      }
    },
    
    goToMailbox() {
      uni.switchTab({ url: '/pages/mailbox/mailbox' });
    },
    
    goToDomain() {
      uni.switchTab({ url: '/pages/domain/domain' });
    },
    
    goToOrder() {
      uni.navigateTo({ url: '/pages/order/order' });
    },
    
    goToSettings() {
      uni.switchTab({ url: '/pages/settings/settings' });
    },
    
    formatSize(bytes) {
      if (bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    },
  },
};
</script>

<style>
.container {
  padding: 30rpx;
  background: #f5f5f5;
  min-height: 100vh;
}

.user-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20rpx;
  padding: 40rpx;
  margin-bottom: 30rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.username {
  font-size: 36rpx;
  font-weight: bold;
  color: #ffffff;
  margin-bottom: 10rpx;
}

.user-email {
  font-size: 26rpx;
  color: rgba(255, 255, 255, 0.8);
}

.vip-tag {
  background: #ffd700;
  color: #333333;
  padding: 10rpx 20rpx;
  border-radius: 30rpx;
  font-size: 24rpx;
  font-weight: bold;
}

.grid-menu {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20rpx;
  margin-bottom: 30rpx;
}

.menu-item {
  background: #ffffff;
  border-radius: 20rpx;
  padding: 40rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.menu-icon {
  font-size: 60rpx;
  margin-bottom: 20rpx;
}

.menu-text {
  font-size: 28rpx;
  color: #333333;
}

.storage-card,
.ai-card {
  background: #ffffff;
  border-radius: 20rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.card-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333333;
  margin-bottom: 20rpx;
}

.storage-info,
.ai-info {
  margin-bottom: 15rpx;
}

.storage-used,
.ai-used {
  font-size: 32rpx;
  font-weight: bold;
  color: #667eea;
}

.storage-total,
.ai-total {
  font-size: 28rpx;
  color: #999999;
}

.storage-bar {
  height: 12rpx;
  background: #f0f0f0;
  border-radius: 6rpx;
  overflow: hidden;
}

.storage-progress {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 6rpx;
  transition: width 0.3s;
}
</style>
