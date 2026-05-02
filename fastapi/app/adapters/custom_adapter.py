"""Custom protocol adapter.

Used by models with non-standard API protocols that the admin configures:
- Aliyun (AK/SK HMAC-SHA256 signing)
- Tencent Cloud (AK/SK signing)
- Baidu (access_token)
- iFlytek (multi-header signing)
- MiniMax, Ideogram, Recraft, Runway, Vidu, Luma, Haiper, Krea, etc.

For custom models, the admin can define HTTP request templates in
model_registry.default_params. This adapter uses those templates.
"""

from typing import Any

import httpx
from loguru import logger

from .base import BaseAdapter


class CustomAdapter(BaseAdapter):
    async def verify_key(self, base_url: str, api_key: str, **kwargs) -> bool:
        """Custom models may not have a standard verification endpoint.
        We attempt a simple GET on the base URL and accept any non-401 response.
        For models where auto-verification is impossible, this returns True by default.
        """
        verify_url = kwargs.get("verify_url", "")
        if verify_url:
            try:
                async with httpx.AsyncClient(timeout=30) as client:
                    resp = await client.get(verify_url)
                    if resp.status_code != 401:
                        logger.info("Custom key verification: endpoint accessible")
                        return True
                    return False
            except Exception as e:
                logger.error(f"Custom key verification error: {e}")
                return False
        # No verification endpoint configured — skip auto-verify
        logger.info("Custom key verification skipped (no verify_url configured)")
        return True

    async def call(
        self,
        base_url: str,
        request_path: str,
        api_key: str,
        params: dict[str, Any],
        **kwargs,
    ) -> dict[str, Any]:
        request_path = request_path or ""
        url = f"{base_url.rstrip('/')}{request_path}" if request_path else base_url

        # Use admin-configured template from default_params or kwargs
        headers_template = kwargs.get("headers_template", {})
        headers = dict(headers_template)

        # Replace {api_key} placeholder in headers/url
        if "{api_key}" in str(headers):
            headers = {k: v.replace("{api_key}", api_key) for k, v in headers.items()}

        # Default to Bearer token if no template
        if not headers:
            headers = {
                "Authorization": f"Bearer {api_key}",
                "Content-Type": "application/json",
            }

        # Request method override
        method = kwargs.get("method", "POST").upper()

        async with httpx.AsyncClient(timeout=120) as client:
            if method == "GET":
                resp = await client.get(url, params=params, headers=headers)
            elif method == "POST":
                resp = await client.post(url, json=params, headers=headers)
            elif method == "PUT":
                resp = await client.put(url, json=params, headers=headers)
            else:
                raise ValueError(f"Unsupported HTTP method: {method}")

            resp.raise_for_status()

            # Try JSON, fall back to text
            try:
                return resp.json()
            except Exception:
                return {"raw_response": resp.text, "status_code": resp.status_code}
