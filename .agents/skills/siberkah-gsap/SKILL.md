---
name: siberkah-gsap
description: Panduan lokal untuk menambahkan atau mengaudit motion GSAP pada halaman Blade SIBERKAH.
---

1. Identifikasi tugas pengguna dan permukaan yang perlu penekanan. Navigasi jangkar, isi, dan submit harus berfungsi sebelum modul dimuat.
2. Gunakan dependensi GSAP yang sudah ada. Muat dinamis berdasarkan penanda halaman; bungkus dengan `gsap.matchMedia()` untuk `prefers-reduced-motion: no-preference` agar efek dibatalkan saat preferensi berubah.
3. Utamakan transform dan opacity. Animasikan kartu, bukan angka harga atau input. Hindari timeline berulang pada transaksi, scroll-jacking, dan listener kedua untuk kontrol yang sudah punya animasi.
4. Gunakan stagger singkat dan bersihkan inline styles. Uji kecil/lebar, fokus keyboard, perubahan total dan submit saat animasi berjalan; cek ringkasan tidak menutupi tombol. Catat apa yang benar-benar diuji.

Contoh Basic Tween di checkout: `from` untuk masuknya kartu, `fromTo` untuk aksen kelopak pada judul tujuan, lalu `to` untuk kembali netral. Tandai tujuan dengan CSS `:target`/`:focus` juga saat motion dimatikan. Aksen tujuan bukan indikator bahwa langkah sudah selesai.

Pilih plugin per interaksi, bukan registrasi global. Contoh: `Flip.getState()` sebelum urutan DOM foto berubah, `Flip.from()` setelahnya. Muat plugin hanya pada galeri Owner dengan minimal dua foto dan preferensi gerak normal. Urutan, fokus keyboard, pembatalan dan submit tidak boleh menunggu tween selesai. Jangan memuat GSDevTools/MotionPathHelper di bundle produksi, atau menambahkan ScrollSmoother pada formulir dengan scroll native, dialog dan sticky header tanpa kebutuhan dan pengujian khusus.

Pada ringkasan pencarian React, ukur posisi sebelum `setItems`, lalu jalankan Flip setelah commit melalui `useLayoutEffect`. Animasikan hanya perubahan keanggotaan chip, bukan perubahan teks setiap ketukan. Batalkan tween dan lepaskan listener saat preferensi berubah atau komponen dilepas. Tombol hapus harus tetap bekerja sebelum modul motion tersedia dan mengembalikan fokus ke kontrol asal.

Navigasi: perubahan mode pill tetap diputuskan oleh IntersectionObserver dan kondisi fokus/dialog. DrawSVG hanya menggambar garis dekoratif di tepi logo setelah mode ringkas aktif; batalkan jika panel dibuka kembali atau preferensi berubah. Jangan mengganti scroll native dengan ScrollTrigger + ScrollSmoother untuk satu penanda ambang. Ikon hamburger tiga garis memakai transform CSS dari aria-expanded yang sama di seluruh layout; jangan menumpuk MorphSVG di atas transform yang sudah cukup. ScrollTo tidak dibutuhkan untuk jangkar yang sudah mendukung fokus; ScrambleText/SplitText/TextPlugin tidak untuk label menu atau nilai tagihan. Draggable/Inertia/Observer tidak boleh mengambil alih scroll/zoom sentuh. MotionPathHelper hanya untuk desain, bukan runtime produksi.
