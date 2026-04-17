<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 数据库配置文件
// +----------------------------------------------------------------------

return [
    // 默认数据库连接
    'default' => 'mysql',
    
    // 数据库连接配置
    'connections' => [
        'mysql' => [
            // 数据库类型
            'type' => 'mysql',
            // 服务器地址
            'hostname' => env('DB_HOST', '127.0.0.1'),
            // 数据库名称
            'database' => env('DB_NAME', 'ai_email_saas'),
            // 用户名
            'username' => env('DB_USER', 'root'),
            // 密码
            'password' => env('DB_PASS', ''),
            // 端口
            'hostport' => env('DB_PORT', '3306'),
            // 数据库连接参数
            'params' => [],
            // 数据库编码默认采用 utf8mb4
            'charset' => 'utf8mb4',
            // 数据库表前缀
            'prefix' => '',
            // 数据库部署方式：common 集中式多服务器单一 master 主库
            'deploy' => 0,
            // 数据库读写是否分离
            'rw_separate' => false,
            // 读写分离后 master 服务器数量
            'master_num' => 1,
            // 读写分离后 slave 服务器序号
            'slave_no' => '',
            // 是否严格检查字段及查询类型
            'debug' => env('APP_DEBUG', false),
            // 数据库字段缓存
            'fields_cache' => false,
            // 字段缓存路径
            'schema_cache_path' => runtime_path() . 'schema/',
            // 监听 SQL
            'trigger_sql' => env('APP_DEBUG', false),
            // 启用字段缓存
            'fields_cache_enable' => false,
        ],
    ],
    
    // 多租户配置
    'tenant' => [
        // 是否启用多租户
        'enabled' => true,
        // 租户 ID 字段名
        'tenant_id_field' => 'tenant_id',
        // 需要多租户隔离的表（不在此列表的表不受多租户限制）
        'tables' => [
            'users',
            'mailboxes',
            'emails',
            'email_folders',
            'email_folder_relations',
            'email_tags',
            'email_tag_relations',
            'email_drafts',
            'email_sent',
            'ai_configs',
            'ai_usage_logs',
            'domains',
            'subscriptions',
            'orders',
            'payments',
            'invoices',
            'attachments',
            'system_notifications',
        ],
    ],
];
