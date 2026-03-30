<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{{ __('messages.editprofile_title') }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Fondamento&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      font-family: 'Open Sans', sans-serif;
    }
    .font-fondamento {
      font-family: 'Fondamento', cursive;
    }
  </style>
</head>
<body class="min-h-screen bg-black text-white">

  @include('navbar')

  <div class="grid grid-cols-1 md:grid-cols-2 min-h-screen">
    <!-- Left: Form -->
    <div class="flex items-center justify-center px-8 py-12">
      <div class="w-full max-w-md">
        <h2 class="text-3xl font-fondamento mb-6 text-white text-center">{{ __('messages.editprofile_heading') }}</h2>

        @if(session('success'))
          <div class="bg-green-100 text-green-800 p-2 rounded mb-4 text-center text-sm font-semibold">
            {{ session('success') }}
          </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-5">
          @csrf

          <div>
            <label class="block text-sm mb-1">{{ __('messages.editprofile_name') }}</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                   class="w-full rounded-md px-3 py-2 bg-white text-black focus:outline-none focus:ring-2 focus:ring-gray-800" required>
          </div>

          <div>
            <label class="block text-sm mb-1">{{ __('messages.editprofile_email') }}</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                   class="w-full rounded-md px-3 py-2 bg-white text-black focus:outline-none focus:ring-2 focus:ring-gray-800" required>
          </div>

          <div>
            <label class="block text-sm mb-1">{{ __('messages.editprofile_phone') }}</label>
            <input type="text" name="number_phone" value="{{ old('number_phone', $user->number_phone) }}"
                   class="w-full rounded-md px-3 py-2 bg-white text-black focus:outline-none focus:ring-2 focus:ring-gray-800">
          </div>

          <div>
            <label class="block text-sm mb-1">{{ __('messages.editprofile_password') }} <span class="text-xs text-gray-400">({{ __('messages.editprofile_optional') }})</span></label>
            <input type="password" name="password"
                   class="w-full rounded-md px-3 py-2 bg-white text-black focus:outline-none focus:ring-2 focus:ring-gray-800">
          </div>

          <div>
            <label class="block text-sm mb-1">{{ __('messages.editprofile_confirm_password') }}</label>
            <input type="password" name="password_confirmation"
                   class="w-full rounded-md px-3 py-2 bg-white text-black focus:outline-none focus:ring-2 focus:ring-gray-800">
          </div>

          <div class="flex items-center justify-between pt-4">
            <button type="submit" class="bg-white text-black px-5 py-2 rounded-md font-semibold hover:bg-gray-200 transition">
              {{ __('messages.editprofile_update_button') }}
            </button>
            <button type="button" onclick="confirmDeleteAccount()" class="text-red-500 text-sm font-semibold hover:underline">
              {{ __('messages.editprofile_delete_button') }}
            </button>
          </div>
        </form>

        <form id="delete-account-form" action="{{ route('profile.delete') }}" method="POST" class="hidden">
          @csrf
          @method('DELETE')
        </form>
      </div>
    </div>

    <!-- Right: Image -->
    <div class="hidden md:flex items-center justify-center">
      <img src="{{ asset('img/becomeamodel.png') }}" 
        alt="Model" 
        class="w-full h-5/6 object-contain mx-auto rounded-md shadow-lg">
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    function confirmDeleteAccount() {
      Swal.fire({
        title: '{{ __('messages.editprofile_delete_title') }}',
        text: '{{ __('messages.editprofile_delete_warning') }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '{{ __('messages.editprofile_delete_confirm') }}',
        background: '#1f1f1f',
        color: '#fff'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('delete-account-form').submit();
        }
      });
    }
  </script>

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
