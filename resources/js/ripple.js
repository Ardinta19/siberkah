/**
 * Ripple Effect Component
 * Menambahkan efek ripple saat klik pada elemen
 */

document.addEventListener('DOMContentLoaded', function() {
  // Setup ripple effect untuk semua elemen dengan kelas .ripple
  const rippleElements = document.querySelectorAll('.ripple');

  rippleElements.forEach(function(element) {
    element.addEventListener('click', function(e) {
      // Hapus ripple yang ada sebelumnya
      const existingRipple = element.querySelector('.ripple-effect');
      if (existingRipple) {
        existingRipple.remove();
      }

      // Buat elemen ripple baru
      const ripple = document.createElement('span');
      ripple.className = 'ripple-effect absolute pointer-events-none overflow-hidden rounded-full';

      // Posisikan ripple di titik klik
      const rect = element.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height) * 2;
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
      ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
      ripple.style.background = 'rgba(255, 255, 255, 0.3)';
      ripple.style.animation = 'ripple-effect 500ms ease-out';

      element.style.position = 'relative';
      element.appendChild(ripple);

      // Hapus ripple setelah animasi selesai
      setTimeout(() => {
        if (ripple.parentNode) {
          ripple.remove();
        }
      }, 500);
    });
  });
});

// Auto-collapse untuk elemen yang tidak pernah digunakan
if ('IntersectionObserver' in window) {
  const lazyElements = document.querySelectorAll('[data-lazy]');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('loaded');
        observer.unobserve(entry.target);
      }
    });
  });

  lazyElements.forEach(el => {
    observer.observe(el);
  });
}