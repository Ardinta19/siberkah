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