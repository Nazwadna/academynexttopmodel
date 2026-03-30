<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indonesia Link Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .test-container { background: white; padding: 20px; margin: 10px; border-radius: 8px; }
        .test-link { display: inline-block; padding: 10px 15px; margin: 5px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; }
        .test-link:hover { background: #c82333; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h1>🇮🇩 Indonesia Link Test</h1>
    
    <div class="test-container">
        <h2>Test 1: Direct Link Test</h2>
        <a href="{{ route('lang.switch', 'id') }}" class="test-link" onclick="console.log('Direct Indonesia link clicked')">
            Switch to Indonesia (Direct)
        </a>
    </div>
    
    <div class="test-container">
        <h2>Test 2: JavaScript Enhanced Link</h2>
        <a href="{{ route('lang.switch', 'id') }}" 
           class="test-link" 
           id="js-indonesia-link"
           onclick="console.log('JS Indonesia link clicked'); return true;">
            Switch to Indonesia (JavaScript)
        </a>
    </div>
    
    <div class="test-container">
        <h2>Test 3: Form Submit Test</h2>
        <form action="{{ route('lang.switch', 'id') }}" method="GET" style="display: inline;">
            <button type="submit" class="test-link" style="border: none; cursor: pointer;">
                Switch to Indonesia (Form)
            </button>
        </form>
    </div>
    
    <div class="test-container">
        <h2>Current Status</h2>
        <div class="status {{ app()->getLocale() == 'id' ? 'success' : 'error' }}">
            <strong>Current Locale:</strong> {{ app()->getLocale() }}
            @if(app()->getLocale() == 'id')
                ✅ Indonesia is active
            @else
                ❌ Indonesia is not active
            @endif
        </div>
        <div class="status">
            <strong>Session Locale:</strong> {{ session('locale') ?? 'null' }}
        </div>
        <div class="status">
            <strong>Home Text:</strong> {{ __('messages.home') }}
        </div>
        <div class="status">
            <strong>Route URL:</strong> {{ route('lang.switch', 'id') }}
        </div>
    </div>
    
    <div class="test-container">
        <h2>Debug Information</h2>
        <p><strong>Route Exists:</strong> {{ Route::has('lang.switch') ? 'YES' : 'NO' }}</p>
        <p><strong>Current URL:</strong> {{ request()->url() }}</p>
        <p><strong>User Agent:</strong> {{ request()->userAgent() }}</p>
    </div>
    
    <script>
        console.log('=== Indonesia Link Test Page Loaded ===');
        console.log('Current locale:', '{{ app()->getLocale() }}');
        console.log('Route URL:', '{{ route('lang.switch', 'id') }}');
        
        // Test all Indonesia links
        document.querySelectorAll('a[href*="lang/id"]').forEach(link => {
            console.log('Found Indonesia link:', link.href);
            link.addEventListener('click', function(e) {
                console.log('Indonesia link clicked:', this.href);
            });
        });
        
        // Test form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            console.log('Form submitted to:', this.action);
        });
        
        // Test button click
        document.getElementById('js-indonesia-link').addEventListener('click', function(e) {
            console.log('JavaScript Indonesia link clicked');
        });
    </script>
</body>
</html>
