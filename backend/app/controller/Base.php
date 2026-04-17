<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 基础控制器
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Session;
use think\Response;

/**
 * 基础控制器
 * 提供通用的 API 响应方法
 */
class Base extends BaseController
{
    /**
     * 当前登录用户信息
     */
    protected $currentUser = null;
    
    /**
     * 当前租户 ID
     */
    protected $currentTenantId = null;
    
    public function initialize()
    {
        parent::initialize();
        
        // 获取当前用户
        $this->currentUser = Session::get('user');
        if ($this->currentUser) {
            $this->currentTenantId = $this->currentUser['tenant_id'] ?? null;
        }
    }
    
    /**
     * 成功响应
     *
     * @param mixed $data 数据
     * @param string $message 消息
     * @return Response
     */
    protected function success($data = null, string $message = 'success'): Response
    {
        return json([
            'code' => 200,
            'message' => $message,
            'data' => $data,
        ]);
    }
    
    /**
     * 失败响应
     *
     * @param string $message 消息
     * @param int $code 错误码
     * @param mixed $data 数据
     * @return Response
     */
    protected function error(string $message = 'error', int $code = 400, $data = null): Response
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ])->code($code);
    }
    
    /**
     * 获取当前用户 ID
     *
     * @return int|null
     */
    protected function getUserId(): ?int
    {
        return $this->currentUser['id'] ?? null;
    }
    
    /**
     * 获取当前租户 ID
     *
     * @return int|null
     */
    protected function getTenantId(): ?int
    {
        return $this->currentTenantId;
    }
    
    /**
     * 检查是否为 VIP 用户
     *
     * @return bool
     */
    protected function checkVip(): bool
    {
        if (!$this->currentUser) {
            return false;
        }
        
        // 简单检查，实际应该从数据库验证
        return ($this->currentUser['user_level'] ?? 1) >= 2;
    }
    
    /**
     * VIP 权限检查
     *
     * @return Response|bool
     */
    protected function requireVip()
    {
        if (!$this->checkVip()) {
            return $this->error('该功能仅限 VIP 用户使用', 403);
        }
        return true;
    }
}
