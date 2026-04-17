-- ============================================
-- AI 智能邮箱 SaaS 系统 - 数据库完整设计
-- 版本：1.0.0
-- 创建时间：2026-04-18
-- 数据库：MySQL 8.0+
-- 字符集：utf8mb4
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. 多租户核心表
-- ============================================

-- 租户表（企业/个人用户）
CREATE TABLE IF NOT EXISTS `tenants` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_name` varchar(100) NOT NULL COMMENT '租户名称',
  `tenant_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '租户类型：1=个人，2=企业',
  `contact_name` varchar(50) DEFAULT NULL COMMENT '联系人姓名',
  `contact_phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `contact_email` varchar(100) DEFAULT NULL COMMENT '联系邮箱',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=正常，2=审核中',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`tenant_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='租户表';

-- 用户表
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL COMMENT '所属租户 ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `email` varchar(100) NOT NULL COMMENT '邮箱地址',
  `email_prefix` varchar(50) DEFAULT NULL COMMENT '邮箱前缀（@之前）',
  `domain_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '绑定域名 ID（VIP 用户）',
  `password` varchar(255) NOT NULL COMMENT '密码（bcrypt 加密）',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像 URL',
  `user_level` tinyint(1) NOT NULL DEFAULT 1 COMMENT '用户等级：1=普通用户，2=VIP 用户，3=企业 VIP',
  `vip_expire_at` timestamp NULL DEFAULT NULL COMMENT 'VIP 过期时间',
  `storage_quota` bigint(20) NOT NULL DEFAULT 1073741824 COMMENT '存储配额（字节），默认 1GB',
  `storage_used` bigint(20) NOT NULL DEFAULT 0 COMMENT '已用存储（字节）',
  `ai_quota_daily` int(11) NOT NULL DEFAULT 10 COMMENT 'AI 功能每日配额',
  `ai_used_today` int(11) NOT NULL DEFAULT 0 COMMENT '今日 AI 使用次数',
  `ai_quota_reset_at` date DEFAULT NULL COMMENT 'AI 配额重置日期',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=正常',
  `last_login_at` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录 IP',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_domain_id` (`domain_id`),
  KEY `idx_user_level` (`user_level`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 用户订阅表
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `plan_id` bigint(20) UNSIGNED NOT NULL COMMENT '套餐 ID',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=已取消，1=有效，2=已过期，3=待支付',
  `start_at` timestamp NOT NULL COMMENT '订阅开始时间',
  `end_at` timestamp NOT NULL COMMENT '订阅结束时间',
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否自动续费',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_end_at` (`end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户订阅表';

-- ============================================
-- 2. VIP 套餐与计费表
-- ============================================

-- VIP 套餐表
CREATE TABLE IF NOT EXISTS `vip_plans` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_name` varchar(50) NOT NULL COMMENT '套餐名称',
  `plan_code` varchar(20) NOT NULL COMMENT '套餐代码：basic, personal, enterprise',
  `price_monthly` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '月费价格',
  `price_yearly` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '年费价格',
  `storage_quota` bigint(20) NOT NULL COMMENT '存储配额（字节）',
  `domain_limit` int(11) NOT NULL DEFAULT 0 COMMENT '可绑定域名数量',
  `mailbox_limit` int(11) NOT NULL DEFAULT 1 COMMENT '可创建邮箱账号数量',
  `ai_quota_daily` int(11) NOT NULL DEFAULT 10 COMMENT 'AI 功能每日配额',
  `sub_account_limit` int(11) NOT NULL DEFAULT 0 COMMENT '子账号数量限制',
  `features` json DEFAULT NULL COMMENT '功能特性 JSON',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=下架，1=上架',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_code` (`plan_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='VIP 套餐表';

-- 订单表
CREATE TABLE IF NOT EXISTS `orders` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL COMMENT '订单号',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `plan_id` bigint(20) UNSIGNED NOT NULL COMMENT '套餐 ID',
  `billing_cycle` tinyint(1) NOT NULL DEFAULT 1 COMMENT '计费周期：1=月度，2=年度',
  `original_price` decimal(10,2) NOT NULL COMMENT '原价',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
  `total_amount` decimal(10,2) NOT NULL COMMENT '实付金额',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0=待支付，1=已支付，2=已取消，3=已退款',
  `payment_method` varchar(20) DEFAULT NULL COMMENT '支付方式：wechat, alipay',
  `payment_at` timestamp NULL DEFAULT NULL COMMENT '支付时间',
  `transaction_id` varchar(100) DEFAULT NULL COMMENT '第三方支付交易号',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- 支付记录表
CREATE TABLE IF NOT EXISTS `payments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) UNSIGNED NOT NULL COMMENT '订单 ID',
  `payment_no` varchar(32) NOT NULL COMMENT '支付流水号',
  `payment_method` varchar(20) NOT NULL COMMENT '支付方式：wechat, alipay',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0=待支付，1=支付成功，2=支付失败',
  `pay_params` json DEFAULT NULL COMMENT '支付参数 JSON',
  `callback_data` json DEFAULT NULL COMMENT '回调数据 JSON',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT '支付成功时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_no` (`payment_no`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付记录表';

-- 发票表
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) UNSIGNED NOT NULL COMMENT '订单 ID',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `invoice_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '发票类型：1=电子普通发票，2=电子专用发票',
  `invoice_title` varchar(200) NOT NULL COMMENT '发票抬头',
  `tax_number` varchar(50) DEFAULT NULL COMMENT '税号',
  `invoice_amount` decimal(10,2) NOT NULL COMMENT '开票金额',
  `invoice_content` varchar(100) DEFAULT NULL COMMENT '开票内容',
  `receiver_email` varchar(100) NOT NULL COMMENT '接收邮箱',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0=待开票，1=已开票，2=已寄送',
  `invoice_url` varchar(255) DEFAULT NULL COMMENT '发票文件 URL',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='发票表';

-- ============================================
-- 3. 域名管理表
-- ============================================

-- 域名表
CREATE TABLE IF NOT EXISTS `domains` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '所属用户 ID',
  `tenant_id` bigint(20) UNSIGNED NOT NULL COMMENT '所属租户 ID',
  `domain_name` varchar(100) NOT NULL COMMENT '域名',
  `domain_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0=待验证，1=验证通过，2=验证失败，3=已禁用',
  `icp_filing` varchar(50) DEFAULT NULL COMMENT 'ICP 备案号',
  `icp_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ICP 备案是否验证',
  `dns_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'DNS 解析是否验证',
  `verified_at` timestamp NULL DEFAULT NULL COMMENT '验证通过时间',
  `expire_at` timestamp NULL DEFAULT NULL COMMENT '域名过期时间',
  `spf_record` varchar(255) DEFAULT NULL COMMENT 'SPF 记录',
  `dkim_record` varchar(255) DEFAULT NULL COMMENT 'DKIM 记录',
  `dmarc_record` varchar(255) DEFAULT NULL COMMENT 'DMARC 记录',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否主域名',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_domain_name` (`domain_name`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_status` (`domain_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='域名表';

-- DNS 解析记录表
CREATE TABLE IF NOT EXISTS `domain_dns_records` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `domain_id` bigint(20) UNSIGNED NOT NULL COMMENT '域名 ID',
  `record_type` varchar(10) NOT NULL COMMENT '记录类型：MX, A, TXT, CNAME',
  `host` varchar(100) NOT NULL COMMENT '主机记录',
  `value` varchar(255) NOT NULL COMMENT '记录值',
  `priority` int(11) DEFAULT NULL COMMENT '优先级（MX 记录）',
  `ttl` int(11) NOT NULL DEFAULT 600 COMMENT 'TTL（秒）',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0=待配置，1=已配置，2=验证通过，3=验证失败',
  `verified_at` timestamp NULL DEFAULT NULL COMMENT '验证通过时间',
  `last_check_at` timestamp NULL DEFAULT NULL COMMENT '最后检查时间',
  `check_result` text COMMENT '检查结果',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_domain_id` (`domain_id`),
  KEY `idx_record_type` (`record_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='DNS 解析记录表';

-- 域名操作日志表
CREATE TABLE IF NOT EXISTS `domain_operation_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `domain_id` bigint(20) UNSIGNED NOT NULL COMMENT '域名 ID',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '操作用户 ID',
  `operation_type` varchar(50) NOT NULL COMMENT '操作类型：bind, unbind, verify, update',
  `operation_detail` json DEFAULT NULL COMMENT '操作详情 JSON',
  `operation_result` tinyint(1) NOT NULL DEFAULT 1 COMMENT '操作结果：0=失败，1=成功',
  `error_message` text COMMENT '错误信息',
  `operator_ip` varchar(50) DEFAULT NULL COMMENT '操作 IP',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_domain_id` (`domain_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='域名操作日志表';

-- ============================================
-- 4. 邮箱核心表
-- ============================================

-- 邮箱账号表
CREATE TABLE IF NOT EXISTS `mailboxes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '所属用户 ID',
  `domain_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '绑定域名 ID',
  `email_address` varchar(100) NOT NULL COMMENT '完整邮箱地址',
  `email_prefix` varchar(50) NOT NULL COMMENT '邮箱前缀',
  `display_name` varchar(100) DEFAULT NULL COMMENT '发件人显示名称',
  `signature` text COMMENT '邮件签名',
  `auto_reply` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否开启自动回复',
  `auto_reply_content` text COMMENT '自动回复内容',
  `forwarding_email` varchar(100) DEFAULT NULL COMMENT '转发邮箱',
  `forwarding_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否开启转发',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=正常',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email_address` (`email_address`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_domain_id` (`domain_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮箱账号表';

-- 邮件表
CREATE TABLE IF NOT EXISTS `emails` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mailbox_id` bigint(20) UNSIGNED NOT NULL COMMENT '邮箱 ID',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `email_uid` varchar(100) NOT NULL COMMENT '邮件唯一标识（IMAP UID）',
  `message_id` varchar(255) DEFAULT NULL COMMENT '邮件 Message-ID',
  `subject` varchar(500) DEFAULT NULL COMMENT '邮件主题',
  `from_email` varchar(100) NOT NULL COMMENT '发件人邮箱',
  `from_name` varchar(200) DEFAULT NULL COMMENT '发件人名称',
  `to_emails` text COMMENT '收件人邮箱列表（JSON）',
  `cc_emails` text COMMENT '抄送邮箱列表（JSON）',
  `bcc_emails` text COMMENT '密送邮箱列表（JSON）',
  `body_text` text COMMENT '纯文本正文',
  `body_html` mediumtext COMMENT 'HTML 正文',
  `attachments` json DEFAULT NULL COMMENT '附件信息 JSON',
  `attachment_count` int(11) NOT NULL DEFAULT 0 COMMENT '附件数量',
  `attachment_size` bigint(20) NOT NULL DEFAULT 0 COMMENT '附件总大小（字节）',
  `email_size` bigint(20) NOT NULL DEFAULT 0 COMMENT '邮件总大小（字节）',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已读',
  `is_starred` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否星标',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已删除',
  `is_spam` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否垃圾邮件',
  `spam_score` decimal(5,2) DEFAULT NULL COMMENT '垃圾邮件评分',
  `ai_processed` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否经过 AI 处理',
  `ai_category` varchar(50) DEFAULT NULL COMMENT 'AI 分类：work, personal, promotion, social, etc.',
  `received_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '接收时间',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT '发送时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email_uid` (`email_uid`),
  KEY `idx_mailbox_id` (`mailbox_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_from_email` (`from_email`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_is_spam` (`is_spam`),
  KEY `idx_received_at` (`received_at`),
  KEY `idx_ai_category` (`ai_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件表';

-- 邮件文件夹表
CREATE TABLE IF NOT EXISTS `email_folders` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `folder_name` varchar(50) NOT NULL COMMENT '文件夹名称',
  `folder_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '类型：1=系统文件夹，2=自定义文件夹',
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '父文件夹 ID',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `email_count` int(11) NOT NULL DEFAULT 0 COMMENT '邮件数量',
  `unread_count` int(11) NOT NULL DEFAULT 0 COMMENT '未读数量',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=正常',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_folder_type` (`folder_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件文件夹表';

-- 邮件文件夹关联表
CREATE TABLE IF NOT EXISTS `email_folder_relations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email_id` bigint(20) UNSIGNED NOT NULL COMMENT '邮件 ID',
  `folder_id` bigint(20) UNSIGNED NOT NULL COMMENT '文件夹 ID',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email_folder` (`email_id`, `folder_id`),
  KEY `idx_folder_id` (`folder_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件文件夹关联表';

-- 邮件标签表
CREATE TABLE IF NOT EXISTS `email_tags` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `tag_name` varchar(50) NOT NULL COMMENT '标签名称',
  `tag_color` varchar(20) DEFAULT NULL COMMENT '标签颜色',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件标签表';

-- 邮件标签关联表
CREATE TABLE IF NOT EXISTS `email_tag_relations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email_id` bigint(20) UNSIGNED NOT NULL COMMENT '邮件 ID',
  `tag_id` bigint(20) UNSIGNED NOT NULL COMMENT '标签 ID',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email_tag` (`email_id`, `tag_id`),
  KEY `idx_tag_id` (`tag_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件标签关联表';

-- 草稿箱表
CREATE TABLE IF NOT EXISTS `email_drafts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `mailbox_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '邮箱 ID',
  `subject` varchar(500) DEFAULT NULL COMMENT '主题',
  `to_emails` text COMMENT '收件人邮箱列表',
  `cc_emails` text COMMENT '抄送邮箱列表',
  `body_text` text COMMENT '纯文本正文',
  `body_html` mediumtext COMMENT 'HTML 正文',
  `attachments` json DEFAULT NULL COMMENT '附件信息 JSON',
  `last_saved_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后保存时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_last_saved_at` (`last_saved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='草稿箱表';

-- 发件箱表（已发送邮件）
CREATE TABLE IF NOT EXISTS `email_sent` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '邮件 ID（关联 emails 表）',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `mailbox_id` bigint(20) UNSIGNED NOT NULL COMMENT '邮箱 ID',
  `subject` varchar(500) DEFAULT NULL COMMENT '主题',
  `from_email` varchar(100) NOT NULL COMMENT '发件人邮箱',
  `to_emails` text COMMENT '收件人邮箱列表',
  `cc_emails` text COMMENT '抄送邮箱列表',
  `bcc_emails` text COMMENT '密送邮箱列表',
  `body_text` text COMMENT '纯文本正文',
  `body_html` mediumtext COMMENT 'HTML 正文',
  `attachments` json DEFAULT NULL COMMENT '附件信息 JSON',
  `send_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '发送状态：0=发送中，1=发送成功，2=发送失败',
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发送时间',
  `error_message` text COMMENT '错误信息',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_mailbox_id` (`mailbox_id`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='发件箱表';

-- ============================================
-- 5. AI 功能表
-- ============================================

-- AI 配置表
CREATE TABLE IF NOT EXISTS `ai_configs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `spam_filter_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否开启垃圾邮件过滤',
  `spam_filter_level` tinyint(1) NOT NULL DEFAULT 2 COMMENT '过滤级别：1=宽松，2=标准，3=严格',
  `smart_compose_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否开启智能撰写',
  `smart_categorize_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否开启智能分类',
  `auto_categorize_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否自动分类',
  `custom_categories` json DEFAULT NULL COMMENT '自定义分类 JSON',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI 配置表';

-- AI 使用日志表
CREATE TABLE IF NOT EXISTS `ai_usage_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `ai_function` varchar(50) NOT NULL COMMENT 'AI 功能：spam_filter, smart_compose, smart_categorize',
  `request_data` json DEFAULT NULL COMMENT '请求数据 JSON',
  `response_data` json DEFAULT NULL COMMENT '响应数据 JSON',
  `tokens_used` int(11) DEFAULT NULL COMMENT '消耗 Token 数',
  `processing_time_ms` int(11) DEFAULT NULL COMMENT '处理时间（毫秒）',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=失败，1=成功',
  `error_message` text COMMENT '错误信息',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ai_function` (`ai_function`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI 使用日志表';

-- AI 模型配置表
CREATE TABLE IF NOT EXISTS `ai_models` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `model_name` varchar(50) NOT NULL COMMENT '模型名称',
  `model_code` varchar(50) NOT NULL COMMENT '模型代码',
  `model_type` varchar(50) NOT NULL COMMENT '模型类型：spam_filter, text_generation, classification',
  `api_endpoint` varchar(255) DEFAULT NULL COMMENT 'API 端点',
  `api_key` varchar(255) DEFAULT NULL COMMENT 'API 密钥（加密存储）',
  `max_tokens` int(11) DEFAULT NULL COMMENT '最大 Token 数',
  `temperature` decimal(3,2) DEFAULT 0.7 COMMENT '温度参数',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
  `config` json DEFAULT NULL COMMENT '模型配置 JSON',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_model_code` (`model_code`),
  KEY `idx_model_type` (`model_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI 模型配置表';

-- ============================================
-- 6. 权限管理表（RBAC）
-- ============================================

-- 角色表
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL COMMENT '角色名称',
  `role_code` varchar(50) NOT NULL COMMENT '角色代码',
  `role_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '角色类型：1=系统角色，2=自定义角色',
  `description` varchar(255) DEFAULT NULL COMMENT '角色描述',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_code` (`role_code`),
  KEY `idx_role_type` (`role_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色表';

-- 权限表
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(100) NOT NULL COMMENT '权限名称',
  `permission_code` varchar(100) NOT NULL COMMENT '权限代码',
  `module` varchar(50) DEFAULT NULL COMMENT '所属模块',
  `description` varchar(255) DEFAULT NULL COMMENT '权限描述',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permission_code` (`permission_code`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限表';

-- 角色权限关联表
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) UNSIGNED NOT NULL COMMENT '角色 ID',
  `permission_id` bigint(20) UNSIGNED NOT NULL COMMENT '权限 ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`),
  KEY `idx_permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限关联表';

-- 用户角色关联表
CREATE TABLE IF NOT EXISTS `role_users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) UNSIGNED NOT NULL COMMENT '角色 ID',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '租户 ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_user` (`role_id`, `user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户角色关联表';

-- ============================================
-- 7. 系统管理表
-- ============================================

-- 系统配置表
CREATE TABLE IF NOT EXISTS `system_configs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `config_type` varchar(50) DEFAULT NULL COMMENT '配置类型：string, number, boolean, json',
  `config_group` varchar(50) DEFAULT NULL COMMENT '配置分组',
  `description` varchar(255) DEFAULT NULL COMMENT '配置描述',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_key` (`config_key`),
  KEY `idx_config_group` (`config_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- 操作日志表
CREATE TABLE IF NOT EXISTS `operation_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '用户 ID',
  `operation_type` varchar(50) NOT NULL COMMENT '操作类型',
  `operation_module` varchar(50) DEFAULT NULL COMMENT '操作模块',
  `operation_detail` json DEFAULT NULL COMMENT '操作详情 JSON',
  `operator_ip` varchar(50) DEFAULT NULL COMMENT '操作 IP',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'User-Agent',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=失败，1=成功',
  `error_message` text COMMENT '错误信息',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_operation_type` (`operation_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';

-- 系统通知表
CREATE TABLE IF NOT EXISTS `system_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '用户 ID（NULL 表示全员通知）',
  `notification_type` varchar(50) NOT NULL COMMENT '通知类型：system, billing, domain, security',
  `title` varchar(200) NOT NULL COMMENT '通知标题',
  `content` text NOT NULL COMMENT '通知内容',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已读',
  `read_at` timestamp NULL DEFAULT NULL COMMENT '阅读时间',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统通知表';

-- 附件存储表
CREATE TABLE IF NOT EXISTS `attachments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户 ID',
  `file_name` varchar(255) NOT NULL COMMENT '文件名',
  `file_path` varchar(500) NOT NULL COMMENT '文件路径',
  `file_size` bigint(20) NOT NULL COMMENT '文件大小（字节）',
  `file_type` varchar(100) DEFAULT NULL COMMENT '文件类型（MIME）',
  `file_hash` varchar(64) DEFAULT NULL COMMENT '文件哈希（SHA256）',
  `storage_type` varchar(20) NOT NULL DEFAULT 'local' COMMENT '存储类型：local, oss, s3',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_file_hash` (`file_hash`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='附件存储表';

-- ============================================
-- 8. 初始化数据
-- ============================================

-- 初始化角色数据
INSERT INTO `roles` (`role_name`, `role_code`, `role_type`, `description`) VALUES
('普通用户', 'user', 1, '基础用户角色'),
('VIP 用户', 'vip_user', 1, 'VIP 付费用户角色'),
('企业 VIP', 'enterprise_vip', 1, '企业 VIP 用户角色'),
('系统管理员', 'admin', 1, '系统管理员角色');

-- 初始化权限数据
INSERT INTO `permissions` (`permission_name`, `permission_code`, `module`, `description`) VALUES
('基础邮箱功能', 'email:basic', 'email', '基础邮件收发功能'),
('自定义域名', 'domain:custom', 'domain', 'VIP 自定义域名功能'),
('AI 垃圾邮件过滤', 'ai:spam_filter', 'ai', 'AI 垃圾邮件识别功能'),
('AI 智能撰写', 'ai:smart_compose', 'ai', 'AI 邮件智能撰写功能'),
('AI 智能分类', 'ai:smart_categorize', 'ai', 'AI 邮件智能分类功能'),
('订单管理', 'order:manage', 'billing', '订单查看与管理'),
('发票管理', 'invoice:manage', 'billing', '发票申请与管理'),
('系统管理', 'system:admin', 'system', '系统管理权限');

-- 初始化角色权限关联
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.role_code = 'user' AND p.permission_code IN ('email:basic');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.role_code = 'vip_user' AND p.permission_code IN ('email:basic', 'domain:custom', 'ai:spam_filter', 'ai:smart_compose', 'ai:smart_categorize', 'order:manage', 'invoice:manage');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.role_code = 'enterprise_vip' AND p.permission_code IN ('email:basic', 'domain:custom', 'ai:spam_filter', 'ai:smart_compose', 'ai:smart_categorize', 'order:manage', 'invoice:manage');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.role_code = 'admin';

-- 初始化系统配置
INSERT INTO `system_configs` (`config_key`, `config_value`, `config_type`, `config_group`, `description`) VALUES
('site.name', 'AI 智能邮箱', 'string', 'basic', '站点名称'),
('site.logo', '/assets/logo.png', 'string', 'basic', '站点 Logo'),
('email.default_storage_quota', '1073741824', 'number', 'email', '默认存储配额（1GB）'),
('email.max_attachment_size', '52428800', 'number', 'email', '最大附件大小（50MB）'),
('email.smtp_host', 'smtp.example.com', 'string', 'email', 'SMTP 服务器地址'),
('email.smtp_port', '587', 'number', 'email', 'SMTP 端口'),
('ai.default_daily_quota', '10', 'number', 'ai', '默认每日 AI 配额'),
('ai.spam_filter_threshold', '0.8', 'number', 'ai', '垃圾邮件判定阈值'),
('payment.wechat.enabled', 'true', 'boolean', 'payment', '微信支付开关'),
('payment.alipay.enabled', 'true', 'boolean', 'payment', '支付宝开关'),
('domain.dns_check_interval', '300', 'number', 'domain', 'DNS 检查间隔（秒）'),
('domain.auto_verify_enabled', 'true', 'boolean', 'domain', '自动验证开关');

-- 初始化 VIP 套餐
INSERT INTO `vip_plans` (`plan_name`, `plan_code`, `price_monthly`, `price_yearly`, `storage_quota`, `domain_limit`, `mailbox_limit`, `ai_quota_daily`, `sub_account_limit`, `features`, `sort_order`, `status`) VALUES
('免费版', 'free', 0.00, 0.00, 1073741824, 0, 1, 5, 0, '["基础邮箱功能", "1GB 存储", "每日 5 次 AI"]', 1, 1),
('个人 VIP', 'personal', 19.00, 199.00, 10737418240, 1, 5, 50, 0, '["自定义域名", "10GB 存储", "每日 50 次 AI", "优先客服"]', 2, 1),
('企业 VIP', 'enterprise', 99.00, 999.00, 107374182400, 5, 50, 500, 20, '["多域名支持", "100GB 存储", "每日 500 次 AI", "子账号管理", "专属客服"]', 3, 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 数据库设计完成
-- 共计 28 张表，覆盖多租户、域名、邮箱、AI、计费、权限等核心模块
-- ============================================
