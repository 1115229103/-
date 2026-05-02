"""Anthropic Messages API adapter.

Used by: Claude Opus 4.7, Claude Sonnet 4.6.
Auth: x-api-key Header + anthropic-version Header.
"""

from typing import Any

import httpx
from loguru import logger

from .base import BaseAdapter


class AnthropicAdapter(BaseAdapter):
    async def verify_key(self, base_url: str, api_key: str, **kwargs) -> bool:
        """Verify key by making a minimal Messages call (max_tokens=1)."""
        try:
            async with httpx.AsyncClient(timeout=30) as client:
                resp = await client.post(
                    f"{base_url.rstrip('/')}/v1/messages",
                    headers={
                        "x-api-key": api_key,
                        "anthropic-version": "2023-06-01",
                        "Content-Type": "application/json",
                    },
                    json={
                        "model": "claude-sonnet-4-6",
                        "max_tokens": 1,
                        "messages": [{"role": "user", "content": "Hi"}],
                    },
                )
                if resp.status_code == 200:
                    logger.info("Anthropic key verified successfully")
                    return True
                logger.warning(f"Anthropic key verification failed: HTTP {resp.status_code} {resp.text[:200]}")
                return False
        except Exception as e:
            logger.error(f"Anthropic key verification error: {e}")
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
            "x-api-key": api_key,
            "anthropic-version": "2023-06-01",
            "Content-Type": "application/json",
        }

        async with httpx.AsyncClient(timeout=120) as client:
            resp = await client.post(url, json=params, headers=headers)
            resp.raise_for_status()
            return resp.json()
