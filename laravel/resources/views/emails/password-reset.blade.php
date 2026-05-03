<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: sans-serif; max-width: 480px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #333;">AIStory 密码重置</h2>
    <p>您请求了密码重置。请使用以下链接完成密码重置：</p>
    <p>
        <a href="{{ $resetUrl }}" style="display: inline-block; padding: 12px 24px; background: #6366f1; color: white; text-decoration: none; border-radius: 6px;">
            重置密码
        </a>
    </p>
    <p style="color: #999; font-size: 13px; margin-top: 24px;">
        该链接在 60 分钟内有效。如果您没有请求密码重置，请忽略此邮件。
    </p>
</body>
</html>
