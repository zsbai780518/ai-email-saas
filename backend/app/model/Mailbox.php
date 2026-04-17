<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 邮箱账号模型
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * 邮箱账号模型
 */
class Mailbox extends Model
{
    protected $pk = 'id';
    protected $name = 'mailboxes';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'domain_id' => 'integer',
        'auto_reply' => 'integer',
        'forwarding_enabled' => 'integer',
        'status' => 'integer',
    ];
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * 关联域名
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }
    
    /**
     * 关联邮件
     */
    public function emails()
    {
        return $this->hasMany(Email::class, 'mailbox_id');
    }
}
