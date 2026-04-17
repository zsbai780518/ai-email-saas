<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - 邮件控制器
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\controller;

use app\model\Email;
use app\model\EmailFolder;
use app\model\EmailTag;
use app\model\EmailDraft;
use app\model\EmailSent;
use think\facade\Validate;

/**
 * 邮件控制器
 * 邮件收发、管理
 */
class Email extends Base
{
    /**
     * 邮件列表
     */
    public function index()
    {
        $userId = $this->getUserId();
        $param = $this->request->param();
        
        $folder = $param['folder'] ?? 'inbox'; // inbox, sent, draft, spam, trash, starred
        $page = $param['page'] ?? 1;
        $pageSize = $param['page_size'] ?? 20;
        
        $query = Email::where('user_id', $userId)
            ->where('is_deleted', 0);
        
        // 按文件夹过滤
        switch ($folder) {
            case 'inbox':
                $query->where('is_spam', 0);
                break;
            case 'spam':
                $query->where('is_spam', 1);
                break;
            case 'starred':
                $query->where('is_starred', 1);
                break;
            case 'sent':
                // 已发送邮件从 email_sent 表查询
                return $this->getSentEmails($userId, $page, $pageSize);
        }
        
        $list = $query->order('received_at', 'desc')
            ->page($page, $pageSize)
            ->select();
        
        $total = $query->count();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }
    
    /**
     * 获取已发送邮件
     */
    protected function getSentEmails(int $userId, int $page, int $pageSize)
    {
        $list = EmailSent::where('user_id', $userId)
            ->order('sent_at', 'desc')
            ->page($page, $pageSize)
            ->select();
        
        $total = EmailSent::where('user_id', $userId)->count();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }
    
    /**
     * 邮件详情
     */
    public function detail()
    {
        $id = $this->request->param('id', 0, 'intval');
        $userId = $this->getUserId();
        
        $email = Email::where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$email) {
            return $this->error('邮件不存在');
        }
        
        // 标记为已读
        if (!$email->is_read) {
            $email->markAsRead();
        }
        
        return $this->success(['email' => $email]);
    }
    
    /**
     * 发送邮件
     */
    public function send()
    {
        $userId = $this->getUserId();
        $user = \app\model\User::find($userId);
        
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'mailbox_id' => 'require|integer',
            'to_emails' => 'require',
            'subject' => 'max:500',
            'body_text' => 'max:100000',
            'body_html' => 'max:500000',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        // 检查邮箱是否存在
        $mailbox = \app\model\Mailbox::where('id', $param['mailbox_id'])
            ->where('user_id', $userId)
            ->find();
        
        if (!$mailbox) {
            return $this->error('发件邮箱不存在');
        }
        
        // 检查存储配额
        $estimatedSize = strlen($param['body_text'] ?? '') + strlen($param['subject'] ?? '');
        if ($user->getStorageRemaining() < $estimatedSize) {
            return $this->error('存储空间不足');
        }
        
        // 创建邮件记录
        $email = Email::create([
            'mailbox_id' => $param['mailbox_id'],
            'user_id' => $userId,
            'email_uid' => $this->generateEmailUid(),
            'subject' => $param['subject'] ?? '',
            'from_email' => $mailbox->email_address,
            'from_name' => $mailbox->display_name ?? $user->username,
            'to_emails' => json_encode($this->parseEmails($param['to_emails'])),
            'cc_emails' => !empty($param['cc_emails']) ? json_encode($this->parseEmails($param['cc_emails'])) : null,
            'bcc_emails' => !empty($param['bcc_emails']) ? json_encode($this->parseEmails($param['bcc_emails'])) : null,
            'body_text' => $param['body_text'] ?? '',
            'body_html' => $param['body_html'] ?? '',
            'email_size' => $estimatedSize,
            'sent_at' => date('Y-m-d H:i:s'),
            'received_at' => date('Y-m-d H:i:s'),
        ]);
        
        // 记录到发件箱
        EmailSent::create([
            'email_id' => $email->id,
            'user_id' => $userId,
            'mailbox_id' => $param['mailbox_id'],
            'subject' => $param['subject'] ?? '',
            'from_email' => $mailbox->email_address,
            'to_emails' => json_encode($this->parseEmails($param['to_emails'])),
            'cc_emails' => !empty($param['cc_emails']) ? json_encode($this->parseEmails($param['cc_emails'])) : null,
            'body_text' => $param['body_text'] ?? '',
            'body_html' => $param['body_html'] ?? '',
            'send_status' => 1,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
        
        // TODO: 实际 SMTP 发送
        
        return $this->success(['email' => $email], '邮件发送成功');
    }
    
    /**
     * 标记已读
     */
    public function read()
    {
        $id = $this->request->param('id', 0, 'intval');
        $userId = $this->getUserId();
        
        $email = Email::where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$email) {
            return $this->error('邮件不存在');
        }
        
        $email->markAsRead();
        
        return $this->success(null, '已标记为已读');
    }
    
    /**
     * 删除邮件
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $userId = $this->getUserId();
        
        $email = Email::where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$email) {
            return $this->error('邮件不存在');
        }
        
        $email->softDelete();
        
        return $this->success(null, '邮件已删除');
    }
    
    /**
     * 标记垃圾邮件
     */
    public function spam()
    {
        $id = $this->request->param('id', 0, 'intval');
        $userId = $this->getUserId();
        
        $email = Email::where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$email) {
            return $this->error('邮件不存在');
        }
        
        $email->moveToSpam();
        
        return $this->success(null, '已标记为垃圾邮件');
    }
    
    /**
     * 标记星标
     */
    public function star()
    {
        $id = $this->request->param('id', 0, 'intval');
        $userId = $this->getUserId();
        
        $email = Email::where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if (!$email) {
            return $this->error('邮件不存在');
        }
        
        $email->markAsStarred();
        
        return $this->success(null, '已添加星标');
    }
    
    /**
     * 保存草稿
     */
    public function saveDraft()
    {
        $userId = $this->getUserId();
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'mailbox_id' => 'integer',
            'to_emails' => 'max:1000',
            'subject' => 'max:500',
            'body_text' => 'max:100000',
            'body_html' => 'max:500000',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        $draft = EmailDraft::create([
            'user_id' => $userId,
            'mailbox_id' => $param['mailbox_id'] ?? null,
            'subject' => $param['subject'] ?? '',
            'to_emails' => $param['to_emails'] ?? '',
            'cc_emails' => $param['cc_emails'] ?? '',
            'body_text' => $param['body_text'] ?? '',
            'body_html' => $param['body_html'] ?? '',
        ]);
        
        return $this->success(['draft' => $draft], '草稿保存成功');
    }
    
    /**
     * 获取草稿列表
     */
    public function drafts()
    {
        $userId = $this->getUserId();
        
        $list = EmailDraft::where('user_id', $userId)
            ->order('last_saved_at', 'desc')
            ->select();
        
        return $this->success(['list' => $list]);
    }
    
    /**
     * 生成邮件 UID
     */
    protected function generateEmailUid(): string
    {
        return uniqid('mail_') . '_' . time();
    }
    
    /**
     * 解析邮箱地址列表
     */
    protected function parseEmails(string $emails): array
    {
        return array_map('trim', explode(',', $emails));
    }
}
