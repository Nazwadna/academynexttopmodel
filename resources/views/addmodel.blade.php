<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{{ __('messages.addmodel_title') }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Fondamento&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/alpinejs" defer></script>
  <style>
    body { font-family: 'Open Sans', sans-serif; }
    .font-fondamento { font-family: 'Fondamento', cursive; }
  </style>
</head>
<body class="bg-black text-white">
  @include('navbar')

  <div class="flex items-center justify-center px-4 py-8 text-sm">
    <!-- Bagian Grid Utama -->
<div class="max-w-6xl w-full grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
  
  <!-- Kolom Kiri: Ilustrasi Gambar (Hanya tampil di md ke atas) -->
  <div class="w-full h-5/6 hidden md:block">
    <img src="{{ asset('img/login.jpg') }}" alt="Ilustrasi Model" class="w-full h-full object-cover rounded-lg shadow-lg">
  </div>

  <!-- Kolom Kanan: Form -->
  <form action="{{ route('models.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4" x-data="{ preview: null }">
    @csrf
    <h1 class="text-3xl font-fondamento text-center mb-2">{{ __('messages.addmodel_heading') }}</h1>

    @if ($errors->any())
      <div class="bg-red-600 text-white p-2 rounded mb-2 text-xs">
        <ul class="space-y-1">
          @foreach ($errors->all() as $error)
            <li>- {{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

<!-- Name tetap sendiri -->
<div class="mb-4">
  <label>{{ __('messages.addmodel_name') }}</label>
  <input type="text" name="nama_model" class="w-full p-1.5 rounded text-black text-sm" required>
</div>

<!-- Mulai grid 2 kolom, termasuk City -->
<div class="grid grid-cols-2 gap-4 mt-4">
  <div>
    <label>{{ __('messages.addmodel_city') }}</label>
    <input type="text" name="city" class="w-full p-1.5 rounded text-black text-sm" required>
  </div>
  <div>
    <label>{{ __('messages.addmodel_height') }}</label>
    <input type="number" name="height" class="w-full p-1.5 rounded text-black text-sm" required>
  </div>
  <div>
    <label>{{ __('messages.addmodel_weight') }}</label>
    <input type="number" name="weight" class="w-full p-1.5 rounded text-black text-sm" required>
  </div>
  <div>
    <label>{{ __('messages.addmodel_age') }}</label>
    <input type="number" name="age" class="w-full p-1.5 rounded text-black text-sm" required>
  </div>
  <div>
    <label>{{ __('messages.addmodel_shoes') }}</label>
    <input type="number" name="shoes_size" class="w-full p-1.5 rounded text-black text-sm">
  </div>
  <div>
    <label>{{ __('messages.addmodel_size') }}</label>
    <input type="text" name="size" class="w-full p-1.5 rounded text-black text-sm">
  </div>
  <div>
    <label>{{ __('messages.addmodel_bust') }}</label>
    <input type="number" name="bust" class="w-full p-1.5 rounded text-black text-sm">
  </div>
  <div>
    <label>{{ __('messages.addmodel_waist') }}</label>
    <input type="number" name="waist" class="w-full p-1.5 rounded text-black text-sm">
  </div>
  
  <!-- Experience (gabungan input + select) -->
  <div class="col-span-2">
    <label>{{ __('messages.addmodel_experience') }}</label>
    <div class="flex gap-2">
      <input type="number" min="0" name="experience_value" class="w-1/2 p-1.5 rounded text-black text-sm" required>
      <select name="experience_type" class="w-1/2 p-1.5 rounded text-black text-sm" required>
        <option value="months">{{ __('messages.portofolio_months') }}</option>
        <option value="years">{{ __('messages.portofolio_years') }}</option>
      </select>
    </div>
  </div>
</div>

<!-- Kategori & Status (tetap 2 kolom langsung) -->
<div class="grid grid-cols-2 gap-4 mt-4">
  <div>
    <label>{{ __('messages.addmodel_category') }}</label>
    <select name="categories" class="w-full p-1.5 rounded text-black text-sm" required>
      <option value="">{{ __('messages.addmodel_select_category') }}</option>
      <option value="kids">{{ __('messages.editmodel_kids') }}</option>
      <option value="teens">{{ __('messages.editmodel_teens') }}</option>
    </select>
  </div>
  <div>
    <label>{{ __('messages.addmodel_status') }}</label>
    <select name="status" class="w-full p-1.5 rounded text-black text-sm" required>
      <option value="">{{ __('messages.addmodel_select_status') }}</option>
      <option value="available">{{ __('messages.editmodel_available') }}</option>
      <option value="unavailable">{{ __('messages.editmodel_unavailable') }}</option>
    </select>
  </div>
</div>


    <!-- Upload Foto -->
    <div>
      <label class="block text-sm font-semibold mb-1">{{ __('messages.addmodel_photo_label') }}</label>
      <label for="photo" class="relative flex items-center justify-center w-full h-48 border-2 border-dashed rounded-md cursor-pointer hover:bg-gray-800 transition">
        <div class="text-gray-400 text-xs text-center" x-show="!preview">
          <svg aria-hidden="true" class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
          </svg>
          {{ __('messages.addmodel_upload_text') }}
        </div>
        <div class="absolute w-24 h-24 rounded-md overflow-hidden" x-show="preview">
          <img :src="preview" alt="Preview" class="object-cover w-full h-full">
        </div>
        <input id="photo" type="file" name="photo" accept="image/*" class="hidden"
          @change="const file = $event.target.files[0]; preview = URL.createObjectURL(file)">
      </label>
    </div>

    <!-- Submit Button -->
    <div class="text-center mt-3">
      <button type="submit" class="bg-white text-black px-6 py-2 rounded font-semibold text-sm hover:bg-gray-200">
        {{ __('messages.addmodel_submit') }}
      </button>
    </div>
  </form>
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
