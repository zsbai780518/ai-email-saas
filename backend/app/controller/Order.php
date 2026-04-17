<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 订单控制器
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\controller;

use app\model\Order;
use app\model\Payment;
use app\model\VipPlan;
use app\model\Subscription;
use think\facade\Validate;

/**
 * 订单控制器
 * VIP 套餐、订单、支付
 */
class Order extends Base
{
    /**
     * 套餐列表
     */
    public function plans()
    {
        $plans = VipPlan::getAvailablePlans();
        
        return $this->success(['plans' => $plans]);
    }
    
    /**
     * 订单列表
     */
    public function index()
    {
        $userId = $this->getUserId();
        
        $list = Order::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->select();
        
        return $this->success(['list' => $list]);
    }
    
    /**
     * 订单详情
     */
    public function detail()
    {
        $id = $this->request->param('id', 0, 'intval');
        $userId = $this->getUserId();
        
        $order = Order::where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$order) {
            return $this->error('订单不存在');
        }
        
        $plan = VipPlan::find($order->plan_id);
        $payment = Payment::where('order_id', $id)->find();
        
        return $this->success([
            'order' => $order,
            'plan' => $plan,
            'payment' => $payment,
        ]);
    }
    
    /**
     * 创建订单
     */
    public function create()
    {
        $userId = $this->getUserId();
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'plan_id' => 'require|integer',
            'billing_cycle' => 'require|in:1,2', // 1=月度，2=年度
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        $plan = VipPlan::find($param['plan_id']);
        if (!$plan || $plan->status != 1) {
            return $this->error('套餐不存在或已下架');
        }
        
        // 计算价格
        $price = $param['billing_cycle'] == 1 ? $plan->price_monthly : $plan->price_yearly;
        
        // 创建订单
        $order = Order::create([
            'order_no' => Order::generateOrderNo(),
            'user_id' => $userId,
            'plan_id' => $plan->id,
            'billing_cycle' => $param['billing_cycle'],
            'original_price' => $price,
            'discount_amount' => 0,
            'total_amount' => $price,
            'status' => 0, // 待支付
        ]);
        
        return $this->success([
            'order' => $order,
            'plan' => $plan,
        ], '订单创建成功');
    }
    
    /**
     * 支付订单
     */
    public function pay()
    {
        $userId = $this->getUserId();
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'order_id' => 'require|integer',
            'payment_method' => 'require|in:wechat,alipay',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        $order = Order::where('id', $param['order_id'])
            ->where('user_id', $userId)
            ->find();
        
        if (!$order) {
            return $this->error('订单不存在');
        }
        
        if ($order->status != 0) {
            return $this->error('订单状态异常');
        }
        
        // 创建支付记录
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_no' => Payment::generatePaymentNo(),
            'payment_method' => $param['payment_method'],
            'amount' => $order->total_amount,
            'status' => 0, // 待支付
            'pay_params' => json_encode($this->getPayParams($order, $param['payment_method'])),
        ]);
        
        // TODO: 实际对接支付接口
        
        return $this->success([
            'order' => $order,
            'payment' => $payment,
            'pay_url' => $this->getPayUrl($order, $param['payment_method']),
        ], '请完成支付');
    }
    
    /**
     * 支付回调（模拟）
     */
    public function callback()
    {
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'order_id' => 'require|integer',
            'transaction_id' => 'require',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error('参数错误');
        }
        
        $order = Order::find($param['order_id']);
        if (!$order) {
            return $this->error('订单不存在');
        }
        
        // 更新订单状态
        $order->update([
            'status' => 1, // 已支付
            'payment_method' => $param['payment_method'] ?? 'wechat',
            'payment_at' => date('Y-m-d H:i:s'),
            'transaction_id' => $param['transaction_id'],
        ]);
        
        // 更新支付记录
        Payment::where('order_id', $order->id)->update([
            'status' => 1,
            'paid_at' => date('Y-m-d H:i:s'),
            'callback_data' => json_encode($param),
        ]);
        
        // 激活 VIP 权益
        $this->activateVip($order->user_id, $order->plan_id, $order->billing_cycle);
        
        return $this->success(null, '支付成功');
    }
    
    /**
     * 激活 VIP 权益
     */
    protected function activateVip(int $userId, int $planId, int $billingCycle)
    {
        $user = \app\model\User::find($userId);
        $plan = VipPlan::find($planId);
        
        if (!$user || !$plan) {
            return;
        }
        
        // 计算 VIP 有效期
        $days = $billingCycle == 1 ? 30 : 365;
        $expireAt = date('Y-m-d H:i:s', strtotime('+' . $days . ' days'));
        
        // 更新用户等级和权益
        $userLevel = $plan->plan_code == 'enterprise' ? 3 : 2;
        
        $user->update([
            'user_level' => $userLevel,
            'vip_expire_at' => $expireAt,
            'storage_quota' => $plan->storage_quota,
            'ai_quota_daily' => $plan->ai_quota_daily,
        ]);
        
        // 创建订阅记录
        Subscription::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'status' => 1,
            'start_at' => date('Y-m-d H:i:s'),
            'end_at' => $expireAt,
            'auto_renew' => 0,
        ]);
    }
    
    /**
     * 获取支付参数（模拟）
     */
    protected function getPayParams(Order $order, string $method): array
    {
        return [
            'order_no' => $order->order_no,
            'amount' => $order->total_amount,
            'subject' => 'AI 智能邮箱 VIP 套餐',
        ];
    }
    
    /**
     * 获取支付 URL（模拟）
     */
    protected function getPayUrl(Order $order, string $method): string
    {
        // 实际应该返回真实的支付链接
        return 'https://pay.example.com/' . $method . '/' . $order->order_no;
    }
}

// 支付记录号生成
class Payment {
    public static function generatePaymentNo(): string
    {
        return 'PAY' . date('YmdHis') . substr(uniqid(), -6);
    }
}
