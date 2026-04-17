<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 域名模型
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * 域名模型
 */
class Domain extends Model
{
    protected $pk = 'id';
    protected $name = 'domains';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'tenant_id' => 'integer',
        'domain_status' => 'integer',
        'icp_verified' => 'integer',
        'dns_verified' => 'integer',
        'is_primary' => 'integer',
    ];
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * 关联 DNS 记录
     */
    public function dnsRecords()
    {
        return $this->hasMany(DomainDnsRecord::class, 'domain_id');
    }
    
    /**
     * 检查 DNS 解析是否生效
     *
     * @return bool
     */
    public function checkDnsStatus(): bool
    {
        // TODO: 实际对接 DNS API 检查
        // 这里返回当前数据库状态
        return $this->dns_verified == 1;
    }
    
    /**
     * 获取推荐的 DNS 解析记录
     *
     * @return array
     */
    public function getRecommendedDnsRecords(): array
    {
        $domainName = $this->domain_name;
        
        return [
            [
                'type' => 'MX',
                'host' => '@',
                'value' => 'mx.ai-email-saas.com',
                'priority' => 10,
                'ttl' => 600,
            ],
            [
                'type' => 'MX',
                'host' => '@',
                'value' => 'mx2.ai-email-saas.com',
                'priority' => 20,
                'ttl' => 600,
            ],
            [
                'type' => 'A',
                'host' => 'mail',
                'value' => '47.100.xxx.xxx', // 替换为实际服务器 IP
                'ttl' => 600,
            ],
            [
                'type' => 'TXT',
                'host' => '@',
                'value' => 'v=spf1 include:spf.ai-email-saas.com ~all',
                'ttl' => 600,
            ],
            [
                'type' => 'TXT',
                'host' => '_dmarc',
                'value' => 'v=DMARC1; p=quarantine; rua=mailto:dmarc@' . $domainName,
                'ttl' => 600,
            ],
        ];
    }
}
