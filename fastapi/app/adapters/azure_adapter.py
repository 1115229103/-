"""Azure Cognitive Services adapter.

Used by: Azure Speech TTS, Azure Speech-to-Text.
Auth: Ocp-Apim-Subscription-Key Header.
Requires: region (from kwargs or params).
"""

from typing import Any

import httpx
from loguru import logger

from .base import BaseAdapter


class AzureAdapter(BaseAdapter):
    async def verify_key(self, base_url: str, api_key: str, **kwargs) -> bool:
        """Verify key by checking the token endpoint. A 401 means invalid key."""
        region = kwargs.get("region", "")
        if not region:
            # Try to extract from base_url
            import re

            match = re.search(r"https?://([^.]+)", base_url)
            region = match.group(1) if match else ""

        try:
            async with httpx.AsyncClient(timeout=30) as client:
                # Try to get an auth token
                resp = await client.post(
                    f"https://{region}.api.cognitive.microsoft.com/sts/v1.0/issuetoken",
                    headers={"Ocp-Apim-Subscription-Key": api_key},
                )
                if resp.status_code == 200:
                    logger.info("Azure key verified successfully")
                    return True
                logger.warning(f"Azure key verification failed: HTTP {resp.status_code}")
                return False
        except Exception as e:
            logger.error(f"Azure key verification error: {e}")
            return False

    async def call(
        self,
        base_url: str,
        request_path: str,
        api_key: str,
        params: dict[str, Any],
        **kwargs,
    ) -> dict[str, Any]:
        region = kwargs.get("region", "")
        if region and "{region}" in base_url:
            base_url = base_url.replace("{region}", region)

        url = f"{base_url.rstrip('/')}{request_path}"
        headers = {
            "Ocp-Apim-Subscription-Key": api_key,
        }

        # TTS: SSML in body; STT: audio binary
        content_type = kwargs.get("content_type", "application/ssml+xml")
        headers["Content-Type"] = content_type

        async with httpx.AsyncClient(timeout=60) as client:
            if content_type == "application/ssml+xml":
                resp = await client.post(url, content=params.get("ssml", ""), headers=headers)
            else:
                resp = await client.post(url, data=params.get("audio", b""), headers=headers)

            resp.raise_for_status()
            return {
                "content_type": resp.headers.get("content-type", "audio/mpeg"),
                "data": resp.content.hex(),
                "status": "success",
            }
