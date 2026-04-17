<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 路由配置
// +----------------------------------------------------------------------
use think\facade\Route;

// API 路由组
Route::group('api', function () {
    
    // 认证相关（无需登录）
    Route::post('register', 'Auth/register');
    Route::post('login', 'Auth/login');
    
    // 认证相关（需要登录）
    Route::group('', function () {
        Route::post('logout', 'Auth/logout');
        Route::post('refresh-token', 'Auth/refreshToken');
        Route::get('user-info', 'Auth/userInfo');
        Route::post('change-password', 'Auth/changePassword');
    })->middleware(\app\middleware\AuthMiddleware::class);
    
    // 域名管理（需要登录 + VIP）
    Route::group('domain', function () {
        Route::get('index', 'Domain/index');
        Route::get('detail', 'Domain/detail');
        Route::post('bind', 'Domain/bind');
        Route::post('verify-dns', 'Domain/verifyDns');
        Route::get('dns-records', 'Domain/getDnsRecords');
        Route::post('unbind', 'Domain/unbind');
    })->middleware(\app\middleware\AuthMiddleware::class);
    
    // 邮箱相关（需要登录）
    Route::group('email', function () {
        Route::get('list', 'Email/index');
        Route::get('detail', 'Email/detail');
        Route::post('send', 'Email/send');
        Route::post('read', 'Email/read');
        Route::post('delete', 'Email/delete');
        Route::post('spam', 'Email/spam');
        Route::post('star', 'Email/star');
    })->middleware(\app\middleware\AuthMiddleware::class);
    
    // 邮箱账号（需要登录）
    Route::group('mailbox', function () {
        Route::get('index', 'Mailbox/index');
        Route::post('create', 'Mailbox/create');
        Route::post('update', 'Mailbox/update');
        Route::post('delete', 'Mailbox/delete');
    })->middleware(\app\middleware\AuthMiddleware::class);
    
    // 订单/支付（需要登录）
    Route::group('order', function () {
        Route::get('index', 'Order/index');
        Route::get('detail', 'Order/detail');
        Route::post('create', 'Order/create');
        Route::post('pay', 'Order/pay');
        Route::get('plans', 'Order/plans');
    })->middleware(\app\middleware\AuthMiddleware::class);
    
    // AI 功能（需要登录）
    Route::group('ai', function () {
        Route::post('compose', 'Ai/compose');
        Route::post('categorize', 'Ai/categorize');
        Route::get('quota', 'Ai/quota');
    })->middleware(\app\middleware\AuthMiddleware::class);
    
})->allowCrossDomain();

// 默认路由
Route::get('/', function () {
    return 'AI 智能邮箱 SaaS 系统 API v1.0';
});
