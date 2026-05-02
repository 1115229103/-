"""Replicate API adapter.

Used by: Flux (via Replicate), Real-ESRGAN, InstantID, GFPGAN, CodeFormer,
Mochi 1, SVD, MusicGen, RIFE, ControlNet, PhotoMaker, IP-Adapter.

Auth: Bearer Token.
Async predictions: POST → poll for completion.
"""

from typing import Any

import httpx
from loguru import logger

from .base import BaseAdapter


class ReplicateAdapter(BaseAdapter):
    async def verify_key(self, base_url: str, api_key: str, **kwargs) -> bool:
        """Verify key by calling GET /v1/account."""
        try:
            async with httpx.AsyncClient(timeout=30) as client:
                resp = await client.get(
                    f"{base_url.rstrip('/')}/v1/account",
                    headers={"Authorization": f"Bearer {api_key}"},
                )
                if resp.status_code == 200:
                    logger.info("Replicate key verified successfully")
                    return True
                logger.warning(f"Replicate key verification failed: HTTP {resp.status_code}")
                return False
        except Exception as e:
            logger.error(f"Replicate key verification error: {e}")
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

        async with httpx.AsyncClient(timeout=120) as client:
            # Create prediction
            resp = await client.post(url, json={"input": params}, headers=headers)
            resp.raise_for_status()
            result = resp.json()

            # Poll for completion
            if "id" in result:
                return await self._poll_prediction(base_url, result["id"], api_key, kwargs.get("poll_timeout", 600))
            return result

    async def _poll_prediction(
        self, base_url: str, prediction_id: str, api_key: str, timeout: int = 600
    ) -> dict[str, Any]:
        """Poll a Replicate prediction until completion."""
        import asyncio
        import time

        async with httpx.AsyncClient(timeout=30) as client:
            start = time.time()
            while time.time() - start < timeout:
                resp = await client.get(
                    f"{base_url.rstrip('/')}/v1/predictions/{prediction_id}",
                    headers={"Authorization": f"Bearer {api_key}"},
                )
                resp.raise_for_status()
                result = resp.json()
                status = result.get("status", "")
                if status in ("succeeded", "completed"):
                    return result
                if status in ("failed", "canceled"):
                    raise Exception(f"Replicate prediction failed: {result}")
                await asyncio.sleep(5)
            raise TimeoutError(f"Replicate prediction {prediction_id} timed out after {timeout}s")
