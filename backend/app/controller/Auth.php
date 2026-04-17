<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 认证控制器
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\controller;

use app\model\User;
use app\model\Tenant;
use app\service\JwtService;
use think\facade\Validate;
use think\facade\Session;

/**
 * 认证控制器
 * 处理用户登录、注册、登出等
 */
class Auth extends Base
{
    /**
     * JWT 服务
     */
    protected JwtService $jwtService;
    
    public function initialize()
    {
        parent::initialize();
        $this->jwtService = new JwtService();
    }
    
    /**
     * 用户注册
     *
     * @return \think\Response
     */
    public function register()
    {
        $param = $this->request->param();
        
        // 验证参数
        $validate = Validate::rule([
            'username' => 'require|length:3,20',
            'email' => 'require|email',
            'password' => 'require|length:6,32',
            'tenant_name' => 'require|length:2,50',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        // 检查邮箱是否已存在
        if (User::where('email', $param['email'])->find()) {
            return $this->error('邮箱已被注册');
        }
        
        // 开启事务
        User::db()->startTrans();
        
        try {
            // 创建租户
            $tenant = Tenant::create([
                'tenant_name' => $param['tenant_name'],
                'tenant_type' => 1, // 个人租户
                'status' => 1,
            ]);
            
            // 创建用户
            $user = User::create([
                'tenant_id' => $tenant->id,
                'username' => $param['username'],
                'email' => $param['email'],
                'password' => password_hash($param['password'], PASSWORD_DEFAULT),
                'user_level' => 1, // 普通用户
                'storage_quota' => 1073741824, // 1GB
                'ai_quota_daily' => 5,
                'status' => 1,
            ]);
            
            User::db()->commit();
            
            // 生成 Token
            $token = $this->jwtService->generateToken([
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
            ]);
            
            return $this->success([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'user_level' => $user->user_level,
                ],
                'token' => $token,
            ], '注册成功');
            
        } catch (\Exception $e) {
            User::db()->rollback();
            return $this->error('注册失败：' . $e->getMessage());
        }
    }
    
    /**
     * 用户登录
     *
     * @return \think\Response
     */
    public function login()
    {
        $param = $this->request->param();
        
        // 验证参数
        $validate = Validate::rule([
            'email' => 'require|email',
            'password' => 'require',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        // 查找用户
        $user = User::where('email', $param['email'])->find();
        
        if (!$user) {
            return $this->error('用户不存在');
        }
        
        // 验证密码
        if (!$user->verifyPassword($param['password'])) {
            return $this->error('密码错误');
        }
        
        // 检查用户状态
        if ($user->status != 1) {
            return $this->error('账号已被禁用');
        }
        
        // 生成 Token
        $token = $this->jwtService->generateToken([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
        
        // 更新最后登录时间
        $user->update([
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $this->request->ip(),
        ]);
        
        return $this->success([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'user_level' => $user->user_level,
                'avatar' => $user->avatar,
            ],
            'token' => $token,
        ], '登录成功');
    }
    
    /**
     * 退出登录
     *
     * @return \think\Response
     */
    public function logout()
    {
        Session::clear();
        return $this->success(null, '退出成功');
    }
    
    /**
     * 刷新 Token
     *
     * @return \think\Response
     */
    public function refreshToken()
    {
        $token = $this->request->header('Authorization');
        if ($token && strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        } else {
            $token = $this->request->param('token');
        }
        
        if (!$token) {
            return $this->error('Token 不能为空', 400);
        }
        
        $newToken = $this->jwtService->refreshToken($token);
        
        if (!$newToken) {
            return $this->error('Token 已过期，请重新登录', 401);
        }
        
        return $this->success([
            'token' => $newToken,
        ], 'Token 刷新成功');
    }
    
    /**
     * 获取当前用户信息
     *
     * @return \think\Response
     */
    public function userInfo()
    {
        if (!$this->currentUser) {
            return $this->error('未登录', 401);
        }
        
        $user = User::find($this->getUserId());
        
        if (!$user) {
            return $this->error('用户不存在');
        }
        
        return $this->success([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'user_level' => $user->user_level,
                'vip_expire_at' => $user->vip_expire_at,
                'storage_quota' => $user->storage_quota,
                'storage_used' => $user->storage_used,
                'storage_remaining' => $user->getStorageRemaining(),
                'ai_quota_daily' => $user->ai_quota_daily,
                'ai_used_today' => $user->ai_used_today,
            ],
        ]);
    }
    
    /**
     * 修改密码
     *
     * @return \think\Response
     */
    public function changePassword()
    {
        if (!$this->currentUser) {
            return $this->error('未登录', 401);
        }
        
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'old_password' => 'require',
            'new_password' => 'require|length:6,32',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        $user = User::find($this->getUserId());
        
        // 验证旧密码
        if (!$user->verifyPassword($param['old_password'])) {
            return $this->error('原密码错误');
        }
        
        // 更新密码
        $user->update([
            'password' => password_hash($param['new_password'], PASSWORD_DEFAULT),
        ]);
        
        return $this->success(null, '密码修改成功');
    }
}
