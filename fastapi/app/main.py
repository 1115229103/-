"""AIStory FastAPI — AI Orchestration Gateway.

Handles:
- Key decryption (envelope encryption: Master KEK → User DEK → API Key)
- Protocol adaptation (11 API types)
- AI API calling with retry
- Async task polling (Kling, Replicate, etc.)

Laravel calls internal endpoints with user_id + stage params.
FastAPI never exposes plaintext keys to Laravel.
"""

from contextlib import asynccontextmanager

from fastapi import FastAPI
from loguru import logger

from app.routers.internal import router as internal_router


@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup — validate critical secrets
    from app.config import get_settings

    settings = get_settings()

    if not settings.MASTER_KEK:
        logger.error("FATAL: MASTER_KEK env var is not set or is empty. Refusing to start.")
        raise RuntimeError("MASTER_KEK must be set to a secure random string (32+ chars)")

    if not settings.INTERNAL_API_TOKEN:
        logger.error("FATAL: INTERNAL_API_TOKEN env var is not set or is empty. Refusing to start.")
        raise RuntimeError("INTERNAL_API_TOKEN must be set to a secure random string")

    weak_defaults = [
        "change-me", "internal-secret-token", "change-me-in-production",
    ]
    if any(w in settings.MASTER_KEK.lower() for w in weak_defaults):
        logger.error("FATAL: MASTER_KEK matches a known-weak pattern. Refusing to start.")
        raise RuntimeError("MASTER_KEK must not use the example default value")

    logger.info("AIStory FastAPI Gateway starting...")
    yield
    # Shutdown
    logger.info("AIStory FastAPI Gateway shutting down...")


app = FastAPI(
    title="AIStory AI Gateway",
    version="0.1.0",
    lifespan=lifespan,
)

app.include_router(internal_router)


@app.get("/")
async def root():
    return {"service": "AIStory AI Gateway", "version": "0.1.0"}


@app.get("/health")
async def health():
    """Infrastructure health check for load balancers and monitoring."""
    from app.config import get_settings

    settings = get_settings()

    db_ok = False
    redis_ok = False

    try:
        import socket
        s = socket.socket()
        s.settimeout(3)
        s.connect((settings.DB_HOST, settings.DB_PORT))
        s.close()
        db_ok = True
    except Exception:
        pass

    try:
        import socket
        s = socket.socket()
        s.settimeout(3)
        s.connect((settings.REDIS_HOST, settings.REDIS_PORT))
        s.close()
        redis_ok = True
    except Exception:
        pass

    status_code = 200 if db_ok and redis_ok else 503
    return {
        "status": "ok" if status_code == 200 else "degraded",
        "service": "AIStory AI Gateway",
        "version": "0.1.0",
        "checks": {
            "database": "ok" if db_ok else "fail",
            "redis": "ok" if redis_ok else "fail",
        },
    }
