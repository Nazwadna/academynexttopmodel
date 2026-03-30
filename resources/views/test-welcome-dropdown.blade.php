<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>Welcome Dropdown Test</title>
    <link href="https://fonts.googleapis.com/css2?family=Fondamento&family=Newsreader:wght@400;600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: "Open Sans", sans-serif;
        }
        h1.main {
            font-family: "Fondamento", cursive;
        }
        
        /* Simulate welcome page loading screen */
        body:not(.loaded)::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: black;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        body:not(.loaded)::after {
            content: 'Loading...';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 24px;
            z-index: 10000;
        }
        
        /* Simulate hero section with high z-index */
        .hero-simulation {
            position: relative;
            z-index: 20;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            padding: 100px 20px;
            margin: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .hero-title {
            position: relative;
            z-index: 30;
            font-size: 48px;
            font-weight: bold;
            color: white;
            margin-bottom: 20px;
        }
        
        .hero-image-sim {
            position: relative;
            z-index: 25;
            width: 200px;
            height: 200px;
            background: #333;
            border-radius: 50%;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        /* Test container */
        .test-container {
            background: white;
            color: black;
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            position: relative;
            z-index: 10001;
        }
    </style>
</head>
<body class="bg-black text-white min-h-screen">
    @include('navbar')
    
    <!-- Hero Section Simulation -->
    <div class="hero-simulation">
        <h1 class="hero-title">ACADEMY NEXT TOP MODEL</h1>
        <div class="hero-image-sim">Hero Image</div>
        <p style="position: relative; z-index: 30; color: white; font-size: 18px;">
            This simulates the hero section with high z-index elements
        </p>
    </div>
    
    <div class="test-container">
        <h1>🧪 Welcome Page Dropdown Test</h1>
        <p>This page simulates the welcome page environment with hero section z-index conflicts.</p>
        
        <h2>Current Status</h2>
        <p><strong>Current Locale:</strong> {{ app()->getLocale() }}</p>
        <p><strong>Session Locale:</strong> {{ session('locale') ?? 'null' }}</p>
        <p><strong>Home Text:</strong> {{ __('messages.home') }}</p>
        
        <h2>Test Instructions</h2>
        <ol>
            <li>Look at the navbar above (should be above hero section)</li>
            <li>Click the language dropdown (EN/ID button)</li>
            <li>Try to click "Indonesia" in the dropdown</li>
            <li>Check console for debugging information</li>
            <li>Verify dropdown appears above hero section</li>
        </ol>
        
        <h2>Debug Information</h2>
        <p><strong>Route URL:</strong> {{ route('lang.switch', 'id') }}</p>
        <p><strong>Current URL:</strong> {{ request()->url() }}</p>
        <p><strong>Page Type:</strong> Welcome Page Simulation with Hero Section</p>
        <p><strong>Navbar Z-Index:</strong> 10050</p>
        <p><strong>Dropdown Z-Index:</strong> 10051</p>
        <p><strong>Dropdown UL Z-Index:</strong> 10052</p>
        <p><strong>Indonesia Link Z-Index:</strong> 10053</p>
    </div>
    
    <script>
        console.log('=== Welcome Page Dropdown Test Loaded ===');
        console.log('Current locale:', '{{ app()->getLocale() }}');
        console.log('Route URL:', '{{ route('lang.switch', 'id') }}');
        
        // Simulate page loading
        setTimeout(() => {
            document.body.classList.add('loaded');
            console.log('Page loaded - loading screen removed');
        }, 2000);
        
        // Test dropdown functionality
        setTimeout(() => {
            const dropdowns = document.querySelectorAll('.language-dropdown');
            console.log('Found dropdowns:', dropdowns.length);
            
            dropdowns.forEach((dropdown, index) => {
                console.log(`Dropdown ${index}:`, dropdown);
                console.log(`Dropdown z-index:`, dropdown.style.zIndex);
                
                const button = dropdown.querySelector('button');
                if (button) {
                    console.log(`Dropdown ${index} button:`, button);
                    console.log(`Button z-index:`, button.style.zIndex);
                }
                
                const ul = dropdown.querySelector('ul');
                if (ul) {
                    console.log(`Dropdown ${index} ul:`, ul);
                    console.log(`UL z-index:`, ul.style.zIndex);
                }
            });
            
            // Test Indonesia links
            const indonesiaLinks = document.querySelectorAll('a[href*="lang/id"]');
            console.log('Found Indonesia links:', indonesiaLinks.length);
            
            indonesiaLinks.forEach((link, index) => {
                console.log(`Indonesia link ${index}:`, link.href);
                console.log(`Link z-index:`, link.style.zIndex);
                
                link.addEventListener('click', function(e) {
                    console.log('Indonesia link clicked:', this.href);
                });
            });
            
            // Check hero section z-index
            const heroSection = document.querySelector('.hero-simulation');
            if (heroSection) {
                console.log('Hero section z-index:', window.getComputedStyle(heroSection).zIndex);
            }
            
            const heroTitle = document.querySelector('.hero-title');
            if (heroTitle) {
                console.log('Hero title z-index:', window.getComputedStyle(heroTitle).zIndex);
            }
            
            // Check navbar z-index
            const navbar = document.querySelector('header');
            if (navbar) {
                console.log('Navbar z-index:', window.getComputedStyle(navbar).zIndex);
            }
        }, 3000);
    </script>
</body>
</html>
