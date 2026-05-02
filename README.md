# AIStory — BYOK AI 视频生成平台

自带 Key (Bring Your Own Key) 的一站式 AI 视频生成工作流引擎。平台提供模型注册和管道编排，用户自持 API Key，自由组合各环节 AI 模型。

## 架构

```
用户浏览器 (React / Vue)
    │
    ▼
Laravel 11 (Web API)  ←→  MySQL / Redis / OSS
    │ 职责: 用户认证、作品CRUD、支付、管理后台
    │
    │ (HTTPS internal, mTLS)
    ▼
FastAPI (AI Gateway)  ←→  各 AI 模型 API (使用用户 Key)
    │ 职责: Key解密、11种API协议适配、重试/轮询
    │
    ▼
FFmpeg Worker  ←→  视频合成/转码/水印
```

## 技术栈

| 层级 | 技术 | 版本 |
|------|------|------|
| Web 应用 | Laravel | 11.x (PHP 8.2+) |
| AI 编排 | FastAPI | Python 3.12+ |
| 数据库 | MySQL | 8.0 |
| 缓存/队列 | Redis | 7.x |
| 视频合成 | FFmpeg | 6.x+ |
| 用户前端 | React 19 + Vite | — |
| 管理后台 | Vue 3 + Vite | — |

## 12 环节 AI 管道

1. 文案解析 → 2. 分镜规划 → 3. 文案续写 → 4. 画面生成 → 5. 角色一致性 → 6. 图像后处理 → 7. 图生视频 → 8. 视频增强 → 9. AI配音 → 10. 背景音乐 → 11. 字幕生成 → 12. 敏感词检测

## 快速开始

### 环境要求

- PHP 8.2+ (with bcmath, ctype, curl, dom, fileinfo, mbstring, pdo_mysql, xml)
- Composer 2.x
- Python 3.12+
- MySQL 8.0
- Redis 7.x
- FFmpeg 6.x+
- Node.js 20.x (for frontend builds)

### 1. 克隆项目

```bash
git clone <repo-url> aistory
cd aistory
```

### 2. 配置 Laravel

```bash
cd laravel
cp .env.example .env
# 编辑 .env — 设置数据库、Redis、FastAPI 地址
composer install
php artisan key:generate
php artisan migrate --seed
```

### 3. 配置 FastAPI

```bash
cd fastapi
pip install -r requirements.txt
# 生成 Master KEK
openssl rand -hex 32  # 将输出填入 laravel/.env 的 MASTER_KEK
uvicorn app.main:app --host 127.0.0.1 --port 8001
```

### 4. 构建前端

```bash
# 用户端 (React)
cd user-app
npm install
npm run build

# 管理后台 (Vue 3)
cd admin-app
npm install
npm run build
```

### 5. 启动服务

```bash
# 启动 Laravel
cd laravel
php artisan serve

# 启动队列 Worker
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

# 启动 FastAPI (另一个终端)
cd fastapi
uvicorn app.main:app --host 127.0.0.1 --port 8001
```

访问:
- 用户端: http://localhost:8000/user-app/
- 管理后台: http://localhost:8000/admin/
- API 文档: [API.md](laravel/API.md)

## 测试

```bash
# API Smoke Tests (~10s)
php tests/api_smoke.php          # 24 tests
php tests/admin_api_smoke.php    # 22 tests

# E2E 综合测试 (~30s)
php tests/e2e.php                # 30+ tests
```

## 生产部署

参考 `laravel/deploy/` 目录:

| 文件 | 用途 |
|------|------|
| `nginx.conf` | Nginx HTTPS 配置 (含安全头、静态缓存、文件保护) |
| `supervisor.conf` | 队列 Worker 守护 (2 workers、重试、日志) |
| `.env.production.example` | 生产环境变量模板 |

### 关键安全配置

```ini
# 信封加密主密钥 (必须设置)
MASTER_KEK=<openssl rand -hex 32>

# FastAPI 内部通信 Token
FASTAPI_INTERNAL_TOKEN=<random-64-char>
```

## API 文档

完整 API 参考: [API.md](laravel/API.md)

- 用户端: `/api/v1/auth/*`, `/api/v1/models/*`, `/api/v1/works/*`, `/api/v1/plans`, `/api/v1/membership`
- 管理端: `/api/v1/admin/*` (需 admin 角色)
- FastAPI 内部: `http://127.0.0.1:8001/` (不暴露公网)

## 安全特性

- **信封加密**: Master KEK → User DEK → API Keys (AES-256-GCM)
- **Key 隔离**: Laravel 不接触用户 Key 明文；Key 仅在 FastAPI 内存中解密
- **提示注入防护**: 内容边界标记 + System/User 角色分离 + JSON Schema 校验
- **mTLS 内部通信**: Laravel ↔ FastAPI 双向证书验证
- **API 限流**: 访客 30/分，用户 120/分，按路由分组

## 模型注册表

管理员可在后台为 10 个类别添加/管理 AI 模型:

| 类别 | 环节 | 种子模型数 |
|------|------|-----------|
| `llm` | 文案解析/分镜/续写 | 15 |
| `image_gen` | 画面生成 | 14 |
| `consistency` | 角色一致 | 6 |
| `image_enhance` | 图像后处理 | 6 |
| `image2video` | 图生视频 | 15 |
| `video_enhance` | 视频增强 | 7 |
| `tts` | AI配音 | 14 |
| `music` | 背景音乐 | 6 |
| `asr` | 字幕生成 | 8 |
| `moderation` | 敏感词检测 | 5 |

## 会员体系

| | 免费版 | 基础版 | 专业版 | 企业版 |
|---|---|---|---|---|
| 月费 | 免费 | ¥39/月 | ¥199/月 | 定制 |
| 分辨率 | 720P | 1080P | 4K | 8K |
| 水印 | 有 | 无 | 无 | 无 |
| 项目数 | 3 | 30 | 200 | ∞ |

## 许可

Proprietary — All Rights Reserved
