<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 租户模型
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * 租户模型
 */
class Tenant extends Model
{
    protected $pk = 'id';
    protected $name = 'tenants';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'tenant_type' => 'integer',
        'status' => 'integer',
    ];
    
    /**
     * 关联用户
     */
    public function users()
    {
        return $this->hasMany(User::class, 'tenant_id');
    }
    
    /**
     * 关联域名
     */
    public function domains()
    {
        return $this->hasMany(Domain::class, 'tenant_id');
    }
}
