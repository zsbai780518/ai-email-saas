<template>
  <view class="container">
    <!-- VIP 提示 -->
    <view class="vip-notice" v-if="!isVip">
      <text class="notice-text">⚠️ 域名管理功能仅限 VIP 用户使用</text>
      <button class="btn-upgrade" @click="goToUpgrade">立即升级</button>
    </view>
    
    <!-- 域名列表 -->
    <view class="domain-list" v-if="isVip">
      <view class="domain-item" v-for="item in domainList" :key="item.id">
        <view class="domain-info">
          <text class="domain-name">{{ item.domain_name }}</text>
          <view class="domain-status">
            <text 
              class="status-tag" 
              :class="'status-' + item.domain_status"
            >
              {{ getStatusText(item.domain_status) }}
            </text>
          </view>
        </view>
        <view class="domain-actions">
          <button 
            class="btn-verify" 
            v-if="item.domain_status === 0"
            @click="handleVerify(item.id)"
          >验证 DNS</button>
          <button 
            class="btn-delete" 
            @click="handleUnbind(item.id)"
          >解绑</button>
        </view>
      </view>
      
      <view class="empty-list" v-if="domainList.length === 0">
        <text class="empty-text">暂无绑定域名</text>
      </view>
    </view>
    
    <!-- 绑定域名按钮 -->
    <view class="bind-btn-wrapper" v-if="isVip">
      <button class="btn-bind" @click="showBindDialog = true">+ 绑定域名</button>
    </view>
    
    <!-- 绑定域名弹窗 -->
    <view class="dialog" v-if="showBindDialog">
      <view class="dialog-mask" @click="showBindDialog = false"></view>
      <view class="dialog-content">
        <view class="dialog-title">绑定域名</view>
        
        <view class="form-item">
          <text class="label">域名</text>
          <input 
            class="input" 
            v-model="bindForm.domain_name" 
            placeholder="example.com"
          />
        </view>
        
        <view class="form-item">
          <text class="label">ICP 备案号（可选）</text>
          <input 
            class="input" 
            v-model="bindForm.icp_filing" 
            placeholder="京 ICP 备 xxxxx 号"
          />
        </view>
        
        <view class="dialog-actions">
          <button class="btn-cancel" @click="showBindDialog = false">取消</button>
          <button class="btn-confirm" @click="handleBind">确定</button>
        </view>
      </view>
    </view>
    
    <!-- DNS 配置指引弹窗 -->
    <view class="dialog" v-if="showDnsDialog">
      <view class="dialog-mask" @click="showDnsDialog = false"></view>
      <view class="dialog-content">
        <view class="dialog-title">DNS 解析配置</view>
        
        <view class="dns-notice">
          <text class="notice-title">请按以下配置添加 DNS 解析记录：</text>
        </view>
        
        <view class="dns-list">
          <view class="dns-item" v-for="item in dnsRecords" :key="item.id">
            <view class="dns-row">
              <text class="dns-label">类型：</text>
              <text class="dns-value">{{ item.record_type }}</text>
            </view>
            <view class="dns-row">
              <text class="dns-label">主机记录：</text>
              <text class="dns-value">{{ item.host }}</text>
            </view>
            <view class="dns-row">
              <text class="dns-label">记录值：</text>
              <text class="dns-value dns-copy" @click="copyText(item.value)">{{ item.value }}</text>
            </view>
            <view class="dns-row" v-if="item.priority">
              <text class="dns-label">优先级：</text>
              <text class="dns-value">{{ item.priority }}</text>
            </view>
            <view class="dns-row">
              <text class="dns-label">TTL：</text>
              <text class="dns-value">{{ item.ttl }}s</text>
            </view>
          </view>
        </view>
        
        <view class="dialog-actions">
          <button class="btn-cancel" @click="showDnsDialog = false">关闭</button>
          <button class="btn-confirm" @click="handleVerifyDns">验证 DNS</button>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import { domainApi } from '@/api/index.js';

export default {
  data() {
    return {
      isVip: false,
      domainList: [],
      showBindDialog: false,
      showDnsDialog: false,
      currentDomainId: 0,
      bindForm: {
        domain_name: '',
        icp_filing: '',
      },
      dnsRecords: [],
    };
  },
  onLoad() {
    this.checkVipStatus();
  },
  methods: {
    checkVipStatus() {
      const userStr = uni.getStorageSync('user');
      if (userStr) {
        const user = JSON.parse(userStr);
        this.isVip = user.user_level >= 2;
      }
      
      if (this.isVip) {
        this.loadDomainList();
      }
    },
    
    async loadDomainList() {
      try {
        const res = await domainApi.list();
        this.domainList = res.list || [];
      } catch (error) {
        console.error('获取域名列表失败:', error);
      }
    },
    
    async handleBind() {
      if (!this.bindForm.domain_name) {
        uni.showToast({ title: '请输入域名', icon: 'none' });
        return;
      }
      
      try {
        await domainApi.bind(this.bindForm);
        uni.showToast({ title: '绑定成功', icon: 'success' });
        
        this.showBindDialog = false;
        this.bindForm.domain_name = '';
        this.bindForm.icp_filing = '';
        
        this.loadDomainList();
      } catch (error) {
        console.error('绑定域名失败:', error);
      }
    },
    
    async handleVerify(id) {
      this.currentDomainId = id;
      
      try {
        const res = await domainApi.dnsRecords(id);
        this.dnsRecords = res.dns_records || [];
        this.showDnsDialog = true;
      } catch (error) {
        console.error('获取 DNS 记录失败:', error);
      }
    },
    
    async handleVerifyDns() {
      try {
        await domainApi.verifyDns(this.currentDomainId);
        uni.showToast({ title: '验证成功', icon: 'success' });
        
        this.showDnsDialog = false;
        this.loadDomainList();
      } catch (error) {
        console.error('验证 DNS 失败:', error);
      }
    },
    
    async handleUnbind(id) {
      uni.showModal({
        title: '确认解绑',
        content: '解绑后该域名邮箱将无法使用，确定继续吗？',
        success: async (res) => {
          if (res.confirm) {
            try {
              await domainApi.unbind(id);
              uni.showToast({ title: '解绑成功', icon: 'success' });
              this.loadDomainList();
            } catch (error) {
              console.error('解绑域名失败:', error);
            }
          }
        }
      });
    },
    
    getStatusText(status) {
      const map = {
        0: '待验证',
        1: '已验证',
        2: '验证失败',
        3: '已禁用',
      };
      return map[status] || '未知';
    },
    
    goToUpgrade() {
      uni.navigateTo({ url: '/pages/order/order' });
    },
    
    copyText(text) {
      uni.setClipboardData({
        data: text,
        success: () => {
          uni.showToast({ title: '已复制', icon: 'success' });
        }
      });
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

.vip-notice {
  background: #fff3cd;
  border-radius: 20rpx;
  padding: 30rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30rpx;
}

.notice-text {
  font-size: 28rpx;
  color: #856404;
}

.btn-upgrade {
  background: #ffc107;
  color: #333333;
  border: none;
  border-radius: 10rpx;
  padding: 10rpx 30rpx;
  font-size: 26rpx;
}

.domain-list {
  margin-bottom: 100rpx;
}

.domain-item {
  background: #ffffff;
  border-radius: 20rpx;
  padding: 30rpx;
  margin-bottom: 20rpx;
}

.domain-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20rpx;
}

.domain-name {
  font-size: 32rpx;
  font-weight: bold;
  color: #333333;
}

.status-tag {
  padding: 6rpx 16rpx;
  border-radius: 30rpx;
  font-size: 24rpx;
}

.status-0 { background: #fff3cd; color: #856404; }
.status-1 { background: #d4edda; color: #155724; }
.status-2 { background: #f8d7da; color: #721c24; }
.status-3 { background: #e2e3e5; color: #383d41; }

.domain-actions {
  display: flex;
  gap: 20rpx;
}

.btn-verify,
.btn-delete {
  flex: 1;
  height: 70rpx;
  line-height: 70rpx;
  border-radius: 10rpx;
  font-size: 26rpx;
}

.btn-verify {
  background: #667eea;
  color: #ffffff;
  border: none;
}

.btn-delete {
  background: #ffffff;
  color: #dc3545;
  border: 2rpx solid #dc3545;
}

.empty-list {
  text-align: center;
  padding: 100rpx 0;
}

.empty-text {
  color: #999999;
  font-size: 28rpx;
}

.bind-btn-wrapper {
  position: fixed;
  bottom: 30rpx;
  left: 30rpx;
  right: 30rpx;
}

.btn-bind {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #ffffff;
  border: none;
  border-radius: 50rpx;
  height: 100rpx;
  line-height: 100rpx;
  font-size: 32rpx;
  box-shadow: 0 10rpx 30rpx rgba(102, 126, 234, 0.4);
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
  max-height: 80vh;
  overflow-y: auto;
}

.dialog-title {
  font-size: 36rpx;
  font-weight: bold;
  color: #333333;
  text-align: center;
  margin-bottom: 40rpx;
}

.form-item {
  margin-bottom: 30rpx;
}

.label {
  display: block;
  font-size: 28rpx;
  color: #666666;
  margin-bottom: 10rpx;
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

.dns-notice {
  margin-bottom: 20rpx;
}

.notice-title {
  font-size: 28rpx;
  color: #333333;
}

.dns-list {
  max-height: 400rpx;
  overflow-y: auto;
}

.dns-item {
  background: #f9f9f9;
  border-radius: 10rpx;
  padding: 20rpx;
  margin-bottom: 15rpx;
}

.dns-row {
  display: flex;
  margin-bottom: 10rpx;
}

.dns-label {
  font-size: 24rpx;
  color: #999999;
  width: 140rpx;
}

.dns-value {
  font-size: 24rpx;
  color: #333333;
  flex: 1;
}

.dns-copy {
  color: #667eea;
  text-decoration: underline;
}
</style>
