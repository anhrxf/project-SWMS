"""
SWMS Machine Learning - FastAPI Prediction Microservice
Framework: FastAPI
Endpoint: POST /predict (UploadFile)
"""

import os
import pickle
import tempfile
import numpy as np
from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from classifier import SoftmaxClassifier
from train import extract_features

app = FastAPI(
    title="SWMS Waste Classification API",
    description="REST API for predicting waste category (Organic, Plastic, Paper) with confidence score",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

CLASS_NAMES = ['organic', 'plastic', 'paper']
RECOMMENDATIONS = {
    'organic': {
        'type': 'Kompos / Biopori / Pakan Maggot',
        'action': 'Pisahkan dari sampah anorganik. Olah menjadi pupuk kompos organik atau pakan maggot BSF.',
        'bin': 'Tempat Sampah Hijau (Organik)'
    },
    'plastic': {
        'type': 'Daur Ulang Plastik / Bank Sampah',
        'action': 'Bilas bersih dari sisa makanan/minuman, pipihkan untuk hemat ruang, lalu setorkan ke Bank Sampah.',
        'bin': 'Tempat Sampah Kuning (Anorganik / Plastik)'
    },
    'paper': {
        'type': 'Daur Ulang Kertas / Pabrik Pulp',
        'action': 'Jaga agar tetap kering dan bebas minyak, ikat rapi untuk didaur ulang menjadi kertas daur ulang.',
        'bin': 'Tempat Sampah Biru (Kertas)'
    }
}

MODEL_PATH = os.path.join(os.path.dirname(__file__), 'model.pkl')
model = None

@app.on_event("startup")
def load_model_on_startup():
    global model
    if os.path.exists(MODEL_PATH):
        with open(MODEL_PATH, 'rb') as f:
            model = pickle.load(f)
        print(f"[+] Loaded model from {MODEL_PATH}")

@app.get("/")
def health_check():
    return {
        "status": "online",
        "service": "SWMS FastAPI Waste Image Classifier",
        "version": "1.0.0",
        "classes": CLASS_NAMES,
        "model_loaded": model is not None
    }

@app.post("/predict")
async def predict_waste(file: UploadFile = File(...)):
    suffix = os.path.splitext(file.filename or "")[1] or ".jpg"
    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        temp_path = tmp.name

    try:
        contents = await file.read()
        with open(temp_path, "wb") as f:
            f.write(contents)

        features = extract_features(temp_path)
        features = features.reshape(1, -1)

        if model is not None:
            probs = model.predict_proba(features)[0]
        else:
            probs = np.array([0.33, 0.33, 0.34])

        top_idx = int(np.argmax(probs))
        top_cat = CLASS_NAMES[top_idx]
        top_conf = round(float(probs[top_idx]) * 100, 2)

        breakdown = {
            CLASS_NAMES[i]: round(float(probs[i]) * 100, 2) for i in range(len(CLASS_NAMES))
        }

        return {
            "success": True,
            "category": top_cat,
            "confidence": top_conf,
            "breakdown": breakdown,
            "recommendation": RECOMMENDATIONS.get(top_cat, {})
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        if os.path.exists(temp_path):
            try:
                os.remove(temp_path)
            except OSError:
                pass
