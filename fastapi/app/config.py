"""Application configuration from environment variables."""

import os
from functools import lru_cache

from dotenv import load_dotenv

# Load .env file from the project root
load_dotenv()


class Settings:
    # Database
    DB_HOST: str = os.getenv("DB_HOST", "127.0.0.1")
    DB_PORT: int = int(os.getenv("DB_PORT", "3306"))
    DB_DATABASE: str = os.getenv("DB_DATABASE", "aistory")
    DB_USERNAME: str = os.getenv("DB_USERNAME", "root")
    DB_PASSWORD: str = os.getenv("DB_PASSWORD", "")

    # Redis
    REDIS_HOST: str = os.getenv("REDIS_HOST", "127.0.0.1")
    REDIS_PORT: int = int(os.getenv("REDIS_PORT", "6379"))

    # Master KEK — will be SHA256-hashed to 32 bytes by KeyService
    MASTER_KEK: str = os.getenv(
        "MASTER_KEK",
        "change-me-in-production-use-32-byte!!"
    )

    # Internal API auth
    INTERNAL_API_TOKEN: str = os.getenv("INTERNAL_API_TOKEN", "internal-secret-token")

    # Default timeouts
    AI_REQUEST_TIMEOUT: int = int(os.getenv("AI_REQUEST_TIMEOUT", "120"))
    AI_POLL_TIMEOUT: int = int(os.getenv("AI_POLL_TIMEOUT", "600"))

    @property
    def db_url(self) -> str:
        return (
            f"mysql+aiomysql://{self.DB_USERNAME}:{self.DB_PASSWORD}"
            f"@{self.DB_HOST}:{self.DB_PORT}/{self.DB_DATABASE}"
        )


@lru_cache()
def get_settings() -> Settings:
    return Settings()
