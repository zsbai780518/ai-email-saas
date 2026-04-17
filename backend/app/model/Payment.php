<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 支付记录模型
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\model;

use think\Model;

/**
 * 支付记录模型
 */
class Payment extends Model
{
    protected $pk = 'id';
    protected $name = 'payments';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'order_id' => 'integer',
        'status' => 'integer',
        'amount' => 'decimal',
    ];
    
    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
