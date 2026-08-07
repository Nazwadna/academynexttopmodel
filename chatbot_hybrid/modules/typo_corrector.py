"""
TYPO CORRECTOR — Academy Next Top Model Chatbot
Koreksi typo ringan sebelum tahap RapidFuzz & TF-IDF/Naive Bayes.

Prinsip desain:
  - Hanya mengoreksi jika threshold kemiripan sangat tinggi (>= 88%)
  - Koreksi per-token (word-level), bukan sentence-level
  - Tidak menggantikan RapidFuzz; bekerja sebelumnya
  - Tidak mengubah kata yang sudah benar
  - Domain-limited: hanya mengoreksi kosakata domain chatbot ini
  - Tidak meningkatkan false positive
"""
import re
from rapidfuzz import process, fuzz

# ============================================================
# 1. DOMAIN VOCABULARY — kosakata valid chatbot
# ============================================================
DOMAIN_VOCAB = {
    # Pendaftaran
    "daftar", "mendaftar", "pendaftaran", "registrasi", "register",
    "formulir", "syarat", "persyaratan", "bergabung", "gabung",
    "join", "masuk", "ikut", "ikutan", "apply",

    # Lokasi
    "lokasi", "alamat", "tempat", "gedung", "kantor", "studio",
    "tsm", "cibubur", "trans", "studio", "mall", "lantai",

    # Jadwal
    "jadwal", "jam", "waktu", "kapan", "hari", "sabtu", "minggu",
    "sesi", "pukul", "senin", "selasa", "rabu", "kamis", "jumat",

    # Program / Kelas
    "program", "kelas", "materi", "kurikulum", "pelatihan",
    "training", "kursus", "catwalk", "runway", "beauty", "pose",
    "photoshoot", "photo", "foto", "portfolio", "portofolio",
    "upload", "unggah", "branding", "speaking",

    # Model / Talent
    "model", "models", "mode", "talent", "anggota", "peserta", "member",
    "remaja", "anak", "kids", "teens", "teen",

    # Biaya (sensitive — hanya normalisasi, bukan ambil jawaban)
    "biaya", "harga", "bayar", "tarif", "fee", "diskon",
    "promo", "cicilan", "booking", "kontrak",

    # Teknis / Auth
    "login", "password", "akun", "otp", "verifikasi",
    "email", "whatsapp", "instagram", "website", "kontak",

    # Ukuran (loanword umum, mis. "baju size small") — TIDAK boleh
    # ke-fuzzy-correct jadi "mall" (QRatio("small","mall")=88.89).
    "small",

    # Sapaan & Umum
    "halo", "hai", "selamat", "pagi", "siang", "sore", "malam",
    "terima", "kasih", "admin", "bagaimana", "berapa", "dimana",
    "tidak", "ingin", "bisa", "saya", "kami", "tolong", "untuk",
    "kapan", "apa", "apakah", "academy", "akademi",
}

# Token yang TIDAK boleh dikoreksi (kata-kata pendek atau ambigu)
NO_CORRECT_TOKENS = {
    # Kata sangat pendek yang bisa salah koreksi
    "di", "ke", "dari", "dan", "atau", "yang", "ini", "itu",
    "ya", "ok", "oke", "hai", "hi", "wa", "ga", "si",
    # Singkatan yang sudah ditangani SLANG_MAP
    "gmn", "brp", "min", "sy", "ak", "yg", "mw", "bs",
    "gak", "nggak", "thx", "sdh", "udh", "kpn", "blm",
}

# Threshold minimum kemiripan untuk koreksi (sangat ketat)
TYPO_CORRECTION_THRESHOLD = 88  # WRatio score, rentang 0-100
# Token harus minimal sepanjang ini agar dikoreksi (hindari false positive di token pendek)
MIN_TOKEN_LENGTH = 4

# Whitelist typo spesifik (BUG-7) — mapping manual, eksplisit, TIDAK memengaruhi
# TYPO_CORRECTION_THRESHOLD global. Dibutuhkan karena typo umum kata kategori
# model (mis. "romaja" -> "remaja") skor fuzzy-nya di bawah threshold 88
# (QRatio("romaja","remaja")=83.33), tapi menurunkan threshold global TERBUKTI
# TIDAK aman saat disimulasikan (38 false positive pada kata sehari-hari umum
# seperti "sama"->"saya"/"makasih"->"kasih", plus 54 kata domain saling
# tertukar seperti "harga"->"hari"/"bisa"->"biaya", pada threshold serendah
# ~67-75 yang dibutuhkan untuk menangkap SEMUA varian typo kategori ini).
# Whitelist ini adalah exact-match tertutup, jadi 0 risiko false positive baru.
TYPO_WHITELIST = {
    "romaja": "remaja",
    "remja": "remaja",
    "remajaa": "remaja",
    "anka": "anak",
    "naak": "anak",
    "kidz": "kids",
    "kds": "kids",
    "tens": "teens",
    "tenns": "teens",
}

# ============================================================
# 2. FAST HEURISTIC CHECK — apakah token kemungkinan mengandung typo
# ============================================================

def _has_repeated_chars(token: str, max_repeat: int = 2) -> bool:
    """Deteksi karakter berulang lebih dari max_repeat kali berturut-turut."""
    count = 1
    for i in range(1, len(token)):
        if token[i] == token[i - 1]:
            count += 1
            if count > max_repeat:
                return True
        else:
            count = 1
    return False


def _looks_like_typo(token: str) -> bool:
    """
    Heuristik cepat: apakah token ini layak dicoba koreksi?
    Tujuan: skip token yang jelas sudah benar, hemat CPU.
    """
    if len(token) < MIN_TOKEN_LENGTH:
        return False
    if token in NO_CORRECT_TOKENS:
        return False
    if token in DOMAIN_VOCAB:
        return False  # Sudah benar
    if _has_repeated_chars(token):
        return True   # "daftarr", "jadwalll", "gabungg"
    # Token tidak ada di vocab domain — kandidat typo
    return True


# ============================================================
# 3. TOKEN-LEVEL TYPO CORRECTOR
# ============================================================

def correct_token(token: str) -> str:
    """
    Koreksi satu token menggunakan RapidFuzz terhadap DOMAIN_VOCAB.
    Return token asli jika tidak ada koreksi yang cukup meyakinkan.
    """
    if token in TYPO_WHITELIST:
        return TYPO_WHITELIST[token]

    if not _looks_like_typo(token):
        return token

    result = process.extractOne(
        token,
        DOMAIN_VOCAB,
        scorer=fuzz.QRatio,  # QRatio lebih tepat untuk single token
        score_cutoff=TYPO_CORRECTION_THRESHOLD,
    )
    if result is None:
        return token

    corrected, score, _ = result

    # Jangan koreksi jika panjang token berbeda terlalu jauh
    # (misal: "ga" jangan → "pagi")
    if abs(len(corrected) - len(token)) > 3:
        return token

    return corrected


# ============================================================
# 4. SENTENCE-LEVEL TYPO CORRECTION
# ============================================================

def correct_text(text: str) -> tuple[str, dict]:
    """
    Koreksi seluruh teks secara token per token.

    Returns:
        corrected_text: str  — teks setelah koreksi
        corrections: dict    — pemetaan {token_asli: token_koreksi}
    """
    text = text.lower().strip()
    tokens = text.split()
    corrected_tokens = []
    corrections = {}

    for tok in tokens:
        # Bersihkan tanda baca menempel
        clean = re.sub(r"[^a-z0-9]", "", tok)
        if not clean:
            corrected_tokens.append(tok)
            continue

        fixed = correct_token(clean)
        if fixed != clean:
            corrections[clean] = fixed
            corrected_tokens.append(fixed)
        else:
            corrected_tokens.append(clean)

    corrected_text = " ".join(corrected_tokens)
    return corrected_text, corrections


# ============================================================
# 5. UTILITY: LOG SUMMARY
# ============================================================

def log_corrections(original: str, corrected: str, corrections: dict) -> None:
    if corrections:
        print(f"[TYPO] '{original}' → '{corrected}' | Koreksi: {corrections}")
    else:
        print(f"[TYPO] Tidak ada koreksi pada: '{original}'")
