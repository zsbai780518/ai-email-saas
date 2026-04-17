# AI 智能邮箱 SaaS 系统

完整的 AI 智能邮箱 SaaS 系统，支持多租户架构、VIP 自定义域名、AI 智能功能、计费系统。

## 技术栈

- **后端**: ThinkPHP 8 + PHP 8.1+
- **前端**: UniApp + Vue3
- **数据库**: MySQL 8.0+
- **服务器**: Nginx + Linux

## 核心功能

### 1. 多租户 SaaS 架构
- 租户数据隔离（独立 tenant_id）
- 用户等级权限体系（普通用户/VIP/企业 VIP）
- RBAC 权限管理

### 2. VIP 自定义域名
- 域名绑定与配置
- DNS 解析自动校验
- SPF/DKIM/DMARC 安全验证
- 域名状态监控

### 3. 邮箱核心功能
- 邮件收发（SMTP/IMAP 集成）
- 邮件管理（文件夹、标签、搜索）
- 草稿箱、发件箱

### 4. AI 智能功能
- AI 垃圾邮件识别
- AI 智能撰写
- AI 收件人智能分类

### 5. SaaS 计费系统
- VIP 套餐管理（免费版/个人 VIP/企业 VIP）
- 订单与支付（微信/支付宝）
- 订阅周期管理
- 发票管理

## 项目结构

```
ai-email-saas/
├── database/
│   └── schema.sql              # 数据库设计（28 张表）
├── backend/                    # ThinkPHP 后端
│   ├── app/
│   │   ├── controller/
│   │   │   ├── Base.php       # 基础控制器
│   │   │   ├── Auth.php       # 认证控制器
│   │   │   ├── Domain.php     # 域名管理控制器
│   │   ├── model/
│   │   │   ├── User.php       # 用户模型
│   │   │   ├── Tenant.php     # 租户模型
│   │   │   ├── Domain.php     # 域名模型
│   │   │   ├── Mailbox.php    # 邮箱模型
│   │   │   ├── Email.php      # 邮件模型
│   │   ├── middleware/
│   │   │   ├── TenantMiddleware.php  # 多租户中间件
│   │   │   └── AuthMiddleware.php    # JWT 认证中间件
│   │   └── service/
│   │       └── JwtService.php        # JWT 服务
│   ├── config/
│   │   └── database.php       # 数据库配置
│   └── route/
│       └── app.php            # 路由配置
└── frontend/                   # UniApp 前端
    ├── api/
    │   └── index.js           # API 封装
    ├── pages/
    │   ├── login/             # 登录注册页
    │   ├── index/             # 首页
    │   ├── mailbox/           # 邮箱管理
    │   ├── domain/            # 域名管理
    │   ├── order/             # 会员中心
    │   └── settings/          # 设置
    ├── pages.json             # 页面配置
    └── package.json           # 依赖配置
```

## 快速开始

### 1. 数据库初始化

```bash
mysql -u root -p ai_email_saas < database/schema.sql
```

### 2. 后端配置

编辑 `backend/.env`:

```env
APP_DEBUG=false
DB_HOST=127.0.0.1
DB_NAME=ai_email_saas
DB_USER=root
DB_PASS=your_password
DB_PORT=3306
```

### 3. 启动后端

```bash
cd backend
php think run
```

访问：http://localhost:8000

### 4. 启动前端

```bash
cd frontend
npm install
npm run dev:h5
```

访问：http://localhost:5173

## API 文档

### 认证接口

| 接口 | 方法 | 说明 |
|------|------|------|
| /api/register | POST | 用户注册 |
| /api/login | POST | 用户登录 |
| /api/logout | POST | 退出登录 |
| /api/user-info | GET | 获取用户信息 |
| /api/change-password | POST | 修改密码 |

### 域名接口（需 VIP）

| 接口 | 方法 | 说明 |
|------|------|------|
| /api/domain/index | GET | 域名列表 |
| /api/domain/detail | GET | 域名详情 |
| /api/domain/bind | POST | 绑定域名 |
| /api/domain/verify-dns | POST | 验证 DNS |
| /api/domain/dns-records | GET | 获取 DNS 记录 |
| /api/domain/unbind | POST | 解绑域名 |

### 邮箱接口

| 接口 | 方法 | 说明 |
|------|------|------|
| /api/email/list | GET | 邮件列表 |
| /api/email/detail | GET | 邮件详情 |
| /api/email/send | POST | 发送邮件 |
| /api/email/read | POST | 标记已读 |
| /api/email/delete | POST | 删除邮件 |

## VIP 套餐

| 套餐 | 价格（月/年） | 存储 | 域名 | 邮箱账号 | AI 配额 |
|------|-------------|------|------|---------|--------|
| 免费版 | ¥0 | 1GB | 0 | 1 | 5 次/日 |
| 个人 VIP | ¥19/¥199 | 10GB | 1 | 5 | 50 次/日 |
| 企业 VIP | ¥99/¥999 | 100GB | 5 | 50 | 500 次/日 |

## 数据库设计

共 28 张表，覆盖以下模块：

1. **多租户核心** (3 张): tenants, users, subscriptions
2. **计费系统** (4 张): vip_plans, orders, payments, invoices
3. **域名管理** (3 张): domains, domain_dns_records, domain_operation_logs
4. **邮箱核心** (8 张): mailboxes, emails, email_folders, email_folder_relations, email_tags, email_tag_relations, email_drafts, email_sent
5. **AI 功能** (3 张): ai_configs, ai_usage_logs, ai_models
6. **权限管理** (4 张): roles, permissions, role_permissions, role_users
7. **系统管理** (4 张): system_configs, operation_logs, system_notifications, attachments

## 开发进度

- ✅ Phase 1: 数据库设计（28 张表）
- ✅ Phase 2: 后端核心架构（中间件、JWT、模型、控制器）
- ✅ Phase 3: 域名管理模块（完整 CRUD + DNS 验证）
- ✅ Phase 4: 前端基础（登录、首页、域名管理）
- ✅ Phase 5: 邮箱核心功能（收发、文件夹、标签、草稿箱）
- ✅ Phase 6: AI 功能模块（智能撰写、智能分类、垃圾邮件识别）
- ✅ Phase 7: SaaS 计费系统（套餐、订单、支付、订阅）
- ✅ Phase 8: 后台管理系统（租户管理、域名审核、数据看板）
- ✅ Phase 9: 前端完善（邮箱、会员、设置页面）

## 部署说明

### 服务器要求

- PHP 8.1+
- MySQL 8.0+
- Nginx 1.20+
- Redis（可选，用于缓存）

### Nginx 配置

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/backend/public;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

## 安全建议

1. 生产环境务必修改 JWT 密钥
2. 启用 HTTPS
3. 配置防火墙规则
4. 定期备份数据库
5. 开启邮件 SPF/DKIM/DMARC 验证

## License

Proprietary - 商业软件

## 联系方式

技术支持：support@ai-email-saas.com
