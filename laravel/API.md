# AIStory API 文档

Base URL: `http://127.0.0.1:8000/api/v1`

## 认证

所有需认证的端点使用 Bearer Token (Laravel Sanctum):

```
Authorization: Bearer <token>
```

Token 通过 `/auth/register` 或 `/auth/login` 获取。

---

## 公开端点

### GET /health
健康检查。返回服务状态和版本。

响应 200: `{status, service, version, timestamp}`

### POST /auth/register
注册新用户。

| 参数 | 类型 | 必填 |
|------|------|------|
| name | string | 是 |
| email | string | 是 |
| password | string | 是 (min:8) |
| password_confirmation | string | 是 |

响应 201: `{data: {token, user: {id, name, email, role, created_at}}}`

### POST /auth/login
登录获取 Token。

| 参数 | 类型 | 必填 |
|------|------|------|
| email | string | 是 |
| password | string | 是 |

响应 200: `{data: {token, user: {id, name, email, role, created_at}}}`

### POST /auth/forgot-password
发送密码重置链接。

| 参数 | 类型 | 必填 |
|------|------|------|
| email | string | 是 |

### POST /auth/reset-password
重置密码。

| 参数 | 类型 | 必填 |
|------|------|------|
| email | string | 是 |
| token | string | 是 |
| password | string | 是 (min:8) |
| password_confirmation | string | 是 |

### GET /models
列出所有可用 AI 模型。可选 `?category=llm` 筛选。

响应 200: `{data: [{id, category, model_name, display_name, provider, api_type, status}]}`

### GET /models/categories
列出所有模型类别及其数量。

响应 200: `{data: [{category, count, label}]}`

### GET /plans
列出所有会员方案。

响应 200: `{data: [{id, name, slug, tier, price_monthly_cny, price_yearly_cny, features}]}`

---

## 需认证端点

Header: `Authorization: Bearer <token>`

### GET /auth/me
获取当前用户信息。

响应 200: `{data: {id, name, email, role, created_at}}`

### POST /auth/logout
撤销当前 Token。Token 立即失效。

### POST /auth/change-password
修改密码。

| 参数 | 类型 | 必填 |
|------|------|------|
| current_password | string | 是 |
| new_password | string | 是 (min:8) |

### PATCH /auth/me
更新个人资料。支持部分更新。

| 参数 | 类型 | 必填 |
|------|------|------|
| name | string | 否 (max:255) |
| avatar_url | string | 否 (url, max:2048) |

响应 200: `{data: {id, name, email, avatar_url}}`

### DELETE /auth/me
注销账号（GDPR 合规）。需密码确认，注销后所有 Token 立即失效，账号软删除保留数据完整性。

| 参数 | 类型 | 必填 |
|------|------|------|
| password | string | 是 |

响应 200: `{data: {message: "账号已注销"}}`
响应 403: `{error: "wrong_password", message: "密码错误，无法注销账号"}`

---

## 用户模型配置 (API Keys)

用户自持 API Key，FastAPI 内存解密后调用外部 AI 服务。

### GET /user/model-configs
列出当前用户的所有模型配置。API Key 已脱敏。

### POST /user/model-configs
添加模型配置（绑定自己的 API Key）。

| 参数 | 类型 | 必填 |
|------|------|------|
| model_registry_id | int | 是 |
| stage | string | 是 |
| api_key | string | 是 |
| priority | int | 否 |

### PUT /user/model-configs/{id}
更新模型配置。

### DELETE /user/model-configs/{id}
删除模型配置。

### POST /user/model-configs/{id}/verify
验证 API Key 是否可用（通过 FastAPI 实际调用测试）。

---

## 作品 (Works)

### GET /works
列出当前用户的所有作品。

### POST /works
创建作品。

| 参数 | 类型 | 必填 |
|------|------|------|
| title | string | 是 (max:128) |
| style | string | 否 |
| duration | int | 否 (1-3600) |
| description | text | 否 |

### GET /works/{id}
获取单个作品详情。仅作品所有者可访问。

### PUT /works/{id}
更新作品信息。

### DELETE /works/{id}
删除作品。

### GET /works/{id}/pipeline/progress
查询作品在 12 环节 AI 管道中的进度。

### POST /works/{id}/pipeline/start
启动管道执行（异步队列处理）。

---

## 会员与订单

### GET /membership
获取当前会员方案和状态。

### POST /orders
创建订阅订单。

| 参数 | 类型 | 必填 |
|------|------|------|
| plan_id | int | 是 |
| billing_cycle | string | 否 (monthly/yearly) |

---

## 管理端 API

所有管理端点需 admin 角色。前缀: `/api/v1/admin`

### Dashboard
- `GET /admin/dashboard` — 仪表盘概览

### 模型管理
- `GET    /admin/models` — 模型注册表列表 (分页)
- `POST   /admin/models` — 添加新模型
- `PUT    /admin/models/{id}` — 编辑模型
- `DELETE /admin/models/{id}` — 删除模型
- `PUT    /admin/models/{id}/status` — 切换启用/禁用
- `PUT    /admin/models/sort` — 批量排序

### 管道阶段
- `GET /admin/pipeline-stages` — 12 阶段列表
- `PUT /admin/pipeline-stages/{stage}` — 更新阶段配置

### 提示词模板
- `GET /admin/prompt-templates` — 阶段模板列表
- `PUT /admin/prompt-templates/{stage}` — 更新模板提示词

### 视觉风格
- `GET    /admin/visual-styles` — 列表
- `POST   /admin/visual-styles` — 创建
- `PUT    /admin/visual-styles/{id}` — 更新
- `DELETE /admin/visual-styles/{id}` — 删除

### 语音库
- `GET    /admin/voice-library` — 列表
- `POST   /admin/voice-library` — 创建
- `PUT    /admin/voice-library/{id}` — 更新
- `DELETE /admin/voice-library/{id}` — 删除

### 动作模板
- `GET    /admin/action-templates` — 列表
- `POST   /admin/action-templates` — 创建
- `PUT    /admin/action-templates/{id}` — 更新
- `DELETE /admin/action-templates/{id}` — 删除

### 水印配置
- `GET /admin/watermark-config` — 查看水印配置
- `PUT /admin/watermark-config` — 更新 (文本水印/图像水印/盲水印)

### 用户管理
- `GET /admin/users` — 用户列表 (分页)
- `GET /admin/users/{id}` — 用户详情

### 内容管理
- `GET    /admin/works` — 作品列表
- `GET    /admin/works/{id}` — 作品详情
- `DELETE /admin/works/{id}` — 删除作品
- `GET    /admin/sensitive-words` — 敏感词列表
- `POST   /admin/sensitive-words` — 添加
- `PUT    /admin/sensitive-words/{id}` — 更新
- `DELETE /admin/sensitive-words/{id}` — 删除
- `GET    /admin/banners` — Banner 列表
- `POST   /admin/banners` — 创建
- `PUT    /admin/banners/{id}` — 更新
- `DELETE /admin/banners/{id}` — 删除
- `GET    /admin/templates` — 模板列表
- `POST   /admin/templates` — 创建
- `PUT    /admin/templates/{id}` — 更新
- `DELETE /admin/templates/{id}` — 删除
- `GET    /admin/assets` — 资源列表
- `POST   /admin/assets` — 上传
- `PUT    /admin/assets/{id}` — 更新
- `DELETE /admin/assets/{id}` — 删除

### 审核
- `GET /admin/review/works` — 待审核作品
- `PUT /admin/review/works/{id}/approve` — 审核通过
- `PUT /admin/review/works/{id}/reject` — 审核拒绝

### 财务
- `GET /admin/orders` — 订单列表
- `GET /admin/finance/report` — 财务报表

### 方案管理
- `GET    /admin/plans` — 方案列表
- `POST   /admin/plans` — 创建方案
- `PUT    /admin/plans/{id}` — 更新方案
- `DELETE /admin/plans/{id}` — 删除方案
- `PUT    /admin/plans/{id}/status` — 切换启用

### 角色权限
- `GET /admin/roles` — 角色列表
- `PUT /admin/roles/{id}` — 更新角色权限

### 系统
- `GET  /admin/system/settings` — 系统设置 (KV)
- `PUT  /admin/system/settings` — 更新设置
- `GET  /admin/system/operation-logs` — 操作日志 (分页)
- `GET  /admin/system/backups` — 备份列表
- `POST /admin/system/backups` — 创建备份

---

## 限流

| 用户类型 | 速率 |
|----------|------|
| 访客 | 30 req/min |
| 认证用户 | 120 req/min |
| 管理员 | 120 req/min |

超出限制返回 `429 Too Many Requests`:

```json
{
  "error": "Too Many Requests",
  "message": "Rate limit exceeded. Try again in N seconds."
}
```

## 错误格式

所有错误返回统一 JSON 结构:

```json
{
  "error": "简短描述",
  "message": "详细消息",
  "errors": {
    "field_name": ["验证错误1", "验证错误2"]
  }
}
```

HTTP 状态码: 200 成功 | 201 已创建 | 204 无内容 | 401 未认证 | 403 无权限 | 404 不存在 | 422 验证失败 | 429 请求过多 | 500 服务端错误

## OpenAPI 规范

机器可读的 OpenAPI 3.0.3 规范文件: `public/openapi.json` (728 lines)
