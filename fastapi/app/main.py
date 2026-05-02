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

from app.routers.internal import router as internal_router


@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup
    import asyncio

    from loguru import logger

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
