<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FAQ Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .header-right { width: calc(100% - 3.5rem); }
        .sidebar:hover { width: 16rem; }
        @media only screen and (min-width: 768px) { .header-right { width: calc(100% - 16rem); } }
        :root { --c-main: #121212; --c-sec:#1e1e1e; }
        .bg-gray-900 { background-color: var(--c-main) !important; }
        .bg-gray-800, .bg-gray-700 { background-color: var(--c-sec) !important; }
    </style>
</head>
<body class="bg-gray-900 text-white" x-data="intentAdmin()" x-init="init()">
<div class="min-h-screen flex flex-col">
    <header class="fixed w-full flex items-center justify-between h-14 bg-gray-800 text-white z-10">
        <div class="flex items-center pl-3 w-14 md:w-64 h-14">
            <img class="w-8 h-8 md:w-10 md:h-10 mr-2 rounded-full" src="{{ asset('img/logo.jpg') }}" alt="Logo" />
            <span class="hidden md:block font-bold text-lg">FAQ Management</span>
        </div>
        <div class="flex items-center header-right pr-4">
            <a href="/admin/chatbot-management" class="flex items-center hover:text-gray-300 text-white">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Chatbot Dashboard
            </a>
        </div>
    </header>

    @include('partials.admin_sidebar_chatbot')

    <main class="flex-1 ml-14 mt-14 md:ml-64 p-6 bg-gray-900 min-h-screen">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">FAQ Management</h1>
                <p class="text-gray-400">Kelola intent chatbot langsung dari `faq.json`, lalu retrain model agar perubahan ikut dipakai chatbot.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button @click="openCreate()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
                    <i class="fas fa-plus mr-2"></i> Tambah Intent
                </button>
                <button @click="retrain()" :disabled="trainingBusy"
                    class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-md flex items-center">
                    <i class="fas fa-rotate mr-2" :class="trainingBusy ? 'fa-spin' : ''"></i> Simpan & Retrain
                </button>
            </div>
        </div>

        <div x-show="flash.message" class="mb-5 rounded-lg px-4 py-3 text-sm"
             :class="flash.type === 'error' ? 'bg-red-900 text-red-100' : 'bg-green-900 text-green-100'">
            <span x-text="flash.message"></span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-800 shadow-lg rounded-md p-4">
                <p class="text-gray-400 text-sm">Total Intents</p>
                <p class="text-3xl font-bold mt-2" x-text="stats.total_intents">0</p>
            </div>
            <div class="bg-gray-800 shadow-lg rounded-md p-4">
                <p class="text-gray-400 text-sm">Total Patterns</p>
                <p class="text-3xl font-bold mt-2" x-text="stats.total_patterns">0</p>
            </div>
            <div class="bg-gray-800 shadow-lg rounded-md p-4">
                <p class="text-gray-400 text-sm">Total Responses</p>
                <p class="text-3xl font-bold mt-2" x-text="stats.total_responses">0</p>
            </div>
            <div class="bg-gray-800 shadow-lg rounded-md p-4">
                <p class="text-gray-400 text-sm">Model Accuracy</p>
                <p class="text-3xl font-bold mt-2" x-text="formatPercent(stats.accuracy)">0%</p>
            </div>
        </div>

        <div class="bg-gray-800 shadow-lg rounded-md p-4 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-white">Cara pakai yang mudah</h2>
                    <p class="text-sm text-gray-400 mt-1">1) Tambah/edit intent, 2) simpan perubahan, 3) klik retrain supaya model Flask ikut diperbarui.</p>
                </div>
                <div class="w-full lg:w-80">
                    <input x-model="search" type="text" placeholder="Cari intent atau pola pertanyaan..."
                        class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="bg-gray-800 shadow-lg rounded-md p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full bg-gray-800">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Intent</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Deskripsi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Pattern</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Respons</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <template x-for="item in filteredItems()" :key="item.intent">
                            <tr class="hover:bg-gray-700/70">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-white" x-text="item.intent"></div>
                                    <span class="inline-block mt-2 px-2 py-1 text-xs rounded-full bg-green-600 text-white" x-text="item.status"></span>
                                </td>
                                <td class="px-4 py-4 text-gray-300 align-top" x-text="item.description"></td>
                                <td class="px-4 py-4 text-gray-300 align-top">
                                    <div class="space-y-1">
                                        <template x-for="pattern in item.patterns.slice(0, 3)" :key="pattern">
                                            <div class="text-xs bg-gray-900 rounded px-2 py-1" x-text="pattern"></div>
                                        </template>
                                        <div class="text-xs text-gray-500" x-text="`${item.pattern_count} pattern`"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-gray-300 align-top max-w-md">
                                    <div class="line-clamp-4" x-text="shorten(item.response, 180)"></div>
                                </td>
                                <td class="px-4 py-4 align-top whitespace-nowrap">
                                    <button @click="openEdit(item)" class="text-blue-400 hover:text-blue-300 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="remove(item)" class="text-red-400 hover:text-red-300">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredItems().length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada intent yang cocok dengan pencarian.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div x-show="modalOpen" x-cloak class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-3xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-white" x-text="editingOriginal ? 'Edit Intent' : 'Tambah Intent'"></h3>
                <button @click="closeModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>

            <form class="space-y-4" @submit.prevent="saveIntent()">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nama Intent</label>
                        <input x-model="form.intent" type="text" class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="contoh: about_academy">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Deskripsi</label>
                        <input x-model="form.description" type="text" class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Penjelasan singkat intent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Pattern Pertanyaan</label>
                    <template x-for="(pattern, index) in form.patterns" :key="index">
                        <div class="flex gap-2 mb-2">
                            <input x-model="form.patterns[index]" type="text" class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" :placeholder="`Pattern ${index + 1}`">
                            <button type="button" @click="removePattern(index)" class="px-3 py-2 bg-red-600 hover:bg-red-700 rounded-md">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="addPattern()" class="text-blue-400 hover:text-blue-300 text-sm">
                        <i class="fas fa-plus mr-1"></i> Tambah Pattern
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Respons</label>
                    <textarea x-model="form.response" class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="5" placeholder="Respons chatbot"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" @click="closeModal()" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">Batal</button>
                    <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan Intent'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function intentAdmin() {
    return {
        stats: { total_intents: 0, total_patterns: 0, total_responses: 0, accuracy: 0, items: [] },
        search: '',
        modalOpen: false,
        saving: false,
        trainingBusy: false,
        editingOriginal: '',
        flash: { type: 'success', message: '' },
        form: { intent: '', description: '', patterns: ['', ''], response: '' },

        async init() {
            await this.loadData();
        },

        formatPercent(value) {
            return `${Number(value || 0).toFixed(2)}%`;
        },

        shorten(text, max = 180) {
            return (text || '').length > max ? `${text.slice(0, max)}...` : (text || '');
        },

        filteredItems() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.stats.items;
            return this.stats.items.filter(item =>
                item.intent.toLowerCase().includes(q) ||
                item.description.toLowerCase().includes(q) ||
                item.patterns.some(p => p.toLowerCase().includes(q))
            );
        },

        async loadData() {
            const res = await fetch(@json(route('admin.intents.data')));
            const data = await res.json();
            this.stats = data;
        },

        openCreate() {
            this.editingOriginal = '';
            this.form = { intent: '', description: '', patterns: ['', ''], response: '' };
            this.modalOpen = true;
        },

        openEdit(item) {
            this.editingOriginal = item.intent;
            this.form = {
                intent: item.intent,
                description: item.description || '',
                patterns: item.patterns.length ? [...item.patterns] : [''],
                response: item.response || ''
            };
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
        },

        addPattern() {
            this.form.patterns.push('');
        },

        removePattern(index) {
            if (this.form.patterns.length === 1) return;
            this.form.patterns.splice(index, 1);
        },

        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        showFlash(type, message) {
            this.flash = { type, message };
            setTimeout(() => {
                if (this.flash.message === message) this.flash.message = '';
            }, 5000);
        },

        async saveIntent() {
            this.saving = true;
            try {
                const payload = {
                    intent: this.form.intent,
                    description: this.form.description,
                    patterns: this.form.patterns.filter(Boolean),
                    response: this.form.response,
                    original_intent: this.editingOriginal || null
                };
                const res = await fetch(@json(route('admin.intents.store')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || data.error || 'Gagal menyimpan intent.');
                }
                this.showFlash('success', `${data.message} Klik "Simpan & Retrain" agar model ikut diperbarui.`);
                this.closeModal();
                await this.loadData();
            } catch (e) {
                this.showFlash('error', e.message || 'Terjadi kesalahan.');
            } finally {
                this.saving = false;
            }
        },

        async remove(item) {
            if (!confirm(`Hapus intent "${item.intent}"?`)) return;
            try {
                const res = await fetch(`/admin/intents/${encodeURIComponent(item.intent)}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || data.error || 'Gagal menghapus intent.');
                }
                this.showFlash('success', `${data.message} Jangan lupa retrain model.`);
                await this.loadData();
            } catch (e) {
                this.showFlash('error', e.message || 'Terjadi kesalahan.');
            }
        },

        async retrain() {
            if (this.trainingBusy) return;
            this.trainingBusy = true;
            try {
                const res = await fetch(@json(url('/chatbot/train')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ mode: 'naive_bayes' })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || data.error || 'Gagal menjalankan retrain.');
                }
                this.showFlash('success', data.message || 'Job retrain berhasil dikirim ke queue.');
            } catch (e) {
                this.showFlash('error', e.message || 'Terjadi kesalahan saat retrain.');
            } finally {
                this.trainingBusy = false;
            }
        }
    };
}
</script>
</body>
</html>
