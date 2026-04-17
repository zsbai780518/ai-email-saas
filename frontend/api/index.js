/**
 * API 请求封装
 */

// API 基础地址
const BASE_URL = 'http://localhost:8080/api';

/**
 * 请求封装
 */
function request(options) {
  return new Promise((resolve, reject) => {
    // 获取 Token
    const token = uni.getStorageSync('token') || '';
    
    uni.request({
      url: BASE_URL + options.url,
      method: options.method || 'GET',
      data: options.data || {},
      header: {
        'Content-Type': 'application/json',
        'Authorization': token ? 'Bearer ' + token : '',
      },
      success: (res) => {
        if (res.statusCode === 200) {
          if (res.data.code === 200) {
            resolve(res.data.data);
          } else if (res.data.code === 401) {
            // Token 过期，跳转登录
            uni.removeStorageSync('token');
            uni.removeStorageSync('user');
            uni.reLaunch({ url: '/pages/login/login' });
            reject(new Error('登录已过期'));
          } else {
            uni.showToast({
              title: res.data.message || '请求失败',
              icon: 'none'
            });
            reject(new Error(res.data.message));
          }
        } else {
          uni.showToast({
            title: '网络错误',
            icon: 'none'
          });
          reject(new Error('网络错误'));
        }
      },
      fail: (err) => {
        uni.showToast({
          title: '网络错误',
          icon: 'none'
        });
        reject(err);
      }
    });
  });
}

/**
 * 认证 API
 */
export const authApi = {
  // 登录
  login: (data) => request({ url: '/login', method: 'POST', data }),
  // 注册
  register: (data) => request({ url: '/register', method: 'POST', data }),
  // 登出
  logout: () => request({ url: '/logout', method: 'POST' }),
  // 获取用户信息
  userInfo: () => request({ url: '/user-info', method: 'GET' }),
  // 修改密码
  changePassword: (data) => request({ url: '/change-password', method: 'POST', data }),
};

/**
 * 域名 API
 */
export const domainApi = {
  // 域名列表
  list: () => request({ url: '/domain/index', method: 'GET' }),
  // 域名详情
  detail: (id) => request({ url: '/domain/detail', method: 'GET', data: { id } }),
  // 绑定域名
  bind: (data) => request({ url: '/domain/bind', method: 'POST', data }),
  // 验证 DNS
  verifyDns: (id) => request({ url: '/domain/verify-dns', method: 'POST', data: { id } }),
  // 获取 DNS 记录
  dnsRecords: (id) => request({ url: '/domain/dns-records', method: 'GET', data: { id } }),
  // 解绑域名
  unbind: (id) => request({ url: '/domain/unbind', method: 'POST', data: { id } }),
};

/**
 * 邮箱 API
 */
export const emailApi = {
  // 邮件列表
  list: (params) => request({ url: '/email/list', method: 'GET', data: params }),
  // 邮件详情
  detail: (id) => request({ url: '/email/detail', method: 'GET', data: { id } }),
  // 发送邮件
  send: (data) => request({ url: '/email/send', method: 'POST', data }),
  // 标记已读
  read: (id) => request({ url: '/email/read', method: 'POST', data: { id } }),
  // 删除邮件
  delete: (id) => request({ url: '/email/delete', method: 'POST', data: { id } }),
  // 标记垃圾邮件
  spam: (id) => request({ url: '/email/spam', method: 'POST', data: { id } }),
  // 标记星标
  star: (id) => request({ url: '/email/star', method: 'POST', data: { id } }),
};

/**
 * 订单 API
 */
export const orderApi = {
  // 套餐列表
  plans: () => request({ url: '/order/plans', method: 'GET' }),
  // 订单列表
  list: () => request({ url: '/order/index', method: 'GET' }),
  // 创建订单
  create: (data) => request({ url: '/order/create', method: 'POST', data }),
  // 支付订单
  pay: (data) => request({ url: '/order/pay', method: 'POST', data }),
};

/**
 * AI 功能 API
 */
export const aiApi = {
  // 智能撰写
  compose: (data) => request({ url: '/ai/compose', method: 'POST', data }),
  // 智能分类
  categorize: (data) => request({ url: '/ai/categorize', method: 'POST', data }),
  // 获取配额
  quota: () => request({ url: '/ai/quota', method: 'GET' }),
};

export default request;
