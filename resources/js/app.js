/* eslint-disable */
// No custom JS dependencies needed - using Alpine.js from CDN

// Utility functions for the project
window.utils = {
  formatRupiah: (nominal) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(nominal);
  },

  formatDate: (tanggal) => {
    return new Date(tanggal).toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  },

  formatDateTime: (tanggal) => {
    return new Date(tanggal).toLocaleDateString('id-ID', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
};
const menuToggle = document.getElementById('guest-menu-toggle');
menuToggle?.addEventListener('click', () => {
  const menu = document.getElementById('mobile-menu');
  menu.hidden = !menu.hidden;
  menuToggle.setAttribute('aria-expanded', String(!menu.hidden));
});

for (const button of document.querySelectorAll('[data-favorite]')) {
  const key = `siberkah:favorite:${button.dataset.favorite}`;
  const render = saved => {
    button.setAttribute('aria-pressed', String(saved));
    button.textContent = saved ? '♥' : '♡';
  };
  try { render(localStorage.getItem(key) === '1'); } catch { /* Storage may be disabled. */ }
  button.addEventListener('click', () => {
    const saved = button.getAttribute('aria-pressed') !== 'true';
    render(saved);
    try { saved ? localStorage.setItem(key, '1') : localStorage.removeItem(key); } catch { /* Session only. */ }
  });
}

// Progressive disclosure: without JS the catalog keeps all filters visible.
const filters = document.querySelector('#kc-extra');
if (filters) {
  const toggle = document.querySelector('.kc-filter-toggle');
  const mobile = matchMedia('(max-width:47.99rem)');
  let open = false;
  const sync = () => {
    toggle.hidden = !mobile.matches;
    filters.hidden = mobile.matches && !open;
    toggle.setAttribute('aria-expanded', String(!filters.hidden));
  };
  toggle.addEventListener('click', () => { open = !open; sync(); });
  mobile.addEventListener('change', sync);
  sync();
  const kind = filters.querySelector('[name=jenis]');
  const day = filters.querySelector('[name=tanggal]');
  const syncDate = () => { day.disabled = ['jual','custom'].includes(kind.value); };
  kind.addEventListener('change', syncDate);
  syncDate();
}
