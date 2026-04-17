<template>
  <view class="container">
    <!-- 用户信息 -->
    <view class="user-section">
      <view class="avatar-wrapper">
        <view class="avatar">{{ user.username ? user.username[0].toUpperCase() : 'U' }}</view>
      </view>
      <view class="user-info">
        <text class="username">{{ user.username || '用户' }}</text>
        <text class="user-email">{{ user.email }}</text>
      </view>
    </view>
    
    <!-- 功能列表 -->
    <view class="menu-section">
      <view class="menu-item" @click="showChangePassword = true">
        <text class="menu-icon">🔐</text>
        <text class="menu-text">修改密码</text>
        <text class="menu-arrow">›</text>
      </view>
      
      <view class="menu-item" @click="goToAiSettings">
        <text class="menu-icon">🤖</text>
        <text class="menu-text">AI 功能设置</text>
        <text class="menu-arrow">›</text>
      </view>
      
      <view class="menu-item" @click="goToMailboxSettings">
        <text class="menu-icon">📧</text>
        <text class="menu-text">邮箱设置</text>
        <text class="menu-arrow">›</text>
      </view>
      
      <view class="menu-item" @click="goToDomainSettings">
        <text class="menu-icon">🌐</text>
        <text class="menu-text">域名管理</text>
        <text class="menu-arrow">›</text>
      </view>
    </view>
    
    <!-- 存储信息 -->
    <view class="menu-section">
      <view class="storage-info">
        <view class="storage-header">
          <text class="storage-title">存储空间</text>
          <text class="storage-text">{{ formatSize(user.storage_used) }} / {{ formatSize(user.storage_quota) }}</text>
        </view>
        <view class="storage-bar">
          <view class="storage-progress" :style="{ width: storagePercent + '%' }"></view>
        </view>
      </view>
    </view>
    
    <!-- AI 配额 -->
    <view class="menu-section">
      <view class="ai-info">
        <view class="ai-header">
          <text class="ai-title">AI 配额</text>
          <text class="ai-text">{{ user.ai_used_today || 0 }} / {{ user.ai_quota_daily || 0 }} 次/日</text>
        </view>
        <view class="ai-bar">
          <view class="ai-progress" :style="{ width: aiPercent + '%' }"></view>
        </view>
      </view>
    </view>
    
    <!-- 其他 -->
    <view class="menu-section">
      <view class="menu-item" @click="showAbout = true">
        <text class="menu-icon">ℹ️</text>
        <text class="menu-text">关于我们</text>
        <text class="menu-arrow">›</text>
      </view>
      
      <view class="menu-item" @click="checkUpdate">
        <text class="menu-icon">🔄</text>
        <text class="menu-text">检查更新</text>
        <text class="menu-arrow">›</text>
      </view>
    </view>
    
    <!-- 退出登录 -->
    <view class="logout-section">
      <button class="logout-btn" @click="handleLogout">退出登录</button>
    </view>
    
    <!-- 修改密码弹窗 -->
    <view class="dialog" v-if="showChangePassword">
      <view class="dialog-mask" @click="showChangePassword = false"></view>
      <view class="dialog-content">
        <view class="dialog-title">修改密码</view>
        <view class="form-item">
          <input class="input" type="password" v-model="passwordForm.old_password" placeholder="原密码" />
        </view>
        <view class="form-item">
          <input class="input" type="password" v-model="passwordForm.new_password" placeholder="新密码" />
        </view>
        <view class="dialog-actions">
          <button class="btn-cancel" @click="showChangePassword = false">取消</button>
          <button class="btn-confirm" @click="handleChangePassword">确定</button>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import { authApi } from '@/api/index.js';

export default {
  data() {
    return {
      user: {},
      showChangePassword: false,
      passwordForm: {
        old_password: '',
        new_password: '',
      },
    };
  },
  computed: {
    storagePercent() {
      if (!this.user.storage_quota) return 0;
      return Math.min(100, (this.user.storage_used / this.user.storage_quota) * 100);
    },
    aiPercent() {
      if (!this.user.ai_quota_daily) return 0;
      return Math.min(100, ((this.user.ai_used_today || 0) / this.user.ai_quota_daily) * 100);
    },
  },
  onLoad() {
    this.loadUserInfo();
  },
  methods: {
    async loadUserInfo() {
      try {
        const res = await authApi.userInfo();
        this.user = res.user;
        uni.setStorageSync('user', JSON.stringify(res.user));
      } catch (error) {
        console.error('获取用户信息失败:', error);
      }
    },
    
    async handleChangePassword() {
      if (!this.passwordForm.old_password || !this.passwordForm.new_password) {
        uni.showToast({ title: '请填写完整信息', icon: 'none' });
        return;
      }
      
      if (this.passwordForm.new_password.length < 6) {
        uni.showToast({ title: '密码至少 6 位', icon: 'none' });
        return;
      }
      
      try {
        await authApi.changePassword(this.passwordForm);
        uni.showToast({ title: '密码修改成功', icon: 'success' });
        
        this.showChangePassword = false;
        this.passwordForm.old_password = '';
        this.passwordForm.new_password = '';
      } catch (error) {
        console.error('修改密码失败:', error);
      }
    },
    
    goToAiSettings() {
      uni.showToast({ title: '功能开发中', icon: 'none' });
    },
    
    goToMailboxSettings() {
      uni.navigateTo({ url: '/pages/mailbox/mailbox' });
    },
    
    goToDomainSettings() {
      uni.switchTab({ url: '/pages/domain/domain' });
    },
    
    showAbout() {
      uni.showModal({
        title: '关于 AI 智能邮箱',
        content: '版本：v1.0.0\n\nAI 智能邮箱 SaaS 系统\n提供多租户邮箱服务、自定义域名、AI 智能功能',
        showCancel: false
      });
    },
    
    checkUpdate() {
      uni.showToast({ title: '已是最新版本', icon: 'none' });
    },
    
    async handleLogout() {
      uni.showModal({
        title: '确认退出',
        content: '确定要退出登录吗？',
        success: async (res) => {
          if (res.confirm) {
            try {
              await authApi.logout();
            } catch (e) {}
            
            uni.clearStorageSync();
            uni.reLaunch({ url: '/pages/login/login' });
          }
        }
      });
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
  min-height: 100vh;
  background: #f5f5f5;
}

.user-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 60rpx 30rpx 40rpx;
  display: flex;
  align-items: center;
}

.avatar-wrapper {
  margin-right: 30rpx;
}

.avatar {
  width: 120rpx;
  height: 120rpx;
  border-radius: 60rpx;
  background: rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 48rpx;
  font-weight: bold;
  color: #ffffff;
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

.menu-section {
  background: #ffffff;
  margin-top: 30rpx;
  padding: 0 30rpx;
}

.menu-item {
  display: flex;
  align-items: center;
  padding: 30rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
}

.menu-item:last-child {
  border-bottom: none;
}

.menu-icon {
  font-size: 40rpx;
  margin-right: 20rpx;
}

.menu-text {
  flex: 1;
  font-size: 30rpx;
  color: #333333;
}

.menu-arrow {
  font-size: 40rpx;
  color: #cccccc;
}

.storage-info,
.ai-info {
  padding: 30rpx 0;
}

.storage-header,
.ai-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15rpx;
}

.storage-title,
.ai-title {
  font-size: 30rpx;
  color: #333333;
}

.storage-text,
.ai-text {
  font-size: 26rpx;
  color: #999999;
}

.storage-bar,
.ai-bar {
  height: 12rpx;
  background: #f0f0f0;
  border-radius: 6rpx;
  overflow: hidden;
}

.storage-progress {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 6rpx;
}

.ai-progress {
  height: 100%;
  background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
  border-radius: 6rpx;
}

.logout-section {
  margin: 40rpx 30rpx;
}

.logout-btn {
  background: #ffffff;
  color: #dc3545;
  border: 2rpx solid #dc3545;
  border-radius: 50rpx;
  height: 90rpx;
  line-height: 90rpx;
  font-size: 30rpx;
}

.dialog {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
}

.dialog-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
}

.dialog-content {
  position: absolute;
  top: 50%;
  left: 30rpx;
  right: 30rpx;
  transform: translateY(-50%);
  background: #ffffff;
  border-radius: 20rpx;
  padding: 40rpx;
}

.dialog-title {
  font-size: 36rpx;
  font-weight: bold;
  color: #333333;
  text-align: center;
  margin-bottom: 40rpx;
}

.form-item {
  margin-bottom: 20rpx;
}

.input {
  height: 80rpx;
  background: #f5f5f5;
  border-radius: 10rpx;
  padding: 0 20rpx;
  font-size: 28rpx;
}

.dialog-actions {
  display: flex;
  gap: 20rpx;
  margin-top: 40rpx;
}

.btn-cancel,
.btn-confirm {
  flex: 1;
  height: 80rpx;
  line-height: 80rpx;
  border-radius: 10rpx;
  font-size: 28rpx;
}

.btn-cancel {
  background: #f5f5f5;
  color: #666666;
  border: none;
}

.btn-confirm {
  background: #667eea;
  color: #ffffff;
  border: none;
}
</style>
