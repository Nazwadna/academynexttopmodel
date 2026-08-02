{{-- Sidebar admin chatbot (Intent / Logs / Management) --}}
<aside class="fixed top-14 left-0 w-14 hover:w-64 md:w-64 bg-gray-800 h-full text-white transition-all duration-300 z-10 sidebar flex flex-col">
    <div class="flex-grow overflow-y-auto">
        <ul class="py-4 space-y-1">
            <li>
                <a href="/admin" class="flex items-center h-11 hover:bg-gray-700 px-4 font-bold">
                    <i class="fa-solid fa-gauge-high w-5 min-w-[20px] text-center"></i>
                    <span class="ml-2 text-sm tracking-wide truncate">{{ __('messages.cb_sidebar_dashboard') }}</span>
                </a>
            </li>
            <li>
                <a href="/admin/chatbot-management"
                   class="flex items-center h-11 px-4 {{ request()->is('admin/chatbot-management') ? 'bg-gray-700' : 'hover:bg-gray-700' }}">
                    <i class="fa-solid fa-robot w-5 min-w-[20px] text-center"></i>
                    <span class="ml-2 text-sm tracking-wide truncate">{{ __('messages.cb_sidebar_management') }}</span>
                </a>
            </li>
            <li>
                <a href="/admin/intents"
                   class="flex items-center h-11 px-4 {{ request()->is('admin/intents') ? 'bg-gray-700' : 'hover:bg-gray-700' }}">
                    <i class="fa-solid fa-brain w-5 min-w-[20px] text-center"></i>
                    <span class="ml-2 text-sm tracking-wide truncate">{{ __('messages.cb_sidebar_intents') }}</span>
                </a>
            </li>
            <li>
                <a href="/admin/chat-logs"
                   class="flex items-center h-11 px-4 {{ request()->is('admin/chat-logs') ? 'bg-gray-700' : 'hover:bg-gray-700' }}">
                    <i class="fa-solid fa-comments w-5 min-w-[20px] text-center"></i>
                    <span class="ml-2 text-sm tracking-wide truncate">{{ __('messages.cb_sidebar_logs') }}</span>
                </a>
            </li>
            <li>
                <a href="/admin/chatbot-evaluation-report"
                   class="flex items-center h-11 px-4 {{ request()->is('admin/chatbot-evaluation-report') ? 'bg-gray-700' : 'hover:bg-gray-700' }}">
                    <i class="fa-solid fa-chart-column w-5 min-w-[20px] text-center"></i>
                    <span class="ml-2 text-sm tracking-wide truncate">{{ __('messages.cb_sidebar_eval_report') }}</span>
                </a>
            </li>
            <li>
                <a href="/admin/chatbot-report"
                   class="flex items-center h-11 px-4 {{ request()->is('admin/chatbot-report') ? 'bg-gray-700' : 'hover:bg-gray-700' }}">
                    <i class="fa-solid fa-file-lines w-5 min-w-[20px] text-center"></i>
                    <span class="ml-2 text-sm tracking-wide truncate">{{ __('messages.cb_sidebar_activity_report') }}</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
