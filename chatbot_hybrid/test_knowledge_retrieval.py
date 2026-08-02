"""
Regression tests for configuration-driven knowledge retrieval.
Tidak memanggil NB / Clarification / Orchestrator — fokus kualitas jawaban SSOT.
"""
import json
import os
import sys

BASE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, BASE)

from modules.knowledge_retriever import (
    select_knowledge_answer,
    select_best_variant,
    pick_academy_response,
    rank_topic_candidates,
)

with open(os.path.join(BASE, "data", "academy_enhanced.json"), encoding="utf-8") as f:
    DATA = json.load(f)


def check(name, cond, detail=""):
    status = "PASS" if cond else "FAIL"
    print(f"[{status}] {name}" + (f" — {detail}" if detail else ""))
    return bool(cond)


def main():
    results = []

    # 1) Schedule: jam → hanya waktu, bukan lokasi penuh
    ans = select_knowledge_answer("jam berapa kelas dimulai", "schedule_info", DATA)
    results.append(check(
        "schedule_time_specific",
        "11.00" in ans and "Trans Studio" not in ans and "Mall TSM" not in ans,
        ans[:120],
    ))

    # 2) Schedule: hari → hanya hari
    ans = select_knowledge_answer("kelas diadakan hari apa", "schedule_info", DATA)
    results.append(check(
        "schedule_day_specific",
        "Sabtu" in ans and "11.00" not in ans,
        ans[:120],
    ))

    # 3) Contact: nomor WA → varian WhatsApp, bukan alamat/IG
    ans = select_knowledge_answer("nomor admin berapa", "contact_admin", DATA)
    results.append(check(
        "contact_whatsapp_specific",
        "6281214237742" in ans or "WhatsApp" in ans,
        ans[:120],
    ))
    results.append(check(
        "contact_not_instagram_when_asking_number",
        "Instagram" not in ans and "Follow" not in ans,
        ans[:120],
    ))

    # 4) Programs: catwalk basic → detail program basic, bukan dump semua
    ans = select_knowledge_answer("apakah ada catwalk basic", "programs_info", DATA)
    results.append(check(
        "program_basic_specific",
        "Catwalk Basic" in ans or "basic" in ans.lower(),
        ans[:160],
    ))
    results.append(check(
        "program_basic_not_full_list",
        "Public Speaking" not in ans or "Catwalk Basic" in ans and ans.count(",") < 5,
        ans[:160],
    ))

    # 5) Become model + syarat → redirect requirements (bukan mega blob penuh)
    ans = select_knowledge_answer("syarat jadi model apa saja", "become_model_info", DATA)
    results.append(check(
        "become_model_redirect_requirements",
        "usia" in ans.lower() or "foto" in ans.lower() or "syarat" in ans.lower() or "persyaratan" in ans.lower(),
        ans[:160],
    ))
    results.append(check(
        "become_model_not_full_kitchen_sink",
        not ("Catwalk Advanced" in ans and "Sabtu" in ans and "WhatsApp" in ans),
        ans[:160],
    ))

    # 5b) Cara jadi model umum → panduan become_model, bukan redirect syarat
    ans = select_knowledge_answer("cara jadi model", "become_model_info", DATA)
    results.append(check(
        "become_model_general_guide",
        "Panduan" in ans or "model" in ans.lower(),
        ans[:160],
    ))
    results.append(check(
        "become_model_general_not_only_requirements_redirect",
        "Persyaratan usia" not in ans,
        ans[:160],
    ))

    # 6) About: sejak kapan → varian 2015
    ans = select_knowledge_answer("academy next top model berdiri sejak kapan", "about_academy", DATA)
    results.append(check(
        "about_since_2015",
        "2015" in ans,
        ans[:160],
    ))

    # 7) Requirements: usia
    ans = select_knowledge_answer("batas usia pendaftaran berapa", "registration_requirements", DATA)
    results.append(check(
        "requirements_age_specific",
        "4th" in ans or "25" in ans or "usia" in ans.lower(),
        ans[:160],
    ))

    # 8) Ranking variants prefer higher similarity
    variants = [
        "Hubungi kami via WhatsApp +6281214237742 atau email info@academyntm.com.",
        "Alamat: Trans Studio Mall Cibubur. Follow Instagram @academyntm.",
    ]
    best = select_best_variant("nomor whatsapp admin", variants)
    results.append(check("variant_rank_whatsapp", "WhatsApp" in best, best[:100]))

    # 9) Topic ranking prefers more specific intent
    ranked = rank_topic_candidates(
        "cara upload foto close up",
        ["become_model_info", "photo_upload", "registration_steps"],
        DATA,
    )
    results.append(check(
        "topic_rank_photo_first",
        ranked and ranked[0][1] == "photo_upload",
        str(ranked[:3]),
    ))

    # 10) Fallback resmi jika intent kosong
    ans = select_knowledge_answer("xyz unknown", "intent_tidak_ada", DATA)
    results.append(check(
        "official_fallback",
        "knowledge base" in ans.lower() or "whatsapp" in ans.lower(),
        ans[:120],
    ))

    # 11) pick_academy_response query-aware equals select
    a = pick_academy_response("schedule_info", DATA, query="jam berapa kelas")
    b = select_knowledge_answer("jam berapa kelas", "schedule_info", DATA)
    results.append(check("pick_equals_select", a == b, f"{a[:60]} | {b[:60]}"))

    # 12) General schedule still returns full when query is general
    ans = select_knowledge_answer("jadwal academy kapan saja", "schedule_info", DATA)
    results.append(check(
        "schedule_general_still_informative",
        "Sabtu" in ans or "11.00" in ans,
        ans[:160],
    ))

    passed = sum(results)
    total = len(results)
    print("\n" + "=" * 50)
    print(f"RESULT: {passed}/{total} PASS")
    print("=" * 50)
    return 0 if passed == total else 1


if __name__ == "__main__":
    raise SystemExit(main())
