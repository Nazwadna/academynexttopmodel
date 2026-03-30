<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dropdown Test</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .test-container { background: white; padding: 20px; margin: 10px; border-radius: 8px; }
        .dropdown-test { background: #333; color: white; padding: 15px; border-radius: 5px; }
        .dropdown-content { background: white; color: black; padding: 10px; margin-top: 10px; border-radius: 5px; }
        .language-link { display: block; padding: 8px; margin: 5px 0; background: #f8f8f8; border-radius: 3px; text-decoration: none; color: #333; }
        .language-link:hover { background: #e0e0e0; }
    </style>
</head>
<body>
    <h1>🧪 Dropdown Test Page</h1>
    
    <div class="test-container">
        <h2>Test 1: Alpine.js Dropdown</h2>
        <div x-data="{ open: false }" class="dropdown-test">
            <button @click="open = !open" style="background: #007bff; color: white; padding: 10px; border: none; border-radius: 3px; cursor: pointer;">
                Language Dropdown (Alpine.js) - Current: {{ app()->getLocale() }}
            </button>
            
            <div x-show="open" class="dropdown-content">
                <a href="{{ route('lang.switch', 'en') }}" class="language-link">🇬🇧 English</a>
                <a href="{{ route('lang.switch', 'id') }}" class="language-link">🇮🇩 Indonesia</a>
            </div>
        </div>
    </div>
    
    <div class="test-container">
        <h2>Test 2: Vanilla JavaScript Dropdown</h2>
        <div class="dropdown-test">
            <button onclick="toggleDropdown()" style="background: #28a745; color: white; padding: 10px; border: none; border-radius: 3px; cursor: pointer;">
                Language Dropdown (Vanilla JS) - Current: {{ app()->getLocale() }}
            </button>
            
            <div id="vanillaDropdown" class="dropdown-content" style="display: none;">
                <a href="{{ route('lang.switch', 'en') }}" class="language-link">🇬🇧 English</a>
                <a href="{{ route('lang.switch', 'id') }}" class="language-link">🇮🇩 Indonesia</a>
            </div>
        </div>
    </div>
    
    <div class="test-container">
        <h2>Test 3: Direct Links</h2>
        <a href="{{ route('lang.switch', 'en') }}" style="display: inline-block; background: #007bff; color: white; padding: 10px; margin: 5px; text-decoration: none; border-radius: 3px;">Switch to English</a>
        <a href="{{ route('lang.switch', 'id') }}" style="display: inline-block; background: #dc3545; color: white; padding: 10px; margin: 5px; text-decoration: none; border-radius: 3px;">Switch to Indonesia</a>
    </div>
    
    <div class="test-container">
        <h2>Current Status</h2>
        <p><strong>Current Locale:</strong> {{ app()->getLocale() }}</p>
        <p><strong>Session Locale:</strong> {{ session('locale') ?? 'null' }}</p>
        <p><strong>Home Text:</strong> {{ __('messages.home') }}</p>
        <p><strong>About Text:</strong> {{ __('messages.about_us') }}</p>
    </div>
    
    <script>
        console.log('=== Dropdown Test Page Loaded ===');
        console.log('Current locale:', '{{ app()->getLocale() }}');
        console.log('Alpine available:', typeof Alpine !== 'undefined');
        
        // Vanilla JavaScript dropdown
        function toggleDropdown() {
            const dropdown = document.getElementById('vanillaDropdown');
            const isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
            console.log('Vanilla dropdown toggled:', !isVisible);
        }
        
        // Test click events
        document.querySelectorAll('a[href*="lang/"]').forEach(link => {
            link.addEventListener('click', function(e) {
                console.log('Language link clicked:', this.href);
            });
        });
        
        // Alpine.js test
        document.addEventListener('alpine:init', () => {
            console.log('✅ Alpine.js initialized');
        });
    </script>
</body>
</html>
