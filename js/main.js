/* js/main.js — Cívica Equipamentos */

// ── Nav scroll ────────────────────────────────────────────────
(function () {
  const nav = document.querySelector('.nav');
  if (!nav) return;
  const update = () => nav.classList.toggle('scrolled', window.scrollY > 10);
  window.addEventListener('scroll', update, { passive: true });
  update();
})();

// ── Mobile menu ───────────────────────────────────────────────
(function () {
  const toggle = document.querySelector('.nav-toggle');
  const menu   = document.querySelector('.nav-mobile');
  if (!toggle || !menu) return;
  toggle.addEventListener('click', () => {
    const open = menu.classList.toggle('open');
    toggle.classList.toggle('open', open);
    toggle.setAttribute('aria-expanded', open);
    document.body.style.overflow = open ? 'hidden' : '';
  });
  menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
    menu.classList.remove('open');
    toggle.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }));
})();

// ── Theme toggle ──────────────────────────────────────────────
(function () {
  // Apply saved theme immediately (also done inline in <head> to avoid flash)
  const saved = localStorage.getItem('civica_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', saved);

  function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('civica_theme', theme);
    // Update all toggle button icons
    document.querySelectorAll('.theme-toggle [data-lucide]').forEach(icon => {
      icon.setAttribute('data-lucide', theme === 'dark' ? 'sun' : 'moon');
    });
    if (window.lucide) lucide.createIcons();
  }

  // Attach to all toggle buttons (there might be one in nav + admin sidebar)
  function attachToggles() {
    document.querySelectorAll('.theme-toggle').forEach(btn => {
      // Set correct icon
      const icon = btn.querySelector('[data-lucide]');
      if (icon) icon.setAttribute('data-lucide', saved === 'dark' ? 'sun' : 'moon');
      btn.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme') || 'dark';
        setTheme(current === 'dark' ? 'light' : 'dark');
      });
    });
  }

  // Run after DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachToggles);
  } else {
    attachToggles();
  }
})();

// ── Scroll reveal ─────────────────────────────────────────────
(function () {
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
  }, { threshold: 0.1 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
})();

// ── Counter animation ─────────────────────────────────────────
(function () {
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      const el = e.target, end = parseInt(el.dataset.count), suf = el.dataset.suffix || '';
      const t0 = performance.now();
      const ease = p => 1 - Math.pow(1 - p, 3);
      const step = now => {
        const p = Math.min((now - t0) / 1600, 1);
        el.textContent = Math.round(ease(p) * end).toLocaleString('pt-PT') + suf;
        if (p < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
      io.unobserve(el);
    });
  }, { threshold: 0.5 });
  document.querySelectorAll('[data-count]').forEach(el => io.observe(el));
})();

// ── Active nav link ───────────────────────────────────────────
(function () {
  const page = location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a').forEach(a => {
    const href = a.getAttribute('href') || '';
    if (href === page || (page === '' && href === 'index.html') || (page === 'index.html' && href === 'index.html'))
      a.classList.add('active');
  });
})();

// ── Machine card builder ──────────────────────────────────────
function buildMachineCard(m, onClick) {
  const card = document.createElement('div');
  card.className = 'mcard';
  card.innerHTML = `
    <div class="mcard-img">
      ${m.image
        ? `<img src="${sanitize(m.image)}" alt="${sanitize(m.name)}" loading="lazy">`
        : '<div class="mcard-img-empty"><i data-lucide="package"></i></div>'}
      <div class="mcard-badges">
        <span class="badge ${typeClass(m.type)}">${typeLabel(m.type)}</span>
      </div>
    </div>
    <div class="mcard-body">
      <div class="mcard-cat">${sanitize(m.category)}</div>
      <div class="mcard-name">${sanitize(m.name)}</div>
      <div class="mcard-desc">${sanitize(m.description)}</div>
      <div class="mcard-foot">
        <div class="mcard-price">${priceFormat(m.price)}<span> ${priceLabel(m.priceUnit)}</span></div>
        <div class="mcard-arrow"><i data-lucide="arrow-right"></i></div>
      </div>
    </div>`;
  card.addEventListener('click', () => onClick && onClick(m));
  return card;
}

// ── Toast ─────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  let t = document.querySelector('.toast');
  if (!t) { t = document.createElement('div'); t.className = 'toast'; document.body.appendChild(t); }
  t.className = `toast ${type}`;
  t.innerHTML = `<i data-lucide="${type === 'success' ? 'check-circle' : 'alert-circle'}"></i><span>${msg}</span>`;
  if (window.lucide) lucide.createIcons({ nodes: [t] });
  t.classList.add('show');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.remove('show'), 3200);
}

// ── Footer year ───────────────────────────────────────────────
const fy = document.getElementById('footerYear');
if (fy) fy.textContent = new Date().getFullYear();

window.CivicaApp = { showToast, buildMachineCard };
