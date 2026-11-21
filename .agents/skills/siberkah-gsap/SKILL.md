---
name: siberkah-gsap
description: Panduan lokal untuk menambahkan atau mengaudit motion GSAP pada halaman Blade SIBERKAH.
---

1. Identifikasi tugas pengguna dan permukaan yang perlu penekanan. Navigasi jangkar, isi, dan submit harus berfungsi sebelum modul dimuat.
2. Gunakan dependensi GSAP yang sudah ada. Muat dinamis berdasarkan penanda halaman; bungkus dengan `gsap.matchMedia()` untuk `prefers-reduced-motion: no-preference` agar efek dibatalkan saat preferensi berubah.
3. Utamakan transform dan opacity. Animasikan kartu, bukan angka harga atau input. Hindari timeline berulang pada transaksi, scroll-jacking, dan listener kedua untuk kontrol yang sudah punya animasi.
4. Gunakan stagger singkat dan bersihkan inline styles. Uji kecil/lebar, fokus keyboard, perubahan total dan submit saat animasi berjalan; cek ringkasan tidak menutupi tombol. Catat apa yang benar-benar diuji.
