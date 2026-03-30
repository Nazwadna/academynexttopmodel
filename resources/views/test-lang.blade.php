<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Language Test - {{ app()->getLocale() }}</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .test-button { padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
        .english { background: blue; color: white; }
        .indonesia { background: red; color: white; }
        .dropdown { background: green; color: white; }
        .dropdown-content { margin-top: 10px; padding: 10px; background: #e0e0e0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🌐 Language Test Page</h1>
    
    <div class="debug-info">
        <h3>Debug Information:</h3>
        <p><strong>Current Locale:</strong> {{ app()->getLocale() }}</p>
        <p><strong>Session Locale:</strong> {{ session('locale') ?? 'null' }}</p>
        <p><strong>App Config Locale:</strong> {{ config('app.locale') }}</p>
        <p><strong>Available Locales:</strong> en, id</p>
    </div>
    
    <h2>Test Language Switching</h2>
    
    <!-- Simple test without Alpine.js -->
    <div style="margin: 20px 0;">
        <h3>Direct Links (No Alpine.js):</h3>
        <a href="{{ route('lang.switch', 'en') }}" class="test-button english">Switch to English</a>
        <a href="{{ route('lang.switch', 'id') }}" class="test-button indonesia">Switch to Indonesia</a>
    </div>
    
    <!-- Test with Alpine.js -->
    <div x-data="{ open: false }" style="margin: 20px 0;">
        <h3>Alpine.js Dropdown Test:</h3>
        <button @click="open = !open" class="test-button dropdown">
            Toggle Dropdown (Alpine.js) - Current: {{ app()->getLocale() }}
        </button>
        
        <div x-show="open" class="dropdown-content">
            <p>✅ Alpine.js dropdown is working!</p>
            <a href="{{ route('lang.switch', 'en') }}" style="display: block; margin: 5px 0; color: blue;">🇬🇧 English</a>
            <a href="{{ route('lang.switch', 'id') }}" style="display: block; margin: 5px 0; color: red;">🇮🇩 Indonesia</a>
        </div>
    </div>
    
    <h2>Messages Test</h2>
    <div class="debug-info">
        <p><strong>Home:</strong> {{ __('messages.home') }}</p>
        <p><strong>About Us:</strong> {{ __('messages.about_us') }}</p>
        <p><strong>Contact Us:</strong> {{ __('messages.contact_us') }}</p>
        <p><strong>Login:</strong> {{ __('messages.login') }}</p>
        <p><strong>Register:</strong> {{ __('messages.register') }}</p>
    </div>
    
    <h2>Route Test</h2>
    <div class="debug-info">
        <p><strong>Current URL:</strong> {{ request()->url() }}</p>
        <p><strong>Language Switch Route (EN):</strong> {{ route('lang.switch', 'en') }}</p>
        <p><strong>Language Switch Route (ID):</strong> {{ route('lang.switch', 'id') }}</p>
    </div>
    
    <script>
        console.log('=== Language Test Page Loaded ===');
        console.log('Current locale:', '{{ app()->getLocale() }}');
        console.log('Session locale:', '{{ session('locale') ?? 'null' }}');
        console.log('Alpine available:', typeof Alpine !== 'undefined');
        
        document.addEventListener('alpine:init', () => {
            console.log('✅ Alpine.js initialized successfully');
        });
        
        // Test click events
        document.querySelectorAll('a[href*="lang/"]').forEach(link => {
            link.addEventListener('click', function(e) {
                console.log('Language link clicked:', this.href);
            });
        });
    </script>
</body>
</html>
