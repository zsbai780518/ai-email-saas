<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - JWT 认证服务
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\service;

/**
 * JWT 认证服务
 * 负责 Token 的生成、验证、刷新
 */
class JwtService
{
    /**
     * JWT 密钥
     */
    protected string $secret;
    
    /**
     * Token 有效期（秒）
     */
    protected int $expire;
    
    /**
     * 刷新 Token 有效期（秒）
     */
    protected int $refreshExpire;
    
    public function __construct()
    {
        $this->secret = config('app.jwt_secret', 'ai-email-saas-secret-key-2026');
        $this->expire = config('app.jwt_expire', 7200); // 默认 2 小时
        $this->refreshExpire = config('app.jwt_refresh_expire', 604800); // 默认 7 天
    }
    
    /**
     * 生成 Token
     *
     * @param array $payload 负载数据
     * @return string
     */
    public function generateToken(array $payload): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->expire;
        $payload['refresh_exp'] = time() + $this->refreshExpire;
        
        $base64Header = $this->base64UrlEncode(json_encode($header));
        $base64Payload = $this->base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $this->secret, true);
        $base64Signature = $this->base64UrlEncode($signature);
        
        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }
    
    /**
     * 验证 Token
     *
     * @param string $token
     * @return array|false 验证成功返回 payload，失败返回 false
     */
    public function verifyToken(string $token)
    {
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return false;
        }
        
        // 验证签名
        $signature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $this->secret, true);
        $base64Signature = $this->base64UrlEncode($signature);
        
        if ($base64Signature !== $parts[2]) {
            return false;
        }
        
        // 解码 payload
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (!$payload) {
            return false;
        }
        
        // 检查过期时间
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * 刷新 Token
     *
     * @param string $token
     * @return string|false 成功返回新 Token，失败返回 false
     */
    public function refreshToken(string $token)
    {
        $payload = $this->verifyToken($token);
        if (!$payload) {
            return false;
        }
        
        // 检查是否在刷新有效期内
        if (isset($payload['refresh_exp']) && $payload['refresh_exp'] < time()) {
            return false;
        }
        
        // 生成新 Token
        unset($payload['iat'], $payload['exp'], $payload['refresh_exp']);
        return $this->generateToken($payload);
    }
    
    /**
     * Base64 URL 编码
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL 解码
     */
    protected function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
