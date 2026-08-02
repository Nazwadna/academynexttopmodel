<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academy NTM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
        }
        
        /* Navbar Animations */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .navbar-animate {
            animation: slideInDown 0.6s ease-out forwards;
        }
        
        .nav-link-hover {
            transition: all 0.3s ease;
        }
        
        .nav-link-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255,255,255,0.1);
        }
        
        .logo-animate {
            transition: transform 0.3s ease;
        }
        
        .logo-animate:hover {
            transform: scale(1.05);
        }
        
        .hamburger-animate {
            transition: all 0.3s ease;
        }
        
        .hamburger-animate:hover {
            transform: scale(1.1);
        }
        
        /* Smooth scrolling and scroll offset */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 100px;
        }
        
        section[id] {
            scroll-margin-top: 100px;
        }
        
        /* Active nav link indicator */
        .nav-link-active {
            background-color: white !important;
            color: black !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255,255,255,0.2);
        }
        
        /* Alpine.js x-cloak */
        [x-cloak] { 
            display: none !important; 
        }
        
        /* Dropdown specific styles */
        .language-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .language-dropdown ul {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            min-width: 144px;
        }
        
        .language-dropdown li {
            list-style: none;
        }
        
        .language-dropdown a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            color: black;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .language-dropdown a:hover {
            background-color: #f3f4f6;
        }
        
        /* Ensure dropdown is clickable */
        .language-dropdown button {
            cursor: pointer !important;
            user-select: none;
        }
        
        .language-dropdown button:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        
        /* Ensure Indonesia link is clickable */
        #indonesia-link,
        #indonesia-link-mobile {
            cursor: pointer !important;
            pointer-events: auto !important;
            position: relative;
            z-index: 1000;
        }
        
        #indonesia-link:hover,
        #indonesia-link-mobile:hover {
            background-color: #f3f4f6 !important;
        }
        
        /* Debug styles */
        .language-dropdown a {
            position: relative;
            z-index: 1000;
        }
        
        .language-dropdown li {
            position: relative;
            z-index: 1000;
        }
        
        /* Override any conflicting styles from welcome page */
        .language-dropdown {
            position: relative !important;
            z-index: 10051 !important; /* Higher than navbar and hero section */
        }
        
        .language-dropdown ul {
            position: absolute !important;
            z-index: 10052 !important; /* Higher than navbar and hero section */
            pointer-events: auto !important;
        }
        
        .language-dropdown button {
            position: relative !important;
            z-index: 10051 !important;
            pointer-events: auto !important;
        }
        
        /* Ensure dropdown is above all other elements */
        .language-dropdown * {
            pointer-events: auto !important;
        }
        
        /* Specific override for welcome page */
        body .language-dropdown {
            z-index: 10051 !important;
        }
        
        body .language-dropdown ul {
            z-index: 10052 !important;
        }
        
        /* Ensure Indonesia links are above everything */
        #indonesia-link,
        #indonesia-link-mobile {
            z-index: 10053 !important;
            position: relative !important;
            pointer-events: auto !important;
        }
        
        /* Welcome page specific overrides */
        body:has(.hero-image) .language-dropdown,
        body:has([data-scroll-animation]) .language-dropdown {
            z-index: 10051 !important;
        }
        
        body:has(.hero-image) .language-dropdown ul,
        body:has([data-scroll-animation]) .language-dropdown ul {
            z-index: 10052 !important;
        }
        
        /* Force navbar to be above hero section */
        header {
            position: relative !important;
            z-index: 10050 !important;
        }
        
        /* Ensure dropdown is above all hero elements */
        .language-dropdown {
            position: relative !important;
            z-index: 10051 !important;
        }
        
        .language-dropdown ul {
            position: absolute !important;
            z-index: 10052 !important;
        }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        // Debug Alpine.js
        document.addEventListener('alpine:init', () => {
        });
        
        // Check if Alpine.js loaded
        window.addEventListener('load', () => {
            if (typeof Alpine === 'undefined') {
                console.error('❌ Alpine.js failed to load!');
                // Fallback: use vanilla JavaScript for dropdown
                initFallbackDropdown();
            } else {
            }
        });
        
        // Welcome page specific debugging
        if (window.location.pathname === '/' || window.location.pathname === '/welcome') {
          
          // Force dropdown to be above loading screen and hero section
          setTimeout(() => {
            const dropdowns = document.querySelectorAll('.language-dropdown');
            dropdowns.forEach((dropdown, index) => {
              dropdown.style.zIndex = '10051';
              dropdown.style.position = 'relative';
              
              const button = dropdown.querySelector('button');
              if (button) {
                button.style.zIndex = '10051';
                button.style.position = 'relative';
              }
              
              const ul = dropdown.querySelector('ul');
              if (ul) {
                ul.style.zIndex = '10052';
                ul.style.position = 'absolute';
              }
            });
          }, 2000); // Wait for page to fully load
        }
    </script>
</head>
<body>
<header class="flex justify-between items-center px-4 md:px-12 py-5 bg-black z-[10050] font-['Open_Sans'] navbar-animate">
  <!-- Logo -->
  <div class="logo flex-shrink-0 logo-animate">
    <img src="{{ asset('img/logo.jpg') }}" alt="Logo" class="h-16">
  </div>
  <!-- Hamburger (mobile & tablet) -->
  <button id="hamburgerBtn" class="md:hidden flex flex-col justify-center items-center w-10 h-10 focus:outline-none hamburger-animate" aria-label="Open Menu">
    <span class="block w-7 h-0.5 bg-white mb-1"></span>
    <span class="block w-7 h-0.5 bg-white mb-1"></span>
    <span class="block w-7 h-0.5 bg-white"></span>
  </button>
  <!-- Menu Desktop -->
  <nav class="hidden md:block flex-1">
    <ul class="flex list-none gap-1.5 items-center text-sm tracking-wider justify-center">
      <li><a href="{{ url('/') }}" class="text-white px-4 py-2 rounded-lg transition duration-300 hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.home') }}</a></li>
      <li><a href="{{ url('/#aboutus') }}" class="text-white px-4 py-2 rounded-lg transition duration-300 hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.about_us') }}</a></li>
      <li><a href="{{ url('/joinacademy') }}" class="text-white px-4 py-2 rounded-lg transition duration-300 hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.model_course') }}</a></li>
      <li><a href="{{ url('/models') }}" class="text-white px-4 py-2 rounded-lg transition duration-300 hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.models') }}</a></li>
      <li><a href="{{ url('/#contactus') }}" class="text-white px-4 py-2 rounded-lg transition duration-300 hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.contact_us') }}</a></li>
      @auth
        @if(auth()->user()->role === 'admin')
          <li><a href="{{ url('/admin') }}" class="text-white px-4 py-2 rounded-lg transition duration-300 hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.admin') }}</a></li>
        @endif
      @endauth
      <li class="relative language-dropdown" x-data="{ open: false }" @click.away="open = false">
        <button @click="open = !open"
          class="flex items-center gap-2 px-4 py-2 text-white rounded-lg hover:bg-white hover:text-black font-medium focus:outline-none nav-link-hover"
          style="cursor: pointer;">
          <span class="inline-block w-5 h-5 rounded-full overflow-hidden align-middle">
            <img src="https://flagcdn.com/24x18/gb.png" alt="English"
              class="w-5 h-5 rounded-full border border-gray-300"
              style="display: {{ app()->getLocale() == 'en' ? 'inline' : 'none' }};" />
            <img src="https://flagcdn.com/24x18/id.png" alt="Indonesia"
              class="w-5 h-5 rounded-full border border-gray-300"
              style="display: {{ app()->getLocale() == 'id' ? 'inline' : 'none' }};" />
          </span>
          <span>{{ strtoupper(app()->getLocale()) }}</span>
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Dropdown -->
        <ul x-show="open" 
          class="absolute right-0 mt-2 w-36 bg-white rounded-lg shadow-xl py-2 z-[9999] border border-gray-200"
          x-cloak
          style="min-width: 144px;">
          <li>
            <a href="{{ route('lang.switch', 'en') }}"
              class="flex items-center gap-2 px-4 py-2 text-black hover:bg-gray-100 transition-colors duration-200"
              style="text-decoration: none; display: block;">
              <img src="https://flagcdn.com/24x18/gb.png" alt="English"
                class="w-5 h-5 rounded-full border border-gray-300" />
              English
            </a>
          </li>
          <li>
            <a href="{{ route('lang.switch', 'id') }}"
              class="flex items-center gap-2 px-4 py-2 text-black hover:bg-gray-100 transition-colors duration-200"
              style="text-decoration: none; display: block; cursor: pointer; pointer-events: auto;"
              id="indonesia-link">
              <img src="https://flagcdn.com/24x18/id.png" alt="Indonesia"
                class="w-5 h-5 rounded-full border border-gray-300" />
              Indonesia
            </a>
          </li>
        </ul>
      </li>

    </ul>
  </nav>
  <!-- Auth Desktop -->
  <div class="hidden md:flex items-center gap-2">
    @guest
      <a href="{{ url('/login') }}" class="bg-gray-200 text-black text-sm px-4 py-1.5 rounded nav-link-hover">{{ __('messages.loginbutton') }}</a>
      <a href="{{ url('/register') }}" class="bg-[#2C2C2C] text-white text-sm px-4 py-1.5 rounded nav-link-hover">{{ __('messages.regisbutton') }}</a>
    @else
      <div class="flex items-end gap-3">
        <a href="{{ route('profile.edit') }}" class="bg-white text-black text-sm px-4 py-1.5 rounded font-semibold nav-link-hover">{{ __('messages.editprofilebutton') }}</a>
        <form action="{{ route('auth.logout') }}" method="POST" class="m-0">
          @csrf
          <button type="submit" class="bg-[#2C2C2C] text-white text-sm px-4 py-1.5 rounded nav-link-hover">{{ __('messages.logoutbutton') }}</button>
        </form>
      </div>
    @endguest
  </div>
</header>
<hr class="border-t border-gray-700 shadow-sm m-0 p-0">
<!-- Mobile/Tablet Dropdown Menu -->
<div id="mobileMenu" class="w-full bg-black shadow-lg py-0 px-0 flex flex-col gap-4 md:hidden transition-all duration-300 max-h-0 overflow-hidden pointer-events-none font-['Open_Sans']">
  <ul class="flex flex-col gap-2">
    <li><a href="{{ url('/') }}" class="text-white block px-4 py-2 rounded-lg hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.home') }}</a></li>
    <li><a href="{{ url('/#aboutus') }}" class="text-white block px-4 py-2 rounded-lg hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.about_us') }}</a></li>
    <li><a href="{{ url('/joinacademy') }}" class="text-white block px-4 py-2 rounded-lg hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.model_course') }}</a></li>
    <li><a href="{{ url('/models') }}" class="text-white block px-4 py-2 rounded-lg hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.models') }}</a></li>
    <li><a href="{{ url('/#contactus') }}" class="text-white block px-4 py-2 rounded-lg hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.contact_us') }}</a></li>
    @auth
      @if(auth()->user()->role === 'admin')
        <li><a href="{{ url('/admin') }}" class="text-white block px-4 py-2 rounded-lg hover:bg-white hover:text-black font-medium nav-link-hover">{{ __('messages.admin') }}</a></li>
      @endif
    @endauth
    <!-- Dropdown Bahasa dengan Alpine.js -->
    <li class="relative language-dropdown" x-data="{ open: false }" @click.away="open = false">
      <button @click="open = !open"
        class="flex items-center gap-2 px-4 py-2 text-white rounded-lg hover:bg-white hover:text-black font-medium focus:outline-none nav-link-hover"
        style="cursor: pointer;">
        <span class="inline-block w-5 h-5 rounded-full overflow-hidden align-middle">
          <img src="https://flagcdn.com/24x18/gb.png" alt="English"
            class="w-5 h-5 rounded-full border border-gray-300"
            style="display: {{ app()->getLocale() == 'en' ? 'inline' : 'none' }};" />
          <img src="https://flagcdn.com/24x18/id.png" alt="Indonesia"
            class="w-5 h-5 rounded-full border border-gray-300"
            style="display: {{ app()->getLocale() == 'id' ? 'inline' : 'none' }};" />
        </span>
        <span>{{ strtoupper(app()->getLocale()) }}</span>
        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      <!-- Dropdown -->
      <ul x-show="open" 
        class="absolute left-0 mt-2 w-36 bg-white rounded-lg shadow-xl py-2 z-[9999] border border-gray-200"
        x-cloak
        style="min-width: 144px;">
        <li>
          <a href="{{ route('lang.switch', 'en') }}"
            class="flex items-center gap-2 px-4 py-2 text-black hover:bg-gray-100 transition-colors duration-200"
            style="text-decoration: none; display: block;">
            <img src="https://flagcdn.com/24x18/gb.png" alt="English"
              class="w-5 h-5 rounded-full border border-gray-300" />
            English
          </a>
        </li>
        <li>
          <a href="{{ route('lang.switch', 'id') }}"
            class="flex items-center gap-2 px-4 py-2 text-black hover:bg-gray-100 transition-colors duration-200"
            style="text-decoration: none; display: block; cursor: pointer; pointer-events: auto;"
            id="indonesia-link-mobile">
            <img src="https://flagcdn.com/24x18/id.png" alt="Indonesia"
              class="w-5 h-5 rounded-full border border-gray-300" />
            Indonesia
          </a>
        </li>
      </ul>
    </li>

  </ul>
  <div class="flex gap-2 mt-2">
    @guest
      <a href="{{ url('/login') }}" class="bg-gray-200 text-black text-sm px-4 py-1.5 rounded w-1/2 text-center nav-link-hover">{{ __('messages.loginbutton') }}</a>
      <a href="{{ url('/register') }}" class="bg-[#2C2C2C] text-white text-sm px-4 py-1.5 rounded w-1/2 text-center nav-link-hover">{{ __('messages.regisbutton') }}</a>
    @else
      <div class="flex flex-col w-full">
        <a href="{{ route('profile.edit') }}" class="bg-white text-black text-sm px-4 py-1.5 rounded font-semibold w-full text-center mb-2 nav-link-hover">{{ __('messages.editprofilebutton') }}</a>
        <form action="{{ route('auth.logout') }}" method="POST" class="m-0 mt-2">
          @csrf
          <button type="submit" class="bg-[#2C2C2C] text-white text-sm px-4 py-1.5 rounded w-full nav-link-hover">{{ __('messages.logoutbutton') }}</button>
        </form>
      </div>
    @endguest
  </div>
</div>

<script>
  // Navbar animation on load
  document.addEventListener('DOMContentLoaded', function() {
    // Debug Alpine.js
    
    // Check if Alpine.js is loaded
    if (typeof Alpine === 'undefined') {
      console.error('Alpine.js not loaded!');
      // Fallback: use vanilla JavaScript for dropdown
      initFallbackDropdown();
    } else {
    }
    
    // Ensure Indonesia links are clickable
    setTimeout(() => {
      const indonesiaLinks = document.querySelectorAll('#indonesia-link, #indonesia-link-mobile');
      indonesiaLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          // Don't prevent default, let it navigate
        });
        
        // Ensure link is clickable
        link.style.pointerEvents = 'auto';
        link.style.cursor = 'pointer';
        link.style.position = 'relative';
        link.style.zIndex = '10053'; // Higher than dropdown
      });
      
      // Additional fix for welcome page
      const dropdowns = document.querySelectorAll('.language-dropdown');
      dropdowns.forEach(dropdown => {
        dropdown.style.zIndex = '10051';
        dropdown.style.position = 'relative';
        
        const dropdownUl = dropdown.querySelector('ul');
        if (dropdownUl) {
          dropdownUl.style.zIndex = '10052';
          dropdownUl.style.position = 'absolute';
          dropdownUl.style.pointerEvents = 'auto';
        }
        
        const dropdownButton = dropdown.querySelector('button');
        if (dropdownButton) {
          dropdownButton.style.zIndex = '10051';
          dropdownButton.style.position = 'relative';
          dropdownButton.style.pointerEvents = 'auto';
        }
      });
    }, 1000);
    
    // Add staggered animation to nav links
    const navLinks = document.querySelectorAll('.nav-link-hover');
    navLinks.forEach((link, index) => {
      link.style.opacity = '0';
      link.style.transform = 'translateY(-10px)';
      setTimeout(() => {
        link.style.transition = 'all 0.3s ease';
        link.style.opacity = '1';
        link.style.transform = 'translateY(0)';
      }, index * 50);
    });

    // Add hover effects to logo
    const logo = document.querySelector('.logo-animate');
    if (logo) {
      logo.addEventListener('mouseenter', () => {
        logo.style.transform = 'scale(1.05)';
      });
      
      logo.addEventListener('mouseleave', () => {
        logo.style.transform = 'scale(1)';
      });
    }

    // Add hover effects to hamburger button
    const hamburgerBtn = document.querySelector('.hamburger-animate');
    if (hamburgerBtn) {
      hamburgerBtn.addEventListener('mouseenter', () => {
        hamburgerBtn.style.transform = 'scale(1.1)';
      });
      
      hamburgerBtn.addEventListener('mouseleave', () => {
        hamburgerBtn.style.transform = 'scale(1)';
      });
    }

    // Enhanced smooth scrolling for navbar links
    const navbarLinks = document.querySelectorAll('a[href^="/#"]');
    navbarLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        const targetId = href.replace('/#', '');
        const target = document.getElementById(targetId);
        
        if (target) {
          // Calculate offset for navbar height
          const navbarHeight = document.querySelector('header')?.offsetHeight || 0;
          const targetPosition = target.offsetTop - navbarHeight - 20; // 20px extra padding
          
          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });
          
          // Add highlight animation to target section
          setTimeout(() => {
            target.style.transition = 'all 0.3s ease';
            target.style.transform = 'scale(1.02)';
            target.style.boxShadow = '0 0 30px rgba(255,255,255,0.3)';
            
            setTimeout(() => {
              target.style.transform = 'scale(1)';
              target.style.boxShadow = 'none';
            }, 300);
          }, 500);
          
          // Update active nav link
          navbarLinks.forEach(navLink => navLink.classList.remove('nav-link-active'));
          this.classList.add('nav-link-active');
          
          // Close mobile menu if open
          const mobileMenu = document.getElementById('mobileMenu');
          if (mobileMenu && mobileMenu.classList.contains('max-h-[600px]')) {
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            if (hamburgerBtn) {
              hamburgerBtn.click();
            }
          }
        }
      });
    });

    // Update active nav link based on scroll position
    window.addEventListener('scroll', () => {
      const sections = document.querySelectorAll('section[id]');
      const navLinks = document.querySelectorAll('a[href^="/#"]');
      
      let current = '';
      sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (window.pageYOffset >= sectionTop - 150) {
          current = section.getAttribute('id');
        }
      });
      
      navLinks.forEach(link => {
        link.classList.remove('nav-link-active');
        if (link.getAttribute('href') === `/#${current}`) {
          link.classList.add('nav-link-active');
        }
      });
    });
  });

  // Fallback dropdown function if Alpine.js fails
  function initFallbackDropdown() {
    
    const dropdownButtons = document.querySelectorAll('[x-data]');
    dropdownButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        const dropdown = this.nextElementSibling;
        if (dropdown && dropdown.tagName === 'UL') {
          const isVisible = dropdown.style.display === 'block';
          dropdown.style.display = isVisible ? 'none' : 'block';
        }
      });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      if (!e.target.closest('[x-data]')) {
        const dropdowns = document.querySelectorAll('[x-data] + ul');
        dropdowns.forEach(dropdown => {
          dropdown.style.display = 'none';
        });
      }
    });
  }
  
  // Additional fallback for welcome page
  function initWelcomePageDropdown() {
    
    // Force all language links to be clickable
    const languageLinks = document.querySelectorAll('a[href*="lang/"]');
    languageLinks.forEach(link => {
      link.style.pointerEvents = 'auto';
      link.style.cursor = 'pointer';
      link.style.position = 'relative';
      link.style.zIndex = '10053';
      
      link.addEventListener('click', function(e) {
        // Let the link navigate normally
      });
    });
    
    // Force dropdown to be visible and clickable
    const dropdowns = document.querySelectorAll('.language-dropdown');
    dropdowns.forEach(dropdown => {
      dropdown.style.zIndex = '10051';
      dropdown.style.position = 'relative';
      dropdown.style.pointerEvents = 'auto';
      
      const button = dropdown.querySelector('button');
      if (button) {
        button.style.zIndex = '10051';
        button.style.pointerEvents = 'auto';
        button.style.cursor = 'pointer';
      }
      
      const ul = dropdown.querySelector('ul');
      if (ul) {
        ul.style.zIndex = '10052';
        ul.style.position = 'absolute';
        ul.style.pointerEvents = 'auto';
      }
    });
  }
  
  // Initialize welcome page fixes
  if (window.location.pathname === '/' || window.location.pathname === '/welcome') {
    setTimeout(initWelcomePageDropdown, 3000);
  }
</script>
</body>
</html> 
