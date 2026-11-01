"""
SWMS Machine Learning - Waste Prediction REST API
Framework: Flask WSGI Microservice
Endpoint: POST /predict (Multipart image upload)
"""

import os
import pickle
import tempfile
import numpy as np
from flask import Flask, request, jsonify
from flask_cors import CORS
from classifier import SoftmaxClassifier
from train import extract_features

app = Flask(__name__)
CORS(app)

CLASS_NAMES = ['organic', 'plastic', 'paper']
RECOMMENDATIONS = {
    'organic': {
        'type': 'Kompos / Biopori / Pakan Maggot',
        'action': 'Pisahkan dari sampah anorganik. Olah menjadi pupuk kompos organik atau pakan maggot BSF.',
        'bin': 'Tempat Sampah Hijau (Organik)'
    },
    'plastic': {
        'type': 'Daur Ulang Plastik / Bank Sampah',
        'action': 'Bilas bersih dari sisa cairan atau minyak, kemas rapi, dan salurkan ke Bank Sampah terdekat.',
        'bin': 'Tempat Sampah Kuning (Anorganik / Plastik)'
    },
    'paper': {
        'type': 'Daur Ulang Kertas / Pabrik Pulp',
        'action': 'Pastikan tetap kering dan tidak terkena minyak. Lipat rapi untuk didaur ulang.',
        'bin': 'Tempat Sampah Biru (Kertas)'
    }
}

MODEL_PATH = os.path.join(os.path.dirname(__file__), 'model.pkl')
model = None

def get_model():
    """Load and cache the trained model instance."""
    global model
    if model is None and os.path.exists(MODEL_PATH):
        try:
            with open(MODEL_PATH, 'rb') as f:
                model = pickle.load(f)
        except Exception as err:
            app.logger.error(f"Gagal memuat model: {err}")
    return model

# Eagerly load model on startup
get_model()

@app.route('/', methods=['GET'])
def health():
    current_model = get_model()
    return jsonify({
        'status': 'online',
        'service': 'SWMS Waste Classification API',
        'classes': CLASS_NAMES,
        'model_loaded': current_model is not None
    })

@app.route('/predict', methods=['POST'])
def predict():
    upload = request.files.get('file') or request.files.get('image')
    if not upload or upload.filename == '':
        return jsonify({
            'success': False,
            'message': 'Berkas gambar wajib diunggah (gunakan field "file" atau "image").'
        }), 400

    # Thread-safe temporary file handling
    suffix = os.path.splitext(upload.filename)[1] or '.jpg'
    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        temp_path = tmp.name
        upload.save(temp_path)

    try:
        features = extract_features(temp_path)
        features = features.reshape(1, -1)

        loaded_model = get_model()
        if loaded_model is not None:
            probs = loaded_model.predict_proba(features)[0]
        else:
            probs = np.array([0.33, 0.33, 0.34])

        top_idx = int(np.argmax(probs))
        top_category = CLASS_NAMES[top_idx]
        top_confidence = round(float(probs[top_idx]) * 100, 2)

        breakdown = {
            CLASS_NAMES[i]: round(float(probs[i]) * 100, 2)
            for i in range(len(CLASS_NAMES))
        }

        return jsonify({
            'success': True,
            'category': top_category,
            'confidence': top_confidence,
            'breakdown': breakdown,
            'recommendation': RECOMMENDATIONS.get(top_category, {})
        })

    except Exception as err:
        return jsonify({
            'success': False,
            'message': f'Gagal memproses gambar: {str(err)}'
        }), 500

    finally:
        if os.path.exists(temp_path):
            try:
                os.remove(temp_path)
            except OSError:
                pass

if __name__ == '__main__':
    print("Menjalankan SWMS AI Microservice pada http://127.0.0.1:8001 ...")
    app.run(host='127.0.0.1', port=8001, debug=False)
