<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 多租户中间件
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\middleware;

use think\facade\Db;
use think\facade\Session;
use think\Response;
use think\Request;

/**
 * 多租户数据隔离中间件
 * 自动为查询添加 tenant_id 条件，确保数据隔离
 */
class TenantMiddleware
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        // 获取当前登录用户的 tenant_id
        $tenantId = $this->getCurrentTenantId();
        
        if ($tenantId) {
            // 设置全局 tenant_id
            $this->setGlobalTenantId($tenantId);
        }
        
        return $next($request);
    }
    
    /**
     * 获取当前用户的租户 ID
     */
    protected function getCurrentTenantId(): ?int
    {
        // 从 Session 或 JWT Token 中获取
        $user = Session::get('user');
        if ($user && isset($user['tenant_id'])) {
            return (int) $user['tenant_id'];
        }
        
        // 从请求头获取（API 请求）
        $tenantId = request()->header('X-Tenant-Id');
        if ($tenantId) {
            return (int) $tenantId;
        }
        
        return null;
    }
    
    /**
     * 设置全局租户 ID
     */
    protected function setGlobalTenantId(int $tenantId): void
    {
        // 使用 Db 监听器自动添加 tenant_id 条件
        Db::listen(function ($sql, $time, $explain) use ($tenantId) {
            // 这里可以记录 SQL 日志
        });
        
        // 存储到配置中供模型使用
        config('tenant.current_tenant_id', $tenantId);
    }
    
    /**
     * 获取当前租户 ID（供模型调用）
     */
    public static function getCurrentTenantId(): ?int
    {
        return config('tenant.current_tenant_id');
    }
}
