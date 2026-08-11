"""
Pelatihan ulang TF-IDF + Multinomial Naive Bayes.
Dipakai Flask endpoint /train/naive-bayes (via Laravel Queue).
"""
from __future__ import annotations

import os
import pickle
import tempfile
import time
from typing import Any

import numpy as np
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics import accuracy_score, f1_score, precision_score, recall_score
from sklearn.model_selection import train_test_split
from sklearn.naive_bayes import MultinomialNB

BASE = os.path.dirname(os.path.abspath(__file__))
NLP_DIR = os.path.join(BASE, "..", "nlp_pipeline")
CSV_PATH = os.path.join(NLP_DIR, "dataset_prepared.csv")
NB_MODEL_PATH = os.path.join(NLP_DIR, "nb_model.pkl")
TFIDF_PATH = os.path.join(NLP_DIR, "tfidf_vectorizer.pkl")


def _atomic_pickle_dump(obj: Any, final_path: str, replace_retries: int = 10, replace_delay: float = 0.3) -> None:
    """Tulis obj ke file temp di direktori yang sama, lalu os.replace() ke final_path.

    Mencegah pembaca lain (mis. evaluation.py) membaca file setengah-tertulis
    kalau kebetulan pickle.load() dipanggil di tengah proses training menimpa file.

    os.replace() sendiri di-retry singkat: di Windows, replace bisa gagal dengan
    PermissionError sesaat kalau pembaca lain kebetulan sedang punya file lama
    terbuka persis di waktu yang sama (dibuktikan lewat pengujian race langsung).
    """
    directory = os.path.dirname(final_path) or "."
    fd, tmp_path = tempfile.mkstemp(dir=directory, prefix=".tmp_", suffix=".pkl")
    try:
        with os.fdopen(fd, "wb") as f:
            pickle.dump(obj, f)
        last_err: PermissionError | None = None
        for attempt in range(1, replace_retries + 1):
            try:
                os.replace(tmp_path, final_path)
                return
            except PermissionError as e:
                last_err = e
                if attempt < replace_retries:
                    time.sleep(replace_delay)
        raise last_err
    except BaseException:
        if os.path.exists(tmp_path):
            os.remove(tmp_path)
        raise


def train_naive_bayes_from_csv() -> dict[str, Any]:
    """Latih ulang NB+TF-IDF dari dataset_prepared.csv, simpan .pkl, kembalikan metrik."""
    if not os.path.exists(CSV_PATH):
        raise FileNotFoundError(
            "dataset_prepared.csv tidak ditemukan. Jalankan nlp_pipeline/data_preparation.py dulu."
        )

    df = pd.read_csv(CSV_PATH)
    if "question" not in df.columns or "kelas" not in df.columns:
        raise ValueError("CSV harus punya kolom 'question' dan 'kelas'")

    X = df["question"].astype(str).values
    y = df["kelas"].astype(str).values

    # CATATAN PENTING (ditemukan saat verifikasi fix data_preparation.py):
    # dataset_prepared.csv sekarang berisi 100% data asli (tidak lagi
    # dibuang 20% secara permanen) dari SUMBER YANG SAMA PERSIS dengan
    # evaluation.py punya load_original(). Akibatnya, memperkecil
    # test_size di sini (sempat dicoba 0.05) membuat overlap verbatim
    # antara "train" di sini dan "test" di evaluation.py melonjak ke
    # ~68% -- metrik Run Evaluation jadi TIDAK VALID (leakage, bukan
    # generalisasi asli; dibuktikan lewat pengecekan subset baris yang
    # BENAR-BENAR tidak overlap: akurasi jujur ~87%, bukan ~95% yang
    # dilaporkan). Dikembalikan ke 0.2 (nilai asli) untuk membatasi
    # overlap sedekat mungkin ke fraksi holdout evaluation.py sendiri
    # (20%) -- TIDAK menghilangkan risiko sepenuhnya (populasi sumber
    # kedua script tetap nyaris identik), tapi paling defensif tanpa
    # rework arsitektur lebih dalam (mengoordinasikan split antar
    # script, di luar scope perbaikan mekanisme kali ini).
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=337, stratify=y
    )

    vectorizer = TfidfVectorizer(
        max_features=1500, ngram_range=(1, 1), min_df=1, sublinear_tf=True
    )
    X_train_tfidf = vectorizer.fit_transform(X_train)
    X_test_tfidf = vectorizer.transform(X_test)

    nb = MultinomialNB(alpha=0.01)
    nb.fit(X_train_tfidf, y_train)

    y_pred = nb.predict(X_test_tfidf)
    acc = float(accuracy_score(y_test, y_pred))
    prec = float(precision_score(y_test, y_pred, average="macro", zero_division=0))
    rec = float(recall_score(y_test, y_pred, average="macro", zero_division=0))
    f1 = float(f1_score(y_test, y_pred, average="macro", zero_division=0))

    _atomic_pickle_dump(nb, NB_MODEL_PATH)
    _atomic_pickle_dump(vectorizer, TFIDF_PATH)

    return {
        "success": True,
        "message": "Model Naive Bayes + TF-IDF berhasil dilatih ulang dan disimpan.",
        "model_info": {
            "algorithm": "Multinomial Naive Bayes + TF-IDF",
            "train_samples": int(len(X_train)),
            "test_samples": int(len(X_test)),
            "classes": [str(c) for c in nb.classes_],
            "vocabulary_size": int(len(vectorizer.vocabulary_)),
            "accuracy": round(acc, 4),
            "precision_macro": round(prec, 4),
            "recall_macro": round(rec, 4),
            "f1_macro": round(f1, 4),
            "nb_model_path": NB_MODEL_PATH,
            "tfidf_path": TFIDF_PATH,
        },
    }
