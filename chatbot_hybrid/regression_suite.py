"""
Regression suite permanen — Academy Next Top Model Chatbot (Retrieval Layer).

Berisi 94 query dari audit Tahap 2 (49 skenario natural/manual "Part A" +
44 query nama model asli "Part B", 22 model x 2 frasa: "status model X" dan
"cari model X"). Dipakai untuk memvalidasi SETIAP batch perbaikan retrieval
layer: bug yang diperbaiki harus jadi benar, dan seluruh query lain yang
sudah benar di baseline Tahap 2 TIDAK BOLEH regresi.

Cara pakai:
    python regression_suite.py
        Jalankan semua query, cetak hasil ke stdout saja (tidak menulis file).

    python regression_suite.py --output hasil.json
        Jalankan semua query, simpan hasil sebagai JSON ke path yang diberikan.

    python regression_suite.py --output sesudah.json --compare-against sebelum.json
        Jalankan, simpan ke sesudah.json, lalu tampilkan diff terhadap baseline
        sebelum.json (SAMA / BERUBAH / TIDAK ADA DI BASELINE).

Catatan penting:
- Query dikirim lewat Flask test_client() langsung ke endpoint /chat asli
  (jalur eksekusi identik dengan server sungguhan — routing, DB, semua layer
  chat() ikut jalan), bukan memanggil fungsi internal secara terisolasi.
- Script ini TIDAK menulis file apa pun ke repo secara default — path
  --output harus diberikan eksplisit. Simpan baseline/hasil di luar repo
  (mis. direktori scratchpad) agar file ini tetap satu-satunya file baru
  yang ditambahkan untuk keperluan audit Tahap 2/3.
- Daftar query di bawah adalah snapshot APA ADANYA dari Tahap 2 — jangan
  diubah kecuali ada keputusan eksplisit untuk memperluas regression suite.
"""
import argparse
import json
import re
import sys
import uuid
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

import app as appmod  # noqa: E402  (import setelah sys.path diset)


# ============================================================
# NOISE FILTER — wrapper greeting/transisi/penutup/klarifikasi-intro
# =====================================================================
# nlu_engine.build_dynamic_response()/build_clarification_question()
# menempelkan kalimat pembuka/transisi/penutup yang dipilih ACAK
# (random.choice) dari daftar variasi di modules/nlu_engine.py — bukan
# bagian dari isi jawaban yang sebenarnya. Tanpa difilter, ini membuat
# diff --compare-against menandai "BERUBAH" padahal isi jawabannya sama
# persis (terbukti di Batch 1: "model unavailable"/"model usia 15"
# berubah wording pembuka 4x berturut-turut TANPA perubahan kode apa pun).
#
# Daftar variasi diimpor LANGSUNG dari modules/nlu_engine.py (bukan
# disalin manual) supaya filter ini otomatis tetap sinkron jika daftar
# itu berubah di kemudian hari — modules/nlu_engine.py sendiri TIDAK
# diubah, hanya dibaca (import read-only).
# ============================================================
def _load_wrapper_variants():
    try:
        from modules.nlu_engine import (
            TIME_GREETINGS, GREETING_VARIATIONS, TRANSITION_VARIATIONS,
            CLOSING_VARIATIONS, CLARIFICATION_INTROS,
        )
    except Exception:
        return []
    variants = []
    for lst in TIME_GREETINGS.values():
        variants.extend(lst)
    variants.extend(GREETING_VARIATIONS)
    variants.extend(TRANSITION_VARIATIONS)
    variants.extend(CLOSING_VARIATIONS)
    variants.extend(CLARIFICATION_INTROS)
    # Terpanjang dulu, supaya frasa yang jadi substring frasa lain tidak
    # menyisakan potongan parsial setelah dihapus.
    return sorted(set(v for v in variants if v), key=len, reverse=True)


_WRAPPER_VARIANTS = _load_wrapper_variants()


# ============================================================
# VARIANT-TIE TOLERANCE — field yang kena random tie-break di
# select_best_variant() (modules/knowledge_retriever.py)
# =====================================================================
# Sejak fix "random tie-break for select_best_variant() to prevent
# permanent variant starvation", 4 field berikut punya >=2 variant
# jawaban yang skor kemiripannya identik untuk query umum ("apa itu X",
# "jadwal kelas", dst) — select_best_variant() sekarang memilih SATU
# secara acak di antara yang tie, alih-alih selalu variant terpendek.
# Ini benar dan disengaja (lihat AUDIT_PROGRESS.md), tapi artinya
# perbandingan string-persis terhadap baseline TIDAK BISA stabil untuk
# field ini — bukan berarti hasilnya boleh diabaikan begitu saja,
# harus tetap divalidasi sebagai ANGGOTA SAH pool variant field itu.
# ============================================================
TIE_PRONE_RESPONSE_KEYS = {
    "about_academy", "registration_requirements", "homepage_info", "schedule_info",
}


def _load_variant_pools():
    path = Path(__file__).resolve().parent / "data" / "academy_enhanced.json"
    try:
        with open(path, "r", encoding="utf-8") as f:
            data = json.load(f)
    except Exception:
        return {}
    responses = data.get("responses", {})
    pools = {}
    for key in TIE_PRONE_RESPONSE_KEYS:
        raw = responses.get(key)
        if isinstance(raw, dict):
            variants = raw.get("id") or raw.get("en") or []
        elif isinstance(raw, list):
            variants = raw
        else:
            variants = []
        pools[key] = {str(v).strip() for v in variants if str(v).strip()}
    return pools


_VARIANT_POOLS = _load_variant_pools()

try:
    from modules.knowledge_retriever import canonicalize_intent as _canonicalize_intent
except Exception:
    _canonicalize_intent = None


def _canonical_response_key(intent):
    if not intent or not _canonicalize_intent:
        return intent
    try:
        return _canonicalize_intent(intent, getattr(appmod, "ACADEMY_DATA", None))
    except Exception:
        return intent


def normalize_core_reply(text):
    """Hilangkan kalimat wrapper greeting/transisi/penutup/klarifikasi-intro
    acak, sisakan ISI jawaban saja, supaya perbandingan regresi fokus ke
    perubahan perilaku nyata (intent/isi), bukan noise pemilihan kalimat
    pembuka/penutup yang memang didesain acak."""
    if not text:
        return text
    normalized = text
    for phrase in _WRAPPER_VARIANTS:
        normalized = normalized.replace(phrase, " ")
    normalized = re.sub(r"[ \t]+", " ", normalized)
    normalized = re.sub(r"\n\s*\n+", "\n\n", normalized)
    normalized = "\n".join(line.strip() for line in normalized.split("\n"))
    return normalized.strip("\n ").strip()


# ============================================================
# PART A — 50 query natural/manual (kombinasi kategori, kota,
# status, usia, rekomendasi, jadwal/knowledge, typo, dan probe
# edge-case tabrakan nama-vs-kategori dari audit Tahap 2)
# ============================================================
PART_A = [
    "ada berapa model",
    "ada berapa model remaja",
    "ada berapa model anak",
    "berapa model aktif",
    "model remaja",
    "model anak",
    "model dari bekasi",
    "model aktif bogor",
    "model remaja dari bekasi",
    "siapa model bogor",
    "apakah ada model kids",
    "saya butuh model remaja",
    "saya cari model anak",
    "tolong rekomendasikan model hijab",
    "jam berapa kelas dimulai",
    "kelas diadakan hari apa",
    "jam berapa kelas",
    "lokasi kelas dimana",
    "nomor admin berapa",
    "syarat daftar apa",
    "jadwal kelas",
    "model teens dari depok",
    "model unavailable",
    "model tidak tersedia",
    "daftar model kids",
    "model apa saja yang tersedia",
    "rekomendasi model remaja",
    "rekomendasi model untuk tema laut",
    "cari model untuk produk anak",
    "status model bekasi",
    "status model kota",
    "cari model aktif",
    "cari model remaja",
    "status model anak",
    "status model remaja",
    "cari model bogor",
    "apakah model sabrina masih aktif",
    "apakah model sabrina tersedia",
    "cek model putri",
    "adakah model bernama camilla",
    "model dengan nama kyna",
    "halo, ada berapa model remaja yang aktif dan dari bogor?",
    "ada model dari bandung?",
    "ada model dari cibubur?",
    "usia berapa saja model yang terdaftar",
    "model usia 15",
    "model usia 10-15",
    "jadwl kelas kpan",
    "ada model romaja gak",
    "berapa byaya pendaftaran",
]

# ============================================================
# PART B — 44 query: seluruh nama model asli di database saat
# audit Tahap 2 dijalankan (22 model x 2 frasa). Dipertahankan
# apa adanya sebagai snapshot regresi — jika data model di DB
# berubah kelak, hasil "tidak ditemukan" untuk nama yang sudah
# tak ada tetap valid dibandingkan (bukan kegagalan suite).
# ============================================================
MODEL_NAMES = [
    "Afia Khansa Rafani", "Camilla Aleecea", "Violla Alexandria Kirana",
    "Clarissa Cheryl Steyz", "Nathania Raynelle Wijaya", "Nikita Wilmer Chung Steyz",
    "Michelle Fidellia Parinussa", "Kalyca Lashira", "Chevazrilia Alfaruk",
    "Aleeya Shafiyah", "Alisha Ahri", "Nazwa Audina", "Rara Dharmawan", "Kyna",
    "Putri Nailah Azzahra", "Dini Aura", "Kirana Khanza Aurelia", "Maya Amjad",
    "Najwa Madina Azzahra", "Fathia Riyandita Anwari", "Caren Magdalena Praselia",
    "Sabrina Andhita Alkaf",
]

PART_B = []
for _name in MODEL_NAMES:
    PART_B.append(f"status model {_name}")
    PART_B.append(f"cari model {_name}")

ALL_QUERIES = [("A", q) for q in PART_A] + [("B", q) for q in PART_B]


def run_suite():
    """Jalankan seluruh query lewat Flask test_client() (jalur /chat asli)."""
    client = appmod.app.test_client()
    results = []
    for tag, q in ALL_QUERIES:
        sid = f"regression_{uuid.uuid4().hex[:8]}"
        resp = client.post("/chat", json={"message": q, "session_id": sid})
        try:
            data = resp.get_json() or {}
        except Exception as e:
            data = {"error": str(e)}
        results.append({
            "tag": tag,
            "query": q,
            "intent": data.get("intent"),
            "source": data.get("source"),
            "confidence": data.get("confidence"),
            "reply": data.get("reply"),
        })
    return results


def compare(current, baseline_path):
    """Perbandingan STRICT: intent + source + reply string persis.
    Bisa menandai 'BERUBAH' palsu untuk query yang lewat wrapper acak
    nlu_engine (greeting/transisi/penutup) — lihat compare_core() untuk
    mode yang menyaring noise itu."""
    with open(baseline_path, "r", encoding="utf-8") as f:
        baseline = json.load(f)
    baseline_by_query = {b["query"]: b for b in baseline}

    changed, same, missing = [], [], []
    for r in current:
        b = baseline_by_query.get(r["query"])
        if b is None:
            missing.append(r["query"])
            continue
        if (r["intent"], r["source"], r["reply"]) != (b["intent"], b["source"], b["reply"]):
            changed.append((r["query"], b, r))
        else:
            same.append(r["query"])
    return changed, same, missing


def compare_core(current, baseline_path):
    """Perbandingan TOLERAN-NOISE: intent + source + reply setelah
    kalimat wrapper greeting/transisi/penutup/klarifikasi-intro acak
    dihapus (normalize_core_reply). Dipakai supaya perubahan wording
    acak (bukan perubahan perilaku) tidak ikut ditandai 'BERUBAH'."""
    with open(baseline_path, "r", encoding="utf-8") as f:
        baseline = json.load(f)
    baseline_by_query = {b["query"]: b for b in baseline}

    changed, same, missing = [], [], []
    for r in current:
        b = baseline_by_query.get(r["query"])
        if b is None:
            missing.append(r["query"])
            continue
        r_core = normalize_core_reply(r["reply"])
        b_core = normalize_core_reply(b["reply"])
        if (r["intent"], r["source"], r_core) != (b["intent"], b["source"], b_core):
            changed.append((r["query"], b, r, b_core, r_core))
        else:
            same.append(r["query"])
    return changed, same, missing


def compare_tolerant_variants(current, baseline_path):
    """Seperti compare_core() (toleran wrapper), TAPI untuk query yang
    intent-nya masuk salah satu TIE_PRONE_RESPONSE_KEYS: reply dianggap
    SAMA kalau (intent, source) cocok dengan baseline DAN core reply
    adalah ANGGOTA SAH pool variant field itu di academy_enhanced.json
    — bukan sekadar di-skip. Reply yang bukan anggota pool sah (mis.
    field/isi berubah karena bug, bukan karena tie-break) tetap
    ditandai BERUBAH seperti biasa."""
    with open(baseline_path, "r", encoding="utf-8") as f:
        baseline = json.load(f)
    baseline_by_query = {b["query"]: b for b in baseline}

    changed, same, missing = [], [], []
    for r in current:
        b = baseline_by_query.get(r["query"])
        if b is None:
            missing.append(r["query"])
            continue

        r_core = normalize_core_reply(r["reply"])
        b_core = normalize_core_reply(b["reply"])

        # Exact match (termasuk sub-field templated deterministik seperti
        # schedule_info.time/.day) -> selalu SAMA, tidak perlu cek pool.
        if (r["intent"], r["source"], r_core) == (b["intent"], b["source"], b_core):
            same.append(r["query"])
            continue

        # Tidak exact match -> baru toleransi tie-break: hanya SAMA kalau
        # intent+source cocok DAN reply saat ini anggota sah pool variant
        # umum field itu (bukan field spesifik lain yang kebetulan share intent).
        canonical_key = _canonical_response_key(r["intent"])
        pool = _VARIANT_POOLS.get(canonical_key)
        if pool and r["intent"] == b["intent"] and r["source"] == b["source"] and r_core in pool:
            same.append(r["query"])
        else:
            changed.append((r["query"], b, r, b_core, r_core))
    return changed, same, missing


def main():
    parser = argparse.ArgumentParser(description="Regression suite permanen (94 query Tahap 2)")
    parser.add_argument("--output", default=None, help="Simpan hasil run sebagai JSON ke path ini (opsional)")
    parser.add_argument("--compare-against", default=None, help="Baseline JSON untuk dibandingkan (opsional)")
    parser.add_argument(
        "--ignore-wrapper", action="store_true",
        help="Saat --compare-against dipakai, abaikan variasi kalimat greeting/transisi/"
             "penutup/klarifikasi-intro acak (nlu_engine) — bandingkan isi jawaban saja.",
    )
    parser.add_argument(
        "--tolerate-variant-tie", action="store_true",
        help="Otomatis toleran-wrapper juga (disarankan tetap pasang --ignore-wrapper "
             "eksplisit untuk kejelasan). Untuk field yang diketahui kena random "
             "tie-break di select_best_variant() (about_academy, "
             "registration_requirements, homepage_info, schedule_info), reply dianggap "
             "SAMA jika (intent, source) cocok DAN reply adalah anggota sah pool variant "
             "field itu — bukan sekadar diabaikan. Query lain tidak terpengaruh.",
    )
    parser.add_argument("--quiet", action="store_true", help="Jangan cetak detail per-query, hanya ringkasan")
    args = parser.parse_args()

    results = run_suite()

    if not args.quiet:
        for r in results:
            print(f"[{r['tag']}] {r['query']}")
            print(f"    intent={r['intent']} source={r['source']} conf={r['confidence']}")
            print(f"    reply={(r['reply'] or '')[:160]}")

    print("=" * 70)
    print(f"TOTAL QUERIES: {len(results)}")

    if args.output:
        with open(args.output, "w", encoding="utf-8") as f:
            json.dump(results, f, ensure_ascii=False, indent=2)
        print(f"Hasil disimpan ke: {args.output}")

    exit_code = 0
    if args.compare_against:
        if args.tolerate_variant_tie:
            mode = "TOLERAN-NOISE + TOLERAN-VARIANT-TIE"
            changed, same, missing = compare_tolerant_variants(results, args.compare_against)
        elif args.ignore_wrapper:
            mode = "TOLERAN-NOISE (--ignore-wrapper)"
            changed, same, missing = compare_core(results, args.compare_against)
        else:
            mode = "STRICT"
            changed, same, missing = compare(results, args.compare_against)
        print("=" * 70)
        print(f"DIBANDINGKAN DENGAN BASELINE ({mode}): {args.compare_against}")
        print(f"  SAMA (tidak berubah):    {len(same)}")
        print(f"  BERUBAH:                 {len(changed)}")
        print(f"  TIDAK ADA DI BASELINE:   {len(missing)}")
        if changed:
            print("-" * 70)
            print("Detail perubahan:")
            for item in changed:
                if args.ignore_wrapper or args.tolerate_variant_tie:
                    q, b, r, b_core, r_core = item
                    print(f"  Q: {q}")
                    print(f"    SEBELUM: intent={b['intent']} source={b['source']}")
                    print(f"             core={b_core[:150]}")
                    print(f"    SESUDAH: intent={r['intent']} source={r['source']}")
                    print(f"             core={r_core[:150]}")
                else:
                    q, b, r = item
                    print(f"  Q: {q}")
                    print(f"    SEBELUM: intent={b['intent']} source={b['source']}")
                    print(f"             reply={(b['reply'] or '')[:150]}")
                    print(f"    SESUDAH: intent={r['intent']} source={r['source']}")
                    print(f"             reply={(r['reply'] or '')[:150]}")
        if missing:
            exit_code = 1

    return exit_code


if __name__ == "__main__":
    raise SystemExit(main())
