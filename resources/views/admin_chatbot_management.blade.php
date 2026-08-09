<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('messages.cb_mgmt_page_title') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .header-right { width: calc(100% - 3.5rem); }
        .sidebar:hover { width: 16rem; }
        @media only screen and (min-width: 768px) { .header-right { width: calc(100% - 16rem); } }
        :root { --c-main: #121212; --c-sec:#1e1e1e; }
        .bg-gray-900 { background-color: var(--c-main) !important; }
        .bg-gray-800, .bg-gray-700 { background-color: var(--c-sec) !important; }
        .metric-tile { border: 1px solid #2f2f2f; box-shadow: 0 0 0 1px rgba(255,255,255,0.02) inset; }
        .metric-accent { height: 3px; border-radius: 9999px; }
        .pill { border: 1px solid #3f3f46; background: #111827; }
        .action-btn { min-height: 46px; justify-content: center; }
        /* Tailwind v2.2.19 (CDN statis) tidak punya utility untuk ini —
           class v3-style di bawah ini diam-diam mati tanpa CSS custom. */
        .dashboard-hero-grid { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
        @media (min-width: 1280px) {
            .dashboard-hero-grid { grid-template-columns: minmax(0, 1fr) 430px; }
        }
        .max-h-900 { max-height: 900px; }
        button:disabled { opacity: .5; cursor: not-allowed; }
    </style>
</head>
<body class="bg-gray-900 text-white" x-data="chatbotAdmin()" x-init="init()">
<div class="min-h-screen flex flex-col">
    <header class="fixed w-full flex items-center justify-between h-14 bg-gray-800 text-white z-20">
        <div class="flex items-center pl-3 w-14 md:w-64 h-14">
            <img class="w-8 h-8 md:w-10 md:h-10 mr-2 rounded-full" src="{{ asset('img/logo.jpg') }}" alt="Logo" />
            <span class="hidden md:block font-bold text-lg">{{ __('messages.admin_label') }}</span>
        </div>
        <div class="flex items-center header-right pr-4 justify-end gap-3">
            <div class="relative" x-data="{ open: false }" @click.away="open = false">
                <button type="button" @click="open = !open"
                    class="flex items-center gap-2 px-2.5 py-1.5 bg-gray-700 rounded-lg text-white hover:bg-gray-600 transition text-sm">
                    <span class="w-5 h-5 rounded-full overflow-hidden inline-flex items-center justify-center">
                        @if(app()->getLocale() === 'id')
                            <img src="https://flagcdn.com/24x18/id.png" alt="ID" class="w-5 h-5 rounded-full border border-gray-300" />
                        @else
                            <img src="https://flagcdn.com/24x18/gb.png" alt="EN" class="w-5 h-5 rounded-full border border-gray-300" />
                        @endif
                    </span>
                    <span class="font-medium">{{ strtoupper(app()->getLocale()) }}</span>
                    <i class="fa-solid fa-chevron-down text-xs opacity-70"></i>
                </button>
                <ul x-show="open" x-transition
                    class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg z-50 text-sm overflow-hidden"
                    style="display: none;">
                    <li>
                        <a href="{{ route('lang.switch', 'en') }}"
                           class="flex items-center gap-2 px-4 py-2 text-black hover:bg-gray-100 transition">
                            <img src="https://flagcdn.com/24x18/gb.png" alt="EN" class="w-5 h-5 rounded-full border border-gray-300" />
                            {{ __('messages.cb_lang_english') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('lang.switch', 'id') }}"
                           class="flex items-center gap-2 px-4 py-2 text-black hover:bg-gray-100 transition">
                            <img src="https://flagcdn.com/24x18/id.png" alt="ID" class="w-5 h-5 rounded-full border border-gray-300" />
                            {{ __('messages.cb_lang_indonesia') }}
                        </a>
                    </li>
                </ul>
            </div>
            <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                @csrf
                <button type="submit" class="flex items-center hover:text-gray-300 bg-transparent border-none cursor-pointer text-white text-sm">
                    <i class="fa-solid fa-right-from-bracket mr-2"></i> {{ __('messages.admin_logout') }}
                </button>
            </form>
        </div>
    </header>

    @include('partials.admin_sidebar_chatbot')

    <main class="flex-1 ml-14 mt-14 md:ml-64 p-5 md:p-6 bg-gray-900 min-h-screen">
        <div class="dashboard-hero-grid mb-5">
            <div class="bg-gray-800 rounded-2xl border border-gray-700 p-6">
                <h1 class="text-3xl font-bold text-white leading-tight">{{ __('messages.cb_mgmt_title') }}</h1>
            </div>
            <div class="bg-gray-800 rounded-2xl border border-gray-700 p-4">
                <p class="text-sm font-semibold text-white mb-3">{{ __('messages.cb_quick_actions') }}</p>
                <div class="flex flex-wrap items-center gap-3">
                    <button @click="syncRetrain()" :disabled="trainingBusy"
                        class="action-btn h-11 bg-purple-600 hover:bg-purple-700 text-white px-4 rounded-lg text-sm font-medium flex items-center whitespace-nowrap min-w-[180px]">
                        <i class="fa-solid fa-rotate mr-2" :class="trainingBusy ? 'fa-spin' : ''"></i>
                        <span class="leading-tight">{{ __('messages.cb_sync_retrain') }}</span>
                    </button>
                    <button @click="runEvaluation()" :disabled="evalBusy || trainingBusy"
                        class="action-btn h-11 bg-green-600 hover:bg-green-700 text-white px-4 rounded-lg text-sm font-medium flex items-center whitespace-nowrap min-w-[160px] justify-center">
                        <i class="fa-solid fa-flask mr-2" :class="evalBusy ? 'fa-spin' : ''"></i>
                        <span class="leading-tight" x-text="evalBusy ? i18n.evaluating : i18n.runEvaluation"></span>
                    </button>
                    <a href="{{ url('/admin/intents') }}"
                        class="action-btn h-11 bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-lg text-sm font-medium flex items-center whitespace-nowrap min-w-[140px] justify-center">
                        <i class="fa-solid fa-plus mr-2"></i>
                        <span class="leading-tight">{{ __('messages.cb_new_intent') }}</span>
                    </a>
                    <a href="{{ route('admin.chatbot.evaluation-report') }}" target="_blank"
                        class="action-btn h-11 bg-orange-500 hover:bg-orange-600 text-white px-4 rounded-lg text-sm font-medium flex items-center whitespace-nowrap min-w-[150px] justify-center">
                        <i class="fa-solid fa-file-lines mr-2"></i>
                        <span class="leading-tight">{{ __('messages.cb_open_report') }}</span>
                    </a>
                    <a href="{{ route('admin.chatbot.report') }}" target="_blank"
                        class="action-btn h-11 bg-teal-600 hover:bg-teal-700 text-white px-4 rounded-lg text-sm font-medium flex items-center whitespace-nowrap min-w-[170px] justify-center">
                        <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                        <span class="leading-tight">{{ __('messages.cb_open_activity_report') }}</span>
                    </a>
                    <button @click="clearLogs()"
                        class="action-btn h-11 bg-red-600 hover:bg-red-700 text-white px-4 rounded-lg text-sm font-medium flex items-center whitespace-nowrap min-w-[130px] justify-center">
                        <i class="fa-solid fa-trash mr-2"></i>
                        <span class="leading-tight">{{ __('messages.cb_clear_logs') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-800 rounded-xl p-4 metric-tile">
                <div class="metric-accent bg-blue-500 mb-3"></div>
                <p class="text-xs uppercase tracking-wider text-gray-400">{{ __('messages.cb_metric_accuracy') }}</p>
                <p class="text-3xl font-bold mt-1" x-text="formatPercent(stats.metrics.accuracy)">88.89%</p>
                <p class="text-xs text-gray-500 mt-3">{{ __('messages.cb_metric_accuracy_hint') }}</p>
            </div>
            <div class="bg-gray-800 rounded-xl p-4 metric-tile">
                <div class="metric-accent bg-green-500 mb-3"></div>
                <p class="text-xs uppercase tracking-wider text-gray-400">{{ __('messages.cb_metric_precision') }}</p>
                <p class="text-3xl font-bold mt-1" x-text="formatPercent(stats.metrics.precision)">89.53%</p>
                <p class="text-xs text-gray-500 mt-3">{{ __('messages.cb_metric_precision_hint') }}</p>
            </div>
            <div class="bg-gray-800 rounded-xl p-4 metric-tile">
                <div class="metric-accent bg-yellow-500 mb-3"></div>
                <p class="text-xs uppercase tracking-wider text-gray-400">{{ __('messages.cb_metric_recall') }}</p>
                <p class="text-3xl font-bold mt-1" x-text="formatPercent(stats.metrics.recall)">88.89%</p>
                <p class="text-xs text-gray-500 mt-3">{{ __('messages.cb_metric_recall_hint') }}</p>
            </div>
            <div class="bg-gray-800 rounded-xl p-4 metric-tile">
                <div class="metric-accent bg-purple-500 mb-3"></div>
                <p class="text-xs uppercase tracking-wider text-gray-400">{{ __('messages.cb_metric_f1') }}</p>
                <p class="text-3xl font-bold mt-1" x-text="formatPercent(stats.metrics.f1)">88.75%</p>
                <p class="text-xs text-gray-500 mt-3">{{ __('messages.cb_metric_f1_hint') }}</p>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl p-5 border border-gray-700 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold flex items-center">
                        <i class="fa-solid fa-microchip mr-2 text-purple-400"></i>
                        {{ __('messages.cb_status_label') }}
                        <span class="ml-2 text-sm font-medium"
                            :class="trainingBusy ? 'text-blue-400' : (stats.health.nb_model_loaded ? 'text-green-400' : 'text-gray-400')"
                            x-text="trainingBusy ? i18n.statusRetraining : (stats.health.nb_model_loaded ? i18n.statusReady : i18n.statusInactive)"></span>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">{{ __('messages.cb_last_activity') }} <span x-text="lastLog || i18n.noRecentActivity"></span></p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div class="bg-gray-900 rounded-lg px-3 py-2">
                        <p class="text-gray-500">{{ __('messages.cb_training_mode') }}</p>
                        <p class="text-white font-semibold">{{ __('messages.cb_queue_worker') }}</p>
                    </div>
                    <div class="bg-gray-900 rounded-lg px-3 py-2">
                        <p class="text-gray-500">{{ __('messages.cb_main_action') }}</p>
                        <p class="text-white font-semibold">{{ __('messages.cb_retrain_model') }}</p>
                    </div>
                    <div class="bg-gray-900 rounded-lg px-3 py-2">
                        <p class="text-gray-500">{{ __('messages.cb_service') }}</p>
                        <p class="font-semibold" :class="stats.health.status === 'ok' || stats.health.status === 'healthy' ? 'text-green-400' : 'text-yellow-400'" x-text="stats.health.status === 'ok' || stats.health.status === 'healthy' ? i18n.online : i18n.offline"></p>
                    </div>
                    <div class="bg-gray-900 rounded-lg px-3 py-2">
                        <p class="text-gray-500">{{ __('messages.cb_top_intent_label') }}</p>
                        <p class="text-white font-semibold truncate" x-text="prettyIntent(stats.top_intent)"></p>
                    </div>
                </div>
            </div>
            <p class="text-xs mt-3 whitespace-pre-wrap break-words max-h-40 overflow-y-auto"
                :class="trainingStatus === 'failed' ? 'text-red-400' : 'text-yellow-500'"
                x-show="trainingMessage" x-text="trainingMessage"></p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gray-800 rounded-xl p-4 border-l-4 border-blue-500">
                        <p class="text-xs uppercase text-gray-400">{{ __('messages.cb_total_conversations') }}</p>
                        <p class="text-3xl font-bold mt-2" x-text="stats.total_conversations">0</p>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-4 border-l-4 border-yellow-500">
                        <p class="text-xs uppercase text-gray-400">{{ __('messages.cb_fallback_responses') }}</p>
                        <p class="text-3xl font-bold mt-2">
                            <span x-text="stats.fallback_count">0</span>
                            <span class="text-base text-yellow-400" x-text="'(' + stats.fallback_percent + '%)'"></span>
                        </p>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-4 border-l-4 border-green-500">
                        <p class="text-xs uppercase text-gray-400">{{ __('messages.cb_whatsapp_handoffs') }}</p>
                        <p class="text-3xl font-bold mt-2" x-text="stats.handoff_count">0</p>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-4 border-l-4 border-purple-500">
                        <p class="text-xs uppercase text-gray-400">{{ __('messages.cb_top_intent') }}</p>
                        <p class="text-lg font-bold mt-3 truncate" x-text="stats.top_intent">N/A</p>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
                        <h3 class="font-semibold flex items-center"><i class="fa-solid fa-clock-rotate-left mr-2"></i> {{ __('messages.cb_chat_history') }}</h3>
                        <span class="text-xs text-gray-500">{{ __('messages.cb_showing_latest') }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-900 text-gray-400 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('messages.cb_col_time') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('messages.cb_col_message') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('messages.cb_col_intent') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('messages.cb_col_confidence') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('messages.cb_col_status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <template x-for="item in stats.latest_interactions" :key="item.timestamp + item.message">
                                    <tr class="hover:bg-gray-900/70">
                                        <td class="px-4 py-3 text-gray-400 whitespace-nowrap" x-text="item.time_ago"></td>
                                        <td class="px-4 py-3 text-gray-200 max-w-md truncate" :title="item.message" x-text="item.message"></td>
                                        <td class="px-4 py-3">
                                            <span class="pill inline-flex rounded-full px-3 py-1 text-xs text-blue-200" x-text="item.intent"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2 min-w-[120px]">
                                                <div class="w-20 bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                                    <div class="bg-green-400 h-1.5 rounded-full" :style="`width:${Math.min(item.confidence, 100)}%`"></div>
                                                </div>
                                                <span class="text-green-400 text-xs" x-text="item.confidence + '%'"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs"
                                                :class="item.handoff ? 'bg-yellow-900 text-yellow-300' : 'bg-gray-700 text-gray-200'"
                                                x-text="item.handoff ? i18n.statusHandoff : i18n.statusNew"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="stats.latest_interactions.length === 0">
                                    <td colspan="5" class="px-4 py-10 text-center text-gray-500">{{ __('messages.cb_no_chat_data') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <h3 class="font-semibold mb-4">{{ __('messages.cb_top5_intents') }}</h3>
                        <div x-show="stats.total_conversations === 0" class="text-gray-500 text-center py-16 text-sm">{{ __('messages.cb_no_conversation_yet') }}</div>
                        <canvas id="intentsChart" class="max-h-64" x-show="stats.total_conversations > 0"></canvas>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                        <h3 class="font-semibold mb-4">{{ __('messages.cb_conversations_7d') }}</h3>
                        <div x-show="stats.total_conversations === 0" class="text-gray-500 text-center py-16 text-sm">{{ __('messages.cb_no_conversation_yet') }}</div>
                        <canvas id="dailyChart" class="max-h-64" x-show="stats.total_conversations > 0"></canvas>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-gray-800 rounded-xl p-5 border border-gray-700">
                    <h3 class="font-semibold mb-4 flex items-center"><i class="fa-solid fa-lightbulb mr-2 text-yellow-300"></i> {{ __('messages.cb_trained_intents') }}</h3>
                    <div class="space-y-4 max-h-900 overflow-y-auto pr-1">
                        <template x-for="intent in stats.trained_intents" :key="intent.intent">
                            <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                                <p class="text-blue-300 font-semibold mb-2" x-text="intent.intent"></p>
                                <p class="text-xs text-gray-400 uppercase mb-1">{{ __('messages.cb_patterns') }}</p>
                                <p class="text-sm text-gray-200 leading-relaxed" x-text="intent.patterns.join('; ')"></p>
                                <p class="text-xs text-gray-400 uppercase mt-3 mb-1">{{ __('messages.cb_response') }}</p>
                                <p class="text-sm text-gray-300 leading-relaxed" x-text="shorten(intent.response, 160)"></p>
                            </div>
                        </template>
                        <div x-show="stats.trained_intents.length === 0" class="text-gray-500 text-center py-8 text-sm">{{ __('messages.cb_no_trained_intents') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const i18n = {
    evaluating: @json(__('messages.cb_evaluating')),
    runEvaluation: @json(__('messages.cb_run_evaluation')),
    statusRetraining: @json(__('messages.cb_status_retraining')),
    statusReady: @json(__('messages.cb_status_ready')),
    statusInactive: @json(__('messages.cb_status_inactive')),
    noRecentActivity: @json(__('messages.cb_no_recent_activity')),
    online: @json(__('messages.cb_online')),
    offline: @json(__('messages.cb_offline')),
    statusHandoff: @json(__('messages.cb_status_handoff')),
    statusNew: @json(__('messages.cb_status_new')),
    queueMsg: @json(__('messages.cb_js_queue_msg')),
    jobQueued: @json(__('messages.cb_js_job_queued')),
    trainingDone: @json(__('messages.cb_js_training_done')),
    trainingDoneWithAccuracy: @json(__('messages.cb_js_training_done_with_accuracy')),
    trainingFailedAsync: @json(__('messages.cb_js_training_failed_async')),
    trainingTimeout: @json(__('messages.cb_js_training_timeout')),
    evalRunning: @json(__('messages.cb_js_eval_running')),
    evalDone: @json(__('messages.cb_js_eval_done')),
    evalFailed: @json(__('messages.cb_js_eval_failed')),
    evalQueued: @json(__('messages.cb_js_eval_queued')),
    evalBlockedByTraining: @json(__('messages.cb_js_eval_blocked_by_training')),
    evalStuckInQueue: @json(__('messages.cb_js_eval_stuck_in_queue')),
    evalTimeout: @json(__('messages.cb_js_eval_timeout')),
    clearConfirm: @json(__('messages.cb_js_clear_confirm')),
    logsCleared: @json(__('messages.cb_js_logs_cleared')),
    logsClearFailed: @json(__('messages.cb_js_logs_clear_failed')),
    chartCount: @json(__('messages.cb_js_chart_count')),
    chartConversations: @json(__('messages.cb_js_chart_conversations')),
    locale: @json(app()->getLocale() === 'id' ? 'id-ID' : 'en-US'),
};

function chatbotAdmin() {
    return {
        i18n,
        stats: {
            total_conversations: 0,
            fallback_count: 0,
            fallback_percent: 0,
            handoff_count: 0,
            top_intent: 'N/A',
            top_intents: [],
            conversations_by_day: [],
            latest_interactions: [],
            trained_intents: [],
            metrics: { accuracy: 88.89, precision: 89.53, recall: 88.89, f1: 88.75 },
            health: { status: 'offline', nb_model_loaded: false }
        },
        trainingBusy: false,
        trainingStatus: 'idle',
        trainingMessage: '',
        evalBusy: false,
        lastLog: null,
        intentsChart: null,
        dailyChart: null,

        async init() {
            await this.loadAnalytics();
        },

        formatPercent(value) {
            return `${Number(value || 0).toFixed(2)}%`;
        },

        prettyIntent(value) {
            const text = (value || 'N/A').replaceAll('_', ' ');
            return text.charAt(0).toUpperCase() + text.slice(1);
        },

        shorten(text, max = 160) {
            const t = text || '';
            return t.length > max ? `${t.slice(0, max)}...` : t;
        },

        async loadAnalytics() {
            try {
                const res = await fetch(@json(url('/admin/chatbot-analytics')));
                const data = await res.json();
                this.stats = {
                    ...this.stats,
                    ...data,
                    metrics: { ...this.stats.metrics, ...(data.metrics || {}) },
                    health: { ...this.stats.health, ...(data.health || {}) }
                };
                this.$nextTick(() => this.renderCharts());
            } catch (e) {
                console.error(e);
            }
        },

        renderCharts() {
            const intents = this.stats.top_intents || [];
            const days = this.stats.conversations_by_day || [];

            const ic = document.getElementById('intentsChart');
            if (ic) {
                if (this.intentsChart) this.intentsChart.destroy();
                if (intents.length > 0) {
                    this.intentsChart = new Chart(ic, {
                        type: 'bar',
                        data: {
                            labels: intents.map(i => i.intent || 'unknown'),
                            datasets: [{
                                label: i18n.chartCount,
                                data: intents.map(i => i.count),
                                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444'],
                                borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } },
                                y: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' }, beginAtZero: true }
                            }
                        }
                    });
                }
            }

            const dc = document.getElementById('dailyChart');
            if (dc) {
                if (this.dailyChart) this.dailyChart.destroy();
                if (days.length > 0) {
                    this.dailyChart = new Chart(dc, {
                        type: 'line',
                        data: {
                            labels: days.map(d => d.date),
                            datasets: [{
                                label: i18n.chartConversations,
                                data: days.map(d => d.count),
                                borderColor: 'rgba(59, 130, 246, 1)',
                                backgroundColor: 'rgba(59, 130, 246, 0.15)',
                                fill: true,
                                tension: 0.35
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } },
                                y: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' }, beginAtZero: true }
                            }
                        }
                    });
                }
            }
        },

        trainPollTimer: null,

        async syncRetrain() {
            if (this.trainingBusy) return;
            this.trainingBusy = true;
            this.trainingStatus = 'running';
            this.trainingMessage = i18n.queueMsg;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

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

                if (!res.ok) {
                    this.trainingBusy = false;
                    this.trainingStatus = 'failed';
                    this.trainingMessage = data.message || data.error || ('HTTP ' + res.status);
                    this.lastLog = new Date().toLocaleString(i18n.locale) + ' - FAILED';
                    return;
                }

                this.trainingMessage = data.message || i18n.jobQueued;
                this.lastLog = new Date().toLocaleString(i18n.locale) + ' - Job queued';
                await this.loadAnalytics();
                // trainingBusy tetap true -- dilepas oleh pollTrainingStatus() saat job BENERAN selesai.
                this.pollTrainingStatus();
            } catch (e) {
                this.trainingBusy = false;
                this.trainingStatus = 'failed';
                this.trainingMessage = 'Error: ' + e.message;
                this.lastLog = new Date().toLocaleString(i18n.locale) + ' - ERROR';
            }
        },

        // Polling singkat status job async (bukan delay buta) -- supaya notifikasi
        // "training selesai" muncul persis saat job selesai, bukan cuma pesan
        // "masuk antrian" yang menggantung tanpa follow-up.
        pollTrainingStatus() {
            if (this.trainPollTimer) clearInterval(this.trainPollTimer);
            let attempts = 0;
            let sawRunning = false;
            const maxAttempts = 40; // ~2 menit @ 3 detik/poll -- jauh di atas durasi training normal (~beberapa detik)

            this.trainPollTimer = setInterval(async () => {
                attempts++;
                try {
                    const res = await fetch(@json(url('/chatbot/train/status')), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });
                    const data = await res.json().catch(() => ({}));

                    if (data.running) sawRunning = true;

                    if (!data.running && (data.result || sawRunning)) {
                        clearInterval(this.trainPollTimer);
                        this.trainPollTimer = null;
                        await this.onTrainingFinished(data.result);
                        return;
                    }
                } catch (e) {
                    // hiccup jaringan sesaat -- lanjut poll sampai maxAttempts
                }

                if (attempts >= maxAttempts) {
                    clearInterval(this.trainPollTimer);
                    this.trainPollTimer = null;
                    this.trainingBusy = false;
                    this.trainingStatus = 'unknown';
                    this.trainingMessage = i18n.trainingTimeout;
                    this.lastLog = new Date().toLocaleString(i18n.locale) + ' - Training status unknown (timeout)';
                }
            }, 3000);
        },

        async onTrainingFinished(result) {
            this.trainingBusy = false;
            await this.loadAnalytics();

            if (result && result.status === 'success') {
                this.trainingStatus = 'done';
                const accVal = result.model_info && typeof result.model_info.accuracy === 'number'
                    ? result.model_info.accuracy
                    : null;
                this.trainingMessage = accVal !== null
                    ? i18n.trainingDoneWithAccuracy.replace(':accuracy', this.formatPercent(accVal * 100))
                    : i18n.trainingDone;
                this.lastLog = new Date().toLocaleString(i18n.locale) + ' - Training selesai';
            } else if (result && result.status === 'failed') {
                this.trainingStatus = 'failed';
                this.trainingMessage = result.error ? `${i18n.trainingFailedAsync}: ${result.error}` : i18n.trainingFailedAsync;
                this.lastLog = new Date().toLocaleString(i18n.locale) + ' - Training gagal';
            } else {
                this.trainingStatus = 'done';
                this.trainingMessage = i18n.jobQueued;
            }
        },

        evalPollTimer: null,

        async runEvaluation() {
            if (this.evalBusy) return;
            this.evalBusy = true;
            this.trainingStatus = 'running';
            this.trainingMessage = i18n.evalRunning;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const res = await fetch(@json(url('/admin/chatbot-evaluation/run')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({})
                });
                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    this.evalBusy = false;
                    this.trainingStatus = 'failed';
                    const baseMsg = data.error || data.message || (i18n.evalFailed + ' (HTTP ' + res.status + ')');
                    this.trainingMessage = data.detail ? `${baseMsg}\n${data.detail}` : baseMsg;
                    this.lastLog = new Date().toLocaleString(i18n.locale) + ' - EVAL FAILED';
                    return;
                }

                this.trainingMessage = data.message || i18n.evalQueued;
                this.lastLog = new Date().toLocaleString(i18n.locale) + ' - Eval queued';
                // evalBusy tetap true -- dilepas oleh pollEvaluationStatus() saat job BENERAN selesai.
                this.pollEvaluationStatus();
            } catch (e) {
                this.evalBusy = false;
                this.trainingStatus = 'failed';
                this.trainingMessage = 'Error: ' + e.message;
                this.lastLog = new Date().toLocaleString(i18n.locale) + ' - EVAL ERROR';
            }
        },

        // Polling status job evaluasi async -- pola sama seperti pollTrainingStatus().
        // Beda tambahan: bedakan "menunggu training selesai" (wajar, satu worker
        // satu job) vs "kemungkinan queue worker tidak aktif" (queued lama TANPA
        // training aktif bersamaan).
        pollEvaluationStatus() {
            if (this.evalPollTimer) clearInterval(this.evalPollTimer);
            let attempts = 0;
            let sawRunning = false;
            let warnedStuck = false;
            const maxAttempts = 40; // ~2 menit @ 3 detik/poll

            this.evalPollTimer = setInterval(async () => {
                attempts++;
                try {
                    const res = await fetch(@json(url('/admin/chatbot-evaluation/status')), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });
                    const data = await res.json().catch(() => ({}));

                    if (data.running) sawRunning = true;

                    if (data.blocked_by_training) {
                        this.trainingMessage = i18n.evalBlockedByTraining;
                    } else if (data.stuck_in_queue && !warnedStuck) {
                        warnedStuck = true;
                        this.trainingMessage = i18n.evalStuckInQueue;
                    }

                    if (!data.running && (data.result || sawRunning)) {
                        clearInterval(this.evalPollTimer);
                        this.evalPollTimer = null;
                        await this.onEvaluationFinished(data.result);
                        return;
                    }
                } catch (e) {
                    // hiccup jaringan sesaat -- lanjut poll sampai maxAttempts
                }

                if (attempts >= maxAttempts) {
                    clearInterval(this.evalPollTimer);
                    this.evalPollTimer = null;
                    this.evalBusy = false;
                    this.trainingStatus = 'unknown';
                    this.trainingMessage = i18n.evalTimeout;
                    this.lastLog = new Date().toLocaleString(i18n.locale) + ' - Evaluation status unknown (timeout)';
                }
            }, 3000);
        },

        async onEvaluationFinished(result) {
            this.evalBusy = false;
            await this.loadAnalytics();

            if (result && result.status === 'success') {
                this.trainingStatus = 'done';
                if (result.metrics) {
                    this.stats.metrics = { ...this.stats.metrics, ...result.metrics };
                }
                this.trainingMessage = i18n.evalDone;
                this.lastLog = new Date().toLocaleString(i18n.locale) + ' - EVAL DONE';
            } else if (result && result.status === 'failed') {
                this.trainingStatus = 'failed';
                const baseMsg = result.error || i18n.evalFailed;
                this.trainingMessage = result.detail ? `${baseMsg}\n${result.detail}` : baseMsg;
                this.lastLog = new Date().toLocaleString(i18n.locale) + ' - EVAL FAILED';
            } else {
                this.trainingStatus = 'done';
                this.trainingMessage = i18n.evalDone;
            }
        },

        async clearLogs() {
            if (!confirm(i18n.clearConfirm)) return;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const res = await fetch(@json(url('/admin/chatbot-clear-logs')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.lastLog = new Date().toLocaleString(i18n.locale) + ' - Logs cleared';
                    this.trainingMessage = data.message || i18n.logsCleared;
                    await this.loadAnalytics();
                    alert(this.trainingMessage);
                } else {
                    alert(data.error || i18n.logsClearFailed);
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }
    };
}
</script>
</body>
</html>
