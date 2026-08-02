# Academy Next Top Model (Academy NTM) — Website & Hybrid Chatbot

Sistem website resmi **Academy Next Top Model** berbasis **Laravel (PHP)** yang terintegrasi dengan **AI Chatbot Hybrid Multi-Layer** berbasis **Python Flask**. Chatbot menangani pertanyaan statis, dinamis (data model dari MySQL), multi-intent, kontekstual (memory/anaphora), dan topik sensitif (handoff WhatsApp).

> **Catatan sidang:** file utama chatbot `chatbot_hybrid/app.py` sengaja **tidak dipecah** agar perilaku runtime stabil untuk demonstrasi skripsi.

---

## Arsitektur Singkat

```
Browser (welcome widget / admin chatbot)
        │
        ▼
Laravel (port 8000)  ──proxy──►  Flask NLP Service (port 5000)
        │                              │
        │                              ├─ Reference Resolution + Typo Correction
        │                              ├─ Multi-Intent → Response Orchestrator
        │                              ├─ Exact QA + Knowledge SSOT (academy_enhanced.json)
        │                              ├─ RapidFuzz (fuzzy Exact QA)
        │                              ├─ Sensitive → WhatsApp handoff
        │                              ├─ Dynamic Query (API Laravel models)
        │                              ├─ TF-IDF + Multinomial NB
        │                              ├─ NLU: Refinement + Clarification + Dynamic Response
        │                              └─ Fallback / FAQ / LLM (opsional)
        │
        ├─ MySQL (academyntm) — data website + tabel jobs (queue)
        └─ Queue Worker (chatbot-training) ──HTTP──► Flask /train/naive-bayes
```

| Komponen | Peran |
|----------|--------|
| **Laravel** | Website, API gateway chatbot, admin training, API data model |
| **Flask (`chatbot_hybrid/app.py`)** | Otak NLP / runtime chatbot |
| **MySQL** | Data talent/model + antrian job (`jobs`, `failed_jobs`) |
| **Queue Worker** | Memproses training secara async agar UI admin tidak timeout |

---

## Apakah Harus Ditaruh di `htdocs`?

**Tidak wajib.**

Project ini **paling aman dan paling mudah ditest** dengan:

```text
php artisan serve --host=127.0.0.1 --port=8000
```

Folder boleh berada di mana saja, contoh:

- `D:\academyntm2-finale`
- `C:\Users\...\Desktop\academyntm2-finale`

**Tidak perlu** dipindah ke `C:\xampp\htdocs` jika memakai `artisan serve`.

| Cara jalan | Perlu `htdocs`? | Catatan |
|------------|-----------------|---------|
| `php artisan serve` (disarankan) | **Tidak** | URL: `http://127.0.0.1:8000` |
| Apache/XAMPP document root | **Ya / opsional** | Folder harus di bawah `htdocs` (atau VirtualHost), URL biasanya `http://localhost/academyntm2-finale/public` |

> Yang **wajib** dari XAMPP biasanya hanya **MySQL** (dan PHP CLI). Apache tidak wajib jika pakai `artisan serve`.

---

## Untuk Teman yang Menerima Folder Ini (Checklist Tes Aman)

Ikuti urutan di bawah supaya testing aman dan tidak bentrok (double Flask, DB kosong, CSRF, dll.).

### 1) Persiapan sekali (setup mesin teman)

1. Install **XAMPP** (PHP 8.2+ + MySQL) dan **Python 3**.
2. Install **Composer** (jika `vendor/` belum ada → jalankan `composer install` di root project).
3. Copy file environment:
   - Pastikan ada file `.env` di root Laravel (minta ke pemilik project jika tidak ikut terkirim).
   - Jangan commit / jangan share sembarangan isi secret (`APP_KEY`, password DB, `OPENAI_API_KEY`).
4. Buat database MySQL: `academyntm`, lalu import `academyntm.sql` (jika ada).
5. Di folder project:
   ```bash
   php artisan key:generate
   php artisan migrate
   ```
   Pastikan tabel queue ada (`jobs`, `failed_jobs`, dll.).
6. Install Python deps:
   ```bash
   cd chatbot_hybrid
   py -3 -m pip install -r requirements.txt
   # atau: python -m pip install -r requirements.txt
   ```

### 2) Isi `.env` yang wajib (Laravel)

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=academyntm
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
CHATBOT_API_URL=http://127.0.0.1:5000
LARAVEL_API_BASE=http://127.0.0.1:8000/api/chatbot
CHATBOT_DEBUG=false
```

> `LARAVEL_API_BASE` penting agar Flask Layer 4 bisa memanggil API model Laravel.  
> `CHATBOT_DEBUG=false` agar log terminal Flask tidak berisik.

### 3) Menjalankan (3 proses, jangan dobel)

**Penting:**

- Hanya **1 Flask** di port `5000`. Kalau Flask dijalankan 2x, hasil `/health` bisa salah.
- File Flask adalah `chatbot_hybrid/app.py` — **bukan** `app.py` di root project.

1. Nyalakan **MySQL** di XAMPP.
2. Matikan proses lama (jika ada):
   - Jalankan `stop_all.bat`, **atau**
   - Pastikan port `5000` dan `8000` kosong.
3. Jalankan salah satu cara:

**Cara cepat**
- `start_all.bat` → Flask + Laravel
- `start_queue_chatbot.bat` → Queue Worker (wajib jika mau tes training)

**Cara manual (3 terminal)**
```bash
# Terminal 1 — Flask (WAJIB masuk folder chatbot_hybrid dulu)
cd chatbot_hybrid
python app.py
# atau: py -3 app.py

# Terminal 2 — Laravel (dari root project)
cd ..
php artisan serve --host=127.0.0.1 --port=8000

# Terminal 3 — Queue Worker (dari root project)
php artisan queue:listen --queue=chatbot-training --tries=1 --timeout=320
```

### 4) Cek aman sebelum testing fitur

| Cek | URL / perintah | Hasil diharapkan |
|-----|----------------|------------------|
| Flask hidup | `http://127.0.0.1:5000/health` | `status: ok`, `nb_model_loaded: true`, `modules.nlu_engine: true` |
| Laravel proxy | `http://127.0.0.1:8000/chatbot/health` | status sehat / healthy |
| Website | `http://127.0.0.1:8000` | Landing + widget chat |
| API model | `http://127.0.0.1:8000/api/chatbot/models` | daftar model / success |
| Hanya 1 Flask | `netstat -ano \| findstr :5000` | Hanya **satu** baris `LISTENING` |

> Membuka `http://127.0.0.1:5000/` (root) akan **404**. Itu normal — Flask tidak punya halaman `/`. Pakai `/health`.

### 5) Skenario tes yang disarankan

1. Chat biasa di widget / `http://127.0.0.1:8000/chatbot`
2. Greeting: `Halo` / `Selamat malam` → sapaan time-aware
3. Typo: `jadwaal` / `uplod foto` → tetap ke knowledge yang benar
4. Pertanyaan teknis: `otp saya tidak masuk` → intent support / handoff (bukan cara daftar)
5. Multi-intent: syarat + jadwal + lokasi dalam satu pesan
6. Admin chatbot: `/admin/chatbot-management` → analytics + retrain
7. Retrain:
   - Queue Worker harus aktif
   - Response Laravel biasanya **202** (queued)
   - Kalau training masih jalan, request kedua → **409** (anti-overlap)

### 6) Aturan aman saat sharing folder

- **Jangan** share `.env` berisi password/API key ke chat publik.
- Teman buat `.env` sendiri dari template di README ini.
- Jangan commit `vendor/`, `node_modules/`, `__pycache__/`, session, atau file secret.
- Untuk demo lokal: `APP_DEBUG=true` boleh.
- Jika nanti di-hosting publik: set `APP_DEBUG=false`, `APP_ENV=production`, dan jangan expose Flask mentah ke internet jika tidak perlu.

---

## Struktur Direktori (ringkas)

```
academyntm2-finale/
├── app/
│   ├── Http/Controllers/
│   │   ├── ChatbotController.php          ← Proxy chat/health/train + admin analytics/intents
│   │   └── ChatbotModelController.php     ← API daftar/cari model (Layer 4)
│   └── Jobs/
│       └── ProcessChatbotTraining.php     ← Job queue → POST Flask train
│
├── bootstrap/app.php                      ← CSRF except chatbot/* + load api.php
├── routes/
│   ├── web.php                            ← /chatbot/*, /admin/chatbot-*
│   └── api.php                            ← /api/chatbot/models*
│
├── resources/views/
│   ├── welcome.blade.php                  ← Landing + @include chatbot.widget_fixed
│   ├── admin_chatbot_management.blade.php ← Dashboard chatbot + training (aktif)
│   ├── admin_intents.blade.php            ← CRUD intent (faq.json)
│   └── chatbot/
│       ├── index.blade.php                ← Halaman chat penuh /chatbot
│       └── widget_fixed.blade.php         ← Floating widget (AKTIF di welcome)
│
├── chatbot_hybrid/                        ← Flask NLP Service  ← JALANKAN app.py DARI SINI
│   ├── app.py                             ← Runtime utama (monolith)
│   ├── requirements.txt
│   ├── start_chatbot.bat                  ← Start Flask dari folder ini
│   ├── modules/
│   │   ├── nlu_engine.py                  ← Memory, clarification, time greeting, dynamic response
│   │   ├── typo_corrector.py              ← Koreksi typo domain (RapidFuzz token)
│   │   ├── response_orchestrator.py       ← Susun jawaban multi-intent
│   │   ├── nb_trainer.py                  ← Latih TF-IDF + Naive Bayes
│   │   ├── data_augmentation.py
│   │   └── chat_logger.py
│   ├── data/                              ← faq.json, academy_enhanced.json, …
│   ├── nlp_pipeline/                      ← CRISP-DM (EDA → prepare → model → eval)
│   │   ├── dataset_prepared.csv
│   │   ├── nb_model.pkl                   ← Model aktif inference
│   │   └── tfidf_vectorizer.pkl
│   └── logs_db/                           ← SQLite log/analytics (opsional)
│
├── start_all.bat                          ← Flask + Laravel sekaligus
├── start_queue_chatbot.bat                ← Queue worker chatbot-training
├── stop_all.bat                           ← Matikan proses di port 5000/8000
├── academyntm.sql                         ← Dump DB utama (nama DB: academyntm)
└── README.md
```

---

## Persyaratan

- **PHP ^8.2** (disarankan XAMPP: `C:\xampp\php\php.exe`)
- **MySQL** (XAMPP) — database `academyntm`
- **Composer** + dependency Laravel sudah terpasang (`composer install`)
- **Python 3.x** + paket di `chatbot_hybrid/requirements.txt`
- Migrasi tabel queue sudah jalan (`jobs`, `failed_jobs`)

### Variabel lingkungan penting

**Laravel `.env`:**
```env
CHATBOT_API_URL=http://127.0.0.1:5000
QUEUE_CONNECTION=database
LARAVEL_API_BASE=http://127.0.0.1:8000/api/chatbot
CHATBOT_DEBUG=false
```

**Flask `chatbot_hybrid/.env` (opsional):**
```env
OPENAI_API_KEY=...                 # fallback LLM (opsional)
LARAVEL_API_BASE=http://127.0.0.1:8000/api/chatbot
CHATBOT_DEBUG=false
```

Jika Laravel dijalankan lewat `php artisan serve`, set `LARAVEL_API_BASE` ke port **8000** agar Layer 4 (query model) bisa mengakses API.

---

## Cara Menjalankan

### Opsi A — Sekali klik (disarankan demo)

1. Nyalakan **MySQL** di XAMPP.
2. Jalankan `start_all.bat` → Flask `:5000` + Laravel `:8000`.
3. (Untuk training admin) jalankan juga `start_queue_chatbot.bat` di jendela terpisah.
4. Buka `http://127.0.0.1:8000`.

Matikan: `stop_all.bat` (atau Ctrl+C di tiap jendela).

### Opsi B — Manual (3 terminal)

**Terminal 1 — Flask**

```bash
cd chatbot_hybrid
python app.py
# atau: py -3 app.py
# Health: http://127.0.0.1:5000/health
```

> **Salah:** menjalankan `python app.py` dari root `academyntm2-finale/`  
> → error `can't open file '...\\app.py'` karena file ada di `chatbot_hybrid/app.py`.

**Terminal 2 — Laravel**

```bash
# dari root project
php artisan serve --host=127.0.0.1 --port=8000
# Website: http://127.0.0.1:8000
# Chat:    http://127.0.0.1:8000/chatbot
```

**Terminal 3 — Queue Worker (wajib jika training dari admin)**

```bash
php artisan queue:listen --queue=chatbot-training --tries=1 --timeout=320
```

Atau klik `start_queue_chatbot.bat`.

---

## Endpoint & Route

### Laravel (gateway)

| Method | URL | Fungsi |
|--------|-----|--------|
| GET | `/chatbot` | Halaman chat penuh |
| POST | `/chatbot/chat` | Proxy pesan → Flask `/chat` (`message` max **500**) |
| POST | `/chatbot/analyze` | Proxy analisis intent |
| GET | `/chatbot/health` | Status Flask + fitur |
| GET | `/chatbot/intents` | Daftar intent |
| POST | `/chatbot/train` | **Dispatch** job training ke queue (**202**; **409** jika overlap) |
| GET | `/admin/chatbot-management` | UI admin chatbot + training |
| GET | `/admin/model-training` | Redirect → `/admin/chatbot-management` |
| GET | `/admin/intents` | Kelola intent (`faq.json`) |
| GET | `/admin/chatbot-evaluation-report` | Laporan evaluasi NB |
| GET | `/api/chatbot/models` | Daftar model (Layer 4) |
| GET | `/api/chatbot/models/search` | Cari model |
| GET | `/api/chatbot/models/count` | Hitung model (smoke/docs) |

CSRF: path `chatbot/*` dikecualikan di `bootstrap/app.php` agar proxy/API/training tidak kena 419.

### Flask (NLP Service)

| Method | URL | Fungsi |
|--------|-----|--------|
| POST | `/chat` | Pipeline hybrid multi-layer |
| POST | `/analyze` | Analisis intent/entity |
| GET | `/intents` | List intent |
| GET | `/health` | Health check + status model + modul NLU |
| POST | `/train/naive-bayes` | Latih ulang TF-IDF + Multinomial NB |
| POST | `/train` | Train TensorFlow (jika ada `training_data`) / default NB |

Contoh cek modul NLU di `/health`:

```json
"modules": {
  "nlu_engine": true,
  "clarification": true,
  "typo_corrector": true,
  "response_orchestrator": true,
  "rapidfuzz": true
}
```

---

## Laravel Queue Worker — Alur Training

Digunakan saat admin menekan training di `/admin/chatbot-management` (atau `POST /chatbot/train`).

### Urutan implementasi

1. **Dispatch** — `ChatbotController::train()` memanggil:
   ```php
   ProcessChatbotTraining::dispatch($payload);
   ```
   Job masuk queue bernama **`chatbot-training`**. Response HTTP **202** (queued).

2. **Anti-overlap** — jika training masih aktif (cache lock / unique job), request berikutnya ditolak **409**.

3. **Job class** — `app/Jobs/ProcessChatbotTraining.php`
   - Constructor: `$this->onQueue('chatbot-training');`
   - `ShouldBeUnique` + middleware `WithoutOverlapping`
   - `handle()` mengirim HTTP POST ke Flask (`Http::timeout(300)`):
     - default `mode=naive_bayes` → `POST {CHATBOT_API_URL}/train/naive-bayes`
     - `mode=tensorflow` + `training_data` → `POST {CHATBOT_API_URL}/train`
   - Jika Flask offline: `ConnectionException` → `Log::error` + job `fail`

4. **Worker** membaca antrian:
   ```bash
   php artisan queue:listen --queue=chatbot-training --tries=1 --timeout=320
   ```

5. Flask selesai latih → menyimpan `nb_model.pkl` / `tfidf_vectorizer.pkl` dan **hot-reload** model di memori.

### Syarat training berhasil

- MySQL aktif + `QUEUE_CONNECTION=database`
- Tabel `jobs` / `failed_jobs` ada
- Flask `:5000` hidup (**hanya 1 proses**)
- Queue worker `chatbot-training` hidup

> Retrain pada dataset yang sama hanya **menyegarkan model**. Agar chatbot “lebih pintar”, tambahkan data latih baru (FAQ/CSV via admin), lalu train lagi.

---

## Pipeline Chatbot (Runtime Hybrid)

Urutan di `POST /chat` (inti aktif di `chatbot_hybrid/app.py`):

1. **Reference Resolution** — anaphora / follow-up dari Context Memory  
2. **Typo Correction** — koreksi token domain (sebelum Exact QA / Knowledge)  
3. **Multi-Intent** — jika ≥2 topik → jawaban digabung lewat **Response Orchestrator**  
4. **Exact QA** — cocok persis alias/kamus (greeting memakai Time-Aware + Anti-Repetition)  
5. **Technical support keywords** — OTP/login/password → handoff support  
6. **Knowledge Retrieval (SSOT)** — `academy_enhanced.json` (daftar, syarat, foto, jadwal, dll.)  
7. **RapidFuzz** — fuzzy Exact QA (similarity + token overlap ketat)  
8. **Sensitive** — harga/kontrak/booking → handoff WhatsApp  
9. **Dynamic Query** — status/jumlah/daftar model via API Laravel (realtime DB)  
10. **TF-IDF + Multinomial Naive Bayes** — klasifikasi intent  
11. **NLU Engine** — Refinement Rule Engine → Clarification (zona ambigu) → Dynamic Response Generator  
12. Fallback FAQ / LLM (jika dikonfigurasi)

Knowledge utama: `chatbot_hybrid/data/academy_enhanced.json` (Single Source of Truth).  
Model aktif inference: `nlp_pipeline/nb_model.pkl` + `tfidf_vectorizer.pkl`.

---

## Pipeline ML / CRISP-DM (dokumentasi Bab 4)

Folder: `chatbot_hybrid/nlp_pipeline/`

```bash
cd chatbot_hybrid/nlp_pipeline
python data_understanding.py   # EDA
python data_preparation.py     # Preprocess + augmentasi → dataset_prepared.csv
python modeling.py             # TF-IDF + Multinomial NB → .pkl
python evaluation.py           # Metrik, confusion matrix, CV
```

Hasil referensi evaluasi (dari pipeline project): akurasi uji ~**88.89%**, 5-Fold CV ~**86.81%**.

Training ulang **runtime** lebih disarankan lewat **admin + queue** (`/train/naive-bayes`), bukan hanya menjalankan `modeling.py` manual.

---

## Troubleshooting

| Gejala | Cek |
|--------|-----|
| `can't open file '...\\app.py'` | Anda di root project. Jalankan: `cd chatbot_hybrid` lalu `python app.py` |
| Widget bilang offline | Flask belum jalan di `:5000` → `cd chatbot_hybrid` + `python app.py` / `start_all.bat` |
| `/health` aneh / modul NLU false | Ada **2 Flask** di port 5000, atau server belum di-restart setelah update → `stop_all.bat` lalu start ulang 1 proses |
| `http://127.0.0.1:5000/` Not Found | Normal — pakai `/health`, bukan root `/` |
| Training admin “mengantung” | Queue worker belum jalan + MySQL + `QUEUE_CONNECTION=database` |
| HTTP 419 di `/chatbot/train` | Pastikan `bootstrap/app.php` masih except `chatbot/*` |
| HTTP 409 di `/chatbot/train` | Training masih berjalan (anti-overlap) — tunggu selesai |
| Layer 4 model kosong | Set `LARAVEL_API_BASE=http://127.0.0.1:8000/api/chatbot` |
| OTP dijawab cara daftar | Pastikan deteksi technical support + RapidFuzz ketat masih aktif |
| Harus pindah ke `htdocs`? | **Tidak**, jika pakai `artisan serve` |

---

## Hosting skala kecil (opsional)

Untuk hosting kecil (bukan enterprise), project ini **bisa dipakai**, dengan catatan:

- `APP_DEBUG=false`, `APP_ENV=production`
- Jangan expose Flask mentah ke publik jika cukup di-proxy lewat Laravel
- Pastikan Queue Worker + Flask selalu jalan (service/supervisor)
- Jaga secret di `.env` (DB password, OpenAI key)

---

## Lisensi / Penggunaan

Proyek akademik Academy Next Top Model — dokumentasi skripsi & demonstrasi sistem website + chatbot hybrid.
