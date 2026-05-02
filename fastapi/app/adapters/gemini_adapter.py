"""Google Gemini / Vertex AI adapter.

Used by: Gemini 3.1 Pro, Gemini Flash Image, Imagen 3, Veo 3.1,
Google Cloud TTS, Google Cloud STT.

Primary auth: API key passed as ?key= query parameter or x-goog-api-key Header.
Supports OAuth (not implemented in MVP).
"""

from typing import Any

import httpx
from loguru import logger

from .base import BaseAdapter


class GeminiAdapter(BaseAdapter):
    async def verify_key(self, base_url: str, api_key: str, **kwargs) -> bool:
        """Verify key by listing available models (free)."""
        try:
            async with httpx.AsyncClient(timeout=30) as client:
                resp = await client.get(
                    f"{base_url.rstrip('/')}/v1beta/models",
                    params={"key": api_key},
                )
                if resp.status_code == 200:
                    logger.info("Gemini key verified successfully")
                    return True
                # Also try x-goog-api-key header for Cloud endpoints
                resp2 = await client.get(
                    f"{base_url.rstrip('/')}/v1beta/models",
                    headers={"x-goog-api-key": api_key},
                )
                if resp2.status_code == 200:
                    logger.info("Gemini key verified via x-goog-api-key header")
                    return True
                logger.warning(f"Gemini key verification failed: HTTP {resp.status_code}")
                return False
        except Exception as e:
            logger.error(f"Gemini key verification error: {e}")
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

        # Gemini Generative Language API uses ?key= query param
        # Cloud endpoints (TTS/STT) use x-goog-api-key header
        use_query_param = kwargs.get("use_query_param", True)

        async with httpx.AsyncClient(timeout=120) as client:
            if use_query_param:
                resp = await client.post(
                    url,
                    params={"key": api_key},
                    json=params,
                    headers={"Content-Type": "application/json"},
                )
            else:
                resp = await client.post(
                    url,
                    json=params,
                    headers={
                        "x-goog-api-key": api_key,
                        "Content-Type": "application/json",
                    },
                )

            resp.raise_for_status()
            return resp.json()
