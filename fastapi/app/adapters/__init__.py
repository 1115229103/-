"""AI model protocol adapters.

Each adapter handles a specific API protocol type and knows how to:
1. Construct the correct HTTP request (headers, body format)
2. Handle authentication (Bearer, JWT, AK/SK signing, etc.)
3. Parse the response back to a unified format
"""

from .base import BaseAdapter
from .openai_adapter import OpenAIAdapter
from .anthropic_adapter import AnthropicAdapter
from .gemini_adapter import GeminiAdapter
from .kling_adapter import KlingAdapter
from .elevenlabs_adapter import ElevenLabsAdapter
from .stability_adapter import StabilityAdapter
from .replicate_adapter import ReplicateAdapter
from .bfl_adapter import BFLAdapter
from .azure_adapter import AzureAdapter
from .custom_adapter import CustomAdapter


ADAPTER_REGISTRY = {
    "openai": OpenAIAdapter,
    "anthropic": AnthropicAdapter,
    "gemini": GeminiAdapter,
    "kling": KlingAdapter,
    "elevenlabs": ElevenLabsAdapter,
    "stability": StabilityAdapter,
    "replicate": ReplicateAdapter,
    "bfl": BFLAdapter,
    "azure": AzureAdapter,
    "custom": CustomAdapter,
}


def get_adapter(api_type: str) -> BaseAdapter:
    adapter_cls = ADAPTER_REGISTRY.get(api_type)
    if not adapter_cls:
        raise ValueError(f"Unknown api_type: {api_type}")
    return adapter_cls()
