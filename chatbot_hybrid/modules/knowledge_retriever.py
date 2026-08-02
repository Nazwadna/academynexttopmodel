"""
Configuration-driven knowledge retrieval & answer selection.

Prioritas: Exact QA key → Knowledge SSOT (field/variant ranking) → Intent fallback.
Tidak mengubah TF-IDF+NB, preprocessing, dataset, Context Memory,
Clarification Engine, Multi Intent, atau Response Orchestrator.
"""
from __future__ import annotations

import random
import re
from typing import Any, Dict, List, Optional, Sequence, Tuple

try:
    from rapidfuzz import fuzz as _fuzz
except Exception:  # pragma: no cover
    _fuzz = None


DEFAULT_FALLBACK = (
    "Maaf, informasi mengenai hal tersebut belum tersedia di knowledge base kami. "
    "Silakan hubungi admin Academy Next Top Model melalui WhatsApp untuk bantuan lebih lanjut."
)

DEFAULT_ALIAS_MAP = {
    "training_programs": "programs_info",
    "technical": "technical_support",
    "join_registration_info": "registration_steps",
    "academy_info": "about_academy",
    "benefit_info": "benefits_joining",
    "pricing_info": "pricing_sensitive",
    "program_info": "programs_info",
}


def _normalize(text: str) -> str:
    t = (text or "").lower().strip()
    t = re.sub(r"[^\w\s]", " ", t)
    t = re.sub(r"\s+", " ", t)
    return t.strip()


def _tokens(text: str, min_len: int = 2) -> set:
    return {w for w in _normalize(text).split() if len(w) >= min_len}


def _get_path(data: dict, dotted: str) -> Any:
    cur: Any = data
    for part in (dotted or "").split("."):
        if not isinstance(cur, dict) or part not in cur:
            return None
        cur = cur[part]
    return cur


def get_retrieval_config(academy_data: Optional[dict]) -> dict:
    cfg = (academy_data or {}).get("retrieval") or {}
    return cfg if isinstance(cfg, dict) else {}


def get_fallback_message(academy_data: Optional[dict] = None) -> str:
    cfg = get_retrieval_config(academy_data)
    msg = cfg.get("fallback_message")
    return str(msg).strip() if msg else DEFAULT_FALLBACK


def canonicalize_intent(intent: str, academy_data: Optional[dict] = None) -> str:
    cfg = get_retrieval_config(academy_data)
    aliases = dict(DEFAULT_ALIAS_MAP)
    extra = cfg.get("intent_aliases") or {}
    if isinstance(extra, dict):
        aliases.update({str(k): str(v) for k, v in extra.items()})
    return aliases.get(intent, intent)


def score_query_similarity(query: str, candidate: str) -> float:
    """Skor kemiripan query↔teks (0..1). Token overlap + optional RapidFuzz."""
    q = _normalize(query)
    c = _normalize(candidate)
    if not q or not c:
        return 0.0

    q_tok = _tokens(q)
    c_tok = _tokens(c)
    overlap = 0.0
    if q_tok:
        overlap = len(q_tok & c_tok) / float(len(q_tok))

    # Phrase / substring bonus for specificity
    phrase_bonus = 0.0
    if q in c:
        phrase_bonus = 0.35
    else:
        # longest query n-gram (2-3 words) present in candidate
        words = q.split()
        for n in (3, 2):
            if len(words) < n:
                continue
            for i in range(len(words) - n + 1):
                gram = " ".join(words[i : i + n])
                if gram in c:
                    phrase_bonus = max(phrase_bonus, 0.12 * n)
                    break

    fuzz_score = 0.0
    if _fuzz is not None:
        try:
            fuzz_score = float(_fuzz.token_set_ratio(q, c)) / 100.0
        except Exception:
            fuzz_score = 0.0

    # Weighted blend; phrase boost for specific matches
    base = (0.45 * overlap) + (0.40 * fuzz_score) + (0.15 * min(1.0, phrase_bonus / 0.35))
    return min(1.0, base + phrase_bonus * 0.25)


def list_response_variants(raw: Any, language: str = "id") -> List[str]:
    """Normalisasi responses[intent] → list string."""
    if isinstance(raw, list):
        return [str(x).strip() for x in raw if str(x).strip()]
    if isinstance(raw, dict):
        lang_list = raw.get(language) or raw.get("id") or raw.get("en") or []
        if isinstance(lang_list, list):
            return [str(x).strip() for x in lang_list if str(x).strip()]
    if isinstance(raw, str) and raw.strip():
        return [raw.strip()]
    return []


def get_intent_variants(
    academy_data: Optional[dict],
    intent: str,
    language: str = "id",
) -> List[str]:
    key = canonicalize_intent(intent, academy_data)
    responses = (academy_data or {}).get("responses") or {}
    return list_response_variants(responses.get(key), language=language)


def rank_variants(query: str, variants: Sequence[str]) -> List[Tuple[float, str]]:
    scored = [(score_query_similarity(query, v), v) for v in variants if v]
    scored.sort(key=lambda x: x[0], reverse=True)
    return scored


def select_best_variant(
    query: str,
    variants: Sequence[str],
    *,
    prefer_shortest_on_tie: bool = True,
) -> str:
    """Pilih varian jawaban dengan skor kemiripan tertinggi terhadap query."""
    clean = [str(v).strip() for v in variants if str(v).strip()]
    if not clean:
        return ""
    if not (query or "").strip():
        return clean[0]

    ranked = rank_variants(query, clean)
    if not ranked:
        return clean[0]

    best_score = ranked[0][0]
    top = [v for s, v in ranked if abs(s - best_score) < 1e-6]
    if prefer_shortest_on_tie and len(top) > 1:
        return random.choice(top)
    return top[0]


def _score_field(query: str, field: dict) -> float:
    q = _normalize(query)
    keywords = field.get("keywords") or []
    if not isinstance(keywords, list):
        return 0.0

    hits = 0
    weighted = 0.0
    for kw in keywords:
        k = _normalize(str(kw))
        if not k:
            continue
        if k in q or all(t in q.split() for t in k.split() if len(t) >= 2):
            hits += 1
            # Longer keyword phrases = more specific
            weighted += 0.15 + 0.08 * max(0, len(k.split()) - 1)

    if hits == 0:
        # Soft match: hanya jika field mengizinkan dan ada overlap token bermakna (>=4 huruf)
        # Hindari false positive (mis. "syarat" menembak field "biaya syarat" → pricing).
        if field.get("strict_keywords") or field.get("redirect_intent") in {
            "pricing_sensitive",
            "pricing_info",
        }:
            return 0.0
        bag = " ".join(str(k) for k in keywords)
        soft = score_query_similarity(query, bag)
        q_tok = {t for t in _tokens(q) if len(t) >= 4}
        bag_tok = {t for t in _tokens(bag) if len(t) >= 4}
        meaningful = q_tok & bag_tok
        # Token generik yang sering salah tembak antar field
        generic = {"syarat", "daftar", "academy", "model", "info", "cara", "apa", "saja"}
        meaningful -= generic
        if soft < 0.55 or len(meaningful) < 1:
            return 0.0
        weighted = soft * 0.35

    specificity = float(field.get("specificity", 1.0))
    return weighted * specificity


def _format_structured_value(value: Any, template: Optional[str], label: str = "") -> str:
    if value is None:
        return ""
    name = label
    if isinstance(value, list):
        rendered = "\n".join(f"- {str(v).strip()}" for v in value if str(v).strip())
    elif isinstance(value, dict):
        name = str(value.get("name") or label or "")
        bits = []
        if value.get("description"):
            bits.append(str(value["description"]))
        for k in ("level", "duration", "focus"):
            if value.get(k):
                bits.append(f"{k.replace('_', ' ').title()}: {value[k]}")
        rendered = " | ".join(bits) if bits else name
    else:
        rendered = str(value).strip()

    if not rendered:
        return ""
    if template:
        try:
            return template.format(value=rendered, name=name or rendered)
        except Exception:
            return template.replace("{value}", rendered).replace("{name}", name or rendered)
    if name and name not in rendered:
        return f"{name}: {rendered}"
    return rendered


def _extract_numbered_snippets(text: str, query: str, min_score: float) -> str:
    """Ambil hanya baris/poin relevan dari jawaban multi-bagian."""
    if not text:
        return ""
    lines = [ln.strip() for ln in text.splitlines() if ln.strip()]
    if len(lines) <= 1:
        return text

    # Keep intro line if present, then only high-scoring bullets/steps
    intro = ""
    body = lines
    if lines and not re.match(r"^(\d+[\.\)]|-)\s+", lines[0]):
        intro = lines[0]
        body = lines[1:]

    scored = []
    for ln in body:
        s = score_query_similarity(query, ln)
        # Also boost if query tokens hit this line
        if s >= min_score:
            scored.append((s, ln))

    if not scored:
        return text  # tidak cukup sinyal → jangan potong sembarangan

    scored.sort(key=lambda x: x[0], reverse=True)
    # Ambil top snippets yang jauh di atas rata-rata / threshold
    top_score = scored[0][0]
    selected = [ln for s, ln in scored if s >= max(min_score, top_score * 0.75)]
    if not selected:
        selected = [scored[0][1]]

    # Preserve original order
    order = {ln: i for i, ln in enumerate(body)}
    selected.sort(key=lambda ln: order.get(ln, 0))
    if intro:
        return intro + "\n" + "\n".join(selected)
    return "\n".join(selected)


def resolve_field_answer(
    query: str,
    field: dict,
    academy_data: dict,
    language: str = "id",
) -> str:
    """Susun jawaban dari satu knowledge field (config-driven)."""
    # Redirect ke intent lain yang lebih spesifik
    redirect = field.get("redirect_intent")
    if redirect:
        variants = get_intent_variants(academy_data, str(redirect), language=language)
        if variants:
            ans = select_best_variant(query, variants)
            if field.get("extract_relevant_lines"):
                min_score = float(
                    get_retrieval_config(academy_data).get("min_snippet_score", 0.22)
                )
                return _extract_numbered_snippets(ans, query, min_score)
            return ans

    # Structured JSON path
    source = field.get("source")
    if source:
        value = _get_path(academy_data, str(source))
        label = field.get("label") or ""
        template = field.get("template")
        rendered = _format_structured_value(value, template, label=str(label))
        if rendered:
            return rendered

    # Ambil dari response_key / intent variants + optional filter keywords
    response_key = field.get("response_key") or field.get("intent")
    variants = get_intent_variants(academy_data, str(response_key), language=language) if response_key else []

    filter_kws = field.get("variant_filter_keywords") or []
    if filter_kws and variants:
        filtered = []
        for v in variants:
            vn = _normalize(v)
            if any(_normalize(str(k)) in vn for k in filter_kws):
                filtered.append(v)
        if filtered:
            variants = filtered

    if variants:
        ans = select_best_variant(query, variants)
        if field.get("extract_relevant_lines"):
            min_score = float(
                get_retrieval_config(academy_data).get("min_snippet_score", 0.22)
            )
            return _extract_numbered_snippets(ans, query, min_score)
        return ans

    static = field.get("answer")
    return str(static).strip() if static else ""


def select_knowledge_answer(
    query: str,
    intent: str,
    academy_data: Optional[dict],
    *,
    language: str = "id",
    allow_general_fallback: bool = True,
) -> str:
    """
    Pilih jawaban SSOT paling spesifik untuk (query, intent).
    1) Rank knowledge_fields by keyword specificity
    2) Else rank response variants by query similarity
    3) Else official fallback
    """
    data = academy_data or {}
    cfg = get_retrieval_config(data)
    canonical = canonicalize_intent(intent, data)
    min_field = float(cfg.get("min_field_score", 0.18))
    prefer_specific = bool(cfg.get("prefer_specific_over_general", True))

    fields_cfg = cfg.get("knowledge_fields") or {}
    fields = fields_cfg.get(canonical) if isinstance(fields_cfg, dict) else None

    if isinstance(fields, list) and fields:
        ranked_fields: List[Tuple[float, dict]] = []
        for field in fields:
            if not isinstance(field, dict):
                continue
            if field.get("general"):
                # general = fallback field, score lightly unless nothing else matches
                score = _score_field(query, field) * 0.35
            else:
                score = _score_field(query, field)
            ranked_fields.append((score, field))

        ranked_fields.sort(key=lambda x: x[0], reverse=True)

        if ranked_fields and ranked_fields[0][0] >= min_field:
            best_score, best_field = ranked_fields[0]
            # Jika ada field spesifik (non-general) yang menang, pakai itu
            if prefer_specific and not best_field.get("general"):
                answer = resolve_field_answer(query, best_field, data, language=language)
                if answer:
                    return answer
            elif best_field.get("general") and allow_general_fallback:
                answer = resolve_field_answer(query, best_field, data, language=language)
                if answer:
                    return answer

        # Tidak ada field spesifik cukup kuat → coba field general eksplisit
        for score, field in ranked_fields:
            if field.get("general"):
                answer = resolve_field_answer(query, field, data, language=language)
                if answer:
                    return answer

    variants = get_intent_variants(data, canonical, language=language)
    if variants:
        selected = select_best_variant(query, variants)
        # Opsional: potong baris tidak relevan pada jawaban multi-bagian
        extract_intents = cfg.get("extract_lines_intents") or []
        if canonical in extract_intents and query:
            min_score = float(cfg.get("min_snippet_score", 0.22))
            trimmed = _extract_numbered_snippets(selected, query, min_score)
            return trimmed or selected
        return selected

    if allow_general_fallback:
        return get_fallback_message(data)
    return ""


def pick_academy_response(
    key: str,
    academy_data: Optional[dict],
    query: Optional[str] = None,
    language: str = "id",
) -> str:
    """Drop-in replacement untuk _pick_academy_response dengan ranking query-aware."""
    if query:
        return select_knowledge_answer(query, key, academy_data, language=language)
    variants = get_intent_variants(academy_data, key, language=language)
    return variants[0] if variants else ""


def rank_topic_candidates(
    query: str,
    matched_intents: Sequence[str],
    academy_data: Optional[dict],
) -> List[Tuple[float, str]]:
    """
    Ranking intent topik yang sudah match checker.
    Skor = specificity config + keyword field hit + soft similarity ke keywords SSOT.
    """
    cfg = get_retrieval_config(academy_data)
    specificity_map = cfg.get("intent_specificity") or {}
    keywords_root = (academy_data or {}).get("keywords") or {}
    scored: List[Tuple[float, str]] = []

    for intent in matched_intents:
        canonical = canonicalize_intent(intent, academy_data)
        base = float(specificity_map.get(canonical, specificity_map.get(intent, 1.0)))

        # Field hit boost
        fields = (cfg.get("knowledge_fields") or {}).get(canonical) or []
        field_boost = 0.0
        if isinstance(fields, list):
            for field in fields:
                if isinstance(field, dict) and not field.get("general"):
                    field_boost = max(field_boost, _score_field(query, field))

        # Keyword bag similarity
        raw_kw = keywords_root.get(canonical) or keywords_root.get(intent) or []
        if isinstance(raw_kw, dict):
            raw_kw = raw_kw.get("id") or raw_kw.get("en") or []
        kw_text = " ".join(str(k) for k in raw_kw) if isinstance(raw_kw, list) else ""
        kw_sim = score_query_similarity(query, kw_text) if kw_text else 0.0

        score = base + (field_boost * 3.0) + (kw_sim * 2.0)
        scored.append((score, intent))

    scored.sort(key=lambda x: x[0], reverse=True)
    return scored
