<template>
  <view class="container">
    <!-- 文件夹导航 -->
    <view class="folder-nav">
      <view 
        class="folder-item" 
        :class="{ active: currentFolder === item.key }"
        v-for="item in folders" 
        :key="item.key"
        @click="switchFolder(item.key)"
      >
        <text class="folder-icon">{{ item.icon }}</text>
        <text class="folder-name">{{ item.name }}</text>
        <text class="folder-count" v-if="item.count > 0">{{ item.count }}</text>
      </view>
    </view>
    
    <!-- 邮件列表 -->
    <view class="email-list">
      <view 
        class="email-item" 
        v-for="item in emailList" 
        :key="item.id"
        @click="goToDetail(item.id)"
      >
        <view class="email-header">
          <text class="email-from">{{ item.from_name || item.from_email }}</text>
          <text class="email-time">{{ formatTime(item.received_at) }}</text>
        </view>
        <view class="email-subject">
          <text class="subject-text" :class="{ unread: !item.is_read }">{{ item.subject }}</text>
        </view>
        <view class="email-preview">
          <text class="preview-text">{{ item.body_text ? item.body_text.substring(0, 50) + '...' : '' }}</text>
        </view>
        <view class="email-actions">
          <text class="action-icon" @click.stop="handleStar(item.id)">{{ item.is_starred ? '⭐' : '☆' }}</text>
          <text class="action-icon" @click.stop="handleDelete(item.id)">🗑️</text>
        </view>
      </view>
      
      <view class="empty-list" v-if="emailList.length === 0">
        <text class="empty-text">暂无邮件</text>
      </view>
    </view>
    
    <!-- 撰写按钮 -->
    <view class="compose-btn-wrapper">
      <button class="compose-btn" @click="goToCompose">✏️ 写邮件</button>
    </view>
  </view>
</template>

<script>
import { emailApi } from '@/api/index.js';

export default {
  data() {
    return {
      currentFolder: 'inbox',
      folders: [
        { key: 'inbox', name: '收件箱', icon: '📥', count: 0 },
        { key: 'sent', name: '已发送', icon: '📤', count: 0 },
        { key: 'draft', name: '草稿箱', icon: '📝', count: 0 },
        { key: 'spam', name: '垃圾邮件', icon: '🚫', count: 0 },
        { key: 'starred', name: '星标', icon: '⭐', count: 0 },
      ],
      emailList: [],
      page: 1,
      pageSize: 20,
    };
  },
  onLoad() {
    this.loadEmails();
  },
  onPullDownRefresh() {
    this.page = 1;
    this.loadEmails().then(() => {
      uni.stopPullDownRefresh();
    });
  },
  methods: {
    async loadEmails() {
      try {
        const res = await emailApi.list({
          folder: this.currentFolder,
          page: this.page,
          page_size: this.pageSize,
        });
        
        this.emailList = res.list || [];
        
        // 更新文件夹计数
        if (this.currentFolder === 'inbox') {
          this.folders[0].count = res.total || 0;
        }
      } catch (error) {
        console.error('加载邮件失败:', error);
      }
    },
    
    switchFolder(key) {
      this.currentFolder = key;
      this.page = 1;
      this.loadEmails();
    },
    
    goToDetail(id) {
      uni.navigateTo({ url: '/pages/email/detail?id=' + id });
    },
    
    goToCompose() {
      uni.navigateTo({ url: '/pages/email/compose' });
    },
    
    async handleStar(id) {
      try {
        await emailApi.star(id);
        uni.showToast({ title: '已添加星标', icon: 'success' });
        this.loadEmails();
      } catch (error) {
        console.error('添加星标失败:', error);
      }
    },
    
    async handleDelete(id) {
      uni.showModal({
        title: '确认删除',
        content: '确定要删除这封邮件吗？',
        success: async (res) => {
          if (res.confirm) {
            try {
              await emailApi.delete(id);
              uni.showToast({ title: '已删除', icon: 'success' });
              this.loadEmails();
            } catch (error) {
              console.error('删除失败:', error);
            }
          }
        }
      });
    },
    
    formatTime(timeStr) {
      const date = new Date(timeStr);
      const now = new Date();
      const diff = now - date;
      
      if (diff < 60000) return '刚刚';
      if (diff < 3600000) return Math.floor(diff / 60000) + '分钟前';
      if (diff < 86400000) return Math.floor(diff / 3600000) + '小时前';
      if (diff < 604800000) return Math.floor(diff / 86400000) + '天前';
      
      return date.toLocaleDateString();
    },
  },
};
</script>

<style>
.container {
  min-height: 100vh;
  background: #f5f5f5;
}

.folder-nav {
  background: #ffffff;
  padding: 20rpx;
  display: flex;
  gap: 20rpx;
  overflow-x: auto;
  border-bottom: 1rpx solid #f0f0f0;
}

.folder-item {
  display: flex;
  align-items: center;
  padding: 15rpx 25rpx;
  background: #f5f5f5;
  border-radius: 30rpx;
  white-space: nowrap;
}

.folder-item.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.folder-icon {
  font-size: 32rpx;
  margin-right: 10rpx;
}

.folder-name {
  font-size: 26rpx;
  color: #333333;
}

.folder-item.active .folder-name {
  color: #ffffff;
}

.folder-count {
  background: #ff4757;
  color: #ffffff;
  font-size: 20rpx;
  padding: 4rpx 12rpx;
  border-radius: 20rpx;
  margin-left: 10rpx;
}

.email-list {
  padding: 20rpx;
}

.email-item {
  background: #ffffff;
  border-radius: 15rpx;
  padding: 25rpx;
  margin-bottom: 20rpx;
  position: relative;
}

.email-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15rpx;
}

.email-from {
  font-size: 28rpx;
  font-weight: bold;
  color: #333333;
  max-width: 400rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.email-time {
  font-size: 24rpx;
  color: #999999;
}

.email-subject {
  margin-bottom: 10rpx;
}

.subject-text {
  font-size: 28rpx;
  color: #333333;
}

.subject-text.unread {
  font-weight: bold;
  color: #000000;
}

.email-preview {
  margin-bottom: 15rpx;
}

.preview-text {
  font-size: 24rpx;
  color: #999999;
}

.email-actions {
  display: flex;
  gap: 20rpx;
  justify-content: flex-end;
}

.action-icon {
  font-size: 32rpx;
  padding: 10rpx;
}

.empty-list {
  text-align: center;
  padding: 100rpx 0;
}

.empty-text {
  color: #999999;
  font-size: 28rpx;
}

.compose-btn-wrapper {
  position: fixed;
  bottom: 30rpx;
  right: 30rpx;
}

.compose-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #ffffff;
  border: none;
  border-radius: 50rpx;
  padding: 20rpx 40rpx;
  font-size: 30rpx;
  box-shadow: 0 10rpx 30rpx rgba(102, 126, 234, 0.4);
}
</style>
