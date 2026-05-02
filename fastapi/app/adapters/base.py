"""Base adapter interface that all protocol adapters must implement."""

from abc import ABC, abstractmethod
from typing import Any, Optional


class BaseAdapter(ABC):
    """Abstract base for AI model protocol adapters."""

    @abstractmethod
    async def verify_key(self, base_url: str, api_key: str, **kwargs) -> bool:
        """Verify that the provided API key is valid by making a minimal zero-cost call.

        Returns True if the key is valid, False otherwise.
        """
        ...

    @abstractmethod
    async def call(
        self,
        base_url: str,
        request_path: str,
        api_key: str,
        params: dict[str, Any],
        **kwargs,
    ) -> dict[str, Any]:
        """Make an API call to the AI model.

        Args:
            base_url: The API base URL (e.g. https://api.openai.com)
            request_path: The API path (e.g. /v1/chat/completions)
            api_key: The user's decrypted API key
            params: Request parameters / body

        Returns:
            Unified response dict
        """
        ...
