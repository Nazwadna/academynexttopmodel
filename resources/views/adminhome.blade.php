<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .header-right { width: calc(100% - 3.5rem); }
        .sidebar:hover { width: 16rem; }
        @media only screen and (min-width: 768px) { .header-right { width: calc(100% - 16rem); } }

        /* Admin theme colors */
        :root { --c-main: #121212; --c-sec:#1e1e1e; }
        .bg-main { background-color: var(--c-main) !important; }
        .bg-secondary { background-color: var(--c-sec) !important; }

        /* Map Tailwind grays to custom palette for this page */
        .bg-gray-900 { background-color: var(--c-main) !important; }
        .bg-gray-800, .bg-gray-700 { background-color: var(--c-sec) !important; }

        .row-hover:hover { background-color:rgb(8, 8, 8) !important; }

        
    </style>
</head>
<script src="//unpkg.com/alpinejs" defer></script>


<body class="bg-gray-900 text-white">
<div x-data="{ isDark: true, toggleTheme() { this.isDark = !this.isDark } }" :class="{ 'dark': isDark }">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="fixed w-full flex items-center justify-between h-14 bg-gray-800 text-white z-10">
            <div class="flex items-center pl-3 w-14 md:w-64 h-14">
                <img class="w-8 h-8 md:w-10 md:h-10 mr-2 rounded-full" src={{ asset('img/logo.jpg') }} />
                <span class="hidden md:block font-bold text-lg">{{ __('messages.admin_label') }}</span>
            </div>
            <div class="flex items-center header-right pr-4">
                <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="ml-4 flex items-center hover:text-gray-300 bg-transparent border-none cursor-pointer text-white">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        {{ __('messages.admin_logout') }}
                    </button>
                </form>
                <!-- Dropdown Bahasa dengan Alpine.js -->
<div class="relative ml-4" x-data="{ open: false }" @click.away="open = false">
  <button @click="open = !open"
    class="flex items-center gap-2 px-3 py-2 bg-gray-700 rounded-lg text-white hover:bg-gray-600 transition">
    <span class="w-5 h-5 rounded-full overflow-hidden">
      <img src="https://flagcdn.com/24x18/gb.png" alt="English"
        class="w-5 h-5 rounded-full border border-gray-300"
        style="display: {{ app()->getLocale() == 'en' ? 'inline' : 'none' }};" />
      <img src="https://flagcdn.com/24x18/id.png" alt="Indonesia"
        class="w-5 h-5 rounded-full border border-gray-300"
        style="display: {{ app()->getLocale() == 'id' ? 'inline' : 'none' }};" />
    </span>
    <span class="text-sm font-medium">{{ strtoupper(app()->getLocale()) }}</span>
    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
  </button>

  <!-- Dropdown -->
  <ul x-show="open" x-transition
    class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg z-50 text-sm"
    @click="open = false">
    <li>
      <a href="{{ route('lang.switch', 'en') }}"
        class="flex items-center gap-2 px-4 py-2 text-black hover:bg-gray-100 transition">
        <img src="https://flagcdn.com/24x18/gb.png" alt="English"
          class="w-5 h-5 rounded-full border border-gray-300" />
        English
      </a>
    </li>
    <li>
      <a href="{{ route('lang.switch', 'id') }}"
        class="flex items-center gap-2 px-4 py-2 text-black hover:bg-gray-100 transition">
        <img src="https://flagcdn.com/24x18/id.png" alt="Indonesia"
          class="w-5 h-5 rounded-full border border-gray-300" />
        Indonesia
      </a>
    </li>
  </ul>
</div>

            </div>
            
        </header>
        <!-- Sidebar -->
        <aside class="fixed top-14 left-0 w-14 hover:w-64 md:w-64 bg-gray-800 h-full text-white transition-all duration-300 z-10 sidebar flex flex-col">
    <div class="flex-grow overflow-y-auto">
        <ul class="py-4 space-y-1">
            <li>
                <a href="/admin" class="flex items-center h-11 hover:bg-gray-700 px-4 font-bold">
                    <i class="fa-solid fa-gauge-high w-5 min-w-[20px] text-center"></i>
                    <span class="ml-2 text-sm tracking-wide truncate">{{ __('messages.admin_dashboard') }}</span>
                </a>
            </li>
            <li>
                <a href="/" class="flex items-center h-11 hover:bg-gray-700 px-4">
                    <i class="fa-solid fa-globe w-5 min-w-[20px] text-center"></i>
                    <span class="ml-2 text-sm tracking-wide truncate">{{ __('messages.admin_mainweb') }}</span>
                </a>
            </li>
            <li>
                <a href="/addmodel" class="flex items-center h-11 hover:bg-gray-700 px-4">
                    <i class="fa-solid fa-user-plus w-5 min-w-[20px] text-center"></i>
                    <span class="ml-2 text-sm tracking-wide truncate">{{ __('messages.admin_addmodell') }}</span>
                </a>
            </li>
            <li>
                <a href="#" id="profile-link" class="flex items-center h-11 hover:bg-gray-700 px-4">
                    <i class="fa-solid fa-users w-5 min-w-[20px] text-center"></i>
                    <span class="ml-2 text-sm tracking-wide truncate">{{ __('messages.admin_userlist') }}</span>
                </a>
            </li>
            <li>
                <a href="#" id="model-list-link" class="flex items-center h-11 hover:bg-gray-700 px-4">
                    <i class="fa-solid fa-user w-5 min-w-[20px] text-center"></i>
                    <span class="ml-2 text-sm tracking-wide truncate">{{ __('messages.admin_listofmodel') }}</span>
                </a>
            </li>

            <!-- Settings Dropdown -->
            <li x-data="{ open: false }">
                <button @click="open = !open" class="flex flex-row items-center justify-between w-full h-11 hover:bg-gray-700 px-4 focus:outline-none">
                    <div class="flex items-center">
                        <i class="fa-solid fa-gear w-5 min-w-[20px] text-center"></i>
                        <span class="ml-2 text-sm tracking-wide truncate">Settings</span>
                    </div>
                    <i :class="{ 'rotate-180': open }" class="fa-solid fa-chevron-down w-4 h-4 transform transition-transform duration-200"></i>
                </button>
                <ul x-show="open" class="pl-6 space-y-1 mt-1 transition-all" x-cloak>
                    <li>
                        <a href="#" id="featured-talent-link" class="flex items-center h-9 hover:bg-gray-700 px-2 text-sm rounded">
                            <i class="fa-solid fa-star w-4 text-center mr-2"></i>
                            Face of Models Setting
                        </a>
                    </li>
                    <li>
                        <a href="#" id="ourtalent-setting-link" class="flex items-center h-9 hover:bg-gray-700 px-2 text-sm rounded">
                            <i class="fa-solid fa-user-group w-4 text-center mr-2"></i>
                            Meet Our Talent Setting
                        </a>
                    </li>
                    <li>
                        <a href="#" id="populartalent-setting-link" class="flex items-center h-9 hover:bg-gray-700 px-2 text-sm rounded">
                            <i class="fa-solid fa-fire w-4 text-center mr-2"></i>
                            Popular Models Setting
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
    <p class="mb-4 px-5 py-3 hidden md:block text-center text-xs text-gray-400">
        Copyright &copy;2021
    </p>
</aside>


        <!-- Main Content -->
        <main class="flex-1 ml-14 mt-14 md:ml-64 p-6 bg-gray-900 min-h-screen">
            <div id="main-dashboard-content">
                <!-- Welcome Section -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-white mb-2">{{ __('messages.admin_welcome') }}</h1>
                    <p class="text-gray-400">{{ __('messages.admin_dashboard_desc') }}</p>
                </div>


                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-800 shadow-lg rounded-md flex items-center justify-between p-4 text-white font-medium transition-transform duration-200 hover:shadow-2xl hover:scale-105 cursor-pointer" id="visitor-card">
                        <div class="flex justify-center items-center w-14 h-14 bg-blue-600 rounded-full">
                            <svg width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="text-white"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl">{{ $visitorCount }}</p>
                            <p>{{ __('messages.admin_active_users') }}</p>
                        </div>
                    </div>
                    <div class="bg-gray-800 shadow-lg rounded-md flex items-center justify-between p-4 text-white font-medium transition-transform duration-200 hover:shadow-2xl hover:scale-105 cursor-pointer" id="model-card">
                        <div class="flex justify-center items-center w-14 h-14 bg-green-600 rounded-full">
                            <svg width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="text-white"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl">{{ $modelCount }}</p>
                            <p>{{ __('messages.admin_model_active') }}</p>
                        </div>
                    </div>
                    <div class="bg-gray-800 shadow-lg rounded-md flex items-center justify-between p-4 text-white font-medium transition-transform duration-200 hover:shadow-2xl hover:scale-105">
                        <div class="flex justify-center items-center w-14 h-14 bg-yellow-600 rounded-full">
                            <i class="fa-solid fa-image text-white text-xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl">{{ $portfolioCount ?? 0 }}</p>
                            <p>{{ __('messages.admin_portfolio_updates') }}</p>
                        </div>
                    </div>
                    <div class="bg-gray-800 shadow-lg rounded-md flex items-center justify-between p-4 text-white font-medium transition-transform duration-200 hover:shadow-2xl hover:scale-105">
                        <div class="flex justify-center items-center w-14 h-14 bg-purple-600 rounded-full">
                            <i class="fa-solid fa-users text-white text-xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl">{{ $clientCount ?? 0 }}</p>
                            <p>{{ __('messages.admin_client_users') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Quick Actions -->
                    <div class="bg-gray-800 rounded-lg p-6">
                        <h3 class="text-xl font-bold text-white mb-4">{{ __('messages.admin_quick_actions') }}</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <a href="/addmodel" class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors group">
                                <i class="fa-solid fa-user-plus text-blue-400 mr-3 group-hover:text-blue-300"></i>
                                <span class="text-white group-hover:text-gray-200">{{ __('messages.admin_addmodell') }}</span>
                            </a>
                            <button onclick="window.showUserList()" class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors group cursor-pointer text-left w-full">
                                <i class="fa-solid fa-users text-green-400 mr-3 group-hover:text-green-300"></i>
                                <span class="text-white group-hover:text-gray-200">{{ __('messages.admin_userlist') }}</span>
                            </button>
                            <button onclick="window.showModelList()" class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors group cursor-pointer text-left w-full">
                                <i class="fa-solid fa-user text-yellow-400 mr-3 group-hover:text-yellow-300"></i>
                                <span class="text-white group-hover:text-gray-200">{{ __('messages.admin_listofmodel') }}</span>
                            </button>
                            <button onclick="window.showFeaturedModels()" class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors group cursor-pointer text-left w-full">
                                <i class="fa-solid fa-star text-purple-400 mr-3 group-hover:text-purple-300"></i>
                                <span class="text-white group-hover:text-gray-200">{{ __('messages.face_of_models') }}</span>
                            </button>
                            <button onclick="window.showOurTalent()" class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors group cursor-pointer text-left w-full">
                                <i class="fa-solid fa-user-group text-indigo-400 mr-3 group-hover:text-indigo-300"></i>
                                <span class="text-white group-hover:text-gray-200">{{ __('messages.talent_meet') }}</span>
                            </button>
                            <button onclick="window.showPopularTalent()" class="flex items-center p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors group cursor-pointer text-left w-full">
                                <i class="fa-solid fa-fire text-red-400 mr-3 group-hover:text-red-300"></i>
                                <span class="text-white group-hover:text-gray-200">{{ __('messages.popular_models') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-gray-800 rounded-lg p-6">
                        <h3 class="text-xl font-bold text-white mb-4">{{ __('messages.admin_recent_activity') }}</h3>
                        <div class="space-y-3">
                            @if(isset($recentActivities) && is_array($recentActivities) && count($recentActivities) > 0)
                                @foreach($recentActivities as $activity)
                                <div class="flex items-center p-3 bg-gray-700 rounded-lg">
                                    <div class="w-2 h-2 bg-{{ $activity['color'] }}-400 rounded-full mr-3"></div>
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <i class="fa-solid {{ $activity['icon'] ?? 'fa-circle' }} text-{{ $activity['color'] }}-400 mr-2"></i>
                                            <p class="text-white text-sm">{{ $activity['message'] }}</p>
                                        </div>
                                        <p class="text-gray-400 text-xs ml-6">{{ $activity['time'] }}</p>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="text-center text-gray-400 py-4">
                                    <i class="fa-solid fa-info-circle text-2xl mb-2"></i>
                                    <p>{{ __('messages.admin_no_activity') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="bg-gray-800 rounded-lg p-6 mb-6">
                    <h3 class="text-xl font-bold text-white mb-4">{{ __('messages.admin_system_status') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                            <span class="text-white">{{ __('messages.admin_database') }}</span>
                            <span class="text-{{ $systemStatus['database']['color'] ?? 'green' }}-400 font-semibold">{{ $systemStatus['database']['status'] ?? 'Online' }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                            <span class="text-white">{{ __('messages.admin_storage') }}</span>
                            <span class="text-{{ $systemStatus['storage']['color'] ?? 'green' }}-400 font-semibold">{{ $systemStatus['storage']['status'] ?? 'Healthy' }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                            <span class="text-white">{{ __('messages.admin_last_activity') }}</span>
                            <span class="text-gray-400">{{ $systemStatus['last_backup'] ?? 'Never' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div id="visitor-chart-container" class="w-full bg-gray-800 rounded-lg p-6 mt-2 hidden" style="min-height:340px;">
                    <canvas id="visitorChart" style="width:100%;height:320px;"></canvas>
                </div>
                <div id="model-chart-container" class="w-full bg-gray-800 rounded-lg p-6 mt-2 hidden" style="min-height:340px;">
                    <canvas id="modelChart" style="width:100%;height:320px;"></canvas>
                </div>
            </div>
            <div id="profile-table-content" class="hidden">
                <div class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-bold mb-4">User List</h2>

                    <table class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('messages.admin_no') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('messages.admin_name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('messages.admin_email') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ROLE</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('messages.admin_last_active') }}</th>

                            </tr>
                        </thead>
                        <tbody class="bg-gray-900 divide-y divide-gray-700">
                            @foreach($users as $i => $u)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $i+1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $u->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $u->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $u->role }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(isset($u->is_online) && $u->is_online)
                                        <span class="text-green-400 font-bold">{{ __('messages.admin_online') }}</span>
                                    @elseif(isset($u->last_active) && $u->last_active)
                                        {{ \Carbon\Carbon::parse($u->last_active)->diffForHumans() }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="model-table-content" class="hidden">
                <div class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Model List</h2>
                    <table class="min-w-full divide-y divide-gray-700">
                    <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('messages.admin_no') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('messages.admin_name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('messages.admin_dom') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('messages.admin_status') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('messages.admin_portfolio') }}</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('messages.admin_edit') }}</th>

                            </tr>
                        </thead>
                        <tbody class="bg-gray-900 divide-y divide-gray-700">
                            @foreach($models as $i => $m)
                            <tr
                                class="row-hover cursor-pointer transition-colors duration-150"
                                data-id="{{ $m->id_model }}"
                                data-name="{{ $m->nama_model }}"
                                data-city="{{ $m->city ?? '' }}"
                                data-status="{{ $m->status ?? '' }}"
                                data-photo="{{ $m->photo ? asset('storage/' . $m->photo) : asset('img/profile.jpg') }}"
                                data-age="{{ $m->age ?? '' }}"
                                data-height="{{ $m->height ?? '' }}"
                                data-weight="{{ $m->weight ?? '' }}"
                                data-shoes="{{ $m->shoes_size ?? '' }}"
                                data-size="{{ $m->size ?? '' }}"
                                data-bust="{{ $m->bust ?? '' }}"
                                data-waist="{{ $m->waist ?? '' }}"
                                data-exp-val="{{ $m->experience_value ?? '' }}"
                                data-exp-unit="{{ $m->experience_unit ?? '' }}"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">{{ $i+1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $m->nama_model }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $m->city ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $m->status ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a href="{{ route('portofolio.detail', $m->id_model) }}" class="inline-flex items-center justify-center text-blue-400 hover:text-white" aria-label="View Portfolio">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a href="{{ route('models.edit', $m->id_model) }}" class="inline-flex items-center justify-center text-yellow-400 hover:text-white" aria-label="Edit Model">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Model Detail Tab (Slide-over) -->
            <div id="model-detail-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

            <div id="model-detail-tab" class="fixed top-14 right-0 w-full max-w-md h-[calc(100vh-56px)] bg-gray-800 text-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50">
            <div class="flex items-center justify-between p-4 border-b border-gray-700">
                <h3 class="text-lg font-bold">Model Detail</h3>
                <button id="close-model-detail" class="text-gray-300 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4 overflow-y-auto">
                <div class="flex items-center gap-4 mb-4">
                <img id="md-photo" src="" alt="Model Photo" class="w-20 h-20 rounded-full object-cover bg-gray-700">
                <div>
                    <div id="md-name" class="text-xl font-semibold">-</div>
                    <div id="md-city" class="text-sm text-gray-300">-</div>
                    <div id="md-status" class="text-xs mt-1 px-2 py-1 inline-block bg-gray-700 rounded-full">-</div>
                </div>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                <div><span class="text-gray-400">{{ __('messages.portofolio_age') }}:</span> <span id="md-age">-</span></div>
                <div><span class="text-gray-400">{{ __('messages.portofolio_height') }}:</span> <span id="md-height">-</span></div>
                <div><span class="text-gray-400">{{ __('messages.portofolio_weight') }}:</span> <span id="md-weight">-</span></div>
                <div><span class="text-gray-400">{{ __('messages.portofolio_shoes') }}:</span> <span id="md-shoes">-</span></div>
                <div><span class="text-gray-400">{{ __('messages.portofolio_size') }}:</span> <span id="md-size">-</span></div>
                <div><span class="text-gray-400">{{ __('messages.portofolio_bust') }}:</span> <span id="md-bust">-</span></div>
                <div><span class="text-gray-400">{{ __('messages.portofolio_waist') }}:</span> <span id="md-waist">-</span></div>
                <div><span class="text-gray-400">{{ __('messages.portofolio_exp') }}:</span> <span id="md-exp">-</span></div>
                </div>

                <div class="mt-5 flex gap-2">
                <a id="md-view" href="#" class="flex-1 inline-flex items-center justify-center gap-2 bg-white text-black px-4 py-2 rounded hover:bg-gray-200 transition">
                    <i class="fa-solid fa-eye"></i> Portfolio
                </a>
                <a id="md-edit" href="#" class="flex-1 inline-flex items-center justify-center gap-2 bg-yellow-500 text-black px-4 py-2 rounded hover:bg-yellow-400 transition">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                </a>
                </div>
                </div>
            </div>
            <div id="featured-talent-content" class="hidden">
                <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4 text-white">{{ __('messages.face_of_models') }}</h2>
                <form method="POST" action="{{ route('admin.featured-talents.save') }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    @for($i = 0; $i < 8; $i++)
                    <div class="flex flex-col gap-2" 
                        x-data="{ selected: '{{ old('model_ids.' . $i, $featured[$i]->id_model ?? '') }}' }">
                        <label class="text-white font-semibold">Model {{ $i + 1 }}</label>
                        <select name="model_ids[]" x-model="selected" class="w-full p-2 rounded bg-gray-700 text-white">
                        <option value="">-- Pilih Model --</option>
                        @foreach($models->sortBy('nama_model') as $model)
                            <option value="{{ $model->id_model }}">{{ $model->nama_model }}</option>
                        @endforeach
                        </select>

                        <template x-if="selected">
                        <img 
                            :src="'{{ asset('storage') }}/' + ({{ Js::from($models->pluck('photo', 'id_model')) }}[selected] ?? '')" 
                            class="w-full h-48 object-cover rounded">
                        </template>
                    </div>
                    @endfor
                </div>
                <div class="mt-6 text-center">
                    <button type="submit" class="bg-white text-black font-semibold px-6 py-2 rounded-full hover:bg-gray-300 transition">
                    {{ __('messages.save_image') }}
                    </button>
                </div>
                </form>
            </div>
            </div>

            <!-- Add more dashboard content here -->

            <div id="ourtalent-setting-content" class="hidden bg-gray-800 p-6 rounded-lg text-white max-w-3xl mx-auto mt-8">
        <h2 class="text-2xl font-bold mb-4">{{ __('messages.meet_our_talent_upload') }}</h2>
        <form method="POST" action="{{ route('admin.ourtalent.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-4 gap-4">
            @for ($i = 0; $i < 8; $i++)
            <div class="relative border-2 border-dashed border-gray-600 rounded-lg h-56 flex items-center justify-center bg-gray-700 group">
                <input type="file" name="images[]" class="hidden" accept="image/*" id="image-{{ $i }}">
                
                <label for="image-{{ $i }}" class="cursor-pointer flex flex-col items-center justify-center text-white group-hover:opacity-100 transition-opacity">
                <span class="text-4xl font-bold">+</span>
                <span class="text-sm mt-1">{{ __('messages.upload_image') }}</span>
                </label>

                <div class="absolute inset-0 hidden" id="preview-wrapper-{{ $i }}">
                <img id="preview-{{ $i }}" class="w-full h-full object-cover rounded-lg" />
                <div class="absolute bottom-2 right-2 flex gap-2">
                    <button type="button" onclick="editImage({{ $i }})" class="bg-white text-black px-2 py-1 text-xs rounded">Edit</button>
                    <button type="button" onclick="removeImage({{ $i }})" class="bg-red-600 text-white px-2 py-1 text-xs rounded">Delete</button>
                </div>
                </div>
            </div>
            @endfor
        </div>
        <button type="submit" class="mt-6 bg-blue-600 text-white px-4 py-2 rounded">{{ __('messages.save_image') }}</button>
        </form>
        </div>

        <div id="populartalent-setting-content" class="hidden bg-gray-800 p-6 rounded-lg text-white max-w-3xl mx-auto mt-8">
    <h2 class="text-2xl font-bold mb-4">{{ __('messages.popular_talent_upload') }}</h2>
    <form method="POST" action="{{ route('admin.populartalent.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-4 gap-4">
        @for ($i = 0; $i < 8; $i++)
    <div class="relative border-2 border-dashed border-gray-600 rounded-lg h-56 flex items-center justify-center bg-gray-700 group">
        <input type="file" name="images[]" class="hidden" accept="image/*" id="popular-image-{{ $i }}">
        <label for="popular-image-{{ $i }}" class="cursor-pointer flex flex-col items-center justify-center text-white group-hover:opacity-100 transition-opacity">
            <span class="text-4xl font-bold">+</span>
            <span class="text-sm mt-1">{{ __('messages.upload_image') }}</span>
        </label>
        @if(isset($items[$i]))
            <div class="absolute inset-0" id="popular-preview-wrapper-{{ $i }}">
                <img id="popular-preview-{{ $i }}" src="{{ asset('storage/' . $items[$i]->image) }}" class="w-full h-full object-cover rounded-lg" />
                <div class="absolute bottom-2 right-2 flex gap-2">
                    <button type="button" onclick="editPopularImage({{ $i }})" class="bg-white text-black px-2 py-1 text-xs rounded">Edit</button>
                    <button type="button" onclick="removePopularImage({{ $i }})" class="bg-red-600 text-white px-2 py-1 text-xs rounded">Delete</button>
                </div>
            </div>
        @else
            <div class="absolute inset-0 hidden" id="popular-preview-wrapper-{{ $i }}">
                <img id="popular-preview-{{ $i }}" class="w-full h-full object-cover rounded-lg" />
                <div class="absolute bottom-2 right-2 flex gap-2">
                    <button type="button" onclick="editPopularImage({{ $i }})" class="bg-white text-black px-2 py-1 text-xs rounded">Edit</button>
                    <button type="button" onclick="removePopularImage({{ $i }})" class="bg-red-600 text-white px-2 py-1 text-xs rounded">Delete</button>
                </div>
            </div>
        @endif
    </div>
@endfor
        </div>
        <button type="submit" class="mt-6 bg-green-600 text-white px-4 py-2 rounded">{{ __('messages.save_image') }}</button>
    </form>
    </div>

        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
    // Quick Actions Functions
    window.showUserList = function() {
        hideAllContent();
        document.getElementById('profile-table-content').classList.remove('hidden');
    };

    window.showModelList = function() {
        hideAllContent();
        document.getElementById('model-table-content').classList.remove('hidden');
    };

    window.showFeaturedModels = function() {
        hideAllContent();
        document.getElementById('featured-talent-content').classList.remove('hidden');
    };

    window.showOurTalent = function() {
        hideAllContent();
        document.getElementById('ourtalent-setting-content').classList.remove('hidden');
    };

    window.showPopularTalent = function() {
        hideAllContent();
        document.getElementById('populartalent-setting-content').classList.remove('hidden');
    };

    window.showDashboard = function() {
        hideAllContent();
        document.getElementById('main-dashboard-content').classList.remove('hidden');
    };

    function hideAllContent() {
        const sections = [
            'main-dashboard-content',
            'profile-table-content',
            'model-table-content', 
            'featured-talent-content',
            'ourtalent-setting-content',
            'populartalent-setting-content'
        ];
        
        sections.forEach(sectionId => {
            const section = document.getElementById(sectionId);
            if (section) section.classList.add('hidden');
        });
    }

    // Event listener untuk semua menu navigasi
    const menuLinks = {
        'profile-link': 'profile-table-content',
        'model-list-link': 'model-table-content',
        'featured-talent-link': 'featured-talent-content',
        'ourtalent-setting-link': 'ourtalent-setting-content',
        'populartalent-setting-link': 'populartalent-setting-content'
    };

    Object.entries(menuLinks).forEach(([linkId, contentId]) => {
        const link = document.getElementById(linkId);
        const content = document.getElementById(contentId);

        if (link && content) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                hideAllContent();
                content.classList.remove('hidden');
            });
        }
    });

    // Dashboard link
    const dashboardLink = document.querySelector('a[href="/admin"]');
    if (dashboardLink) {
        dashboardLink.addEventListener('click', function(e) {
            e.preventDefault();
            showDashboard();
        });
    }

    // Event listener untuk dashboard cards
    document.getElementById('visitor-card')?.addEventListener('click', function() {
        const chartContainer = document.getElementById('visitor-chart-container');
        chartContainer.classList.toggle('hidden');
        if (!window.visitorChartLoaded) {
            fetch('/admin/visitor-stats')
                .then(res => res.json())
                .then(data => {
                    const ctx = document.getElementById('visitorChart').getContext('2d');
                    window.visitorChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Visitor Count',
                                data: data.counts,
                                borderColor: 'rgba(59, 130, 246, 1)',
                                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                title: {
                                    display: true,
                                    text: 'Active Users',
                                    color: 'white',
                                    font: { size: 16, weight: 'bold' }
                                }
                            },
                            scales: {
                                x: { title: { display: true, text: 'Tanggal' } },
                                y: { title: { display: true, text: 'Jumlah Active Users' }, beginAtZero: true }
                            }
                        }
                    });
                    window.visitorChartLoaded = true;
                });
        }
    });

    document.getElementById('model-card')?.addEventListener('click', function() {
        const chartContainer = document.getElementById('model-chart-container');
        chartContainer.classList.toggle('hidden');
        if (!window.modelChartLoaded) {
            fetch('/admin/model-stats')
                .then(res => res.json())
                .then(data => {
                    const ctx = document.getElementById('modelChart').getContext('2d');
                    window.modelChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Model Active',
                                data: data.counts,
                                borderColor: 'rgba(239, 68, 68, 1)',
                                backgroundColor: 'rgba(239, 68, 68, 0.2)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                title: {
                                    display: true,
                                    text: 'Model Active',
                                    color: 'white',
                                    font: { size: 16, weight: 'bold' }
                                }
                            },
                            scales: {
                                x: { title: { display: true, text: 'Tanggal' } },
                                y: { title: { display: true, text: 'Jumlah Model Active' }, beginAtZero: true }
                            }
                        }
                    });
                    window.modelChartLoaded = true;
                });
        }
    });

    // Image preview functions
    window.readAndPreview = function(input, index) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('preview-' + index).src = e.target.result;
                document.getElementById('preview-wrapper-' + index).classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    };

    window.editImage = function(index) {
        document.getElementById('image-' + index).click();
    };

    window.removeImage = function(index) {
        const input = document.getElementById('image-' + index);
        input.value = '';
        document.getElementById('preview-wrapper-' + index).classList.add('hidden');
    };

    // Popular talent functions
    window.editPopularImage = function(index) {
        document.getElementById('popular-image-' + index).click();
    };

    window.removePopularImage = function(index) {
        const input = document.getElementById('popular-image-' + index);
        const wrapper = document.getElementById('popular-preview-wrapper-' + index);
        input.value = '';
        wrapper.classList.add('hidden');
    };

    // Setup image preview listeners
    for (let i = 0; i < 8; i++) {
        const imageInput = document.getElementById('image-' + i);
        if (imageInput) {
            imageInput.addEventListener('change', function () {
                readAndPreview(this, i);
            });
        }

        const popularImageInput = document.getElementById('popular-image-' + i);
        if (popularImageInput) {
            popularImageInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    const preview = document.getElementById('popular-preview-' + i);
                    const wrapper = document.getElementById('popular-preview-wrapper-' + i);
                    preview.src = URL.createObjectURL(file);
                    wrapper.classList.remove('hidden');
                }
            });
        }
    }

    // Model detail panel functions
    const table = document.querySelector('#model-table-content tbody');
    const panel = document.getElementById('model-detail-tab');
    const overlay = document.getElementById('model-detail-overlay');
    const closeBtn = document.getElementById('close-model-detail');

    if (table && panel) {
        function openPanel() {
            overlay.classList.remove('hidden');
            panel.classList.remove('translate-x-full');
        }
        
        function closePanel() {
            panel.classList.add('translate-x-full');
            overlay.classList.add('hidden');
        }

        overlay?.addEventListener('click', closePanel);
        closeBtn?.addEventListener('click', closePanel);
        document.addEventListener('keydown', (e) => { 
            if (e.key === 'Escape') closePanel(); 
        });

        table.querySelectorAll('tr').forEach(row => {
            row.addEventListener('click', (e) => {
                if (e.target.closest('a')) return;

                const d = row.dataset;
                document.getElementById('md-photo').src = d.photo || '';
                document.getElementById('md-name').textContent = d.name || '-';
                document.getElementById('md-city').textContent = d.city || '-';
                document.getElementById('md-status').textContent = d.status || '-';
                document.getElementById('md-age').textContent = d.age || '-';
                document.getElementById('md-height').textContent = d.height ? d.height + ' cm' : '-';
                document.getElementById('md-weight').textContent = d.weight ? d.weight + ' kg' : '-';
                document.getElementById('md-shoes').textContent = d.shoes || '-';
                document.getElementById('md-size').textContent = d.size || '-';
                document.getElementById('md-bust').textContent = d.bust ? d.bust + ' cm' : '-';
                document.getElementById('md-waist').textContent = d.waist ? d.waist + ' cm' : '-';
                document.getElementById('md-exp').textContent = (d.expVal && d.expUnit) ? (d.expVal + ' ' + d.expUnit) : (d.expVal || '-');

                const id = d.id;
                const view = document.getElementById('md-view');
                const edit = document.getElementById('md-edit');
                if (view) view.href = '/portofolio/' + id;
                if (edit) edit.href = '/models/' + id + '/edit';

                openPanel();
            });
        });
    }
    });
</script>
