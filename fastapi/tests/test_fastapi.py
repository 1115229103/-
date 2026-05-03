"""
FastAPI Test Suite — AIStory AI Gateway
Tests: KeyService encryption, SSRF protection, internal auth, health.

Run: python tests/test_fastapi.py
Requires: pip install httpx pytest (or just run directly)
"""

import os
import sys
import unittest

# Ensure the fastapi directory is in path
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from unittest.mock import patch, MagicMock
from urllib.parse import urlparse


# ═══════════════════════════════════════════════════════════════
# Test: SSRF Protection
# ═══════════════════════════════════════════════════════════════

class TestSSRFProtection(unittest.TestCase):
    """Verify private/internal IP blocking for SSRF prevention."""

    @classmethod
    def setUpClass(cls):
        from app.routers.internal import _is_safe_url as _fn
        cls._is_safe_url = staticmethod(_fn)

    def test_blocks_localhost(self):
        self.assertFalse(self._is_safe_url("http://localhost:8080"))
        self.assertFalse(self._is_safe_url("http://localhost:3000/api"))

    def test_blocks_127_loopback(self):
        self.assertFalse(self._is_safe_url("http://127.0.0.1:8000"))
        self.assertFalse(self._is_safe_url("http://127.0.0.2:9000"))

    def test_blocks_10_private(self):
        self.assertFalse(self._is_safe_url("http://10.0.0.1:5000"))
        self.assertFalse(self._is_safe_url("http://10.255.255.255:5000"))

    def test_blocks_172_16_31_private(self):
        self.assertFalse(self._is_safe_url("http://172.16.0.1:8080"))
        self.assertFalse(self._is_safe_url("http://172.31.255.255:8080"))

    def test_blocks_192_168_private(self):
        self.assertFalse(self._is_safe_url("http://192.168.1.1:5432"))
        self.assertFalse(self._is_safe_url("http://192.168.0.100:80"))

    def test_blocks_ipv6_localhost(self):
        self.assertFalse(self._is_safe_url("http://[::1]:8000"))

    def test_blocks_zero_ip(self):
        self.assertFalse(self._is_safe_url("http://0.0.0.0:8080"))

    def test_allows_public_urls(self):
        self.assertTrue(self._is_safe_url("https://api.openai.com/v1"))
        self.assertTrue(self._is_safe_url("https://api.anthropic.com"))
        self.assertTrue(self._is_safe_url("https://generativelanguage.googleapis.com"))
        self.assertTrue(self._is_safe_url("https://api.stability.ai"))

    def test_allows_custom_public(self):
        self.assertTrue(self._is_safe_url("https://my-custom-llm.example.com:443"))
        self.assertTrue(self._is_safe_url("https://ai-proxy.company.org/api"))

    def test_raw_hostname_no_scheme(self):
        self.assertFalse(self._is_safe_url("localhost"))
        self.assertTrue(self._is_safe_url("api.openai.com"))


# ═══════════════════════════════════════════════════════════════
# Test: KeyService — Envelope Encryption
# ═══════════════════════════════════════════════════════════════

class TestKeyService(unittest.TestCase):
    """Verify envelope encryption: KEK → DEK → API Key round-trip."""

    @classmethod
    def setUpClass(cls):
        # Set a test Master KEK before importing KeyService
        os.environ["MASTER_KEK"] = "test-master-kek-for-unit-tests-32bytes!"
        os.environ["INTERNAL_API_TOKEN"] = "test-internal-token"

        from app.services.key_service import KeyService
        cls.KeyService = KeyService

    def setUp(self):
        self.ks = self.KeyService()

    def test_generate_dek_returns_32_bytes(self):
        dek = self.ks.generate_user_dek()
        self.assertEqual(len(dek), 32)

    def test_generate_dek_is_random(self):
        dek1 = self.ks.generate_user_dek()
        dek2 = self.ks.generate_user_dek()
        self.assertNotEqual(dek1, dek2)

    def test_wrap_then_unwrap_dek(self):
        dek = self.ks.generate_user_dek()
        wrapped = self.ks.wrap_user_dek(dek)
        self.assertIsInstance(wrapped, str)
        unwrapped = self.ks.unwrap_user_dek(wrapped)
        self.assertEqual(dek, unwrapped)

    def test_unwrap_tampered_dek_fails(self):
        dek = self.ks.generate_user_dek()
        wrapped = self.ks.wrap_user_dek(dek)
        # Tamper with the wrapped data
        tampered = wrapped[:-4] + "AAAA" if len(wrapped) > 8 else wrapped
        with self.assertRaises(Exception):
            self.ks.unwrap_user_dek(tampered)

    def test_unwrap_invalid_base64_fails(self):
        with self.assertRaises(Exception):
            self.ks.unwrap_user_dek("not-valid-base64!!!")

    def test_encrypt_then_decrypt_api_key(self):
        dek = self.ks.generate_user_dek()
        plaintext = "sk-test-api-key-1234567890abcdef"
        encrypted = self.ks.encrypt_api_key(dek, plaintext)
        self.assertIsInstance(encrypted, str)
        self.assertNotIn(plaintext, encrypted)
        decrypted = self.ks.decrypt_api_key(dek, encrypted)
        self.assertEqual(plaintext, decrypted)

    def test_decrypt_with_wrong_dek_fails(self):
        dek1 = self.ks.generate_user_dek()
        dek2 = self.ks.generate_user_dek()
        encrypted = self.ks.encrypt_api_key(dek1, "my-secret-key")
        with self.assertRaises(Exception):
            self.ks.decrypt_api_key(dek2, encrypted)

    def test_full_round_trip(self):
        """Simulate the full Laravel→FastAPI→Laravel flow."""
        original_key = "sk-proj-abc123xyz"

        # Laravel stores: wrapped_dek (in user table) + encrypted_key (in model_config)
        dek = self.ks.generate_user_dek()
        wrapped_dek = self.ks.wrap_user_dek(dek)
        encrypted_key = self.ks.encrypt_key_for_user(wrapped_dek, original_key)

        # FastAPI decrypts:
        decrypted = self.ks.decrypt_key_for_user(wrapped_dek, encrypted_key)
        self.assertEqual(original_key, decrypted)

    def test_encrypt_then_decrypt_unicode_key(self):
        """API keys can contain Unicode characters in edge cases."""
        dek = self.ks.generate_user_dek()
        unicode_key = "sk-测试-日本語-한국어-🎬"
        encrypted = self.ks.encrypt_api_key(dek, unicode_key)
        decrypted = self.ks.decrypt_api_key(dek, encrypted)
        self.assertEqual(unicode_key, decrypted)

    def test_empty_key_round_trip(self):
        dek = self.ks.generate_user_dek()
        encrypted = self.ks.encrypt_api_key(dek, "")
        decrypted = self.ks.decrypt_api_key(dek, encrypted)
        self.assertEqual("", decrypted)

    def test_long_key_round_trip(self):
        dek = self.ks.generate_user_dek()
        long_key = "sk-" + ("x" * 500)
        encrypted = self.ks.encrypt_api_key(dek, long_key)
        decrypted = self.ks.decrypt_api_key(dek, encrypted)
        self.assertEqual(long_key, decrypted)


# ═══════════════════════════════════════════════════════════════
# Test: Pydantic Schemas — Input Validation
# ═══════════════════════════════════════════════════════════════

class TestSchemas(unittest.TestCase):
    """Verify Pydantic request validation and SSRF guards on schemas."""

    @classmethod
    def setUpClass(cls):
        os.environ.setdefault("MASTER_KEK", "test-kek")
        os.environ.setdefault("INTERNAL_API_TOKEN", "test-token")

    def test_stage_run_rejects_localhost_base_url(self):
        from app.routers.internal import StageRunRequest
        with self.assertRaises(Exception):
            StageRunRequest(
                user_id=1,
                stage="script_analysis",
                wrapped_dek="dGVzdA==",
                model_config={
                    "base_url": "http://127.0.0.1:8000",
                    "api_type": "openai",
                    "encrypted_key": "test",
                },
            )

    def test_stage_run_rejects_192_168_base_url(self):
        from app.routers.internal import StageRunRequest
        with self.assertRaises(Exception):
            StageRunRequest(
                user_id=1,
                stage="script_analysis",
                wrapped_dek="dGVzdA==",
                model_config={
                    "base_url": "http://192.168.1.100:8080",
                    "api_type": "custom",
                    "encrypted_key": "test",
                },
            )

    def test_stage_run_allows_public_base_url(self):
        from app.routers.internal import StageRunRequest
        req = StageRunRequest(
            user_id=1,
            stage="script_analysis",
            wrapped_dek="dGVzdA==",
            model_config={
                "base_url": "https://api.openai.com/v1",
                "api_type": "openai",
                "encrypted_key": "test",
            },
        )
        self.assertEqual(req.user_id, 1)
        self.assertEqual(req.stage, "script_analysis")

    def test_stage_run_no_base_url_is_ok(self):
        from app.routers.internal import StageRunRequest
        req = StageRunRequest(
            user_id=2,
            stage="image_gen",
            wrapped_dek="dGVzdDI=",
            model_config={
                "api_type": "stability",
                "encrypted_key": "test",
            },
        )
        self.assertEqual(req.user_id, 2)

    def test_key_verify_rejects_localhost(self):
        from app.routers.internal import KeyVerifyRequest
        with self.assertRaises(Exception):
            KeyVerifyRequest(
                wrapped_dek="dGVzdA==",
                api_type="openai",
                base_url="http://localhost:8000",
                encrypted_key="test",
            )

    def test_key_verify_allows_public(self):
        from app.routers.internal import KeyVerifyRequest
        req = KeyVerifyRequest(
            wrapped_dek="dGVzdA==",
            api_type="openai",
            base_url="https://api.openai.com",
            encrypted_key="test",
        )
        self.assertEqual(req.api_type, "openai")


# ═══════════════════════════════════════════════════════════════
# Test: Config / Settings
# ═══════════════════════════════════════════════════════════════

class TestConfig(unittest.TestCase):
    """Verify configuration loads and has expected structure."""

    def test_settings_loads_without_error(self):
        from app.config import Settings
        s = Settings()
        self.assertIsInstance(s.DB_HOST, str)
        self.assertIsInstance(s.DB_PORT, int)
        self.assertIsInstance(s.DB_DATABASE, str)
        self.assertTrue(len(s.DB_HOST) > 0)
        self.assertTrue(s.DB_PORT > 0)

    def test_db_url_format(self):
        from app.config import Settings
        s = Settings()
        url = s.db_url
        self.assertIn("mysql+aiomysql://", url)
        self.assertIn(s.DB_DATABASE, url)

    def test_master_kek_configured(self):
        from app.config import Settings
        s = Settings()
        self.assertTrue(len(s.MASTER_KEK) > 0, "MASTER_KEK must be configured")

    def test_internal_token_configured(self):
        from app.config import Settings
        s = Settings()
        self.assertTrue(len(s.INTERNAL_API_TOKEN) > 0, "INTERNAL_API_TOKEN must be configured")


# ═══════════════════════════════════════════════════════════════
# Test: Internal Auth
# ═══════════════════════════════════════════════════════════════

class TestInternalAuth(unittest.TestCase):
    """Verify internal API token authentication."""

    @classmethod
    def setUpClass(cls):
        from app.config import get_settings
        cls._token = get_settings().INTERNAL_API_TOKEN

    def test_correct_token_passes(self):
        import asyncio
        async def _test():
            from app.routers.internal import verify_internal_token
            result = await verify_internal_token(x_internal_token=self._token)
            self.assertTrue(result)

        asyncio.run(_test())

    def test_wrong_token_raises_403(self):
        import asyncio
        async def _test():
            from app.routers.internal import verify_internal_token
            from fastapi import HTTPException
            with self.assertRaises(HTTPException) as ctx:
                await verify_internal_token(x_internal_token="wrong-token")
            self.assertEqual(ctx.exception.status_code, 403)

        asyncio.run(_test())

    def test_empty_token_raises_403(self):
        import asyncio
        async def _test():
            from app.routers.internal import verify_internal_token
            from fastapi import HTTPException
            with self.assertRaises(HTTPException) as ctx:
                await verify_internal_token(x_internal_token="")
            self.assertEqual(ctx.exception.status_code, 403)

        asyncio.run(_test())


# ═══════════════════════════════════════════════════════════════
# Runner
# ═══════════════════════════════════════════════════════════════

if __name__ == "__main__":
    print("=" * 65)
    print("  AIStory FastAPI Test Suite")
    print("=" * 65)
    print()

    # Discover and run all tests
    unittest.main(verbosity=2)
