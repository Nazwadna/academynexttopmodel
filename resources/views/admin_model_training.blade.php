<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Model Training - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .header-right { width: calc(100% - 3.5rem); }
        .sidebar:hover { width: 16rem; }
        @media only screen and (min-width: 768px) { .header-right { width: calc(100% - 16rem); } }
        :root { --c-main: #121212; --c-sec:#1e1e1e; }
        .bg-main { background-color: var(--c-main) !important; }
        .bg-secondary { background-color: var(--c-sec) !important; }
        .bg-gray-900 { background-color: var(--c-main) !important; }
        .bg-gray-800, .bg-gray-700 { background-color: var(--c-sec) !important; }
        .progress-bar {
            transition: width 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
<div x-data="{ 
    isTraining: false,
    trainingProgress: 0,
    trainingStatus: 'ready',
    lastResult: null,
    statusMessage: 'Siap melatih ulang Naive Bayes + TF-IDF dari dataset_prepared.csv',
    trainingHistory: []
}" :class="{ 'dark': true }">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="fixed w-full flex items-center justify-between h-14 bg-gray-800 text-white z-10">
            <div class="flex items-center pl-3 w-14 md:w-64 h-14">
                <img class="w-8 h-8 md:w-10 md:h-10 mr-2 rounded-full" src="{{ asset('img/logo.jpg') }}" />
                <span class="hidden md:block font-bold text-lg">Model Training</span>
            </div>
            <div class="flex items-center header-right pr-4">
                <a href="/admin" class="flex items-center hover:text-gray-300 text-white">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </header>

        <!-- Sidebar -->
        <aside class="fixed top-14 left-0 w-14 hover:w-64 md:w-64 bg-gray-800 h-full text-white transition-all duration-300 z-10 sidebar flex flex-col">
            <div class="flex-grow overflow-y-auto">
                <ul class="py-4 space-y-1">
                    <li>
                        <a href="/admin" class="flex items-center h-11 hover:bg-gray-700 px-4 font-bold">
                            <i class="fa-solid fa-gauge-high w-5 min-w-[20px] text-center"></i>
                            <span class="ml-2 text-sm tracking-wide truncate">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="/admin/intents" class="flex items-center h-11 hover:bg-gray-700 px-4">
                            <i class="fa-solid fa-brain w-5 min-w-[20px] text-center"></i>
                            <span class="ml-2 text-sm tracking-wide truncate">Intent Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="/admin/chat-logs" class="flex items-center h-11 hover:bg-gray-700 px-4">
                            <i class="fa-solid fa-comments w-5 min-w-[20px] text-center"></i>
                            <span class="ml-2 text-sm tracking-wide truncate">Chat Logs</span>
                        </a>
                    </li>
                    <li>
                        <a href="/admin/model-training" class="flex items-center h-11 bg-gray-700 px-4">
                            <i class="fa-solid fa-robot w-5 min-w-[20px] text-center"></i>
                            <span class="ml-2 text-sm tracking-wide truncate">Model Training</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-14 mt-14 md:ml-64 p-6 bg-gray-900 min-h-screen">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white mb-2">Model Training</h1>
                <p class="text-gray-400">Latih ulang model chatbot (Naive Bayes + TF-IDF) secara nyata via Laravel Queue → Flask</p>
            </div>

            <!-- Model Status -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-gray-800 shadow-lg rounded-md p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex justify-center items-center w-12 h-12 bg-green-600 rounded-full mr-4">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Model Status</h3>
                            <p class="text-gray-400">Current Model</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Algorithm:</span>
                            <span class="text-green-400 font-bold">NB + TF-IDF</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Queue:</span>
                            <span class="text-gray-300">chatbot-training</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Endpoint:</span>
                            <span class="text-gray-300 text-xs">/chatbot/train</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 shadow-lg rounded-md p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex justify-center items-center w-12 h-12 bg-blue-600 rounded-full mr-4">
                            <i class="fas fa-database text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Training Data</h3>
                            <p class="text-gray-400">Dataset Info</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Total Examples:</span>
                            <span class="text-blue-400 font-bold">1,247</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Intents:</span>
                            <span class="text-blue-400 font-bold">15</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Augmented:</span>
                            <span class="text-blue-400 font-bold">3,741</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 shadow-lg rounded-md p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex justify-center items-center w-12 h-12 bg-purple-600 rounded-full mr-4">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Performance</h3>
                            <p class="text-gray-400">Metrics</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Precision:</span>
                            <span class="text-purple-400 font-bold">0.85</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Recall:</span>
                            <span class="text-purple-400 font-bold">0.83</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">F1-Score:</span>
                            <span class="text-purple-400 font-bold">0.84</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Training Control -->
            <div class="bg-gray-800 shadow-lg rounded-md p-6 mb-6">
                <h2 class="text-xl font-bold text-white mb-4">Training Control</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-3">Training Configuration</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Epochs</label>
                                <input type="number" value="50" class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Batch Size</label>
                                <input type="number" value="32" class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Learning Rate</label>
                                <input type="number" value="0.001" step="0.0001" class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" checked class="mr-2">
                                    <span class="text-gray-300">Use Data Augmentation</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-white mb-3">Training Progress</h3>
                        <div x-show="!isTraining" class="text-center py-8">
                            <i class="fas fa-robot text-6xl text-gray-600 mb-4"></i>
                            <p class="text-gray-400 mb-2" x-text="statusMessage"></p>
                            <p class="text-xs text-yellow-400 mb-4">Pastikan Flask + Queue Worker aktif sebelum mulai</p>
                            <button @click="startRealTraining()"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md flex items-center mx-auto">
                                <i class="fas fa-play mr-2"></i>
                                Mulai Training (Naive Bayes)
                            </button>
                        </div>

                        <div x-show="isTraining" class="space-y-4">
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
                                    <i class="fas fa-spinner fa-spin text-white text-2xl"></i>
                                </div>
                                <p class="text-lg font-semibold text-white">Training in Progress...</p>
                                <p class="text-gray-400" x-text="statusMessage"></p>
                                <p class="text-gray-400" x-text="`Progress: ${trainingProgress}%`"></p>
                            </div>

                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-400">Training Progress</span>
                                    <span x-text="`${trainingProgress}%`"></span>
                                </div>
                                <div class="w-full bg-gray-700 rounded-full h-2">
                                    <div class="progress-bar bg-blue-600 h-2 rounded-full"
                                         :style="`width: ${trainingProgress}%`"></div>
                                </div>
                            </div>
                        </div>

                        <div x-show="lastResult" class="mt-4 p-4 bg-gray-900 rounded border border-gray-700 text-sm">
                            <p class="text-green-400 font-semibold mb-2">Hasil training terakhir</p>
                            <pre class="text-gray-300 whitespace-pre-wrap text-xs" x-text="JSON.stringify(lastResult, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Training History -->
            <div class="bg-gray-800 shadow-lg rounded-md p-6">
                <h2 class="text-xl font-bold text-white mb-4">Training History</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-gray-800">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Accuracy</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Loss</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Epochs</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                <tbody class="divide-y divide-gray-700">
                    <template x-for="history in trainingHistory" :key="history.date">
                        <tr class="hover:bg-gray-700">
                            <td class="px-4 py-3 text-gray-300" x-text="history.date"></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full bg-green-600 text-white" x-text="history.accuracy"></span>
                            </td>
                            <td class="px-4 py-3 text-gray-300" x-text="history.f1"></td>
                            <td class="px-4 py-3 text-gray-300">1-pass (NB)</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full bg-green-600 text-white" x-text="history.status"></span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">-</td>
                        </tr>
                    </template>
                    <tr x-show="trainingHistory.length === 0">
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">Belum ada history training di sesi ini</td>
                    </tr>
                </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Dipanggil dari Alpine via window agar akses ke state x-data mudah
        async function startRealTraining() {
            const root = document.querySelector('[x-data]');
            if (!root || !root._x_dataStack) {
                alert('Alpine belum siap');
                return;
            }
            const state = root._x_dataStack[0];

            state.isTraining = true;
            state.trainingProgress = 5;
            state.trainingStatus = 'training';
            state.statusMessage = 'Mengirim job ke queue chatbot-training...';
            state.lastResult = null;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content
                || @json(csrf_token());

            // Progress visual sementara job dijalankan worker
            const tick = setInterval(() => {
                if (state.trainingProgress < 90) state.trainingProgress += 5;
            }, 800);

            try {
                const res = await fetch(@json(url('/chatbot/train')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ mode: 'naive_bayes' })
                });

                const data = await res.json().catch(() => ({}));
                clearInterval(tick);

                if (!res.ok) {
                    state.isTraining = false;
                    state.trainingStatus = 'failed';
                    state.trainingProgress = 0;
                    state.statusMessage = data.error || data.message || ('HTTP ' + res.status);
                    return;
                }

                state.trainingProgress = 100;
                state.statusMessage = data.message || 'Job masuk antrian. Worker sedang / akan melatih model.';
                state.lastResult = data;
                state.trainingHistory.unshift({
                    date: new Date().toLocaleString('id-ID'),
                    accuracy: 'queued',
                    f1: '-',
                    status: 'queued'
                });

                // Cek health Flask setelah jeda (model sudah di-reload jika worker cepat)
                setTimeout(async () => {
                    try {
                        const h = await fetch(@json(url('/chatbot/health')));
                        const hj = await h.json();
                        state.lastResult = { ...data, health: hj };
                        if (hj?.api_status?.nb_model_loaded) {
                            state.statusMessage = 'Training diantrikan & Flask melaporkan NB model loaded.';
                        }
                    } catch (e) {}
                    state.isTraining = false;
                    state.trainingStatus = 'completed';
                }, 3000);
            } catch (e) {
                clearInterval(tick);
                state.isTraining = false;
                state.trainingStatus = 'failed';
                state.trainingProgress = 0;
                state.statusMessage = 'Gagal memanggil /chatbot/train: ' + e.message;
            }
        }
    </script>
</body>
</html>
