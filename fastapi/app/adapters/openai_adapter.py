"""OpenAI-compatible protocol adapter.

Used by: OpenAI (GPT-4o, GPT-5, GPT-Image-2, TTS, Whisper, Moderation),
DeepSeek, Kimi, Qwen, Doubao, ERNIE, GLM, Mistral, Yi, Grok, Llama.
All use Bearer token auth + OpenAI-compatible request/response format.
"""

from typing import Any

import httpx
from loguru import logger

from .base import BaseAdapter


class OpenAIAdapter(BaseAdapter):
    async def verify_key(self, base_url: str, api_key: str, **kwargs) -> bool:
        """Verify key by calling GET /v1/models (free, zero-cost)."""
        try:
            async with httpx.AsyncClient(timeout=30) as client:
                resp = await client.get(
                    f"{base_url.rstrip('/')}/v1/models",
                    headers={"Authorization": f"Bearer {api_key}"},
                )
                if resp.status_code == 200:
                    logger.info("OpenAI key verified successfully")
                    return True
                logger.warning(f"OpenAI key verification failed: HTTP {resp.status_code}")
                return False
        except Exception as e:
            logger.error(f"OpenAI key verification error: {e}")
            return False

    async def call(
        self,
        base_url: str,
        request_path: str,
        api_key: str,
        params: dict[str, Any],
        **kwargs,
    ) -> dict[str, Any]:
        url = f"{base_url.rstrip('/')}{request_path}"
        headers = {
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        }

        # Determine if this is a multipart request (e.g. Whisper audio upload)
        is_multipart = kwargs.get("is_multipart", False)

        async with httpx.AsyncClient(timeout=120) as client:
            if is_multipart:
                files = kwargs.get("files", {})
                data = kwargs.get("data", params)
                resp = await client.post(url, headers={"Authorization": f"Bearer {api_key}"}, files=files, data=data)
            else:
                resp = await client.post(url, json=params, headers=headers)

            resp.raise_for_status()
            return resp.json()
