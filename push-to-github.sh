#!/bin/bash

# AI 智能邮箱 SaaS 系统 - GitHub 推送脚本
# 使用方法：./push-to-github.sh YOUR_GITHUB_USERNAME

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=====================================${NC}"
echo -e "${GREEN}  AI 智能邮箱 SaaS - GitHub 推送脚本${NC}"
echo -e "${GREEN}=====================================${NC}"

# 检查参数
if [ -z "$1" ]; then
    echo -e "${YELLOW}用法：./push-to-github.sh YOUR_GITHUB_USERNAME${NC}"
    echo -e "${YELLOW}例如：./push-to-github.sh zhangsan${NC}"
    exit 1
fi

USERNAME=$1
REPO_NAME="ai-email-saas"
REMOTE_URL="https://github.com/${USERNAME}/${REPO_NAME}.git"

echo -e "\n${YELLOW}📦 准备推送到 GitHub...${NC}"
echo "   用户名：${USERNAME}"
echo "   仓库名：${REPO_NAME}"
echo "   地址：${REMOTE_URL}"

# 检查 Git 是否已初始化
if [ ! -d ".git" ]; then
    echo -e "${RED}❌ 错误：.git 目录不存在，请先运行 git init${NC}"
    exit 1
fi

# 检查是否有未提交的更改
if ! git diff-index --quiet HEAD --; then
    echo -e "${YELLOW}⚠️  检测到未提交的更改，正在提交...${NC}"
    git add -A
    git commit -m "chore: 自动提交未完成的更改"
fi

# 添加或更新远程仓库
if git remote | grep -q "^origin$"; then
    echo -e "${YELLOW}⚠️  已存在 origin 远程，正在更新...${NC}"
    git remote set-url origin ${REMOTE_URL}
else
    echo -e "${GREEN}✅ 添加远程仓库...${NC}"
    git remote add origin ${REMOTE_URL}
fi

# 推送代码
echo -e "\n${YELLOW}🚀 开始推送代码到 GitHub...${NC}"
echo -e "${YELLOW}   提示：首次推送可能需要输入 GitHub 用户名和密码（或 Token）${NC}"
echo ""

if git push -u origin main; then
    echo ""
    echo -e "${GREEN}=====================================${NC}"
    echo -e "${GREEN}  ✅ 推送成功！${NC}"
    echo -e "${GREEN}=====================================${NC}"
    echo ""
    echo -e "📦 访问你的仓库："
    echo -e "   ${GREEN}https://github.com/${USERNAME}/${REPO_NAME}${NC}"
    echo ""
    echo -e "📝 下一步建议："
    echo -e "   1. 在 GitHub 仓库页面添加项目截图"
    echo -e "   2. 设置 Topics 标签（saas, email, thinkphp, uniapp）"
    echo -e "   3. 启用 GitHub Pages 部署前端演示"
    echo -e "   4. 添加 LICENSE 文件"
    echo ""
else
    echo ""
    echo -e "${RED}=====================================${NC}"
    echo -e "${RED}  ❌ 推送失败${NC}"
    echo -e "${RED}=====================================${NC}"
    echo ""
    echo -e "${YELLOW}可能的原因：${NC}"
    echo "   1. GitHub 仓库不存在 → 请先在 https://github.com/new 创建空仓库"
    echo "   2. 认证失败 → 使用 Personal Access Token 代替密码"
    echo "   3. 网络问题 → 检查网络连接"
    echo ""
    echo -e "${YELLOW}解决方案：${NC}"
    echo "   1. 创建仓库：https://github.com/new"
    echo "   2. 获取 Token: https://github.com/settings/tokens"
    echo "   3. 使用 Token 推送："
    echo "      git remote set-url origin https://YOUR_TOKEN@github.com/${USERNAME}/${REPO_NAME}.git"
    echo "      git push -u origin main"
    echo ""
    exit 1
fi
