"""
=======================================================================
  FASE 1 — DATA UNDERSTANDING
  Chatbot Academy Next Top Model | CRISP-DM
  Jalankan : python data_understanding.py (dari folder nlp_pipeline/)
=======================================================================
"""
import json, re, string, warnings
import pandas as pd
from collections import Counter
warnings.filterwarnings('ignore')

# ── Path sumber data ────────────────────────────────────────────────
APP_PY    = "../app.py"
FAQ_JSON  = "../data/faq.json"
ACAD_JSON = "../data/academy_enhanced.json"

# ── Load data mentah dari 3 sumber ──────────────────────────────────
def load_raw_data():
    rows = []

    # Sumber 1: app.py (EXACT_QA)
    with open(APP_PY, "r", encoding="utf-8") as f:
        qa_match = re.search(r'EXACT_QA\s*=\s*\{(.+?)\n\}', f.read(), re.DOTALL)
        if qa_match:
            for q, a in re.findall(r'"([^"]+)":\s*"([^"]+)"', qa_match.group(1)):
                rows.append({"sumber": "app.py", "intent": "exact_qa",
                              "question": q, "response": a})

    # Sumber 2: faq.json
    with open(FAQ_JSON, "r", encoding="utf-8") as f:
        for item in json.load(f).get("faqs", []):
            for q in item.get("questions", []):
                rows.append({"sumber": "faq.json", "intent": item["intent"],
                              "question": q, "response": item["answer"]})

    # Sumber 3: academy_enhanced.json
    with open(ACAD_JSON, "r", encoding="utf-8") as f:
        d = json.load(f)
        for intent, kws in d.get("keywords", {}).items():
            resp = d.get("responses", {}).get(intent, ["Info"])
            resp_str = resp[0] if isinstance(resp, list) else str(resp)
            if isinstance(kws, list):
                for kw in kws:
                    rows.append({"sumber": "academy_enhanced.json",
                                  "intent": intent, "question": kw, "response": resp_str})
            elif isinstance(kws, dict):
                for lang_kws in kws.values():
                    if isinstance(lang_kws, list):
                        for kw in lang_kws:
                            rows.append({"sumber": "academy_enhanced.json",
                                          "intent": intent, "question": kw, "response": resp_str})
    return pd.DataFrame(rows)



print("DATA UNDERSTANDING — NLP DATASET CHATBOT ACADEMY NEXT TOP MODEL")
print(f"  Analisis Eksplorasi Data Teks Bahasa Alami (NLP)")

df = load_raw_data()
df["q_length"] = df["question"].apply(len)
df["q_words"]  = df["question"].apply(lambda x: len(str(x).split()))

# ── [1.1] Informasi umum ─────────────────────────────────────────────
print(f"\nInformasi Umum Dataset")
print(f"  Total baris (pertanyaan)   : {len(df)}")
print(f"  Total kolom                : {len(df.columns)}")
print(f"  Total sumber file          : {df['sumber'].nunique()}")
print(f"  Total intent unik          : {df['intent'].nunique()}")
print(f"  Missing values             : {df.isnull().sum().sum()}")
print(f"  Data duplikat (pertanyaan) : {df.duplicated(subset=['question']).sum()}")

# ── [1.2] Distribusi per sumber ──────────────────────────────────────
print(f"\nDistribusi Data per Sumber File")
print(f"  {'No':<4} {'Sumber File':<35} {'Jumlah':>7}")
print(f"  {'-'*4} {'-'*35} {'-'*7}")
for i, (src, cnt) in enumerate(df["sumber"].value_counts().items(), 1):
    print(f"  {i:<4} {src:<35} {cnt:>7}")

# ── [1.3] Distribusi 26 intent asli ─────────────────────────────────
print(f"\nDistribusi 26 Intent Asli (Sebelum Mapping)")
ic = df["intent"].value_counts()
print(f"  {'No':<4} {'Intent':<30} {'Jumlah':>6}  {'Bar'}")
print(f"  {'-'*4} {'-'*30} {'-'*6}  {'-'*20}")
for i, (k, v) in enumerate(ic.items(), 1):
    bar = "#" * (v // 5)
    print(f"  {i:<4} {k:<30} {v:>6}  {bar}")

# ── [1.4] Statistik deskriptif ───────────────────────────────────────
print(f"\nStatistik Deskriptif Panjang Pertanyaan")
stats = df[["q_length", "q_words"]].describe().round(2)
stats.columns = ["Panjang (Karakter)", "Panjang (Kata)"]
print(stats.to_string())

# ── [1.5] Top 10 kata ────────────────────────────────────────────────
print(f"\nTop 10 Kata Paling Sering Muncul (tanpa stopword)")
STOPWORDS = {"di","ke","dari","yang","dan","atau","untuk","ini","itu",
             "apa","bagaimana","cara","saya","bisa","ada","apakah","dengan",
             "tidak","adalah","berapa","dimana","siapa","kapan","ya","jika"}
all_words = " ".join(df["question"].str.lower()).translate(
    str.maketrans("", "", string.punctuation)).split()
filtered = [w for w in all_words if w not in STOPWORDS]
top10 = Counter(filtered).most_common(10)
print(f"  {'No':<4} {'Kata':<20} {'Frekuensi':>10}  Bar")
print(f"  {'-'*4} {'-'*20} {'-'*10}  {'-'*20}")
for i, (word, count) in enumerate(top10, 1):
    bar = "#" * (count // 10)
    print(f"  {i:<4} {word:<20} {count:>10}  {bar}")

# ── [1.6] Contoh data ────────────────────────────────────────────────
print(f"\nContoh Data (5 Baris Pertama)")
pd.set_option("display.max_colwidth", 45)
pd.set_option("display.width", 120)
print(df[["sumber", "intent", "question"]].head().to_string(index=True))
