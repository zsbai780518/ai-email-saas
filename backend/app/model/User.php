<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 用户模型
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户模型
 */
class User extends Model
{
    /**
     * 数据表主键
     */
    protected $pk = 'id';
    
    /**
     * 模型名称
     */
    protected $name = 'users';
    
    /**
     * 自动写入时间戳
     */
    protected $autoWriteTimestamp = 'datetime';
    
    /**
     * 创建时间字段
     */
    protected $createTime = 'created_at';
    
    /**
     * 更新时间字段
     */
    protected $updateTime = 'updated_at';
    
    /**
     * 字段类型转换
     */
    protected $type = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'user_level' => 'integer',
        'storage_quota' => 'integer',
        'storage_used' => 'integer',
        'ai_quota_daily' => 'integer',
        'ai_used_today' => 'integer',
        'status' => 'integer',
    ];
    
    /**
     * 隐藏字段
     */
    protected $hidden = ['password'];
    
    /**
     * 关联租户
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
    
    /**
     * 关联域名
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }
    
    /**
     * 关联邮箱
     */
    public function mailboxes()
    {
        return $this->hasMany(Mailbox::class, 'user_id');
    }
    
    /**
     * 关联订阅
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }
    
    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
    
    /**
     * 验证密码
     *
     * @param string $password 明文密码
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
    
    /**
     * 检查是否为 VIP 用户
     *
     * @return bool
     */
    public function isVip(): bool
    {
        if ($this->user_level < 2) {
            return false;
        }
        
        // 检查 VIP 是否过期
        if ($this->vip_expire_at && strtotime($this->vip_expire_at) < time()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 检查是否为企业 VIP
     *
     * @return bool
     */
    public function isEnterpriseVip(): bool
    {
        return $this->user_level == 3 && $this->isVip();
    }
    
    /**
     * 获取剩余存储空间
     *
     * @return int
     */
    public function getStorageRemaining(): int
    {
        return max(0, $this->storage_quota - $this->storage_used);
    }
    
    /**
     * 检查 AI 配额
     *
     * @return bool
     */
    public function canUseAi(): bool
    {
        // 检查是否需要重置配额
        $this->checkQuotaReset();
        
        return $this->ai_used_today < $this->ai_quota_daily;
    }
    
    /**
     * 使用 AI 配额
     *
     * @return bool
     */
    public function consumeAiQuota(): bool
    {
        if (!$this->canUseAi()) {
            return false;
        }
        
        $this->ai_used_today += 1;
        $this->save();
        
        return true;
    }
    
    /**
     * 检查并重置 AI 配额
     */
    protected function checkQuotaReset(): void
    {
        $today = date('Y-m-d');
        
        if ($this->ai_quota_reset_at != $today) {
            $this->ai_used_today = 0;
            $this->ai_quota_reset_at = $today;
            $this->save();
        }
    }
    
    /**
     * 获取用户邮箱地址（带域名）
     *
     * @return string
     */
    public function getFullEmailAttr($value, $data): string
    {
        if (!empty($data['email'])) {
            return $data['email'];
        }
        
        // 如果有自定义域名
        if (!empty($data['email_prefix']) && !empty($data['domain_id'])) {
            $domain = Domain::find($data['domain_id']);
            if ($domain) {
                return $data['email_prefix'] . '@' . $domain->domain_name;
            }
        }
        
        return '';
    }
}
