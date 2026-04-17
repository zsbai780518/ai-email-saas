<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 邮箱控制器
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\controller;

use app\model\Mailbox;
use app\model\Email;
use app\model\EmailFolder;
use app\model\EmailTag;
use app\model\EmailDraft;
use think\facade\Validate;

/**
 * 邮箱控制器
 * 邮箱账号管理
 */
class Mailbox extends Base
{
    /**
     * 邮箱列表
     */
    public function index()
    {
        $userId = $this->getUserId();
        
        $list = Mailbox::where('user_id', $userId)
            ->where('status', 1)
            ->order('id', 'desc')
            ->select();
        
        return $this->success(['list' => $list]);
    }
    
    /**
     * 创建邮箱账号
     */
    public function create()
    {
        $userId = $this->getUserId();
        $user = \app\model\User::find($userId);
        
        // 检查邮箱数量限制
        $plan = \app\model\VipPlan::find($user->user_level == 3 ? 3 : ($user->user_level == 2 ? 2 : 1));
        $mailboxLimit = $plan ? $plan->mailbox_limit : 1;
        $currentCount = Mailbox::where('user_id', $userId)->count();
        
        if ($currentCount >= $mailboxLimit) {
            return $this->error('已达到邮箱账号数量上限');
        }
        
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'email_prefix' => 'require|regex:/^[a-zA-Z0-9._-]{3,20}$/',
            'domain_id' => 'requireIf:user_level,2|integer',
            'display_name' => 'max:50',
            'signature' => 'max:500',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        // 检查前缀是否已存在
        $exists = Mailbox::where('email_prefix', $param['email_prefix'])
            ->where('user_id', $userId)
            ->find();
        
        if ($exists) {
            return $this->error('该邮箱前缀已被使用');
        }
        
        // 生成邮箱地址
        $emailAddress = $param['email_prefix'] . '@';
        if (!empty($param['domain_id'])) {
            $domain = \app\model\Domain::find($param['domain_id']);
            if ($domain) {
                $emailAddress .= $domain->domain_name;
            } else {
                $emailAddress .= 'ai-email-saas.com'; // 默认域名
            }
        } else {
            $emailAddress .= 'ai-email-saas.com'; // 默认域名
        }
        
        $mailbox = Mailbox::create([
            'user_id' => $userId,
            'domain_id' => $param['domain_id'] ?? null,
            'email_address' => $emailAddress,
            'email_prefix' => $param['email_prefix'],
            'display_name' => $param['display_name'] ?? $user->username,
            'signature' => $param['signature'] ?? null,
            'status' => 1,
        ]);
        
        // 创建系统文件夹
        EmailFolder::getSystemFolders($userId);
        
        return $this->success(['mailbox' => $mailbox], '邮箱账号创建成功');
    }
    
    /**
     * 更新邮箱设置
     */
    public function update()
    {
        $id = $this->request->param('id', 0, 'intval');
        $userId = $this->getUserId();
        
        $mailbox = Mailbox::where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$mailbox) {
            return $this->error('邮箱不存在');
        }
        
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'display_name' => 'max:50',
            'signature' => 'max:500',
            'auto_reply' => 'in:0,1',
            'auto_reply_content' => 'max:1000',
            'forwarding_email' => 'email',
            'forwarding_enabled' => 'in:0,1',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        $mailbox->save($param);
        
        return $this->success(['mailbox' => $mailbox], '设置更新成功');
    }
    
    /**
     * 删除邮箱账号
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $userId = $this->getUserId();
        
        $mailbox = Mailbox::where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$mailbox) {
            return $this->error('邮箱不存在');
        }
        
        // 软删除
        $mailbox->update(['status' => 0]);
        
        return $this->success(null, '邮箱账号已删除');
    }
}
