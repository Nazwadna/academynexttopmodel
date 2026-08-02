# ============================================================
# Response Orchestrator — Academy Next Top Model Chatbot
# ============================================================
# Tanggung jawab: menyusun jawaban akhir yang cerdas, natural,
# dan kontekstual setelah intent diketahui.
#
# PRINSIP:
# - Semua aturan configuration-driven (tidak ada hardcode di engine).
# - Knowledge selalu diambil dari SSOT (academy_enhanced.json).
# - Penambahan intent baru cukup menambahkan entry di konfigurasi.
# - Tidak mengubah: TF-IDF, NB, preprocessing, Memory, Clarification,
#   Multi-Intent detector, Dynamic Response Generator, Time Greeting,
#   Anti-Repetition, SSOT Retrieval.
# ============================================================

import random
import re
from typing import Optional

# ============================================================
# 1. KONFIGURASI — INTENT PRIORITY
# Urutan prioritas saat lebih dari satu intent terdeteksi.
# Nilai lebih kecil = prioritas lebih tinggi.
# ============================================================
INTENT_PRIORITY: dict[str, int] = {
    "technical_support":        10,
    # otp_help / login_help (di bawah): saat ini tidak terjangkau — is_technical_support_query()
    # di app.py hanya mengembalikan intent generik "technical_support", tidak memecah
    # OTP/login/password. Entry ini kemungkinan placeholder untuk pemisahan granular di masa
    # depan; jangan dihapus tanpa konfirmasi produk. (Audit Tahap 4)
    "otp_help":                 15,
    "login_help":               20,
    "registration_info":        25,
    "registration_steps":       30,
    "registration_requirements": 35,
    "photo_upload":             40,
    "schedule_info":            50,
    "location_info":            55,
    "programs_info":            60,
    "benefit_info":             65,
    "become_model_info":        70,
    "about_academy":            75,
    "homepage_info":            80,
    "model_status":             85,
    "model_count":              90,
    "model_list":               95,
    "model_recommendation":     100,
    "contact_admin":            105,
    "pricing_info":             110,
    "pricing_sensitive":        115,
    "booking_info":             120,
}

_DEFAULT_PRIORITY = 999

# ============================================================
# 2. KONFIGURASI — SECTION LABELS
# Judul section per intent pada respons multi-intent.
# ============================================================
SECTION_LABELS: dict[str, str] = {
    "registration_requirements": "Syarat Pendaftaran",
    "photo_upload":              "Panduan Upload Foto",
    "registration_steps":        "Cara & Proses Pendaftaran",
    "registration_info":         "Info Pendaftaran",
    "schedule_info":             "Jadwal Academy",
    "location_info":             "Lokasi Academy",
    "programs_info":             "Program & Materi Kelas",
    "benefit_info":              "Manfaat Bergabung",
    "become_model_info":         "Panduan Jadi Model",
    "pricing_info":              "Biaya & Promo",
    "pricing_sensitive":         "Informasi Biaya",
    "model_count":               "Jumlah Model",
    "model_list":                "Daftar Model",
    "model_recommendation":      "Rekomendasi Model",
    "model_status":              "Status Model",
    "technical_support":         "Bantuan Teknis",
    "otp_help":                  "Bantuan OTP",
    "login_help":                "Bantuan Login",
    "contact_admin":             "Kontak Admin",
    "about_academy":             "Tentang Academy",
    "homepage_info":             "Profil Academy",
    "booking_info":              "Pemesanan Model",
}

# ============================================================
# 3. KONFIGURASI — RESPONSE PROFILES (Closing Style)
# ============================================================
RESPONSE_PROFILE: dict[str, dict] = {
    "sensitive": {
        "intents": {
            "pricing_info", "pricing_sensitive", "booking_info", "contact_admin"
        },
        "closings": [
            "Silakan hubungi admin Academy Next Top Model melalui WhatsApp untuk informasi lebih lanjut.",
            "Admin kami siap melayani pertanyaan Anda melalui WhatsApp.",
            "Untuk detail selengkapnya, silakan konfirmasi langsung ke admin via WhatsApp.",
        ],
    },
    "technical": {
        "intents": {"technical_support", "otp_help", "login_help"},
        "closings": [
            "Jika masalah masih berlanjut, silakan hubungi admin melalui WhatsApp untuk bantuan langsung.",
            "Admin kami siap membantu kendala teknis Anda melalui WhatsApp.",
        ],
    },
    "multi_long": {
        "min_sections": 3,
        "closings": [
            "Semoga informasi di atas membantu. Jangan ragu bertanya jika ada hal lain yang ingin Anda ketahui.",
            "Itu saja informasi yang dapat saya sampaikan. Apakah ada yang ingin Anda tanyakan lebih lanjut?",
            "Silakan bertanya kapan saja jika ada informasi lain yang Anda butuhkan.",
        ],
    },
    "friendly": {
        "closings": [
            "Semoga informasi ini bermanfaat! 😊",
            "Terima kasih telah bertanya. Ada hal lain yang bisa saya bantu?",
            "Semoga membantu. Jangan ragu untuk bertanya lagi ya!",
        ],
    },
}

# ============================================================
# 4. KONFIGURASI — TRANSITIONAL SENTENCES
# Variasi kalimat penghubung antar section.
# ============================================================
TRANSITION_PHRASES: list[str] = [
    "Selain itu,",
    "Berikut informasi lainnya,",
    "Selanjutnya,",
    "Satu hal lagi yang perlu diketahui,",
    "Informasi tambahan yang relevan,",
]

# ============================================================
# 5. KONFIGURASI — DEDUPLICATION FINGERPRINT KEYS
# Kunci yang digunakan untuk mendeteksi informasi duplikat.
# ============================================================
DEDUP_FINGERPRINT_KEYS: dict[str, list[str]] = {
    "location": ["trans studio mall", "tsm cibubur", "gf floor", "second atrium"],
    "schedule":  ["setiap hari sabtu", "pukul 11.00", "11.00 sampai 15.00"],
    "wa_admin":  ["whatsapp", "wa.me", "+6281214237742"],
    "program":   ["catwalk basic", "catwalk advanced", "runway class"],
    "register":  ["halaman register", "buka halaman", "isi data diri"],
    "syarat":    ["usia start 4th", "foto close-up", "foto full body"],
}


# ============================================================
# ENGINE FUNCTIONS
# ============================================================

def _sort_intents_by_priority(intents: list[str]) -> list[str]:
    """Urutkan intent berdasarkan INTENT_PRIORITY (value kecil = lebih dulu)."""
    return sorted(intents, key=lambda i: INTENT_PRIORITY.get(i, _DEFAULT_PRIORITY))


def _get_closing(intents: list[str], section_count: int) -> str:
    """Pilih kalimat penutup berdasarkan profil respons."""
    intent_set = set(intents)

    # Sensitive closing
    if intent_set & RESPONSE_PROFILE["sensitive"]["intents"]:
        return random.choice(RESPONSE_PROFILE["sensitive"]["closings"])

    # Technical closing
    if intent_set & RESPONSE_PROFILE["technical"]["intents"]:
        return random.choice(RESPONSE_PROFILE["technical"]["closings"])

    # Multi-long closing
    if section_count >= RESPONSE_PROFILE["multi_long"]["min_sections"]:
        return random.choice(RESPONSE_PROFILE["multi_long"]["closings"])

    # Default friendly closing
    return random.choice(RESPONSE_PROFILE["friendly"]["closings"])


def _compute_fingerprints(text: str) -> set[str]:
    """Hitung fingerprint topik dari teks untuk deduplication."""
    text_lower = text.lower()
    found = set()
    for fp_key, keywords in DEDUP_FINGERPRINT_KEYS.items():
        if any(k in text_lower for k in keywords):
            found.add(fp_key)
    return found


def _build_intro(section_count: int) -> str:
    """Buat kalimat pembuka berdasarkan jumlah section."""
    if section_count == 2:
        return "Berikut jawaban untuk kedua pertanyaan Anda:"
    return f"Berikut jawaban untuk {section_count} pertanyaan Anda:"


def orchestrate_multi_intent_response(
    intents: list[str],
    answers: dict[str, str],
    user_query: str = "",
    is_handoff: bool = False,
) -> str:
    """
    Susun jawaban multi-intent menjadi respons terstruktur yang:
    1. Diurutkan berdasarkan INTENT_PRIORITY.
    2. Deduplikasi informasi yang sama.
    3. Section hanya dibuat jika ada jawaban (tidak ada heading kosong).
    4. Diberi transisi natural antar section.
    5. Ditutup dengan kalimat sesuai profil.

    Parameters
    ----------
    intents  : Daftar nama intent yang terdeteksi.
    answers  : Dict {intent_name: teks_jawaban}.
    user_query: Query asli pengguna (untuk konteks).
    is_handoff: True jika ada intent yang memerlukan WhatsApp handoff.

    Returns
    -------
    str : Teks respons final yang sudah diorchestrate.
    """
    if not intents or not answers:
        return ""

    # Step 1 — Sort by priority
    ordered = _sort_intents_by_priority(intents)

    # Step 2 — Build sections with deduplication
    sections: list[str] = []
    seen_fingerprints: set[str] = set()

    for intent_name in ordered:
        answer = (answers.get(intent_name) or "").strip()
        if not answer:
            # Skip: tidak ada knowledge → tidak buat heading kosong
            continue

        # Dedup check
        fps = _compute_fingerprints(answer)
        overlap = fps & seen_fingerprints
        if overlap and fps:
            # Informasi duplikat — lewati section ini
            continue

        seen_fingerprints |= fps
        label = SECTION_LABELS.get(
            intent_name,
            intent_name.replace("_", " ").title()
        )
        sections.append((label, answer))

    if len(sections) < 2:
        # Jika setelah dedup hanya tersisa 1 section, kembalikan plain
        if sections:
            return sections[0][1]
        return ""

    # Step 3 — Assemble dengan transisi
    intro = _build_intro(len(sections))
    parts: list[str] = [intro]

    for idx, (label, content) in enumerate(sections):
        if idx == 0:
            parts.append(f"**{label}**\n{content}")
        else:
            transition = random.choice(TRANSITION_PHRASES)
            parts.append(f"{transition}\n\n**{label}**\n{content}")

    # Step 4 — Closing
    active_intents = [s for s in ordered if s in answers and answers.get(s)]
    closing = _get_closing(active_intents, len(sections))
    parts.append(f"\n{closing}")

    return "\n\n".join(parts)


def orchestrate_single_intent_response(
    intent: str,
    answer: str,
) -> str:
    """
    Untuk single intent: kembalikan jawaban apa adanya (orchestrator
    tidak menambahkan section heading pada single intent).
    Closing ditangani oleh Dynamic Response Generator yang sudah ada.
    """
    return answer.strip()
