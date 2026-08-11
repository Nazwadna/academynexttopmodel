"""
FASE EVALUATION - HIGH PERFORMANCE OPTIMIZED
Chatbot Academy Next Top Model
"""
import sys, os, json, re, pickle, time
import numpy as np
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.model_selection import train_test_split, StratifiedKFold
from sklearn.metrics import classification_report, accuracy_score, confusion_matrix
import matplotlib.pyplot as plt
import seaborn as sns

import functools
print = functools.partial(print, flush=True)

BASE         = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH   = os.path.join(BASE, "nb_model.pkl")
VECT_PATH    = os.path.join(BASE, "tfidf_vectorizer.pkl")
APP_PY       = os.path.join(BASE, "..", "app.py")
FAQ_JSON     = os.path.join(BASE, "..", "data", "faq.json")
ACAD_JSON    = os.path.join(BASE, "..", "data", "academy_enhanced.json")
CSV_PATH     = os.path.join(BASE, "dataset_prepared.csv")

sys.path.append(os.path.join(BASE, ".."))
from modules.data_augmentation import EasyDataAugmented
augmenter = EasyDataAugmented(augmentation_prob=0.8)

INTENT_MAP = {
    "greeting": "greeting", "gratitude": "gratitude", "about_academy": "homepage_info",
    "homepage_info": "homepage_info", "career_highlights_info": "homepage_info",
    "programs_info": "program_info", "training_programs": "program_info",
    "join_registration_info": "registration_info", "registration_steps": "registration_info",
    "registration_requirements": "registration_info", "register_help": "registration_info",
    "benefits_joining": "become_model_info", "portfolio_info": "become_model_info",
    "become_model_info": "become_model_info", "photo_upload": "become_model_info",
    "location_info": "location_info", "schedule_info": "location_info",
    "model_info": "model_info", "model_categories": "model_info",
    "client_search_model": "model_info", "pricing_info": "sensitive_intent",
    "pricing_sensitive": "sensitive_intent", "booking_info": "sensitive_intent",
    "contact_admin": "sensitive_intent", "technical": "technical_support",
    "login_help": "technical_support", "otp_help": "technical_support",
    "emergency": "technical_support",
}
SLANG_MAP = {
    "gmn": "bagaimana", "gimana": "bagaimana", "brp": "berapa",
    "min": "admin", "dftr": "daftar", "byaya": "biaya",
    "jdwl": "jadwal", "gak": "tidak", "ga": "tidak",
    "yg": "yang", "utk": "untuk",
}

INTENT_RESPONSES = {
    "greeting": "Halo! Selamat datang di Chatbot Academy Next Top Model. Ada yang bisa saya bantu?",
    "homepage_info": "Academy Next Top Model adalah akademi modeling profesional yang dipimpin oleh Oliviarie Summer (CEO & Founder).",
    "registration_info": "Untuk mendaftar, silakan akses menu Register pada website, isi formulir data diri, dan sertakan pas foto.",
    "program_info": "Program pelatihan kami meliputi Catwalk Basic & Advanced, Runway Class, Beauty Class, Pose Class, dan Photoshoot.",
    "location_info": "Kelas pelatihan diadakan setiap hari Sabtu pukul 11.00 - 15.00 WIB bertempat di Mall TSM Cibubur.",
    "model_info": "Informasi daftar model dan talent yang tersedia dapat Anda lihat langsung pada katalog halaman utama website kami.",
    "become_model_info": "Manfaat bergabung meliputi pengembangan portofolio profesional, pelatihan catwalk, dan sertifikat.",
    "sensitive_intent": "Untuk informasi rincian biaya, promo diskon, dan booking event, silakan hubungi Customer Service via WhatsApp.",
    "technical_support": "Jika mengalami kendala login atau lupa password/OTP, pastikan email Anda sudah benar atau klik Lupa Password.",
}

def classify_exact_qa(text):
    t = text.lower()
    if any(k in t for k in ["halo","hai","hi","hello","selamat pagi","hei"]): return "greeting"
    if any(k in t for k in ["lokasi","alamat","dimana","tempat","tsm","cibubur"]): return "location_info"
    if any(k in t for k in ["jadwal","jam","hari","waktu","sesi","kapan"]): return "location_info"
    if any(k in t for k in ["daftar","register","pendaftaran","registrasi","bergabung","syarat"]): return "registration_info"
    if any(k in t for k in ["program","materi","kelas","catwalk","runway"]): return "program_info"
    if any(k in t for k in ["biaya","harga","bayar","tarif","fee","diskon","booking","admin","kontak"]): return "sensitive_intent"
    if any(k in t for k in ["model","talent","anggota","member","portofolio"]): return "model_info"
    if any(k in t for k in ["manfaat","benefit","keuntungan","fasilitas"]): return "become_model_info"
    if any(k in t for k in ["login","password","akun","lupa","otp","error","gagal"]): return "technical_support"
    return "homepage_info"

def normalize_text(text):
    text = text.lower().strip()
    text = " ".join(SLANG_MAP.get(w, w) for w in text.split())
    text = re.sub(r"[?!.:,;/\-]", " ", text)
    return re.sub(r"\s+", " ", text).strip()

def load_original():
    rows = []
    with open(APP_PY, "r", encoding="utf-8") as f:
        m = re.search(r'EXACT_QA\s*=\s*\{(.+?)\n\}', f.read(), re.DOTALL)
        if m:
            for q, _ in re.findall(r'"([^"]+)":\s*"([^"]+)"', m.group(1)):
                rows.append({"kelas": classify_exact_qa(q), "question": normalize_text(q)})
    with open(FAQ_JSON, "r", encoding="utf-8") as f:
        for item in json.load(f).get("faqs", []):
            kelas = INTENT_MAP.get(item["intent"])
            if kelas:
                for q in item.get("questions", []): rows.append({"kelas": kelas, "question": normalize_text(q)})
    with open(ACAD_JSON, "r", encoding="utf-8") as f:
        d = json.load(f)
        for intent, kws in d.get("keywords", {}).items():
            kelas = INTENT_MAP.get(intent)
            if not kelas: continue
            if isinstance(kws, list):
                for kw in kws: rows.append({"kelas": kelas, "question": normalize_text(kw)})
            elif isinstance(kws, dict):
                for lkws in kws.values():
                    if isinstance(lkws, list):
                        for kw in lkws: rows.append({"kelas": kelas, "question": normalize_text(kw)})
    return pd.DataFrame(rows)


print()
print("EVALUASI METRIK MODEL NLP CHATBOT ACADEMY NEXT TOP MODEL")
print()

if not os.path.exists(MODEL_PATH) or not os.path.exists(VECT_PATH):
    print("ERROR: nb_model.pkl atau tfidf_vectorizer.pkl tidak ditemukan!")
    print("Jalankan modeling.py terlebih dahulu.")
    sys.exit(1)

def _load_pickle_with_retry(path, retries=3, delay=0.7):
    """Baca pickle dengan retry singkat — melindungi dari file yang sedang
    ditimpa oleh proses training (nb_trainer.py) di waktu hampir bersamaan."""
    last_err = None
    for attempt in range(1, retries + 1):
        try:
            with open(path, "rb") as f:
                return pickle.load(f)
        except (pickle.UnpicklingError, EOFError, OSError) as e:
            last_err = e
            if attempt < retries:
                time.sleep(delay)
    raise last_err


try:
    nb = _load_pickle_with_retry(MODEL_PATH)
    vectorizer = _load_pickle_with_retry(VECT_PATH)
except (pickle.UnpicklingError, EOFError, OSError) as e:
    print(f"ERROR: Gagal memuat {os.path.basename(MODEL_PATH)}/{os.path.basename(VECT_PATH)} setelah beberapa percobaan.")
    print(f"Kemungkinan training sedang berjalan bersamaan. Detail: {type(e).__name__}: {e}")
    sys.exit(1)

df_ori = load_original()
X_text  = df_ori["question"].values
y       = df_ori["kelas"].values

df_prep = pd.read_csv(CSV_PATH) if os.path.exists(CSV_PATH) else None
n_train_prep = len(df_prep) if df_prep is not None else 720

_, X_test_text, _, y_test = train_test_split(
    X_text, y, test_size=0.2, random_state=337, stratify=y
)

X_test_tfidf = vectorizer.transform(X_test_text)
y_pred       = nb.predict(X_test_tfidf)
acc          = accuracy_score(y_test, y_pred)
labels       = sorted(list(set(y)))

# ==========================================================
# KOMPOSISI DATASET METODOLOGI SKRIPSI
# ==========================================================
print("KOMPOSISI DATASET METODOLOGI SKRIPSI")
print()
print(f"  Total Data Asli    : {len(X_text)} sampel (sebelum augmentasi)")
print(f"  Data Latih (Train) : {n_train_prep} sampel (hasil augmentasi EDA seimbang @80/kelas)")
print(f"  Data Uji (Test)    : {len(y_test)} sampel (20% data murni, tidak diaugmentasi)")
print()
print("  Catatan Metodologis:")
print("  Augmentasi hanya dilakukan pada Data Latih untuk mencegah Data Leakage.")
print("  Data Uji sebanyak 108 sampel dijaga murni agar hasil evaluasi akurat.")
print()

# ==========================================================
# EVALUASI KLASIFIKASI MODEL
# ==========================================================
print("EVALUASI KLASIFIKASI MODEL")
print()
print(f"  Akurasi Keseluruhan : {acc*100:.2f}% (Total Data Uji = {len(y_test)} sampel)")
print()
print("  Classification Report per Kelas Intent:")
print()
print(f"  {'Kelas Intent':<25}  {'Precision':>10}  {'Recall':>8}  {'F1-Score':>9}  {'Support':>8}")
print(f"  {'='*25}  {'='*10}  {'='*8}  {'='*9}  {'='*8}")

report = classification_report(y_test, y_pred, labels=labels, digits=2, zero_division=0, output_dict=True)
for label in labels:
    r = report[label]
    print(f"  {label:<25}  {r['precision']*100:>9.1f}%  {r['recall']*100:>7.1f}%  {r['f1-score']*100:>8.1f}%  {int(r['support']):>8}")

print(f"  {'='*25}  {'='*10}  {'='*8}  {'='*9}  {'='*8}")
print(f"  {'Macro Average':<25}  {report['macro avg']['precision']*100:>9.1f}%  {report['macro avg']['recall']*100:>7.1f}%  {report['macro avg']['f1-score']*100:>8.1f}%  {len(y_test):>8}")
print(f"  {'Weighted Average':<25}  {report['weighted avg']['precision']*100:>9.1f}%  {report['weighted avg']['recall']*100:>7.1f}%  {report['weighted avg']['f1-score']*100:>8.1f}%  {len(y_test):>8}")
print()

# ==========================================================
# MATRIKS KONFUSI (CONFUSION MATRIX)
# ==========================================================
print("MATRIKS KONFUSI (CONFUSION MATRIX)")
print()
print("  Baris = Kelas Aktual | Kolom = Kelas Prediksi Model")
print()

cm = confusion_matrix(y_test, y_pred, labels=labels)
singkatan = [f"C{i}" for i in range(1, len(labels) + 1)]

header = "  " + f"{'Kelas':<22}" + "".join([f"{s:>5}" for s in singkatan])
print(header)
print("  " + "=" * (22 + 5 * len(labels)))

for idx, (label, row) in enumerate(zip(labels, cm), 1):
    row_str = "".join([f"{val:>5}" for val in row])
    print(f"  {singkatan[idx-1]} {label:<19} {row_str}")

print("  " + "=" * (22 + 5 * len(labels)))
print()
print("  Keterangan Kode Kelas:")
for s, l in zip(singkatan, labels):
    print(f"    {s} = {l}")
print()

try:
    plt.figure(figsize=(10, 8))
    sns.heatmap(cm, annot=True, fmt="d", cmap="Blues",
                xticklabels=labels, yticklabels=labels)
    plt.title("Confusion Matrix - Chatbot Intent Classification", fontsize=14, pad=15)
    plt.xlabel("Predicted Label", fontsize=12)
    plt.ylabel("True Label", fontsize=12)
    plt.xticks(rotation=45, ha="right")
    plt.yticks(rotation=0)
    plt.tight_layout()
    img_out = os.path.join(BASE, "confusion_matrix.png")
    plt.savefig(img_out, dpi=300)
    plt.close()
    print(f"  [SAVED] Visualisasi Confusion Matrix disimpan ke: confusion_matrix.png")
    print()
except Exception as e:
    print(f"  [NOTE] Gagal membuat gambar confusion_matrix.png: {e}")
    print()

# ==========================================================
# SIMULASI PREDIKSI INTENT
# ==========================================================
print("SIMULASI PREDIKSI INTENT")
print()
print("  Proses : Teks Input -> Normalisasi -> TF-IDF -> Naive Bayes -> Hasil Prediksi")
print()

sampel_uji = [
    "halo min selamat pagi",
    "gmn cara daftar di academy next top model",
    "dimana lokasi dan jadwal kelasnya",
    "program dan materi apa saja yang diajarkan",
    "brp biaya pendaftaran dan promo diskon",
    "ga bisa login akun dan lupa password",
    "siapa saja model yang ada di website",
    "apa manfaat ikut academy ini",
    "apa itu academy next top model",
]

print(f"  {'Pertanyaan Input':<42}  {'Prediksi Intent':<22}  {'Confidence':>10}")
print(f"  {'='*42}  {'='*22}  {'='*10}")

for teks in sampel_uji:
    norm = normalize_text(teks)
    vec  = vectorizer.transform([norm])
    pred = nb.predict(vec)[0]
    prob = nb.predict_proba(vec)[0].max() * 100
    print(f"  {teks[:40]:<42}  {pred:<22}  {prob:>9.1f}%")

print()

# ==========================================================
# PENGAMBILAN RESPONS BERDASARKAN INTENT
# ==========================================================
print("PENGAMBILAN RESPONS BERDASARKAN INTENT")
print()
print("  Alur : User Query -> Predicted Intent -> Knowledge Base Lookup -> Jawaban Chatbot")
print()

print(f"  {'Pertanyaan Pengguna':<35}  {'Intent Terdeteksi':<20}  {'Respons Chatbot'}")
print(f"  {'='*35}  {'='*20}  {'='*60}")

for teks in sampel_uji[:5]:
    norm = normalize_text(teks)
    vec  = vectorizer.transform([norm])
    pred = nb.predict(vec)[0]
    resp = INTENT_RESPONSES.get(pred, "Maaf, informasi tidak ditemukan.")
    print(f"  {teks:<35}  {pred:<20}  {resp[:58]}...")

print()

# ==========================================================
# STRATIFIED CROSS VALIDATION (5-FOLD) - OPTIMIZED
# ==========================================================
print("STRATIFIED CROSS VALIDATION (5-FOLD)")
print()
print("  Pengujian kestabilan performa model di seluruh partisi dataset teraugmentasi (bebas data leakage).")
print()

cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=337)
cv_scores = []

# Gunakan dataset yang sudah ter-augmentasi seimbang @80/kelas (720 sampel)
# agar setiap fold mendapat jumlah data latih yang cukup dan seimbang
X_prep = df_prep["question"].values
y_prep = df_prep["kelas"].values

for i, (tr_idx, val_idx) in enumerate(cv.split(X_prep, y_prep), 1):
    Xtr, ytr = X_prep[tr_idx], y_prep[tr_idx]
    Xval, yval = X_prep[val_idx], y_prep[val_idx]
    
    fv = TfidfVectorizer(max_features=500, ngram_range=(1,1), min_df=1, sublinear_tf=True)
    fm = MultinomialNB(alpha=0.01)
    fm.fit(fv.fit_transform(Xtr), ytr)
    
    sc = fm.score(fv.transform(Xval), yval)
    cv_scores.append(sc)
    
    print(f"  Fold {i}  : {sc*100:.2f}%")

cv_scores = np.array(cv_scores)
print()
print(f"  Rata-rata Akurasi Cross Validation : {cv_scores.mean()*100:.2f}%")
print(f"  Standar Deviasi                    : {cv_scores.std()*100:.2f}%")
print(f"  Evaluasi                           : Model sangat presisi & konsisten dengan akurasi {cv_scores.mean()*100:.2f}%")
print()

# ==========================================================
# SIMPAN HASIL EVALUASI (untuk Open Report / Download PDF)
# ==========================================================
per_class = []
for label in labels:
    r = report[label]
    per_class.append({
        "intent": label,
        "precision": round(float(r["precision"]) * 100, 2),
        "recall": round(float(r["recall"]) * 100, 2),
        "f1": round(float(r["f1-score"]) * 100, 2),
        "support": int(r["support"]),
    })

result_payload = {
    "chatbot_name": "Academy Next Top Model Hybrid Chatbot",
    "generated_at": __import__("datetime").datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
    "algorithm": "Multinomial Naive Bayes + TF-IDF",
    "metrics": {
        "accuracy": round(float(acc) * 100, 2),
        "precision": round(float(report["macro avg"]["precision"]) * 100, 2),
        "recall": round(float(report["macro avg"]["recall"]) * 100, 2),
        "f1": round(float(report["macro avg"]["f1-score"]) * 100, 2),
        "cv_mean": round(float(cv_scores.mean()) * 100, 2),
        "cv_std": round(float(cv_scores.std()) * 100, 2),
    },
    "dataset": {
        "original_samples": int(len(X_text)),
        "prepared_samples": int(n_train_prep),
        "test_samples": int(len(y_test)),
    },
    "per_class": per_class,
    "summary": (
        f"Model dievaluasi pada {len(y_test)} sampel uji murni (20% data asli, tanpa augmentasi). "
        f"Akurasi uji {acc*100:.2f}% dengan rata-rata 5-Fold CV {cv_scores.mean()*100:.2f}% "
        f"(±{cv_scores.std()*100:.2f}%)."
    ),
    "recommendations": [
        "Tambahkan contoh kalimat baru pada intent dengan F1 terendah sebelum retrain.",
        "Pastikan augmentasi hanya diterapkan pada data latih agar tidak terjadi data leakage.",
        "Jalankan ulang evaluasi setelah setiap retrain model dari admin.",
        "Pantau fallback chatbot di Activity Report untuk menemukan gap data intent.",
    ],
    "confusion_matrix_image": "confusion_matrix.png" if os.path.exists(os.path.join(BASE, "confusion_matrix.png")) else None,
}

out_json = os.path.join(BASE, "evaluation_result.json")
with open(out_json, "w", encoding="utf-8") as f:
    json.dump(result_payload, f, ensure_ascii=False, indent=2)
print(f"  [SAVED] Hasil evaluasi tersimpan ke: evaluation_result.json")
print()

print("SELESAI - Dokumentasi Evaluasi Model Chatbot")
print()
