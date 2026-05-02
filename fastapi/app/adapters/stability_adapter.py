"""Stability AI API adapter.

Used by: Stable Diffusion 3.5, Stable Audio 2, SVD.
Auth: Bearer Token.
Note: Stable Audio uses multipart/form-data, not JSON.
"""

from typing import Any

import httpx
from loguru import logger

from .base import BaseAdapter


class StabilityAdapter(BaseAdapter):
    async def verify_key(self, base_url: str, api_key: str, **kwargs) -> bool:
        """Verify key by calling GET /v2beta/account."""
        try:
            async with httpx.AsyncClient(timeout=30) as client:
                resp = await client.get(
                    f"{base_url.rstrip('/')}/v2beta/account",
                    headers={"Authorization": f"Bearer {api_key}"},
                )
                if resp.status_code == 200:
                    logger.info("Stability key verified successfully")
                    return True
                logger.warning(f"Stability key verification failed: HTTP {resp.status_code}")
                return False
        except Exception as e:
            logger.error(f"Stability key verification error: {e}")
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
        headers = {"Authorization": f"Bearer {api_key}"}

        # Stable Audio uses multipart/form-data
        is_multipart = kwargs.get("is_multipart", "audio" in request_path)

        async with httpx.AsyncClient(timeout=120) as client:
            if is_multipart:
                # Build form data from params
                form_data = {}
                files = {}
                for key, value in params.items():
                    if key in ("file", "image", "audio"):
                        files[key] = value
                    else:
                        form_data[key] = str(value)
                resp = await client.post(url, headers=headers, data=form_data, files=files)
            else:
                resp = await client.post(url, json=params, headers={**headers, "Content-Type": "application/json"})

            resp.raise_for_status()
            return resp.json()
