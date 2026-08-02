<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Logs - Admin Dashboard</title>
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
    </style>
</head>
<body class="bg-gray-900 text-white">
<div x-data="{ isDark: true }" :class="{ 'dark': isDark }">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="fixed w-full flex items-center justify-between h-14 bg-gray-800 text-white z-10">
            <div class="flex items-center pl-3 w-14 md:w-64 h-14">
                <img class="w-8 h-8 md:w-10 md:h-10 mr-2 rounded-full" src="{{ asset('img/logo.jpg') }}" />
                <span class="hidden md:block font-bold text-lg">Chat Logs</span>
            </div>
            <div class="flex items-center header-right pr-4">
                <a href="/admin" class="flex items-center hover:text-gray-300 text-white">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </header>

        <!-- Sidebar -->
        @include('partials.admin_sidebar_chatbot')

        <!-- Main Content -->
        <main class="flex-1 ml-14 mt-14 md:ml-64 p-6 bg-gray-900 min-h-screen">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-white mb-2">Chat Logs</h1>
                <p class="text-gray-400">Pantau dan analisis riwayat percakapan dengan chatbot</p>
            </div>

            <!-- Filter Section -->
            <div class="bg-gray-800 shadow-lg rounded-md p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tanggal Mulai</label>
                        <input type="date" class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tanggal Akhir</label>
                        <input type="date" class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Intent</label>
                        <select class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Intent</option>
                            <option value="about_academy">About Academy</option>
                            <option value="model_list">Model List</option>
                            <option value="schedule_info">Schedule Info</option>
                            <option value="registration_steps">Registration Steps</option>
                            <option value="contact_admin">Contact Admin</option>
                            <option value="ai_fallback">AI Fallback</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Status Handoff</label>
                        <select class="w-full px-3 py-2 bg-gray-700 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="yes">Handoff</option>
                            <option value="no">Tidak Handoff</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex justify-end space-x-3">
                    <button class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-redo mr-2"></i>
                        Reset
                    </button>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-filter mr-2"></i>
                        Filter
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-800 shadow-lg rounded-md p-4 text-white">
                    <div class="flex items-center">
                        <div class="flex justify-center items-center w-12 h-12 bg-blue-600 rounded-full mr-4">
                            <i class="fas fa-comments text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold">1,247</p>
                            <p class="text-gray-400">Total Percakapan</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 shadow-lg rounded-md p-4 text-white">
                    <div class="flex items-center">
                        <div class="flex justify-center items-center w-12 h-12 bg-green-600 rounded-full mr-4">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold">1,089</p>
                            <p class="text-gray-400">Berhasil Dijawab</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 shadow-lg rounded-md p-4 text-white">
                    <div class="flex items-center">
                        <div class="flex justify-center items-center w-12 h-12 bg-yellow-600 rounded-full mr-4">
                            <i class="fas fa-exchange-alt text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold">158</p>
                            <p class="text-gray-400">Handoff ke Admin</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 shadow-lg rounded-md p-4 text-white">
                    <div class="flex items-center">
                        <div class="flex justify-center items-center w-12 h-12 bg-purple-600 rounded-full mr-4">
                            <i class="fas fa-percentage text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold">87.3%</p>
                            <p class="text-gray-400">Success Rate</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Logs Table -->
            <div class="bg-gray-800 shadow-lg rounded-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-white">Riwayat Percakapan</h2>
                    <div class="flex space-x-2">
                        <button class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm">
                            <i class="fas fa-download mr-1"></i>
                            Export
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-gray-800">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Waktu</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Pertanyaan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Intent</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Respons</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Confidence</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Handoff</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <tr class="hover:bg-gray-700">
                                <td class="px-4 py-3 text-gray-300">2026-04-26 14:23:45</td>
                                <td class="px-4 py-3 text-gray-300">nazwa@gmail.com</td>
                                <td class="px-4 py-3 text-gray-300 max-w-xs truncate">apa itu academy next top model</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-600 text-white">about_academy</span>
                                </td>
                                <td class="px-4 py-3 text-gray-300 max-w-xs truncate">Academy Next Top Model adalah academy modeling yang berfokus pada pengembangan talent...</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-600 text-white">95%</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-600 text-white">Tidak</span>
                                </td>
                                <td class="px-4 py-3">
                                    <button class="text-blue-400 hover:text-blue-300 mr-2" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="text-yellow-400 hover:text-yellow-300" title="Evaluasi">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-700">
                                <td class="px-4 py-3 text-gray-300">2026-04-26 14:25:12</td>
                                <td class="px-4 py-3 text-gray-300">user@example.com</td>
                                <td class="px-4 py-3 text-gray-300 max-w-xs truncate">berapa biaya pendaftaran?</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-600 text-white">sensitive_intent</span>
                                </td>
                                <td class="px-4 py-3 text-gray-300 max-w-xs truncate">Untuk informasi biaya pendaftaran, silakan hubungi admin WhatsApp...</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-600 text-white">72%</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-600 text-white">Ya</span>
                                </td>
                                <td class="px-4 py-3">
                                    <button class="text-blue-400 hover:text-blue-300 mr-2" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="text-yellow-400 hover:text-yellow-300" title="Evaluasi">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-700">
                                <td class="px-4 py-3 text-gray-300">2026-04-26 14:28:33</td>
                                <td class="px-4 py-3 text-gray-300">test@gmail.com</td>
                                <td class="px-4 py-3 text-gray-300 max-w-xs truncate">siapa saja model yang tersedia?</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-600 text-white">model_list</span>
                                </td>
                                <td class="px-4 py-3 text-gray-300 max-w-xs truncate">Model yang ada di website antara lain: [Daftar model]...</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-600 text-white">91%</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-600 text-white">Tidak</span>
                                </td>
                                <td class="px-4 py-3">
                                    <button class="text-blue-400 hover:text-blue-300 mr-2" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="text-yellow-400 hover:text-yellow-300" title="Evaluasi">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-700">
                                <td class="px-4 py-3 text-gray-300">2026-04-26 14:30:15</td>
                                <td class="px-4 py-3 text-gray-300">guest@session.com</td>
                                <td class="px-4 py-3 text-gray-300 max-w-xs truncate">apa kabar hari ini?</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-purple-600 text-white">ai_fallback</span>
                                </td>
                                <td class="px-4 py-3 text-gray-300 max-w-xs truncate">Saya adalah asisten virtual Academy Next Top Model...</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-600 text-white">88%</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-600 text-white">Tidak</span>
                                </td>
                                <td class="px-4 py-3">
                                    <button class="text-blue-400 hover:text-blue-300 mr-2" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="text-yellow-400 hover:text-yellow-300" title="Evaluasi">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4 flex justify-between items-center">
                    <div class="text-gray-400 text-sm">
                        Menampilkan 1-10 dari 1,247 percakapan
                    </div>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 bg-gray-700 text-white rounded-md hover:bg-gray-600 disabled:opacity-50" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="px-3 py-1 bg-blue-600 text-white rounded-md">1</button>
                        <button class="px-3 py-1 bg-gray-700 text-white rounded-md hover:bg-gray-600">2</button>
                        <button class="px-3 py-1 bg-gray-700 text-white rounded-md hover:bg-gray-600">3</button>
                        <button class="px-3 py-1 bg-gray-700 text-white rounded-md hover:bg-gray-600">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
