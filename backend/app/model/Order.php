<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 订单模型
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * 订单模型
 */
class Order extends Model
{
    protected $pk = 'id';
    protected $name = 'orders';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'plan_id' => 'integer',
        'billing_cycle' => 'integer',
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
     * 关联套餐
     */
    public function plan()
    {
        return $this->belongsTo(VipPlan::class, 'plan_id');
    }
    
    /**
     * 关联支付记录
     */
    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id');
    }
    
    /**
     * 生成订单号
     */
    public static function generateOrderNo(): string
    {
        return date('YmdHis') . substr(uniqid(), -6);
    }
}
