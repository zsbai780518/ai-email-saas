<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - JWT 认证中间件
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\middleware;

use think\facade\Session;
use think\Response;
use think\Request;
use think\facade\Log;

/**
 * JWT 认证中间件
 * 验证用户登录状态，解析 JWT Token
 */
class AuthMiddleware
{
    /**
     * 不需要登录验证的路由
     */
    protected $exceptPaths = [
        'api/login',
        'api/register',
        'api/public',
    ];
    
    /**
     * 处理请求
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        // 检查是否在排除列表中
        if ($this->isExceptPath($request->pathinfo())) {
            return $next($request);
        }
        
        // 获取 Token
        $token = $this->getToken($request);
        
        if (!$token) {
            return $this->errorResponse('未登录或 Token 已过期', 401);
        }
        
        // 验证 Token
        $user = $this->verifyToken($token);
        
        if (!$user) {
            return $this->errorResponse('Token 无效或已过期', 401);
        }
        
        // 检查用户状态
        if ($user['status'] != 1) {
            return $this->errorResponse('账号已被禁用', 403);
        }
        
        // 设置用户信息到 Session 和配置
        Session::set('user', $user);
        Session::set('token', $token);
        config('user.current', $user);
        
        // 更新最后登录时间
        $this->updateLastLogin($user['id']);
        
        return $next($request);
    }
    
    /**
     * 检查是否在排除路径中
     */
    protected function isExceptPath(string $path): bool
    {
        foreach ($this->exceptPaths as $except) {
            if (strpos($path, $except) === 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 获取 Token
     */
    protected function getToken(Request $request): ?string
    {
        // 从 Header 获取
        $token = $request->header('Authorization');
        if ($token && strpos($token, 'Bearer ') === 0) {
            return substr($token, 7);
        }
        
        // 从参数获取
        $token = $request->param('token');
        if ($token) {
            return $token;
        }
        
        return null;
    }
    
    /**
     * 验证 Token
     */
    protected function verifyToken(string $token): ?array
    {
        // 使用 Firebase JWT 或简单实现
        // 这里使用简单的 Base64 编码示例，生产环境请使用 Firebase JWT
        
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return null;
        }
        
        // 解码 payload
        $payload = json_decode(base64_decode($parts[1]), true);
        if (!$payload) {
            return null;
        }
        
        // 检查过期时间
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        
        // 验证签名（简单示例，生产环境需要验证 HMAC）
        $signature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], config('app.jwt_secret', 'your-secret-key'), true);
        $signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if ($signature !== $parts[2]) {
            return null;
        }
        
        // 从数据库验证用户
        $user = \app\model\User::find($payload['user_id']);
        if (!$user) {
            return null;
        }
        
        return $user->toArray();
    }
    
    /**
     * 错误响应
     */
    protected function errorResponse(string $message, int $code = 401): Response
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => null,
        ])->code($code);
    }
    
    /**
     * 更新最后登录时间
     */
    protected function updateLastLogin(int $userId): void
    {
        \app\model\User::where('id', $userId)->update([
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => request()->ip(),
        ]);
    }
}
