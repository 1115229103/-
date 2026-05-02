# AIStory API 文档

## 基础信息

- **Base URL**: `http://localhost:8000/api/v1`
- **鉴权方式**: Bearer Token (Laravel Sanctum)
- **Content-Type**: `application/json` (GET 除外)
- **限流**: 公开端点 30次/分钟（访客），认证端点 120次/分钟（用户）

---

## 一、公开端点（无需 Token）

### 认证

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/auth/register` | 用户注册 |
| POST | `/auth/login` | 用户登录（返回 token） |

**POST /auth/register**
```json
{ "name": "用户名", "email": "user@example.com", "password": "password", "password_confirmation": "password" }
```
**Response**: `{ "data": { "user": {...}, "token": "..." } }`

**POST /auth/login**
```json
{ "email": "user@example.com", "password": "password" }
```
**Response**: `{ "data": { "user": {...}, "token": "..." } }`

### 模型浏览

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/models/categories` | 获取所有环节类别（10个） |
| GET | `/models` | 获取模型列表 |
| GET | `/models?category=llm` | 按类别筛选模型 |

---

## 二、用户认证端点（需要 Token）

Header: `Authorization: Bearer {token}`

### 用户信息

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/auth/me` | 获取当前用户信息 |
| POST | `/auth/logout` | 退出登录（Token 失效） |

### 模型配置 (BYOK)

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/user/model-configs` | 获取已配置的模型列表 |
| POST | `/user/model-configs` | 添加模型+Key 配置 |
| PUT | `/user/model-configs/{id}` | 更新配置 |
| DELETE | `/user/model-configs/{id}` | 删除配置 |
| POST | `/user/model-configs/{id}/verify` | 校验 Key 有效性 |

**POST /user/model-configs**
```json
{ "model_registry_id": 1, "category": "llm", "stage": "script_analysis", "api_key": "sk-xxx", "priority": 0 }
```

### 作品管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/works` | 作品列表（当前用户） |
| POST | `/works` | 创建作品 |
| GET | `/works/{id}` | 作品详情（含角色/场景/分镜等） |
| PUT | `/works/{id}` | 更新作品 |
| DELETE | `/works/{id}` | 删除作品 |

**POST /works**
```json
{ "title": "作品标题", "style": "写实", "target_duration_sec": 60 }
```

### 管道控制

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/works/{id}/pipeline/start` | 启动 12 阶段 AI 管道 |
| GET | `/works/{id}/pipeline/progress` | 获取管道进度（轮询用） |

### 会员 & 支付

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/plans` | 套餐列表 |
| GET | `/membership` | 当前用户的会员信息 |
| POST | `/orders` | 创建订单 |

---

## 三、管理后台 API

前缀: `/api/v1/admin` | 需要: `auth:sanctum` + admin 角色

### 仪表盘

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/dashboard` | 数据概览（用户数/作品数/模型数/今日订单） |

### 模型注册

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/models` | 模型列表 |
| POST | `/admin/models` | 添加模型 |
| PUT | `/admin/models/{id}` | 编辑模型 |
| DELETE | `/admin/models/{id}` | 删除模型 |
| PUT | `/admin/models/{id}/status` | 启用/禁用模型 |
| PUT | `/admin/models/sort` | 调整排序 |

### 管道配置

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/pipeline-stages` | 12 阶段配置列表 |
| PUT | `/admin/pipeline-stages/{stage}` | 更新阶段配置 |

### 提示词模板

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/prompt-templates` | 提示词模板列表 |
| PUT | `/admin/prompt-templates/{stage}` | 更新某阶段提示词 |

### 风格预设

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/visual-styles` | 风格列表 |
| POST | `/admin/visual-styles` | 添加风格 |
| PUT | `/admin/visual-styles/{id}` | 编辑风格 |
| DELETE | `/admin/visual-styles/{id}` | 删除风格 |

### 音色库

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/voice-library` | 音色列表 |
| POST | `/admin/voice-library` | 添加音色 |
| PUT | `/admin/voice-library/{id}` | 编辑音色 |
| DELETE | `/admin/voice-library/{id}` | 删除音色 |

### 动作模板

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/action-templates` | 动作模板列表 |
| POST | `/admin/action-templates` | 添加动作模板 |
| PUT | `/admin/action-templates/{id}` | 编辑动作模板 |
| DELETE | `/admin/action-templates/{id}` | 删除动作模板 |

### 水印配置

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/watermark-config` | 水印配置详情 |
| PUT | `/admin/watermark-config` | 更新水印配置 |

### 用户管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/users` | 用户列表（分页） |
| GET | `/admin/users/{id}` | 用户详情 |

### 内容管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/works` | 全部作品列表 |
| GET | `/admin/works/{id}` | 作品详情 |
| DELETE | `/admin/works/{id}` | 删除作品 |
| GET | `/admin/sensitive-words` | 敏感词列表 |
| POST | `/admin/sensitive-words` | 添加敏感词 |
| PUT | `/admin/sensitive-words/{id}` | 编辑敏感词 |
| DELETE | `/admin/sensitive-words/{id}` | 删除敏感词 |

### 运营管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/banners` | Banner CRUD |
| GET/POST/PUT/DELETE | `/admin/templates` | 文案模板 CRUD |
| GET/POST/PUT/DELETE | `/admin/assets` | 素材库 CRUD (BGM/音效/图片) |

### 财务管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/orders` | 订单列表 |
| GET | `/admin/finance/report` | 财务报表 |

### 系统管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/system/settings` | 系统设置 |
| PUT | `/admin/system/settings` | 更新系统设置 |
| GET | `/admin/system/operation-logs` | 操作日志 |
| GET | `/admin/system/backups` | 备份记录 |
| POST | `/admin/system/backups` | 创建备份 |

---

## 四、FastAPI AI 编排服务

内部服务，端口独立（不在公开 API 中暴露）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/` | 服务状态 |
| GET | `/health` | 健康检查 |
| POST | `/generate-dek` | 生成用户数据密钥（信封加密） |

---

## 五、通用约定

### 响应格式
所有端点返回统一格式:
```json
{ "data": {...} }
```
列表返回:
```json
{ "data": [...] }
```
分页返回:
```json
{ "data": [...], "meta": { "current_page": 1, "total": 100 } }
```

### 错误响应
```json
{ "message": "错误描述", "errors": { "field": ["错误信息"] } }
```

### HTTP 状态码
- `200` — 成功
- `201` — 创建成功
- `401` — 未认证（Token 缺失或失效）
- `403` — 无权限（非 admin 访问管理端点）
- `422` — 参数校验失败
- `429` — 触发限流
- `500` — 服务器错误
