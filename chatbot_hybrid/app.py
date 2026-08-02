# ========================= IMPORTS =========================
import os
import re
import json
import random
import requests
import logging
import sys
from datetime import datetime
import time
from flask import Flask, request, jsonify, render_template, session
from flask_cors import CORS
from werkzeug.utils import secure_filename
from werkzeug.exceptions import RequestEntityTooLarge
from dotenv import load_dotenv
from urllib.parse import quote

# Load env: chatbot_hybrid/.env lalu root Laravel (.env) tanpa menimpa nilai yang sudah ada
_BASE_DIR = os.path.dirname(os.path.abspath(__file__))
load_dotenv(os.path.join(_BASE_DIR, ".env"))
load_dotenv(os.path.join(_BASE_DIR, "..", ".env"), override=False)

# ========================= DEBUG NOISE CONTROL =========================
# Banyak `print(...)` dipakai saat develop. Untuk sidang/demo, kita kurangi
# dengan menyaring line yang sifatnya debug (sensitif/verbose).
CHATBOT_DEBUG = str(os.getenv("CHATBOT_DEBUG", "false")).strip().lower() in ["1", "true", "yes", "y"]
_builtin_print = print

def _filtered_print(*args, **kwargs):
    if CHATBOT_DEBUG:
        _builtin_print(*args, **kwargs)
        return

    msg = " ".join(str(a) for a in args)
    blacklist_parts = [
        "DEBUG:", "NOTICE:",
        "OPENAI KEY LOADED", "OPENAI KEY VALUE",
        "OpenAI client initialized", "OpenAI client not initialized",
        "USER MESSAGE:", "PROCESSED MESSAGE:",
        "Message lower:",
        "Processed message:",
        "Processing message:",
        "FALLBACK DETECTED:",
        "DETECTED INTENT:", "REPLY:",
        "keywords:", "About keywords list:", "About keywords list:",
        "Reg keywords:", "Schedule keywords:", "Academy info keywords:",
        "Programs info keywords:", "Homepage info keywords:", "Model info keywords:",
        "Contact admin keywords:",
        "Matched schedule:", "Matched about:",
        "Schedule responses:", "About responses:",
        "DATABASE CONNECTION NOTICE",
        "TENSORFLOW PROCESSED", "TENSORFLOW ERROR",
        "DEBUG: Skipping",
        "RAPIDFUZZ ",
        "[NB]",
        "[SENSITIVE]",
        "[ANALYZE]",
        "is_model_status_query",
        "is_model_count_query",
        "Chat endpoint called",
    ]

    # Jika mengandung kata debug/verbose tertentu, suppress.
    if any(part in msg for part in blacklist_parts):
        return

    _builtin_print(*args, **kwargs)

# Replace print di modul ini (hanya logging, tidak mengubah logic).
print = _filtered_print

try:
    import openai
    from openai import OpenAI
    openai_client = OpenAI(api_key=os.getenv("OPENAI_API_KEY")) if os.getenv("OPENAI_API_KEY") else None
except ImportError:
    openai = None
    OpenAI = None
    openai_client = None

try:
    from sqlalchemy import create_engine  # type: ignore[import-not-found]
except ImportError:
    create_engine = None
import numpy as np

try:
    from rapidfuzz import process, fuzz
    RAPIDFUZZ_AVAILABLE = True
except ImportError:
    RAPIDFUZZ_AVAILABLE = False
    process = None
    fuzz = None

import joblib

# ========================= PATH SETUP =========================
# Direktori root chatbot_hybrid
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
# Tambahkan folder modules ke sys.path agar modul bisa diimport
sys.path.insert(0, os.path.join(BASE_DIR, 'modules'))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

# Import modul dari folder modules/ — GRANULAR agar 1 kegagalan tidak mematikan semua engine
NLU_ENGINE_AVAILABLE = False
TYPO_CORRECTOR_AVAILABLE = False
RESPONSE_ORCHESTRATOR_AVAILABLE = False
CLARIFICATION_AVAILABLE = False
CHAT_LOGGER_AVAILABLE = False
DATA_AUG_AVAILABLE = False
NB_TRAINER_AVAILABLE = False

process_nlu = None
update_memory = None
get_memory = None
normalize_nlu = None
extract_entities = None
build_clarification = None
build_dynamic_response = None
typo_correct_text = None
orchestrate_multi_intent_response = None
save_chat_log = None
train_naive_bayes_from_csv = None
EasyDataAugmented = None
TensorFlowDataProcessor = None

def _try_import(module_paths, names):
    """Coba import atribut dari modules.X lalu fallback X (sys.path)."""
    last_err = None
    for mod_name in module_paths:
        try:
            mod = __import__(mod_name, fromlist=list(names))
            return {n: getattr(mod, n) for n in names}, None
        except Exception as e:
            last_err = e
    return None, last_err

# --- chat_logger ---
_objs, _err = _try_import(["modules.chat_logger", "chat_logger"], ["save_chat_log"])
if _objs:
    save_chat_log = _objs["save_chat_log"]
    CHAT_LOGGER_AVAILABLE = True
else:
    def save_chat_log(*args, **kwargs):
        return None
    print(f"[MODULE] chat_logger DISABLED: {_err}")

# --- data_augmentation ---
_objs, _err = _try_import(
    ["modules.data_augmentation", "data_augmentation"],
    ["EasyDataAugmented", "TensorFlowDataProcessor"],
)
if _objs:
    EasyDataAugmented = _objs["EasyDataAugmented"]
    TensorFlowDataProcessor = _objs["TensorFlowDataProcessor"]
    DATA_AUG_AVAILABLE = True
else:
    class EasyDataAugmented:  # type: ignore[no-redef]
        def __init__(self, *a, **k): pass
        def augment_text(self, text, *a, **k): return [text]
    class TensorFlowDataProcessor:  # type: ignore[no-redef]
        def __init__(self, *a, **k): pass
    print(f"[MODULE] data_augmentation DISABLED: {_err}")

# --- nb_trainer ---
_objs, _err = _try_import(["modules.nb_trainer", "nb_trainer"], ["train_naive_bayes_from_csv"])
if _objs:
    train_naive_bayes_from_csv = _objs["train_naive_bayes_from_csv"]
    NB_TRAINER_AVAILABLE = True
else:
    def train_naive_bayes_from_csv(*args, **kwargs):
        return {"success": False, "error": "nb_trainer unavailable"}
    print(f"[MODULE] nb_trainer DISABLED: {_err}")

# --- nlu_engine (core + clarification + dynamic response) ---
_nlu_names = [
    "process_nlu", "update_memory", "get_memory",
    "normalize_nlu", "extract_entities",
    "build_clarification_question", "build_dynamic_response",
]
_objs, _err = _try_import(["modules.nlu_engine", "nlu_engine"], _nlu_names)
if _objs:
    process_nlu = _objs["process_nlu"]
    update_memory = _objs["update_memory"]
    get_memory = _objs["get_memory"]
    normalize_nlu = _objs["normalize_nlu"]
    extract_entities = _objs["extract_entities"]
    build_clarification = _objs["build_clarification_question"]
    build_dynamic_response = _objs["build_dynamic_response"]
    NLU_ENGINE_AVAILABLE = True
    CLARIFICATION_AVAILABLE = True
    print("[MODULE] nlu_engine LOADED (process_nlu, memory, clarification, dynamic response)")
else:
    # Coba muat subset tanpa clarification bila nama beda
    _core_names = [
        "process_nlu", "update_memory", "get_memory",
        "normalize_nlu", "extract_entities", "build_dynamic_response",
    ]
    _objs2, _err2 = _try_import(["modules.nlu_engine", "nlu_engine"], _core_names)
    if _objs2:
        process_nlu = _objs2["process_nlu"]
        update_memory = _objs2["update_memory"]
        get_memory = _objs2["get_memory"]
        normalize_nlu = _objs2["normalize_nlu"]
        extract_entities = _objs2["extract_entities"]
        build_dynamic_response = _objs2["build_dynamic_response"]
        NLU_ENGINE_AVAILABLE = True
        CLARIFICATION_AVAILABLE = False
        print(f"[MODULE] nlu_engine PARTIAL LOADED (clarification DISABLED: {_err})")
    else:
        print(f"[MODULE] nlu_engine DISABLED: {_err2 or _err}")

# Alias opsional build_clarification jika diekspor dengan nama lama
if NLU_ENGINE_AVAILABLE and build_clarification is None:
    _alias, _ = _try_import(["modules.nlu_engine", "nlu_engine"], ["build_clarification"])
    if _alias:
        build_clarification = _alias["build_clarification"]
        CLARIFICATION_AVAILABLE = True

# --- typo_corrector (independen dari NLU) ---
_objs, _err = _try_import(["modules.typo_corrector", "typo_corrector"], ["correct_text"])
if _objs:
    typo_correct_text = _objs["correct_text"]
    TYPO_CORRECTOR_AVAILABLE = True
    print("[MODULE] typo_corrector LOADED")
else:
    print(f"[MODULE] typo_corrector DISABLED: {_err}")

# --- response_orchestrator (independen dari NLU) ---
_objs, _err = _try_import(
    ["modules.response_orchestrator", "response_orchestrator"],
    ["orchestrate_multi_intent_response"],
)
if _objs:
    orchestrate_multi_intent_response = _objs["orchestrate_multi_intent_response"]
    RESPONSE_ORCHESTRATOR_AVAILABLE = True
    print("[MODULE] response_orchestrator LOADED")
else:
    orchestrate_multi_intent_response = None
    print(f"[MODULE] response_orchestrator DISABLED: {_err}")

# --- knowledge_retriever (Exact QA / SSOT answer ranking — config-driven) ---
KNOWLEDGE_RETRIEVER_AVAILABLE = False
select_knowledge_answer = None
select_best_variant = None
pick_academy_response_ranked = None
rank_topic_candidates = None
get_kb_fallback_message = None
_objs, _err = _try_import(
    ["modules.knowledge_retriever", "knowledge_retriever"],
    [
        "select_knowledge_answer",
        "select_best_variant",
        "pick_academy_response",
        "rank_topic_candidates",
        "get_fallback_message",
    ],
)
if _objs:
    select_knowledge_answer = _objs["select_knowledge_answer"]
    select_best_variant = _objs["select_best_variant"]
    pick_academy_response_ranked = _objs["pick_academy_response"]
    rank_topic_candidates = _objs["rank_topic_candidates"]
    get_kb_fallback_message = _objs["get_fallback_message"]
    KNOWLEDGE_RETRIEVER_AVAILABLE = True
    print("[MODULE] knowledge_retriever LOADED")
else:
    print(f"[MODULE] knowledge_retriever DISABLED: {_err}")

print(
    "[MODULE] STATUS | "
    f"NLU={NLU_ENGINE_AVAILABLE} | Clarification={CLARIFICATION_AVAILABLE} | "
    f"Typo={TYPO_CORRECTOR_AVAILABLE} | Orchestrator={RESPONSE_ORCHESTRATOR_AVAILABLE} | "
    f"KnowledgeRetriever={KNOWLEDGE_RETRIEVER_AVAILABLE}"
)

# Path ke file data dan model
DATA_DIR   = os.path.join(BASE_DIR, 'data')
MODELS_DIR = os.path.join(BASE_DIR, 'models')
NLP_PIPELINE_DIR = os.path.join(BASE_DIR, 'nlp_pipeline')

FAQ_PATH          = os.path.join(DATA_DIR, 'faq.json')
ACADEMY_JSON_PATH = os.path.join(DATA_DIR, 'academy_enhanced.json')

try:
    with open(ACADEMY_JSON_PATH, "r", encoding="utf-8") as f:
        ACADEMY_DATA = json.load(f)
except Exception as e:
    ACADEMY_DATA = {"responses": {}, "keywords": {}, "intents": {}}
    print(f"Warning: academy_enhanced.json loading notice: {e}")
LABEL_MAPPING_PATH = os.path.join(DATA_DIR, 'label_mapping.json')
MODEL_PATH        = os.path.join(MODELS_DIR, 'intent_classifier_model.h5')

# Path model Naive Bayes & TF-IDF Vectorizer
NB_MODEL_PATH = os.path.join(NLP_PIPELINE_DIR, 'nb_model.pkl')
TFIDF_PATH = os.path.join(NLP_PIPELINE_DIR, 'tfidf_vectorizer.pkl')

try:
    nb_model = joblib.load(NB_MODEL_PATH)
    tfidf_vectorizer = joblib.load(TFIDF_PATH)
    NB_MODEL_LOADED = True
    print("SUCCESS: Model Naive Bayes & TF-IDF Vectorizer berhasil dimuat ke app.py!")
except Exception as e:
    nb_model = None
    tfidf_vectorizer = None
    NB_MODEL_LOADED = False
    print(f"NOTICE: Loading Model Naive Bayes notice: {e}")

# TensorFlow (opsional; dipakai training & preprocessing teks)
try:
    import tensorflow as tf
    tf_processor = TensorFlowDataProcessor()
except Exception as e:
    tf = None
    tf_processor = None
    print(f"NOTICE: TensorFlow tidak tersedia: {e}")

# ========================= CONFIGURATION =========================
WHATSAPP_ADMIN = "6281214237742"
# Default XAMPP/Apache: http://127.0.0.1/api/chatbot
# Override via .env LARAVEL_API_BASE (contoh artisan serve: http://127.0.0.1:8000/api/chatbot)
LARAVEL_API_BASE = os.getenv("LARAVEL_API_BASE", "http://127.0.0.1/api/chatbot").rstrip("/")

# ========================= HELPER FUNCTIONS =========================
# Initialize data augmentation
data_augmenter = EasyDataAugmented(augmentation_prob=0.3)

def normalize_text(text: str) -> str:
    """Normalize text for exact Q&A / intent matching — basic cleaning only (V3)."""
    import re
    text = text.lower().strip()
    text = re.sub(r"[?!.:,;/\-]", " ", text)
    text = re.sub(r"\s+", " ", text)
    return text.strip()

TECHNICAL_SUPPORT_KEYWORDS = {
    "otp", "kode", "verifikasi", "login", "masuk", "gagal", "error",
    "akun", "reset", "password", "lupa", "tidak bisa",
}

def is_technical_support_query(text: str) -> bool:
    """Detect technical support issues (OTP/login/account access)."""
    normalized = normalize_text(text)
    words = set(normalized.split())
    if not words:
        return False

    # Require at least one strong signal to avoid over-triggering.
    strong_signals = {"otp", "verifikasi", "login", "password", "akun"}
    has_signal = bool(words & strong_signals)
    has_support_context = bool(words & TECHNICAL_SUPPORT_KEYWORDS)
    return has_signal and has_support_context

def augment_text_for_training(text: str) -> str:
    """Apply data augmentation for training data"""
    augmented_texts = data_augmenter.augment_text(text, ['sr', 'ri', 'rs', 'rd'])
    # Return random augmented version
    if len(augmented_texts) > 1:
        return random.choice(augmented_texts[1:])  # Skip original
    return text

def build_whatsapp_url(message: str) -> str:
    """Build WhatsApp URL with pre-filled message"""
    return f"https://wa.me/{WHATSAPP_ADMIN}?text={quote(message)}"

# =========================
# MODEL RECOMMENDATION RESPONSES
# =========================
model_recommendation_responses = {
    "general": [
        "Untuk kebutuhan model, saya dapat membantu menampilkan beberapa model yang tersedia sesuai kategori yang Anda butuhkan. Namun, pemilihan model terbaik tetap disesuaikan dengan konsep, tema, dan brief yang diinginkan. Untuk rekomendasi final, admin kami siap membantu memilih model yang paling sesuai."
    ],
    "kids_teens": [
        "Untuk kategori anak-anak dan remaja, saya dapat membantu menampilkan beberapa model yang tersedia. Namun, pemilihan model terbaik tetap perlu disesuaikan dengan konsep, tema, dan kebutuhan visual yang diinginkan. Untuk rekomendasi final, admin kami dapat membantu memilihkan model yang paling sesuai."
    ],
    "sea_beach": [
        "Untuk kebutuhan model dengan tema laut atau pantai, saya dapat membantu menampilkan beberapa model yang tersedia. Namun, pemilihan model terbaik tetap perlu disesuaikan dengan konsep visual, ekspresi, styling, dan brief yang Anda inginkan. Untuk rekomendasi final, admin kami siap membantu memilihkan model yang paling sesuai."
    ],
    "fashion_show": [
        "Untuk kebutuhan fashion show, saya dapat membantu menampilkan beberapa model yang tersedia sesuai kategori yang dibutuhkan. Namun, pemilihan model terbaik tetap perlu disesuaikan dengan karakter busana, tema acara, dan kebutuhan runway. Untuk rekomendasi final, admin kami akan membantu memilihkan model yang paling sesuai."
    ],
    "formal": [
        "Untuk kebutuhan model dengan tema formal atau elegan, saya dapat membantu menampilkan beberapa model yang tersedia. Namun, pemilihan model terbaik tetap disesuaikan dengan konsep, karakter visual, dan brief yang diinginkan. Untuk rekomendasi final, admin kami dapat membantu memilih model yang paling sesuai."
    ],
    "catalog_kids": [
        "Untuk kebutuhan model anak-anak pada photoshoot produk atau katalog, saya dapat membantu menampilkan beberapa model yang tersedia. Namun, pemilihan model terbaik tetap perlu disesuaikan dengan konsep brand, target pasar, dan brief visual yang diinginkan. Untuk rekomendasi final, admin kami siap membantu memilihkan model yang paling sesuai."
    ],
    "beauty": [
        "Untuk kebutuhan model pada tema beauty atau makeup, saya dapat membantu menampilkan beberapa model yang tersedia. Namun, pemilihan model terbaik tetap perlu disesuaikan dengan konsep campaign, karakter wajah, dan kebutuhan visual yang diinginkan. Untuk rekomendasi final, admin kami akan membantu memilihkan model yang paling sesuai."
    ],
    "casual": [
        "Untuk kebutuhan model dengan tema casual atau lifestyle, saya dapat membantu menampilkan beberapa model yang tersedia. Namun, pemilihan model terbaik tetap disesuaikan dengan konsep, ekspresi, dan brief visual yang diinginkan. Untuk rekomendasi final, admin kami siap membantu memilihkan model yang paling sesuai."
    ],
    "brand": [
        "Untuk kebutuhan model pada kegiatan promosi atau campaign brand, saya dapat membantu menampilkan beberapa model yang tersedia. Namun, pemilihan model terbaik tetap perlu disesuaikan dengan identitas brand, target audiens, dan kebutuhan visual campaign. Untuk rekomendasi final, admin kami dapat membantu memilih model yang paling sesuai."
    ],
    "shortlist": [
        "Saya dapat membantu menampilkan beberapa model yang tersedia sebagai bahan pertimbangan. Namun, untuk menentukan model yang paling sesuai dengan konsep, tema, dan kebutuhan Anda, admin kami akan membantu memberikan rekomendasi final serta shortlist terbaik."
    ]
}

# =========================
# STATIC INTENTS DICTIONARY
# Static Responses Dictionary for consistent answers
STATIC_RESPONSES = {
    "greeting": [
        "Halo! Saya adalah asisten virtual Academy Next Top Model. Saya siap membantu Anda dengan informasi seputar program pelatihan, pendaftaran, jadwal, dan layanan lainnya. Ada yang bisa saya bantu hari ini?"
    ]
}

# Exact Q&A Dictionary for factual questions with specific answers
def _pick_academy_response(key: str, query: str = None) -> str:
    """Ambil jawaban dari knowledge academy_enhanced.json (tanpa mengarang).

    Jika query diberikan dan knowledge_retriever aktif, pilih field/varian
    paling spesifik berdasarkan ranking kemiripan (config-driven).
    """
    if KNOWLEDGE_RETRIEVER_AVAILABLE and pick_academy_response_ranked:
        text = pick_academy_response_ranked(key, ACADEMY_DATA, query=query)
        if text:
            return text

    alias_map = {
        "training_programs": "programs_info",
        "technical": "technical_support",
        "join_registration_info": "registration_steps",
        "academy_info": "about_academy"
    }
    canonical_key = alias_map.get(key, key)
    responses = (ACADEMY_DATA or {}).get("responses", {})
    raw = responses.get(canonical_key)
    variants = []
    if isinstance(raw, list) and raw:
        variants = [str(x).strip() for x in raw if str(x).strip()]
    elif isinstance(raw, dict):
        id_list = raw.get("id") or raw.get("en") or []
        if isinstance(id_list, list) and id_list:
            variants = [str(x).strip() for x in id_list if str(x).strip()]

    if not variants:
        return ""
    if query and KNOWLEDGE_RETRIEVER_AVAILABLE and select_best_variant:
        return select_best_variant(query, variants)
    return variants[0]

# Configuration-Driven Knowledge Query Aliases Engine
# Centralized mapping from exact query variants to canonical knowledge keys
KNOWLEDGE_QUERY_ALIASES = {
    # Greeting Aliases
    "halo": "greeting",
    "hai": "greeting",
    "hi": "greeting",
    "hello": "greeting",

    # Location Aliases
    "lokasinya di mana": "location_info",
    "lokasi academy di mana": "location_info",
    "alamat academy next top model di mana": "location_info",
    "tempat kelasnya di mana": "location_info",
    "dimana tempat pelatihannya": "location_info",
    "apakah lokasinya di mall tsm cibubur": "location_info",
    "academy ini ada di mana": "location_info",
    "lokasinya dimana ya": "location_info",
    "dimana academy": "location_info",
    "academy lokasi": "location_info",
    "tempat kelas dimana": "location_info",
    "lokasi": "location_info",
    "lokasi academy": "location_info",
    "alamat": "location_info",
    "tempat": "location_info",
    "alamat nya dimana": "location_info",
    "alamatnya dimana": "location_info",
    "alamat nya di mana": "location_info",
    "alamatnya di mana": "location_info",
    "alamat academy dimana": "location_info",
    "alamat academy di mana": "location_info",
    "alamat academy next top model dimana": "location_info",
    "alamat academy next top model di mana": "location_info",
    "alamat lengkapnya dimana": "location_info",
    "alamat lengkapnya di mana": "location_info",
    "alamatnya dimana ya": "location_info",
    "alamat academynya dimana": "location_info",
    "lokasi academy dimana": "location_info",
    "tempat academy dimana": "location_info",
    "tempat academy": "location_info",
    "lokasi academynya dimana": "location_info",

    # Become Model / Panduan Aliases
    "panduan jadi model": "become_model_info",
    "cara jadi model": "become_model_info",
    "cara jadi model profesional": "become_model_info",
    "aku pengen jadi model mulai dari mana": "become_model_info",
    "saya pengen jadi model mulai dari mana": "become_model_info",
    "aku ingin jadi model mulai dari mana": "become_model_info",

    # Registration Steps Aliases
    "bagaimana cara daftar": "registration_steps",
    "cara daftar bagaimana": "registration_steps",
    "cara join": "registration_steps",
    "cara join academy": "registration_steps",
    "proses pendaftaran": "registration_steps",
    "gimana cara daftar": "registration_steps",
    "gimana cara daftar kak": "registration_steps",
    "bagaimana cara mendaftar di academy next top model": "registration_steps",
    "saya mau join gimana caranya": "registration_steps",
    "aku mau join nih caranya gimana": "registration_steps",
    "saya ingin bergabung bagaimana caranya": "registration_steps",
    "mau gabung caranya gimana": "registration_steps",
    "saya mau masuk academy prosesnya bagaimana": "registration_steps",
    "pendaftaran": "registration_steps",
    "cara daftar": "registration_steps",

    # Registration Requirements Aliases
    "apa syarat pendaftaran": "registration_requirements",
    "syarat pendaftaran": "registration_requirements",
    "syarat": "registration_requirements",
    "syarat pendaftaran apa saja": "registration_requirements",
    "apakah harus upload foto close up dan full body": "registration_requirements",
    "setelah daftar langkah selanjutnya apa": "registration_steps",
    "apakah pendaftaran bisa online": "registration_steps",

    # Photo Upload Aliases
    "cara upload foto": "photo_upload",
    "bagaimana cara mengunggah foto": "photo_upload",
    "format foto close up dan full body": "photo_upload",

    # Schedule Aliases
    "jadwal academy kapan saja": "schedule_info",
    "jadwal academy kapan": "schedule_info",
    "jadwal kelas kapan": "schedule_info",
    "kelas diadakan hari apa": "schedule_info",
    "jadwalnya setiap hari apa": "schedule_info",
    "jam berapa kelas dimulai": "schedule_info",
    "kelasnya jam berapa": "schedule_info",
    "kelasnya kapan sih": "schedule_info",
    "apakah kelas berlangsung setiap hari sabtu": "schedule_info",

    # Sensitive Pricing Aliases
    "berapa biaya pendaftaran": "pricing_sensitive",
    "biaya pendaftaran berapa": "pricing_sensitive",
    "apakah diskon masih ada": "pricing_sensitive",
    "pembayaran dilakukan ke siapa": "pricing_sensitive",
    "bayarnya per bulan atau mingguan": "pricing_sensitive",
    "berapa fee model": "pricing_sensitive",
    "berapa biaya kontrak model": "pricing_sensitive",

    # Contact Admin Aliases
    "adminnya bisa dihubungi lewat wa kan": "contact_admin",
    "admin bisa dihubungi lewat wa kan": "contact_admin",
    "admin bisa dihubungi lewat whatsapp kan": "contact_admin",
    "hubungi admin": "contact_admin",
    "kontak admin": "contact_admin",
    "nomor admin berapa": "contact_admin",
    "saya ingin tanya admin langsung": "contact_admin",

    # About Academy Aliases
    "apa itu academy next top model": "about_academy",
    "academy next top model itu apa": "about_academy",
    "apa sih academy next top model": "about_academy",
    "ceritakan tentang academy next top model": "about_academy",
    "academy next top model bergerak di bidang apa": "about_academy",
    "bidang academy next top model apa": "about_academy",
    "academy next top model berdiri sejak kapan": "about_academy",
    "sejak kapan academy next top model berdiri": "about_academy",
    "academy ini berdiri sejak kapan": "about_academy",
    "apa tujuan academy next top model": "about_academy",
    "tujuan academy next top model apa": "about_academy",
    "apa tujuan academy ini": "about_academy",

    # Owner / Founder Aliases
    "siapa owner academy next top model": "homepage_info",
    "siapa pemilik academy next top model": "homepage_info",
    "siapa founder academy next top model": "homepage_info",
    "siapa ceo academy next top model": "homepage_info",
    "siapa coach academy next top model": "homepage_info",
    "coach academy next top model siapa": "homepage_info",
    "siapa coach academy": "homepage_info",
    "coach academy siapa": "homepage_info",
    "siapakah oliviarie summer": "homepage_info",
    "oliviarie summer itu siapa": "homepage_info",
    "siapa sih oliviarie summer": "homepage_info",
    "siapa owner academy": "homepage_info",
    "siapa pemilik academy": "homepage_info",
    "siapa owner": "homepage_info",

    # Tagline Aliases
    "apa tagline academy next top model": "tagline_info",
    "tagline academy next top model apa": "tagline_info",
    "tagline academy apa": "tagline_info",
    "apa tagline academy": "tagline_info",

    # Program & Materi Aliases
    "program apa saja yang tersedia": "programs_info",
    "program yang tersedia apa saja": "programs_info",
    "materi kelas apa saja": "programs_info",
    "materi kelas ada apa saja": "programs_info",
    "materi pelatihan apa saja": "programs_info",
    "materi pelatihannya apa saja": "programs_info",
    "kelas apa saja yang ada di academy ini": "programs_info",
    "apakah ada catwalk basic": "programs_info",
    "apakah ada catwalk advanced": "programs_info",
    "apakah ada runway class": "programs_info",
    "apakah ada beauty class": "programs_info",
    "apakah ada pose class": "programs_info",
    "apakah ada photoshoot": "programs_info",
    "apakah ada self branding": "programs_info",
    "apakah ada public speaking": "programs_info",

    # Benefits Aliases
    "benefit dari ikut akademi ini apa ya": "benefits_joining",
    "benefit ikut academy ini apa": "benefits_joining",
    "apa manfaat ikut academy next top model": "benefits_joining",
    "apa manfaat ikut academy ini": "benefits_joining",
    "manfaat ikut academy apa": "benefits_joining",
    "kelebihan academy ini apa": "benefits_joining",
    "keuntungan ikut academy ini apa": "benefits_joining",

    # Out of scope Aliases
    "apa kabar": "out_of_scope_greeting",
    "cuaca hari ini bagaimana": "out_of_scope_weather",
    "siapa presiden indonesia": "out_of_scope_general",
}

def resolve_alias_knowledge(target_key: str, query: str = None) -> str:
    """Resolve canonical knowledge text from SSOT (ACADEMY_DATA) or fallback dictionary."""
    if target_key == "tagline_info":
        return "Tagline Academy Next Top Model adalah 'Be smart, Beautiful, And confident' serta 'Small step to make big dream come true'."
    if target_key == "out_of_scope_greeting":
        return "Saya siap membantu Anda mengenai informasi Academy Next Top Model. Silakan ajukan pertanyaan Anda."
    if target_key == "out_of_scope_weather":
        return "Saya fokus membantu informasi seputar Academy Next Top Model, seperti program, pendaftaran, jadwal, lokasi, model, benefit, dan kontak admin."
    if target_key == "out_of_scope_general":
        return "Saya fokus membantu informasi seputar Academy Next Top Model. Jika Anda ingin mengetahui program, pendaftaran, model, atau jadwal, saya siap membantu."
    if target_key == "greeting":
        return "Halo. Saya adalah asisten virtual Academy Next Top Model. Saya dapat membantu informasi seputar program, pendaftaran, jadwal, lokasi, model, benefit, dan kontak admin."

    text = _pick_academy_response(target_key, query=query)
    if text:
        return text

    if get_kb_fallback_message:
        return get_kb_fallback_message(ACADEMY_DATA)
    return (
        "Maaf, informasi mengenai hal tersebut belum tersedia di knowledge base kami. "
        "Silakan hubungi admin Academy Next Top Model melalui WhatsApp untuk bantuan lebih lanjut."
    )

# Dynamic EXACT_QA generator ensuring 100% Single Source of Truth
# Simpan target key (bukan jawaban final) agar jawaban bisa di-rank per query saat runtime.
def build_exact_qa_dict() -> dict:
    qa = {}
    for alias_query, target_key in KNOWLEDGE_QUERY_ALIASES.items():
        # Nilai tetap string jawaban untuk kompatibilitas, di-resolve ulang saat chat
        qa[alias_query] = resolve_alias_knowledge(target_key, query=alias_query)
    return qa

EXACT_QA = build_exact_qa_dict()
EXACT_QA_TARGETS = dict(KNOWLEDGE_QUERY_ALIASES)


def resolve_exact_qa_reply(matched_key: str, user_query: str = None) -> str:
    """Exact QA → resolve SSOT dengan ranking spesifik terhadap pertanyaan pengguna."""
    target = EXACT_QA_TARGETS.get(matched_key)
    if target:
        return resolve_alias_knowledge(target, query=user_query or matched_key)
    # Fallback ke jawaban pra-bangun
    return EXACT_QA.get(matched_key, "")

def get_static_response(intent):
    """Get static response by intent"""
    responses = STATIC_RESPONSES.get(intent, [])
    return responses[0] if responses else "Maaf, saya belum menemukan jawaban yang sesuai."

# Static Intents Dictionary for Q&A
static_intents = {
    "become_model_info": {
        "keywords": [
            "gimana caranya jadi model profesional",
            "bagaimana cara menjadi model profesional",
            "apa yang harus dilakukan untuk jadi model",
            "tips jadi model profesional apa saja",
            "bagaimana memulai karier modeling",
            "apa yang harus disiapkan untuk jadi model",
            "bagaimana meningkatkan kemampuan modeling",
            "apakah academy next top model bisa membantu saya jadi model profesional",
            "aku pengen jadi model mulai dari mana",
            "aku pengen jadi model mulai dari mana"
        ],
        "responses": [
            "Panduan menjadi model di Academy Next Top Model:\n1. Daftar online melalui halaman Register di website.\n2. Lengkapi syarat pendaftaran: usia start 4th-25th, mengisi formulir, menyiapkan foto close-up dan full body, serta melakukan pembayaran registrasi.\n3. Ikuti program pelatihan: Catwalk Basic, Catwalk Advanced, Runway Class, Beauty Class, Pose Class, Photoshoot, Self Branding, dan Public Speaking.\n4. Lengkapi profil dan bangun portofolio digital di website.\n5. Kelas berlangsung setiap hari Sabtu pukul 11.00 sampai 15.00 di Mall TSM Cibubur.\nUntuk informasi biaya atau promo, silakan hubungi admin melalui WhatsApp."
        ]
    },
    "ambiguous_help": {
        "keywords": [
            "bisa bantu saya",
            "tolong jelaskan dong",
            "saya mau tanya",
            "ada info",
            "gimana ya",
            "bisa dibantu",
            "tolong kasih penjelasan",
            "saya bingung",
            "saya ingin informasi",
            "ada yang bisa dijelaskan",
            "ada info"
        ],
        "responses": [
            "Tentu. Saya dapat membantu informasi seputar Academy Next Top Model, seperti program, pendaftaran, jadwal, lokasi, data model, benefit, dan kontak admin.",
            "Silakan sampaikan informasi apa yang ingin Anda ketahui, misalnya tentang program, pendaftaran, jadwal, lokasi, model, atau kontak admin.",
            "Saya siap membantu menjelaskan informasi tentang Academy Next Top Model. Silakan ajukan pertanyaan Anda.",
            "Tentu. Silakan sampaikan informasi apa yang ingin Anda ketahui, misalnya tentang program, pendaftaran, jadwal, lokasi, model, atau kontak admin.",
            "Tidak apa-apa. Saya bisa membantu menjelaskan informasi tentang Academy Next Top Model. Silakan sampaikan pertanyaan Anda, misalnya tentang program, pendaftaran, jadwal, lokasi, atau data model."
        ]
    },
    "out_of_scope": {
        "keywords": [
            "apa kabar",
            "cuaca hari ini bagaimana",
            "siapa presiden indonesia",
            "tolong bikin puisi",
            "rekomendasi laptop bagus",
            "jam sekarang berapa",
            "siapa pemain bola terbaik",
            "tolong kerjakan matematika saya",
            "berita hari ini apa",
            "kamu suka musik",
            "apa kabar",
            "cuaca hari ini bagaimana",
            "siapa presiden indonesia"
        ],
        "responses": [
            "Saya fokus membantu informasi seputar Academy Next Top Model, seperti program, pendaftaran, jadwal, lokasi, model, benefit, dan kontak admin.",
            "Saya adalah asisten virtual Academy Next Top Model. Jika Anda ingin mengetahui informasi tentang academy, saya siap membantu.",
            "Saya fokus pada informasi Academy Next Top Model. Silakan ajukan pertanyaan seputar program, pendaftaran, model, jadwal, atau admin.",
            "Saya siap membantu Anda mengenai informasi Academy Next Top Model. Silakan ajukan pertanyaan Anda.",
            "Saya fokus membantu informasi seputar Academy Next Top Model, seperti program, pendaftaran, jadwal, model, dan kontak admin.",
            "Saya fokus membantu informasi seputar Academy Next Top Model. Jika Anda ingin mengetahui program, pendaftaran, model, atau jadwal, saya siap membantu."
        ]
    },
    "edge_case": {
        "keywords": [
            "nazwa",
            "daftar",
            "owner",
            "model",
            "tersedia",
            "halo",
            "hi",
            "???",
            "pesan kosong",
            "biaya",
            "diskon",
            "promo"
        ],
        "responses": [
            "Apakah Anda ingin mengecek apakah model Nazwa tersedia di website Academy Next Top Model?",
            "Jika Anda ingin mendaftar, saya dapat menjelaskan langkah pendaftaran di Academy Next Top Model.",
            "Untuk informasi biaya, silakan hubungi admin Academy Next Top Model melalui WhatsApp agar mendapatkan informasi terbaru.",
            "Owner Academy Next Top Model adalah Oliviarie Summer.",
            "Saya dapat membantu menampilkan daftar model, jumlah model, atau mengecek status model tertentu. Silakan sebutkan nama model yang ingin Anda cari.",
            "Silakan sebutkan nama model yang ingin Anda cek status ketersediaannya.",
            "Halo. Saya adalah asisten virtual Academy Next Top Model. Saya dapat membantu informasi seputar program, pendaftaran, jadwal, lokasi, model, dan kontak admin.",
            "Pesan tidak boleh kosong. Silakan ketik pertanyaan Anda.",
            "Untuk memastikan promo atau diskon masih aktif, silakan hubungi admin Academy Next Top Model melalui WhatsApp agar mendapatkan informasi terbaru.",
            "Owner Academy Next Top Model adalah Oliviarie Summer.",
            "Model Nazwa Audina saat ini terdaftar di website Academy Next Top Model.",
            "Ya, admin Academy Next Top Model dapat dihubungi melalui WhatsApp."
        ]
    }
}

# =========================
# CONFIGURATION
# =========================
load_dotenv()

# Configuration
CHATBOT_MODE = os.getenv("CHATBOT_MODE", "openai")
WHATSAPP_ADMIN = os.getenv("WHATSAPP_ADMIN", "+6281214237742")

# OpenAI configuration
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY", "")
print("DEBUG: OPENAI KEY LOADED:", bool(OPENAI_API_KEY))
print("DEBUG: OPENAI KEY VALUE:", OPENAI_API_KEY[:10] + "..." if OPENAI_API_KEY else "None")

if OPENAI_API_KEY and OPENAI_API_KEY.startswith("sk-"):
    openai_client = OpenAI(api_key=OPENAI_API_KEY)
    print("DEBUG: OpenAI client initialized successfully")
else:
    openai_client = None
    print("DEBUG: OpenAI client not initialized - invalid or missing key")

# =========================
# DATABASE CONFIGURATION & REALTIME QUERYING
# ============================================
try:
    import pymysql
except ImportError:
    pymysql = None

import logging
logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_NAME = os.getenv("DB_DATABASE", "academyntm")
DB_USER = os.getenv("DB_USERNAME", "root")
DB_PASS = os.getenv("DB_PASSWORD", "")

if create_engine:
    try:
        engine = create_engine(
            f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME}",
            pool_pre_ping=True
        )
    except Exception as e:
        print(f"DATABASE CONNECTION NOTICE: {e}")
        engine = None
else:
    engine = None

# =========================
# LOAD DATASETS
# =========================
# ACADEMY_DATA loaded early at path setup

try:
    with open(FAQ_PATH, "r", encoding="utf-8") as f:
        _faq_raw = json.load(f)
    if isinstance(_faq_raw, dict) and isinstance(_faq_raw.get("faqs"), list):
        FAQ_DATA = _faq_raw["faqs"]
    elif isinstance(_faq_raw, list):
        FAQ_DATA = _faq_raw
    else:
        FAQ_DATA = []
except FileNotFoundError:
    FAQ_DATA = []
    print(f"Warning: faq.json not found at {FAQ_PATH}")

# =========================
# CATATAN (audit Tahap 5): blok ini menimpa blok inisialisasi openai_client
# sebelumnya (baris ~781, yang mewajibkan prefix "sk-") TANPA validasi format
# apa pun — kemungkinan bug tersembunyi (validasi "sk-" jadi sia-sia). Belum
# diperbaiki, di luar scope Tahap 5 (murni logging), perlu diaudit terpisah.
try:
    from openai import OpenAI
    openai_client = OpenAI(api_key=OPENAI_API_KEY) if OPENAI_API_KEY else None
except ImportError:
    OpenAI = None
    openai_client = None

# ============================================
# REAL-TIME DATABASE RETRIEVAL FUNCTIONS
# ============================================
def query_db_models():
    """Direct real-time MySQL database retrieval with fallback to Laravel API endpoint."""
    models = []
    # 1. Try Direct MySQL connection via PyMySQL
    if pymysql:
        try:
            conn = pymysql.connect(
                host=DB_HOST,
                port=DB_PORT,
                user=DB_USER,
                password=DB_PASS,
                database=DB_NAME,
                connect_timeout=3,
                cursorclass=pymysql.cursors.DictCursor
            )
            with conn.cursor() as cursor:
                cursor.execute("""
                    SELECT id_model, nama_model, city, age, height, weight, categories, status, photo 
                    FROM models 
                    ORDER BY id_model ASC
                """)
                models = cursor.fetchall()
            conn.close()
            if models:
                logging.info(f"[DB REALTIME SUCCESS] Retrieved {len(models)} models directly from MySQL database '{DB_NAME}'.")
                return models
        except Exception as e:
            logging.warning(f"[DB REALTIME NOTICE] Direct MySQL query to '{DB_NAME}' notice: {e}. Trying Laravel API.")

    # 2. Fallback to Laravel API endpoint
    try:
        response = requests.get(f"{LARAVEL_API_BASE}/models", timeout=5)
        if response.status_code == 200:
            data = response.json()
            if data.get("success"):
                models = data.get("models", [])
                logging.info(f"[DB API SUCCESS] Retrieved {len(models)} models via Laravel API.")
                return models
    except Exception as e:
        logging.error(f"[DB API ERROR] Laravel API request failed: {e}")

    return []

def get_models_count():
    """Get total model count in real-time."""
    models = query_db_models()
    return len(models)

def search_model_status(model_name: str):
    """Search specific model in database by name."""
    models = query_db_models()
    if not models:
        return None
    
    clean_name = normalize_text(model_name)
    found = find_model_flexible(clean_name, models)
    if found:
        return {"success": True, "model": found}
    return {"success": False, "message": "Model tidak ditemukan"}

def detect_age_category(message: str):
    """Detect age categories from message"""
    message = normalize_text(message)

    is_kids = any(k in message for k in ["anak", "anak anak", "anak-anak", "kids", "children"])
    is_teens = any(k in message for k in ["remaja", "teen", "teens"])

    if is_kids and is_teens:
        return ["Kids", "Teens"]
    elif is_kids:
        return ["Kids"]
    elif is_teens:
        return ["Teens"]
    return []


# Kota yang muncul di data model (substring match, config ringan di kode existing)
_KNOWN_MODEL_CITIES = [
    "bekasi", "bogor", "depok", "tangerang", "jakarta", "bandung", "cibubur",
]

# Token yang BUKAN nama model (hindari misroute status)
_MODEL_NON_NAME_TOKENS = {
    "remaja", "anak", "kids", "teens", "teen", "children", "aktif", "available",
    "tersedia", "dari", "kota", "kategori", "usia", "umur", "berapa", "jumlah",
    "total", "daftar", "list", "semua", "yang", "ada", "di", "website", "tahun",
    "unavailable", "tidak", "status", "aktifkah", "berapa", "banyak", "model",
    "siapa", "saja", "apa", "untuk", "cari", "rekomendasi", "hire", "butuh",
} | set(_KNOWN_MODEL_CITIES)


def extract_model_filters(message: str) -> dict:
    """
    Parse filter natural language dari pertanyaan model (fungsi existing, diperluas).
    Mengembalikan dict: categories, city, available_only, age_min/max.
    """
    msg = normalize_text(message or "")
    filters = {
        "categories": [],
        "city": None,
        "available_only": False,
        "unavailable_only": False,
        "age_min": None,
        "age_max": None,
    }

    for cat in detect_age_category(msg):
        filters["categories"].append(cat.lower())  # kids / teens

    if any(k in msg for k in ["tidak tersedia", "unavailable", "tidak aktif", "nonaktif"]):
        filters["unavailable_only"] = True
    elif any(k in msg for k in ["aktif", "available", "tersedia"]):
        filters["available_only"] = True

    for city in _KNOWN_MODEL_CITIES:
        if city in msg:
            filters["city"] = city
            break
    if not filters["city"]:
        m = re.search(r"\bdari\s+([a-z]+)\b", msg)
        if m and m.group(1) not in {"mana", "mana", "sini", "sana", "website", "academy"}:
            filters["city"] = m.group(1)
        else:
            m2 = re.search(r"\b(?:di|kota)\s+([a-z]+)\b", msg)
            if m2 and m2.group(1) in _KNOWN_MODEL_CITIES:
                filters["city"] = m2.group(1)

    am = re.search(r"(?:usia|umur)\s*(\d+)(?:\s*(?:-|s/?d|sampai|hingga)\s*(\d+))?", msg)
    if am:
        filters["age_min"] = int(am.group(1))
        filters["age_max"] = int(am.group(2)) if am.group(2) else int(am.group(1))
        # Fallback rentang tanpa pemisah eksplisit: normalize_text() di atas sudah
        # menghapus '-' SEBELUM regex ini jalan, jadi "usia 10-15" terbaca sebagai
        # "usia 10 15" dan grup kedua (batas atas) gagal tertangkap oleh pola utama
        # di atas (BUG-10, audit Tahap 2/3). Kalau angka kedua menempel langsung
        # setelah angka pertama (tanpa pemisah kata lain di antaranya), tetap
        # perlakukan sebagai batas atas rentang.
        if am.group(2) is None:
            am2 = re.search(r"(?:usia|umur)\s*" + re.escape(am.group(1)) + r"\s+(\d+)\b", msg)
            if am2:
                filters["age_max"] = int(am2.group(1))

    return filters


def apply_model_filters(models: list, filters: dict) -> list:
    """Terapkan filter ke list model (in-memory, tanpa ubah schema)."""
    if not models:
        return []
    result = list(models)

    cats = [c.lower() for c in (filters.get("categories") or [])]
    if cats:
        allowed = set(cats)
        if "kids" in allowed:
            allowed.update({"children", "kid"})
        if "teens" in allowed:
            allowed.update({"teen", "remaja"})
        result = [
            m for m in result
            if str(m.get("categories") or "").strip().lower() in allowed
        ]

    city = (filters.get("city") or "").strip().lower()
    if city:
        result = [
            m for m in result
            if city in normalize_text(str(m.get("city") or ""))
        ]

    if filters.get("available_only"):
        result = [m for m in result if _is_available_status(m.get("status"))]
    if filters.get("unavailable_only"):
        result = [m for m in result if not _is_available_status(m.get("status"))]

    age_min = filters.get("age_min")
    age_max = filters.get("age_max")
    if age_min is not None:
        filtered = []
        for m in result:
            try:
                age = int(m.get("age"))
            except (TypeError, ValueError):
                continue
            hi = age_max if age_max is not None else age_min
            if age_min <= age <= hi:
                filtered.append(m)
        result = filtered

    return result


def _describe_model_filters(filters: dict) -> str:
    bits = []
    cats = filters.get("categories") or []
    if cats:
        label = {"kids": "anak/kids", "teens": "remaja/teens"}
        bits.append("kategori " + " & ".join(label.get(c, c) for c in cats))
    if filters.get("city"):
        bits.append("kota " + str(filters["city"]).title())
    if filters.get("available_only"):
        bits.append("status aktif/tersedia")
    if filters.get("unavailable_only"):
        bits.append("status tidak tersedia")
    if filters.get("age_min") is not None:
        if filters.get("age_max") and filters["age_max"] != filters["age_min"]:
            bits.append(f"usia {filters['age_min']}-{filters['age_max']}")
        else:
            bits.append(f"usia {filters['age_min']}")
    return ", ".join(bits)

def filter_models_by_criteria(message: str) -> dict:
    """Filter models based on criteria from user message (kategori/kota/status/usia)."""
    models = get_all_models()
    if not models:
        return {"success": False, "message": "Data model tidak tersedia"}

    filters = extract_model_filters(message)
    filtered_models = apply_model_filters(models, filters)

    # Shortlist: prefer available dalam hasil filter
    available_models = [m for m in filtered_models if _is_available_status(m.get("status"))]
    shortlist_src = available_models if available_models else filtered_models
    shortlist = shortlist_src[:5]

    return {
        "success": True,
        "filters": filters,
        "total_models": len(models),
        "filtered_count": len(filtered_models),
        "available_count": len(available_models),
        "shortlist": shortlist,
        "models": filtered_models,
    }

def search_faq(query: str):
    """Search FAQ for matching questions"""
    if not FAQ_DATA:
        return None
    
    query_lower = query.lower()
    for faq in FAQ_DATA:
        # Handle if FAQ item is string instead of dictionary
        if isinstance(faq, str):
            continue
        if not isinstance(faq, dict):
            continue

        questions = faq.get("questions") or []
        if not questions and faq.get("question"):
            questions = [faq.get("question")]

        matched = False
        for q in questions:
            q_norm = str(q or "").lower().strip()
            if not q_norm:
                continue
            if q_norm in query_lower or query_lower in q_norm:
                matched = True
                break

        if matched:
            return {
                'success': True,
                'reply': faq.get('answer', 'FAQ tidak ditemukan.'),
                'confidence': 0.85,
                'handoff': False,
                'intent': faq.get('intent', 'faq'),
                'source': 'faq_database'
            }
    
    return None

# =========================
# SMART LLM FALLBACK
# =========================
def _redact_secrets(text: str) -> str:
    """Hapus fragmen yang mirip API key dari teks sebelum di-log (Tahap 5).
    Beberapa error message OpenAI SDK menyertakan sebagian karakter key
    (mis. 'sk-abc***...xyz') — fungsi ini memastikan itu tidak pernah
    ikut tercetak ke log/console, baik pada kasus sukses maupun gagal."""
    if not text:
        return text
    return re.sub(r"sk-[A-Za-z0-9_\-*]{6,}", "[REDACTED]", str(text))


def smart_llm_fallback(original_message: str, processed_message: str) -> str:
    """Smart LLM fallback with sensitive data filtering"""
    # Check for sensitive keywords that should NOT use OpenAI
    sensitive_keywords = [
        'biaya', 'harga', 'bayar', 'pembayaran', 'kartu kredit', 'transfer', 'cash',
        'kontrak', 'syarat', 'perjanjian', 'fee', 'tarif', 'endorsement', 'booking',
        'reservasi', 'dp', 'uang muka', 'cicilan', 'kredit', 'diskon', 'promo',
        'nomor telepon', 'whatsapp', 'kontak', 'email', 'alamat', 'nomor hp'
    ]
    
    # If message contains sensitive info, use safe response only
    if any(keyword in processed_message.lower() for keyword in sensitive_keywords):
        print(f"DEBUG: Sensitive content detected, using safe fallback")
        return (
            "Untuk informasi mengenai biaya, kontak, atau detail pendaftaran, "
            "silakan menghubungi admin Academy Next Top Model melalui WhatsApp "
            "untuk mendapatkan informasi yang akurat dan terbaru."
        )
    
    # Only use OpenAI for general questions
    if CHATBOT_MODE == "openai" and openai_client:
        try:
            logging.info("[LLM_FALLBACK] Memanggil OpenAI model=gpt-4o-mini")
            context = f"""
            Kamu adalah asisten virtual Academy Next Top Model.
            Jawab dengan bahasa Indonesia yang sopan, natural, dan membantu.
            HANYA jawab pertanyaan umum tentang modeling, academy, atau program.
            JANGAN berikan informasi spesifik tentang biaya, kontak, atau data sensitif.
            Jika ditanya hal sensitif, arahkan ke admin.

            Pertanyaan user: {original_message}
            """

            response = openai_client.chat.completions.create(
                model="gpt-4o-mini",
                messages=[
                    {"role": "system", "content": context},
                    {"role": "user", "content": original_message}
                ],
                max_tokens=250,
                temperature=0.4
            )
            content = response.choices[0].message.content
            if content:
                logging.info("[LLM_FALLBACK] OpenAI sukses, panjang balasan=%d karakter", len(content))
                return content
        except Exception as e:
            # Klasifikasi jenis error (Tahap 5) — tidak mengubah reply ke user,
            # hanya menambah visibility di sisi server. Pesan error di-redaksi
            # dulu (_redact_secrets) karena beberapa error OpenAI SDK (mis.
            # AuthenticationError) menyertakan sebagian karakter API key.
            if isinstance(e, openai.AuthenticationError):
                error_type = "auth"
            elif isinstance(e, openai.RateLimitError):
                error_type = "rate_limit"
            elif isinstance(e, openai.APITimeoutError):
                error_type = "timeout"
            elif isinstance(e, openai.APIConnectionError):
                error_type = "network"
            else:
                error_type = "other"
            logging.error(
                "[LLM_FALLBACK] OpenAI gagal (jenis=%s): %s",
                error_type, _redact_secrets(str(e))
            )
    elif CHATBOT_MODE != "openai":
        logging.warning(
            "[LLM_FALLBACK] Dilewati — CHATBOT_MODE=%s (bukan 'openai')", CHATBOT_MODE
        )
    else:
        logging.warning(
            "[LLM_FALLBACK] Dilewati — openai_client tidak tersedia "
            "(key kosong/format tidak valid). CHATBOT_MODE=%s", CHATBOT_MODE
        )

    return (
        "Saya adalah asisten virtual Academy Next Top Model. "
        "Saya dapat membantu informasi seputar academy, program, dan modeling. "
        "Untuk informasi biaya atau pendaftaran, silakan hubungi admin kami."
    )

# =========================
# INTENT DETECTION FUNCTIONS
# =========================
def is_owner_query(message: str) -> bool:
    """Detect if message is asking about owner/founder"""
    message = normalize_text(message)
    owner_keywords = [
        "owner academy",
        "pemilik academy",
        "founder academy",
        "ceo academy",
        "coach academy",
        "siapa oliviarie summer",
        "oliviarie summer siapa",
        "oliviarie",
        "olivarie",
        "siapa owner",
        "siapa pemilik",
        "siapa founder",
        "siapa ceo"
    ]
    return any(k in message for k in owner_keywords)

def is_contact_admin_query(message: str) -> bool:
    """Detect if message is asking to contact admin"""
    message = normalize_text(message)
    keywords = [
        "hubungi admin",
        "kontak admin",
        "whatsapp admin",
        "nomor admin",
        "cara menghubungi admin"
    ]
    return any(k in message for k in keywords)

def detect_intent_by_keywords(message: str) -> dict:
    """Detect intent from static intents dictionary using keywords"""
    message_normalized = normalize_text(message)
    
    for intent_name, intent_data in static_intents.items():
        keywords = intent_data["keywords"]
        responses = intent_data["responses"]
        
        # Check if any keyword matches
        if any(keyword in message_normalized for keyword in keywords):
            return {
                "intent": intent_name,
                "responses": responses,
                "source": "static_dictionary"
            }
    
    return {"intent": None, "responses": [], "source": None}

def detect_recommendation_theme(message: str) -> str:
    """Detect recommendation theme from message"""
    msg = message.lower()

    if any(k in msg for k in ["anak", "anak-anak", "kids"]) and any(k in msg for k in ["remaja", "teen", "teens"]):
        if any(k in msg for k in ["laut", "pantai", "beach"]):
            return "sea_beach"
        return "kids_teens"

    if any(k in msg for k in ["laut", "pantai", "beach"]):
        return "sea_beach"

    if any(k in msg for k in ["fashion show", "runway", "catwalk"]):
        return "fashion_show"

    if any(k in msg for k in ["formal", "elegan", "elegant"]):
        return "formal"

    if any(k in msg for k in ["katalog", "catalog", "produk anak", "produk kids"]):
        return "catalog_kids"

    if any(k in msg for k in ["beauty", "makeup", "kosmetik", "skincare"]):
        return "beauty"

    if any(k in msg for k in ["casual", "lifestyle"]):
        return "casual"

    if any(k in msg for k in ["brand", "campaign", "promosi", "iklan"]):
        return "brand"

    return "general"

def is_greeting_query(message: str) -> bool:
    """Detect if message is a greeting"""
    msg = normalize_text(message)
    return msg in ["halo", "hai", "hi", "hello"]

def is_about_academy_query(message: str) -> bool:
    """Detect if message is asking about academy info - exact match for important questions"""
    msg = normalize_text(message)
    keywords = [
        "apa itu academy next top model",
        "academy next top model bergerak di bidang apa",
        "ceritakan tentang academy next top model",
        "academy next top model berdiri sejak kapan",
        "apa tujuan academy next top model"
    ]
    return any(k == msg for k in keywords) or any(k in msg for k in keywords)

def is_homepage_info_query(message: str) -> bool:
    """Detect if message is asking about homepage info - exact match for important questions"""
    msg = normalize_text(message)
    keywords = [
        "siapa owner academy",
        "siapa pemilik academy",
        "siapa founder academy",
        "siapa ceo academy",
        "siapakah oliviarie summer",
        "apa tagline academy"
    ]
    return any(k == msg for k in keywords) or any(k in msg for k in keywords)

def is_academy_definition_query(message: str) -> bool:
    """Detect if message is asking about academy definition"""
    msg = normalize_text(message)
    keywords = [
        "apa itu academy next top model",
        "ceritakan tentang academy next top model"
    ]
    return any(k in msg for k in keywords)

def is_academy_field_query(message: str) -> bool:
    """Detect if message is asking about academy field"""
    msg = normalize_text(message)
    keywords = [
        "academy next top model bergerak di bidang apa",
        "bergerak di bidang apa"
    ]
    return any(k in msg for k in keywords)

def is_academy_founded_query(message: str) -> bool:
    """Detect if message is asking about academy founding year"""
    msg = normalize_text(message)
    keywords = [
        "academy next top model berdiri sejak kapan",
        "berdiri sejak kapan",
        "sejak kapan academy next top model berdiri"
    ]
    return any(k in msg for k in keywords)

def is_academy_purpose_query(message: str) -> bool:
    """Detect if message is asking about academy purpose"""
    msg = normalize_text(message)
    keywords = [
        "apa tujuan academy next top model",
        "tujuan academy next top model",
        "apa tujuan academy ini"
    ]
    return any(k in msg for k in keywords)

def is_about_query(message: str) -> bool:
    """Detect if message is asking about academy info"""
    message = message.lower()
    triggers = [
        "apa itu academy",
        "tentang academy",
        "ceritakan tentang academy",
        "academy next top model itu apa"
    ]
    return any(t in message for t in triggers)

# ========================= MODEL HANDLER FUNCTIONS =========================
def get_all_models():
    """Ambil data model real-time: MySQL langsung, fallback Laravel API."""
    return query_db_models()


def _is_available_status(status) -> bool:
    s = str(status or "").strip().lower()
    return s in {"available", "aktif", "tersedia", "active", "1", "true"}


def search_model_via_api(model_name: str):
    """Cari model via Laravel GET /api/chatbot/models/search?q=..."""
    name = (model_name or "").strip()
    if not name:
        return None
    try:
        response = requests.get(
            f"{LARAVEL_API_BASE}/models/search",
            params={"q": name},
            timeout=5,
        )
        if response.status_code != 200:
            return None
        data = response.json() or {}
        if data.get("success") and data.get("found") and data.get("model"):
            return data["model"]
    except Exception as e:
        print(f"search_model_via_api error: {e}")
    return None


def extract_model_name(query: str) -> str:
    """Extract model name from query"""
    q = normalize_text(query)

    patterns = [
        r"nama\s+(.+)",
        r"model\s+(.+?)\s+masih",
        r"model\s+(.+?)\s+sedang",
        r"apakah ada model dengan nama\s+(.+)",
        r"adakah model dengan nama\s+(.+)",
        r"apakah model\s+(.+?)\s+sedang",
        r"ada model yang bernama\s+(.+)",
        r"adakah model yang bernama\s+(.+)",
        r"model yang bernama\s+(.+)",
        r"adakah model\s+(.+)",
        r"apakah ada model\s+(.+)",
        r"ada model\s+(.+?)\s+nggak",
        r"ada model\s+(.+)",
        r"status model\s+(.+)",
        r"cek model\s+(.+)",
    ]

    for p in patterns:
        m = re.search(p, q)
        if m:
            name = m.group(1).strip()
            name = re.sub(r"\b(nggak|tidak|ya|kah|di website|tersedia|available)\b", "", name).strip()
            if name:
                return name

    stopwords = {
        "apakah", "adakah", "ada", "model", "dengan", "nama", "yang", "sedang",
        "masih", "available", "nggak", "tidak", "status", "di", "website", "itu",
        "cek", "cari", "bernama", "kah", "aktif", "tersedia", "remaja", "anak",
        "kids", "teens", "kategori", "kota", "dari", "berapa", "jumlah",
    } | set(_KNOWN_MODEL_CITIES)
    words = [w for w in q.split() if w not in stopwords]
    return " ".join(words).strip()

def find_model_flexible(model_name: str, models: list):
    """Find model by flexible matching"""
    if not model_name:
        return None

    target = normalize_text(model_name)

    for m in models:
        name = normalize_text(m.get("nama_model") or m.get("name") or "")
        if name == target:
            return m

    for m in models:
        name = normalize_text(m.get("nama_model") or m.get("name") or "")
        if target in name or name in target:
            return m

    target_words = set(target.split())
    best = None
    best_score = 0

    for m in models:
        name = normalize_text(m.get("nama_model") or m.get("name") or "")
        words = set(name.split())
        score = len(target_words & words)
        if score > best_score:
            best = m
            best_score = score

    return best if best_score >= 1 else None

def handle_model_list(query: str = ""):
    """Handle model list / available models query from DB (dengan filter NL)."""
    models = get_all_models()
    if not models:
        # BUG-9 (audit Tahap 2/4): DB/API tidak terjangkau — kegagalan sistem,
        # bukan jawaban valid, jadi confidence harus rendah.
        return {
            "reply": "Saat ini data model di database tidak dapat diakses. Pastikan MySQL/Laravel API aktif, atau periksa menu Model pada website.",
            "intent": "model_list",
            "source": "database_realtime",
            "confidence": 0.3,
            "handoff": False
        }

    filters = extract_model_filters(query)
    filtered = apply_model_filters(models, filters)

    # Tanpa filter status eksplisit: prefer yang available; jika filter sudah available_only, pakai filtered apa adanya
    if filters.get("available_only") or filters.get("unavailable_only") or filters.get("categories") or filters.get("city") or filters.get("age_min") is not None:
        display_list = filtered
    else:
        available_models = [m for m in filtered if _is_available_status(m.get("status"))]
        display_list = available_models if available_models else filtered

    filter_desc = _describe_model_filters(filters)
    scope = f" dengan filter {filter_desc}" if filter_desc else ""

    if not display_list:
        # BUG-9: query valid tapi hasil kosong — bukan kegagalan sistem, tapi juga
        # bukan jawaban spesifik seperti hasil ketemu, jadi confidence menengah.
        return {
            "reply": (
                f"Tidak ditemukan model{scope} di database Academy Next Top Model. "
                "Silakan coba filter lain atau lihat menu Model pada website."
            ),
            "intent": "model_list",
            "source": "database_realtime",
            "confidence": 0.6,
            "handoff": False,
        }

    formatted_models = []
    for m in display_list[:12]:
        name = m.get("nama_model") or m.get("name") or "Model"
        category = (m.get("categories") or "general").capitalize()
        city = m.get("city") or "-"
        status = "tersedia" if _is_available_status(m.get("status")) else "tidak tersedia"
        formatted_models.append(f"- {name} (kategori: {category}, kota: {city}, status: {status})")

    preview = "\n".join(formatted_models)
    extra = len(display_list) - 12
    tail = f"\n...dan {extra} model lainnya." if extra > 0 else ""

    reply = (
        f"Ditemukan {len(display_list)} model{scope} di database Academy Next Top Model:\n"
        f"{preview}{tail}"
    )

    return {
        "reply": reply,
        "intent": "model_list",
        "source": "database_realtime",
        "confidence": 0.95,
        "handoff": False
    }

def handle_model_count(query: str = ""):
    """Handle model count query in real-time from DB (dengan filter NL)."""
    models = get_all_models()
    if not models:
        # BUG-9: DB/API tidak terjangkau — confidence rendah.
        return {
            "reply": "Saat ini data model di database tidak dapat diakses secara langsung. Pastikan MySQL/Laravel API aktif, lalu coba lagi.",
            "intent": "model_count",
            "source": "database_realtime",
            "confidence": 0.3,
            "handoff": False
        }

    filters = extract_model_filters(query)
    filtered = apply_model_filters(models, filters)
    filter_desc = _describe_model_filters(filters)

    total_models = len(filtered)
    active_models = len([m for m in filtered if _is_available_status(m.get("status"))])

    if filter_desc:
        if filters.get("available_only"):
            reply = (
                f"Saat ini terdapat {total_models} model dengan filter {filter_desc} "
                f"di database Academy Next Top Model."
            )
        else:
            reply = (
                f"Saat ini terdapat {total_models} model dengan filter {filter_desc} "
                f"di database Academy Next Top Model"
                f"{f', termasuk {active_models} berstatus aktif/tersedia' if not filters.get('unavailable_only') else ''}."
            )
    else:
        reply = (
            f"Saat ini terdapat {total_models} model yang terdaftar di database Academy Next Top Model, "
            f"dengan {active_models} model berstatus aktif/tersedia (available)."
        )

    return {
        "reply": reply,
        "intent": "model_count",
        "source": "database_realtime",
        "confidence": 0.95,
        "handoff": False
    }

def handle_model_status(query: str):
    """Handle model status / name lookup from DB (+ Laravel search fallback)"""
    models = get_all_models()
    model_name = extract_model_name(query)
    if not model_name:
        try:
            model_name = extract_possible_model_name(query)
        except Exception:
            model_name = ""
    model_name = (model_name or "").strip()

    model = None
    if models and model_name:
        model = find_model_flexible(model_name, models)
    if not model and model_name:
        model = search_model_via_api(model_name)

    if not models and not model:
        # BUG-9: DB/API tidak terjangkau — confidence rendah.
        return {
            "reply": "Saat ini data model di database tidak dapat diakses. Pastikan MySQL/Laravel API aktif, lalu coba lagi.",
            "intent": "model_status",
            "source": "database_realtime",
            "confidence": 0.3,
            "handoff": False
        }

    if not model:
        # BUG-9: pencarian valid tapi nama tidak ketemu — confidence menengah,
        # bukan sama tingginya dengan hasil yang benar-benar ditemukan.
        hint = f" '{model_name}'" if model_name else ""
        return {
            "reply": (
                f"Saya belum menemukan model{hint} pada database website saat ini. "
                "Anda dapat memeriksa daftar model aktif di menu Model."
            ),
            "intent": "model_status",
            "source": "database_realtime",
            "confidence": 0.6,
            "handoff": False
        }

    name = model.get("nama_model") or model.get("name") or "Model"
    status_str = (
        "Tersedia (available)"
        if _is_available_status(model.get("status"))
        else "Tidak tersedia (unavailable)"
    )
    category = (model.get("categories") or "-").capitalize()
    city = model.get("city") or "-"

    return {
        "reply": (
            f"Ya, model {name} terdaftar di database Academy Next Top Model. "
            f"Kategori: {category}, kota: {city}, status: {status_str}."
        ),
        "intent": "model_status",
        "source": "database_realtime",
        "confidence": 0.95,
        "handoff": False
    }

def handle_model_recommendation(query: str):
    """Handle model recommendation query — pakai shortlist DB + filter NL."""
    q = normalize_text(query)
    filter_result = filter_models_by_criteria(query)
    shortlist = []
    if filter_result.get("success"):
        shortlist = filter_result.get("shortlist") or []
    names = [m.get("nama_model") or m.get("name") for m in shortlist if (m.get("nama_model") or m.get("name"))]
    names = [n for n in names if n]
    filter_desc = _describe_model_filters(filter_result.get("filters") or extract_model_filters(query))
    scope = f" ({filter_desc})" if filter_desc else ""

    if "anak" in q and "laut" in q:
        intro = (
            "Untuk kebutuhan model anak-anak dengan tema laut, berikut beberapa model dari kategori Kids "
            "yang dapat dipertimbangkan."
        )
    elif "anak" in q and "remaja" in q:
        intro = (
            "Untuk kategori anak-anak dan remaja, berikut beberapa model dari kategori Kids dan Teens "
            "yang dapat dipertimbangkan."
        )
    elif "anak" in q:
        intro = "Untuk kategori anak-anak, berikut beberapa model Kids yang dapat dipertimbangkan."
    elif "remaja" in q:
        intro = "Untuk kategori remaja, berikut beberapa model Teens yang dapat dipertimbangkan."
    elif filter_desc:
        intro = f"Berdasarkan filter{scope}, berikut beberapa model yang dapat dipertimbangkan."
    else:
        intro = (
            "Saya dapat membantu memberikan rekomendasi model berdasarkan kategori, tema, dan kebutuhan Anda."
        )

    if names:
        reply = (
            f"{intro} Contoh model: {', '.join(names)}. "
            "Pemilihan final tetap disesuaikan dengan konsep, tema, dan brief. "
            "Admin kami siap membantu rekomendasi final."
        )
        # BUG-9: shortlist nyata ditemukan — confidence tinggi.
        confidence = 0.95
    else:
        reply = (
            f"{intro} "
            "Saat ini belum ada shortlist yang cocok dari database untuk filter tersebut. "
            "Untuk rekomendasi final, admin kami dapat membantu memilihkan model terbaik."
        )
        # BUG-9: tidak ada shortlist yang cocok — confidence menengah.
        confidence = 0.6

    return {
        "reply": reply,
        "intent": "model_recommendation",
        "source": "database_realtime",
        "confidence": confidence,
        "handoff": True,
        "whatsapp_url": build_whatsapp_url(
            "Halo Admin Academy Next Top Model, saya ingin meminta rekomendasi model."
        )
    }

def is_model_list_query(message: str) -> bool:
    """Detect model list / filtered catalog queries"""
    msg = normalize_text(message)
    # Count questions handled by count detector
    if any(k in msg for k in ["berapa", "jumlah", "total"]) and "model" in msg:
        return False
    # Recommendation markers — jangan curi ke list
    if any(k in msg for k in [
        "rekomendasi", "yang cocok", "yang terbaik", "hire model", "butuh model",
        "cari model untuk", "tema laut", "tema beauty", "produk anak",
    ]):
        return False
    # Pengecualian pencarian nama eksplisit — dicek SEBELUM keyword list & fallback
    # supaya "status model anak"/"cek model remaja" tidak dicuri hanya karena
    # mengandung kata kategori (BUG-2, audit Tahap 2)
    name_lookup_exclusion = re.search(r"\b(bernama|nama|status model|cek model)\b", msg)

    keywords = [
        "siapa saja model yang ada di website",
        "tampilkan daftar model yang ada",
        "daftar model",
        "model yang tersedia",
        "model yang ada",
        "list model",
        "daftar semua model",
        "siapa saja model",
        "siapa model",
        "model apa saja",
        "apa saja model",
        "model tersedia",
        "model dari",
        "model di ",
        "model aktif",
        "model remaja",
        "model anak",
        "model kids",
        "model teens",
        "kategori remaja",
        "kategori anak",
        "kategori kids",
        "kategori teens",
    ]
    if any(k in msg for k in keywords) and not name_lookup_exclusion:
        return True
    # "model <kota/kategori>" tanpa nama orang
    if "model" in msg:
        filters = extract_model_filters(msg)
        # BUG-3: sertakan unavailable_only & age_min — sebelumnya diabaikan sehingga
        # query filter usia/unavailable jatuh ke NB/clarification, bukan list.
        if (filters.get("categories") or filters.get("city") or filters.get("available_only")
                or filters.get("unavailable_only") or filters.get("age_min") is not None):
            if name_lookup_exclusion:
                return False
            # Token tepat setelah "model " yang BUKAN kata filter dikenal →
            # kemungkinan nama orang (mis. "apakah model sabrina masih aktif") →
            # jangan diklaim sebagai list (BUG-1, audit Tahap 2)
            m = re.search(r"\bmodel\s+([a-z0-9]+)", msg)
            if m and m.group(1) not in _MODEL_NON_NAME_TOKENS:
                return False
            return True
    return False

def is_model_count_query(message: str) -> bool:
    """Detect model count queries (termasuk berapa model remaja/anak/aktif)"""
    msg = normalize_text(message)
    if "model" not in msg:
        return False
    # BUG-6 (audit Tahap 2/4): "berapa" saja terlalu luas — menangkap "usia berapa
    # saja model yang terdaftar" (tanya rentang usia) sebagai count query. "jumlah"/
    # "total" cukup jarang dipakai untuk hal selain hitungan, jadi tetap dicek bebas;
    # "berapa" dipersempit supaya harus berdekatan langsung dengan "model".
    if "jumlah" in msg or "total" in msg:
        return True
    return bool(re.search(r"\bberapa\s+(banyak\s+)?model\b", msg))

def is_model_status_query(message: str) -> bool:
    """Detect model status / name lookup queries"""
    msg = normalize_text(message)
    if is_become_model_query(msg):
        return False
    if any(x in msg for x in [
        "jadi model", "menjadi model", "model profesional", "sebagai model",
        "karier modeling", "karir modeling", "panduan model"
    ]):
        return False
    # Jangan curi query filter kategori/kota/count/list
    if is_model_count_query(msg) or is_model_list_query(msg) or is_model_recommendation_query(msg):
        return False

    keywords = [
        "apakah ada model dengan nama",
        "adakah model dengan nama",
        "apakah model",
        "adakah model",
        "sedang tersedia",
        "bernama",
        "status model",
        "cek model",
        "cari model bernama",
        "model dengan nama",
        "model yang bernama"
    ]
    if any(k in msg for k in keywords):
        return True

    # Pola singkat: "model <namaorang>" — tolak token filter
    # (?<!top ) mengecualikan "...Next Top Model <predikat>" — nama brand
    # akademi sendiri diakhiri kata "Model", jadi tanpa pengecualian ini
    # SETIAP kalimat yang menyebut nama akademi sebagai subjek (diikuti
    # predikat apa pun) salah-tembak sebagai name-lookup. (Audit Grup D)
    m = re.search(r"(?<!top )\bmodel\s+([a-z0-9]+(?:\s+[a-z0-9]+)?)", msg)
    if m:
        candidate = m.group(1).strip()
        tokens = candidate.split()
        if tokens and all(t not in _MODEL_NON_NAME_TOKENS for t in tokens):
            if not is_model_list_query(msg) and not is_model_count_query(msg):
                return True
    return False

def is_model_recommendation_query(message: str) -> bool:
    """Detect model recommendation queries"""
    msg = normalize_text(message)
    # Toleransi infleksi: rekomendasi/rekomendasikan/direkomendasikan/merekomendasikan + model
    # (BUG-4, audit Tahap 2 — keyword persis "rekomendasi model" gagal untuk "rekomendasikan model")
    if re.search(r"rekomendasi\w*\s+model", msg):
        return True
    keywords = [
        "model yang terbaik",
        "rekomendasi model",
        "model yang cocok",
        "pilih model",
        "cari model untuk",
        "model yang paling",
        "hire model",
        "butuh model",
        "untuk produk anak",
        "model anak anak",
        "usia remaja",
        "usia anak",
        "anak anak dan remaja",
        "tema beauty",
        "tema laut",
    ]
    return any(k in msg for k in keywords)

# ========================= INTENT DETECTOR FUNCTIONS =========================
def is_program_query(q: str) -> bool:
    q = normalize_text(q)
    keywords = [
        "program apa saja",
        "program yang tersedia",
        "materi kelas apa saja",
        "kelas apa saja",
        "materi academy",
        "class materials",
        "pelatihan apa saja",
        "program academy",
        "program apa",
        "materi kelas",
        "materi pelatihan",
        "ngajarin apa",
    ]
    return any(k in q for k in keywords)

def is_location_query(q: str) -> bool:
    q = normalize_text(q)
    return any(k in q for k in ["lokasi", "alamat", "di mana", "dimana", "tempat kelas"])

def is_registration_steps_query(q: str) -> bool:
    q = normalize_text(q)
    # "pengen jadi model mulai dari mana" = panduan jadi model, bukan tutorial register
    if any(k in q for k in ["jadi model", "menjadi model", "karier modeling", "karir modeling"]):
        return False
    return any(k in q for k in [
        "cara daftar", "cara mendaftar", "cara join", "gimana daftar", "gimana join",
        "mau join", "ingin bergabung", "mulai dari mana", "cara gabung",
        "proses pendaftaran", "proses daftar", "langkah daftar", "langkah pendaftaran",
        "cara registrasi", "ingin daftar", "saya mau daftar", "tutorial daftar",
        "cara register", "cara buat akun", "join academy", "gabung academy",
        "mau ikut academy", "mau masuk academy", "proses join", "cara bergabung",
    ])

def is_registration_requirements_query(text: str) -> bool:
    q = normalize_text(text)
    keywords = [
        "syarat", "persyaratan", "requirement", "dokumen", "apa syarat", "syaratnya apa",
        "yang disiapkan", "berkas", "apa yang harus disiapkan", "requirements",
        "batas usia", "usia pendaftaran", "usia daftar", "umur pendaftaran", "umur daftar",
    ]
    return any(k in q for k in keywords)

def is_photo_upload_query(text: str) -> bool:
    q = normalize_text(text)
    keywords = [
        "upload foto", "unggah foto", "foto close up", "foto full body", "kirim foto",
        "fotonya bagaimana", "cara upload foto", "format foto", "upload portofolio",
        "unggah portofolio", "cara upload", "upload gambar"
    ]
    return any(k in q for k in keywords)

def is_schedule_query(q: str) -> bool:
    q = normalize_text(q)
    return any(k in q for k in [
        "jadwal", "kapan kelas", "hari apa", "jam berapa", "jadwal academy",
        "jadwal pelatihan", "kelas kapan", "kapan pelatihan"
    ])





def get_registration_steps_knowledge(query: str = None) -> str:
    text = _pick_academy_response("registration_steps", query=query)
    if text:
        return text
    steps = ((ACADEMY_DATA or {}).get("registration") or {}).get("steps") or []
    if steps:
        numbered = "\n".join(f"{i}. {s}" for i, s in enumerate(steps, 1))
        return "Cara / proses pendaftaran di Academy Next Top Model:\n" + numbered
    return (
        "Cara pendaftaran di Academy Next Top Model:\n"
        "1. Buka halaman Register pada website.\n"
        "2. Isi data diri lengkap (nama, email, WhatsApp, password).\n"
        "3. Lakukan verifikasi akun jika diminta.\n"
        "4. Login ke akun yang sudah dibuat.\n"
        "5. Lengkapi profil dan portofolio (termasuk foto).\n"
        "6. Ikuti instruksi lanjutan dari sistem atau admin."
    )


def get_registration_requirements_knowledge(query: str = None) -> str:
    # Prioritas jawaban kanonis di responses (hindari data requirements yang bertentangan)
    text = _pick_academy_response("registration_requirements", query=query)
    if text:
        return text
    reqs = ((ACADEMY_DATA or {}).get("registration") or {}).get("requirements") or []
    if reqs:
        bullets = "\n".join(f"- {r}" for r in reqs)
        return "Syarat pendaftaran Academy Next Top Model:\n" + bullets
    return (
        "Persyaratan pendaftaran di Academy Next Top Model meliputi usia start 4th-25th, "
        "mengisi formulir, menyiapkan foto close-up dan full body, serta melakukan pembayaran registrasi."
    )


def get_photo_upload_knowledge(query: str = None) -> str:
    text = _pick_academy_response("photo_upload", query=query)
    if text:
        return text
    return (
        "Panduan upload foto di Academy Next Top Model:\n"
        "1. Daftar/login ke website Academy Next Top Model.\n"
        "2. Buka menu profil/portofolio.\n"
        "3. Unggah foto close-up (wajah) dan foto full body sesuai ketentuan.\n"
        "4. Gunakan format JPEG/JPG/PNG, foto jelas, dan tidak diedit berlebihan.\n"
        "5. Simpan/submit profil agar data portofolio tersimpan.\n"
        "Jika gagal upload, hubungi admin via WhatsApp untuk bantuan."
    )


def get_schedule_knowledge(query: str = None) -> str:
    text = _pick_academy_response("schedule_info", query=query)
    if text:
        return text
    sched = (ACADEMY_DATA or {}).get("schedule") or {}
    if sched:
        day = sched.get("class_day", "Sabtu")
        time = sched.get("class_time", "11.00 - 15.00")
        loc = sched.get("location", "Mall TSM Cibubur")
        return f"Jadwal Academy Next Top Model: setiap hari {day} pukul {time} di {loc}."
    return "Kelas Academy Next Top Model berlangsung setiap hari Sabtu, pukul 11.00 hingga 15.00 di Mall TSM Cibubur."


def is_benefit_query(q: str) -> bool:
    q = normalize_text(q)
    return any(k in q for k in ["benefit", "manfaat", "kelebihan", "keuntungan"])

def is_become_model_query(text: str) -> bool:
    q = normalize_text(text)
    # "syarat jadi model" = persyaratan, bukan panduan karier
    if is_registration_requirements_query(q) and "syarat" in q:
        return False
    keywords = [
        "panduan jadi model", "cara jadi model", "cara menjadi model",
        "bagaimana jadi model", "bagaimana menjadi model", "tips jadi model",
        "mulai jadi model", "pengen jadi model", "ingin jadi model", "mau jadi model",
        "jadi model profesional", "menjadi model profesional", "jadi model",
        "menjadi model", "karier modeling", "karir modeling", "memulai karier modeling",
        "apa yang harus dilakukan untuk jadi model", "apa yang harus disiapkan untuk jadi model",
    ]
    return any(k in q for k in keywords)


def get_become_model_knowledge(query: str = None) -> str:
    """Ambil panduan jadi model dari academy_enhanced.json; jangan mengarang."""
    text = _pick_academy_response("become_model_info", query=query)
    if text:
        return text
    # Susun dari potongan knowledge yang ada (tanpa fakta baru) — hanya jika query umum
    parts = []
    programs = _pick_academy_response("programs_info", query=query) or _pick_academy_response("program_info", query=query)
    schedule = get_schedule_knowledge(query=query)
    requirements = get_registration_requirements_knowledge(query=query)
    steps = get_registration_steps_knowledge(query=query)
    if steps:
        parts.append("Langkah awal: " + steps.split("\n")[0] if "\n" in steps else steps)
    if requirements:
        parts.append(requirements)
    if programs:
        parts.append("Program pelatihan: " + programs)
    if schedule:
        parts.append(schedule)
    if parts:
        return "Panduan menjadi model di Academy Next Top Model:\n" + "\n".join(f"- {p}" for p in parts)
    return (
        "Maaf, informasi detail panduan jadi model belum tersedia di knowledge base saat ini. "
        "Silakan hubungi admin Academy Next Top Model melalui WhatsApp untuk panduan resmi."
    )


def retrieve_academy_topic_reply(message: str):
    """
    Retrieval knowledge untuk topik pendaftaran/jadwal/foto/panduan jadi model.
    Prioritas: knowledge academy_enhanced (bukan NB hardcode).
    Jika beberapa topik match, ranking berdasarkan specificity config + kemiripan query.
    """
    topic_handlers = [
        ("photo_upload", is_photo_upload_query, get_photo_upload_knowledge),
        ("registration_requirements", is_registration_requirements_query, get_registration_requirements_knowledge),
        ("become_model_info", is_become_model_query, get_become_model_knowledge),
        ("registration_steps", is_registration_steps_query, get_registration_steps_knowledge),
        ("schedule_info", is_schedule_query, get_schedule_knowledge),
        ("location_info", is_location_query, lambda q: _pick_academy_response("location_info", query=q)),
        ("programs_info", is_program_query, lambda q: _pick_academy_response("programs_info", query=q) or _pick_academy_response("program_info", query=q)),
        ("benefit_info", is_benefit_query, lambda q: _pick_academy_response("benefits_joining", query=q)),
        ("contact_admin", lambda q: any(k in normalize_text(q) for k in ["hubungi admin", "kontak admin", "nomor admin", "whatsapp admin", "wa admin"]),
         lambda q: _pick_academy_response("contact_admin", query=q)),
    ]

    matched = []
    for intent_name, checker, getter in topic_handlers:
        try:
            if checker(message):
                matched.append((intent_name, getter))
        except Exception:
            continue

    if not matched:
        return None

    # Ranking kandidat: knowledge paling spesifik terhadap pertanyaan
    ordered_intents = [m[0] for m in matched]
    if KNOWLEDGE_RETRIEVER_AVAILABLE and rank_topic_candidates and len(matched) > 1:
        ranked = rank_topic_candidates(message, ordered_intents, ACADEMY_DATA)
        intent_order = [intent for _, intent in ranked]
    else:
        # Pertahankan urutan prioritas lama (photo → syarat → become → steps → schedule)
        intent_order = ordered_intents

    getter_map = {name: getter for name, getter in matched}
    best_intent = intent_order[0]
    reply = (getter_map[best_intent](message) or "").strip()
    if not reply:
        return None

    return {
        "reply": reply,
        "intent": best_intent,
        "source": "academy_knowledge",
        "confidence": 0.97,
        "handoff": best_intent in ("pricing_info", "pricing_sensitive"),
    }


def is_sensitive_query(q: str) -> bool:
    q = normalize_text(q)
    keywords = [
        "biaya", "harga", "bayar", "pembayaran", "diskon",
        "promo", "kontrak", "booking", "fee", "tarif", "cicilan",
    ]
    return any(k in q for k in keywords)


def is_multi_intent_query(message: str) -> bool:
    """True jika pesan mengandung 2+ topik/pertanyaan berbeda."""
    intents = detect_all_topic_intents(message)
    if len(intents) >= 2:
        return True
    # Beberapa pertanyaan ditandai tanda tanya, meski detector belum lengkap
    q_marks = message.count("?")
    if q_marks >= 2 and len(intents) >= 1:
        return True
    msg = normalize_text(message)
    connectors = [" dan ", " serta ", " juga ", " lalu ", " selain itu ", " kemudian ", " sekaligus "]
    if any(c in f" {msg} " for c in connectors) and len(intents) >= 1:
        # Hindari false positive "cara daftar dan login" → tetap butuh 2 intent
        return len(intents) >= 2
    return False


def detect_all_topic_intents(message: str) -> list:
    """
    Deteksi semua intent di pesan (urut prioritas jawaban).
    Cek seluruh pesan + pecahan per kalimat/? agar multi-tanya terdeteksi.
    """
    raw = (message or "").strip()
    if not raw:
        return []

    # Pecah berdasarkan ?, !, ;, dan newline — simpan fragmen non-kosong
    parts = re.split(r"[?!\n;]+", raw)
    parts = [p.strip() for p in parts if p and p.strip()]
    # Juga cek pesan utuh
    candidates = [raw] + parts

    ordered_checks = [
        ("registration_requirements", is_registration_requirements_query),
        ("photo_upload", is_photo_upload_query),
        ("registration_steps", is_registration_steps_query),
        ("schedule_info", is_schedule_query),
        ("location_info", is_location_query),
        ("programs_info", is_program_query),
        ("benefit_info", is_benefit_query),
        ("become_model_info", is_become_model_query),
        ("about_academy", is_about_academy_query),
        ("homepage_info", is_homepage_info_query),
        ("pricing_info", is_sensitive_query),
        ("model_count", is_model_count_query),
        ("model_list", is_model_list_query),
        ("model_recommendation", is_model_recommendation_query),
        ("model_status", is_model_status_query),
    ]

    found = []
    seen = set()
    for text in candidates:
        for intent_name, checker in ordered_checks:
            if intent_name in seen:
                continue
            try:
                if checker(text):
                    found.append(intent_name)
                    seen.add(intent_name)
            except Exception:
                continue
    return found


_INTENT_SECTION_TITLES = {
    "registration_requirements": "Syarat pendaftaran",
    "photo_upload": "Upload foto",
    "registration_steps": "Cara / proses pendaftaran",
    "schedule_info": "Jadwal Academy",
    "location_info": "Lokasi Academy",
    "programs_info": "Program & materi kelas",
    "benefit_info": "Manfaat bergabung",
    "become_model_info": "Panduan jadi model",
    "pricing_info": "Biaya & promo",
    "model_count": "Jumlah model",
    "model_list": "Daftar model",
    "model_recommendation": "Rekomendasi model",
    "model_status": "Status model",
}


def _answer_for_intent(intent_name: str, message: str) -> str:
    """Ambil jawaban kanonis per intent dari knowledge / handler (tanpa mengarang)."""
    if intent_name == "registration_requirements":
        return get_registration_requirements_knowledge(query=message)
    if intent_name == "photo_upload":
        return get_photo_upload_knowledge(query=message)
    if intent_name == "registration_steps":
        return get_registration_steps_knowledge(query=message)
    if intent_name == "schedule_info":
        return get_schedule_knowledge(query=message)
    if intent_name == "location_info":
        text = _pick_academy_response("location_info", query=message)
        return text or "Lokasi Academy Next Top Model berada di Trans Studio Mall Cibubur, GF Floor Second Atrium."
    if intent_name == "programs_info":
        text = _pick_academy_response("programs_info", query=message) or _pick_academy_response("program_info", query=message)
        return text or (
            "Class Materials di Academy Next Top Model meliputi Catwalk Basic, Catwalk Advanced, "
            "Runway Class, Beauty Class, Pose Class, Photoshoot, Self Branding, dan Public Speaking."
        )
    if intent_name == "benefit_info":
        text = _pick_academy_response("benefits_joining", query=message)
        return text or (
            "Benefit mengikuti Academy Next Top Model antara lain pelatihan modeling, "
            "pengembangan portofolio, serta peningkatan kemampuan catwalk, pose, dan self branding."
        )
    if intent_name == "become_model_info":
        return get_become_model_knowledge(query=message)
    if intent_name == "about_academy":
        text = _pick_academy_response("about_academy", query=message)
        return text or "Academy Next Top Model adalah academy modeling yang menyediakan layanan pengembangan model, pengelolaan portofolio digital, dan informasi seputar kegiatan academy."
    if intent_name == "homepage_info":
        text = _pick_academy_response("homepage_info", query=message)
        return text or "Academy Next Top Model dipimpin oleh Oliviarie Summer sebagai Owner, CEO, Founder, dan Coach."
    if intent_name == "pricing_info":
        return (
            "Untuk informasi biaya pendaftaran, promo, diskon, dan sistem pembayaran, "
            "silakan hubungi admin Academy Next Top Model melalui WhatsApp agar mendapat info terbaru."
        )
    if intent_name == "model_count":
        return handle_model_count(message).get("reply", "")
    if intent_name == "model_list":
        return handle_model_list(message).get("reply", "")
    if intent_name == "model_recommendation":
        return handle_model_recommendation(message).get("reply", "")
    if intent_name == "model_status":
        return handle_model_status(message).get("reply", "")
    return ""


def handle_multi_intent(query: str):
    """
    Deteksi beberapa intent → ambil jawaban masing-masing dari knowledge →
    gabungkan melalui Response Orchestrator menjadi respons yang terstruktur,
    cerdas, dan bebas duplikasi.
    """
    intents = detect_all_topic_intents(query)
    if len(intents) < 2:
        return None

    # Kumpulkan jawaban per intent (hanya dari SSOT knowledge)
    answers: dict = {}
    handoff = False
    whatsapp_url = None

    for intent_name in intents:
        answer = (_answer_for_intent(intent_name, query) or "").strip()
        if answer:
            answers[intent_name] = answer
        if intent_name in ("pricing_info", "pricing_sensitive"):
            handoff = True
            whatsapp_url = build_whatsapp_url(
                "Halo Admin Academy Next Top Model, saya ingin bertanya mengenai biaya atau promo."
            )

    if len(answers) < 2:
        return None

    # Delegasikan perakitan respons ke Response Orchestrator
    orch_source = "multi_intent_fallback"
    if RESPONSE_ORCHESTRATOR_AVAILABLE and orchestrate_multi_intent_response:
        synthesized = orchestrate_multi_intent_response(
            intents=list(answers.keys()),
            answers=answers,
            user_query=query,
            is_handoff=handoff,
        )
        orch_source = "response_orchestrator"
    else:
        # Fallback jika orchestrator tidak tersedia
        parts = []
        for intent_name, answer in answers.items():
            title = _INTENT_SECTION_TITLES.get(intent_name, intent_name.replace("_", " ").title())
            parts.append(f"**{title}**\n{answer}")
        synthesized = "Berikut informasi yang Anda butuhkan:\n\n" + "\n\n".join(parts)

    if not synthesized:
        return None

    return {
        "reply": synthesized,
        "intent": "multi_intent",
        "intents": intents,
        "source": orch_source,
        "handoff": handoff,
        "whatsapp_url": whatsapp_url,
        "confidence": 0.96,
    }

def extract_possible_model_name(message: str) -> str:
    if not message:
        return ""

    text = message.lower().strip()

    # hapus tanda baca umum
    text = re.sub(r"[?!.:,;]", "", text)
    text = re.sub(r"\s+", " ", text).strip()

    patterns = [
        r"apakah ada model dengan nama (.+)",
        r"apakah ada model yang bernama (.+)",
        r"apakah model dengan nama (.+)",
        r"apakah model (.+)",
        r"status model (.+)",
        r"model dengan nama (.+)",
        r"model bernama (.+)",
        r"cek model (.+)",
        r"(.+) ada di member academy",
        r"(.+) ada di academy"
    ]

    for pattern in patterns:
        match = re.match(pattern, text)
        if match:
            name = match.group(1).strip()

            suffixes = [
                " sedang tersedia",
                " tersedia",
                " available",
                " ada",
                " atau tidak",
                " statusnya",
                " status"
            ]

            for suffix in suffixes:
                if name.endswith(suffix):
                    name = name[:-len(suffix)].strip()

            # bersihkan lagi kalau masih ada karakter aneh
            name = re.sub(r"[^a-zA-Z\s]", "", name)
            name = re.sub(r"\s+", " ", name).strip()

            return name.title()

    # fallback: bersihkan total
    text = re.sub(r"[^a-zA-Z\s]", "", text)
    text = re.sub(r"\s+", " ", text).strip()
    return text.title()


def search_model_status_flexible(model_name: str):
    """Flexible model search with 2-tier fallback: API search + manual search"""
    # 1. Coba endpoint search dulu
    model = search_model_status(model_name)
    if model and model.get("found"):
        return model

    # 2. Fallback cari dari semua model
    models = get_all_models()
    if not models:
        return None

    q = model_name.lower().strip()

    for m in models:
        name = str(m.get("name", "")).lower()
        # Exact match
        if q == name:
            return {
                "found": True,
                "name": m.get("name"),
                "status": m.get("status"),
                "category": m.get("category")
            }
        # Partial match (query contains in name)
        if q in name:
            return {
                "found": True,
                "name": m.get("name"),
                "status": m.get("status"),
                "category": m.get("category")
            }
        # Name starts with query
        if name.startswith(q):
            return {
                "found": True,
                "name": m.get("name"),
                "status": m.get("status"),
                "category": m.get("category")
            }

    return None


# =========================
# SIMPLE SEARCH FUNCTIONS
# =========================
def _rank_ssot_choice(variants, query: str, language: str = "id"):
    """Pilih varian SSOT paling relevan terhadap query (bukan random)."""
    if not variants:
        return ""
    if isinstance(variants, dict):
        variants = variants.get(language) or variants.get("id") or variants.get("en") or []
    if not isinstance(variants, list) or not variants:
        return str(variants).strip() if variants else ""
    if KNOWLEDGE_RETRIEVER_AVAILABLE and select_best_variant:
        return select_best_variant(query or "", [str(v) for v in variants])
    return str(variants[0]).strip()


def academy_search_simple(message: str, language: str = "id"):
    """Simple academy search with error handling for registration blocks"""
    
    if not ACADEMY_DATA:
        return None
    
    message_lower = message.lower()
    responses = ACADEMY_DATA.get("responses", {})
    keywords = ACADEMY_DATA.get("keywords", {})
    intents = ACADEMY_DATA.get("intents", {})
    
    # Debug print untuk tracking intent
    print("Processed message:", message)
    print("Message lower:", message_lower)
    about_keys = keywords.get("about_academy", {})
    reg_keys = keywords.get("registration_steps", {})
    schedule_keys = keywords.get("schedule_info", {})
    academy_info_keys = keywords.get("academy_info", {})
    programs_info_keys = keywords.get("programs_info", {})
    homepage_info_keys = keywords.get("homepage_info", {})
    model_info_keys = keywords.get("model_info", {})
    contact_admin_keys = keywords.get("contact_admin", {})
    print("About keywords:", about_keys.get("id", []) if isinstance(about_keys, dict) else about_keys)
    print("Reg keywords:", reg_keys.get("id", []) if isinstance(reg_keys, dict) else reg_keys)
    print("Schedule keywords:", schedule_keys.get("id", []) if isinstance(schedule_keys, dict) else schedule_keys)
    print("Academy info keywords:", academy_info_keys.get("id", []) if isinstance(academy_info_keys, dict) else academy_info_keys)
    print("Programs info keywords:", programs_info_keys.get("id", []) if isinstance(programs_info_keys, dict) else programs_info_keys)
    print("Homepage info keywords:", homepage_info_keys.get("id", []) if isinstance(homepage_info_keys, dict) else homepage_info_keys)
    print("Model info keywords:", model_info_keys.get("id", []) if isinstance(model_info_keys, dict) else model_info_keys)
    print("Contact admin keywords:", contact_admin_keys.get("id", []) if isinstance(contact_admin_keys, dict) else contact_admin_keys)
    schedule_keys_dict = keywords.get("schedule_info", {})
    schedule_keys_list = schedule_keys_dict.get("id", []) if isinstance(schedule_keys_dict, dict) else schedule_keys_dict
    about_keys_dict = keywords.get("about_academy", {})
    about_keys_list = about_keys_dict.get("id", []) if isinstance(about_keys_dict, dict) else about_keys_dict
    
    print("Matched schedule:", any(keyword in message_lower for keyword in schedule_keys_list))
    print("Matched about:", any(keyword in message_lower for keyword in about_keys_list))
    
    # Registration steps handling moved to high priority - skip here to avoid conflicts
    # This section is now handled by is_registration_steps_query() at higher priority
    print("DEBUG: Skipping registration steps - handled by high priority registration_steps")
    
    # Check for registration requirements keywords - SECURED
    reg_req_keywords = keywords.get("registration_requirements", [])
    if any(keyword in message_lower for keyword in reg_req_keywords):
        try:
            if KNOWLEDGE_RETRIEVER_AVAILABLE and select_knowledge_answer:
                response = select_knowledge_answer(message_lower, "registration_requirements", ACADEMY_DATA, language=language)
            else:
                reg_req_responses = responses.get("registration_requirements", [])
                response = _rank_ssot_choice(reg_req_responses, message_lower, language) if reg_req_responses else (
                    "Untuk informasi lengkap persyaratan pendaftaran, silakan kunjungi halaman registrasi atau hubungi admin kami melalui WhatsApp."
                )
            
            return {
                "answer": response,
                "intent": intents.get("registration_requirements", "registration_requirements"),
                "confidence": 0.90,
                "source": "academy_registration"
            }
        except Exception as e:
            print(f"Error in registration_requirements: {e}")
            return {
                "answer": "Maaf, terjadi kesalahan saat memproses informasi persyaratan. Silakan hubungi admin kami.",
                "intent": "registration_requirements",
                "confidence": 0.50,
                "source": "academy_registration_fallback"
            }
    
    # Check for greeting keywords
    greeting_keywords = keywords.get("greeting", {}).get(language, keywords.get("greeting", {}).get("id", []))
    if any(keyword in message_lower for keyword in greeting_keywords):
        response_data = responses.get("greeting", {}).get(language, responses.get("greeting", {}).get("id", []))
        if response_data and len(response_data) > 0:
            response = _rank_ssot_choice(response_data, message_lower, language)
        else:
            response = "Halo! Selamat datang di Academy Next Top Model. Saya asisten AI siap membantu Anda."
        
        return {
            "answer": response,
            "intent": intents.get("greeting", "greeting"),
            "confidence": 0.90,
            "source": "academy_greeting",
            "language": language
        }
    
    # Check for model info keywords - PRIORITY HIGH
    model_info_keywords = keywords.get("model_info", [])
    if any(keyword in message_lower for keyword in model_info_keywords):
        model_info_responses = responses.get("model_info", [])
        if model_info_responses:
            response = _rank_ssot_choice(model_info_responses, message_lower, language) if isinstance(model_info_responses, list) else model_info_responses
        else:
            response = "Saya dapat membantu memberikan informasi model yang tersedia pada website. Silakan sebutkan nama model yang ingin Anda cari."

        return {
            "answer": response,
            "intent": intents.get("model_info", "model_info"),
            "confidence": 0.95,
            "source": "academy_model"
        }
    
    # Check for schedule info keywords - PRIORITIZED
    schedule_keywords = keywords.get("schedule_info", [])
    if any(keyword in message_lower for keyword in schedule_keywords) or (
        "jadwal" in message_lower or "schedule" in message_lower or "kapan" in message_lower
    ):
        schedule_responses = responses.get("schedule_info", [])
        if schedule_responses:
            response = _rank_ssot_choice(schedule_responses, message_lower, language) if isinstance(schedule_responses, list) else schedule_responses
        else:
            response = (
                "Jadwal kelas di Academy Next Top Model berlangsung setiap Sabtu "
                "pukul 11.00 sampai 15.00 di Trans Studio Mall Cibubur, GF Floor Second Atrium."
            )

        return {
            "answer": response,
            "intent": intents.get("schedule_info", "schedule_info"),
            "confidence": 0.95,
            "source": "academy_schedule"
        }
    
    # Check for registration steps keywords
    reg_steps_keywords = keywords.get("registration_steps", [])
    if any(keyword in message_lower for keyword in reg_steps_keywords) or (
        "daftar" in message_lower or "pendaftaran" in message_lower or "register" in message_lower
    ):
        reg_steps_responses = responses.get("registration_steps", [])
        if reg_steps_responses:
            response = _rank_ssot_choice(reg_steps_responses, message_lower, language) if isinstance(reg_steps_responses, list) else reg_steps_responses
        else:
            response = (
                "Cara pendaftaran di Academy Next Top Model adalah sebagai berikut:\n"
                "1. Buka halaman Register pada website.\n"
                "2. Isi data diri lengkap.\n"
                "3. Lakukan verifikasi akun jika diminta.\n"
                "4. Login ke akun yang sudah dibuat.\n"
                "5. Lengkapi profil dan portofolio.\n"
                "6. Jika mengalami kendala, silakan hubungi admin."
            )

        return {
            "answer": response,
            "intent": intents.get("registration_steps", "registration_steps"),
            "confidence": 0.95,
            "source": "academy_registration"
        }
    
    # Check for registration requirements keywords
    reg_req_keywords = keywords.get("registration_requirements", [])
    if any(keyword in message_lower for keyword in reg_req_keywords):
        reg_req_responses = responses.get("registration_requirements", [])
        if reg_req_responses:
            response = _rank_ssot_choice(reg_req_responses, message_lower, language) if isinstance(reg_req_responses, list) else reg_req_responses
        else:
            response = (
                "Persyaratan pendaftaran meliputi mengisi formulir, "
                "menyiapkan foto close-up dan full body, serta mengikuti ketentuan academy."
            )

        return {
            "answer": response,
            "intent": intents.get("registration_requirements", "registration_requirements"),
            "confidence": 0.95,
            "source": "academy_registration"
        }
    
    # Check for become model info keywords
    become_keywords = keywords.get("become_model_info", [])
    if any(keyword in message_lower for keyword in become_keywords) or (
        "jadi model" in message_lower or "model profesional" in message_lower
    ):
        become_responses = responses.get("become_model_info_response", [])
        response = _rank_ssot_choice(become_responses, message_lower, language) if become_responses else (
            "Untuk menjadi model profesional, Anda perlu latihan, portofolio, dan pengalaman. "
            "Academy Next Top Model dapat membantu melalui program pelatihan modeling."
        )

        return {
            "answer": response,
            "intent": intents.get("become_model_info", "become_model_info"),
            "confidence": 0.95,
            "source": "academy_guidance"
        }
    
    # Check for programs info keywords
    programs_keywords = keywords.get("programs_info", [])
    if any(keyword in message_lower for keyword in programs_keywords) or (
        "kelas" in message_lower or "materi" in message_lower or "program" in message_lower
    ):
        programs_responses = responses.get("programs_info", [])
        if programs_responses:
            response = _rank_ssot_choice(programs_responses, message_lower, language) if isinstance(programs_responses, list) else programs_responses
        else:
            response = (
                "Class Materials di Academy Next Top Model meliputi Catwalk Basic, "
                "Catwalk Advanced, Runway Class, Beauty Class, Pose Class, Photoshoot, "
                "Self Branding, dan Public Speaking."
            )

        return {
            "answer": response,
            "intent": intents.get("programs_info", "programs_info"),
            "confidence": 0.95,
            "source": "academy_programs"
        }
    
    # Check for location info keywords - DISABLED - Prioritize EXACT_QA instead
    # location_keywords = keywords.get("location_info", [])
    # if any(keyword in message_lower for keyword in location_keywords):
    #     location_responses = responses.get("schedule_info", [])
    #     if location_responses and len(location_responses) > 0:
    #         response = _rank_ssot_choice(location_responses, message_lower, language) if isinstance(location_responses, list) else location_responses
    #     else:
    #         response = (
    #             "Lokasi Academy Next Top Model berada di Trans Studio Mall Cibubur, GF Floor Second Atrium."
    #         )

    #     return {
    #         "answer": response,
    #         "intent": intents.get("location_info", "location_info"),
    #         "confidence": 0.95,
    #         "source": "academy_location"
    #     }
    
    # Check for join registration info keywords
    join_keywords = keywords.get("join_registration_info", [])
    if any(keyword in message_lower for keyword in join_keywords):
        join_responses = responses.get("registration_steps", [])
        if join_responses and len(join_responses) > 0:
            response = _rank_ssot_choice(join_responses, message_lower, language) if isinstance(join_responses, list) else join_responses
        else:
            response = (
                "Untuk bergabung dengan Academy Next Top Model, silakan kunjungi website "
                "dan klik tombol Register untuk mengisi formulir pendaftaran."
            )

        return {
            "answer": response,
            "intent": intents.get("join_registration_info", "join_registration_info"),
            "confidence": 0.95,
            "source": "academy_registration"
        }
    
    # Check for contact admin keywords
    contact_admin_keywords = keywords.get("contact_admin", [])
    if any(keyword in message_lower for keyword in contact_admin_keywords):
        contact_admin_responses = responses.get("contact_admin", [])
        if contact_admin_responses:
            # Handle nested structure in responses
            if isinstance(contact_admin_responses, dict):
                # Get language-specific response
                lang_responses = contact_admin_responses.get(language, contact_admin_responses.get("id", []))
                if isinstance(lang_responses, list):
                    response = _rank_ssot_choice(lang_responses, message_lower, language)
                else:
                    response = lang_responses
            elif isinstance(contact_admin_responses, list):
                response = _rank_ssot_choice(contact_admin_responses, message_lower, language)
            else:
                response = contact_admin_responses
        else:
            response = "Anda dapat menghubungi admin Academy Next Top Model melalui WhatsApp untuk informasi lebih lanjut."

        return {
            "answer": response,
            "intent": intents.get("contact_admin", "contact_admin"),
            "confidence": 0.95,
            "source": "academy_contact"
        }
    
    # Check for homepage info keywords (owner/founder)
    homepage_keywords = keywords.get("homepage_info", [])
    if any(keyword in message_lower for keyword in homepage_keywords) or (
        "owner" in message_lower or "pemilik" in message_lower or "founder" in message_lower or "ceo" in message_lower
    ):
        homepage_responses = responses.get("homepage_info", [])
        if homepage_responses:
            response = _rank_ssot_choice(homepage_responses, message_lower, language) if isinstance(homepage_responses, list) else homepage_responses
        else:
            response = (
                "Owner, CEO, Founder, dan Coach Academy Next Top Model adalah Oliviarie Summer. "
                "Beliau juga merupakan Miss Queen Indonesia 2023 dan Miss International Queen 2024."
            )

        return {
            "answer": response,
            "intent": "homepage_info",
            "confidence": 0.95,
            "source": "academy_homepage"
        }
    
    # Check for about academy keywords - RESTRICTED
    about_keywords = keywords.get("about_academy", [])
    if any(keyword in message_lower for keyword in about_keywords) or (
        "academy" in message_lower and (
            "apa itu" in message_lower or
            "tentang" in message_lower or
            "bergerak di bidang apa" in message_lower or
            "next top model" in message_lower
        )
    ):
        about_responses = responses.get("about_academy", [])
        if about_responses:
            response = _rank_ssot_choice(about_responses, message_lower, language) if isinstance(about_responses, list) else about_responses
        else:
            response = "Academy Next Top Model adalah academy modeling yang menyediakan pelatihan dan portofolio digital."

        return {
            "answer": response,
            "intent": intents.get("about_academy", "about_academy"),
            "confidence": 0.95,
            "source": "academy_about"
        }
        schedule_responses_dict = responses.get("schedule_info", {})
        schedule_responses = schedule_responses_dict.get("id", []) if isinstance(schedule_responses_dict, dict) else schedule_responses_dict
        
        print("Schedule responses:", schedule_responses)
        
        if schedule_responses and len(schedule_responses) > 0:
            response = _rank_ssot_choice(schedule_responses, message_lower, language) if isinstance(schedule_responses, list) else schedule_responses
        else:
            response = (
                "Jadwal kelas di Academy Next Top Model berlangsung setiap Sabtu "
                "pukul 11.00 sampai 15.00 di Trans Studio Mall Cibubur, GF Floor Second Atrium."
            )

        return {
            "answer": response,
            "intent": intents.get("schedule_info", "schedule_info"),
            "confidence": 0.95,
            "source": "academy_schedule"
        }
    
    # Check for about academy keywords - RESTRICTED
    about_keywords_dict = keywords.get("about_academy", {})
    about_keywords = about_keywords_dict.get("id", []) if isinstance(about_keywords_dict, dict) else about_keywords_dict
    
    print("About keywords list:", about_keywords)
    
    if any(keyword in message_lower for keyword in about_keywords) or (
        "academy" in message_lower and (
            "apa itu" in message_lower or
            "tentang" in message_lower or
            "bergerak di bidang apa" in message_lower or
            "next top model" in message_lower
        )
    ):
        about_responses_dict = responses.get("about_academy", {})
        about_responses = about_responses_dict.get("id", []) if isinstance(about_responses_dict, dict) else about_responses_dict
        
        print("About responses:", about_responses)
        
        if about_responses and len(about_responses) > 0:
            response = _rank_ssot_choice(about_responses, message_lower, language) if isinstance(about_responses, list) else about_responses
        else:
            response = (
                "Academy Next Top Model adalah academy modeling yang menyediakan "
                "layanan pengembangan model, pelatihan, dan portofolio digital."
            )

        return {
            "answer": response,
            "intent": intents.get("about_academy", "about_academy"),
            "confidence": 0.95,
            "source": "academy_about"
        }
    
    return None

# =========================
# NLU / MEMORY HELPERS (aktivasi fitur yang sudah ada, tanpa ubah arsitektur)
# =========================
def _safe_update_memory(session_id: str, user_message: str, reply: str,
                        intent: str, entities: dict = None, confidence: float = 1.0):
    """Update Context Memory bila NLU tersedia; gagal tidak menghentikan /chat."""
    if not (NLU_ENGINE_AVAILABLE and update_memory and session_id):
        return
    try:
        update_memory(
            session_id, user_message, reply or "", intent or "unknown",
            entities or {}, confidence=confidence,
        )
    except Exception as e:
        print(f"[MEMORY] update failed: {e}")


def _resolve_references_for_chat(user_message: str, session_id: str) -> str:
    """Anaphora / reference resolution dari ConversationMemory (jika NLU aktif)."""
    if not (NLU_ENGINE_AVAILABLE and get_memory and session_id):
        return user_message
    try:
        mem = get_memory(session_id)
        resolved = mem.resolve_reference(user_message)
        return resolved if resolved else user_message
    except Exception as e:
        print(f"[NLU] reference resolution failed: {e}")
        return user_message


def _apply_typo_correction(text: str) -> str:
    """Typo correction domain (RapidFuzz token-level) bila modul tersedia."""
    if not (TYPO_CORRECTOR_AVAILABLE and typo_correct_text) or not text:
        return text
    try:
        corrected, corrections = typo_correct_text(text)
        if corrections:
            print(f"[TYPO_CORRECT] {corrections} | '{text}' -> '{corrected}'")
            return corrected
    except Exception as e:
        print(f"[TYPO_CORRECT] Error: {e}")
    return text


def _build_time_aware_greeting(session_id: str) -> str:
    """Time-Aware + Anti-Repetition greeting via Dynamic Response Generator."""
    if NLU_ENGINE_AVAILABLE and build_dynamic_response and get_memory:
        try:
            mem = get_memory(session_id or "default")
            return build_dynamic_response("greeting", {}, mem)
        except Exception as e:
            print(f"[NLU] time-aware greeting failed: {e}")
    return resolve_alias_knowledge("greeting")


def _exact_qa_memory_intent(query_key: str) -> str:
    """Map Exact QA key ke intent kanonis untuk Context Memory."""
    return KNOWLEDGE_QUERY_ALIASES.get(query_key, "exact_qa")


# =========================
# LOGGING HELPER FUNCTIONS
# =========================
def log_chat_interaction(user_message: str, response_data: dict, session_id: str, 
                         language: str, user_ip: str, user_agent: str, start_time: float):
    """Log chat interaction to database"""
    try:
        response_time = time.time() - start_time
        is_fallback = response_data.get('intent') in ['ai_fallback', 'safe_fallback']
        
        save_chat_log(
            user_message=user_message,
            bot_response=response_data.get('reply', ''),
            intent=response_data.get('intent'),
            confidence=response_data.get('confidence', 0.0),
            source=response_data.get('source', 'unknown'),
            language=language,
            handoff=response_data.get('handoff', False),
            whatsapp_url=response_data.get('whatsapp_url'),
            response_time=response_time,
            session_id=session_id,
            user_ip=user_ip,
            user_agent=user_agent
        )
        
        # Mark fallback queries for dataset update
        if is_fallback:
            print(f"FALLBACK DETECTED: '{user_message}' -> {response_data.get('intent')}")
            
    except Exception as e:
        print(f"Logging error: {e}")

# =========================
# FLASK APP
# =========================
app = Flask(__name__)
CORS(app)

# =========================
# CHAT ENDPOINT
# =========================
# =========================
# TENSORFLOW TRAINING ENDPOINT
# =========================
@app.route("/train/naive-bayes", methods=["POST"])
def train_naive_bayes_endpoint():
    """Latih ulang TF-IDF + Multinomial Naive Bayes dari dataset_prepared.csv, lalu hot-reload."""
    global nb_model, tfidf_vectorizer, NB_MODEL_LOADED
    try:
        print("[TRAIN NB] Memulai pelatihan Naive Bayes + TF-IDF...")
        result = train_naive_bayes_from_csv()

        nb_model = joblib.load(NB_MODEL_PATH)
        tfidf_vectorizer = joblib.load(TFIDF_PATH)
        NB_MODEL_LOADED = True

        print("[TRAIN NB] Selesai. Model di-reload ke memori Flask.")
        print(f"[TRAIN NB] Accuracy={result['model_info']['accuracy']}")

        result["reloaded"] = True
        result["nb_model_loaded"] = True
        return jsonify(result)
    except FileNotFoundError as e:
        return jsonify({"success": False, "error": "dataset_missing", "message": str(e)}), 404
    except Exception as e:
        print(f"[TRAIN NB] ERROR: {e}")
        return jsonify({"success": False, "error": "training_error", "message": str(e)}), 500


@app.route("/train", methods=["POST"])
def train_tensorflow_model():
    """Train TensorFlow model with data augmentation"""
    try:
        # Default: jika mode naive_bayes / tanpa training_data → latih NB
        data = request.get_json(silent=True) or {}
        mode = (data.get("mode") or "").strip().lower()
        if mode in ("naive_bayes", "nb", "tfidf_nb") or not data.get("training_data"):
            return train_naive_bayes_endpoint()

        if tf is None or tf_processor is None:
            return jsonify({
                "success": False,
                "message": "TensorFlow belum tersedia di environment ini.",
                "error": "tensorflow_unavailable"
            }), 503

        if not data:
            return jsonify({
                "success": False,
                "message": "Format request tidak valid.",
                "error": "invalid_request"
            }), 400
        
        training_data = data.get("training_data", [])
        epochs = data.get("epochs", 10)
        batch_size = data.get("batch_size", 32)
        
        if not training_data:
            return jsonify({
                "success": False,
                "message": "Training data tidak boleh kosong.",
                "error": "no_training_data"
            }), 400
        
        # Extract texts and labels
        texts = [item.get("text", "") for item in training_data]
        labels = [item.get("intent", "") for item in training_data]
        
        # Apply data augmentation
        augmented_data = []
        for text, label in zip(texts, labels):
            augmented_texts = data_augmenter.augment_text(text, ['sr', 'ri', 'rs', 'rd'])
            for aug_text in augmented_texts:
                augmented_data.append({"text": aug_text, "intent": label})
        
        # Combine original and augmented data
        all_texts = texts + [item["text"] for item in augmented_data]
        all_labels = labels + [item["intent"] for item in augmented_data]
        
        # Create TensorFlow dataset
        X, y, label_to_int = tf_processor.create_training_data(all_texts, all_labels)
        
        # Build simple TensorFlow model
        model = tf.keras.Sequential([
            tf.keras.layers.Embedding(input_dim=tf_processor.max_words, 
                                  output_dim=128, 
                                  input_length=tf_processor.max_len),
            tf.keras.layers.GlobalAveragePooling1D(),
            tf.keras.layers.Dense(64, activation='relu'),
            tf.keras.layers.Dropout(0.5),
            tf.keras.layers.Dense(len(label_to_int), activation='softmax')
        ])
        
        # Compile model
        model.compile(
            optimizer='adam',
            loss='sparse_categorical_crossentropy',
            metrics=['accuracy']
        )
        
        # Train model
        history = model.fit(
            X, y,
            epochs=epochs,
            batch_size=batch_size,
            validation_split=0.2,
            verbose=1
        )
        
        # Save model ke folder models/
        model.save(MODEL_PATH)
        
        # Save label mapping ke folder data/
        with open(LABEL_MAPPING_PATH, 'w') as f:
            json.dump(label_to_int, f)
        
        return jsonify({
            "success": True,
            "message": "Model TensorFlow berhasil dilatih dengan data augmentation.",
            "model_info": {
                "total_samples": len(all_texts),
                "augmented_samples": len(augmented_data),
                "unique_intents": len(label_to_int),
                "epochs": epochs,
                "batch_size": batch_size,
                "final_accuracy": float(history.history['accuracy'][-1]),
                "augmentation_techniques": ["SR", "RI", "RS", "RD"]
            }
        })
        
    except Exception as e:
        print(f"TENSORFLOW TRAINING ERROR: {e}")
        return jsonify({
            "success": False,
            "message": f"Error during training: {str(e)}",
            "error": "training_error"
        }), 500

@app.route("/chat", methods=["POST"])
def chat():
    print("DEBUG: Chat endpoint called")
    start_time = time.time()
    session_id = None
    try:
        data = request.get_json()
        if not data:
            return jsonify({
                "success": False,
                "reply": "Format request tidak valid.",
                "confidence": 0,
                "handoff": False,
                "intent": "invalid_request",
                "source": "system"
            }), 400
            
        user_message = data.get("message", "").strip()
        language = data.get("language", "id")
        session_id = data.get("session_id", f"session_{int(time.time())}")
        user_ip = request.remote_addr
        user_agent = request.headers.get('User-Agent', '')
        
        if not user_message:
            return jsonify({
                "success": False,
                "reply": "Pesan tidak boleh kosong. Silakan ketik pertanyaan Anda.",
                "confidence": 0,
                "handoff": False,
                "intent": "empty",
                "source": "system"
            }), 400

        if len(user_message) > 500:
            return jsonify({
                "success": False,
                "reply": "Pesan terlalu panjang. Maksimal 500 karakter.",
                "confidence": 0,
                "handoff": False,
                "intent": "invalid_length",
                "source": "system"
            }), 400
        
        print(f"Processing message: {user_message} (lang: {language})")
        
        message_lower = user_message.lower()
        print(f"DEBUG: message_lower = '{message_lower}'")
        
        # LAYER 1: RULE-BASED INTENT DETECTION
        
        # Debug log untuk tracking intent
        print("USER MESSAGE:", user_message)
        print("PROCESSED MESSAGE:", normalize_text(user_message))
        
        processed_message = normalize_text(user_message)

        # LAYER 1.5: TENSORFLOW ENHANCED PROCESSING
        # Apply data augmentation for better intent recognition
        augmented_messages = data_augmenter.augment_text(processed_message, ['sr', 'ri', 'rs', 'rd'])
        
        # Use TensorFlow for enhanced text processing
        try:
            # Create TensorFlow tensor from text
            text_tensor = tf.constant(processed_message, dtype=tf.string)
            
            # Apply TensorFlow text processing
            processed_tensor = tf.strings.lower(text_tensor)
            processed_tensor = tf.strings.regex_replace(processed_tensor, r"[?!.:,;/\-]", " ")
            processed_tensor = tf.strings.regex_replace(processed_tensor, r"\s+", " ")
            
            # Convert back to string for comparison
            tf_processed = processed_tensor.numpy().decode('utf-8').strip()
            print(f"TENSORFLOW PROCESSED: {tf_processed}")
            
            # Use both original and TensorFlow processed for matching
            enhanced_processed = tf_processed if tf_processed else processed_message
            
        except Exception as e:
            print(f"TENSORFLOW ERROR: {e}")
            enhanced_processed = processed_message

        # =====================================================================
        # NLU PRE-PIPELINE (fitur yang sudah ada — diaktifkan di /chat)
        # Urutan: Reference Resolution → Typo Correction → (layer berikutnya)
        # =====================================================================
        resolved_message = _resolve_references_for_chat(user_message, session_id)
        if resolved_message and resolved_message.strip().lower() != user_message.strip().lower():
            print(f"[NLU] reference resolved: '{user_message}' -> '{resolved_message}'")
            processed_message = normalize_text(resolved_message)
            enhanced_processed = processed_message
            message_lower = resolved_message.lower()

        # Typo correction SEBELUM Exact QA / Knowledge / Multi-intent matching
        enhanced_processed = _apply_typo_correction(enhanced_processed)
        processed_message = _apply_typo_correction(processed_message)
        
        # =====================================================================
        # LAYER 1.0: MULTI-INTENT (sebelum Exact/Knowledge single-answer)
        # Contoh: "Syarat...? Upload foto...? Jadwal...?" → gabungan jawaban
        # =====================================================================
        multi_check_text = resolved_message or user_message or processed_message or enhanced_processed
        if is_multi_intent_query(multi_check_text) or is_multi_intent_query(enhanced_processed):
            multi_result = handle_multi_intent(multi_check_text) or handle_multi_intent(enhanced_processed)
            if multi_result and multi_result.get("reply"):
                print(f"[MULTI-INTENT] intents={multi_result.get('intents')} source={multi_result.get('source')}")
                response_data = {
                    "success": True,
                    "reply": multi_result["reply"],
                    "confidence": multi_result.get("confidence", 0.96),
                    "handoff": multi_result.get("handoff", False),
                    "intent": "multi_intent",
                    "intents": multi_result.get("intents", []),
                    "source": multi_result.get("source", "multi_intent_decomposition_synthesis"),
                    "whatsapp_url": multi_result.get("whatsapp_url"),
                }
                _safe_update_memory(
                    session_id, user_message, response_data["reply"],
                    "multi_intent", {}, response_data["confidence"],
                )
                log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                return jsonify(response_data)

        # EXACT QA
        if enhanced_processed in EXACT_QA:
            qa_intent = _exact_qa_memory_intent(enhanced_processed)
            # Greeting Exact QA → Dynamic Response (Time-Aware + Anti-Repetition)
            if qa_intent == "greeting" and NLU_ENGINE_AVAILABLE and build_dynamic_response:
                reply = _build_time_aware_greeting(session_id)
                response_data = {
                    "success": True,
                    "reply": reply,
                    "confidence": 0.99,
                    "handoff": False,
                    "intent": "greeting",
                    "source": "nlu_dynamic_greeting",
                }
                _safe_update_memory(session_id, user_message, reply, "greeting", {}, 0.99)
                log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                return jsonify(response_data)

            reply = resolve_exact_qa_reply(enhanced_processed, user_query=enhanced_processed)
            handoff_needed = enhanced_processed in [
                "berapa biaya pendaftaran",
                "biaya pendaftaran berapa",
                "apakah diskon masih ada",
                "pembayaran dilakukan ke siapa",
                "bayarnya per bulan atau mingguan",
                "berapa fee model",
                "berapa biaya kontrak model",
                "hubungi admin",
                "kontak admin",
                "nomor admin berapa",
                "saya ingin tanya admin langsung",
                "adminnya bisa dihubungi lewat wa kan",
                "admin bisa dihubungi lewat wa kan",
                "admin bisa dihubungi lewat whatsapp kan"
            ]

            response_data = {
                "success": True,
                "reply": reply,
                "confidence": 0.99,
                "handoff": handoff_needed,
                "intent": qa_intent if qa_intent != "exact_qa" else "exact_qa",
                "source": "static_dictionary",
                "whatsapp_url": build_whatsapp_url(
                    "Halo Admin Academy Next Top Model, saya ingin bertanya lebih lanjut."
                ) if handoff_needed else None
            }
            _safe_update_memory(
                session_id, user_message, reply,
                response_data["intent"], {}, 0.99,
            )
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)

        # LAYER 1.05: TECHNICAL SUPPORT KEYWORDS
        if is_technical_support_query(processed_message):
            wa_message = "Halo Admin Academy Next Top Model, saya mengalami kendala OTP/login pada website."
            response_data = {
                "success": True,
                "reply": "Jika Anda mengalami kendala OTP, login, verifikasi akun, atau reset password, silakan hubungi admin melalui WhatsApp agar dibantu pengecekan akun secara langsung.",
                "confidence": 0.95,
                "handoff": True,
                "intent": "technical_support",
                "source": "keyword_detection",
                "whatsapp_url": build_whatsapp_url(wa_message),
            }
            _safe_update_memory(session_id, user_message, response_data["reply"], "technical_support", {}, 0.95)
            return jsonify(response_data)

        # LAYER 1.08: ACADEMY KNOWLEDGE (cara join / syarat / foto / proses / jadwal)
        # Sebelum RapidFuzz & NB agar jawaban selalu dari knowledge, bukan hardcode generik
        knowledge = (
            retrieve_academy_topic_reply(enhanced_processed)
            or retrieve_academy_topic_reply(processed_message)
            or retrieve_academy_topic_reply(resolved_message)
            or retrieve_academy_topic_reply(user_message)
        )
        if knowledge:
            response_data = {
                "success": True,
                "reply": knowledge["reply"],
                "confidence": knowledge.get("confidence", 0.97),
                "handoff": knowledge.get("handoff", False),
                "intent": knowledge["intent"],
                "source": knowledge.get("source", "academy_knowledge"),
                "whatsapp_url": knowledge.get("whatsapp_url"),
            }
            _safe_update_memory(
                session_id, user_message, response_data["reply"],
                knowledge["intent"], {}, response_data["confidence"],
            )
            return jsonify(response_data)

        # LAYER 1.1: RAPIDFUZZ FUZZY MATCHING (TYPO TOLERANCE)
        # Ambil beberapa kandidat key, lalu ranking ulang berdasarkan
        # kemiripan jawaban SSOT terhadap query (pilih yang paling spesifik).
        if RAPIDFUZZ_AVAILABLE and EXACT_QA and process is not None and fuzz is not None:
            top_matches = process.extract(
                enhanced_processed, list(EXACT_QA.keys()), scorer=fuzz.WRatio, limit=8
            ) or []

            q_tokens = {w for w in enhanced_processed.split() if len(w) >= 3}
            min_similarity = 93.0 if len(q_tokens) <= 3 else 90.0
            min_overlap = 0.60 if len(q_tokens) <= 3 else 0.45

            ranked_candidates = []
            for matched_key, similarity_score, _ in top_matches:
                similarity_score = float(similarity_score)
                k_tokens = {w for w in matched_key.split() if len(w) >= 3}
                token_overlap = (len(q_tokens & k_tokens) / float(len(q_tokens))) if q_tokens else 0.0
                if similarity_score < min_similarity or token_overlap < min_overlap:
                    continue

                target = EXACT_QA_TARGETS.get(matched_key, "")
                answer_preview = resolve_exact_qa_reply(matched_key, user_query=enhanced_processed)
                answer_score = 0.0
                if KNOWLEDGE_RETRIEVER_AVAILABLE and select_knowledge_answer:
                    try:
                        from modules.knowledge_retriever import score_query_similarity
                        answer_score = float(score_query_similarity(enhanced_processed, answer_preview or matched_key))
                    except Exception:
                        answer_score = 0.0

                # Prefer jawaban spesifik: gabungan fuzzy key + skor jawaban + panjang key (lebih spesifik)
                specificity = (len(matched_key.split()) / 12.0) + (0.15 if target and target != "become_model_info" else 0.0)
                combined = (similarity_score / 100.0) * 0.45 + answer_score * 0.45 + min(0.2, specificity)
                ranked_candidates.append((combined, similarity_score, token_overlap, matched_key, answer_preview))

            if ranked_candidates:
                ranked_candidates.sort(key=lambda x: x[0], reverse=True)
                combined, similarity_score, token_overlap, matched_key, reply = ranked_candidates[0]
                print(
                    f"RAPIDFUZZ RANKED: '{enhanced_processed}' -> '{matched_key}' "
                    f"(sim={similarity_score:.1f}%, overlap={token_overlap:.2f}, combined={combined:.3f}, candidates={len(ranked_candidates)})"
                )
                qa_intent = _exact_qa_memory_intent(matched_key)
                if qa_intent == "greeting" and NLU_ENGINE_AVAILABLE and build_dynamic_response:
                    reply = _build_time_aware_greeting(session_id)
                    response_data = {
                        "success": True,
                        "reply": reply,
                        "confidence": round(similarity_score / 100.0, 2),
                        "handoff": False,
                        "intent": "greeting",
                        "source": "nlu_dynamic_greeting",
                    }
                    _safe_update_memory(session_id, user_message, reply, "greeting", {}, response_data["confidence"])
                    return jsonify(response_data)

                handoff_needed = matched_key in [
                    "berapa biaya pendaftaran",
                    "biaya pendaftaran berapa",
                    "apakah diskon masih ada",
                    "pembayaran dilakukan ke siapa",
                    "bayarnya per bulan atau mingguan",
                    "berapa fee model",
                    "berapa biaya kontrak model",
                    "hubungi admin",
                    "kontak admin",
                    "nomor admin berapa",
                    "saya ingin tanya admin langsung",
                    "adminnya bisa dihubungi lewat wa kan",
                    "admin bisa dihubungi lewat wa kan",
                    "admin bisa dihubungi lewat whatsapp kan"
                ]
                response_data = {
                    "success": True,
                    "reply": reply,
                    "confidence": round(similarity_score / 100.0, 2),
                    "handoff": handoff_needed,
                    "intent": qa_intent if qa_intent != "exact_qa" else "exact_qa_rapidfuzz",
                    "source": "rapidfuzz_matching",
                    "whatsapp_url": build_whatsapp_url(
                        "Halo Admin Academy Next Top Model, saya ingin bertanya lebih lanjut."
                    ) if handoff_needed else None
                }
                _safe_update_memory(
                    session_id, user_message, reply,
                    response_data["intent"], {}, response_data["confidence"],
                )
                return jsonify(response_data)
            else:
                print(f"RAPIDFUZZ REJECT ALL for '{enhanced_processed}' -> lanjut layer berikutnya")

        # SENSITIVE KEYWORD DETECTION (Layer 2)
        try:
            if is_sensitive_query(processed_message) and not is_multi_intent_query(processed_message):
                response_data = {
                    "success": True,
                    "reply": "Untuk informasi biaya pendaftaran, promo, diskon, sistem pembayaran, booking, kontrak, dan fee model, silakan hubungi admin Academy Next Top Model melalui WhatsApp agar mendapatkan informasi terbaru dan akurat.",
                    "confidence": 0.95,
                    "handoff": True,
                    "intent": "sensitive_intent",
                    "source": "keyword_detection",
                    "whatsapp_url": f"https://wa.me/{WHATSAPP_ADMIN}?text={quote('Halo Admin Academy Next Top Model, saya ingin bertanya mengenai biaya atau informasi sensitif.')}"
                }
                _safe_update_memory(session_id, user_message, response_data["reply"], "sensitive_intent", {}, 0.95)
                return jsonify(response_data)
        except Exception as se:
            print(f"[SENSITIVE] Error: {se}")

        # =====================================================================
        # LAYER 4: DYNAMIC QUERY (real-time DB/API) — sebelum Naive Bayes
        # Urutan: count → list → recommendation → status (hindari misroute filter→nama)
        # =====================================================================
        if is_model_count_query(processed_message) or is_model_count_query(user_message):
            q = processed_message if is_model_count_query(processed_message) else user_message
            result = handle_model_count(q)
            # BUG-9 (audit Tahap 2/4): pakai confidence dari handler (beda untuk
            # sukses/gagal/DB-unreachable), bukan literal 0.95 yang seragam.
            conf = result.get("confidence", 0.95)
            response_data = {
                "success": True,
                "reply": result["reply"],
                "confidence": conf,
                "handoff": result.get("handoff", False),
                "intent": result["intent"],
                "source": result["source"],
                "whatsapp_url": result.get("whatsapp_url")
            }
            _safe_update_memory(session_id, user_message, response_data["reply"], result["intent"], {}, conf)
            return jsonify(response_data)

        if is_model_recommendation_query(processed_message) or is_model_recommendation_query(user_message):
            q = processed_message if is_model_recommendation_query(processed_message) else user_message
            result = handle_model_recommendation(q)
            conf = result.get("confidence", 0.95)
            response_data = {
                "success": True,
                "reply": result["reply"],
                "confidence": conf,
                "handoff": result.get("handoff", False),
                "intent": result["intent"],
                "source": result["source"],
                "whatsapp_url": result.get("whatsapp_url")
            }
            _safe_update_memory(session_id, user_message, response_data["reply"], result["intent"], {}, conf)
            return jsonify(response_data)

        if is_model_list_query(processed_message) or is_model_list_query(user_message):
            q = processed_message if is_model_list_query(processed_message) else user_message
            result = handle_model_list(q)
            conf = result.get("confidence", 0.95)
            response_data = {
                "success": True,
                "reply": result["reply"],
                "confidence": conf,
                "handoff": result.get("handoff", False),
                "intent": result["intent"],
                "source": result["source"],
                "whatsapp_url": result.get("whatsapp_url")
            }
            _safe_update_memory(session_id, user_message, response_data["reply"], result["intent"], {}, conf)
            return jsonify(response_data)

        if is_model_status_query(processed_message) or is_model_status_query(user_message):
            q = processed_message if is_model_status_query(processed_message) else user_message
            result = handle_model_status(q)
            conf = result.get("confidence", 0.95)
            response_data = {
                "success": True,
                "reply": result["reply"],
                "confidence": conf,
                "handoff": result.get("handoff", False),
                "intent": result["intent"],
                "source": result["source"],
                "whatsapp_url": result.get("whatsapp_url")
            }
            _safe_update_memory(session_id, user_message, response_data["reply"], result["intent"], {}, conf)
            return jsonify(response_data)

        # =====================================================================
        # LAYER 3: MACHINE LEARNING CLASSIFICATION (TF-IDF + MULTINOMIAL NAIVE BAYES)
        #          + NLU ENGINE (Conversation Memory, Entity, Reasoning, Dynamic Response)
        # =====================================================================
        if NB_MODEL_LOADED and nb_model and tfidf_vectorizer:
            try:
                # 3a. Vectorize dengan teks yang sudah dinormalisasi (+ typo/reference bila ada)
                vec_input = tfidf_vectorizer.transform([processed_message])
                predicted_intent = nb_model.predict(vec_input)[0]
                probabilities    = nb_model.predict_proba(vec_input)[0]
                confidence_score = float(np.max(probabilities))
                print(f"[NB] intent='{predicted_intent}'  confidence={confidence_score:.2f}")

                # 3b. NLU Engine: context refinement, entity, reasoning, dynamic response
                if NLU_ENGINE_AVAILABLE and process_nlu:
                    nlu_input = resolved_message or user_message
                    nlu_result = process_nlu(
                        nlu_input, session_id,
                        str(predicted_intent), confidence_score
                    )
                    nlu_intent  = nlu_result["intent"]
                    nlu_conf    = nlu_result["confidence"]
                    nlu_reply   = nlu_result.get("reply", "")
                    nlu_entities = nlu_result.get("entities", {})

                    # Jika NLU menghasilkan reply berkualitas (non-empty), gunakan
                    if nlu_reply and nlu_intent not in ("low_confidence", "unknown"):
                        is_sensitive = (nlu_intent == "sensitive_intent")
                        response_data = {
                            "success":      True,
                            "reply":        nlu_reply,
                            "confidence":   round(nlu_conf, 2),
                            "handoff":      is_sensitive,
                            "intent":       nlu_intent,
                            "source":       "nlu_engine+naive_bayes",
                            "entities":     nlu_entities,
                            "whatsapp_url": "https://wa.me/6281214237742?text=Halo%20Admin%20Academy%20Next%20Top%20Model" if is_sensitive else None
                        }
                        update_memory(session_id, user_message, nlu_reply, nlu_intent, nlu_entities)
                        log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                        return jsonify(response_data)

                    # Smart Clarification jika confidence rendah
                    if nlu_intent == "low_confidence" and nlu_reply:
                        response_data = {
                            "success":    True,
                            "reply":      nlu_reply,
                            "confidence": round(nlu_conf, 2),
                            "handoff":    False,
                            "intent":     "clarification",
                            "source":     "nlu_clarification",
                        }
                        log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                        return jsonify(response_data)

                # 3c. Fallback: static NB responses (bila NLU tidak tersedia)
                # model_info / registration_info dari NB bersifat generik → skip
                if str(predicted_intent) in ("model_info", "registration_info", "become_model_info"):
                    predicted_intent = "unknown"

                NB_INTENT_RESPONSES = {
                    "greeting":          "Halo! Selamat datang di Academy Next Top Model 👋. Ada yang bisa kami bantu mengenai pendaftaran, lokasi, atau program modeling?",
                    "location_info":     "Lokasi Academy Next Top Model berada di Trans Studio Mall Cibubur, GF Floor Second Atrium.",
                    "registration_info": "Pendaftaran Academy Next Top Model dibuka secara online melalui website ini. Silakan klik menu Register atau hubungi admin via WhatsApp.",
                    "program_info":      "Program di Academy Next Top Model mencakup: Catwalk Basic, Catwalk Advanced, Runway Class, Beauty Class, Pose Class, Photoshoot Session, Self Branding, dan Public Speaking.",
                    "become_model_info": "Untuk bergabung sebagai model di Academy Next Top Model, Anda dapat mendaftar kursus pelatihan terlebih dahulu. Kami menerima usia 4–25 tahun.",
                    "homepage_info":     "Academy Next Top Model adalah lembaga pelatihan modeling terpercaya yang mencetak model profesional untuk dunia fashion dan entertainment Indonesia.",
                    "technical_support": "Jika mengalami kendala teknis (login, akun, atau website), silakan hubungi admin kami melalui WhatsApp.",
                    "sensitive_intent":  "Untuk informasi biaya pendaftaran, promo, diskon, sistem pembayaran, booking, kontrak, dan fee model, silakan hubungi admin Academy Next Top Model via WhatsApp.",
                    "gratitude":         "Sama-sama! Jangan ragu untuk bertanya kembali jika ada yang ingin diketahui tentang Academy Next Top Model. 😊",
                }

                if confidence_score >= 0.50 and predicted_intent in NB_INTENT_RESPONSES:
                    reply_text   = NB_INTENT_RESPONSES[predicted_intent]
                    is_sensitive = (predicted_intent == "sensitive_intent")
                    response_data = {
                        "success":      True,
                        "reply":        reply_text,
                        "confidence":   round(confidence_score, 2),
                        "handoff":      is_sensitive,
                        "intent":       predicted_intent,
                        "source":       "multinomial_naive_bayes",
                        "whatsapp_url": "https://wa.me/6281214237742?text=Halo%20Admin%20Academy%20Next%20Top%20Model" if is_sensitive else None
                    }
                    log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                    return jsonify(response_data)

            except Exception as nb_err:
                print(f"[NB] Error: {nb_err}")

        # Secondary safety net (disengaja): Multi-Intent fallback pada user_message mentah.
        # Primary multi-intent sudah jalan di awal chat(); blok ini menangkap edge case
        # bila resolve_reference membuat teks resolved beda deteksi vs user_message.
        if is_multi_intent_query(user_message):
            detected_intent = "multi_intent"
            print("DETECTED INTENT:", detected_intent)
            multi_result = handle_multi_intent(user_message)

            if multi_result and multi_result.get("reply"):
                handoff_needed = bool(multi_result.get("handoff")) or is_sensitive_query(user_message)
                whatsapp_url = multi_result.get("whatsapp_url")
                if handoff_needed and not whatsapp_url:
                    whatsapp_url = "https://wa.me/6281214237742?text=Halo%20Admin%20Academy%20Next%20Top%20Model,%20saya%20ingin%20bertanya%20mengenai%20biaya%20atau%20informasi%20lainnya."

                response_data = {
                    "success": True,
                    "reply": multi_result["reply"],
                    "confidence": multi_result.get("confidence", 0.95),
                    "handoff": handoff_needed,
                    "intent": detected_intent,
                    "intents": multi_result.get("intents", []),
                    "source": multi_result.get("source", "multi_intent_handler"),
                    "whatsapp_url": whatsapp_url
                }
                print("REPLY:", multi_result["reply"])
                log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                return jsonify(response_data)

        # 1.1 Greeting - HIGHEST PRIORITY
        if is_greeting_query(user_message):
            detected_intent = "greeting"
            print("DETECTED INTENT:", detected_intent)
            reply = get_static_response(detected_intent)
            response_data = {
                "success": True,
                "reply": reply,
                "confidence": 0.99,
                "handoff": False,
                "intent": detected_intent,
                "source": "static_dictionary"
            }
            print("REPLY:", reply)
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 1.2 Academy Sub-Intents - SPECIFIC ANSWERS for factual questions
        if is_academy_founded_query(user_message):
            detected_intent = "academy_founded"
            print("DETECTED INTENT:", detected_intent)
            response_data = {
                "success": True,
                "reply": "Academy Next Top Model telah berjalan sejak tahun 2015.",
                "confidence": 0.99,
                "handoff": False,
                "intent": detected_intent,
                "source": "static_dictionary"
            }
            print("REPLY:", response_data["reply"])
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        if is_academy_purpose_query(user_message):
            detected_intent = "academy_purpose"
            print("DETECTED INTENT:", detected_intent)
            response_data = {
                "success": True,
                "reply": "Tujuan Academy Next Top Model adalah membantu pengembangan kemampuan model, membangun portofolio profesional, serta membuka peluang model untuk berkembang di dunia modeling.",
                "confidence": 0.99,
                "handoff": False,
                "intent": detected_intent,
                "source": "static_dictionary"
            }
            print("REPLY:", response_data["reply"])
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        if is_academy_definition_query(user_message):
            detected_intent = "academy_definition"
            print("DETECTED INTENT:", detected_intent)
            response_data = {
                "success": True,
                "reply": "Academy Next Top Model adalah academy modeling yang menyediakan pelatihan, pengembangan talent, dan pengelolaan portofolio digital untuk mendukung calon model, model aktif, dan kebutuhan klien.",
                "confidence": 0.99,
                "handoff": False,
                "intent": detected_intent,
                "source": "static_dictionary"
            }
            print("REPLY:", response_data["reply"])
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        if is_academy_field_query(user_message):
            detected_intent = "academy_field"
            print("DETECTED INTENT:", detected_intent)
            response_data = {
                "success": True,
                "reply": "Academy Next Top Model bergerak di bidang modeling, pengembangan talent, pelatihan model, dan manajemen portofolio digital.",
                "confidence": 0.99,
                "handoff": False,
                "intent": detected_intent,
                "source": "static_dictionary"
            }
            print("REPLY:", response_data["reply"])
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 1.3 Sensitive intent (biaya, harga, pembayaran) - HIGH PRIORITY BEFORE REGISTRATION
        if is_sensitive_query(message_lower):
            detected_intent = "sensitive_intent"
            print("DETECTED INTENT:", detected_intent)
            wa_url = "https://wa.me/6281214237742?text=Halo%20Admin%20Academy%20Next%20Top%20Model"
            response_data = {
                "success": True,
                "reply": "Untuk informasi mengenai biaya, pembayaran, promo, diskon, kontrak, atau booking, silakan hubungi admin Academy Next Top Model melalui WhatsApp agar mendapatkan informasi terbaru.",
                "confidence": 0.95,
                "handoff": True,
                "intent": detected_intent,
                "source": "sensitive_detection",
                "whatsapp_url": wa_url
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 1.3 Sensitive intent (biaya, harga, pembayaran) - HIGH PRIORITY BEFORE REGISTRATION
        if is_sensitive_query(message_lower):
            wa_url = "https://wa.me/6281214237742?text=Halo%20Admin%20Academy%20Next%20Top%20Model"
            response_data = {
                "success": True,
                "reply": "Untuk informasi mengenai biaya, pembayaran, promo, diskon, kontrak, atau booking, silakan hubungi admin Academy Next Top Model melalui WhatsApp agar mendapatkan informasi terbaru.",
                "confidence": 0.95,
                "handoff": True,
                "intent": "sensitive_intent",
                "source": "sensitive_detection",
                "whatsapp_url": wa_url
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 1.3 About Academy - EXACT MATCH for important questions
        if is_about_academy_query(user_message):
            detected_intent = "about_academy"
            print("DETECTED INTENT:", detected_intent)
            reply = get_static_response(detected_intent)
            response_data = {
                "success": True,
                "reply": reply,
                "confidence": 0.98,
                "handoff": False,
                "intent": detected_intent,
                "source": "static_dictionary"
            }
            print("REPLY:", reply)
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 1.4 Homepage Info - EXACT MATCH for important questions
        if is_homepage_info_query(user_message):
            detected_intent = "homepage_info"
            print("DETECTED INTENT:", detected_intent)
            reply = get_static_response(detected_intent)
            response_data = {
                "success": True,
                "reply": reply,
                "confidence": 0.98,
                "handoff": False,
                "intent": detected_intent,
                "source": "static_dictionary"
            }
            print("REPLY:", reply)
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 1.5 Registration Steps Processing (HIGHEST PRIORITY - Before All Other Systems)
        if is_registration_steps_query(user_message):
            detected_intent = "registration_steps"
            print("DETECTED INTENT:", detected_intent)
            response_data = {
                "success": True,
                "reply": (
                    "Cara pendaftaran di Academy Next Top Model adalah sebagai berikut: "
                    "1. Buka halaman Register pada website. "
                    "2. Isi data diri lengkap seperti nama, email, nomor WhatsApp, dan password. "
                    "3. Lakukan verifikasi akun jika diminta. "
                    "4. Login ke akun yang sudah dibuat. "
                    "5. Lengkapi profil dan portofolio. "
                    "6. Ikuti instruksi lanjutan dari sistem atau admin."
                ),
                "confidence": 0.97,
                "handoff": False,
                "intent": detected_intent,
                "source": "static_dictionary"
            }
            print("REPLY:", response_data["reply"])
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 1.5 Static Intents Dictionary Processing (HIGHEST PRIORITY for Q&A - Before Academy System)
        static_intent_result = detect_intent_by_keywords(user_message)
        if static_intent_result["intent"]:
            intent_name = static_intent_result["intent"]
            responses = static_intent_result["responses"]
            reply = random.choice(responses)
            
            # Determine if handoff is needed
            handoff = intent_name in ["contact_admin"]
            whatsapp_url = "https://wa.me/6281214237742?text=Halo%20Admin%20Academy%20Next%20Top%20Model" if handoff else None
            
            response_data = {
                "success": True,
                "reply": reply,
                "confidence": 0.95,
                "handoff": handoff,
                "intent": intent_name,
                "source": "static_dictionary",
                "whatsapp_url": whatsapp_url
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # Registration Steps Processing (HIGH PRIORITY - Before Academy System Legacy)
        if is_registration_steps_query(user_message):
            response_data = {
                "success": True,
                "reply": (
                    "Cara pendaftaran di Academy Next Top Model adalah sebagai berikut: "
                    "1. Buka halaman Register pada website. "
                    "2. Isi data diri lengkap seperti nama, email, nomor WhatsApp, dan password. "
                    "3. Lakukan verifikasi akun jika diminta. "
                    "4. Login ke akun yang sudah dibuat. "
                    "5. Lengkapi profil dan portofolio. "
                    "6. Ikuti instruksi lanjutan dari sistem atau admin."
                ),
                "confidence": 0.96,
                "handoff": False,
                "intent": "registration_steps",
                "source": "static_dictionary"
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 2.6 Static data lookup (schedule, registration, programs, etc) - Only for dynamic intents not covered by static_intents
        # Skip academy_search completely since static_intents are handled above
        print(f"DEBUG: Skipping academy_search - static_intents already processed above")
        
        # 2.7 FAQ matching
        print(f"DEBUG: Calling search_faq with '{user_message}'")
        faq_result = search_faq(user_message)
        print(f"DEBUG: search_faq returned: {faq_result}")
        print(f"DEBUG: faq_result type: {type(faq_result)}")
        if faq_result:
            print(f"DEBUG: Returning faq_result as JSON")
            log_chat_interaction(user_message, faq_result, session_id, language, user_ip, user_agent, start_time)
            return jsonify(faq_result)
        else:
            print(f"DEBUG: faq_result is None, continuing to static intents")
        
        # Academy System
        # Benefit info
        if is_benefit_query(message_lower):
            return jsonify({
                "success": True,
                "reply": (
                    "Benefit mengikuti Academy Next Top Model antara lain memperoleh pelatihan modeling, "
                    "pengembangan portofolio, peningkatan kemampuan catwalk, pose, public speaking, "
                    "self branding, dan pengalaman untuk mendukung karier di dunia modeling."
                ),
                "confidence": 0.95,
                "handoff": False,
                "intent": "benefit_info",
                "source": "academy_benefit"
            })
        
        # Schedule info
        if is_schedule_query(message_lower):
            return jsonify({
                "success": True,
                "reply": "Kelas di Academy Next Top Model berlangsung setiap hari Sabtu, dari jam 11.00 hingga 15.00.",
                "confidence": 0.95,
                "handoff": False,
                "intent": "schedule_info",
                "source": "academy_schedule"
            })
        
        # Location info - DISABLED - Prioritize EXACT_QA instead
        # if is_location_query(message_lower):
        #     return jsonify({
        #         "success": True,
        #         "reply": "Lokasi Academy Next Top Model berada di Trans Studio Mall Cibubur, GF Floor Second Atrium.",
        #         "confidence": 0.95,
        #         "handoff": False,
        #         "intent": "location_info",
        #         "source": "academy_location"
        #     })
        
        # 1.2 Sensitive intent (harga, diskon, kontrak, booking) - HIGHEST PRIORITY AFTER OWNER
        sensitive_keywords = [
            'biaya', 'harga', 'bayar', 'pembayaran', 'kartu kredit', 'transfer', 'cash',
            'kontrak', 'syarat', 'perjanjian', 'fee', 'tarif', 'endorsement', 'booking',
            'reservasi', 'dp', 'uang muka', 'cicilan', 'kredit',
            'diskon', 'promo', 'potongan harga', 'harga terbaru', 'masih berlaku', 'masih aktif'
        ]
        
        # Check for sensitive keywords first
        if any(keyword in message_lower for keyword in sensitive_keywords):
            whatsapp_url = f"https://wa.me/{WHATSAPP_ADMIN.replace('+', '')}?text={quote(user_message)}"
            
            response_data = {
                "success": True,
                "reply": (
                    "Untuk informasi mengenai biaya, diskon, kontrak, atau booking, "
                    "silakan menghubungi admin kami melalui WhatsApp."
                ),
                "confidence": 0.90,
                "handoff": True,
                "whatsapp_url": whatsapp_url,
                "intent": "sensitive_intent",
                "source": "sensitive_detection"
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # LAYER 2: DATABASE LOOKUP
        
        # 2.1 Model count from Laravel API
        if is_model_count_query(message_lower):
            count = get_models_count()
            response_data = {
                "success": True,
                "reply": f"Saat ini terdapat {count} model yang terdaftar di website Academy Next Top Model.",
                "confidence": 0.96,
                "handoff": False,
                "intent": "model_count",
                "source": "model_database"
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 2.2 Model recommendation
        if is_model_recommendation_query(message_lower):
            theme_key = detect_recommendation_theme(user_message)
            reply = random.choice(model_recommendation_responses.get(theme_key, model_recommendation_responses["general"]))

            return jsonify({
                "success": True,
                "reply": reply,
                "confidence": 0.90,
                "handoff": True,
                "intent": "model_recommendation",
                "source": "academy_guidance",
                "whatsapp_url": "https://wa.me/6281214237742?text=Halo%20Admin%2C%20saya%20ingin%20rekomendasi%20model."
            })
        
        # 2.3 Model list from Laravel API
        if is_model_list_query(message_lower):
            models = get_all_models()
            if models:
                names = [m["nama_model"] for m in models[:12] if m.get("nama_model")]
                preview = ", ".join(names)
                
                more_text = ""
                if len(models) > 12:
                    more_text = f" dan {len(models)-12} model lainnya"
                
                response_data = {
                    "success": True,
                    "reply": f"Model yang ada di website antara lain: {preview}{more_text}.",
                    "confidence": 0.95,
                    "handoff": False,
                    "intent": "model_list",
                    "source": "model_database"
                }
                log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                return jsonify(response_data)
            else:
                response_data = {
                    "success": True,
                    "reply": "Saat ini saya belum dapat mengambil data model dari database.",
                    "confidence": 0.60,
                    "handoff": False,
                    "intent": "model_list",
                    "source": "model_database"
                }
                log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                return jsonify(response_data)
        
        # 2.3 Model status from Laravel API
        if is_model_status_query(message_lower):
            possible_name = extract_possible_model_name(user_message)
            print("Searching model name:", possible_name)
            models = get_all_models()
            model = find_model_flexible(possible_name, models or [])

            if model:
                status_raw = str(model.get("status", "")).lower()
                model_name = model.get("nama_model") or model.get("name") or "Unknown"

                if status_raw in ["available", "aktif", "tersedia", "1", "true"]:
                    status_text = "available"
                else:
                    status_text = "not available"

                response_data = {
                    "success": True,
                    "reply": f"Model {model_name} saat ini berstatus {status_text}.",
                    "confidence": 0.96,
                    "handoff": False,
                    "intent": "model_status",
                    "source": "model_database"
                }
                log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                return jsonify(response_data)
            else:
                response_data = {
                    "success": True,
                    "reply": f"Saya belum menemukan model dengan nama {possible_name} pada data website saat ini.",
                    "confidence": 0.75,
                    "handoff": False,
                    "intent": "model_status",
                    "source": "model_database"
                }
                log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                return jsonify(response_data)
        
        # 1.4 Static Intents Dictionary Processing (HIGHEST PRIORITY for Q&A - Before Academy System)
        static_intent_result = detect_intent_by_keywords(user_message)
        if static_intent_result["intent"]:
            intent_name = static_intent_result["intent"]
            responses = static_intent_result["responses"]
            reply = random.choice(responses)
            
            # Determine if handoff is needed
            handoff = intent_name in ["contact_admin"]
            whatsapp_url = "https://wa.me/6281214237742?text=Halo%20Admin%20Academy%20Next%20Top%20Model" if handoff else None
            
            response_data = {
                "success": True,
                "reply": reply,
                "confidence": 0.95,
                "handoff": handoff,
                "intent": intent_name,
                "source": "static_dictionary",
                "whatsapp_url": whatsapp_url
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # Registration Steps Processing (HIGH PRIORITY - Before Academy System Legacy)
        if is_registration_steps_query(user_message):
            response_data = {
                "success": True,
                "reply": (
                    "Cara pendaftaran di Academy Next Top Model adalah sebagai berikut: "
                    "1. Buka halaman Register pada website. "
                    "2. Isi data diri lengkap seperti nama, email, nomor WhatsApp, dan password. "
                    "3. Lakukan verifikasi akun jika diminta. "
                    "4. Login ke akun yang sudah dibuat. "
                    "5. Lengkapi profil dan portofolio. "
                    "6. Ikuti instruksi lanjutan dari sistem atau admin."
                ),
                "confidence": 0.96,
                "handoff": False,
                "intent": "registration_steps",
                "source": "static_dictionary"
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 2.6 Static data lookup (schedule, registration, programs, etc) - Only for dynamic intents not covered by static_intents
        # Skip academy_search completely since static_intents are handled above
        print(f"DEBUG: Skipping academy_search - static_intents already processed above")
        
        # 2.7 FAQ matching
        print(f"DEBUG: Calling search_faq with '{user_message}'")
        faq_result = search_faq(user_message)
        print(f"DEBUG: search_faq returned: {faq_result}")
        print(f"DEBUG: faq_result type: {type(faq_result)}")
        if faq_result:
            print(f"DEBUG: Returning faq_result as JSON")
            log_chat_interaction(user_message, faq_result, session_id, language, user_ip, user_agent, start_time)
            return jsonify(faq_result)
        else:
            print(f"DEBUG: faq_result is None, continuing to static intents")
        
        # 1.2 Sensitive intent (harga, diskon, kontrak, booking) - HIGHEST PRIORITY AFTER OWNER
        sensitive_keywords = [
            'biaya', 'harga', 'bayar', 'pembayaran', 'kartu kredit', 'transfer', 'cash',
            'kontrak', 'syarat', 'perjanjian', 'fee', 'tarif', 'endorsement', 'booking',
            'reservasi', 'dp', 'uang muka', 'cicilan', 'kredit',
            'diskon', 'promo', 'potongan harga', 'harga terbaru', 'masih berlaku', 'masih aktif'
        ]
        
        # Check for sensitive keywords first
        if any(keyword in message_lower for keyword in sensitive_keywords):
            whatsapp_url = f"https://wa.me/{WHATSAPP_ADMIN.replace('+', '')}?text={quote(user_message)}"
            
            response_data = {
                "success": True,
                "reply": (
                    "Untuk informasi mengenai biaya, diskon, kontrak, atau booking, "
                    "silakan menghubungi admin kami melalui WhatsApp."
                ),
                "confidence": 0.90,
                "handoff": True,
                "whatsapp_url": whatsapp_url,
                "intent": "sensitive_intent",
                "source": "sensitive_detection"
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 2.3 About academy query
        if is_about_query(message_lower):
            response_data = {
                "success": True,
                "reply": "Academy Next Top Model adalah academy modeling yang berfokus pada pengembangan talent, pelatihan modeling, dan portofolio digital.",
                "confidence": 0.95,
                "handoff": False,
                "intent": "about_academy",
                "source": "academy_about"
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # 2.4 Static data lookup (schedule, registration, programs, etc) - Skip registration queries
        # Skip academy_search for registration queries since they're handled by registration_steps above
        if not is_registration_steps_query(user_message):
            print(f"DEBUG: Calling academy_search_simple with '{user_message}'")
            academy_result = academy_search_simple(user_message, language)
            print(f"DEBUG: academy_search_simple returned: {academy_result}")
            print(f"DEBUG: academy_result type: {type(academy_result)}")
            if academy_result and isinstance(academy_result, dict):
                print(f"DEBUG: Processing academy_result for JSON response")
                # Convert "answer" field to "reply" for consistency
                response_data = {
                    "success": True,
                    "reply": academy_result.get("answer", academy_result.get("reply", "Response tidak tersedia.")),
                    "confidence": academy_result.get("confidence", 0.80),
                    "handoff": academy_result.get("handoff", False),
                    "intent": academy_result.get("intent", "unknown"),
                    "source": academy_result.get("source", "academy_search")
                }
                log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                print(f"DEBUG: Returning processed academy_result as JSON")
                return jsonify(response_data)
            else:
                print(f"DEBUG: academy_result is None or not dict, continuing to FAQ")
        else:
            print(f"DEBUG: Skipping academy_search - registration query detected")
        
        # 2.4 FAQ matching
        print(f"DEBUG: Calling search_faq with '{user_message}'")
        faq_result = search_faq(user_message)
        print(f"DEBUG: search_faq returned: {faq_result}")
        print(f"DEBUG: faq_result type: {type(faq_result)}")
        if faq_result:
            print(f"DEBUG: Returning faq_result as JSON")
            log_chat_interaction(user_message, faq_result, session_id, language, user_ip, user_agent, start_time)
            return jsonify(faq_result)
        else:
            print(f"DEBUG: faq_result is None, continuing to AI fallback")
        
        # 3.0 Contact admin intent - BEFORE AI FALLBACK
        if is_contact_admin_query(message_lower):
            wa_url = "https://wa.me/6281214237742?text=Halo%20Admin%20Academy%20Next%20Top%20Model"
            response_data = {
                "success": True,
                "reply": "Silakan hubungi admin Academy Next Top Model melalui WhatsApp untuk informasi lebih lanjut.",
                "confidence": 0.95,
                "handoff": True,
                "intent": "contact_admin",
                "source": "academy_contact",
                "whatsapp_url": wa_url
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
        # LOCATION QUERY DETECTOR - SEBELUM FALLBACK
        if is_location_query(processed_message):
            return jsonify({
                "success": True,
                "reply": "Lokasi Academy Next Top Model berada di Trans Studio Mall Cibubur, GF Floor Second Atrium.",
                "confidence": 0.99,
                "handoff": False,
                "intent": "location_info",
                "source": "static_dictionary"
            })

        # LAYER 3: MACHINE LEARNING INTENT CLASSIFICATION (MULTINOMIAL NAIVE BAYES + TF-IDF)
        if NB_MODEL_LOADED and nb_model and tfidf_vectorizer:
            try:
                vec_input = tfidf_vectorizer.transform([processed_message])
                predicted_intent = nb_model.predict(vec_input)[0]
                probabilities = nb_model.predict_proba(vec_input)[0]
                confidence_score = float(np.max(probabilities))
                print(f"NAIVE BAYES INFERENCE: intent='{predicted_intent}', confidence={confidence_score:.2f}")

                # Hindari jawaban NB generik untuk topik yang sudah punya knowledge kanonis
                if str(predicted_intent) in ("model_info", "registration_info", "become_model_info"):
                    predicted_intent = "unknown"

                INTENT_RESPONSES = {
                    "greeting": "Halo! Selamat datang di Academy Next Top Model. Ada yang bisa kami bantu mengenai pendaftaran, lokasi, atau program model?",
                    "location_info": "Lokasi studio utama Academy Next Top Model berada di Jl. Terusan Pasirkoja No. 70, Kota Bandung, Jawa Barat. Kami juga memfasilitasi kelas offline dan online.",
                    "registration_info": "Pendaftaran peserta baru Academy Next Top Model dibuka secara online melalui website ini. Silakan klik menu Register di bagian atas atau hubungi admin via WhatsApp.",
                    "program_info": "Academy Next Top Model menyediakan berbagai kelas pelatihan: Modeling Basic, Advanced Runway, Catwalk & Pose Class, sertifikasi profesional, dan penyaluran karir ke agency.",
                    "become_model_info": "Untuk menjadi bagian dari model kami, Anda dapat mendaftar kursus pelatihan modeling terlebih dahulu. Kami menerima usia dari 5 hingga 35 tahun.",
                    "model_info": "Kami memiliki talenta model profesional pria dan wanita untuk photoshoot, fashion show, commercial ad, dan event endorsement. Detail daftar model dapat dilihat di menu Models.",
                    "homepage_info": "Academy Next Top Model adalah lembaga pelatihan modeling terpercaya yang mencetak model profesional siap kerja untuk dunia fashion dan entertainment.",
                    "technical_support": "Jika mengalami kendala teknis pada sistem pendaftaran atau website, Anda dapat menghubungi tim support kami melalui kontak WhatsApp admin.",
                    "sensitive_intent": "Untuk informasi biaya pendaftaran, promo, diskon, sistem pembayaran, booking, kontrak, dan fee model, silakan hubungi admin Academy Next Top Model via WhatsApp."
                }

                if confidence_score >= 0.40 and predicted_intent in INTENT_RESPONSES:
                    reply_text = INTENT_RESPONSES[predicted_intent]
                    is_sensitive = (predicted_intent == "sensitive_intent")
                    response_data = {
                        "success": True,
                        "reply": reply_text,
                        "confidence": round(confidence_score, 2),
                        "handoff": is_sensitive,
                        "intent": predicted_intent,
                        "source": "multinomial_naive_bayes",
                        "whatsapp_url": "https://wa.me/6281214237742?text=Halo%20Admin%20Academy%20Next%20Top%20Model" if is_sensitive else None
                    }
                    log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
                    return jsonify(response_data)
            except Exception as nb_err:
                print(f"NAIVE BAYES INFERENCE NOTICE: {nb_err}")

        # LAYER 4: AI FALLBACK
        
        # 3.1 Smart LLM fallback dengan batasan scope Academy Next Top Model
        try:
            llm_reply = smart_llm_fallback(user_message, message_lower)
            # Tambahkan batasan scope untuk LLM fallback
            scope_limited_reply = f"Saya adalah asisten virtual khusus Academy Next Top Model. {llm_reply}"
            response_data = {
                "success": True,
                "reply": scope_limited_reply,
                "confidence": 0.70,
                "handoff": False,
                "intent": "ai_fallback",
                "source": "openai_fallback" if CHATBOT_MODE == "openai" else "local_fallback"
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        except Exception as e:
            print("Fallback final error:", e)
            # Fallback aman dengan batasan scope yang jelas
            response_data = {
                "success": True,
                "reply": "Saya adalah asisten virtual khusus Academy Next Top Model. Saya dapat membantu informasi seputar program pelatihan, pendaftaran, jadwal, lokasi, model, benefit, dan kontak admin. Silakan ajukan pertanyaan Anda terkait informasi Academy Next Top Model.",
                "confidence": 0.40,
                "handoff": False,
                "intent": "safe_fallback",
                "source": "safe_fallback"
            }
            log_chat_interaction(user_message, response_data, session_id, language, user_ip, user_agent, start_time)
            return jsonify(response_data)
        
    except Exception as e:
        import traceback
        print(f"Error in chat endpoint: {str(e)}")
        traceback.print_exc()
        return jsonify({
            "success": False,
            "reply": "Maaf, terjadi kesalahan internal. Silakan coba lagi.",
            "confidence": 0,
            "handoff": False,
            "intent": "error",
            "source": "system"
        }), 500

# =========================
# HEALTH ENDPOINT
# =========================
@app.route("/health", methods=["GET"])
def health():
    return jsonify({
        "status": "ok",
        "mode": CHATBOT_MODE,
        "openai_available": bool(openai_client),
        "faq_count": len(FAQ_DATA),
        "nb_model_loaded": bool(NB_MODEL_LOADED),
        "modules": {
            "nlu_engine": bool(NLU_ENGINE_AVAILABLE),
            "clarification": bool(CLARIFICATION_AVAILABLE),
            "typo_corrector": bool(TYPO_CORRECTOR_AVAILABLE),
            "response_orchestrator": bool(RESPONSE_ORCHESTRATOR_AVAILABLE),
            "rapidfuzz": bool(RAPIDFUZZ_AVAILABLE),
            "chat_logger": bool(CHAT_LOGGER_AVAILABLE),
        },
        "systems": {
            "analytics_enabled": False,
            "feedback_enabled": False,
            "sensitive_intents_enabled": True,
            "whatsapp_handoff_enabled": True,
            "chat_logging_enabled": False
        },
        "features": {
            "multilingual_support": True,
            "response_logging": False,
            "sensitive_detection": True,
            "session_tracking": True,
            "whatsapp_integration": True
        }
    })


# =========================
# INTENTS ENDPOINT
# =========================
@app.route("/intents", methods=["GET"])
def intents():
    """Daftar intent yang tersedia (label mapping + academy_enhanced)."""
    intents_from_academy = list((ACADEMY_DATA or {}).get("intents", {}).keys())
    intents_from_labels = []
    try:
        if os.path.exists(LABEL_MAPPING_PATH):
            with open(LABEL_MAPPING_PATH, "r", encoding="utf-8") as f:
                label_map = json.load(f)
            intents_from_labels = list(label_map.keys()) if isinstance(label_map, dict) else []
    except Exception as e:
        print(f"[INTENTS] label_mapping load error: {e}")

    # NB model classes jika tersedia
    nb_classes = []
    try:
        if NB_MODEL_LOADED and nb_model is not None and hasattr(nb_model, "classes_"):
            nb_classes = [str(c) for c in nb_model.classes_]
    except Exception:
        pass

    merged = sorted(set(intents_from_academy) | set(intents_from_labels) | set(nb_classes) | {
        "exact_qa", "exact_qa_rapidfuzz", "sensitive_intent", "multi_intent",
        "ai_fallback", "safe_fallback", "greeting"
    })

    return jsonify({
        "success": True,
        "count": len(merged),
        "intents": merged,
        "sources": {
            "academy_enhanced": intents_from_academy,
            "label_mapping": intents_from_labels,
            "naive_bayes_classes": nb_classes,
        }
    })


# =========================
# ANALYZE ENDPOINT
# =========================
@app.route("/analyze", methods=["POST"])
def analyze():
    """Analisis intent/confidence tanpa menghasilkan jawaban chat penuh."""
    try:
        data = request.get_json(silent=True) or {}
        user_message = (data.get("message") or "").strip()
        if not user_message:
            return jsonify({
                "success": False,
                "error": "Pesan tidak boleh kosong.",
                "intent": "empty",
                "confidence": 0.0,
                "entities": [],
            }), 400

        if len(user_message) > 500:
            return jsonify({
                "success": False,
                "error": "Pesan terlalu panjang. Maksimal 500 karakter.",
                "intent": "invalid_length",
                "confidence": 0.0,
                "entities": [],
            }), 400

        processed = normalize_text(user_message)
        entities = []
        intent = "unknown"
        confidence = 0.0
        source = "analyze"
        handoff = False

        # Sensitive
        if is_sensitive_query(processed):
            intent = "sensitive_intent"
            confidence = 0.95
            source = "keyword_detection"
            handoff = True
            entities.append({"type": "sensitive", "value": True})

        # Exact QA
        elif processed in EXACT_QA:
            intent = "exact_qa"
            confidence = 0.99
            source = "static_dictionary"

        # RapidFuzz
        elif RAPIDFUZZ_AVAILABLE and EXACT_QA:
            best_match = process.extractOne(processed, list(EXACT_QA.keys()), scorer=fuzz.WRatio)
            if best_match:
                matched_key = str(best_match[0])
                similarity_score = float(best_match[1])
                q_tokens = {w for w in processed.split() if len(w) >= 3}
                k_tokens = {w for w in matched_key.split() if len(w) >= 3}
                token_overlap = (len(q_tokens & k_tokens) / float(len(q_tokens))) if q_tokens else 0.0
                min_similarity = 93.0 if len(q_tokens) <= 3 else 90.0
                min_overlap = 0.60 if len(q_tokens) <= 3 else 0.45

                if similarity_score >= min_similarity and token_overlap >= min_overlap:
                    intent = "exact_qa_rapidfuzz"
                    confidence = round(similarity_score / 100.0, 2)
                    source = "rapidfuzz_matching"
                    entities.append({"type": "matched_question", "value": matched_key})

        # Naive Bayes
        if intent == "unknown" and NB_MODEL_LOADED and nb_model and tfidf_vectorizer:
            try:
                vec = tfidf_vectorizer.transform([processed])
                predicted = nb_model.predict(vec)[0]
                probs = nb_model.predict_proba(vec)[0]
                confidence = float(np.max(probs))
                intent = str(predicted)
                source = "multinomial_naive_bayes"
            except Exception as nb_err:
                print(f"[ANALYZE] NB error: {nb_err}")

        if is_multi_intent_query(processed):
            entities.append({"type": "multi_intent", "value": True})

        return jsonify({
            "success": True,
            "message": user_message,
            "normalized": processed,
            "intent": intent,
            "confidence": round(float(confidence), 2),
            "entities": entities,
            "handoff": handoff,
            "source": source,
        })
    except Exception as e:
        print(f"[ANALYZE] Error: {e}")
        return jsonify({
            "success": False,
            "error": str(e),
            "intent": "error",
            "confidence": 0.0,
            "entities": [],
        }), 500


# =========================
# START SERVER
# =========================
if __name__ == "__main__":
    print("=" * 70)
    print("ACADEMY NEXT TOP MODEL - SECURED REGISTRATION BLOCKS")
    print("=" * 70)
    print(f"Mode: {CHATBOT_MODE}")
    print(f"WhatsApp Admin: {WHATSAPP_ADMIN}")
    print(f"OpenAI Available: {bool(openai_client)}")
    print(f"FAQ Entries: {len(FAQ_DATA)}")
    print("\n" + "=" * 40 + " MODULES " + "=" * 40)
    print(f"NLU Engine: {'LOADED' if NLU_ENGINE_AVAILABLE else 'DISABLED'}")
    print(f"Clarification: {'LOADED' if CLARIFICATION_AVAILABLE else 'DISABLED'}")
    print(f"Typo Corrector: {'LOADED' if TYPO_CORRECTOR_AVAILABLE else 'DISABLED'}")
    print(f"Response Orchestrator: {'LOADED' if RESPONSE_ORCHESTRATOR_AVAILABLE else 'DISABLED'}")
    print(f"RapidFuzz: {'LOADED' if RAPIDFUZZ_AVAILABLE else 'DISABLED'}")
    print(f"Naive Bayes + TF-IDF: {'LOADED' if NB_MODEL_LOADED else 'DISABLED'}")
    print("\n" + "=" * 40 + " SYSTEMS STATUS " + "=" * 40)
    print("Analytics System: DISABLED")
    print("Feedback System: DISABLED")
    print("Sensitive Intents: ENABLED")
    print("WhatsApp Handoff: ENABLED")
    print("Chat Logging: DISABLED")
    print("\n" + "=" * 40 + " FEATURES " + "=" * 40)
    print("Multilingual Support: ID/EN")
    print("Session Tracking: ENABLED")
    print("Sensitive Detection: ENABLED")
    print("WhatsApp Integration: ENABLED")
    print("Registration Blocks: SECURED")
    print("\n" + "=" * 40 + " API ENDPOINTS " + "=" * 40)
    print("  POST /chat      - Main chat endpoint")
    print("  POST /analyze   - Intent/entity analysis")
    print("  GET  /intents   - List available intents")
    print("  POST /train              - Train (default: Naive Bayes) / TensorFlow jika ada training_data")
    print("  POST /train/naive-bayes  - Latih ulang TF-IDF + Multinomial Naive Bayes")
    print("  GET  /health             - Health check & system status")
    print("\nServer starting on http://127.0.0.1:5000")
    print("=" * 70)
    
    app.run(host="0.0.0.0", port=5000, debug=False)
