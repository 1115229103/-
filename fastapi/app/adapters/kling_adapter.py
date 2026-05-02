"""Kling (快手可灵) API adapter.

Used by: 可灵 Image O1, 可灵 V3 Omni (video/TTS/sound).

Auth: JWT HS256 signature using AccessKey ID + AccessKey Secret.
The AccessKey ID is the kid, the Secret is used to sign the JWT.

Async API for video: submit task → poll for result.
"""

import hashlib
import hmac
import json
import time
from typing import Any

import httpx
from loguru import logger

from .base import BaseAdapter


class KlingAdapter(BaseAdapter):
    def _generate_jwt(self, access_key_id: str, access_key_secret: str) -> str:
        """Generate a HS256 JWT token for Kling API authentication.

        Kling uses a custom HS256 JWT:
        - Header: {"alg": "HS256", "typ": "JWT"}
        - Payload: {"iss": access_key_id, "iat": now, "exp": now+1800}
        - Sign with access_key_secret
        """
        import base64

        header = {"alg": "HS256", "typ": "JWT"}
        now = int(time.time())
        payload = {
            "iss": access_key_id,
            "iat": now,
            "exp": now + 1800,
            "nbf": now,
        }

        def b64url(data: bytes) -> str:
            return base64.urlsafe_b64encode(data).rstrip(b"=").decode()

        header_b64 = b64url(json.dumps(header).encode())
        payload_b64 = b64url(json.dumps(payload).encode())
        signing_input = f"{header_b64}.{payload_b64}"

        signature = hmac.new(
            access_key_secret.encode(),
            signing_input.encode(),
            hashlib.sha256,
        ).digest()
        signature_b64 = b64url(signature)

        return f"{signing_input}.{signature_b64}"

    async def _get_token(self, api_key: str) -> str:
        """Parse the api_key field to get JWT token.

        The api_key field for Kling contains the access_key_secret
        (or a JSON with both access_key_id and access_key_secret).
        The access_key_id comes from kwargs or the api_key field.
        """
        return api_key  # The api_key IS the access_key_secret; kid is in kwargs

    async def verify_key(self, base_url: str, api_key: str, **kwargs) -> bool:
        """Verify key by making a simple GET to account/status."""
        access_key_id = kwargs.get("access_key_id", "")
        if not access_key_id:
            logger.warning("Kling key verification requires access_key_id in kwargs")
            return False

        try:
            token = self._generate_jwt(access_key_id, api_key)
            async with httpx.AsyncClient(timeout=30) as client:
                resp = await client.get(
                    f"{base_url.rstrip('/')}/v1/account/status",
                    headers={"Authorization": f"Bearer {token}"},
                )
                if resp.status_code == 200:
                    logger.info("Kling key verified successfully")
                    return True
                logger.warning(f"Kling key verification failed: HTTP {resp.status_code}")
                return False
        except Exception as e:
            logger.error(f"Kling key verification error: {e}")
            return False

    async def call(
        self,
        base_url: str,
        request_path: str,
        api_key: str,
        params: dict[str, Any],
        **kwargs,
    ) -> dict[str, Any]:
        access_key_id = kwargs.get("access_key_id", "")
        token = self._generate_jwt(access_key_id, api_key)

        url = f"{base_url.rstrip('/')}{request_path}"
        headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json",
        }

        async with httpx.AsyncClient(timeout=120) as client:
            resp = await client.post(url, json=params, headers=headers)
            resp.raise_for_status()
            result = resp.json()

            # Handle async API (videos): response contains task_id, need polling
            if kwargs.get("is_async"):
                task_id = result.get("data", {}).get("task_id", "")
                if task_id:
                    return await self._poll_task(base_url, task_id, token, kwargs.get("poll_timeout", 600))
            return result

    async def _poll_task(self, base_url: str, task_id: str, token: str, timeout: int = 600) -> dict[str, Any]:
        """Poll an async Kling task until completion."""
        import asyncio

        async with httpx.AsyncClient(timeout=30) as client:
            start = time.time()
            while time.time() - start < timeout:
                resp = await client.get(
                    f"{base_url.rstrip('/')}/v1/videos/{task_id}",
                    headers={"Authorization": f"Bearer {token}"},
                )
                resp.raise_for_status()
                result = resp.json()
                status = result.get("data", {}).get("status", "")
                if status == "completed":
                    return result
                if status == "failed":
                    raise Exception(f"Kling task failed: {result}")
                await asyncio.sleep(3)
            raise TimeoutError(f"Kling task {task_id} timed out after {timeout}s")
