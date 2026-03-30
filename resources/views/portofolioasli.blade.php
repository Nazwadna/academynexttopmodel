<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Academy Next Top Model</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Font + Tailwind -->
  <link href="https://fonts.googleapis.com/css2?family=Fondamento&family=Newsreader:wght@400;600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <!-- PhotoSwipe CSS -->
<link rel="stylesheet" href="https://unpkg.com/photoswipe@5/dist/photoswipe.css">

  <script src="https://cdn.tailwindcss.com"></script>
  <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>




  <!-- Custom Font Style -->
  <style>
    body {
      font-family: "Open Sans", sans-serif;
    }
    .font-fondamento { font-family: 'Fondamento', cursive; }
  </style>

<script>
  function confirmDelete(id) {
    Swal.fire({
      title: 'Hapus model ini?',
      text: "Data akan dihapus secara permanen.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#666',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        document.getElementById('delete-form-' + id).submit();
      }
    });
  }
</script>

</head>
<!-- PhotoSwipe JS -->
<script src="https://unpkg.com/photoswipe@5/dist/photoswipe.umd.js"></script>
<body>
    @include('navbar')

    @guest
  <div class="fixed inset-0 z-50 bg-[#1f1f1f]/90 backdrop-blur-sm flex items-center justify-center px-4">
    <div class="bg-[#1f1f1f] text-white rounded-2xl p-6 w-full max-w-md shadow-xl text-center">
      <h3 class="text-xl font-bold mb-2">{{ __('messages.needlogin') }}</h3>
      <p class="text-sm text-gray-300 mb-4">{{ __('messages.needlogindesc') }}</p>
      <div class="flex gap-3">
        <a href="{{ route('login') }}"
          class="flex-1 bg-black text-white py-2 rounded-full transition
                  hover:bg-gray-800 hover:text-white">
                  {{ __('messages.loginbutton') }}
        </a>
        <a href="{{ url('/register') }}"
          class="flex-1 bg-white text-black py-2 rounded-full transition
                  hover:bg-gray-800 hover:text-white">
                  {{ __('messages.regisbutton') }}
        </a>
      </div>
      <a href="{{ url('/') }}" class="mt-4 inline-block text-xs text-gray-400 underline hover:text-gray-200">
      {{ __('messages.backtohomee') }}
        
      </a>
    </div>
  </div>
  @endguest
  
  @php $selected = isset($model) ? $model : $models->first(); @endphp
  @if($selected)
    <section class="bg-black text-white px-[50px] py-[60px] font-['Open_Sans']">
      <!-- Model Profile -->
      <div class="hidden md:block">
      <div class="flex items-center gap-[30px] mb-[30px]">
        <img src="{{ $selected->photo ? asset('storage/' . $selected->photo) : asset('img/profile.jpg') }}" alt="Model Profile" class="w-[240px] h-[240px] rounded-full object-cover" />
        <div>
        <button class="bg-white text-black text-[12px] font-bold rounded-full px-[10px] py-[3px] mb-[5px]">
    {{ $selected->experience_value }} {{ __('messages.portofolio_' . ($selected->experience_unit ?? 'year')) }} {{ __('messages.portofolio_exp') }}
</button>
          <h2 class="text-[26px] font-semibold mb-[4px]">{{ $selected->nama_model }}</h2>
          <p class="text-[16px] text-gray-300 mb-[6px]">{{ $selected->city }}</p>
          <p class="text-[16px] text-gray-400 mb-[10px]">
    {{ $selected->status == 'available' ? __('messages.portofolio_available') : __('messages.portofolio_not_available') }}
</p>
@php
                        $pesan3 = "Halo, saya tertarik untuk menggunakan jasa model dari Academy Next Top Model. Saya ingin menanyakan ketersediaan dan informasi lebih lanjut. Terima kasih.";
                    @endphp

          <a href="https://api.whatsapp.com/send?phone=6281214237742&text={{ urlencode($pesan3) }}" target="_blank" class="inline-block mt-[6px] bg-white text-black text-[12px] font-semibold px-[12px] py-[6px] rounded-full hover:bg-gray-200 transition-colors duration-300">{{ __('messages.portofolio_hire') }} ↗</a>
          <div class="flex items-center gap-3 mt-2">
            

<form id="delete-form-{{ $selected->id_model }}" action="{{ route('models.destroy', $selected->id_model) }}" method="POST" class="hidden">
  @csrf
  @method('DELETE')
</form>

          </div>
        </div>
      </div>
      <!-- Model Stats -->
      <div class="flex justify-around my-[30px] text-[24px] text-center">
      <div>{{ $selected->height }} cm<br><span class="text-[12px] text-gray-400">{{ __('messages.portofolio_height') }}</span></div>
        <div>{{ $selected->weight }} kg<br><span class="text-[12px] text-gray-400">{{ __('messages.portofolio_weight') }}</span></div>
        <div>{{ $selected->age }} th<br><span class="text-[12px] text-gray-400">{{ __('messages.portofolio_age') }}</span></div>
        <div>{{ $selected->shoes_size }}<br><span class="text-[12px] text-gray-400">{{ __('messages.portofolio_shoes') }}</span></div>
        <div>{{ $selected->size }}<br><span class="text-[12px] text-gray-400">{{ __('messages.portofolio_size') }}</span></div>
        <div>{{ $selected->bust }} cm<br><span class="text-[12px] text-gray-400">{{ __('messages.portofolio_bust') }}</span></div>
        <div>{{ $selected->waist }} cm<br><span class="text-[12px] text-gray-400">{{ __('messages.portofolio_waist') }}</span></div>
      </div>
      <!-- Portfolio Tabs -->
      <div id="portfolio-tabs" class="flex justify-center gap-[20px] text-[16px] font-bold pb-[15px] border-b border-white mb-[30px]">
      <span id="tab-portfolio" class="text-white border-b-2 border-white cursor-pointer" onclick="showTab('portfolio')">{{ __('messages.portofolio_tab_portfolio') }}</span>
  <span id="tab-career" class="text-gray-400 cursor-pointer" onclick="showTab('career')">{{ __('messages.portofolio_tab_career') }}</span>
  <span id="tab-awards" class="text-gray-400 cursor-pointer" onclick="showTab('awards')">{{ __('messages.portofolio_tab_awards') }}</span>
</div>
<div id="tab-content-portfolio">
  <!-- Gallery -->
  <div class="max-w-6xl mx-auto px-4 mb-[40px]">

    <!-- Baris 1: 1 kiri, 4 kanan (2x2 grid) -->
<div class="flex gap-4 mb-4">
  <!-- Card kiri persegi (slot 1) -->
  <div class="aspect-square flex-1 bg-gray-700 flex items-center justify-center relative overflow-hidden">
    @if(isset($portfolioSlots[1]) && $portfolioSlots[1])
      @php
        $size = $portfolioSizes[1] ?? ['width' => 1200, 'height' => 900];
      @endphp
      <a href="{{ asset('storage/' . $portfolioSlots[1]->photo) }}"
         data-pswp-width="{{ $size['width'] }}"
         data-pswp-height="{{ $size['height'] }}"
         data-pswp-index="0"
         target="_blank"
         class="block w-full h-full">
        <img src="{{ asset('storage/' . $portfolioSlots[1]->photo) }}" class="object-cover object-top w-full h-full" />
      </a>
    @endif
  </div>

  <!-- 4 card kanan dalam grid 2x2 (slot 2-5) -->
  <div class="grid grid-cols-2 grid-rows-2 gap-4 flex-1">
    @for($i = 2; $i <= 5; $i++)
      <div class="aspect-square bg-gray-700 flex items-center justify-center relative overflow-hidden">
        @if(isset($portfolioSlots[$i]) && $portfolioSlots[$i])
          @php
            $size = $portfolioSizes[$i] ?? ['width' => 1200, 'height' => 900];
          @endphp
          <a href="{{ asset('storage/' . $portfolioSlots[$i]->photo) }}"
             data-pswp-width="{{ $size['width'] }}"
             data-pswp-height="{{ $size['height'] }}"
             data-pswp-index="{{ $i - 1 }}"
             target="_blank"
             class="block w-full h-full">
            <img src="{{ asset('storage/' . $portfolioSlots[$i]->photo) }}" class="object-cover object-top w-full h-full" />
          </a>
        @endif
      </div>
    @endfor
  </div>
</div>

<!-- Baris 2: 4 kiri (2x2 grid, slot 6-9), 1 kanan persegi (slot 10) -->
<div class="flex gap-4">
  <div class="grid grid-cols-2 grid-rows-2 gap-4 flex-1">
    @for($i = 6; $i <= 9; $i++)
      <div class="aspect-square bg-gray-700 flex items-center justify-center relative overflow-hidden">
        @if(isset($portfolioSlots[$i]) && $portfolioSlots[$i])
          @php
            $size = $portfolioSizes[$i] ?? ['width' => 1200, 'height' => 900];
          @endphp
          <a href="{{ asset('storage/' . $portfolioSlots[$i]->photo) }}"
             data-pswp-width="{{ $size['width'] }}"
             data-pswp-height="{{ $size['height'] }}"
             data-pswp-index="{{ $i - 1 }}"
             target="_blank"
             class="block w-full h-full">
            <img src="{{ asset('storage/' . $portfolioSlots[$i]->photo) }}" class="object-cover object-top w-full h-full" />
          </a>
        @endif
      </div>
    @endfor
  </div>

  <div class="aspect-square flex-1 bg-gray-700 flex items-center justify-center relative overflow-hidden">
    @if(isset($portfolioSlots[10]) && $portfolioSlots[10])
      @php
        $size = $portfolioSizes[10] ?? ['width' => 1200, 'height' => 900];
      @endphp
      <a href="{{ asset('storage/' . $portfolioSlots[10]->photo) }}"
         data-pswp-width="{{ $size['width'] }}"
         data-pswp-height="{{ $size['height'] }}"
         data-pswp-index="9"
         target="_blank"
         class="block w-full h-full">
        <img src="{{ asset('storage/' . $portfolioSlots[10]->photo) }}" class="object-cover object-top w-full h-full" />
      </a>
    @endif
  </div>
</div>
  </div>

  <script>
    function confirmDeletePortfolio(slot) {
      Swal.fire({
        title: 'Hapus foto portofolio?',
        text: 'Foto akan dihapus secara permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#666',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('delete-form-' + slot).submit();
        }
      });
    }

    // PhotoSwipe init
    document.addEventListener("DOMContentLoaded", function () {
      const galleryElements = document.querySelectorAll('[data-pswp-index]');
      const items = [];

      galleryElements.forEach((el, index) => {
        items.push({
          src: el.getAttribute('href'),
          width: parseInt(el.getAttribute('data-pswp-width')),
          height: parseInt(el.getAttribute('data-pswp-height'))
        });

        el.addEventListener('click', function (e) {
          e.preventDefault();
          const pswp = new PhotoSwipe({
            dataSource: items,
            index: index,
          });
          pswp.init();
        });
      });
    });
  </script>
</div>

      <div id="tab-content-career" style="display:none;">
        <div class="max-w-6xl mx-auto px-4 mb-[40px]">
          <div class="grid grid-cols-3 grid-rows-2 gap-4">
            @for($i=1; $i<=6; $i++)
            <div class="w-full h-[450px] bg-gray-700 flex items-center justify-center relative overflow-hidden">
              @if(isset($careerSlots[$i]) && $careerSlots[$i])
                <img src="{{ asset('storage/' . $careerSlots[$i]->photo) }}" class="object-cover w-full h-full" />
                <div class="absolute bottom-2 left-2 bg-black bg-opacity-60 text-white text-xs px-2 py-1 rounded">
                  {{ $careerSlots[$i]->event_name }}<br>{{ $careerSlots[$i]->year }}
                </div>
                <!-- Edit Modal Form (hidden) -->
                <form action="{{ route('career.update', [$selected->id_model ?? $model->id_model, $i]) }}" method="POST" enctype="multipart/form-data" id="edit-career-form-{{ $i }}" style="display:none;">
                  @csrf
                  <input type="file" name="photo" accept="image/*" class="hidden" id="edit-career-upload-{{ $i }}">
                  <input type="hidden" name="event_name">
                  <input type="hidden" name="year">
                </form>
              @endif
            </div>
            @endfor
          </div>
        </div>
      </div>
      <div id="tab-content-awards" style="display:none;">
        <div class="max-w-6xl mx-auto px-4 mb-[40px]">
          <div class="grid grid-cols-2 grid-rows-3 gap-4">
            @for($i=1; $i<=6; $i++)
            <div class="w-full h-[350px] bg-gray-700 flex items-center justify-center relative overflow-hidden">
              @if(isset($awardSlots[$i]) && $awardSlots[$i])
                <img src="{{ asset('storage/' . $awardSlots[$i]->photo) }}" class="object-cover w-full h-full" />
                <div class="absolute top-2 right-2 flex gap-2 z-10">
                  <form action="{{ route('award.update', [$selected->id_model ?? $model->id_model, $i]) }}" method="POST" enctype="multipart/form-data" id="edit-award-form-{{ $i }}">
                    @csrf
                    <input type="file" name="photo" accept="image/*" class="hidden" onchange="this.form.submit()" id="edit-award-upload-{{ $i }}">
                    <label for="edit-award-upload-{{ $i }}" class="cursor-pointer bg-white text-black px-2 py-1 rounded text-xs font-bold">Edit</label>
                  </form>
                  <form action="{{ route('award.delete', [$selected->id_model ?? $model->id_model, $i]) }}" method="POST" id="delete-award-form-{{ $i }}">
                    @csrf
                    @method('DELETE')
                    <button type="button" onclick="confirmDeleteAward({{ $i }})" class="bg-red-600 text-white px-2 py-1 rounded text-xs font-bold">Delete</button>
                  </form>
                </div>
              @endif
            </div>
            @endfor
          </div>
        </div>
      </div>
  </div>
      <script>
        function showTab(tab) {
          document.getElementById('tab-content-portfolio').style.display = tab === 'portfolio' ? '' : 'none';
          document.getElementById('tab-content-career').style.display = tab === 'career' ? '' : 'none';
          document.getElementById('tab-content-awards').style.display = tab === 'awards' ? '' : 'none';
          document.getElementById('tab-portfolio').classList.toggle('text-white', tab === 'portfolio');
          document.getElementById('tab-portfolio').classList.toggle('text-gray-400', tab !== 'portfolio');
          document.getElementById('tab-portfolio').classList.toggle('border-b-2', tab === 'portfolio');
          document.getElementById('tab-career').classList.toggle('text-white', tab === 'career');
          document.getElementById('tab-career').classList.toggle('text-gray-400', tab !== 'career');
          document.getElementById('tab-career').classList.toggle('border-b-2', tab === 'career');
          document.getElementById('tab-awards').classList.toggle('text-white', tab === 'awards');
          document.getElementById('tab-awards').classList.toggle('text-gray-400', tab !== 'awards');
          document.getElementById('tab-awards').classList.toggle('border-b-2', tab === 'awards');
        }
        function confirmDeleteCareer(slot) {
          Swal.fire({
            title: 'Hapus foto career?',
            text: 'Foto akan dihapus secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#666',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
          }).then((result) => {
            if (result.isConfirmed) {
              document.getElementById('delete-career-form-' + slot).submit();
            }
          });
        }
        function confirmDeleteAward(slot) {
          Swal.fire({
            title: 'Hapus foto award?',
            text: 'Foto akan dihapus secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#666',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
          }).then((result) => {
            if (result.isConfirmed) {
              document.getElementById('delete-award-form-' + slot).submit();
            }
          });
        }
        // Modal Career
        function openCareerEditModal(slot, eventName = '', year = '') {
          Swal.fire({
            title: 'Edit Data Event',
            html: `<input id="career-event-name-edit" class="swal2-input" placeholder="Nama Event" value="${eventName}">
                   <input id="career-year-edit" class="swal2-input" placeholder="Tahun" value="${year}">
                   <input id="career-photo-edit" type="file" accept="image/*" class="swal2-file">`,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            background: '#212121',
            color: '#fff',
            preConfirm: () => {
              return {
                event_name: document.getElementById('career-event-name-edit').value,
                year: document.getElementById('career-year-edit').value,
                photo: document.getElementById('career-photo-edit').files[0]
              };
            }
          }).then((result) => {
            if (result.isConfirmed) {
              let form = document.getElementById('edit-career-form-' + slot);
              if (form) {
                form.querySelector('input[name="event_name"]').value = result.value.event_name;
                form.querySelector('input[name="year"]').value = result.value.year;
                if (result.value.photo) {
                  // Buat DataTransfer untuk set file input
                  const dt = new DataTransfer();
                  dt.items.add(result.value.photo);
                  form.querySelector('input[type="file"]').files = dt.files;
                }
                form.submit();
              }
            }
          });
        }
        function handleCareerFileChange(e, slot) {
          if (e.target.files.length > 0) {
            Swal.fire({
              title: 'Isi Data Event',
              html: `<input id="career-event-name" class="swal2-input" placeholder="Nama Event">
                     <input id="career-year" class="swal2-input" placeholder="Tahun">`,
              focusConfirm: false,
              showCancelButton: true,
              confirmButtonText: 'Simpan',
              background: '#212121',
              color: '#fff',
              preConfirm: () => {
                return [
                  document.getElementById('career-event-name').value,
                  document.getElementById('career-year').value
                ];
              }
            }).then((result) => {
              if (result.isConfirmed) {
                let form = document.getElementById('career-upload-form-' + slot);
                if (form) {
                  form.querySelector('input[name="event_name"]').value = result.value[0];
                  form.querySelector('input[name="year"]').value = result.value[1];
                  form.submit();
                }
              } else {
                // Reset file input jika batal
                e.target.value = '';
              }
            });
          }
        }
      </script>
      <script type="module">
  import PhotoSwipeLightbox from 'https://unpkg.com/photoswipe@5/dist/photoswipe-lightbox.esm.js';

  const lightbox = new PhotoSwipeLightbox({
    gallery: '#tab-content-portfolio',
    children: 'a[data-pswp-index]',
    pswpModule: () => import('https://unpkg.com/photoswipe@5/dist/photoswipe.esm.js')
  });

  lightbox.init();
</script>

<!-- === MOBILE ONLY: Profile & Portfolio === -->
<div class="md:hidden px-4 py-6 bg-black text-white font-['Open_Sans']">
  <!-- Model Profile -->
  <div class="flex flex-col items-center text-center mb-6">
    <img src="{{ $selected->photo ? asset('storage/' . $selected->photo) : asset('img/profile.jpg') }}" alt="Model Profile" class="w-[180px] h-[180px] rounded-full object-cover mb-4" />

    <button class="bg-white text-black text-[12px] font-bold rounded-full px-3 py-1 mb-2">
      {{ $selected->experience_value }} {{ __('messages.portofolio_' . ($selected->experience_unit ?? 'year')) }} {{ __('messages.portofolio_exp') }}
    </button>
    <h2 class="text-[28px] font-semibold mb-[4px]">{{ $selected->nama_model }}</h2>
    <p class="text-[12px] text-gray-300 mb-[2px]">{{ $selected->city }}</p>
    <p class="text-[12px] text-gray-400 mb-[8px]">
      {{ $selected->status == 'available' ? __('messages.portofolio_available') : __('messages.portofolio_not_available') }}
    </p>
    @php
                        $pesan3 = "Halo, saya [Nama Anda] dari [Nama Perusahaan/Organisasi]. Saya tertarik dengan profil Anda dan ingin berdiskusi mengenai proyek [Nama Proyek].";
                    @endphp

          <a href="https://api.whatsapp.com/send?phone=6281214237742&text={{ urlencode($pesan3) }}" target="_blank" class="bg-white text-black text-[10px] font-semibold px-3 py-2 rounded-full hover:bg-gray-200 transition-colors duration-300">{{ __('messages.portofolio_hire') }} ↗</a>
  </div>

  <!-- Model Stats -->
  <div class="grid grid-cols-4 gap-3 text-center text-[16px] text-gray-300 mb-6">
    <div>
      {{ $selected->height }} cm<br>
      <span class="text-[12px] text-gray-500">{{ __('messages.portofolio_height') }}</span>
    </div>
    <div>
      {{ $selected->weight }} kg<br>
      <span class="text-[12px] text-gray-500">{{ __('messages.portofolio_weight') }}</span>
    </div>
    <div>
      {{ $selected->age }} th<br>
      <span class="text-[12px] text-gray-500">{{ __('messages.portofolio_age') }}</span>
    </div>
    <div>
      {{ $selected->shoes_size }}<br>
      <span class="text-[12px] text-gray-500">{{ __('messages.portofolio_shoes') }}</span>
    </div>
    <div>
      {{ $selected->size }}<br>
      <span class="text-[12px] text-gray-500">{{ __('messages.portofolio_size') }}</span>
    </div>
    <div>
      {{ $selected->bust }} cm<br>
      <span class="text-[12px] text-gray-500">{{ __('messages.portofolio_bust') }}</span>
    </div>
    <div>
      {{ $selected->waist }} cm<br>
      <span class="text-[12px] text-gray-500">{{ __('messages.portofolio_waist') }}</span>
    </div>
  </div>

  <!-- Mobile Tabs Header -->
<div class="flex justify-center gap-4 mb-6 text-[14px] font-semibold">
  <button onclick="showMobileTab('portfolio', this)" class="tab-mobile-btn text-white border-b-2 border-white">Portfolio</button>
  <button onclick="showMobileTab('career', this)" class="tab-mobile-btn text-gray-400">{{ __('messages.portofolio_tab_career') }}</button>
  <button onclick="showMobileTab('awards', this)" class="tab-mobile-btn text-gray-400">{{ __('messages.portofolio_tab_awards') }}</button>
</div>




<!-- Tab Contents -->
<div id="mobile-tab-portfolio">
  <!-- Portfolio Gallery 2 Columns -->
  <div class="grid grid-cols-2 gap-3 mb-10">
    @for ($i = 1; $i <= 10; $i++)
      @if(isset($portfolioSlots[$i]) && $portfolioSlots[$i])
        @php
          $size = $portfolioSizes[$i] ?? ['width' => 1200, 'height' => 900];
        @endphp
        <a href="{{ asset('storage/' . $portfolioSlots[$i]->photo) }}"
          data-pswp-width="{{ $size['width'] }}"
          data-pswp-height="{{ $size['height'] }}"
          data-pswp-index="{{ $i - 1 }}"
          target="_blank"
          class="block aspect-square overflow-hidden">
          <img src="{{ asset('storage/' . $portfolioSlots[$i]->photo) }}" class="object-cover w-full h-full" />
        </a>
      @endif
    @endfor
  </div>
</div>

<!-- Career Tab -->
<div id="mobile-tab-career" class="hidden">
  <div class="mb-8">
    <h3 class="text-[16px] font-semibold mb-3"></h3>
    <div class="grid grid-cols-2 gap-3">
      @if($selected->career && count($selected->career))
        @foreach($selected->career as $career)
          <div class="w-full bg-gray-700 rounded overflow-hidden relative h-[150px]">
            @if($career->photo)
              <img src="{{ asset('storage/' . $career->photo) }}" class="object-cover w-full h-full" />
            @endif
            <div class="absolute bottom-2 left-2 bg-black bg-opacity-60 text-white text-xs px-2 py-1 rounded">
              {{ $career->title }}<br>{{ $career->year }}
            </div>
          </div>
        @endforeach
      @else
        @for ($i = 0; $i < 6; $i++)
          <div class="w-full bg-gray-800 rounded h-[150px] flex items-center justify-center text-gray-500 text-sm">
          </div>
        @endfor
      @endif
    </div>
  </div>
</div>


<!-- Awards Tab -->
<div id="mobile-tab-awards" class="hidden">
  <div class="mb-8">
    <div class="grid grid-cols-2 gap-3">
      @if($selected->awards && count($selected->awards))
        @foreach($selected->awards as $award)
        <div class="w-full bg-gray-700 rounded overflow-hidden relative aspect-[3/2]">
            @if($award->photo)
              <img src="{{ asset('storage/' . $award->photo) }}" class="object-cover w-full h-full" />
            @endif
            <div class="absolute bottom-2 left-2 bg-black bg-opacity-60 text-white text-xs px-2 py-1 rounded">
              {{ $award->title }}<br>{{ $award->year }}
            </div>
          </div>
        @endforeach
      @else
        @for ($i = 0; $i < 6; $i++)
        <div class="w-full bg-gray-800 rounded aspect-[3/2] flex items-center justify-center text-gray-500 text-sm">
          </div>
        @endfor
      @endif
    </div>
  </div>
</div>
</div>

<script>
  function showMobileTab(tab, el) {
    const tabs = ['portfolio', 'career', 'awards'];
    tabs.forEach(t => {
      document.getElementById(`mobile-tab-${t}`).classList.add('hidden');
    });
    document.getElementById(`mobile-tab-${tab}`).classList.remove('hidden');

    document.querySelectorAll('.tab-mobile-btn').forEach(btn => {
      btn.classList.remove('text-white', 'border-b-2', 'border-white');
      btn.classList.add('text-gray-400');
    });
    el.classList.add('text-white', 'border-b-2', 'border-white');
    el.classList.remove('text-gray-400');
  }

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

<!-- Contact & Booking -->
<div class="text-[14px] text-gray-300 mb-[40px]">
          <h3 class="text-[22px] text-white mb-[15px] font-fondamento">{{ __('messages.portofolio_contact_booking') }}</h3>
          <p>{{ __('messages.portofolio_office') }}<br />
          {{ __('messages.portofolio_address') }}</p>
          <p>{{ __('messages.portofolio_wa') }}</p>
          <p>{{ __('messages.portofolio_ig') }}</p>
          <p>{{ __('messages.portofolio_email') }}</p>
        </div>
        <!-- Footer -->
        <footer class="flex justify-between text-[12px] text-gray-500 border-t border-[#333] pt-[15px]">
          <span>{{ __('messages.portofolio_footer') }}</span>
          <span>
            <a href="https://www.instagram.com/academynexttopmodel?igsh=MTdmMDdyZXRxdjk3MA==" target="_blank" rel="noopener noreferrer">
              {{ __('messages.portofolio_instagram') }}
            </a>
          </span>
        </footer>
    </section>
  @endif
  <div class="pswp" tabindex="-1" role="dialog" aria-hidden="true"></div>

  
</body>
</html>
