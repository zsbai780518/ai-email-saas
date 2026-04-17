<?php
// +----------------------------------------------------------------------
// | AI 智能邮箱 SaaS 系统 - AI 功能控制器
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\controller;

use app\model\AiConfig;
use app\model\AiUsageLog;
use think\facade\Validate;

/**
 * AI 功能控制器
 * 智能撰写、智能分类、垃圾邮件识别
 */
class Ai extends Base
{
    /**
     * 智能撰写邮件
     */
    public function compose()
    {
        $userId = $this->getUserId();
        $user = \app\model\User::find($userId);
        
        // 检查 AI 配额
        if (!$user->canUseAi()) {
            return $this->error('今日 AI 配额已用完');
        }
        
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'prompt' => 'require|max:2000',
            'tone' => 'in:formal,casual,friendly,professional',
            'language' => 'in:zh,en',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        // 调用 AI 模型（这里模拟，实际对接大模型 API）
        $response = $this->callAiCompose($param);
        
        // 记录使用日志
        $this->logAiUsage($userId, 'smart_compose', $param, $response);
        
        // 消耗配额
        $user->consumeAiQuota();
        
        return $this->success([
            'content' => $response['content'],
            'subject' => $response['subject'] ?? '',
            'tokens_used' => $response['tokens_used'] ?? 0,
        ]);
    }
    
    /**
     * 智能分类邮件
     */
    public function categorize()
    {
        $userId = $this->getUserId();
        $user = \app\model\User::find($userId);
        
        if (!$user->canUseAi()) {
            return $this->error('今日 AI 配额已用完');
        }
        
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'email_id' => 'require|integer',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        $email = \app\model\Email::where('id', $param['email_id'])
            ->where('user_id', $userId)
            ->find();
        
        if (!$email) {
            return $this->error('邮件不存在');
        }
        
        // AI 分类（模拟）
        $categories = ['work', 'personal', 'promotion', 'social', 'notification'];
        $category = $categories[array_rand($categories)];
        
        // 更新邮件分类
        $email->update([
            'ai_category' => $category,
            'ai_processed' => 1,
        ]);
        
        // 记录日志
        $this->logAiUsage($userId, 'smart_categorize', ['email_id' => $param['email_id']], [
            'category' => $category,
        ]);
        
        $user->consumeAiQuota();
        
        return $this->success([
            'category' => $category,
            'confidence' => 0.85 + rand(0, 14) / 100,
        ]);
    }
    
    /**
     * 垃圾邮件识别
     */
    public function detectSpam()
    {
        $userId = $this->getUserId();
        $user = \app\model\User::find($userId);
        
        if (!$user->canUseAi()) {
            return $this->error('今日 AI 配额已用完');
        }
        
        $param = $this->request->param();
        
        $validate = Validate::rule([
            'email_id' => 'require|integer',
        ]);
        
        if (!$validate->check($param)) {
            return $this->error($validate->getError());
        }
        
        $email = \app\model\Email::where('id', $param['email_id'])
            ->where('user_id', $userId)
            ->find();
        
        if (!$email) {
            return $this->error('邮件不存在');
        }
        
        // AI 垃圾邮件识别（模拟）
        $spamScore = rand(0, 100) / 100;
        $isSpam = $spamScore > 0.7;
        
        if ($isSpam) {
            $email->update([
                'is_spam' => 1,
                'spam_score' => $spamScore,
                'ai_processed' => 1,
            ]);
        }
        
        // 记录日志
        $this->logAiUsage($userId, 'spam_filter', ['email_id' => $param['email_id']], [
            'spam_score' => $spamScore,
            'is_spam' => $isSpam,
        ]);
        
        $user->consumeAiQuota();
        
        return $this->success([
            'is_spam' => $isSpam,
            'spam_score' => $spamScore,
            'threshold' => 0.7,
        ]);
    }
    
    /**
     * 获取 AI 配额
     */
    public function quota()
    {
        $userId = $this->getUserId();
        $user = \app\model\User::find($userId);
        
        // 检查是否需要重置配额
        $user->checkQuotaReset();
        
        return $this->success([
            'daily_quota' => $user->ai_quota_daily,
            'used_today' => $user->ai_used_today,
            'remaining' => $user->ai_quota_daily - $user->ai_used_today,
            'reset_at' => date('Y-m-d', strtotime('+1 day')) . ' 00:00:00',
        ]);
    }
    
    /**
     * 获取 AI 配置
     */
    public function getConfig()
    {
        $userId = $this->getUserId();
        
        $config = AiConfig::where('user_id', $userId)->find();
        
        if (!$config) {
            // 创建默认配置
            $config = AiConfig::create([
                'user_id' => $userId,
                'spam_filter_enabled' => 1,
                'spam_filter_level' => 2,
                'smart_compose_enabled' => 1,
                'smart_categorize_enabled' => 1,
                'auto_categorize_enabled' => 1,
            ]);
        }
        
        return $this->success(['config' => $config]);
    }
    
    /**
     * 更新 AI 配置
     */
    public function updateConfig()
    {
        $userId = $this->getUserId();
        $param = $this->request->param();
        
        $config = AiConfig::where('user_id', $userId)->find();
        
        if (!$config) {
            $config = AiConfig::create(array_merge($param, ['user_id' => $userId]));
        } else {
            $config->save($param);
        }
        
        return $this->success(['config' => $config]);
    }
    
    /**
     * 调用 AI 撰写（模拟）
     */
    protected function callAiCompose(array $param): array
    {
        // 实际应该调用大模型 API（如通义千问、文心一言等）
        // 这里返回模拟响应
        
        $templates = [
            'formal' => '尊敬的先生/女士：\n\n关于您提及的事宜，我方已收到并高度重视。经内部讨论，现回复如下...\n\n此致\n敬礼',
            'casual' => '嗨！\n\n看到你的邮件了，我觉得...\n\n有空再聊！',
            'friendly' => '你好呀！\n\n非常感谢你的来信，关于你说的事情...\n\n期待你的回复！',
            'professional' => '尊敬的客户：\n\n感谢您选择我们的服务。针对您提出的问题，我们提供专业的解决方案...\n\n如有任何疑问，请随时联系。',
        ];
        
        $tone = $param['tone'] ?? 'professional';
        $content = $templates[$tone] ?? $templates['professional'];
        
        return [
            'content' => $content,
            'subject' => '回复：' . (explode(':', $param['prompt'] ?? '')[0] ?? '您的邮件'),
            'tokens_used' => rand(50, 200),
        ];
    }
    
    /**
     * 记录 AI 使用日志
     */
    protected function logAiUsage(int $userId, string $function, array $request, array $response): void
    {
        AiUsageLog::create([
            'user_id' => $userId,
            'ai_function' => $function,
            'request_data' => json_encode($request),
            'response_data' => json_encode($response),
            'tokens_used' => $response['tokens_used'] ?? 0,
            'processing_time_ms' => rand(100, 1000),
            'status' => 1,
        ]);
    }
}
