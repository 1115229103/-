"""Envelope encryption service for user API keys.

Architecture:
  Master KEK (env var / K8s Secret / Vault)
      ↓ encrypts
  User DEK (Data Encryption Key) — one per user, randomized at registration
      ↓ encrypts
  User API Keys (stored in user_model_configs.api_key)

Key properties:
- Password change does NOT invalidate DEK (DEK lifetime = user lifetime)
- Database leak alone cannot decrypt keys (need Master KEK)
- Keys only decrypted in FastAPI memory during AI calls, then zeroed
- Laravel never touches plaintext API keys
"""

import hashlib
import os
from base64 import b64decode, b64encode

from cryptography.hazmat.primitives.ciphers.aead import AESGCM

from app.config import get_settings


class KeyService:
    """Envelope encryption: Master KEK → User DEK → API Keys."""

    def __init__(self):
        settings = get_settings()
        # Ensure Master KEK is exactly 32 bytes for AES-256-GCM
        kek_raw = settings.MASTER_KEK.encode()
        self.master_kek = hashlib.sha256(kek_raw).digest()  # Always 32 bytes

    # ─── User DEK Management ───────────────────────────────────

    def generate_user_dek(self) -> bytes:
        """Generate a new random AES-256-GCM key for a user."""
        return AESGCM.generate_key(bit_length=256)  # 32 bytes

    def wrap_user_dek(self, dek: bytes) -> str:
        """Encrypt a User DEK with the Master KEK → stored as wrapped_dek in DB."""
        nonce = os.urandom(12)
        aesgcm = AESGCM(self.master_kek)
        ciphertext = aesgcm.encrypt(nonce, dek, None)
        # Format: base64(nonce + ciphertext)
        return b64encode(nonce + ciphertext).decode()

    def unwrap_user_dek(self, wrapped_dek: str) -> bytes:
        """Decrypt a wrapped User DEK using the Master KEK."""
        data = b64decode(wrapped_dek)
        nonce, ciphertext = data[:12], data[12:]
        aesgcm = AESGCM(self.master_kek)
        return aesgcm.decrypt(nonce, ciphertext, None)

    # ─── API Key Encryption ────────────────────────────────────

    def encrypt_api_key(self, dek: bytes, api_key: str) -> str:
        """Encrypt a user's API key with their DEK → stored in user_model_configs."""
        nonce = os.urandom(12)
        aesgcm = AESGCM(dek)
        ciphertext = aesgcm.encrypt(nonce, api_key.encode(), None)
        return b64encode(nonce + ciphertext).decode()

    def decrypt_api_key(self, dek: bytes, encrypted_key: str) -> str:
        """Decrypt a user's API key using their DEK."""
        data = b64decode(encrypted_key)
        nonce, ciphertext = data[:12], data[12:]
        aesgcm = AESGCM(dek)
        plaintext = aesgcm.decrypt(nonce, ciphertext, None)
        return plaintext.decode()

    # ─── High-Level API ────────────────────────────────────────

    def encrypt_key_for_user(self, wrapped_dek: str, api_key: str) -> str:
        """Full path: unwrap DEK → encrypt key. Returns encrypted key for DB storage."""
        dek = self.unwrap_user_dek(wrapped_dek)
        return self.encrypt_api_key(dek, api_key)

    def decrypt_key_for_user(self, wrapped_dek: str, encrypted_key: str) -> str:
        """Full path: unwrap DEK → decrypt key. Returns plaintext API key.

        Caller must zero the returned string after use.
        """
        dek = self.unwrap_user_dek(wrapped_dek)
        return self.decrypt_api_key(dek, encrypted_key)


# Singleton
_key_service: KeyService | None = None


def get_key_service() -> KeyService:
    global _key_service
    if _key_service is None:
        _key_service = KeyService()
    return _key_service
