<header id="isgh-header" class="w-full px-4 sm:px-6 py-3 bg-transparent" style="z-index:20;position:relative">
  <div class="max-w-screen-xl mx-auto">
 
    <!-- Top bar: Logo | Desktop pill nav | Mobile right controls -->
    <div class="flex items-center justify-between gap-4">
 
      <!-- ISGH Logo -->
      <a href="/" aria-label="ISGH Home" class="flex-shrink-0">
        <svg width="52" height="52" viewBox="0 0 54 54" xmlns="http://www.w3.org/2000/svg">
          <circle cx="27" cy="27" r="27" fill="#1a4a2e"/>
          <circle cx="27" cy="27" r="25" fill="#1a4a2e" stroke="#c8a84b" stroke-width="1.8"/>
          <polygon points="27,6 29,11 25,11" fill="#c8a84b"/>
          <rect x="25.5" y="11" width="3" height="5" rx="0.5" fill="#c8a84b"/>
          <polygon points="15,16 16.5,12 18,16" fill="#c8a84b"/>
          <rect x="15" y="16" width="3" height="5" rx="0.5" fill="#c8a84b"/>
          <polygon points="36,16 37.5,12 39,16" fill="#c8a84b"/>
          <rect x="36" y="16" width="3" height="5" rx="0.5" fill="#c8a84b"/>
          <path d="M27 16 C20 16 15 21 15 25 L39 25 C39 21 34 16 27 16Z" fill="#c8a84b"/>
          <rect x="13" y="25" width="28" height="17" rx="1" fill="#c8a84b"/>
          <path d="M23.5 42 L23.5 34 Q27 30 30.5 34 L30.5 42Z" fill="#1a4a2e"/>
          <ellipse cx="18" cy="30" rx="2.5" ry="3" fill="#1a4a2e"/>
          <ellipse cx="36" cy="30" rx="2.5" ry="3" fill="#1a4a2e"/>
          <rect x="10" y="42" width="34" height="2.5" rx="1.2" fill="#c8a84b"/>
          <text x="26" y="22" font-size="4" fill="white" text-anchor="middle" font-family="serif">&#9789;</text>
        </svg>
      </a>
 
      <!-- Desktop pill nav (hidden on mobile) -->
      <nav class="hidden border border-[10px] border-white lg:flex items-center bg-[#1c1c1c] rounded-full px-3 py-2 gap-1 flex-1 max-w-4xl justify-between shadow-lg" aria-label="Main navigation">
        <div class="flex items-center gap-1">
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
 
      <!-- Mobile right: Join Now + Hamburger -->
      <div class="flex lg:hidden items-center gap-3">
        <a href="/join" class="bg-[#00d084] hover:bg-[#00b870] text-white text-sm font-bold px-4 py-2 rounded-full transition-colors duration-200 shadow whitespace-nowrap">Join Now</a>
        <button
          id="isgh-ham-btn"
          aria-label="Toggle menu"
          aria-expanded="false"
          aria-controls="isgh-mobile-menu"
          class="flex flex-col gap-[5px] justify-center items-center w-10 h-10 bg-[#1c1c1c] rounded-full p-2 cursor-pointer border-0 focus:outline-none"
        >
          <span id="ham-line1" class="ham-line w-5 h-0.5 bg-white rounded-full"></span>
          <span id="ham-line2" class="ham-line w-5 h-0.5 bg-white rounded-full"></span>
          <span id="ham-line3" class="ham-line w-5 h-0.5 bg-white rounded-full"></span>
        </button>
      </div>
 
    </div>
  </div>
</header>
<script>
  (function () {
    var btn = document.getElementById('isgh-ham-btn');
    var menu = document.getElementById('isgh-mobile-menu');
    var header = document.getElementById('isgh-header');
 
    if (!btn || !menu || !header) return;
 
    btn.addEventListener('click', function () {
      var isOpen = menu.classList.toggle('open');
      header.classList.toggle('menu-open', isOpen);
      btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
 
    window.addEventListener('resize', function () {
      if (window.innerWidth >= 1024) {
        menu.classList.remove('open');
        header.classList.remove('menu-open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  })();
</script>