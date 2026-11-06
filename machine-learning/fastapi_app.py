"""FastAPI prediction service for the trained SWMS image classifier."""

from __future__ import annotations

from contextlib import asynccontextmanager
from io import BytesIO
from pathlib import Path
from typing import Any, Literal

import torch
from fastapi import FastAPI, File, HTTPException, Request, UploadFile
from PIL import Image, UnidentifiedImageError
from pydantic import BaseModel

from classifier import MIN_CONFIDENCE, load_model

ROOT = Path(__file__).resolve().parent
MODEL_PATH = ROOT / "artifacts" / "model.pt"
MAX_IMAGE_BYTES = 10 * 1024 * 1024
MAX_IMAGE_PIXELS = 25_000_000
DEVICE = torch.device("cuda" if torch.cuda.is_available() else "cpu")

RECOMMENDATIONS = {
    "organic": {
        "type": "Kompos / Biopori / Pakan Maggot",
        "action": "Pisahkan dari sampah anorganik. Olah menjadi pupuk kompos organik atau pakan maggot BSF.",
        "bin": "Tempat Sampah Hijau (Organik)",
    },
    "plastic": {
        "type": "Daur Ulang Plastik / Bank Sampah",
        "action": "Bilas bersih dari sisa cairan atau minyak, kemas rapi, dan salurkan ke Bank Sampah terdekat.",
        "bin": "Tempat Sampah Kuning (Anorganik / Plastik)",
    },
    "paper": {
        "type": "Daur Ulang Kertas / Pabrik Pulp",
        "action": "Pastikan tetap kering dan tidak terkena minyak. Lipat rapi untuk didaur ulang.",
        "bin": "Tempat Sampah Biru (Kertas)",
    },
    "other": {
        "type": "Pemisahan Sampah / TPS3R",
        "action": "Tumpukan terdeteksi berisi campuran berbagai jenis sampah. Pisahkan sampah organik dari plastik dan kertas sebelum disalurkan ke TPS3R.",
        "bin": "Tempat Sampah Terpilah / Residu",
    },
}


class PredictionResponse(BaseModel):
    success: bool
    status: Literal["classified", "uncertain"]
    message: str
    category: str | None
    confidence: float
    breakdown: dict[str, float]
    recommendation: dict[str, str] | None


@asynccontextmanager
async def lifespan(application: FastAPI):
    application.state.predictor = load_model(MODEL_PATH, DEVICE)
    yield


app = FastAPI(
    title="SWMS Waste Classification API",
    description="Classifies organic, plastic, and paper waste photos.",
    version="2.0.0",
    lifespan=lifespan,
)


def _predict(image: Image.Image, predictor: tuple[Any, list[str], Any]) -> dict[str, Any]:
    model, classes, image_transform = predictor
    width, height = image.size

    # Spatial patches to detect presence of multiple waste types in different regions of the pile
    boxes = [
        (0, 0, max(1, int(width * 0.7)), max(1, int(height * 0.7))),
        (int(width * 0.3), 0, width, max(1, int(height * 0.7))),
        (0, int(height * 0.3), max(1, int(width * 0.7)), height),
        (int(width * 0.3), int(height * 0.3), width, height),
        (int(width * 0.15), int(height * 0.15), max(1, int(width * 0.85)), max(1, int(height * 0.85))),
    ]
    patch_tensors = torch.stack(
        [
            image_transform(image.crop(box).resize((224, 224), Image.Resampling.BILINEAR))
            for box in boxes
        ]
    ).to(DEVICE)
    global_tensor = image_transform(image).unsqueeze(0).to(DEVICE)

    with torch.inference_mode():
        original_probabilities = torch.softmax(model(global_tensor), dim=1)
        flipped_probabilities = torch.softmax(model(global_tensor.flip([-1])), dim=1)
        probabilities = (
            (original_probabilities + flipped_probabilities) / 2
        )[0].cpu()
        patch_probabilities = torch.softmax(model(patch_tensors), dim=1).cpu()

    # Detect categories recognized with significant confidence in sub-regions
    detected_classes: dict[str, float] = {}
    for patch_prob in patch_probabilities:
        top_patch_idx = int(patch_prob.argmax().item())
        patch_conf = float(patch_prob[top_patch_idx].item())
        if patch_conf >= 0.50:
            category_name = classes[top_patch_idx]
            detected_classes[category_name] = max(
                detected_classes.get(category_name, 0.0), patch_conf
            )

    sorted_probabilities, _ = torch.sort(probabilities, descending=True)
    top_index = int(probabilities.argmax().item())
    confidence = float(probabilities[top_index].item())
    view_predictions_agree = int(original_probabilities.argmax().item()) == int(
        flipped_probabilities.argmax().item()
    )

    # Determine if this image contains mixed waste (tumpukan sampah campuran)
    is_mixed = False
    mixed_confidence = 0.0
    if len(detected_classes) >= 2:
        is_mixed = True
        mixed_confidence = round(
            float(sum(detected_classes.values()) / len(detected_classes) * 100), 2
        )
    elif (
        confidence < MIN_CONFIDENCE
        and sorted_probabilities[1].item() >= 0.25
        and (sorted_probabilities[0].item() + sorted_probabilities[1].item()) >= 0.70
        and (sorted_probabilities[0].item() - sorted_probabilities[1].item()) <= 0.30
        and len(detected_classes) >= 1
    ):
        is_mixed = True
        mixed_confidence = round(
            float((sorted_probabilities[0].item() + sorted_probabilities[1].item()) * 100),
            2,
        )

    if is_mixed:
        category = "other"
        classified = True
        final_confidence = mixed_confidence
        message = "Prediksi tersedia: Terdeteksi tumpukan sampah campuran."
    elif confidence >= MIN_CONFIDENCE and view_predictions_agree:
        category = classes[top_index]
        classified = True
        final_confidence = round(confidence * 100, 2)
        message = "Prediksi tersedia."
    else:
        category = None
        classified = False
        final_confidence = round(confidence * 100, 2)
        message = "AI belum cukup yakin. Pilih kategori secara manual dan gunakan foto yang lebih jelas bila perlu."

    return {
        "success": True,
        "status": "classified" if classified else "uncertain",
        "message": message,
        "category": category,
        "confidence": final_confidence,
        "breakdown": {
            name: round(float(probability.item()) * 100, 2)
            for name, probability in zip(classes, probabilities)
        },
        "recommendation": RECOMMENDATIONS[category] if category else None,
    }


@app.get("/")
@app.get("/health")
def health(request: Request) -> dict[str, Any]:
    predictor = getattr(request.app.state, "predictor", None)
    if predictor is None:
        raise HTTPException(status_code=503, detail="Model is not loaded.")
    return {
        "status": "online",
        "service": "SWMS Waste Classification API",
        "classes": predictor[1],
        "model_loaded": True,
    }


@app.post("/predict", response_model=PredictionResponse)
async def predict(
    request: Request,
    file: UploadFile = File(...),
) -> PredictionResponse:
    image_bytes = await file.read(MAX_IMAGE_BYTES + 1)
    if not image_bytes:
        raise HTTPException(status_code=400, detail="Image upload is empty.")
    if len(image_bytes) > MAX_IMAGE_BYTES:
        raise HTTPException(status_code=413, detail="Image must be 10 MB or smaller.")

    try:
        with Image.open(BytesIO(image_bytes)) as source:
            if source.width * source.height > MAX_IMAGE_PIXELS:
                raise HTTPException(
                    status_code=413,
                    detail="Image dimensions must not exceed 25 megapixels.",
                )
            source.verify()
        with Image.open(BytesIO(image_bytes)) as source:
            image = source.convert("RGB")
    except (
        UnidentifiedImageError,
        OSError,
        ValueError,
        Image.DecompressionBombError,
    ) as error:
        raise HTTPException(
            status_code=422, detail="Upload must be a valid image."
        ) from error

    predictor = getattr(request.app.state, "predictor", None)
    if predictor is None:
        raise HTTPException(status_code=503, detail="Model is not loaded.")
    return PredictionResponse(**_predict(image, predictor))
