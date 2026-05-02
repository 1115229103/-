"""Pipeline orchestration service.

Coordinates AI calls across the 12-stage pipeline:
1. 文案解析 → 2. 分镜规划 → 3. 文案续写 → 4. 画面生成 →
5. 角色一致 → 6. 图像后处理 → 7. 图生视频 → 8. 视频增强 →
9. AI配音 → 10. 背景音乐 → 11. 字幕生成 → 12. 敏感词检测

Each stage reads the user's model config (model + encrypted key),
decrypts the key, calls the appropriate AI API via protocol adapter,
and returns results. Keys are zeroed after each call.
"""

import asyncio
from typing import Any

from loguru import logger

from app.adapters import get_adapter
from app.services.key_service import get_key_service


class PipelineService:
    """Orchestrates the AI pipeline stages."""

    async def run_stage(
        self,
        stage: str,
        user_id: int,
        wrapped_dek: str,
        model_config: dict[str, Any],
        stage_input: dict[str, Any],
    ) -> dict[str, Any]:
        """Execute a single pipeline stage for a user.

        Args:
            stage: Stage identifier (e.g. 'script_analysis', 'image_gen')
            user_id: User ID for logging
            wrapped_dek: User's wrapped DEK from DB
            model_config: Model registry entry + user's encrypted key + custom_params
            stage_input: Stage-specific input data (prompt, images, etc.)

        Returns:
            Stage output dict. Structure depends on the stage type.
        """
        ks = get_key_service()
        api_type = model_config.get("api_type", "openai")
        base_url = model_config.get("base_url", "")
        request_path = model_config.get("request_path", "")
        encrypted_key = model_config.get("api_key", "")
        default_params = model_config.get("default_params", {})
        custom_params = model_config.get("custom_params", {})

        # Merge params (user custom overrides admin defaults)
        params = {**(default_params or {}), **(custom_params or {})}
        params.update(stage_input.get("params", {}))

        # Decrypt key
        try:
            api_key = ks.decrypt_key_for_user(wrapped_dek, encrypted_key)
        except Exception as e:
            logger.error(f"Failed to decrypt key for user {user_id}: {e}")
            raise

        try:
            adapter = get_adapter(api_type)
            extra_kwargs = self._build_adapter_kwargs(model_config, stage_input)
            result = await adapter.call(base_url, request_path, api_key, params, **extra_kwargs)
            logger.info(f"Stage {stage} completed for user {user_id}")
            return {"status": "success", "data": result}
        except Exception as e:
            logger.error(f"Stage {stage} failed for user {user_id}: {e}")
            return {"status": "failed", "error": str(e)}
        finally:
            # Zero the decrypted key from memory
            api_key = "\x00" * len(api_key)

    async def verify_user_key(
        self,
        api_type: str,
        base_url: str,
        encrypted_key: str,
        wrapped_dek: str,
        **kwargs,
    ) -> bool:
        """Verify that a user's API key is valid."""
        ks = get_key_service()
        try:
            api_key = ks.decrypt_key_for_user(wrapped_dek, encrypted_key)
        except Exception as e:
            logger.error(f"Key decryption failed for verification: {e}")
            return False

        try:
            adapter = get_adapter(api_type)
            return await adapter.verify_key(base_url, api_key, **kwargs)
        finally:
            api_key = "\x00" * len(api_key)

    def _build_adapter_kwargs(
        self,
        model_config: dict[str, Any],
        stage_input: dict[str, Any],
    ) -> dict[str, Any]:
        """Build extra keyword arguments for the adapter call."""
        kwargs = {}

        # Kling: need access_key_id for JWT generation
        if model_config.get("api_type") == "kling":
            kwargs["access_key_id"] = stage_input.get("access_key_id", "")

        # Azure: need region
        if model_config.get("api_type") == "azure":
            kwargs["region"] = stage_input.get("region", "")

        # Async polling
        if stage_input.get("is_async"):
            kwargs["is_async"] = True
            kwargs["poll_timeout"] = stage_input.get("poll_timeout", 600)

        # Multipart
        if stage_input.get("is_multipart"):
            kwargs["is_multipart"] = True
            kwargs["files"] = stage_input.get("files", {})

        return kwargs
