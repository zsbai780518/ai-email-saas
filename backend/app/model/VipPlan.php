<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - VIP 套餐模型
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * VIP 套餐模型
 */
class VipPlan extends Model
{
    protected $pk = 'id';
    protected $name = 'vip_plans';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'price_monthly' => 'decimal',
        'price_yearly' => 'decimal',
        'storage_quota' => 'integer',
        'domain_limit' => 'integer',
        'mailbox_limit' => 'integer',
        'ai_quota_daily' => 'integer',
        'sub_account_limit' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];
    
    /**
     * 获取所有可用套餐
     */
    public static function getAvailablePlans(): array
    {
        return self::where('status', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
    }
}
