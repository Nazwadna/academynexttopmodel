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
import sys
import uuid
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

import app as appmod  # noqa: E402  (import setelah sys.path diset)


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


def main():
    parser = argparse.ArgumentParser(description="Regression suite permanen (94 query Tahap 2)")
    parser.add_argument("--output", default=None, help="Simpan hasil run sebagai JSON ke path ini (opsional)")
    parser.add_argument("--compare-against", default=None, help="Baseline JSON untuk dibandingkan (opsional)")
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
        changed, same, missing = compare(results, args.compare_against)
        print("=" * 70)
        print(f"DIBANDINGKAN DENGAN BASELINE: {args.compare_against}")
        print(f"  SAMA (tidak berubah):    {len(same)}")
        print(f"  BERUBAH:                 {len(changed)}")
        print(f"  TIDAK ADA DI BASELINE:   {len(missing)}")
        if changed:
            print("-" * 70)
            print("Detail perubahan:")
            for q, b, r in changed:
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
