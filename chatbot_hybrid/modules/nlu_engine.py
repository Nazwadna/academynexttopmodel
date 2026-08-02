"""
NLU ENGINE — Academy Next Top Model Chatbot
Menyediakan kemampuan NLU lanjutan tanpa mengganti arsitektur inti:
  - Conversation Memory (multi-turn)
  - Entity Extractor (rule-based)
  - Intent Context Refinement
  - Semantic Synonym Expansion (offline)
  - Smart Clarification
  - Dynamic Response Generator
  - Confidence Calibration per intent
"""
import re
import random
from collections import deque
from typing import Optional

# ============================================================
# 1. EXPANDED SYNONYM MAP (domain-grouped)
# ============================================================
SYNONYM_MAP = {
    # --- PENDAFTARAN & BERGABUNG (Sinonim Alami) ---
    "gabung": "daftar", "bergabung": "daftar", "join": "daftar",
    "ikut": "daftar", "ikutan": "daftar", "masuk": "daftar",
    "mendaftar": "daftar", "registrasi": "daftar", "register": "daftar",
    "pendaftaran": "daftar", "daftarkan": "daftar", "apply": "daftar",
    "ikutserta": "daftar", "daftar sekarang": "daftar", "mau gabung": "daftar",
    "mau ikut": "daftar", "mau masuk": "daftar", "pengen gabung": "daftar",
    "pengen ikut": "daftar", "ingin bergabung": "daftar", "ingin join": "daftar",
    "ingin mendaftar": "daftar", "cara masuk": "cara daftar", "cara bergabung": "cara daftar",
    "cara join": "cara daftar", "cara mendaftar": "cara daftar",
    "proses pendaftaran": "cara daftar", "proses daftar": "cara daftar",

    # --- KEINGINAN & PERMINTAAN (Sinonim Alami & Bantuan) ---
    "pengen": "ingin", "pengan": "ingin", "pingin": "ingin", "pengin": "ingin",
    "berniat": "ingin", "mw": "ingin",
    "mohon": "tolong", "mhon": "tolong", "tlg": "tolong", "tlgong": "tolong",
    "plis": "tolong", "pls": "tolong", "please": "tolong",

    # --- TERIMA KASIH (Polite Closings) ---
    "makasih": "terima kasih", "makasi": "terima kasih", "mksih": "terima kasih",
    "thanks": "terima kasih", "thx": "terima kasih", "thankyou": "terima kasih",
    "thank you": "terima kasih", "tq": "terima kasih", "nuhun": "terima kasih",
    "suwun": "terima kasih", "maturnuwun": "terima kasih", "trims": "terima kasih",
    "tengkyu": "terima kasih",

    # --- LOKASI ---
    "alamat": "lokasi", "tempat": "lokasi", "gedung": "lokasi",
    "kantor": "lokasi", "studio": "lokasi", "tempat latihan": "lokasi",
    "google maps": "lokasi", "maps": "lokasi", "peta": "lokasi",
    "dimana": "lokasi", "di mana": "lokasi", "ada di mana": "lokasi",
    "lokasinya": "lokasi", "tempatnya": "lokasi", "alamatnya": "lokasi",
    "lok": "lokasi", "lksi": "lokasi", "lokasiny": "lokasi",

    # --- JADWAL ---
    "jam": "jadwal", "waktu": "jadwal", "kapan": "jadwal",
    "hari apa": "jadwal", "hari": "jadwal", "sesi": "jadwal",
    "mulai jam": "jadwal", "selesai jam": "jadwal", "kelas jam": "jadwal",
    "jdwl": "jadwal", "jdwal": "jadwal", "jadwalnnya": "jadwal", "kpn": "kapan",

    # --- BIAYA ---
    "harga": "biaya", "bayar": "biaya", "tarif": "biaya",
    "cost": "biaya", "fee": "biaya", "investasi": "biaya",
    "iuran": "biaya", "ongkos": "biaya", "rate": "biaya",
    "byaya": "biaya", "bya": "biaya", "biayanya": "biaya",
    "berapa harganya": "biaya berapa", "berapa biayanya": "biaya berapa",

    # --- MODEL / TALENT ---
    "talent": "model", "fashion model": "model", "catwalk model": "model",
    "runway model": "model", "peserta": "model", "anggota": "model",
    "rekomen": "rekomendasi", "rekomendasiin": "rekomendasi", "saranin": "rekomendasi",

    # --- FOTO / PORTOFOLIO ---
    "gambar": "foto", "photo": "foto", "image": "foto",
    "upload foto": "foto", "unggah foto": "foto", "foto profil": "foto",
    "portofolio": "foto", "portfolio": "foto",

    # --- PROGRAM / KELAS ---
    "materi": "program", "kelas": "program", "kurikulum": "program",
    "pelatihan": "program", "training": "program", "kursus": "program",
    "pelajaran": "program", "kelas apa": "program apa",
    "pgm": "program", "prgrm": "program", "progrm": "program",

    # --- SAPAAN ---
    "hai": "halo", "hi": "halo", "hey": "halo", "hei": "halo",
    "permisi": "halo", "selamat pagi": "halo", "selamat siang": "halo",
    "selamat sore": "halo", "selamat malam": "halo", "good morning": "halo",
    "good afternoon": "halo", "p": "halo",

    # --- KONFIRMASI & INTERAKSI ---
    "oke": "baik", "ok": "baik", "siap": "baik", "iya": "ya",
    "yup": "ya", "yep": "ya", "betul": "ya", "benar": "ya",
    "hub": "hubungi", "kontak": "hubungi",
    "wa": "whatsapp", "ig": "instagram",

    # --- TEKNIS ---
    "akun": "login", "masuk akun": "login", "log in": "login",
    "sign in": "login", "lupa sandi": "lupa password", "lupa kata sandi": "lupa password",
    "kode": "otp", "kode otp": "otp", "kode verifikasi": "otp",

    # --- SINGKATAN & SLANG UMUM INDONESIA ---
    "gmn": "bagaimana", "gimana": "bagaimana", "gmna": "bagaimana", "mna": "bagaimana", "gimene": "bagaimana",
    "brp": "berapa", "brapa": "berapa", "berapaan": "berapa",
    "min": "admin", "admn": "admin", "mimin": "admin",
    "dftr": "daftar", "daftr": "daftar", "daftaar": "daftar", "dftar": "daftar", "daftarr": "daftar",
    "blm": "belum", "belom": "belum",
    "sdh": "sudah", "udh": "sudah", "udah": "sudah", "udha": "sudah",
    "gak": "tidak", "ga": "tidak", "nggak": "tidak", "ngga": "tidak", "gk": "tidak", "gatau": "tidak", "kagak": "tidak", "kga": "tidak",
    "bs": "bisa", "bsa": "bisa", "bisaa": "bisa",
    "sy": "saya", "ak": "aku", "gua": "saya", "gue": "saya", "gw": "saya",
    "lu": "kamu", "elo": "kamu", "elu": "kamu", "km": "kamu", "kmu": "kamu",
    "yg": "yang", "utk": "untuk", "bwt": "untuk", "buat": "untuk", "krn": "karena", "karnaa": "karena",
    "bgt": "sangat", "banget": "sangat", "bnget": "sangat",
    "skrg": "sekarang", "sekarang": "sekarang",
    "fastres": "cepat", "fastresp": "cepat",
    "nih": "", "sih": "", "deh": "", "dong": "", "lah": "", "kan": "",
}

# ============================================================
# 2. ENTITY PATTERNS (rule-based)
# ============================================================
ENTITY_PATTERNS = {
    "umur": re.compile(
        r'\b(?:umur|usia|berumur|berusia|tahun)\s*[:=]?\s*(\d{1,2})\b'
        r'|\b(\d{1,2})\s*(?:tahun|thn|th)\b', re.IGNORECASE),
    "hari": re.compile(
        r'\b(senin|selasa|rabu|kamis|jumat|sabtu|minggu)\b', re.IGNORECASE),
    "jam": re.compile(
        r'\b(\d{1,2})[:.h](\d{0,2})\s*(?:wib|wita|wit)?\b', re.IGNORECASE),
    "lokasi": re.compile(
        r'\b(tsm|cibubur|trans studio|mall|jakarta|bandung|bogor|depok|tangerang)\b',
        re.IGNORECASE),
    "kontak": re.compile(
        r'\b(whatsapp|wa|instagram|ig|email|website|web)\b', re.IGNORECASE),
    "program": re.compile(
        r'\b(catwalk|runway|beauty\s*class|pose\s*class|photoshoot|'
        r'self\s*branding|public\s*speaking|basic|advanced)\b', re.IGNORECASE),
    "nominal": re.compile(r'\b(\d{3}[\d.,]*)\b'),
    "kelas": re.compile(r'\b(kelas\s*\d+|level\s*\d+|batch\s*\d+)\b', re.IGNORECASE),
}

# ============================================================
# 3. CONFIGURATION-DRIVEN CONVERSATION MEMORY MANAGER
# ============================================================
import time

MEMORY_POLICY = {
    "max_turns": 10,
    "expire_minutes": 30,
    "max_entities": 10
}

REFERENCE_MAP = {
    "biayanya": "biaya pendaftaran",
    "jadwalnya": "jadwal kelas",
    "lokasinya": "lokasi academy",
    "syaratnya": "syarat pendaftaran",
    "prosesnya": "proses pendaftaran",
    "harganya": "harga pendaftaran",
    "itu": "{active_topic}",
    "di sana": "{active_topic}",
    "disana": "{active_topic}",
    "yang tadi": "{active_topic}",
    "yang tersebut": "{active_topic}",
    "tadi": "{active_topic}"
}

# C1 (audit lanjutan Grup A #1): pronoun anaforik yang HANYA masuk akal
# disubstitusi kalau memang ada current_topic nyata — jangan dipaksa default
# "pendaftaran" saat current_topic masih None (sesi baru / belum ada topik).
_TOPIC_DEPENDENT_PRONOUNS = {
    "itu", "di sana", "disana", "yang tadi", "yang tersebut", "tadi",
}

INTENT_TO_TOPIC = {
    "registration_info": "pendaftaran",
    "registration_steps": "pendaftaran",
    "registration_requirements": "persyaratan pendaftaran",
    "photo_upload": "upload foto",
    "location_info": "lokasi",
    "schedule_info": "jadwal",
    "program_info": "program kelas",
    "programs_info": "program kelas",
    "become_model_info": "menjadi model",
    "sensitive_intent": "biaya",
    "pricing_info": "biaya",
    "model_info": "informasi model",
    "technical_support": "kendala teknis",
    "greeting": "sapaan",
    "gratitude": "terima kasih",
    "homepage_info": "informasi academy",
    "contact_admin": "kontak admin",
}

class ConversationMemory:
    """Configuration-Driven Session Context Memory Manager."""
    def __init__(self, policy: dict = None):
        self.policy = policy or MEMORY_POLICY
        self.max_turns: int = self.policy.get("max_turns", 10)
        self.expire_seconds: float = self.policy.get("expire_minutes", 30) * 60

        self.current_intent: Optional[str] = None
        self.previous_intent: Optional[str] = None
        self.confidence: float = 0.0
        self.current_topic: Optional[str] = None
        self.previous_topic: Optional[str] = None
        self.active_topics: list = []
        self.secondary_intents: list = []
        self.entities: dict = {}
        self.last_response: str = ""
        self.last_greeting: Optional[str] = None
        self.last_updated: float = time.time()
        self.turn_count: int = 0
        self.history: deque = deque(maxlen=self.max_turns)

    @property
    def last_intent(self) -> Optional[str]:
        return self.current_intent

    @last_intent.setter
    def last_intent(self, value: Optional[str]):
        self.current_intent = value

    @property
    def last_topic(self) -> Optional[str]:
        return self.current_topic

    @last_topic.setter
    def last_topic(self, value: Optional[str]):
        self.current_topic = value

    @property
    def last_entities(self) -> dict:
        return self.entities

    def is_expired(self) -> bool:
        """Cek apakah sesi percakapan telah kedaluwarsa sesuai MEMORY_POLICY."""
        return (time.time() - self.last_updated) > self.expire_seconds

    def add_turn(self, user_msg: str, bot_reply: str, intent: str, entities: dict,
                 confidence: float = 1.0, secondary_intents: list = None):
        """Lifecycle Memory Update: dipanggil otomatis setiap selesai memproses intent."""
        now = time.time()
        if self.is_expired():
            self.reset_session()

        self.last_updated = now
        new_topic = INTENT_TO_TOPIC.get(intent, intent)

        self.previous_intent = self.current_intent
        self.current_intent = intent
        self.confidence = float(confidence)
        self.secondary_intents = secondary_intents or []

        if new_topic != self.current_topic:
            self.previous_topic = self.current_topic
            self.current_topic = new_topic
            if new_topic not in self.active_topics:
                self.active_topics.append(new_topic)
                if len(self.active_topics) > 3:
                    self.active_topics.pop(0)

        self.entities.update(entities)
        self.last_response = bot_reply
        self.turn_count += 1

        self.history.append({
            "turn": self.turn_count,
            "user": user_msg,
            "bot": bot_reply,
            "intent": intent,
            "confidence": self.confidence,
            "topic": self.current_topic,
            "secondary_intents": self.secondary_intents,
            "entities": entities,
            "timestamp": now
        })

        max_ent = self.policy.get("max_entities", 10)
        if len(self.entities) > max_ent:
            self.prune_old_context()

    def prune_old_context(self):
        """Soft reset / pemangkasan entitas lama sesuai konfigurasi MEMORY_POLICY."""
        max_ent = self.policy.get("max_entities", 10)
        if len(self.entities) > max_ent:
            keys_to_remove = list(self.entities.keys())[:-5]
            for k in keys_to_remove:
                del self.entities[k]

    def reset_session(self):
        """Reset seluruh memori sesi jika kedaluwarsa atau di-reset ulang."""
        self.current_intent = None
        self.previous_intent = None
        self.confidence = 0.0
        self.current_topic = None
        self.previous_topic = None
        self.active_topics.clear()
        self.secondary_intents.clear()
        self.entities.clear()
        self.last_response = ""
        self.last_greeting = None
        self.turn_count = 0
        self.history.clear()
        self.clarification_pending: bool = False
        self.clarification_candidates: list = []

    def get_context_summary(self) -> str:
        if not self.history:
            return ""
        return f"Topik terakhir: {self.current_topic}, Intent: {self.current_intent}"

    def resolve_reference(self, text: str) -> str:
        """Resolusi anaphora & kata ganti menggunakan konfigurasi REFERENCE_MAP."""
        text_lower = text.lower().strip()
        topic_context = self.current_topic or "pendaftaran"

        for pronoun, replacement in REFERENCE_MAP.items():
            if pronoun not in text_lower:
                continue
            if pronoun == "itu":
                # Fix Grup A (audit kegagalan "Informasi Academy"): "itu" sering
                # dipakai non-anaforik dalam idiom "apa itu X" (tanya definisi,
                # bukan merujuk topik sebelumnya) — jangan disubstitusi dalam
                # pola ini.
                if re.search(r"\bapa itu\b", text_lower):
                    continue
            if pronoun in _TOPIC_DEPENDENT_PRONOUNS and not self.current_topic:
                # C1 (audit lanjutan): pronoun anaforik lain berbagi bug yang
                # sama dengan "itu" — jangan disubstitusi kalau memang belum
                # ada topik nyata (current_topic None), biarkan teks asli
                # daripada memaksa default "pendaftaran" yang sering salah.
                continue
            actual_rep = replacement.replace("{active_topic}", topic_context)
            if actual_rep:
                text_lower = text_lower.replace(pronoun, actual_rep)

        return text_lower

    def is_followup(self, current_intent: str) -> bool:
        """Deteksi apakah intent baru merupakan lanjutan kontekstual dari intent sebelumnya."""
        if not self.current_intent:
            return False

        RELATED_GROUPS = [
            {"registration_info", "registration_steps", "registration_requirements", "sensitive_intent"},
            {"location_info", "schedule_info", "homepage_info"},
            {"program_info", "become_model_info", "model_info"},
            {"technical_support", "contact_admin"}
        ]

        for grp in RELATED_GROUPS:
            if self.current_intent in grp and current_intent in grp:
                return True

        return False

    def to_dict(self) -> dict:
        """Export objek memori lengkap."""
        return {
            "current_intent": self.current_intent,
            "previous_intent": self.previous_intent,
            "confidence": self.confidence,
            "current_topic": self.current_topic,
            "previous_topic": self.previous_topic,
            "active_topics": self.active_topics,
            "secondary_intents": self.secondary_intents,
            "entities": self.entities,
            "last_response": self.last_response[:50] + "..." if self.last_response else "",
            "last_greeting": self.last_greeting,
            "last_updated": self.last_updated,
            "turn_count": self.turn_count,
            "history_length": len(self.history)
        }

# Global memory per session_id
_session_memories: dict = {}

def get_memory(session_id: str) -> ConversationMemory:
    if session_id not in _session_memories:
        _session_memories[session_id] = ConversationMemory()
    return _session_memories[session_id]

# ============================================================
# 4. TEXT NORMALIZATION (expanded)
# ============================================================
def normalize_nlu(text: str) -> str:
    """Full NLU normalization: lowercase, slang, synonym, punctuation, whitespace."""
    text = text.lower().strip()
    # Hapus tanda baca berulang
    text = re.sub(r'[!?]{2,}', ' ', text)
    text = re.sub(r'[.,:;/\-\(\)]+', ' ', text)
    # Normalisasi whitespace
    text = re.sub(r'\s+', ' ', text).strip()
    # Token-level synonym replacement
    tokens = text.split()
    normalized = []
    for tok in tokens:
        rep = SYNONYM_MAP.get(tok)
        if rep is not None:
            if rep:  # skip empty string (filler words)
                normalized.append(rep)
        else:
            normalized.append(tok)
    return " ".join(normalized)

# ============================================================
# 5. ENTITY EXTRACTOR
# ============================================================
def extract_entities(text: str) -> dict:
    entities = {}
    for name, pattern in ENTITY_PATTERNS.items():
        matches = pattern.findall(text)
        if matches:
            # Flatten nested groups
            flat = [m for grp in matches for m in (grp if isinstance(grp, tuple) else [grp]) if m]
            entities[name] = flat[0] if len(flat) == 1 else flat
    return entities

# ============================================================
# 6. CONFIGURATION-DRIVEN INTENT REFINEMENT RULE ENGINE
# ============================================================
CONFIDENCE_THRESHOLDS = {
    "greeting":          0.30,
    "gratitude":         0.30,
    "location_info":     0.45,
    "schedule_info":     0.45,
    "registration_info": 0.50,
    "program_info":      0.50,
    "become_model_info": 0.50,
    "model_info":        0.55,
    "sensitive_intent":  0.55,
    "technical_support": 0.60,
    "homepage_info":     0.40,
}
DEFAULT_THRESHOLD = 0.45

REFINEMENT_RULES = [
    {
        "name": "registration_to_pricing",
        "from_intents": ["registration_info", "homepage_info", "about_academy", "become_model_info", "model_info"],
        "to_intent": "sensitive_intent",
        "keywords": ["biaya", "harga", "bayar", "pembayaran", "tarif", "fee", "diskon", "promo", "cicilan", "dp"],
        "exclude": [],
        "min_confidence": 0.40,
        "max_confidence": 0.95,
        "boost_confidence": 0.85
    },
    {
        "name": "homepage_to_location",
        "from_intents": ["homepage_info", "about_academy", "registration_info", "become_model_info"],
        "to_intent": "location_info",
        "keywords": ["alamat", "lokasi", "maps", "google maps", "tsm", "cibubur", "dimana tempatnya", "dimana lokasinya", "patokan"],
        "exclude": [],
        "min_confidence": 0.40,
        "max_confidence": 0.95,
        "boost_confidence": 0.85
    },
    {
        "name": "model_to_become_model",
        "from_intents": ["model_info", "homepage_info", "registration_info"],
        "to_intent": "become_model_info",
        "keywords": ["panduan", "tips", "cara jadi model", "mulai dari mana", "langkah awal", "persiapan jadi model", "manfaat bergabung", "keuntungan join"],
        "exclude": [],
        "min_confidence": 0.40,
        "max_confidence": 0.95,
        "boost_confidence": 0.85
    },
    {
        "name": "become_model_to_model_catalog",
        "from_intents": ["become_model_info", "homepage_info"],
        "to_intent": "model_info",
        "keywords": ["katalog model", "daftar model", "nama model", "talent ready", "model available", "direktori model", "kategori model"],
        "exclude": [],
        "min_confidence": 0.40,
        "max_confidence": 0.95,
        "boost_confidence": 0.85
    },
    {
        "name": "become_model_to_registration_proc",
        "from_intents": ["become_model_info", "homepage_info"],
        "to_intent": "registration_info",
        "keywords": ["syarat daftar", "proses pendaftaran", "formulir pendaftaran", "cara daftar online", "langkah pendaftaran"],
        "exclude": [],
        "min_confidence": 0.40,
        "max_confidence": 0.95,
        "boost_confidence": 0.85
    },
    {
        "name": "any_info_to_technical_support",
        "from_intents": ["registration_info", "contact_admin", "model_info", "homepage_info", "become_model_info"],
        "to_intent": "technical_support",
        "keywords": ["error", "gagal login", "gagal register", "bug", "loading terus", "blank", "otp tidak masuk", "lupa password", "tidak bisa login", "tidak bisa register", "reset password", "kendala sistem"],
        "exclude": [],
        "min_confidence": 0.40,
        "max_confidence": 0.95,
        "boost_confidence": 0.88
    },
    {
        "name": "technical_to_contact_admin",
        "from_intents": ["technical_support", "homepage_info"],
        "to_intent": "sensitive_intent",
        "keywords": ["nomor wa", "whatsapp admin", "telepon admin", "email admin", "minta wa", "kontak admin", "hubungi cs"],
        "exclude": ["error", "gagal", "bug", "loading", "blank"],
        "min_confidence": 0.40,
        "max_confidence": 0.95,
        "boost_confidence": 0.85
    },
    {
        "name": "general_to_schedule_info",
        "from_intents": ["homepage_info", "about_academy", "registration_info"],
        "to_intent": "location_info",
        "keywords": ["jadwal", "jam berapa", "hari apa", "sabtu", "setiap sabtu", "buka jam berapa", "sesi kelas"],
        "exclude": [],
        "min_confidence": 0.40,
        "max_confidence": 0.95,
        "boost_confidence": 0.85
    }
]

def apply_post_classification_refinement(user_message: str, predicted_intent: str, confidence: float) -> tuple[str, float]:
    """
    Configuration-Driven Post-Classification Intent Refinement Engine.
    Engine generik reusable yang membaca aturan dari REFINEMENT_RULES.
    Tidak ada hardcoded keyword dalam fungsi ini.
    """
    if not user_message or confidence >= 0.95:
        return predicted_intent, confidence

    text = user_message.lower().strip()

    for rule in REFINEMENT_RULES:
        min_conf = rule.get("min_confidence", 0.40)
        max_conf = rule.get("max_confidence", 0.95)

        # 1. Evaluasi range confidence
        if not (min_conf <= confidence < max_conf):
            continue

        # 2. Evaluasi intent asal (from_intents)
        from_intents = rule.get("from_intents", [])
        if from_intents and predicted_intent not in from_intents:
            continue

        # 3. Evaluasi kata kunci pengecualian (exclude)
        exclude_kws = rule.get("exclude", [])
        if exclude_kws and any(ex_kw in text for ex_kw in exclude_kws):
            continue

        # 4. Evaluasi kata kunci target (keywords)
        keywords = rule.get("keywords", [])
        if any(kw in text for kw in keywords):
            boosted_conf = max(confidence, rule.get("boost_confidence", 0.85))
            return rule["to_intent"], boosted_conf

    return predicted_intent, confidence

def refine_intent(predicted_intent: str, confidence: float,
                  memory: ConversationMemory, entities: dict,
                  user_message: str = "") -> tuple[str, float]:
    """Gabungkan prediksi NB dengan Disambiguation Refinement & konteks percakapan."""

    # Jalankan Post-Classification Refinement Layer terlebih dahulu
    predicted_intent, confidence = apply_post_classification_refinement(
        user_message, predicted_intent, confidence
    )

    threshold = CONFIDENCE_THRESHOLDS.get(predicted_intent, DEFAULT_THRESHOLD)

    # Jika confidence rendah tapi topik sama seperti sebelumnya → pertahankan
    if confidence < threshold and memory.last_intent:
        if memory.is_followup(predicted_intent) or memory.is_followup(memory.last_intent):
            return memory.last_intent, max(confidence, 0.50)

    # Boosting: jika ada entitas umur → registration context
    if "umur" in entities:
        age = entities["umur"]
        try:
            if int(age) <= 25:
                return "registration_info", max(confidence, 0.70)
        except (ValueError, TypeError):
            pass

    if confidence < threshold:
        return "low_confidence", confidence

    return predicted_intent, confidence

# ============================================================
# 7. CONFIGURATION-DRIVEN CLARIFICATION QUESTION ENGINE
# ============================================================

CLARIFICATION_POLICY = {
    'ambiguous_min_confidence': 0.35,
    'ambiguous_max_confidence': 0.62,
    'max_candidates':           3,
    'skip_intents':             ['greeting', 'gratitude'],
}

CLARIFICATION_INTENT_LABELS = {
    'registration_info':         'proses dan langkah pendaftaran',
    'registration_requirements': 'syarat dan dokumen pendaftaran',
    'sensitive_intent':          'biaya, harga, dan pembayaran',
    'location_info':             'lokasi dan alamat academy',
    'schedule_info':             'jadwal dan jam kelas',
    'program_info':              'program dan materi pelatihan',
    'become_model_info':         'cara dan tips menjadi model',
    'model_info':                'katalog dan daftar model talent',
    'homepage_info':             'informasi umum academy',
    'technical_support':         'kendala teknis dan akun',
}

CLARIFICATION_TOPIC_GROUPS = {
    'pendaftaran':   ['registration_info', 'registration_requirements', 'sensitive_intent'],
    'biaya':         ['sensitive_intent', 'registration_info'],
    'lokasi':        ['location_info', 'schedule_info'],
    'jadwal':        ['schedule_info', 'location_info'],
    'program kelas': ['program_info', 'become_model_info'],
    'menjadi model': ['become_model_info', 'registration_info', 'program_info'],
    'default':       ['registration_info', 'location_info', 'program_info', 'sensitive_intent'],
}

CLARIFICATION_INTROS = [
    'Boleh saya bantu lebih spesifik? 😊 Apakah pertanyaan Anda tentang:',
    'Agar saya bisa menjawab dengan tepat, maksud Anda:',
    'Saya ingin memastikan agar jawabannya tepat. Apakah yang ditanyakan:',
    'Pertanyaan Anda bisa mengarah ke beberapa topik. Yang dimaksud:',
]

CLARIFICATION_CHOICE_ANCHORS = {
    'registration_info':         ['daftar', 'pendaftaran', 'register', 'cara daftar', 'langkah'],
    'registration_requirements': ['syarat', 'dokumen', 'persyaratan', 'foto', 'usia'],
    'sensitive_intent':          ['biaya', 'harga', 'bayar', 'pembayaran', 'tarif', 'fee'],
    'location_info':             ['lokasi', 'alamat', 'tempat', 'tsm', 'cibubur', 'dimana'],
    'schedule_info':             ['jadwal', 'jam', 'sabtu', 'waktu', 'hari'],
    'program_info':              ['program', 'materi', 'kelas', 'kurikulum', 'pelajaran'],
    'become_model_info':         ['model', 'jadi model', 'tips', 'cara jadi', 'karir'],
    'model_info':                ['katalog', 'daftar model', 'talent', 'nama model'],
    'homepage_info':             ['informasi', 'info', 'tentang', 'academy', 'umum'],
    'technical_support':         ['error', 'gagal', 'login', 'otp', 'kendala', 'akun'],
}

CLARIFICATION_SCORING = {
    'base_confidence_weight': 1.0,
    'topic_bonus':            0.15,
    'memory_bonus':           0.10,
    'refinement_bonus':       0.12,
    'secondary_bonus':        0.08,
    'keyword_bonus':          0.10,
    'minimum_score':          0.10,
    'candidate_gap':          0.05,
}


def _score_candidate(intent, user_message, confidence, refined_intent, memory, secondary_intents):
    s = CLARIFICATION_SCORING
    score = 0.0
    if intent == refined_intent:
        score += confidence * s['base_confidence_weight']
    for topic in memory.active_topics:
        if intent in CLARIFICATION_TOPIC_GROUPS.get(topic, []):
            score += s['topic_bonus']
            break
    if intent == memory.previous_intent:
        score += s['memory_bonus']
    if intent == refined_intent and refined_intent != 'low_confidence':
        score += s['refinement_bonus']
    if intent in (secondary_intents or []):
        score += s['secondary_bonus']
    text = user_message.lower()
    hits = sum(1 for kw in CLARIFICATION_CHOICE_ANCHORS.get(intent, []) if kw in text)
    score += hits * s['keyword_bonus']
    return round(score, 4)


def rank_clarification_candidates(refined_intent, confidence, user_message, memory, secondary_intents):
    pool = set()
    if refined_intent and refined_intent != 'low_confidence':
        pool.add(refined_intent)
    for si in (secondary_intents or []):
        pool.add(si)
    for topic in memory.active_topics:
        for intent in CLARIFICATION_TOPIC_GROUPS.get(topic, []):
            pool.add(intent)
    topic_key = memory.current_topic or 'default'
    for intent in CLARIFICATION_TOPIC_GROUPS.get(topic_key, CLARIFICATION_TOPIC_GROUPS['default']):
        pool.add(intent)
    scored = []
    min_score = CLARIFICATION_SCORING['minimum_score']
    for intent in pool:
        sc = _score_candidate(intent, user_message, confidence, refined_intent, memory, secondary_intents)
        if sc >= min_score:
            label = CLARIFICATION_INTENT_LABELS.get(intent, intent.replace('_', ' '))
            scored.append({'intent': intent, 'score': sc, 'label': label})
    scored.sort(key=lambda x: x['score'], reverse=True)
    gap = CLARIFICATION_SCORING['candidate_gap']
    filtered, prev_score = [], None
    for item in scored:
        if not filtered or prev_score is None or (prev_score - item['score']) >= gap:
            filtered.append(item)
            prev_score = item['score']
    return filtered[:CLARIFICATION_POLICY['max_candidates']]


def should_clarify(refined_intent, confidence, memory, secondary_intents):
    policy = CLARIFICATION_POLICY
    if refined_intent in policy['skip_intents']:
        return False
    if secondary_intents and confidence >= policy['ambiguous_min_confidence']:
        return False
    return policy['ambiguous_min_confidence'] <= confidence < policy['ambiguous_max_confidence']


def build_clarification_question(candidates, memory):
    intro = random.choice(CLARIFICATION_INTROS)
    lines = [f'  {i}. {c["label"]}' for i, c in enumerate(candidates, 1)]
    return intro + '\n' + '\n'.join(lines)


def _default_clarification_candidates():
    """Fallback kandidat bila ranking kosong agar Clarification Engine tetap usable."""
    intents = CLARIFICATION_TOPIC_GROUPS.get(
        "default",
        ["registration_info", "location_info", "program_info"],
    )
    max_n = CLARIFICATION_POLICY.get("max_candidates", 3)
    out = []
    for intent in intents[:max_n]:
        out.append({
            "intent": intent,
            "score": 0.2,
            "label": CLARIFICATION_INTENT_LABELS.get(intent, intent.replace("_", " ")),
        })
    return out


def _ensure_clarification_candidates(ranked):
    if ranked:
        return ranked
    return _default_clarification_candidates()


# Alias kompatibilitas mundur (app.py / import lama)
build_clarification = build_clarification_question


def resolve_clarification_choice(user_message, candidates):
    text = user_message.lower().strip()
    intents = [c['intent'] for c in candidates]
    num_match = re.search(r'\b([1-9])\b', text)
    if num_match:
        idx = int(num_match.group(1)) - 1
        if 0 <= idx < len(intents):
            return intents[idx]
    for intent in intents:
        if any(anchor in text for anchor in CLARIFICATION_CHOICE_ANCHORS.get(intent, [])):
            return intent
    for intent in intents:
        label = CLARIFICATION_INTENT_LABELS.get(intent, '').lower()
        if label and label in text:
            return intent
    return None

# ============================================================
# 8. INTENT REASONING (rule-based)
# ============================================================
AGE_REQUIREMENT = 25   # tahun maksimal sesuai FAQ

def apply_reasoning(intent: str, entities: dict, memory: ConversationMemory) -> Optional[str]:
    """Hasilkan reasoning sederhana berbasis fakta pengguna + aturan FAQ."""
    if "umur" in entities:
        try:
            age = int(str(entities["umur"]).strip())
        except (ValueError, TypeError):
            age = None
        if age is not None:
            if intent in ("registration_info", "registration_steps", "become_model_info"):
                if age <= AGE_REQUIREMENT:
                    return (
                        f"Kabar baik! 🎉 Dengan usia {age} tahun, "
                        f"Anda memenuhi syarat pendaftaran Academy Next Top Model "
                        f"(batas usia 4–{AGE_REQUIREMENT} tahun). "
                        f"Anda bisa langsung mendaftar melalui halaman Register di website kami."
                    )
                else:
                    return (
                        f"Mohon maaf, Academy Next Top Model saat ini menerima peserta "
                        f"dengan usia maksimal {AGE_REQUIREMENT} tahun, "
                        f"sementara usia Anda {age} tahun. "
                        f"Untuk informasi lebih lanjut, silakan hubungi admin melalui WhatsApp."
                    )

    if "hari" in entities:
        hari = str(entities["hari"]).capitalize()
        if intent in ("registration_info", "program_info", "schedule_info"):
            if hari.lower() == "sabtu":
                return (
                    f"Kelas reguler memang diadakan setiap hari Sabtu, "
                    f"jadi jadwal Anda di hari {hari} sangat cocok! 😊"
                )
            else:
                return (
                    f"Saat ini kelas reguler diadakan setiap hari Sabtu (11.00–15.00 WIB). "
                    f"Jika Anda hanya tersedia di hari {hari}, "
                    f"silakan hubungi admin untuk informasi jadwal alternatif."
                )
    return None

# ============================================================
# 9. TIME-AWARE GREETING & MODULAR RESPONSE GENERATOR
# ============================================================
from datetime import datetime

TIME_GREETINGS = {
    "morning": [
        "Selamat pagi! 😊",
        "Pagi Kak! 😊",
        "Selamat pagi. Ada yang bisa saya bantu?",
        "Pagi! Senang bisa menyapa Anda."
    ],
    "afternoon": [
        "Selamat siang! 😊",
        "Siang Kak! 😊",
        "Selamat siang. Ada yang bisa saya bantu?",
        "Siang! Ada yang ingin Anda tanyakan?"
    ],
    "evening": [
        "Selamat sore! 😊",
        "Sore Kak! 😊",
        "Selamat sore. Ada yang bisa saya bantu?",
        "Sore! Ada yang perlu saya bantu?"
    ],
    "night": [
        "Selamat malam! 😊",
        "Malam Kak! 😊",
        "Selamat malam. Ada yang bisa saya bantu?",
        "Malam! Terima kasih telah menghubungi kami."
    ],
    "default": [
        "Halo!", "Hai!", "Halo Kak.", "Hai Kak."
    ]
}

GREETING_VARIATIONS = [
    "Halo!", "Hai!", "Halo Kak.", "Hai Kak.", "Baik.",
    "Tentu.", "Dengan senang hati.", "Senang membantu.", "Salam.", "Selamat datang."
]

TRANSITION_VARIATIONS = [
    "Berikut informasinya.", "Berikut penjelasannya.", "Saya jelaskan ya.",
    "Berikut detailnya.", "Berikut informasi yang dapat saya sampaikan.",
    "Ini informasinya.", "Saya bantu jelaskan.", "Berikut yang perlu diketahui.",
    "Berikut jawabannya.", "Silakan disimak ya."
]

CLOSING_VARIATIONS = [
    "Semoga membantu.", "Jangan ragu bertanya kembali.",
    "Jika masih ada pertanyaan silakan hubungi kami.",
    "Semoga informasi ini bermanfaat.", "Saya siap membantu jika ada pertanyaan lainnya.",
    "Terima kasih.", "Semoga harimu menyenangkan.", "Silakan bertanya kapan saja.",
    "Senang bisa membantu.", "Semoga sukses."
]

RESPONSE_PROFILES = {
    "FULL": ["greeting", "transition", "core", "closing"],
    "MINIMAL": ["transition", "core"],
    "DIRECT": ["core"]
}

INTENT_PROFILE_MAP = {
    "greeting": "DIRECT",
    "gratitude": "DIRECT",
    "technical_support": "MINIMAL",
    "sensitive_intent": "MINIMAL",
    "registration_info": "FULL",
    "registration_steps": "FULL",
    "registration_requirements": "FULL",
    "photo_upload": "FULL",
    "location_info": "FULL",
    "schedule_info": "FULL",
    "program_info": "FULL",
    "become_model_info": "FULL",
    "model_info": "FULL",
    "homepage_info": "FULL",
    "default": "FULL"
}

import os, json
# Single Source of Truth (SSOT) Loader for NLU Engine
_BASE_NLU_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
_ACADEMY_JSON_PATH = os.path.join(_BASE_NLU_DIR, 'data', 'academy_enhanced.json')
try:
    with open(_ACADEMY_JSON_PATH, 'r', encoding='utf-8') as f:
        NLU_SSOT_DATA = json.load(f)
except Exception:
    NLU_SSOT_DATA = {"responses": {}}

def get_ssot_intent_answers(intent: str) -> list:
    """Ambil jawaban kanonis dari SSOT (academy_enhanced.json), dengan fallback ke INTENT_CORE_RESPONSES."""
    alias_map = {
        "training_programs": "programs_info",
        "technical": "technical_support",
        "join_registration_info": "registration_steps",
        "academy_info": "about_academy"
    }
    canonical_key = alias_map.get(intent, intent)
    responses = (NLU_SSOT_DATA or {}).get("responses", {})
    raw = responses.get(canonical_key)
    if isinstance(raw, list) and raw:
        return [str(x).strip() for x in raw]
    if isinstance(raw, dict):
        id_list = raw.get("id") or raw.get("en") or []
        if isinstance(id_list, list) and id_list:
            return [str(x).strip() for x in id_list]
    return INTENT_CORE_RESPONSES.get(intent, [])

INTENT_CORE_RESPONSES = {
    "registration_info": [
        "untuk mendaftar di Academy Next Top Model, Anda cukup membuka halaman Register di website, isi data diri (nama, email, nomor WhatsApp), lalu verifikasi akun. Setelah itu lengkapi profil dan portofolio foto Anda.",
        "langkah pendaftarannya cukup mudah: buka website → klik Register → isi formulir data diri → verifikasi akun → login → lengkapi profil dan foto portofolio.",
        "caranya: kunjungi website Academy Next Top Model, pilih menu Register, isi informasi dasar Anda, lakukan verifikasi OTP, kemudian lengkapi profil di dashboard."
    ],
    "registration_steps": [
        "cara pendaftaran di Academy Next Top Model adalah: 1. Buka halaman Register pada website -> 2. Isi data diri lengkap -> 3. Verifikasi akun jika diminta -> 4. Login -> 5. Lengkapi profil dan portofolio foto Anda.",
        "langkah pendaftarannya cukup mudah: buka website → klik Register → isi formulir data diri → verifikasi akun → login → lengkapi profil dan foto portofolio."
    ],
    "photo_upload": [
        "panduan upload foto di Academy Next Top Model: 1. Login ke akun website -> 2. Masuk ke menu profil/portofolio -> 3. Unggah foto close-up (wajah) dan foto full body (format JPEG/JPG/PNG, jelas, tidak diedit berlebihan) -> 4. Simpan profil.",
        "untuk mengunggah foto portofolio: buka menu profil di website, pilih foto close-up & full body yang jelas dan terang berformat JPG/PNG, lalu simpan profil."
    ],
    "registration_requirements": [
        "persyaratan utama pendaftaran adalah: usia 4–25 tahun, mengisi formulir online, menyiapkan foto close-up dan foto full body, serta menyelesaikan proses pembayaran registrasi.",
        "syarat bergabung antara lain: usia maksimal 25 tahun, melengkapi form pendaftaran, melampirkan foto close-up & full body, dan konfirmasi pembayaran."
    ],
    "location_info": [
        "Academy Next Top Model berlokasi di Trans Studio Mall Cibubur, GF Floor Second Atrium.",
        "lokasi kelas kami ada di Trans Studio Mall Cibubur, GF Floor Second Atrium."
    ],
    "schedule_info": [
        "kelas berlangsung setiap hari Sabtu, pukul 11.00 sampai 15.00 WIB.",
        "jadwal reguler adalah tiap Sabtu dari jam 11 pagi hingga 3 sore WIB di TSM Cibubur."
    ],
    "program_info": [
        "program pelatihan mencakup: Catwalk Basic, Catwalk Advanced, Runway Class, Beauty Class, Pose Class, Photoshoot, Self Branding, dan Public Speaking.",
        "materi yang diajarkan meliputi: teknik catwalk (basic & advanced), runway, beauty & pose class, sesi photoshoot, self branding, hingga public speaking."
    ],
    "become_model_info": [
        "untuk menjadi model di Academy Next Top Model: daftar online → ikuti pelatihan mingguan → bangun portofolio digital → tampilkan karya terbaik Anda.",
        "langkah menjadi model profesional: (1) daftar & verifikasi akun, (2) ikuti program kelas rutin setiap Sabtu, (3) bangun portofolio foto berkualitas, (4) tingkatkan exposure melalui platform kami."
    ],
    "model_info": [
        "Anda bisa melihat daftar lengkap model dan talent kami di halaman Models pada website. Sebutkan nama model yang ingin Anda cari, dan saya akan membantu.",
        "katalog model tersedia di halaman Models website kami. Apakah ada nama model spesifik yang ingin Anda cek ketersediaannya?"
    ],
    "homepage_info": [
        "Academy Next Top Model adalah akademi modeling profesional yang berfokus pada pelatihan, pengembangan portofolio digital, dan manajemen talent model sejak 2015.",
        "kami adalah lembaga pelatihan modeling terpercaya yang dipimpin oleh Oliviarie Summer (Miss Queen Indonesia 2023). Visi kami: Be smart, Beautiful, and Confident."
    ],
    "greeting": [
        "Selamat datang di Academy Next Top Model! 👋 Ada yang bisa saya bantu seputar pendaftaran, program kelas, jadwal, atau informasi model?",
        "Halo! Saya asisten virtual Academy Next Top Model. Silakan tanyakan apa saja seputar program pelatihan, cara daftar, lokasi, atau informasi model kami.",
        "Hai! Senang bertemu Anda. 😊 Saya siap membantu menjawab pertanyaan seputar Academy Next Top Model."
    ],
    "gratitude": [
        "Sama-sama! Senang bisa membantu. Jika ada pertanyaan lain seputar Academy Next Top Model, jangan ragu bertanya ya. 😊",
        "Dengan senang hati! Terima kasih sudah menghubungi kami. Semoga harimu menyenangkan!",
        "Sama-sama! Ada hal lain yang ingin Anda ketahui tentang Academy Next Top Model?"
    ],
    "technical_support": [
        "untuk kendala teknis seperti masalah login, OTP tidak masuk, atau akun bermasalah, silakan hubungi admin kami melalui WhatsApp untuk penanganan lebih cepat.",
        "jika mengalami kesulitan teknis pada website (login, verifikasi OTP, atau kendala akun), admin siap membantu via WhatsApp."
    ],
    "sensitive_intent": [
        "untuk informasi biaya, promo, diskon, sistem pembayaran, booking, atau kontrak, silakan langsung hubungi admin melalui WhatsApp agar mendapat info terbaru dan akurat.",
        "detail biaya dan penawaran terkini bisa dikonfirmasi langsung ke admin melalui WhatsApp. Admin kami siap melayani pertanyaan Anda."
    ]
}

# Alias for backward compatibility
INTENT_MAIN_ANSWERS = INTENT_CORE_RESPONSES

def get_current_time_slot() -> str:
    """Deteksi slot waktu berdasarkan jam server (pagi, siang, sore, malam)."""
    try:
        hour = datetime.now().hour
        if 5 <= hour < 11:
            return "morning"
        elif 11 <= hour < 15:
            return "afternoon"
        elif 15 <= hour < 18:
            return "evening"
        else:
            return "night"
    except Exception:
        return "default"

def select_unique_greeting(greeting_list: list, last_greeting: Optional[str] = None) -> str:
    """Pilih greeting unik yang berbeda dari sapaan sebelumnya (Anti-Repetition)."""
    candidates = [g for g in greeting_list if g != last_greeting]
    if not candidates:
        candidates = greeting_list
    return random.choice(candidates)

def compose_modular_response(core_response: str, intent: str = None,
                             memory: Optional[ConversationMemory] = None,
                             user_message: str = "") -> str:
    """
    Response Assembly berbasis Response Profile:
    - FULL: Greeting + Transition + Core + Closing
    - MINIMAL: Transition + Core
    - DIRECT: Core saja
    """
    if not core_response:
        return ""

    profile_name = INTENT_PROFILE_MAP.get(intent, INTENT_PROFILE_MAP["default"])

    # Respons khusus intent 'greeting' (Time-Aware User Greeting Detection)
    if intent == "greeting":
        time_slot = get_current_time_slot()
        time_greet_list = TIME_GREETINGS.get(time_slot, GREETING_VARIATIONS)
        last_greet = memory.last_greeting if memory else None
        chosen_time_greet = select_unique_greeting(time_greet_list, last_greet)
        if memory:
            memory.last_greeting = chosen_time_greet
        return f"{chosen_time_greet}\nAda yang bisa saya bantu hari ini seputar pendaftaran, jadwal, lokasi, atau informasi model?"

    if profile_name == "DIRECT":
        return core_response.strip()

    time_slot = get_current_time_slot()
    time_greet_list = TIME_GREETINGS.get(time_slot, GREETING_VARIATIONS)

    last_greet = memory.last_greeting if memory else None
    chosen_greeting = select_unique_greeting(time_greet_list, last_greet)
    if memory:
        memory.last_greeting = chosen_greeting

    chosen_trans = random.choice(TRANSITION_VARIATIONS)
    chosen_close = random.choice(CLOSING_VARIATIONS)

    if profile_name == "MINIMAL":
        return f"{chosen_trans}\n\n{core_response.strip()}"
    else:  # FULL
        return f"{chosen_greeting} {chosen_trans}\n\n{core_response.strip()}\n\n{chosen_close}"

def build_dynamic_response(intent: str, entities: dict,
                            memory: ConversationMemory,
                            reasoning: Optional[str] = None,
                            user_message: str = "") -> str:
    """Bangun respons dinamis berdasarkan Profile & Time-Aware Greeting.

    Pemilihan jawaban SSOT memakai ranking kemiripan query (config-driven)
    bila knowledge_retriever tersedia — bukan random.
    """
    if reasoning:
        return compose_modular_response(reasoning, intent, memory)

    # Query-aware SSOT selection (tanpa mengubah Clarification / Memory / NB)
    try:
        from modules.knowledge_retriever import select_knowledge_answer, get_fallback_message
        core_selected = select_knowledge_answer(
            user_message or "",
            intent,
            NLU_SSOT_DATA,
            allow_general_fallback=False,
        )
        if core_selected:
            return compose_modular_response(core_selected, intent, memory)
        fallback_msg = get_fallback_message(NLU_SSOT_DATA)
        return compose_modular_response(fallback_msg, intent, memory)
    except Exception:
        pass

    core_answers = get_ssot_intent_answers(intent)
    if not core_answers:
        fallback_msg = "Maaf, informasi mengenai hal tersebut belum tersedia di knowledge base kami. Silakan hubungi admin Academy Next Top Model melalui WhatsApp untuk bantuan lebih lanjut."
        return compose_modular_response(fallback_msg, intent, memory)

    # Fallback legacy: pilih varian pertama (stabil) jika retriever tidak tersedia
    core_selected = core_answers[0]
    return compose_modular_response(core_selected, intent, memory)


# ============================================================
# 10. MAIN NLU PROCESS FUNCTION
# ============================================================
def process_nlu(user_message: str, session_id: str,
                predicted_intent: str, confidence: float,
                secondary_intents: list = None) -> dict:
    """
    Pipeline NLU utama. Dipanggil setelah NB classification.
    Pipeline order:
      Resolve Reference → Extract Entities → Refinement → Memory Check
      → Multi-Intent Check → Clarification Engine → Response Generator
    Returns dict dengan keys: intent, confidence, reply, entities,
                               clarification, resolved_text, has_reasoning
    """
    memory = get_memory(session_id)
    secondary_intents = secondary_intents or memory.secondary_intents

    # Resolve reference dari konteks percakapan (anaphora resolution)
    resolved_text = memory.resolve_reference(user_message)

    # Extract entities
    entities = extract_entities(resolved_text)

    # ── STEP A: Cek apakah ada pending clarification dari turn sebelumnya ──
    clarification = None
    if getattr(memory, "clarification_pending", False):
        candidates = getattr(memory, "clarification_candidates", [])
        if candidates:
            resolved_intent = resolve_clarification_choice(user_message, candidates)
            if resolved_intent:
                # Pengguna menjawab pilihan → proses intent yang dipilih
                memory.clarification_pending = False
                memory.clarification_candidates = []
                refined_intent = resolved_intent
                refined_conf = CLARIFICATION_POLICY["ambiguous_max_confidence"]
            else:
                # Topik berubah → batalkan clarification, proses normal
                memory.clarification_pending = False
                memory.clarification_candidates = []
                refined_intent, refined_conf = refine_intent(
                    predicted_intent, confidence, memory, entities,
                    user_message=resolved_text)
        else:
            memory.clarification_pending = False
            refined_intent, refined_conf = refine_intent(
                predicted_intent, confidence, memory, entities,
                user_message=resolved_text)
    else:
        # ── STEP B: Alur normal → Refinement Layer ──────────────────────────
        refined_intent, refined_conf = refine_intent(
            predicted_intent, confidence, memory, entities,
            user_message=resolved_text)

    # ── STEP C: Intent Reasoning (entity-based) ─────────────────────────────
    reasoning = apply_reasoning(refined_intent, entities, memory)

    # ── STEP D: Clarification Engine (zona ambigu) ──────────────────────────
    final_intent = refined_intent
    final_conf   = refined_conf

    if refined_intent == "low_confidence":
        # Confidence terlalu rendah → buat ranked candidates lalu tanya klarifikasi
        ranked = _ensure_clarification_candidates(rank_clarification_candidates(
            "low_confidence", refined_conf, user_message, memory, secondary_intents))
        reply = build_clarification_question(ranked, memory)
        final_intent = "clarification"
        clarification = reply
        memory.clarification_pending = True
        memory.clarification_candidates = ranked

    elif should_clarify(refined_intent, refined_conf, memory, secondary_intents):
        # Zona ambigu → ranking multi-sinyal → tanya klarifikasi
        ranked = _ensure_clarification_candidates(rank_clarification_candidates(
            refined_intent, refined_conf, user_message, memory, secondary_intents))
        reply = build_clarification_question(ranked, memory)
        final_intent = "clarification"
        clarification = reply
        memory.clarification_pending = True
        memory.clarification_candidates = ranked

    else:
        # ── STEP E: Confidence cukup → langsung jawab ───────────────────────
        reply = build_dynamic_response(
            refined_intent, entities, memory, reasoning, user_message=resolved_text
        )

    return {
        "intent": final_intent,
        "confidence": final_conf,
        "reply": reply,
        "entities": entities,
        "clarification": clarification,
        "resolved_text": resolved_text,
        "has_reasoning": reasoning is not None,
    }


def update_memory(session_id: str, user_msg: str,
                  bot_reply: str, intent: str, entities: dict,
                  confidence: float = 1.0, secondary_intents: list = None):
    """Update conversation memory setelah reply dikirim."""
    mem = get_memory(session_id)
    mem.add_turn(user_msg, bot_reply, intent, entities,
                 confidence=confidence, secondary_intents=secondary_intents or [])
