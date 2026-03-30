<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login - Academy Next Top Model</title>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      font-family: "Open Sans", sans-serif;
    }
  </style>
</head>
<body class="bg-black min-h-screen">
  <div class="flex h-screen w-screen">
    <!-- Left Side - Image -->
    <div class="hidden md:flex w-1/2 h-full bg-black items-center justify-center p-0 m-0">
      <img src="{{ asset('img/login.png') }}" alt="Model" class="w-full h-full object-contain m-0 p-0" style="object-position: left;">
    </div>

    <!-- Right Side - Form -->
    <div class="w-full md:w-1/2 h-full flex items-center justify-start bg-black">
      <div class="max-w-md p-4 text-white mx-0 my-auto space-y-6 w-full ml-8">
        <a href="/" class="flex items-center text-white mb-6 text-sm">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
          {{ __('messages.back') }}
        </a>

        <h1 class="text-3xl font-semibold mb-2">{{ __('messages.login') }}</h1>
        <p class="text-gray-400 text-sm mb-6">{{ __('messages.welcome_back') }}</p>

        @if(session('google_data'))
          <div class="mb-4 p-3 bg-blue-900 bg-opacity-50 rounded-lg border border-blue-500">
            <p class="text-sm text-blue-200">
              <svg class="w-4 h-4 inline mr-2" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.15 0 5.87 1.09 8.05 2.88l6-5.85C34.2 3.14 29.49 1 24 1 14.96 1 7.41 6.48 4.22 14.07l7.32 5.66C13.08 13.8 17.97 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.1 24.55c0-1.52-.14-2.99-.39-4.4H24v8.37h12.46c-.53 2.84-2.2 5.24-4.69 6.88l7.34 5.69c4.27-3.94 6.99-9.76 6.99-16.54z"/>
                <path fill="#FBBC05" d="M10.35 28.12A14.54 14.54 0 019.5 24c0-1.43.22-2.8.62-4.12L2.8 14.23C1.62 16.8 1 19.76 1 22.88s.62 6.07 1.8 8.64l7.55-5.4z"/>
                <path fill="#34A853" d="M24 47c6.48 0 11.94-2.14 15.92-5.8l-7.34-5.69c-2.06 1.39-4.7 2.21-8.58 2.21-6.03 0-11.13-4.3-12.95-10.07l-7.4 5.43C7.39 41.52 14.96 47 24 47z"/>
                <path fill="none" d="M0 0h48v48H0z"/>
              </svg>
              Silakan masukkan password untuk melanjutkan login
            </p>
          </div>
        @endif

        <form action="{{ route('auth.login') }}" method="POST">
          @csrf
          <div class="mb-4">
            <label class="block mb-1 text-sm">{{ __('messages.email_address') }}</label>
            <input type="email" name="email" value="{{ session('google_data.email') ?? old('email') }}" class="w-full px-3 py-2 rounded-lg bg-white text-black text-sm" required {{ session('google_data.email') ? 'readonly' : '' }}>
            @error('email')
                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
            @enderror
          </div>

          <div class="mb-4">
            <label class="block mb-1 text-sm">{{ __('messages.password') }}</label>
            <div class="relative">
              <input type="password" name="password" id="password" class="w-full px-3 py-2 rounded-lg bg-white text-black text-sm" required>
              <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2" onclick="togglePassword('password')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-500">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </button>
            </div>
            @error('password')
                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
            @enderror
          </div>

          <div class="flex items-center mb-6">
            <input type="checkbox" name="remember" class="mr-2">
            <span class="text-xs">{{ __('messages.remember_me') }}</span>
          </div>

          <div class="flex items-center justify-between">
            <div class="text-xs">
              {{ __('messages.no_account') }} <a href="{{ url('/register') }}" class="text-white underline hover:text-gray-300">{{ __('messages.register_here') }}</a>
            </div>
            <button type="submit" class="bg-white text-black px-5 py-2 rounded-lg text-sm font-medium flex items-center">
              {{ __('messages.login') }}
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 ml-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </button>
          </div>
          <div class="flex items-center my-6">
  <hr class="flex-grow border-gray-600">
  <span class="mx-4 text-gray-400 text-xs">{{ __('messages.or') }}</span>
  <hr class="flex-grow border-gray-600">
</div>
<div class="mt-6">
  <a href="{{ url('auth/google') }}" class="w-full inline-flex items-center justify-center gap-2 border border-white text-white px-4 py-2 rounded-lg text-sm hover:bg-white hover:text-black transition duration-200">
    <svg class="w-5 h-5" viewBox="0 0 48 48">
      <path fill="#EA4335" d="M24 9.5c3.15 0 5.87 1.09 8.05 2.88l6-5.85C34.2 3.14 29.49 1 24 1 14.96 1 7.41 6.48 4.22 14.07l7.32 5.66C13.08 13.8 17.97 9.5 24 9.5z"/>
      <path fill="#4285F4" d="M46.1 24.55c0-1.52-.14-2.99-.39-4.4H24v8.37h12.46c-.53 2.84-2.2 5.24-4.69 6.88l7.34 5.69c4.27-3.94 6.99-9.76 6.99-16.54z"/>
      <path fill="#FBBC05" d="M10.35 28.12A14.54 14.54 0 019.5 24c0-1.43.22-2.8.62-4.12L2.8 14.23C1.62 16.8 1 19.76 1 22.88s.62 6.07 1.8 8.64l7.55-5.4z"/>
      <path fill="#34A853" d="M24 47c6.48 0 11.94-2.14 15.92-5.8l-7.34-5.69c-2.06 1.39-4.7 2.21-8.58 2.21-6.03 0-11.13-4.3-12.95-10.07l-7.4 5.43C7.39 41.52 14.96 47 24 47z"/>
      <path fill="none" d="M0 0h48v48H0z"/>
    </svg>
    {{ __('messages.continue_with_google') }}
  </a>
</div>

        </form>
      </div>
    </div>
  </div>
  <script>
function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  const button = input.nextElementSibling;
  const icon = button.querySelector('svg');

  if (input.type === 'password') {
    input.type = 'text';
    icon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
    `;
  } else {
    input.type = 'password';
    icon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    `;
  }
}
</script>
</body>
</html>
