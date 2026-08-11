"""
FASE 3 - MODELING
Chatbot Academy Next Top Model
Algoritma : TF-IDF + Multinomial Naive Bayes
"""
import sys, os, json, re, pickle
import numpy as np
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.model_selection import train_test_split
from sklearn.metrics import (accuracy_score, precision_score,
                              recall_score, f1_score, classification_report)

# Force flush agar output tidak berantakan di terminal Windows
import functools
print = functools.partial(print, flush=True)

BASE      = os.path.dirname(os.path.abspath(__file__))
CSV_PATH  = os.path.join(BASE, "dataset_prepared.csv")
APP_PY    = os.path.join(BASE, "..", "app.py")
FAQ_JSON  = os.path.join(BASE, "..", "data", "faq.json")
ACAD_JSON = os.path.join(BASE, "..", "data", "academy_enhanced.json")

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
    # Singkatan & Slang Umum Indonesia
    "gmn": "bagaimana", "gimana": "bagaimana", "gmna": "bagaimana", "mna": "bagaimana", "gimene": "bagaimana",
    "brp": "berapa",   "brapa": "berapa", "berapaan": "berapa",
    "min": "admin",    "admn": "admin",  "mimin": "admin",
    "dftr": "daftar",  "daftr": "daftar", "daftaar": "daftar", "dftar": "daftar", "daftarr": "daftar",
    "byaya": "biaya",  "bya": "biaya",    "biayanya": "biaya",
    "lok": "lokasi",   "lksi": "lokasi",  "lokasiny": "lokasi",
    "jdwl": "jadwal",  "jdwal": "jadwal", "jadwalnnya": "jadwal",
    "pgm": "program",  "prgrm": "program", "progrm": "program",
    "kpn": "kapan",
    "blm": "belum",    "belom": "belum",
    "sdh": "sudah",    "udh": "sudah",    "udah": "sudah", "udha": "sudah",
    "gak": "tidak",    "ga": "tidak",     "nggak": "tidak", "ngga": "tidak", "gk": "tidak", "gatau": "tidak", "kagak": "tidak", "kga": "tidak",
    "mw": "ingin",     "pengen": "ingin", "pengan": "ingin", "pingin": "ingin", "pengin": "ingin", "berniat": "ingin",
    "mohon": "tolong", "mhon": "tolong",  "tlg": "tolong", "tlgong": "tolong", "plis": "tolong", "pls": "tolong", "please": "tolong",
    "bs": "bisa",      "bsa": "bisa",     "bisaa": "bisa",
    "sy": "saya",      "ak": "aku",       "gua": "saya", "gue": "saya", "gw": "saya",
    "lu": "kamu",      "elo": "kamu",     "elu": "kamu", "km": "kamu", "kmu": "kamu",
    "yg": "yang",      "utk": "untuk",    "bwt": "untuk", "buat": "untuk",
    "krn": "karena",   "karnaa": "karena",
    "bgt": "sangat",   "banget": "sangat", "bnget": "sangat",
    "skrg": "sekarang",
    "fastres": "cepat", "fastresp": "cepat",
    "thx": "terima kasih", "thanks": "terima kasih", "makasih": "terima kasih", "makasi": "terima kasih", "mksih": "terima kasih",
    "thankyou": "terima kasih", "tq": "terima kasih",
    "nuhun": "terima kasih", "suwun": "terima kasih", "maturnuwun": "terima kasih", "trims": "terima kasih", "tengkyu": "terima kasih",
    
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
                for q in item.get("questions", []):
                    rows.append({"kelas": kelas, "question": normalize_text(q)})
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
print("MODELING NLP — TF-IDF + MULTINOMIAL NAIVE BAYES")
print("Chatbot Academy Next Top Model")
print()

# ==========================================================
# KELAS INTENT YANG DIKENALI
# ==========================================================
print("IDENTIFIKASI KELAS INTENT")
print()
print("  Model dilatih untuk mengenali 9 kelas intent berikut ini.")
print("  Setiap kelas mewakili kategori pertanyaan pengguna di chatbot.")
print()

kelas_info = [
    ("greeting",          "Sapaan pengguna",                    "halo, hai, selamat pagi"),
    ("homepage_info",     "Info umum tentang academy",          "apa itu academy next top model"),
    ("registration_info", "Pendaftaran dan persyaratan",        "cara daftar, syarat bergabung"),
    ("program_info",      "Program dan materi kelas",           "program apa saja, materi kelas"),
    ("location_info",     "Lokasi dan jadwal kelas",            "dimana lokasi, jadwal kelas kapan"),
    ("model_info",        "Informasi model dan talent",         "siapa saja modelnya, talent academy"),
    ("become_model_info", "Manfaat bergabung",                  "manfaat bergabung, keuntungan"),
    ("sensitive_intent",  "Harga, booking, kontak admin",       "berapa biaya, cara booking"),
    ("technical_support", "Kendala teknis sistem",              "tidak bisa login, otp tidak masuk"),
]
print(f"  {'Kelas Intent':<25}  {'Fungsi':<35}  {'Contoh Pertanyaan'}")
print(f"  {'='*25}  {'='*35}  {'='*35}")
for kls, fungsi, contoh in kelas_info:
    print(f"  {kls:<25}  {fungsi:<35}  {contoh}")

print()

# ==========================================================
# TOKENISASI
# ==========================================================
print("TOKENISASI TEKS")
print()
print("  Tokenisasi memecah kalimat menjadi token (kata) yang siap")
print("  diproses oleh TF-IDF. Alur tokenisasi yang diterapkan:")
print()
print("  Kalimat Asli  ->  Lowercase  ->  Normalisasi Slang")
print("               ->  Hapus Tanda Baca  ->  Split per Kata  ->  Token List")
print()

contoh_tok = [
    "Bagaimana cara Daftar di Academy???",
    "JADWAL kelas kapan Min!!",
    "gmn biaya pendaftaran next top model",
    "ga bisa Login otp tidak masuk",
]
print(f"  {'Kalimat Asli':<40}  {'Token Hasil'}")
print(f"  {'='*40}  {'='*40}")
for k in contoh_tok:
    tokens = normalize_text(k).split()
    print(f"  {k:<40}  {tokens}")

print()

# ==========================================================
# KONFIGURASI PELATIHAN
# ==========================================================
print("KONFIGURASI PELATIHAN")
print()
print("  Algoritma  : Multinomial Naive Bayes")
print("  Extractor  : TF-IDF (Term Frequency - Inverse Document Frequency)")
print("  Pipeline   : Teks -> Tokenisasi -> TF-IDF -> Naive Bayes -> Kelas")
print()
print(f"  {'Parameter':<35}  {'Nilai':<20}  {'Keterangan'}")
print(f"  {'='*35}  {'='*20}  {'='*35}")
konfigurasi = [
    ("Pembagian data : test_size",   "20%",            "Data uji murni tanpa augmentasi"),
    ("Pembagian data : random_state","337",             "Seed untuk reprodusibilitas"),
    ("Pembagian data : stratify",    "kelas intent",   "Proporsional setiap kelas"),
    ("TF-IDF : max_features",        "1500 fitur",     "Maks kata unik yang dipakai"),
    ("TF-IDF : ngram_range",         "(1,1) unigram",  "Satu kata per token"),
    ("TF-IDF : sublinear_tf",        "True",           "Pakai skala log: 1+log(TF)"),
    ("TF-IDF : min_df",              "1 dokumen",      "Kata minimal muncul 1 kali"),
    ("Naive Bayes : alpha",          "0.01",           "Laplace smoothing"),
    ("Naive Bayes : fit_prior",      "True",           "Prior dihitung dari data"),
]
for param, val, ket in konfigurasi:
    print(f"  {param:<35}  {val:<20}  {ket}")

print()

# ==========================================================
# CATATAN: NAIVE BAYES TIDAK PAKAI EPOCH
# ==========================================================
print("  CATATAN PENTING :")
print("  Multinomial Naive Bayes adalah algoritma statistik closed-form.")
print("  Berbeda dengan Neural Network, Naive Bayes tidak menggunakan epoch")
print("  atau iterasi berulang. Model belajar dalam SATU KALI PROSES dengan")
print("  menghitung probabilitas langsung dari distribusi data training.")
print("  Rumus utama : P(C|X) = [ P(X|C) x P(C) ] / P(X)")
print()

# ==========================================================
# LOAD DATA
# ==========================================================
if not os.path.exists(CSV_PATH):
    print("  ERROR : dataset_prepared.csv tidak ditemukan!")
    print("  Jalankan data_preparation.py terlebih dahulu.")
    sys.exit(1)

df_prep = pd.read_csv(CSV_PATH)
X_train = df_prep["question"].values
y_train = df_prep["kelas"].values

df_ori = load_original()
_, X_test, _, y_test = train_test_split(
    df_ori["question"].values, df_ori["kelas"].values,
    test_size=0.2, random_state=337, stratify=df_ori["kelas"].values
)

# ==========================================================
# PROSES TRAINING
# ==========================================================
print("PROSES TRAINING")
print()
print(f"  Data latih (setelah augmentasi)  : {len(X_train)} baris")
print(f"  Data uji   (murni, tidak diaugmentasi) : {len(X_test)} baris")
print()

print("  Langkah 1  Fit TF-IDF Vectorizer pada data latih ...")
vectorizer = TfidfVectorizer(max_features=1500, ngram_range=(1,1), min_df=1, sublinear_tf=True)
X_train_tfidf = vectorizer.fit_transform(X_train)
X_test_tfidf  = vectorizer.transform(X_test)
feat_names    = vectorizer.get_feature_names_out()
print(f"             Vocabulary terbentuk    : {len(vectorizer.vocabulary_)} kata unik")
print(f"             Matriks latih           : {X_train_tfidf.shape[0]} baris x {X_train_tfidf.shape[1]} fitur")
print(f"             Matriks uji             : {X_test_tfidf.shape[0]} baris x {X_test_tfidf.shape[1]} fitur")
print()

print("  Langkah 2  Hitung Prior Probability P(C) untuk setiap kelas ...")
print(f"             Data seimbang @80/kelas  ->  P(C) = 1/9 = 11.11% untuk semua kelas")
print()

print("  Langkah 3  Hitung Likelihood P(kata|C) dengan Multinomial Naive Bayes ...")
nb = MultinomialNB(alpha=0.01)
nb.fit(X_train_tfidf, y_train)
print(f"             Kelas diproses          : {len(nb.classes_)} kelas")
print(f"             Fitur per kelas         : {nb.feature_log_prob_.shape[1]} kata")
print(f"             Total log-probabilitas  : {nb.feature_log_prob_.size} nilai")
print(f"             Alpha (Laplace smooth.) : {nb.alpha}")
print(f"             Status                  : SELESAI")
print()

print("  Langkah 4  Prediksi pada data uji ...")
y_pred = nb.predict(X_test_tfidf)
benar  = (y_pred == y_test).sum()
salah  = (y_pred != y_test).sum()
print(f"             Total sampel uji        : {len(y_test)}")
print(f"             Prediksi benar          : {benar}")
print(f"             Prediksi salah          : {salah}")
print()

# ==========================================================
# PARAMETER MODEL (TABEL)
# ==========================================================
print("PARAMETER MODEL YANG DIPELAJARI")
print()
print("  Tabel Prior Probability P(C) dan Jumlah Sampel per Kelas")
print()
print(f"  {'Kelas Intent':<25}  {'Jumlah Sampel':>14}  {'Log P(C)':>10}  {'P(C)':>8}")
print(f"  {'='*25}  {'='*14}  {'='*10}  {'='*8}")
for cls, lp in zip(nb.classes_, nb.class_log_prior_):
    n = int((y_train == cls).sum())
    print(f"  {cls:<25}  {n:>14}  {lp:>10.4f}  {np.exp(lp)*100:>7.2f}%")
print()

print("  Top 5 Kata Paling Berpengaruh per Kelas (P kata | kelas)")
print()
print(f"  {'Kelas Intent':<25}  {'Kata 1':<18}  {'Kata 2':<18}  {'Kata 3':<18}  {'Kata 4':<18}  {'Kata 5'}")
print(f"  {'='*25}  {'='*18}  {'='*18}  {'='*18}  {'='*18}  {'='*18}")
for cls in nb.classes_:
    idx_cls   = list(nb.classes_).index(cls)
    log_probs = nb.feature_log_prob_[idx_cls]
    top5_idx  = log_probs.argsort()[-5:][::-1]
    words     = [f"{feat_names[j]}({np.exp(log_probs[j]):.3f})" for j in top5_idx]
    print(f"  {cls:<25}  {words[0]:<18}  {words[1]:<18}  {words[2]:<18}  {words[3]:<18}  {words[4]}")

print()

# ==========================================================
# METRIK EVALUASI
# ==========================================================
print("HASIL EVALUASI MODEL")
print()

acc  = accuracy_score(y_test, y_pred)
prec = precision_score(y_test, y_pred, average="macro", zero_division=0)
rec  = recall_score(y_test, y_pred, average="macro", zero_division=0)
f1m  = f1_score(y_test, y_pred, average="macro", zero_division=0)
f1w  = f1_score(y_test, y_pred, average="weighted", zero_division=0)

print(f"  Data Uji : {len(y_test)} sampel (20% dari dataset asli, tidak diaugmentasi)")
print()
print(f"  {'Metrik':<30}  {'Nilai':>10}  {'Interpretasi'}")
print(f"  {'='*30}  {'='*10}  {'='*40}")
print(f"  {'Accuracy':<30}  {acc*100:>9.2f}%  Dari {len(y_test)} sampel, {benar} diprediksi benar")
print(f"  {'Precision (Macro Avg)':<30}  {prec*100:>9.2f}%  Rata-rata ketepatan prediksi per kelas")
print(f"  {'Recall (Macro Avg)':<30}  {rec*100:>9.2f}%  Rata-rata kelengkapan deteksi per kelas")
print(f"  {'F1-Score (Macro Avg)':<30}  {f1m*100:>9.2f}%  Keseimbangan Precision & Recall")
print(f"  {'F1-Score (Weighted Avg)':<30}  {f1w*100:>9.2f}%  F1 dipbobot jumlah sampel per kelas")
print()

print("  Precision, Recall, F1-Score per Kelas Intent")
print()
print(f"  {'Kelas Intent':<25}  {'Precision':>10}  {'Recall':>8}  {'F1-Score':>9}  {'Support':>8}")
print(f"  {'='*25}  {'='*10}  {'='*8}  {'='*9}  {'='*8}")

labels = sorted(set(y_test))
report = classification_report(y_test, y_pred, labels=labels,
                                digits=2, zero_division=0, output_dict=True)
for label in labels:
    r = report[label]
    print(f"  {label:<25}  {r['precision']*100:>9.1f}%  {r['recall']*100:>7.1f}%  {r['f1-score']*100:>8.1f}%  {int(r['support']):>8}")

print(f"  {'='*25}  {'='*10}  {'='*8}  {'='*9}  {'='*8}")
print(f"  {'Macro Average':<25}  {prec*100:>9.1f}%  {rec*100:>7.1f}%  {f1m*100:>8.1f}%  {len(y_test):>8}")
print(f"  {'Weighted Average':<25}  {precision_score(y_test,y_pred,average='weighted',zero_division=0)*100:>9.1f}%  {recall_score(y_test,y_pred,average='weighted',zero_division=0)*100:>7.1f}%  {f1w*100:>8.1f}%  {len(y_test):>8}")

print()

# ==========================================================
# SIMPAN MODEL
# ==========================================================
model_path  = os.path.join(BASE, "nb_model.pkl")
vector_path = os.path.join(BASE, "tfidf_vectorizer.pkl")
with open(model_path, "wb") as f:  pickle.dump(nb, f)
with open(vector_path, "wb") as f: pickle.dump(vectorizer, f)

print("MODEL DISIMPAN")
print()
print(f"  nb_model.pkl         : Model Naive Bayes terlatih  ({os.path.getsize(model_path)//1024} KB)")
print(f"  tfidf_vectorizer.pkl : Vectorizer TF-IDF terlatih  ({os.path.getsize(vector_path)//1024} KB)")
print()
print("  SELESAI - Screenshot untuk dokumentasi Bab 4 Skripsi")
print()
