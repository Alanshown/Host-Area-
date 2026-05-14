# HostArea - AI驱动的社区平台

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Vue.js-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white" alt="Vue.js">
  <img src="https://img.shields.io/badge/AI-LLM_Tool_Use-7C3AED?style=for-the-badge" alt="LLM Tool Use">
</p>

<p align="center">
  <img src=".github/preview.png" alt="HostArea 预览" width="800">
</p>

> 一个现代科技社区平台，集成 AI 助手 Alma，用于智能群聊协作与管理，基于 LLM Tool Use 架构构建。

<!-- README-I18N:START -->

[中文](./README.md) | English

<!-- README-I18N:END -->

---

## 特性

### AI 助手 Alma

Alma 是一个基于 **LLM Tool Use** 架构构建的创新型 AI 助手。与仅返回文本回复的传统聊天机器人不同，Alma 可以**执行实际操作**：

| 工具 | 描述 |
|------|------|
| `user_query` | 查询用户信息、封禁历史 |
| `channel_ban` | 在频道封禁用户 |
| `global_ban` | 全平台封禁 |
| `channel_mute` | 在频道禁言用户 |
| `revoke_message` | 撤回不当消息 |
| `post_delete` | 删除违规帖子 |
| `web_search` | 搜索网络实时信息 |
| `memory_recall` | 从记忆中召回用户上下文 |
| `memory_save` | 保存信息到记忆 |

**示例**：当管理员输入"封禁用户 ZhangSan 30分钟"，Alma 理解意图后自动调用 `channel_ban` 工具完成操作。

**核心特性**：
- 多轮对话，上下文感知
- 网络搜索增强，实时回答
- 从记忆中召回用户画像
- 通过 `bot_pending_actions` 表进行分布式状态管理

### 多频道实时聊天

- **多频道支持**：综合大厅、概念讨论、技术交流、随机聊天等
- **消息类型**：文本、图片、代码片段、系统通知
- **流式响应**：基于 SSE 的 AI 回复，实时体验
- **消息管理**：撤回、置顶、反应

### 内容社区

- **帖子发布**：分享技术文章、提问、讨论
- **分类**：编程、DevOps、AI/ML、职业、随机
- **互动**：评论、点赞、收藏、热榜排名
- **内容审核**：人工审核队列、AI 辅助检测

### 管理后台

- **数据概览**：用户统计、帖子数量、频道指标
- **用户管理**：查看、警告、禁言、封禁、删除用户
- **内容审核**：审核待发布帖子、处理举报
- **数据库操作**：管理员直接 SQL 查询界面
- **系统日志**：追踪管理员操作和系统事件

### 权限系统

- **认证**：Laravel Sanctum 基于令牌的身份验证
- **角色**：普通用户、版主、管理员
- **频道权限**：按频道的封禁/禁言控制
- **全局权限**：全平台封禁和管理权限

---

## 技术栈

### 后端
- **框架**：Laravel 11
- **数据库**：MySQL 8.0
- **认证**：Laravel Sanctum
- **API 风格**：RESTful API

### 前端
- **框架**：Nuxt.js 4 (Vue 3)
- **样式**：Tailwind CSS
- **状态管理**：Pinia
- **HTTP 客户端**：Axios

### AI 集成
- **LLM 提供商**：SiliconFlow API
- **模型**：GLM-4-Flash
- **架构**：LLM Tool Use

---

## 项目结构

```
host-area/
├── host-area-backend/        # Laravel 后端
│   ├── app/
│   │   ├── Http/Controllers/    # API 控制器
│   │   ├── Models/               # Eloquent 模型
│   │   ├── Services/             # 业务逻辑
│   │   └── Tools/                # Alma 工具实现
│   ├── database/
│   │   └── migrations/            # 数据库迁移
│   └── routes/
│       └── api.php                # API 路由
│
├── host-area-frontend/       # Nuxt.js 前端
│   ├── components/              # Vue 组件
│   ├── pages/                   # 路由页面
│   ├── stores/                  # Pinia 状态存储
│   └── composables/             # Vue 组合式函数
│
└── host-area-paper/          # 毕业论文
```

---

## 快速开始

### 环境要求

- PHP 8.2+
- Composer 2.x
- Node.js 18+
- MySQL 8.0+
- SiliconFlow API Key

### 后端配置

```bash
cd host-area-backend

# 安装依赖
composer install

# 配置环境
cp .env.example .env
php artisan key:generate

# 在 .env 中配置数据库，然后运行：
php artisan migrate
php artisan db:seed --class=AdminSeeder

# 启动开发服务器
php artisan serve
```

### 前端配置

```bash
cd host-area-frontend

# 安装依赖
npm install

# 配置环境
cp .env.example .env

# 启动开发服务器
npm run dev
```

### 环境变量

**后端 (`.env`)：**
```env
APP_NAME=HostArea
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=host_area
DB_USERNAME=root
DB_PASSWORD=your_password

SANCTUM_STATEFUL_DOMAINS=localhost:3000

SILICONFLOW_API_KEY=your_api_key
```

**前端 (`.env`)：**
```env
NUXT_PUBLIC_API_BASE_URL=http://localhost:8000/api
```

---

## 数据库架构

### 核心表

| 表名 | 描述 |
|------|------|
| `users` | 用户账户和个人资料 |
| `channels` | 聊天频道 |
| `messages` | 聊天消息 |
| `posts` | 社区帖子 |
| `comments` | 帖子评论 |
| `likes` | 点赞和收藏 |
| `bot_pending_actions` | AI 动作确认状态 |
| `memory_entries` | AI 记忆存储 |

### 权限表

| 表名 | 描述 |
|------|------|
| `user_roles` | 用户角色分配 |
| `permissions` | 权限定义 |
| `user_bans` | 封禁记录 |
| `channel_mutes` | 禁言记录 |

---

## 架构亮点

### LLM Tool Use 流程

```
用户输入 → LLM 意图识别 → 工具选择 → 动作执行 → 响应
                                     ↓
                              权限检查
                              （仅管理员工具）
                                     ↓
                              确认状态（破坏性操作）
```

### 分布式状态管理

`bot_pending_actions` 表支持分布式环境中的可靠 AI 动作确认：

```sql
CREATE TABLE bot_pending_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tool_name VARCHAR(255) NOT NULL,
    tool_args JSON NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL
);
```

---

## 致谢

- [Laravel](https://laravel.com/) - PHP 框架
- [Nuxt.js](https://nuxt.com/) - Vue.js 框架
- [SiliconFlow](https://siliconflow.cn/) - LLM API 提供商
- [Tailwind CSS](https://tailwindcss.com/) - 实用优先 CSS 框架

---

<p align="center">
  <sub>毕业设计项目 · 2025</sub>
</p>
