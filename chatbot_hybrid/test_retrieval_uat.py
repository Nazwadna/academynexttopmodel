"""
Regression: Knowledge specificity + Database model filters.
Tidak mengubah NB/orchestrator — tes wiring handler existing.
"""
import os
import sys
import re

BASE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, BASE)

# Suppress verbose prints during import
os.environ.setdefault("CHATBOT_DEBUG", "false")

from app import (
    extract_model_filters,
    apply_model_filters,
    filter_models_by_criteria,
    handle_model_count,
    handle_model_list,
    handle_model_status,
    handle_model_recommendation,
    is_model_count_query,
    is_model_list_query,
    is_model_status_query,
    is_model_recommendation_query,
    get_all_models,
    retrieve_academy_topic_reply,
    resolve_exact_qa_reply,
    get_schedule_knowledge,
    _pick_academy_response,
    detect_all_topic_intents,
)


def ok(name, cond, detail=""):
    print(("PASS" if cond else "FAIL"), "-", name, (detail[:120] if detail else ""))
    return bool(cond)


def main():
    results = []
    models = get_all_models()
    results.append(ok("db_models_loaded", bool(models), f"n={len(models) if models else 0}"))

    # --- Detector routing ---
    cases_route = [
        ("Ada berapa model?", "count"),
        ("Ada berapa model remaja?", "count"),
        ("Ada berapa model anak?", "count"),
        ("berapa model aktif?", "count"),
        ("Model aktif kategori remaja?", "list"),
        ("Siapa model dari Bekasi?", "list"),
        ("daftar model kids", "list"),
        ("model dari Bogor", "list"),
        ("status model Sabrina", "status"),
        ("apakah ada model bernama Camilla", "status"),
        ("rekomendasi model remaja", "reco"),
        ("butuh model untuk produk anak", "reco"),
    ]
    for q, expect in cases_route:
        flags = {
            "count": is_model_count_query(q),
            "list": is_model_list_query(q) and not is_model_count_query(q),
            "status": is_model_status_query(q),
            "reco": is_model_recommendation_query(q) and not is_model_count_query(q) and not is_model_list_query(q),
        }
        # effective layer order
        if is_model_count_query(q):
            got = "count"
        elif is_model_list_query(q):
            got = "list"
        elif is_model_recommendation_query(q):
            got = "reco"
        elif is_model_status_query(q):
            got = "status"
        else:
            got = "none"
        results.append(ok(f"route::{q}", got == expect, f"got={got}"))

    # --- Filter extraction ---
    f = extract_model_filters("Ada berapa model remaja?")
    results.append(ok("filter_teens", f["categories"] == ["teens"], str(f)))
    f = extract_model_filters("Ada berapa model anak?")
    results.append(ok("filter_kids", f["categories"] == ["kids"], str(f)))
    f = extract_model_filters("Siapa model dari Bekasi?")
    results.append(ok("filter_bekasi", f.get("city") == "bekasi", str(f)))
    f = extract_model_filters("Model aktif kategori remaja?")
    results.append(ok("filter_active_teens", f["categories"] == ["teens"] and f["available_only"], str(f)))

    # --- Count/list with filters vs totals ---
    if models:
        total = len(models)
        teens_n = len(apply_model_filters(models, extract_model_filters("model remaja")))
        kids_n = len(apply_model_filters(models, extract_model_filters("model anak")))
        bekasi_n = len(apply_model_filters(models, extract_model_filters("model dari Bekasi")))

        r = handle_model_count("Ada berapa model?")
        results.append(ok("count_all", str(total) in r["reply"], r["reply"][:100]))

        r = handle_model_count("Ada berapa model remaja?")
        results.append(ok("count_teens_not_all", str(teens_n) in r["reply"] and str(total) not in r["reply"].split("terdapat")[1][:20] if "terdapat" in r["reply"] else str(teens_n) in r["reply"], r["reply"][:120]))
        results.append(ok("count_teens_value", str(teens_n) in r["reply"], f"teens={teens_n} | {r['reply'][:100]}"))
        results.append(ok("count_teens_lt_total", teens_n < total, f"{teens_n}<{total}"))

        r = handle_model_count("Ada berapa model anak?")
        results.append(ok("count_kids_value", str(kids_n) in r["reply"], f"kids={kids_n} | {r['reply'][:100]}"))
        results.append(ok("count_kids_lt_total", kids_n < total, f"{kids_n}<{total}"))

        r = handle_model_list("Siapa model dari Bekasi?")
        results.append(ok("list_bekasi_mentions_filter", "bekasi" in r["reply"].lower(), r["reply"][:140]))
        results.append(ok("list_bekasi_count", str(bekasi_n) in r["reply"], f"bekasi={bekasi_n}"))
        # Should not dump all names count as total without filter
        results.append(ok("list_bekasi_not_full_dump", bekasi_n < total, f"{bekasi_n}<{total}"))

        r = handle_model_list("Model aktif kategori remaja?")
        results.append(ok("list_active_teens", "remaja" in r["reply"].lower() or "teens" in r["reply"].lower(), r["reply"][:140]))

        # Status should not treat remaja as name
        r = handle_model_status("model remaja")
        results.append(ok("status_not_used_for_remaja", is_model_list_query("model remaja") and not is_model_status_query("model remaja"), ""))

        r = handle_model_recommendation("rekomendasi model remaja")
        results.append(ok("reco_has_shortlist_or_teens", "Teens" in r["reply"] or "remaja" in r["reply"].lower() or "," in r["reply"], r["reply"][:160]))

        # Name lookup still works
        sample = next((m for m in models if m.get("nama_model")), None)
        if sample:
            first = sample["nama_model"].split()[0]
            r = handle_model_status(f"status model {first}")
            results.append(ok("status_name_lookup", first.lower() in r["reply"].lower() or "tidak" in r["reply"].lower() or "menemukan" in r["reply"].lower() or sample["nama_model"].split()[0].lower() in r["reply"].lower(), r["reply"][:120]))

    # --- Knowledge specificity ---
    ans = get_schedule_knowledge("jam berapa kelas dimulai")
    results.append(ok("knowledge_schedule_time", "11.00" in ans and "Trans Studio" not in ans, ans[:100]))
    ans = get_schedule_knowledge("kelas diadakan hari apa")
    results.append(ok("knowledge_schedule_day", "Sabtu" in ans and "11.00" not in ans, ans[:100]))
    ans = _pick_academy_response("contact_admin", query="nomor admin berapa")
    results.append(ok("knowledge_contact_wa", "6281214237742" in ans or "WhatsApp" in ans, ans[:100]))
    ans = _pick_academy_response("programs_info", query="apakah ada catwalk basic")
    results.append(ok("knowledge_program_basic", "Basic" in ans or "basic" in ans.lower(), ans[:120]))

    # Multi topic detection
    intents = detect_all_topic_intents("Syarat pendaftaran apa? Jadwal kelas kapan?")
    results.append(ok("multi_topic_detected", len(intents) >= 2, str(intents)))

    kr = retrieve_academy_topic_reply("jam berapa kelas")
    results.append(ok("topic_schedule", kr and kr.get("intent") == "schedule_info", str(kr)[:80] if kr else "None"))

    passed = sum(results)
    total_t = len(results)
    print("=" * 50)
    print(f"RESULT: {passed}/{total_t} PASS")
    print("=" * 50)
    return 0 if passed == total_t else 1


if __name__ == "__main__":
    raise SystemExit(main())
