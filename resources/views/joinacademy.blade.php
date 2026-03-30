<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.join_academy_title') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Fondamento&family=Newsreader:wght@400;600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black min-h-screen">
    @include('navbar')

    <div class="min-h-screen flex items-center justify-center">
        <div class="min-h-screen flex flex-col md:flex-row">
            <!-- Left: Image -->
            <div class="md:w-1/2 w-full h-[400px] md:h-auto">
                <img src="{{ asset('img/join.jpg') }}" alt="Join Academy" class="object-cover w-full h-full md:rounded-none rounded-b-2xl" />
            </div>

            <!-- Right: Content -->
            <div class="md:w-1/2 w-full bg-black flex flex-col justify-center px-6 md:px-12 py-10 text-white font-[Open Sans]">
                <div class="max-w-lg mx-auto">
                    <h1 class="font-['Fondamento'] text-4xl md:text-5xl font-bold mb-2 leading-tight">
                        {{ __('messages.join_academy_title') }}
                    </h1>
                    <p class="text-gray-300 text-base mb-6 mt-1">
                        {{ __('messages.join_academy_since') }}
                    </p>

                    <div class="flex flex-col md:flex-row gap-8 mb-6">
                        <!-- Materi Class -->
                        <div>
                            <h2 class="font-bold text-lg mb-2">{{ __('messages.join_academy_materi_class') }}</h2>
                            <ul class="list-disc list-inside space-y-1 text-sm">
                                <li>{{ __('messages.join_academy_catwalk_basic') }}</li>
                                <li>{{ __('messages.join_academy_catwalk_advanced') }}</li>
                                <li>{{ __('messages.join_academy_runway_class') }}</li>
                                <li>{{ __('messages.join_academy_beauty_class') }}</li>
                                <li>{{ __('messages.join_academy_pose_class') }}</li>
                                <li>{{ __('messages.join_academy_photoshoot') }}</li>
                                <li>{{ __('messages.join_academy_self_branding') }}</li>
                                <li>{{ __('messages.join_academy_public_speaking') }}</li>
                            </ul>
                        </div>

                        <!-- Persyaratan & Schedule -->
                        <div>
                            <h2 class="font-bold text-lg mb-2">{{ __('messages.join_academy_requirements') }}</h2>
                            <ul class="list-disc list-inside space-y-1 text-sm">
                                <li>{{ __('messages.join_academy_date') }}</li>
                                <li>{{ __('messages.join_academy_fill_form') }}</li>
                                <li>{{ __('messages.join_academy_photo') }}</li>
                                <li>{{ __('messages.join_academy_payment') }}</li>
                            </ul>
                            <h2 class="font-bold text-lg mt-4 mb-2">{{ __('messages.join_academy_schedule') }}</h2>
                            <ul class="list-disc list-inside space-y-1 text-sm">
                                <li>{{ __('messages.join_academy_day') }}</li>
                                <li>{{ __('messages.join_academy_time') }}</li>
                                <li>{{ __('messages.join_academy_location') }}</li>
                            </ul>
                        </div>
                    </div>

                    <h2 class="font-bold text-lg mb-2">{{ __('messages.join_academy_register') }}</h2>
                    <div class="mb-2">
                        <span class="block text-lg font-semibold">Rp. 3.000.000,00</span>
                        <span class="block text-base text-[#b3b3b3] font-semibold">{{ __('messages.join_academy_discount') }}</span>
                        <span class="block text-2xl font-bold text-[#fff]">{{ __('messages.join_academy_promo_price') }}</span>
                    </div>

                    <div class="mt-6 flex items-center">
                    @php
                        $pesan2 = "Halo, saya ingin mendaftar di Academy Next Top Model. Tolong berikan saya informasi lebih lanjut mengenai pendaftaran dan jadwalnya. Terima kasih.";
                    @endphp

          <a href="https://api.whatsapp.com/send?phone=6281214237742&text={{ urlencode($pesan2) }}" target="_blank" class="flex items-center bg-white text-black px-6 py-2 rounded-full font-semibold text-sm shadow hover:bg-gray-200 transition">
                            {{ __('messages.join_academy_register_now') }}
                            <span class="ml-2">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="white" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline w-5 h-5">
                                    <circle cx="12" cy="12" r="11" fill="white" stroke="black" stroke-width="2"/>
                                    <path d="M10 8l4 4-4 4" stroke="black" stroke-width="2" fill="none"/>
                                </svg>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
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
    </script>
</body>
</html>
