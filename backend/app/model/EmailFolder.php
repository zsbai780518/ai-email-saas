<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 邮件文件夹模型
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * 邮件文件夹模型
 */
class EmailFolder extends Model
{
    protected $pk = 'id';
    protected $name = 'email_folders';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'folder_type' => 'integer',
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'email_count' => 'integer',
        'unread_count' => 'integer',
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
     * 关联邮件
     */
    public function emails()
    {
        return $this->belongsToMany(Email::class, 'email_folder_relations', 'email_id', 'folder_id');
    }
    
    /**
     * 获取系统文件夹列表
     */
    public static function getSystemFolders(int $userId): array
    {
        $folders = [
            ['folder_name' => '收件箱', 'folder_type' => 1, 'sort_order' => 1],
            ['folder_name' => '已发送', 'folder_type' => 1, 'sort_order' => 2],
            ['folder_name' => '草稿箱', 'folder_type' => 1, 'sort_order' => 3],
            ['folder_name' => '垃圾邮件', 'folder_type' => 1, 'sort_order' => 4],
            ['folder_name' => '已删除', 'folder_type' => 1, 'sort_order' => 5],
            ['folder_name' => '星标邮件', 'folder_type' => 1, 'sort_order' => 6],
        ];
        
        foreach ($folders as &$folder) {
            $exists = self::where('user_id', $userId)
                ->where('folder_name', $folder['folder_name'])
                ->find();
            
            if (!$exists) {
                self::create(array_merge($folder, ['user_id' => $userId, 'status' => 1]));
            }
        }
        
        return self::where('user_id', $userId)
            ->where('folder_type', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
    }
}
