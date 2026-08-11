"""Peta normalisasi kata informal chat sehari-hari -> bentuk formal.

SATU sumber kebenaran dipakai BERSAMA oleh training (nlp_pipeline/
data_preparation.py, nlp_pipeline/evaluation.py) dan live inference
(app.py normalize_informal()) -- supaya tidak ada kata yang "dikenal"
model saat belajar tapi "asing" saat menjawab user sungguhan (atau
sebaliknya). Pola sama dengan SCHEDULE_CONTEXT_KEYWORDS di
modules/nlu_engine.py (satu konstanta, dipakai ulang, tidak drift).

WAJIB diterapkan word-boundary (\\bkata\\b) oleh pemanggil, BUKAN
substring polos -- substring polos merusak kata seperti "remaja"
(mengandung substring "aja") dan "harga" (mengandung substring "ga").

Sengaja HANYA berisi normalisasi ejaan informal -> baku (mis. "gimana"
-> "bagaimana", "trims" -> "terima kasih"). TIDAK berisi collapsing
sinonim konten lintas-topik (mis. "alamat" -> "lokasi", "gabung" ->
"daftar") -- itu tetap training-only di data_preparation.py (lihat
CONTENT_SYNONYM_MAP di sana), karena banyak checker is_*_query() di
app.py bergantung pada kata asli seperti "alamat"/"gabung" tersebut
sebagai keyword-nya sendiri; menyamakan itu ke live pipeline butuh
audit dampak tersendiri di luar scope unifikasi ini.

"buat"/"bwt" SENGAJA TIDAK dimasukkan -- ambigu antara "untuk" dan
"membuat" (beda dengan "utk" yang murni singkatan "untuk" saja);
memetakannya paksa akan merusak keyword existing seperti "cara buat
akun" di is_registration_steps_query() (app.py). Tetap training-only
di CONTENT_SYNONYM_MAP data_preparation.py, TIDAK di sini.
"""

INFORMAL_WORD_MAP = {
    "aja": "saja",
    "ga": "tidak", "gak": "tidak", "nggak": "tidak", "ngga": "tidak",
    "gk": "tidak", "kaga": "tidak", "kga": "tidak", "kagak": "tidak", "gatau": "tidak",
    "gimana": "bagaimana", "gmn": "bagaimana", "gmna": "bagaimana", "mna": "bagaimana", "gimene": "bagaimana",
    "dmn": "dimana",
    "yg": "yang",
    "sdh": "sudah", "udah": "sudah", "udh": "sudah", "udha": "sudah",
    "blm": "belum", "belom": "belum",
    "bs": "bisa", "bsa": "bisa", "bisaa": "bisa",
    "pengen": "ingin", "pingin": "ingin", "pengin": "ingin", "mw": "ingin", "pengan": "ingin", "berniat": "ingin",
    "jd": "jadi",
    "krn": "karena", "karnaa": "karena",
    "hrs": "harus",
    "utk": "untuk",
    "sy": "saya", "gua": "saya", "gue": "saya", "gw": "saya", "ak": "aku",
    "lu": "kamu", "elu": "kamu", "elo": "kamu",
    # "km"/"kmu" -> "kamu": AMAN UNTUK SEKARANG (tidak ada fitur di codebase
    # ini yang memakai "km" sebagai satuan jarak/kilometer), BUKAN aman
    # selamanya -- kalau nanti ada fitur jarak/lokasi berbasis km, mapping
    # ini WAJIB ditinjau ulang dulu. Konsisten dengan precedent SYNONYM_MAP
    # di nlu_engine.py yang sudah lebih dulu memetakan "km":"kamu".
    "km": "kamu", "kmu": "kamu",
    "byr": "bayar",
    "brp": "berapa", "brapa": "berapa", "berapaan": "berapa",
    "kpn": "kapan",
    "min": "admin", "admn": "admin", "mimin": "admin",
    "dftr": "daftar", "daftr": "daftar", "daftaar": "daftar", "dftar": "daftar", "daftarr": "daftar",
    "byaya": "biaya", "bya": "biaya", "biayanya": "biaya",
    "lok": "lokasi", "lksi": "lokasi", "lokasiny": "lokasi",
    "jdwl": "jadwal", "jdwal": "jadwal", "jadwalnnya": "jadwal",
    "pgm": "program", "prgrm": "program", "progrm": "program",
    "mohon": "tolong", "mhon": "tolong", "tlg": "tolong", "tlgong": "tolong", "plis": "tolong", "pls": "tolong", "please": "tolong",
    "bgt": "sangat", "banget": "sangat", "bnget": "sangat",
    "skrg": "sekarang",
    "fastres": "cepat", "fastresp": "cepat",
    "thx": "terima kasih", "thanks": "terima kasih", "makasih": "terima kasih", "makasi": "terima kasih",
    "mksih": "terima kasih", "thankyou": "terima kasih", "tq": "terima kasih",
    "nuhun": "terima kasih", "suwun": "terima kasih", "maturnuwun": "terima kasih", "trims": "terima kasih",
    "tengkyu": "terima kasih",
}
