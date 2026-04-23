<header id="isgh-header" class="relative z-50 w-full px-4 sm:px-6 py-3">
  <div class="mx-auto max-w-screen-xl">
    <div class="flex items-center gap-3">
      <a href="/" aria-label="ISGH Home" class="hidden lg:block shrink-0">
        <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="h-12 w-12 sm:h-14 sm:w-14 object-contain">
      </a>

      <nav class="hidden lg:flex flex-1 items-center justify-between gap-4 rounded-full border-[8px] border-white bg-[#1c1c1c] px-3 py-2 shadow-[0_12px_30px_rgba(0,0,0,0.18)]" aria-label="Main navigation">
        <div class="flex flex-wrap items-center gap-1">
          <a href="/" class="text-white text-sm font-medium px-4 py-2 rounded-full hover:bg-white/10 transition-colors duration-200 whitespace-nowrap">Home</a>
          <a href="/centers" class="text-white text-sm font-medium px-4 py-2 rounded-full hover:bg-white/10 transition-colors duration-200 whitespace-nowrap">Centers</a>
          <a href="/donate" class="text-white text-sm font-medium px-4 py-2 rounded-full hover:bg-white/10 transition-colors duration-200 whitespace-nowrap">Donate</a>
          <a href="/membership" class="text-white text-sm font-medium px-4 py-2 rounded-full hover:bg-white/10 transition-colors duration-200 whitespace-nowrap">Become a Member</a>
          <a href="/verify" class="text-white text-sm font-medium px-4 py-2 rounded-full hover:bg-white/10 transition-colors duration-200 whitespace-nowrap">Verify Membership Status</a>
        </div>
        <div class="flex items-center gap-2 ml-2">
          <a href="https://isgh.wildapricot.org/Sys/Login" target="_blank" rel="noopener noreferrer" class="bg-[#9ca3af] hover:bg-[#6b7280] text-white text-sm font-semibold px-5 py-2 rounded-full transition-colors duration-200 whitespace-nowrap">Sign in</a>
          <a href="/join" class="bg-[#00d084] hover:bg-[#00b870] text-white text-sm font-bold px-5 py-2 rounded-full transition-colors duration-200 whitespace-nowrap shadow-md">Join Now</a>
        </div>
      </nav>

      <div class="w-full lg:hidden">
        <div class="flex w-full items-center justify-between rounded-full border-[8px] border-white bg-[#1c1c1c] px-4 py-3 shadow-[0_12px_30px_rgba(0,0,0,0.18)] min-h-[72px]">
          <a href="/" aria-label="ISGH Home" class="shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="h-11 w-11 sm:h-12 sm:w-12 object-contain">
          </a>

          <button
            id="isgh-ham-btn"
            aria-label="Toggle menu"
            aria-expanded="false"
            aria-controls="isgh-mobile-menu"
            class="flex h-11 w-11 sm:h-12 sm:w-12 flex-col items-center justify-center gap-1.5 rounded-full bg-white/5"
            type="button"
          >
            <span class="isgh-ham-line block h-0.5 w-6 rounded-full bg-white"></span>
            <span class="isgh-ham-line block h-0.5 w-6 rounded-full bg-white"></span>
            <span class="isgh-ham-line block h-0.5 w-6 rounded-full bg-white"></span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div id="isgh-mobile-menu" class="pointer-events-none absolute left-4 right-4 top-full z-40 mt-3 translate-y-2 opacity-0 transition duration-200 lg:hidden">
    <div class="overflow-hidden rounded-[28px] border-[8px] border-white bg-[#1c1c1c] p-4 shadow-[0_20px_45px_rgba(0,0,0,0.25)]">
      <nav class="flex flex-col gap-1" aria-label="Mobile navigation">
        <a href="/" class="rounded-2xl px-4 py-3 text-base text-white/90 transition-colors hover:bg-white/10 hover:text-white">Home</a>
        <a href="/centers" class="rounded-2xl px-4 py-3 text-base text-white/90 transition-colors hover:bg-white/10 hover:text-white">Centers</a>
        <a href="/donate" class="rounded-2xl px-4 py-3 text-base text-white/90 transition-colors hover:bg-white/10 hover:text-white">Donate</a>
        <a href="/membership" class="rounded-2xl px-4 py-3 text-base text-white/90 transition-colors hover:bg-white/10 hover:text-white">Become a Member</a>
        <a href="/verify" class="rounded-2xl px-4 py-3 text-base text-white/90 transition-colors hover:bg-white/10 hover:text-white">Verify Membership Status</a>
      </nav>

      <div class="mt-4 flex flex-col gap-3">
        <a href="https://isgh.wildapricot.org/Sys/Login" target="_blank" rel="noopener noreferrer" class="rounded-full bg-[#9ca3af] px-6 py-2.5 text-center font-semibold text-white transition-colors hover:bg-[#6b7280]">Sign in</a>
        <a href="/join" class="rounded-full bg-[#00d084] px-6 py-2.5 text-center font-bold text-white transition-colors hover:bg-[#00b870]">Join Now</a>
      </div>
    </div>
  </div>
</header>

<style>
  #isgh-mobile-menu.open {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
  }

  .isgh-ham-line {
    transform-origin: center;
    transition: transform 0.25s ease, opacity 0.2s ease;
  }

  #isgh-ham-btn[aria-expanded="true"] .isgh-ham-line:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
  }

  #isgh-ham-btn[aria-expanded="true"] .isgh-ham-line:nth-child(2) {
    opacity: 0;
  }

  #isgh-ham-btn[aria-expanded="true"] .isgh-ham-line:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
  }
</style>

<script>
  (function () {
    var btn = document.getElementById('isgh-ham-btn');
    var menu = document.getElementById('isgh-mobile-menu');
    var header = document.getElementById('isgh-header');

    if (!btn || !menu || !header) return;

    function setOpen(isOpen) {
      menu.classList.toggle('open', isOpen);
      btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    btn.addEventListener('click', function (event) {
      event.stopPropagation();
      setOpen(!menu.classList.contains('open'));
    });

    menu.addEventListener('click', function (event) {
      if (event.target.closest('a')) {
        setOpen(false);
      }
    });

    document.addEventListener('click', function (event) {
      if (!header.contains(event.target)) {
        setOpen(false);
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth >= 1024) {
        setOpen(false);
      }
    });
  })();
</script>
