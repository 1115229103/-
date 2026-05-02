"""Internal API endpoints called by the Laravel backend.

All endpoints are authenticated via a shared internal token.
Laravel passes user_id + stage + prompt, FastAPI handles key
decryption and AI API calling. Laravel never sees plaintext keys.
"""

from typing import Any

from fastapi import APIRouter, Depends, HTTPException, Header
from pydantic import BaseModel, Field

from app.services.key_service import get_key_service
from app.services.pipeline_service import PipelineService

router = APIRouter(prefix="/internal", tags=["internal"])


# ─── Request/Response Schemas ──────────────────────────────────

class StageRunRequest(BaseModel):
    user_id: int
    stage: str
    wrapped_dek: str
    model_cfg: dict[str, Any] = Field(validation_alias="model_config")
    stage_input: dict[str, Any] = {}


class KeyVerifyRequest(BaseModel):
    wrapped_dek: str
    api_type: str
    base_url: str
    encrypted_key: str
    access_key_id: str | None = None
    region: str | None = None


class GenerateDEKRequest(BaseModel):
    user_id: int


class DEKResponse(BaseModel):
    wrapped_dek: str


class EncryptKeyRequest(BaseModel):
    wrapped_dek: str
    api_key: str


class EncryptKeyResponse(BaseModel):
    encrypted_key: str


# ─── Auth Dependency ───────────────────────────────────────────

async def verify_internal_token(
    x_internal_token: str = Header(..., alias="X-Internal-Token"),
):
    from app.config import get_settings

    settings = get_settings()
    if x_internal_token != settings.INTERNAL_API_TOKEN:
        raise HTTPException(status_code=403, detail="Invalid internal token")
    return True


# ─── Endpoints ─────────────────────────────────────────────────

@router.post("/run-stage")
async def run_pipeline_stage(
    req: StageRunRequest,
    _auth: bool = Depends(verify_internal_token),
):
    """Execute a single pipeline stage. Called by Laravel for each stage."""
    svc = PipelineService()
    result = await svc.run_stage(
        stage=req.stage,
        user_id=req.user_id,
        wrapped_dek=req.wrapped_dek,
        model_config=req.model_cfg,
        stage_input=req.stage_input,
    )
    return result


@router.post("/verify-key")
async def verify_api_key(
    req: KeyVerifyRequest,
    _auth: bool = Depends(verify_internal_token),
):
    """Verify a user's API key for a given model."""
    svc = PipelineService()
    kwargs = {}
    if req.access_key_id:
        kwargs["access_key_id"] = req.access_key_id
    if req.region:
        kwargs["region"] = req.region

    valid = await svc.verify_user_key(
        api_type=req.api_type,
        base_url=req.base_url,
        encrypted_key=req.encrypted_key,
        wrapped_dek=req.wrapped_dek,
        **kwargs,
    )
    return {"valid": valid}


@router.post("/generate-dek")
async def generate_user_dek(
    _auth: bool = Depends(verify_internal_token),
):
    """Generate a new User DEK and return it wrapped with Master KEK.
    Called during user registration (by Laravel).
    """
    ks = get_key_service()
    dek = ks.generate_user_dek()
    wrapped = ks.wrap_user_dek(dek)
    return DEKResponse(wrapped_dek=wrapped)


@router.post("/encrypt-key")
async def encrypt_api_key(
    req: EncryptKeyRequest,
    _auth: bool = Depends(verify_internal_token),
):
    """Encrypt a user's API key with their DEK.
    Called when a user saves a new model config (by Laravel).
    """
    ks = get_key_service()
    encrypted = ks.encrypt_key_for_user(req.wrapped_dek, req.api_key)
    return EncryptKeyResponse(encrypted_key=encrypted)


@router.get("/health")
async def health_check():
    return {"status": "ok", "service": "aistory-fastapi"}
