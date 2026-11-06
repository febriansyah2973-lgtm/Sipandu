import os
import sys
import uuid
import tempfile
from flask import Flask, request, jsonify
from flask_cors import CORS

# Windows UTF-8 console output fix
try:
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8')
    if hasattr(sys.stderr, 'reconfigure'):
        sys.stderr.reconfigure(encoding='utf-8')
except Exception:
    pass

os.environ['TF_ENABLE_ONEDNN_OPTS'] = '0'
os.environ['PYTHONIOENCODING'] = 'utf-8'
os.environ['PYTHONUTF8'] = '1'

from deepface import DeepFace

app = Flask(__name__)
CORS(app)

MODEL_NAME = "VGG-Face"
DISTANCE_METRIC = "cosine"
DETECTOR_BACKEND = "mtcnn"

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'status': 'ok',
        'service': 'SIPANDU AI Face Recognition',
        'model': MODEL_NAME,
        'metric': DISTANCE_METRIC,
        'detector': DETECTOR_BACKEND
    }), 200

@app.route('/verify-face', methods=['POST'])
def fn_verify_face():
    if 'foto_profil' not in request.files or 'foto_selfie' not in request.files:
        return jsonify({'status': 'error', 'message': 'File gambar profil atau selfie tidak lengkap'}), 400

    file_profil = request.files['foto_profil']
    file_selfie = request.files['foto_selfie']

    temp_dir = tempfile.gettempdir()
    unique_id = uuid.uuid4().hex
    path_profil = os.path.join(temp_dir, f"sipandu_ref_{unique_id}.jpg")
    path_selfie = os.path.join(temp_dir, f"sipandu_selfie_{unique_id}.jpg")

    try:
        file_profil.save(path_profil)
        file_selfie.save(path_selfie)

        # Proses Pencocokan Wajah dengan DeepFace menggunakan MTCNN
        try:
            result = DeepFace.verify(
                img1_path=path_profil,
                img2_path=path_selfie,
                model_name=MODEL_NAME,
                distance_metric=DISTANCE_METRIC,
                detector_backend=DETECTOR_BACKEND,
                enforce_detection=False,
                align=True
            )
        except Exception:
            # Fallback tanpa ekstraksi detektor jika detektor mengalami kendala
            result = DeepFace.verify(
                img1_path=path_profil,
                img2_path=path_selfie,
                model_name=MODEL_NAME,
                distance_metric=DISTANCE_METRIC,
                detector_backend="skip",
                enforce_detection=False,
                align=False
            )

        distance = float(result.get("distance", 1.0))
        # Ambang batas ketat (strict threshold) untuk VGG-Face Cosine Distance
        threshold = 0.40
        verified = bool(distance <= threshold)

        # Hitung skor kemiripan (similarity percentage)
        if verified:
            # Jarak 0 -> 100%, jarak 0.40 -> 75%
            similarity = round(100.0 - (distance / threshold) * 25.0, 1)
            similarity = min(99.9, max(75.0, similarity))
            is_matched = True
            msg = f"Wajah Cocok! Tingkat Kemiripan: {similarity}%"
        else:
            # Jarak > 0.40
            similarity = max(0.0, round(70.0 - ((distance - threshold) / (1.0 - threshold)) * 70.0, 1))
            is_matched = False
            msg = f"Wajah Tidak Sesuai Foto Profil! Tingkat Kemiripan: {similarity}%"

        return jsonify({
            'status': 'success',
            'matched': is_matched,
            'verified': verified,
            'similarity': similarity,
            'distance': round(distance, 4),
            'threshold': round(threshold, 4),
            'message': msg
        }), 200

    except Exception as e:
        return jsonify({
            'status': 'error',
            'matched': False,
            'similarity': 0,
            'message': f"Gagal memproses pengenalan wajah: {str(e)}"
        }), 500

    finally:
        for p in [path_profil, path_selfie]:
            try:
                if os.path.exists(p):
                    os.remove(p)
            except Exception:
                pass

if __name__ == '__main__':
    print("🚀 SIPANDU AI Face Recognition Service starting on http://127.0.0.1:5000 ...")
    app.run(host='127.0.0.1', port=5000, debug=False, threaded=True)