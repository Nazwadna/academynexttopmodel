<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Academy Next Top Model</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Font + Tailwind -->
  <link href="https://fonts.googleapis.com/css2?family=Fondamento&family=Newsreader:wght@400;600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Custom Font Style -->
  <style>
    body {
      font-family: "Open Sans", sans-serif;
    }
    .font-fondamento { font-family: 'Fondamento', cursive; }

    .shadow-text {
  font-family: 'Fondamento', cursive;
  color: black;
  font-weight: bold;
  text-shadow:
    -0.5px -0.5px 0 white,
     0.5px -0.5px 0 white,
    -0.5px  0.5px 0 white,
     0.5px  0.5px 0 white;
}

    /* Animation Classes */
    .animate-fade-in-up {
      animation: fadeInUp 0.8s ease-out forwards;
    }
    
    .animate-pulse-slow {
      animation: pulse 3s ease-in-out infinite;
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.05);
      }
    }
    
    /* Instagram link animations */
    .instagram-link {
      transition: all 0.3s ease;
    }
    
    .instagram-link:hover {
      transform: translateY(-2px);
      text-shadow: 0 2px 4px rgba(48, 176, 199, 0.3);
    }
    
    .instagram-link:hover svg {
      filter: drop-shadow(0 2px 4px rgba(48, 176, 199, 0.5));
    }
    
    /* Instagram gradient effect */
    .instagram-link::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
      border-radius: 4px;
      z-index: -1;
    }
    
    .instagram-link:hover::before {
      opacity: 0.1;
    }
    
    /* Instagram specific hover effects */
    .instagram-link:hover {
      background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: instagramGlow 2s ease-in-out infinite alternate;
    }
    
    @keyframes instagramGlow {
      from {
        filter: drop-shadow(0 0 5px rgba(220, 39, 67, 0.3));
      }
      to {
        filter: drop-shadow(0 0 15px rgba(220, 39, 67, 0.6));
      }
    }
    
    /* Instagram icon animation */
    .instagram-link:hover svg {
      animation: instagramIconPulse 1s ease-in-out infinite;
    }
    
    @keyframes instagramIconPulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.2);
      }
    }
    
    /* Tooltip styles */
    .tooltip {
      position: relative;
    }
    
    .tooltip::after {
      content: '';
      position: absolute;
      top: 100%;
      left: 50%;
      transform: translateX(-50%);
      border: 5px solid transparent;
      border-top-color: black;
    }
  </style>
</head>
<body class="bg-black text-white">
    @include('navbar')

  <!-- === CATEGORY SECTION === -->
<div class="h-px w-full bg-white/10 my-6"></div>

<section class="px-4 sm:px-6 md:px-[60px] py-6 md:py-[40px]">
  <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6 md:mb-[30px]">
    <h1 class="text-[28px] sm:text-[36px] md:text-[48px] font-fondamento">
      {{ __('messages.models_title') }}
    </h1>

    <!-- Filter Tabs + Add Model + Search -->
    <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-[14px] sm:text-[16px] md:text-[18px]" id="filterTabs">
    @if(auth()->check() && auth()->user()->role === 'admin')
      <a href="{{ route('models.create') }}" class="ml-2 bg-[#004030] text-white px-3 py-1.5 sm:px-4 sm:py-2 rounded text-[12px] sm:text-[14px] hover:bg-[#005040]">
        {{ __('messages.models_addmodell') }}
      </a>
      @endif
      
      <span class="cursor-pointer font-bold underline" data-filter="all">{{ __('messages.models_all') }}</span>
      <span class="cursor-pointer" data-filter="kids">{{ __('messages.models_kids') }}</span>
      <span class="cursor-pointer" data-filter="teens">{{ __('messages.models_teens') }}</span>

      <!-- Search -->
      <span class="cursor-pointer" id="searchIcon">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1111.5 4.5a7.5 7.5 0 015.15 12.15z" />
        </svg>
      </span>

      <input type="text" id="searchInput" class="hidden ml-2 px-2 py-1 rounded text-black text-[14px] sm:text-[16px]" />

      
    </div>
  </div>

  <!-- Grid Model -->
  <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-[20px]" id="modelGrid">
    @foreach($models as $model)
    <a href="{{ route('portofolio.detail', $model->id_model) }}" class="block">
      <div class="relative group model-card" data-category="{{ $model->categories }}">
        <span class="absolute top-2 left-2 text-[18px] sm:text-[20px] md:text-[24px] font-[Fondamento] px-2 py-1 shadow-text ">
          {{ explode(' ', $model->nama_model)[0] }}
        </span>

        <img src="{{ $model->photo ? asset('storage/' . $model->photo) : asset('img/models_list/jasmine.png') }}" class="w-full aspect-square object-cover" />
        
        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition duration-300 flex items-center justify-center">
          <span class="text-white text-xs sm:text-sm">{{ __('messages.models_seeporto') }}</span>
        </div>
      </div>
    </a>
    @endforeach
  </div>
</section>

<!-- Footer -->
<footer class="w-full bg-white py-4 px-6 sm:px-8 flex flex-col sm:flex-row justify-between items-center gap-2 sm:gap-0 text-black text-[14px] sm:text-[16px] font-['Newsreader'] mt-16 md:mt-20" data-scroll-animation="fade-in-up" data-delay="200">
  <span class="animate-pulse-slow">{{ __('messages.footer') }}</span>
  <a href="https://www.instagram.com/academynexttopmodel?igsh=MTdmMDdyZXRxdjk3MA==" target="_blank" rel="noopener noreferrer" class="animate-pulse-slow hover:text-[#30B0C7] transition-colors duration-300 flex items-center gap-2 group instagram-link relative tooltip">
    <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-300" fill="currentColor" viewBox="0 0 24 24">
      <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
    </svg>
    {{ __('messages.instagram') }}
  </a>
</footer>


  <!-- Filtering Script -->
  <script>
    const tabs = document.querySelectorAll('#filterTabs span[data-filter]');
    const cards = Array.from(document.querySelectorAll('.model-card'));
    const grid = document.getElementById('modelGrid');
    const searchIcon = document.getElementById('searchIcon');
    const searchInput = document.getElementById('searchInput');

    function renderCards(filteredCards) {
      grid.innerHTML = '';
      filteredCards.forEach(card => {
        grid.appendChild(card.parentElement);
      });
    }

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('font-bold', 'underline'));
        tab.classList.add('font-bold', 'underline');
        const filter = tab.dataset.filter;
        let filtered = cards;
        if (filter !== 'all') {
          filtered = cards.filter(card => card.dataset.category === filter);
        }
        // Reset search
        searchInput.value = '';
        renderCards(filtered);
      });
    });

    searchIcon.addEventListener('click', () => {
      searchInput.classList.toggle('hidden');
      searchInput.focus();
    });
    searchInput.addEventListener('input', function() {
      const val = this.value.toLowerCase();
      let filtered = cards.filter(card => {
        const name = card.querySelector('span').textContent.toLowerCase();
        return name.includes(val);
      });
      renderCards(filtered);
    });

    const hamburgerBtn = document.getElementById('hamburgerBtn');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileLinks = mobileMenu.querySelectorAll('a');

  let menuOpen = false;
  hamburgerBtn.addEventListener('click', function() {
    menuOpen = !menuOpen;
    if (menuOpen) {
      mobileMenu.classList.remove('max-h-0', 'pointer-events-none', 'py-0', 'px-0');
      mobileMenu.classList.add('max-h-[600px]', 'py-4', 'px-6');
    } else {
      mobileMenu.classList.add('max-h-0', 'pointer-events-none', 'py-0', 'px-0');
      mobileMenu.classList.remove('max-h-[600px]', 'py-4', 'px-6');
    }
  });

  // Tutup menu saat link diklik
  mobileLinks.forEach(link => {
    link.addEventListener('click', function() {
      menuOpen = false;
      mobileMenu.classList.add('max-h-0', 'pointer-events-none', 'py-0', 'px-0');
      mobileMenu.classList.remove('max-h-[600px]', 'py-4', 'px-6');
    });
  });
  
  // Instagram link click animation
  const instagramLink = document.querySelector('.instagram-link');
  if (instagramLink) {
    // Add tooltip on hover
    instagramLink.addEventListener('mouseenter', function() {
      const tooltip = document.createElement('div');
      tooltip.className = 'absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 bg-black text-white text-xs rounded-lg opacity-0 transition-opacity duration-300 whitespace-nowrap z-50';
      tooltip.textContent = 'Follow us on Instagram!';
      tooltip.style.pointerEvents = 'none';
      
      this.appendChild(tooltip);
      
      setTimeout(() => {
        tooltip.style.opacity = '1';
      }, 100);
    });
    
    instagramLink.addEventListener('mouseleave', function() {
      const tooltip = this.querySelector('div');
      if (tooltip) {
        tooltip.style.opacity = '0';
        setTimeout(() => {
          if (tooltip.parentNode) {
            tooltip.parentNode.removeChild(tooltip);
          }
        }, 300);
      }
    });
    
    instagramLink.addEventListener('click', function(e) {
      // Add click animation
      this.style.transform = 'scale(0.95)';
      setTimeout(() => {
        this.style.transform = 'scale(1.05)';
        setTimeout(() => {
          this.style.transform = '';
        }, 150);
      }, 100);
      
      // Add success feedback
      const originalHTML = this.innerHTML;
      
      this.innerHTML = `
        <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-300" fill="currentColor" viewBox="0 0 24 24">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Opening Instagram...
      `;
      
      // Show success message
      const successMessage = document.createElement('div');
      successMessage.className = 'fixed top-4 right-4 bg-gradient-to-r from-purple-500 to-pink-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
      successMessage.innerHTML = `
        <div class="flex items-center">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
          </svg>
          Opening Instagram...
        </div>
      `;
      document.body.appendChild(successMessage);
      
      // Remove after 3 seconds
      setTimeout(() => {
        if (document.body.contains(successMessage)) {
          document.body.removeChild(successMessage);
        }
      }, 3000);
      
      // Restore original content
      setTimeout(() => {
        this.innerHTML = originalHTML;
      }, 2000);
    });
  }
  
  </script>

</body>
</html>
