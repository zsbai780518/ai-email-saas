<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 邮件标签模型
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * 邮件标签模型
 */
class EmailTag extends Model
{
    protected $pk = 'id';
    protected $name = 'email_tags';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'sort_order' => 'integer',
    ];
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * 关联邮件
     */
    public function emails()
    {
        return $this->belongsToMany(Email::class, 'email_tag_relations', 'email_id', 'tag_id');
    }
}
