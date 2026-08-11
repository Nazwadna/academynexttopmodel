"""
  FASE 2 — DATA PREPARATION (termasuk Augmentasi EDA)
  Chatbot Academy Next Top Model | CRISP-DM
  Jalankan : python data_preparation.py (dari folder nlp_pipeline/)
  Isi      : Mapping Intent → Slang Normalization → Preprocessing
             → Easy Data Augmentation (SR, RI, RS, RD)
             → Simpan hasil ke dataset_prepared.csv
"""
import sys, os, json, re
import numpy as np
import pandas as pd
from collections import Counter

sys.stdout.reconfigure(encoding="utf-8")

BASE = os.path.dirname(os.path.abspath(__file__))
APP_PY    = os.path.join(BASE, "..", "app.py")
FAQ_JSON  = os.path.join(BASE, "..", "data", "faq.json")
ACAD_JSON = os.path.join(BASE, "..", "data", "academy_enhanced.json")

sys.path.append(os.path.join(BASE, ".."))
from modules.data_augmentation import EasyDataAugmented
from modules.informal_word_map import INFORMAL_WORD_MAP
augmenter      = EasyDataAugmented(augmentation_prob=0.3)  # untuk proses augmentasi data
augmenter_demo = EasyDataAugmented(augmentation_prob=1.0)  # khusus demo visual (prob=100%)

# ── Normalisasi ejaan informal -> baku: SATU sumber kebenaran dipakai
# BERSAMA dengan live inference (app.py normalize_informal()), lihat
# modules/informal_word_map.py. Sebelumnya file ini punya salinan
# terpisah (SLANG_MAP) yang tidak sinkron dengan app.py -- menyebabkan
# kata seperti "trims"/"tq"/"mksh" "dikenal" saat training (dicollapse
# ke "terima kasih") tapi "asing" saat live (app.py tidak melakukan
# collapse yang sama, TF-IDF vectorizer tidak pernah melihat token
# aslinya) -- diperbaiki dengan unifikasi ini.
#
# CONTENT_SYNONYM_MAP di bawah TETAP training-only (SENGAJA TIDAK
# dipindah ke modules/informal_word_map.py) -- ini collapsing sinonim
# lintas-topik (mis. "alamat"->"lokasi", "gabung"->"daftar"), BUKAN
# normalisasi ejaan informal. Banyak checker is_*_query() di app.py
# bergantung pada kata asli ini sebagai keyword-nya sendiri; audit
# dampak menyamakan ini ke live pipeline di luar scope unifikasi ini.
CONTENT_SYNONYM_MAP = {
    # "buat"/"bwt" -> "untuk": SENGAJA TIDAK di modules/informal_word_map.py
    # (ambigu "untuk" vs "membuat", lihat app.py untuk detail) -- tetap
    # training-only di sini seperti sebelumnya.
    "bwt": "untuk", "buat": "untuk",

    # Sinonim Pendaftaran & Bergabung (Alami)
    "gabung": "daftar", "bergabung": "daftar", "join": "daftar", "ikut": "daftar", "masuk": "daftar",
    "ikutan": "daftar", "registrasi": "daftar", "pendaftaran": "daftar", "register": "daftar", "apply": "daftar", "ikutserta": "daftar",

    # Sinonim Lokasi & Tempat
    "alamat": "lokasi", "tempat": "lokasi", "gedung": "lokasi", "kantor": "lokasi", "studio": "lokasi",

    # Sinonim Foto & Portofolio
    "gambar": "foto", "photo": "foto", "photoshoot": "foto", "image": "foto", "portofolio": "foto", "portfolio": "foto",

    # Rekomendasi & Kontak
    "rekomen": "rekomendasi", "rekomendasiin": "rekomendasi", "saranin": "rekomendasi",
    "hub": "hubungi", "kontak": "hubungi", "wa": "whatsapp", "ig": "instagram",
}

# Gabungan untuk normalize_text() -- perilaku IDENTIK dengan SLANG_MAP lama
# (union informal-spelling + content-synonym), hanya sumbernya sekarang
# terpecah 2 (shared + training-only) supaya bagian yang seharusnya sama
# dengan live tidak lagi drift diam-diam.
SLANG_MAP = {**INFORMAL_WORD_MAP, **CONTENT_SYNONYM_MAP}

# ── Mapping 27 intent → 10 kelas utama ───────────────────────────────
INTENT_MAP = {
    "greeting":                  "greeting",
    "gratitude":                 "gratitude",
    "about_academy":             "homepage_info",
    "homepage_info":             "homepage_info",
    "career_highlights_info":    "homepage_info",
    "programs_info":             "program_info",
    "training_programs":         "program_info",
    "join_registration_info":    "registration_info",
    "registration_steps":        "registration_info",
    "registration_requirements": "registration_info",
    "register_help":             "registration_info",
    "benefits_joining":          "become_model_info",
    "portfolio_info":            "become_model_info",
    "become_model_info":          "become_model_info",
    "photo_upload":               "become_model_info",
    "location_info":             "location_info",
    "schedule_info":             "location_info",
    "model_info":                "model_info",
    "model_categories":          "model_info",
    "client_search_model":       "model_info",
    "pricing_info":              "sensitive_intent",
    "pricing_sensitive":         "sensitive_intent",
    "booking_info":              "sensitive_intent",
    "contact_admin":             "sensitive_intent",
    "technical":                 "technical_support",
    "login_help":                "technical_support",
    "otp_help":                  "technical_support",
    "emergency":                 "technical_support",
}

def classify_exact_qa(text):
    t = text.lower()
    if any(k in t for k in ["terima kasih","makasih","thanks","thank you","nuhun","suwun"]): return "gratitude"
    if any(k in t for k in ["halo","hai","hi","hello","selamat pagi","selamat siang","selamat sore","selamat malam","apa kabar","hei"]): return "greeting"
    if any(k in t for k in ["error","gagal","kendala","tidak bisa","bug","loading","problem","lupa password","otp","verifikasi","login","blank","terblokir","reset"]): return "technical_support"
    if any(k in t for k in ["lokasi","alamat","dimana","di mana","tempat","tsm","cibubur","mall","gedung","lantai","jadwal","jam","hari","sabtu","waktu","sesi","kapan"]): return "location_info"
    if any(k in t for k in ["biaya","harga","bayar","tarif","fee","diskon","promo","dp","cicilan","booking","admin","kontak","whatsapp","kontrak","payment","price"]): return "sensitive_intent"
    if any(k in t for k in ["manfaat","benefit","keuntungan","fasilitas","upload foto","unggah foto","format foto","close up","full body","tips","langkah awal","pemula"]): return "become_model_info"
    if any(k in t for k in ["program","materi","kelas","catwalk","runway","beauty","pose","photoshoot","self branding","public speaking","kurikulum"]): return "program_info"
    if any(k in t for k in ["daftar","register","pendaftaran","registrasi","bergabung","join","syarat","persyaratan","formulir","gabung","ikut","masuk"]): return "registration_info"
    if any(k in t for k in ["model","talent","anggota","member","portofolio","katalog","biodata"]): return "model_info"
    return "homepage_info"


print("DATA PREPARATION & NLP TEXT PREPROCESSING — CHATBOT ACADEMY NEXT TOP MODEL")

# ── [2.1] Load & mapping intent ──────────────────────────────────────
print(f"\nMapping 26 Intent → 9 Kelas Utama")

rows = []
with open(APP_PY, "r", encoding="utf-8") as f:
    qa_match = re.search(r'EXACT_QA\s*=\s*\{(.+?)\n\}', f.read(), re.DOTALL)
    if qa_match:
        for q, a in re.findall(r'"([^"]+)":\s*"([^"]+)"', qa_match.group(1)):
            kelas = classify_exact_qa(q)
            rows.append({"intent_asli": "exact_qa", "kelas": kelas, "question": q})

with open(FAQ_JSON, "r", encoding="utf-8") as f:
    for item in json.load(f).get("faqs", []):
        kelas = INTENT_MAP.get(item["intent"])
        if kelas:
            for q in item.get("questions", []):
                rows.append({"intent_asli": item["intent"], "kelas": kelas, "question": q})

with open(ACAD_JSON, "r", encoding="utf-8") as f:
    d = json.load(f)
    for intent, kws in d.get("keywords", {}).items():
        kelas = INTENT_MAP.get(intent)
        if not kelas: continue
        if isinstance(kws, list):
            for kw in kws: rows.append({"intent_asli": intent, "kelas": kelas, "question": kw})
        elif isinstance(kws, dict):
            for lkws in kws.values():
                if isinstance(lkws, list):
                    for kw in lkws: rows.append({"intent_asli": intent, "kelas": kelas, "question": kw})

df = pd.DataFrame(rows)

print(f"  {'No':<4} {'Kelas Utama':<25} {'Intent Asli Tercakup':>5}  {'Jumlah':>6}")
for i, (k, v) in enumerate(df["kelas"].value_counts().items(), 1):
    intent_count = df[df["kelas"] == k]["intent_asli"].nunique()
    print(f"  {i:<4} {k:<25} {intent_count:>21}  {v:>6}")
print(f"  {'':4} {'TOTAL':<25} {'':21}  {len(df):>6}")

# ── [2.2] Normalisasi slang ──────────────────────────────────────────
print(f"\nNormalisasi Slang Bahasa Indonesia")

def normalize_slang(text):
    return " ".join(SLANG_MAP.get(w, w) for w in text.lower().split())

contoh_slang = [
    "gmn cara dftr min?",
    "brp biaya pendaftaran?",
    "jdwl kelas kapan?",
    "ga bisa login ni",
    "mw tanya lok academy dmn?",
]
print(f"  {'Teks Asli (Slang)':<35} {'Setelah Normalisasi Slang'}")
for teks in contoh_slang:
    print(f"  {teks:<35} {normalize_slang(teks)}")

print(f"\n  Kamus slang yang digunakan : {len(SLANG_MAP)} entri")

# ── [2.3] Text preprocessing ─────────────────────────────────────────
print(f"\nText Preprocessing (Normalisasi Lengkap)")

def normalize_text(text: str) -> str:
    text = text.lower().strip()
    words = [SLANG_MAP.get(w, w) for w in text.split()]  # slang
    text = " ".join(words)
    text = re.sub(r"[?!.:,;/\-]", " ", text)             # hapus tanda baca
    text = re.sub(r"\s+", " ", text)                       # normalisasi spasi
    return text.strip()

contoh_input = [
    "Apa itu Academy Next Top Model?",
    "JADWAL KELAS KAPAN!!",
    "gmn cara dftr min???",
    "Berapa  Biaya-Pendaftaran-nya?",
    "  ga bisa login ni...  ",
]
print(f"  Langkah preprocessing yang diterapkan:")
print(f"    1. lower()         : konversi ke huruf kecil")
print(f"    2. Slang mapping   : normalisasi kata tidak baku")
print(f"    3. re.sub()        : hapus tanda baca (? ! . : ; / -)")
print(f"    4. re.sub()        : normalisasi spasi ganda")
print(f"\n  {'Teks Asli':<38} {'Hasil Normalisasi'}")
for t in contoh_input:
    print(f"  {t.strip()[:36]:<38} {normalize_text(t)}")

df["question_clean"] = df["question"].apply(normalize_text)

print(f"\n  Contoh hasil preprocessing per kelas intent :")
print(f"  {'Teks Asli':<38} {'Setelah Preprocessing':<30} {'Kelas'}")
for _, row in df.groupby("kelas").first().reset_index().iterrows():
    print(f"  {row['question'][:36]:<38} {row['question_clean'][:28]:<30} {row['kelas']}")

# ── [2.4] Augmentasi EDA ─────────────────────────────────────────────
print(f"\nEasy Data Augmentation (EDA) — 4 Teknik Bahasa Indonesia")
print("  Tujuan : Menyeimbangkan kelas minoritas tanpa kebocoran data (no leakage)")
print("  Teknik yang diterapkan:")
print("    SR (Synonym Replacement) — mengganti kata dengan sinonim bahasa Indonesia")
print("    RI (Random Insertion)    — menyisipkan kata baru secara acak dalam kalimat")
print("    RS (Random Swap)         — menukar posisi dua kata secara acak")
print("    RD (Random Deletion)     — menghapus satu kata secara acak")

print(f"\n  Contoh hasil augmentasi pada satu kalimat :")
samples_demo = [
    ("program daftar academy next top model",     "registration_info"),
    ("lokasi dimana academy next top model",       "location_info"),
    ("jadwal training program academy",            "program_info"),
]
print(f"  {'Teknik':<5} {'Teks Asli':<40} {'Hasil Augmentasi'}")
for sample, kelas in samples_demo:
    print(f"  [Kelas: {kelas}] | Original   : {sample}")
    print(f"  {'':5} {'SR':<40} {augmenter_demo.synonym_replacement(sample)}")
    print(f"  {'':5} {'RI':<40} {augmenter_demo.random_insertion(sample)}")
    print(f"  {'':5} {'RS':<40} {augmenter_demo.random_swap(sample)}")
    print(f"  {'':5} {'RD':<40} {augmenter_demo.random_deletion(sample)}")
    print()

# ── Augmentasi memakai SELURUH data (bukan split 80:20 internal) ─────
# Sebelumnya file ini men-split 80:20 di sini KHUSUS untuk klaim "data
# uji murni untuk referensi modeling" -- tapi nilai X_test_text/y_test
# hasil split itu TIDAK PERNAH disimpan ke file apa pun maupun dipakai
# downstream (nb_trainer.py dan evaluation.py masing-masing membangun
# ulang & mensplit data secara independen dari sumber asli, tidak
# pernah membaca split ini). Akibatnya ~20% kalimat asli (termasuk
# entri seperti "wassup"/"hoi"/"yo"/"haaii") dibuang permanen tanpa
# manfaat evaluasi apa pun. Diperbaiki: pakai 100% data untuk
# augmentasi/training -- evaluasi jujur & independen tetap terjaga
# lewat evaluation.py (rebuild dari sumber asli, split sendiri).
X_text = df["question_clean"].values
y = df["kelas"].values

TARGET = 80
X_aug, y_aug = [], []
for cls in np.unique(y):
    idx = np.where(y == cls)[0]
    texts = X_text[idx].tolist()
    X_aug.extend(texts); y_aug.extend([cls] * len(texts))
    if len(texts) < TARGET:
        needed = TARGET - len(texts)
        np.random.seed(337)
        bases = np.random.choice(texts, size=needed, replace=True)
        for s in bases:
            tech = np.random.choice(["sr", "ri", "rs", "rd"])
            if tech == "sr":   a = augmenter.synonym_replacement(s)
            elif tech == "ri": a = augmenter.random_insertion(s)
            elif tech == "rs": a = augmenter.random_swap(s)
            else:              a = augmenter.random_deletion(s)
            X_aug.append(a); y_aug.append(cls)

X_aug = np.array(X_aug); y_aug = np.array(y_aug)

print(f"\n  Distribusi kelas sebelum dan sesudah augmentasi:")
print(f"  {'Kelas':<25} {'Sebelum':>8} {'Sesudah':>8}  {'Keterangan'}")
before = Counter(y); after = Counter(y_aug)
for k in sorted(after):
    b = before.get(k, 0)
    note = "ditambah" if b < TARGET else "tidak diubah"
    print(f"  {k:<25} {b:>8} {after[k]:>8}  {note}")
print(f"  {'TOTAL':<25} {len(X_text):>8} {len(X_aug):>8}")

print(f"\n  Catatan: seluruh data asli ({len(X_text)} baris) dipakai untuk")
print(f"  training/augmentasi -- tidak ada lagi porsi yang dibuang. Evaluasi")
print(f"  jujur & independen tetap dari evaluation.py (split terpisah dari")
print(f"  sumber asli, tidak bersentuhan dengan file ini).")

# ── Simpan hasil ke CSV ───────────────────────────────────────────────
out_csv = os.path.join(BASE, "dataset_prepared.csv")
df_out = pd.DataFrame({"question": X_aug, "kelas": y_aug})
df_out.to_csv(out_csv, index=False, encoding="utf-8-sig")
print(f"\nHasil augmentasi tersimpan ke : dataset_prepared.csv ({len(df_out)} baris)")
