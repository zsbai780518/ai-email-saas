<template>
  <view class="container">
    <view class="logo">
      <text class="logo-text">AI 智能邮箱</text>
      <text class="logo-sub">SaaS 云端邮箱服务</text>
    </view>
    
    <view class="form">
      <view class="input-group">
        <input 
          class="input" 
          type="text" 
          v-model="form.email" 
          placeholder="请输入邮箱地址"
        />
      </view>
      
      <view class="input-group">
        <input 
          class="input" 
          type="password" 
          v-model="form.password" 
          placeholder="请输入密码"
        />
      </view>
      
      <view class="btn-group">
        <button class="btn-primary" @click="handleLogin">登录</button>
        <button class="btn-secondary" @click="handleRegister">注册</button>
      </view>
    </view>
    
    <view class="footer">
      <text class="footer-text">登录即代表您同意《用户协议》和《隐私政策》</text>
    </view>
  </view>
</template>

<script>
import { authApi } from '@/api/index.js';

export default {
  data() {
    return {
      form: {
        email: '',
        password: '',
      },
      isLogin: true,
    };
  },
  methods: {
    async handleLogin() {
      if (!this.form.email || !this.form.password) {
        uni.showToast({ title: '请填写完整信息', icon: 'none' });
        return;
      }
      
      try {
        const res = await authApi.login(this.form);
        
        // 保存 Token 和用户信息
        uni.setStorageSync('token', res.token);
        uni.setStorageSync('user', JSON.stringify(res.user));
        
        uni.showToast({ title: '登录成功', icon: 'success' });
        
        // 跳转首页
        setTimeout(() => {
          uni.switchTab({ url: '/pages/index/index' });
        }, 1000);
        
      } catch (error) {
        console.error('登录失败:', error);
      }
    },
    
    async handleRegister() {
      if (!this.form.email || !this.form.password) {
        uni.showToast({ title: '请填写完整信息', icon: 'none' });
        return;
      }
      
      try {
        const res = await authApi.register({
          ...this.form,
          username: this.form.email.split('@')[0],
          tenant_name: '个人租户',
        });
        
        // 保存 Token 和用户信息
        uni.setStorageSync('token', res.token);
        uni.setStorageSync('user', JSON.stringify(res.user));
        
        uni.showToast({ title: '注册成功', icon: 'success' });
        
        // 跳转首页
        setTimeout(() => {
          uni.switchTab({ url: '/pages/index/index' });
        }, 1000);
        
      } catch (error) {
        console.error('注册失败:', error);
      }
    },
  },
};
</script>

<style>
.container {
  padding: 40rpx;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.logo {
  text-align: center;
  margin-top: 100rpx;
  margin-bottom: 80rpx;
}

.logo-text {
  display: block;
  font-size: 48rpx;
  font-weight: bold;
  color: #ffffff;
  margin-bottom: 20rpx;
}

.logo-sub {
  display: block;
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.8);
}

.form {
  background: #ffffff;
  border-radius: 20rpx;
  padding: 40rpx;
}

.input-group {
  margin-bottom: 30rpx;
}

.input {
  height: 88rpx;
  background: #f5f5f5;
  border-radius: 10rpx;
  padding: 0 30rpx;
  font-size: 28rpx;
}

.btn-group {
  margin-top: 40rpx;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #ffffff;
  border: none;
  border-radius: 10rpx;
  height: 88rpx;
  line-height: 88rpx;
  font-size: 32rpx;
  margin-bottom: 20rpx;
}

.btn-secondary {
  background: #ffffff;
  color: #667eea;
  border: 2rpx solid #667eea;
  border-radius: 10rpx;
  height: 88rpx;
  line-height: 88rpx;
  font-size: 32rpx;
}

.footer {
  margin-top: 60rpx;
  text-align: center;
}

.footer-text {
  color: rgba(255, 255, 255, 0.6);
  font-size: 24rpx;
}
</style>
