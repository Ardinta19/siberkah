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
