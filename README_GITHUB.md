# ai-email-saas

AI 智能邮箱 SaaS 系统 - 完整的云端邮箱服务

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.0-blue.svg)](https://www.thinkphp.cn/)
[![UniApp](https://img.shields.io/badge/UniApp-Vue3-green.svg)](https://uniapp.dcloud.net.cn/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange.svg)](https://www.mysql.com/)

## 📖 项目简介

AI 智能邮箱 SaaS 系统是一套完整的多租户邮箱服务平台，基于 ThinkPHP 8 + UniApp 构建，支持自定义域名、AI 智能功能、SaaS 计费系统。

### ✨ 核心特性

- 🏢 **多租户架构** - 完整的数据隔离和权限管理
- 🌐 **自定义域名** - VIP 用户可绑定专属域名
- 📧 **邮箱核心** - 完整的邮件收发、文件夹、标签管理
- 🤖 **AI 智能** - 智能撰写、分类、垃圾邮件识别
- 💰 **SaaS 计费** - 套餐管理、订单支付、订阅系统
- 📱 **跨平台** - UniApp 支持 H5/小程序/APP

## 🚀 快速开始

### 环境要求

- PHP 8.1+
- MySQL 8.0+
- Node.js 16+
- Nginx 1.20+

### 1. 克隆项目

```bash
git clone https://github.com/zsbai780518/ai-email-saas.git
cd ai-email-saas
```

### 2. 导入数据库

```bash
mysql -u root -p
CREATE DATABASE ai_email_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
mysql -u root -p ai_email_saas < database/schema.sql
```

### 3. 配置后端

编辑 `backend/.env`:

```env
APP_DEBUG=false
DB_HOST=127.0.0.1
DB_NAME=ai_email_saas
DB_USER=root
DB_PASS=your_password
DB_PORT=3306
```

### 4. 启动服务

```bash
# 后端
cd backend
php think run

# 前端
cd frontend
npm install
npm run dev:h5
```

访问：http://localhost:8000

## 📁 项目结构

```
ai-email-saas/
├── database/           # 数据库设计
│   └── schema.sql     # 28 张表完整 SQL
├── backend/           # ThinkPHP 后端
│   ├── app/
│   │   ├── controller/   # 8 个控制器
│   │   ├── model/        # 11 个模型
│   │   ├── middleware/   # 2 个中间件
│   │   └── service/      # JWT 服务
│   ├── config/         # 配置文件
│   └── route/          # 路由配置
├── frontend/          # UniApp 前端
│   ├── api/          # API 封装
│   ├── pages/        # 7 个页面
│   ├── pages.json    # 页面配置
│   └── package.json  # 依赖配置
├── README.md         # 本文件
├── DELIVERY.md       # 交付文档
└── LICENSE           # MIT 协议
```

## 📊 功能模块

### 1. 认证系统
- ✅ 用户注册/登录
- ✅ JWT Token 认证
- ✅ 密码修改

### 2. 多租户架构
- ✅ 租户数据隔离
- ✅ RBAC 权限管理
- ✅ 用户等级体系

### 3. 域名管理（VIP）
- ✅ 自定义域名绑定
- ✅ DNS 解析验证
- ✅ 域名状态监控

### 4. 邮箱核心
- ✅ 邮件收发
- ✅ 文件夹管理
- ✅ 标签系统
- ✅ 草稿箱

### 5. AI 功能
- ✅ 智能撰写
- ✅ 智能分类
- ✅ 垃圾邮件识别
- ✅ AI 配额管理

### 6. SaaS 计费
- ✅ VIP 套餐
- ✅ 订单支付
- ✅ 订阅管理

### 7. 后台管理
- ✅ 租户管理
- ✅ 域名审核
- ✅ 数据看板

## 💰 VIP 套餐

| 套餐 | 月费 | 年费 | 存储 | 域名 | 邮箱 | AI 配额 |
|------|------|------|------|------|------|--------|
| 免费版 | ¥0 | ¥0 | 1GB | 0 | 1 | 5 次/日 |
| 个人 VIP | ¥19 | ¥199 | 10GB | 1 | 5 | 50 次/日 |
| 企业 VIP | ¥99 | ¥999 | 100GB | 5 | 50 | 500 次/日 |

## 🔌 API 接口

### 认证接口
- `POST /api/register` - 注册
- `POST /api/login` - 登录
- `GET /api/user-info` - 用户信息

### 域名接口
- `GET /api/domain/index` - 域名列表
- `POST /api/domain/bind` - 绑定域名
- `POST /api/domain/verify-dns` - 验证 DNS

### 邮箱接口
- `GET /api/email/list` - 邮件列表
- `POST /api/email/send` - 发送邮件
- `POST /api/email/read` - 标记已读

### AI 接口
- `POST /api/ai/compose` - 智能撰写
- `POST /api/ai/categorize` - 智能分类
- `GET /api/ai/quota` - 配额查询

详见 [DELIVERY.md](DELIVERY.md)

## 🛡️ 安全建议

1. **生产环境** 修改 JWT 密钥
2. **启用 HTTPS** 保护数据传输
3. **配置防火墙** 限制访问
4. **定期备份** 数据库
5. **更新依赖** 保持最新版本

## 📄 License

MIT License - 详见 [LICENSE](LICENSE)

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📞 联系

- GitHub Issues: https://github.com/zsbai780518/ai-email-saas/issues
- Email: support@ai-email-saas.com

---

**Made with ❤️ by AI Developer**
