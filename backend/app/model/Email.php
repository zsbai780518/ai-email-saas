<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 邮件模型
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * 邮件模型
 */
class Email extends Model
{
    protected $pk = 'id';
    protected $name = 'emails';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'mailbox_id' => 'integer',
        'user_id' => 'integer',
        'is_read' => 'integer',
        'is_starred' => 'integer',
        'is_deleted' => 'integer',
        'is_spam' => 'integer',
        'ai_processed' => 'integer',
        'attachment_count' => 'integer',
        'attachment_size' => 'integer',
        'email_size' => 'integer',
    ];
    
    /**
     * 关联邮箱
     */
    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class, 'mailbox_id');
    }
    
    /**
     * 关联文件夹
     */
    public function folders()
    {
        return $this->belongsToMany(EmailFolder::class, 'email_folder_relations', 'folder_id', 'email_id');
    }
    
    /**
     * 关联标签
     */
    public function tags()
    {
        return $this->belongsToMany(EmailTag::class, 'email_tag_relations', 'tag_id', 'email_id');
    }
    
    /**
     * 标记为已读
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => 1]);
    }
    
    /**
     * 标记为星标
     */
    public function markAsStarred(): bool
    {
        return $this->update(['is_starred' => 1]);
    }
    
    /**
     * 移动到垃圾箱
     */
    public function moveToSpam(): bool
    {
        return $this->update(['is_spam' => 1, 'is_deleted' => 0]);
    }
    
    /**
     * 软删除
     */
    public function softDelete(): bool
    {
        return $this->update(['is_deleted' => 1]);
    }
}
