"""ElevenLabs TTS API adapter.

Auth: xi-api-key Header.
Endpoints: GET /v1/voices (key verification), POST /v1/text-to-speech/{voice_id}.
"""

from typing import Any

import httpx
from loguru import logger

from .base import BaseAdapter


class ElevenLabsAdapter(BaseAdapter):
    async def verify_key(self, base_url: str, api_key: str, **kwargs) -> bool:
        """Verify key by calling GET /v1/voices (free)."""
        try:
            async with httpx.AsyncClient(timeout=30) as client:
                resp = await client.get(
                    f"{base_url.rstrip('/')}/v1/voices",
                    headers={"xi-api-key": api_key},
                )
                if resp.status_code == 200:
                    logger.info("ElevenLabs key verified successfully")
                    return True
                logger.warning(f"ElevenLabs key verification failed: HTTP {resp.status_code}")
                return False
        except Exception as e:
            logger.error(f"ElevenLabs key verification error: {e}")
            return False

    async def call(
        self,
        base_url: str,
        request_path: str,
        api_key: str,
        params: dict[str, Any],
        **kwargs,
    ) -> dict[str, Any]:
        # Replace {voice_id} placeholder in path
        voice_id = kwargs.get("voice_id", params.pop("voice_id", "default"))
        path = request_path.replace("{voice_id}", voice_id)
        url = f"{base_url.rstrip('/')}{path}"

        headers = {
            "xi-api-key": api_key,
            "Content-Type": "application/json",
            "Accept": "audio/mpeg",
        }

        async with httpx.AsyncClient(timeout=60) as client:
            resp = await client.post(url, json=params, headers=headers)
            resp.raise_for_status()
            # ElevenLabs returns raw audio bytes — wrap in dict for consistency
            return {
                "content_type": resp.headers.get("content-type", "audio/mpeg"),
                "audio_data": resp.content.hex(),
                "status": "success",
            }
