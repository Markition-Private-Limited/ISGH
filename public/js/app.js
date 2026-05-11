/* ============================================================
   ISGH Staff Portal â€” app.js
   Vanilla JS utilities: sidebar, password toggle, dropdowns
   ============================================================ */

(function () {
  'use strict';

  /* â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

  function on(el, event, handler) {
    if (el) el.addEventListener(event, handler);
  }

  /* â”€â”€ DOM Ready â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
  document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    initSidebarCollapse();
    initPasswordToggles();
    initRoleDropdown();
    initAutoHideAlerts();
    initZipSearch();
    initFilterSelects();
    initCarousel();
  });

  /* â”€â”€ 1a. Desktop Sidebar Collapse â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
  function initSidebarCollapse() {
    var collapseBtn = $('#sidebar-collapse-btn');
    var shell       = $('#app-shell');
    if (!collapseBtn || !shell) return;

    // Restore persisted collapsed state on page load
    if (localStorage.getItem('sidebar-collapsed') === '1') {
      shell.classList.add('collapsed');
    }

    on(collapseBtn, 'click', function () {
      // Only run on desktop
      if (window.innerWidth <= 768) return;
      var isCollapsed = shell.classList.toggle('collapsed');
      localStorage.setItem('sidebar-collapsed', isCollapsed ? '1' : '0');
    });
  }

  /* â”€â”€ 1b. Mobile Sidebar Toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
  function initSidebar() {
    var toggleBtn = $('#sidebar-toggle-btn');
    var closeBtn  = $('#sidebar-mobile-close');
    var sidebar   = $('#app-sidebar');
    var overlay   = $('#sidebar-overlay');

    function openSidebar() {
      if (sidebar)  sidebar.classList.add('open');
      if (overlay)  overlay.classList.add('open');
      if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
      if (sidebar)  sidebar.classList.remove('open');
      if (overlay)  overlay.classList.remove('open');
      if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    }

    on(toggleBtn, 'click', openSidebar);
    on(closeBtn,  'click', closeSidebar);
    on(overlay,   'click', closeSidebar);

    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');

    // Close on ESC
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeSidebar();
    });

    // Close sidebar when clicking a nav link on mobile
    $$('.sidebar-link', sidebar).forEach(function (link) {
      on(link, 'click', function () {
        if (window.innerWidth < 768) closeSidebar();
      });
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 768) closeSidebar();
    });
  }

  /* â”€â”€ 2. Password Show / Hide Toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
  function initPasswordToggles() {
    $$('[data-password-toggle]').forEach(function (btn) {
      var targetId = btn.dataset.passwordToggle;
      var input    = document.getElementById(targetId);
      if (!input) return;

      var eyeOpen   = btn.querySelector('.eye-open');
      var eyeClosed = btn.querySelector('.eye-closed');

      // initial state
      if (eyeClosed) eyeClosed.style.display = 'none';

      on(btn, 'click', function () {
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        if (eyeOpen)   eyeOpen.style.display   = isPassword ? 'none'  : 'block';
        if (eyeClosed) eyeClosed.style.display  = isPassword ? 'block' : 'none';

        btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      });
    });
  }

  /* â”€â”€ 3. Role / User Dropdown â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
  function initRoleDropdown() {
    $$('[data-dropdown]').forEach(function (trigger) {
      var targetId = trigger.dataset.dropdown;
      var menu     = document.getElementById(targetId);
      if (!menu) return;

      on(trigger, 'click', function (e) {
        e.stopPropagation();
        var isOpen = menu.classList.contains('open');

        // Close all other open dropdowns
        $$('.role-dropdown-menu.open').forEach(function (m) {
          m.classList.remove('open');
        });
        $$('[data-dropdown].open').forEach(function (t) {
          t.classList.remove('open');
        });

        if (!isOpen) {
          menu.classList.add('open');
          trigger.classList.add('open');
        }
      });
    });

    // Click outside closes all dropdowns
    document.addEventListener('click', function () {
      $$('.role-dropdown-menu.open').forEach(function (m) {
        m.classList.remove('open');
      });
      $$('[data-dropdown].open').forEach(function (t) {
        t.classList.remove('open');
      });
    });
  }

  /* â”€â”€ 4. Auto-hide Flash Alerts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
  function initAutoHideAlerts() {
    $$('.alert[data-auto-hide]').forEach(function (alert) {
      var delay = parseInt(alert.dataset.autoHide, 10) || 4000;
      setTimeout(function () {
        alert.style.transition = 'opacity 0.5s ease, max-height 0.5s ease, margin 0.5s ease';
        alert.style.opacity    = '0';
        alert.style.maxHeight  = '0';
        alert.style.overflow   = 'hidden';
        alert.style.marginBottom = '0';
        setTimeout(function () { alert.remove(); }, 500);
      }, delay);
    });
  }

  /* â”€â”€ 5. ZIP Code Live Search (dashboard table) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
  function initZipSearch() {
    var searchInput = $('#zip-search-input');
    var tableBody   = $('#zip-table-body');
    if (!searchInput || !tableBody) return;

    on(searchInput, 'input', function () {
      var query = this.value.toLowerCase().trim();
      var rows  = $$('tr', tableBody);

      rows.forEach(function (row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
      });
    });
  }

  /* â”€â”€ 6. Stat Card Carousel (mobile dot sync) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
  function initCarousel() {
    var track = document.getElementById('stats-carousel-track');
    var dots  = $$('.carousel-dot');
    if (!track || !dots.length) return;

    // Dot click â†’ scroll to card
    dots.forEach(function (dot) {
      on(dot, 'click', function () {
        var idx   = parseInt(dot.dataset.dot, 10);
        var cards = $$('.stat-card', track);
        if (cards[idx]) {
          cards[idx].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
      });
    });

    // Scroll â†’ update active dot
    track.addEventListener('scroll', function () {
      var cards     = $$('.stat-card', track);
      var trackRect = track.getBoundingClientRect();
      var trackMid  = trackRect.left + (trackRect.width / 2);
      var closest   = 0;
      var minDist   = Infinity;
      cards.forEach(function (card, i) {
        var cardRect = card.getBoundingClientRect();
        var cardMid  = cardRect.left + (cardRect.width / 2);
        var dist = Math.abs(cardMid - trackMid);
        if (dist < minDist) { minDist = dist; closest = i; }
      });
      dots.forEach(function (d, i) { d.classList.toggle('active', i === closest); });
    }, { passive: true });
  }

  /* â”€â”€ 7. Members Filter: live filter on selects â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
  function initFilterSelects() {
    // Submits the filter form when any select changes
    var filterForm = $('#members-filter-form');
    if (!filterForm) return;

    $$('select', filterForm).forEach(function (select) {
      on(select, 'change', function () {
        filterForm.submit();
      });
    });
  }

  /* â”€â”€ 8. Chart.js initialisation (deferred) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
  // Charts are initialised inline in the blade view using
  // data attributes so they stay decoupled from this file.
  // This helper is exposed globally so blade partials can call it.
  window.ISGH = window.ISGH || {};

  window.ISGH.initPieChart = function (canvasId, labels, data, colors) {
    var canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;

    // eslint-disable-next-line no-new
    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: colors || ['#1a5c42', '#f59e0b', '#3aab7b'],
          borderWidth: 0,
          hoverOffset: 6,
        }],
      },
      options: {
        cutout: '65%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return ' ' + ctx.label + ': ' + ctx.parsed.toLocaleString();
              },
            },
          },
        },
      },
    });
  };

  window.ISGH.initBarChart = function (canvasId, labels, data) {
    var canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;

    // eslint-disable-next-line no-new
    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: 'rgba(58,171,123,0.25)',
          borderColor: '#3aab7b',
          borderWidth: 2,
          borderRadius: 4,
        }],
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 10 } } },
          y: { grid: { color: '#f0f2f5' }, ticks: { font: { size: 10 } } },
        },
      },
    });
  };

})();
