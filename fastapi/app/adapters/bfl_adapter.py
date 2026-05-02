"""Black Forest Labs (BFL) API adapter.

Used by: Flux.1 Pro/Dev (direct, not via Replicate).
Auth: x-key Header.
"""

from typing import Any

import httpx
from loguru import logger

from .base import BaseAdapter


class BFLAdapter(BaseAdapter):
    async def verify_key(self, base_url: str, api_key: str, **kwargs) -> bool:
        """Verify key by making a minimal status check. BFL has no free endpoint,
        so we attempt a minimal GET to the base URL."""
        try:
            async with httpx.AsyncClient(timeout=30) as client:
                resp = await client.get(
                    f"{base_url.rstrip('/')}/v1/",
                    headers={"x-key": api_key},
                )
                # BFL returns 404 on root but 401 on bad key
                if resp.status_code != 401:
                    logger.info("BFL key appears valid")
                    return True
                logger.warning("BFL key verification failed: HTTP 401")
                return False
        except Exception as e:
            logger.error(f"BFL key verification error: {e}")
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
            "x-key": api_key,
            "Content-Type": "application/json",
        }

        async with httpx.AsyncClient(timeout=120) as client:
            resp = await client.post(url, json=params, headers=headers)
            resp.raise_for_status()
            return resp.json()
