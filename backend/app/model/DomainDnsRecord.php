<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - DNS 解析记录模型
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * DNS 解析记录模型
 */
class DomainDnsRecord extends Model
{
    protected $pk = 'id';
    protected $name = 'domain_dns_records';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'domain_id' => 'integer',
        'priority' => 'integer',
        'ttl' => 'integer',
        'status' => 'integer',
    ];
    
    /**
     * 关联域名
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }
}
