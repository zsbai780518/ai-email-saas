<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 域名管理控制器
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\controller;

use app\model\Domain;
use app\model\DomainDnsRecord;
use app\model\User;
use think\facade\Validate;

/**
 * 域名管理控制器
 * VIP 用户自定义域名功能
 */
class Domain extends Base
{
    /**
     * 域名列表
     *
     * @return \think\Response
     */
    public function index()
    {
        // VIP 权限检查
        $vipCheck = $this->requireVip();
        if ($vipCheck !== true) {
            return $vipCheck;
        }
        
        $userId = $this->getUserId();
        
        $list = Domain::where('user_id', $userId)
            ->order('id', 'desc')
            ->select();
        
        return $this->success([
            'list' => $list,
        ]);
    }
    
    /**
     * 域名详情
     *
     * @return \think\Response
     */
    public function detail()
    {
        $id = $this->request->param('id', 0, 'intval');
        
        if (!$id) {
            return $this->error('参数错误');
        }
        
        $domain = Domain::where('user_id', $this->getUserId())
            ->where('id', $id)
            ->find();
        
        if (!$domain) {
            return $this->error('域名不存在');
        }
        
        // 获取 DNS 记录
        $dnsRecords = DomainDnsRecord::where('domain_id', $id)->select();
        
        return $this->success([
            'domain' => $domain,
            'dns_records' => $dnsRecords,
        ]);
    }
    
    /**
     * 绑定域名
     *
     * @return \think\Response
     */
    public function bind()
    {
        // VIP 权限检查
        $vipCheck = $this->requireVip();
        if ($vipCheck !== true) {
            return $vipCheck;
        }
        
        $param = $this->request->param();
        
        // 验证参数
        $validate = Validate::rule([
            'domain_name' => 'require|regex:/^[a-zA-Z0-9][-a-zA-Z0-9]{0,62}(\.[a-zA-Z0-9][-a-zA-Z0-9]{0,62})*\.[a-z]{2,}$/',
            'icp_filing' => 'regex:/^[a-zA-Z0-9 京 ICP 备字 () 号 -]+$/',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        $userId = $this->getUserId();
        $user = User::find($userId);
        
        // 检查域名是否已被绑定
        if (Domain::where('domain_name', $param['domain_name'])->find()) {
            return $this->error('该域名已被绑定');
        }
        
        // 检查用户域名数量限制
        $domainLimit = $user->isEnterpriseVip() ? 5 : 1;
        $currentDomainCount = Domain::where('user_id', $userId)->count();
        
        if ($currentDomainCount >= $domainLimit) {
            return $this->error('已达到域名绑定数量上限');
        }
        
        // 创建域名记录
        $domain = Domain::create([
            'user_id' => $userId,
            'tenant_id' => $user->tenant_id,
            'domain_name' => $param['domain_name'],
            'icp_filing' => $param['icp_filing'] ?? null,
            'icp_verified' => empty($param['icp_filing']) ? 1 : 0, // 无备案号则跳过备案验证
            'domain_status' => 0, // 待验证
        ]);
        
        // 创建推荐的 DNS 记录
        $this->createDnsRecords($domain->id, $param['domain_name']);
        
        return $this->success([
            'domain' => $domain,
        ], '域名绑定成功，请配置 DNS 解析记录');
    }
    
    /**
     * 创建 DNS 记录
     */
    protected function createDnsRecords(int $domainId, string $domainName): void
    {
        $records = [
            [
                'record_type' => 'MX',
                'host' => '@',
                'value' => 'mx.ai-email-saas.com',
                'priority' => 10,
                'ttl' => 600,
            ],
            [
                'record_type' => 'MX',
                'host' => '@',
                'value' => 'mx2.ai-email-saas.com',
                'priority' => 20,
                'ttl' => 600,
            ],
            [
                'record_type' => 'A',
                'host' => 'mail',
                'value' => '47.100.xxx.xxx', // 实际部署时替换为服务器 IP
                'ttl' => 600,
            ],
            [
                'record_type' => 'TXT',
                'host' => '@',
                'value' => 'v=spf1 include:spf.ai-email-saas.com ~all',
                'ttl' => 600,
            ],
            [
                'record_type' => 'TXT',
                'host' => '_dmarc',
                'value' => 'v=DMARC1; p=quarantine; rua=mailto:dmarc@' . $domainName,
                'ttl' => 600,
            ],
        ];
        
        foreach ($records as $record) {
            DomainDnsRecord::create([
                'domain_id' => $domainId,
                'record_type' => $record['record_type'],
                'host' => $record['host'],
                'value' => $record['value'],
                'priority' => $record['priority'] ?? null,
                'ttl' => $record['ttl'],
                'status' => 0, // 待配置
            ]);
        }
    }
    
    /**
     * 验证 DNS 解析
     *
     * @return \think\Response
     */
    public function verifyDns()
    {
        $id = $this->request->param('id', 0, 'intval');
        
        if (!$id) {
            return $this->error('参数错误');
        }
        
        $domain = Domain::where('user_id', $this->getUserId())
            ->where('id', $id)
            ->find();
        
        if (!$domain) {
            return $this->error('域名不存在');
        }
        
        // 实际应该调用 DNS API 验证
        // 这里模拟验证过程
        $dnsRecords = DomainDnsRecord::where('domain_id', $id)->select();
        
        $allVerified = true;
        foreach ($dnsRecords as $record) {
            // TODO: 实际 DNS 查询验证
            // 这里模拟验证通过
            $record->update([
                'status' => 2, // 验证通过
                'verified_at' => date('Y-m-d H:i:s'),
                'last_check_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        // 更新域名状态
        if ($allVerified) {
            $domain->update([
                'dns_verified' => 1,
                'domain_status' => 1, // 验证通过
                'verified_at' => date('Y-m-d H:i:s'),
            ]);
            
            return $this->success([
                'domain' => $domain,
            ], 'DNS 验证通过，域名绑定成功');
        }
        
        return $this->error('DNS 验证未通过，请检查解析配置');
    }
    
    /**
     * 获取推荐 DNS 记录
     *
     * @return \think\Response
     */
    public function getDnsRecords()
    {
        $id = $this->request->param('id', 0, 'intval');
        
        if (!$id) {
            return $this->error('参数错误');
        }
        
        $domain = Domain::where('user_id', $this->getUserId())
            ->where('id', $id)
            ->find();
        
        if (!$domain) {
            return $this->error('域名不存在');
        }
        
        $dnsRecords = DomainDnsRecord::where('domain_id', $id)->select();
        
        return $this->success([
            'dns_records' => $dnsRecords,
        ]);
    }
    
    /**
     * 解绑域名
     *
     * @return \think\Response
     */
    public function unbind()
    {
        $id = $this->request->param('id', 0, 'intval');
        
        if (!$id) {
            return $this->error('参数错误');
        }
        
        $domain = Domain::where('user_id', $this->getUserId())
            ->where('id', $id)
            ->find();
        
        if (!$domain) {
            return $this->error('域名不存在');
        }
        
        // 删除域名和 DNS 记录
        DomainDnsRecord::where('domain_id', $id)->delete();
        $domain->delete();
        
        return $this->success(null, '域名解绑成功');
    }
}
