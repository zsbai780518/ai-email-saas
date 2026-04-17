# GitHub 部署指南

## 方式一：使用 Git 命令推送（推荐）

### 1. 在 GitHub 创建新仓库

访问 https://github.com/new

- **Repository name**: `ai-email-saas`
- **Description**: AI 智能邮箱 SaaS 系统 - 多租户邮箱服务，支持自定义域名、AI 智能功能
- **Visibility**: Public（公开）或 Private（私有）
- **不要** 初始化 README/.gitignore（我们已经有）

创建后获取仓库地址，例如：`https://github.com/yourusername/ai-email-saas.git`

### 2. 推送代码到 GitHub

```bash
cd /home/admin/openclaw/workspace/ai-email-saas

# 添加远程仓库（替换为你的 GitHub 用户名）
git remote add origin https://github.com/YOUR_USERNAME/ai-email-saas.git

# 推送代码
git push -u origin main
```

### 3. 验证推送

访问 `https://github.com/YOUR_USERNAME/ai-email-saas` 查看代码

---

## 方式二：使用 GitHub Desktop

1. 下载 GitHub Desktop: https://desktop.github.com/

2. 添加现有仓库：
   - File → Add Local Repository
   - 选择 `/home/admin/openclaw/workspace/ai-email-saas`

3. 发布到 GitHub：
   - File → Publish Repository
   - 填写名称和描述
   - 选择 Public 或 Private
   - 点击 Publish

---

## 方式三：手动上传（不推荐，仅用于小文件）

1. 在 GitHub 创建空仓库

2. 点击 "uploading an existing file"

3. 拖拽文件上传

4. 提交更改

---

## 推送后的 GitHub 页面优化

### 1. 添加主题标签（Topics）

在仓库页面右侧设置：
- `saas`
- `email`
- `thinkphp`
- `uniapp`
- `multi-tenant`
- `ai`
- `php`
- `vue`

### 2. 固定重要文件

在 README 顶部添加目录导航：

```markdown
## 📑 目录

- [快速开始](#快速开始)
- [功能特性](#功能特性)
- [技术栈](#技术栈)
- [数据库设计](#数据库设计)
- [API 文档](#api-文档)
- [部署指南](#部署指南)
- [License](#license)
```

### 3. 添加 GitHub Actions（可选）

创建 `.github/workflows/ci.yml` 实现自动测试：

```yaml
name: CI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php vendor/bin/phpunit
```

---

## GitHub Pages 部署前端（可选）

如果要部署前端演示：

### 1. 构建前端

```bash
cd frontend
npm install
npm run build:h5
```

### 2. 创建 gh-pages 分支

```bash
cd frontend
npx gh-pages -d dist
```

### 3. 启用 GitHub Pages

- Settings → Pages
- Source: gh-pages branch
- 保存后访问：`https://YOUR_USERNAME.github.io/ai-email-saas`

---

## 常见问题

### Q1: 推送失败 "remote: Repository not found"
**A**: 检查仓库地址是否正确，确认有写入权限

### Q2: 推送失败 "Permission denied"
**A**: 配置 SSH Key 或使用 HTTPS + Token

```bash
# 使用 HTTPS（推荐）
git remote set-url origin https://YOUR_TOKEN@github.com/YOUR_USERNAME/ai-email-saas.git
```

### Q3: 仓库太大无法推送
**A**: 检查是否有大文件，使用 `.gitignore` 排除

```bash
# 查看大文件
git ls-files -s | sort -rn | head -20
```

---

## 仓库安全建议

1. **保护主分支**
   - Settings → Branches → Add branch protection rule
   - Branch name: `main`
   - 勾选 "Require pull request reviews"

2. **启用 Dependabot**
   - Settings → Code security and analysis → Enable Dependabot

3. **添加 License**
   - 创建 `LICENSE` 文件
   - 选择适合的开源协议（MIT/Apache 2.0/GPL）

4. **敏感信息**
   - 永远不要提交 `.env` 文件
   - 使用 GitHub Secrets 管理密钥

---

## 下一步

推送成功后：

1. ✅ 在 GitHub 查看代码
2. ✅ 添加项目演示截图到 README
3. ✅ 邀请团队成员（Settings → Collaborators）
4. ✅ 设置自动备份
5. ✅ 考虑开源协议选择

---

## 快捷命令汇总

```bash
# 查看远程仓库
git remote -v

# 修改远程仓库地址
git remote set-url origin https://github.com/USERNAME/ai-email-saas.git

# 推送代码
git push -u origin main

# 查看提交历史
git log --oneline

# 拉取最新代码
git pull origin main
```

---

**祝部署顺利！** 🚀

如有问题，请查看：
- GitHub Docs: https://docs.github.com/
- Git 官方文档：https://git-scm.com/doc
