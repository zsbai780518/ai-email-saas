<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 后台管理控制器
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\User;
use app\model\Tenant;
use app\model\Domain;
use app\model\Order;
use think\facade\Validate;

/**
 * 后台管理 - 租户管理
 */
class Tenant extends BaseController
{
    /**
     * 租户列表
     */
    public function index()
    {
        $param = $this->request->param();
        $page = $param['page'] ?? 1;
        $pageSize = $param['page_size'] ?? 20;
        
        $query = Tenant::order('id', 'desc');
        
        // 搜索
        if (!empty($param['keyword'])) {
            $query->where('tenant_name|contact_name|contact_phone', 'like', '%' . $param['keyword'] . '%');
        }
        
        // 状态过滤
        if (isset($param['status']) && $param['status'] !== '') {
            $query->where('status', $param['status']);
        }
        
        $list = $query->page($page, $pageSize)->select();
        $total = $query->count();
        
        // 加载用户数量
        foreach ($list as &$tenant) {
            $tenant->user_count = User::where('tenant_id', $tenant->id)->count();
            $tenant->domain_count = Domain::where('tenant_id', $tenant->id)->count();
        }
        
        return json([
            'code' => 200,
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
            ],
        ]);
    }
    
    /**
     * 租户详情
     */
    public function detail()
    {
        $id = $this->request->param('id', 0, 'intval');
        
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return json(['code' => 404, 'message' => '租户不存在']);
        }
        
        $users = User::where('tenant_id', $id)->select();
        $domains = Domain::where('tenant_id', $id)->select();
        $orders = Order::whereHas('user', function($q) use ($id) {
            $q->where('tenant_id', $id);
        })->order('id', 'desc')->limit(10)->select();
        
        return json([
            'code' => 200,
            'data' => [
                'tenant' => $tenant,
                'users' => $users,
                'domains' => $domains,
                'recent_orders' => $orders,
            ],
        ]);
    }
    
    /**
     * 更新租户状态
     */
    public function updateStatus()
    {
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'id' => 'require|integer',
            'status' => 'require|in:0,1,2',
        ]);
        
        if (!$validate->check($param)) {
            return json(['code' => 400, 'message' => '参数错误']);
        }
        
        $tenant = Tenant::find($param['id']);
        if (!$tenant) {
            return json(['code' => 404, 'message' => '租户不存在']);
        }
        
        $tenant->update(['status' => $param['status']]);
        
        return json(['code' => 200, 'message' => '状态更新成功']);
    }
}

/**
 * 后台管理 - 域名审核
 */
class DomainAudit extends BaseController
{
    /**
     * 待审核域名列表
     */
    public function pending()
    {
        $list = Domain::where('domain_status', 0)
            ->order('id', 'desc')
            ->select();
        
        return json([
            'code' => 200,
            'data' => ['list' => $list],
        ]);
    }
    
    /**
     * 审核域名
     */
    public function audit()
    {
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'id' => 'require|integer',
            'passed' => 'require|in:0,1',
        ]);
        
        if (!$validate->check($param)) {
            return json(['code' => 400, 'message' => '参数错误']);
        }
        
        $domain = Domain::find($param['id']);
        if (!$domain) {
            return json(['code' => 404, 'message' => '域名不存在']);
        }
        
        $domain->update([
            'domain_status' => $param['passed'] ? 1 : 2,
            'verified_at' => $param['passed'] ? date('Y-m-d H:i:s') : null,
        ]);
        
        return json([
            'code' => 200, 
            'message' => $param['passed'] ? '审核通过' : '审核拒绝'
        ]);
    }
}

/**
 * 后台管理 - 数据统计
 */
class Dashboard extends BaseController
{
    /**
     * 仪表盘数据
     */
    public function index()
    {
        $today = date('Y-m-d');
        $thisWeek = date('Y-m-d', strtotime('-7 days'));
        
        return json([
            'code' => 200,
            'data' => [
                'total_tenants' => Tenant::count(),
                'total_users' => User::count(),
                'total_domains' => Domain::count(),
                'today_orders' => Order::where('created_at', '>=', $today . ' 00:00:00')->count(),
                'today_revenue' => Order::where('created_at', '>=', $today . ' 00:00:00')
                    ->where('status', 1)
                    ->sum('total_amount'),
                'week_orders' => Order::where('created_at', '>=', $thisWeek)->count(),
                'week_revenue' => Order::where('created_at', '>=', $thisWeek)
                    ->where('status', 1)
                    ->sum('total_amount'),
            ],
        ]);
    }
}
