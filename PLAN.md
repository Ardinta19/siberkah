# Rencana Lengkap Project SIBERKAH (Detail Keseluruhan, Tanpa AI-generated Look)

Dokumen ini adalah panduan langkah demi langkah untuk melanjutkan pengerjaan project SIBERKAH dari kondisi saat ini (hanya migrasi dan seed sederhana) hingga sistem siap rilis. Semua dikerjakan secara manual, efisien, dan seolah-olah dikerjakan oleh manusia tanpa jejak AI.

---

## KONDISI SAAT INI (18 November 2026)

**Yang sudah ada:**
- ✅ Laravel 13.42.0 + PHP 8.5.11 terpasang
- ✅ 9 migrasi basis data lengkap (27 tabel) + constraints CHECK
- ✅ Laravel Boost terpasang (guidelines, skills, MCP)
- ✅ Draw.io: flowchart (3 alur), wireframe pelanggan (6 layar + desktop), wireframe staf (mobile + desktop)
- ✅ SRS.html lengkap 73 kebutuhan (43 Must, 22 Should, 2 Could, 6 Won't)

**Yang sudah terbuat (status aktual):**
- ✅ 8 Enum terbentuk: OrderStatus, PaymentStatus, PaymentType, ProductType, DeliveryType, AdjustmentType, UserRole, TransitionCode
- ✅ 27 Model Eloquent terbuat di app/Models/
- ✅ 23+ Controller terbuat di app/Http/Controllers/
- ✅ 45+ Route web terdaftar di routes/web.php
- ✅ Pengaturan timezone sudah diperbaiki ke Asia/Jakarta

**Yang BELUM/sedang dikerjakan:**
- ⚠️ Test ExampleTest gagal karena route `/` belum terdefinisi — perlu ditambahkan route root atau mengubah test
- ⚠️ View Blade + Tailwind CSS belum di-build (lalu ke FASE 5)
- ⚠️ API Spec (OpenAPI) belum dibuat (lalu ke FASE 6)
- ⚠️ CI/CD pipeline belum disiapkan (lalu ke FASE 8)
- ⚠️ Design token, UI kit belum dibuat (lalu ke FASE 5)

---

## FASE 1: PENYELESAIAN DOKUMEN & PLACEHOLDER (Target: 14-15 Nov)

### 1.1 Isi Placeholder di `docs/SRS.html`
- [ ] Ganti seluruh teks di dalam `<span class="isi">...</span>` (warna kuning #FFF2A8) dengan data asli
- [ ] Data yang perlu diisi: Nama Lengkap, NIM, Nama Dosen, Program Studi, Tanggal Observasi, Versi PHP/Laravel, Alamat Toko
- [ ] Cek: semua 6 kolom `td.isi` di tabel sampul sudah terisi

### 1.2 Lengkapi Lampiran B (Register Nilai Bisnis)
- [ ] **K10**: Masukkan daftar harga riil semua produk (tangkapan layar Instagram/TikTok @berkahkreatif_)
- [ ] **Status data**: Tentukan setiap baris: Pengamatan / Asumsi / Keputusan perancang / Belum jelas / Dikutip
- [ ] **Berkas bukti**: Simpan tangkapan layar ke `docs/bukti/` dengan nama file sesuai kolom Bukti
- [ ] **Validasi pemilik**: Jika akses ke pemilik terbuka, minta persetujuan tertulis untuk BR-10, BR-13, BR-32, BR-41

---

## FASE 2: EKSPOR ARTEFAK VISUAL (Target: 15-16 Nov)

### 2.1 Export Flowchart
- [ ] Buka `docs/siberkah-flowchart.drawio` di Draw.io desktop/online
- [ ] Export 3 diagram: Alur A (Pemesanan), Alur B (OTP), Alur C (Operasional)
- [ ] Simpan ke `docs/flowchart-alur-a.png`, `docs/flowchart-alur-b.png`, `docs/flowchart-alur-c.png` (1920×1080)

### 2.2 Export Wireframe
- [ ] `docs/siberkah-wareframe.drawio` → `docs/wireframe-pelanggan-mobile.png`, `docs/wireframe-pelanggan-desktop.png`
- [ ] `docs/bangun-wireframe.drawio` → `docs/wireframe-staf-mobile.png`, `docs/wireframe-staf-desktop.png`
- [ ] Pastikan semua catatan kuning (perilaku) terbaca di ekspor

### 2.3 Buat Diagram ERD & Use Case
- [ ] **ERD**: Visualisasi 27 tabel dari Bab 5.2 SRS → `docs/er-diagram.png`
  - Gunakan DBDiagram.io, dbdocs.io, atau Draw.io manual
  - Tunjukkan PK, FK, unique constraints, index utama
  - Kelompokkan: Identitas, Katalog, Unit Sewa, Pesanan, Reservasi, Pembayaran, Inspeksi, Acuan, Log
- [ ] **Use Case Diagram**: 28 UC dari Bab 5.3 SRS → `docs/usecase-diagram.png`
  - 4 aktor: Pengunjung/Pelanggan, Owner, Admin, Staf Produksi+Kurir
  - Pakai PlantUML atau Draw.io

---

## FASE 3: FOUNDATION CODE (Model, Enum, Action, Request) (Target: 16-19 Nov)

### 3.1 Enum & Value Object (app/Enums/)
- [ ] `OrderStatus`: awaiting_payment, expired, confirmed, in_production, ready, handed_over, returned, completed, cancelled
- [ ] `PaymentStatus`: pending, verified, rejected, expired
- [ ] `PaymentType`: dp, pelunasan, penuh, refund
- [ ] `ProductType`: sewa, jual, custom
- [ ] `DeliveryType`: antar, ambil
- [ ] `UserRole`: owner, admin, staf_produksi, staf_kurir (gabung jadi satu peran staf)
- [ ] `OptionType`: pilihan, teks, satuan
- [ ] `AdjustmentType`: denda_telat, kerusakan, perpanjangan, diskon_manual, biaya_admin_refund
- [ ] `TransitionCode`: T01–T14
- [ ] `InspectionStage`: serah, kembali
- [ ] `InspectionCondition`: baik, cacat_ringan, rusak

### 3.2 Model Eloquent (app/Models/) — 27 model
**Identitas (3):**
- [ ] `Pengguna` (staff) — table: pengguna, guard: staff
- [ ] `Pelanggan` — table: pelanggan, guard: customer
- [ ] `KodeOtp` — table: kode_otp

**Katalog (6):**
- [ ] `Kategori` — table: kategori
- [ ] `Produk` — table: produk, relasi: kategori, varian, opsi, gambar
- [ ] `VarianProduk` — table: varian_produk, relasi: unit_sewa
- [ ] `OpsiProduk` — table: opsi_produk, relasi: nilai_opsi
- [ ] `NilaiOpsi` — table: nilai_opsi
- [ ] `GambarProduk` — table: gambar_produk (bukan product_images)

**Unit Sewa (1):**
- [ ] `UnitSewa` — table: unit_sewa, scope: aktif()

**Acuan (6):**
- [ ] `ZonaAntar` — table: zona_antar
- [ ] `TarifKerusakan` — table: tarif_kerusakan
- [ ] `Pengaturan` — table: pengaturan, static `get($key, $default)`, `set($key, $val)`
- [ ] `RekeningPembayaran` — table: rekening_pembayaran
- [ ] `BlokirKalender` — table: blokir_kalender
- [ ] `KapasitasProduksiHarian` — table: kapasitas_produksi_harian

**Pesanan (5):**
- [ ] `Pesanan` — table: pesanan, relasi: item, pembayaran, penyesuaian, riwayat_status, inspeksi
- [ ] `ItemPesanan` — table: item_pesanan, relasi: opsi
- [ ] `OpsiItemPesanan` — table: opsi_item_pesanan
- [ ] `PenyesuaianPesanan` — table: penyesuaian_pesanan
- [ ] `RiwayatStatusPesanan` — table: riwayat_status_pesanan

**Reservasi (1):**
- [ ] `HariReservasi` — table: hari_reservasi

**Pembayaran & Inspeksi (3):**
- [ ] `Pembayaran` — table: pembayaran, unique: nominal_tertunda
- [ ] `InspeksiSewa` — table: inspeksi_sewa, unique: (item_pesanan_id, tahap)
- [ ] `FotoInspeksi` — table: foto_inspeksi, unique: (inspeksi_sewa_id, urutan 1-6)

**Log (1):**
- [ ] `LogAktivitas` — table: log_aktivitas, append-only

### 3.3 Action Classes (app/Actions/) — satu tugas per kelas
**Auth:**
- [ ] `IssueOtp` — kirim OTP, rate limit, hash simpan
- [ ] `VerifyOtp` — cek hash, max 5 percobaan, mark used, login/create customer
- [ ] `StaffLogin` — email/password, rate limit 5/menit, force password change first login
- [ ] `LogoutCustomer`, `LogoutStaff`

**Katalog:**
- [ ] `GetAvailableVariantsForDate` — query varian dengan unit bebas pada tanggal (NFR-04: <500ms)
- [ ] `GetProductDetail` — load varian, opsi, gambar, ketersediaan

**Pemesanan:**
- [ ] `CalculateOrderPrice` — server-side only, salin harga, hitung ongkir zona, DP, rounding Rp1.000
- [ ] `PlaceOrder` — **transaksi DB**: cek kapasitas, lock unit (SELECT FOR UPDATE), insert hari_reservasi (unique unit+tanggal), buat pesanan+item+opsi, generate nomor BK-YYMM-NNNN, buat tagihan + kode unik 1-999, set tahan_hingga_pada (60/30 menit)
- [ ] `CancelOrderByCustomer` — hanya status awaiting_payment, release unit
- [ ] `RequestCancellation` — setelah dikonfirmasi, butuh approval admin/owner
- [ ] `ReactivateExpiredOrder` — T13/T14, cek unit masih bebas, lock ulang dalam transaksi

**Pembayaran:**
- [ ] `CreatePaymentBill` — hitung tagihan + kode unik, cek unique nominal_tertunda
- [ ] `VerifyPayment` — admin/owner only, cek nominal sama persis, update status, update pesanan status & saldo
- [ ] `RejectOrAcceptWithNotePayment` — nominal beda: tolak (alasan wajib) / terima dengan catatan
- [ ] `RecordCashPayment` — tunai (siap jual & deposit jaminan)
- [ ] `RecordRefund` — hitung BR-13 (min dari waktu & status), catat penyesuaian, admin catat transfer

**Operasional:**
- [ ] `TransitionOrderStatus` — **satu pintu** semua perpindahan status, cek tabel transisi T01-T14, write riwayat_status_pesanan
- [ ] `RecordHandover` — inspeksi serah, foto 1-6, cek saldo nol, jaminan, status → handed_over
- [ ] `RecordReturn` — inspeksi kembali, foto, hitung denda BR-10 (jam × Rp25.000, max tarif harian), catat kerusakan BR-11, status → returned
- [ ] `CompleteOrder` — cek saldo nol & jaminan dikembalikan/disita, status → completed
- [ ] `ExpireStaleOrders` — scheduler tiap menit: status awaiting_payment + tahan_hingga_pada < now → expired, release unit & stok

**Katalog & Pengaturan (Owner):**
- [ ] `ManageProduct` — CRUD produk, varian, opsi, gambar (WebP, EXIF strip, alt text wajib)
- [ ] `ManageRentalUnit` — CRUD unit_sewa, aktif/nonaktif
- [ ] `ManageSettings` — update pengaturan, log aktivitas (sebelum/sesudah)
- [ ] `ManageCalendarBlock` — blokir tanggal/produk, cek pesanan aktif bentrok (peringatan saja)
- [ ] `ManageProductionCapacity` — set batas harian per kelompok_kapasitas

**Laporan:**
- [ ] `GenerateSummaryReport` — periode harian/mingguan/bulanan, hanya pembayaran terverifikasi
- [ ] `ExportAnonymousResearchData` — CSV anonim (tanpa nama, WA, alamat, nomor pesanan → kode acak)

### 3.4 Form Request (app/Http/Requests/)
- [ ] `IssueOtpRequest` — nomor_wa (regex Indonesia, unique check), g-recaptcha-response (optional)
- [ ] `VerifyOtpRequest` — nomor_wa, kode (6 digit), g-recaptcha-response
- [ ] `StaffLoginRequest` — email, password, g-recaptcha-response
- [ ] `PlaceOrderRequest` — produk_id, varian_id, tanggal_serah, opsi[], tulisan_kustom (judul 25, penerima 45, pengirim 40), cara_serah, alamat_antar, zona_antar_id, jam_serah, catatan, setuju_syarat
- [ ] `VerifyPaymentRequest` — pembayaran_id, action (terima/tolak/catat), nominal_diterima, catatan (wajib jika tolak/catat)
- [ ] `TransitionStatusRequest` — pesanan_id, status_baru, kode_transisi, catatan
- [ ] `RecordHandoverRequest` — item_pesanan_id, kondisi, catatan, foto[], jaminan_tercatat
- [ ] `RecordReturnRequest` — item_pesanan_id, kondisi, catatan, foto[], kerusakan[], telat_jam
- [ ] `ManageProductRequest` — validasi foto: JPG/PNG/WebP, max 5MB, min 800px width, alt text wajib
- [ ] `ManageSettingsRequest` — kunci, nilai, tipe (teks/angka/boolean/json)

### 3.5 API Resource (app/Http/Resources/) — untuk API & Inertia/Blade data
- [ ] `ProductResource`, `ProductDetailResource`
- [ ] `OrderResource`, `OrderDetailResource`
- [ ] `PaymentResource`
- [ ] `CustomerResource`, `StaffResource`
- [ ] `SettingsResource`

---

## FASE 4: ROUTE & CONTROLLER (Target: 19-21 Nov)

### 4.1 Route Groups & Middleware
- [ ] `Route::middleware('guest:customer')` → pelanggan (OTP)
- [ ] `Route::middleware('auth:customer')` → pelanggan login
- [ ] `Route::middleware('guest:staff')` → staf login
- [ ] `Route::middleware(['auth:staff', 'role:owner,admin'])` → owner/admin
- [ ] `Route::middleware(['auth:staff', 'role:staf_produksi,staf_kurir'])` → staf
- [ ] Custom middleware: `RoleMiddleware` (cek peran), `OwnershipMiddleware` (cek pemilik data → 404 bukan 403)

### 4.2 Controller Structure (app/Http/Controllers/)
**Public (tanpa login):**
- [ ] `CatalogController` — index, show, searchByDate (GET)
- [ ] `LegalController` — syaratSewa, kebijakanPrivasi, kontak
- [ ] `OtpController` — showForm, issue, verify, resend (POST)

**Pelanggan (auth:customer):**
- [ ] `CustomerOrderController` — create (dari katalog), store (PlaceOrder), show (detail + track), index (riwayat), cancel, requestCancellation
- [ ] `CustomerPaymentController` — show (halaman bayar), confirm (kirim WA)
- [ ] `CustomerProfileController` — edit (nama), destroy (anonimkan, cek pesanan aktif)

**Staf Login (guest:staff):**
- [ ] `StaffAuthController` — showLogin, login, logout, passwordChangeFirstLogin

**Owner/Admin (auth:staff + role):**
- [ ] `DashboardController` — hariIni (antaran, jemput, produksi, verifikasi, peringatan unit belum kembali)
- [ ] `AdminOrderController` — index (filter status/tanggal), show, manualStore (pesanan WA), transitionStatus (T01-T14 via Action), verifyPayment, rejectPayment, recordCash, recordRefund, reactivateExpired
- [ ] `AdminCatalogController` — index, create, store, edit, update, archive, publish, manageVariants, manageOptions, manageImages, manageUnits
- [ ] `AdminSettingsController` — index, update (business rules), manageCalendarBlocks, manageProductionCapacity
- [ ] `AdminReportController` — index (rekap), exportAnonymousCsv
- [ ] `AdminActivityLogController` — index (read-only)

**Staf Produksi & Kurir (auth:staff + role:staf):**
- [ ] `StaffProductionController` — index (antrean rangkai), startProduction (T05), markReady (T07)
- [ ] `StaffDeliveryController` — index (agenda antar/jemput), handover (T08), receiveReturn (T09)

### 4.3 Scheduler (app/Console/Kernel.php)
- [ ] `ExpireStaleOrders` — everyMinute()
- [ ] `ReleaseExpiredReservations` — everyMinute() (sudah di Action ExpireStaleOrders)

---

## FASE 5: VIEW BLADE + TAILWIND + ALPINE.JS (Target: 21-24 Nov)

### 5.1 Design Token (resources/css/app.css + tailwind.config.js)
- [ ] Warna utama dari logo @berkahkreatif_ (color picker), bukan palet bawaan
- [ ] Token: `primary`, `secondary`, `accent`, `success`, `warning`, `danger`, `neutral`
- [ ] Dark mode: `class` strategy, cek kontras 4.5:1 semua pasangan
- [ ] Font: self-hosted (Inter/Plus Jakarta Sans), size base 16px, minimal 44px touch target
- [ ] Spacing: 4px base, consistent scale
- [ ] Animasi: `prefers-reduced-motion: reduce` → disable semua transisi

### 5.2 Layout & Components (resources/views/)
**Layout:**
- [ ] `layouts.guest` — header minimal, footer (kontak, syarat, privasi, WA)
- [ ] `layouts.customer` — header dengan nama pelanggan, menu: Katalog, Pesanan Saya, Profil
- [ ] `layouts.staff` — sidebar/nav bawah mobile: Agenda, Pesanan, Katalog, Rekap, Akun
- [ ] `layouts.admin` — sidebar kiri desktop, nav bawah mobile

**Komponen reusable (resources/views/components/):**
- [ ] `Button` (primary, secondary, ghost, danger, disabled, loading, full-width mobile)
- [ ] `Input` (text, tel, email, date, time, select, textarea, counter char)
- [ ] `Card` (produk, pesanan, ringkasan biaya)
- [ ] `Badge` (status pesanan, status pembayaran, ketersediaan)
- [ ] `Stepper` (progress pemesanan 5 langkah)
- [ ] `Timeline` (riwayat status pelanggan)
- [ ] `Modal` (konfirmasi batal, hapus akun, verifikasi pembayaran)
- [ ] `Toast` (success, error, info, auto-dismiss 3s)
- [ ] `ImageGallery` (lazy load, srcset, alt text)
- [ ] `OtpInput` (6 kotak, auto-focus, paste support, autocomplete=one-time-code)

### 5.3 Halaman Pelanggan (26 layar dari SRS 5.5)
- [ ] **K-01** Beranda/Katalog (`catalog.index`) — grid 2 kolom mobile, 4 kolom desktop, filter kategori, tanggal, "Cek ketersediaan"
- [ ] **K-02** Detail Produk Sewa (`catalog.show`) — galeri foto, varian warna, tanggal, opsi, total, "Lanjut Pesan"
- [ ] **K-10** Detail Produk Custom (`catalog.showCustom`) — tanpa varian unit, opsi teks/pilihan/satuan, pratinjau teks
- [ ] **K-03** Masuk OTP 2 langkah (`otp.form`, `otp.verify`) — nomor WA → kode 6 kotak, hitung mundur 60s kirim ulang
- [ ] **K-04** Isi Pesanan (`order.create`) — ringkasan produk, teks papan 3 kolom (live preview), cara serah (antar/ambil), alamat+zona+jam, ringkasan biaya sticky bottom, "Buat Pesanan"
- [ ] **K-05** Pembayaran (`payment.show`) — hitung mundur 60/30 menit, nominal + kode unik, rekening+QRIS, "Salin nominal", "Kirim konfirmasi WA"
- [ ] **K-06** Lacak Pesanan (`order.show`) — timeline status, rincian, jadwal, tombol sesuai status (Bayar/Batalkan/Ajukan Pembatalan/Hubungi WA)
- [ ] **K-07** Pesanan Saya (`order.index`) — daftar + riwayat, auto-refresh 60s
- [ ] **K-08** Profil (`profile.edit`) — ubah nama, hapus akun (konfirmasi, anonimkan)
- [ ] **K-09** Kebijakan Privasi & Syarat Sewa — dari LegalController

### 5.4 Halaman Staf (sesuai wireframe S-01 sampai S-02b, A-01 sampai A-03, O-01 sampai O-04)
- [ ] **Staff Login** — email/password, force change password first login
- [ ] **S-01 Agenda Harian** — cards per tugas (antar/jemput/produksi/verifikasi), sorted by jam, peringatan unit belum kembali, bottom nav mobile
- [ ] **S-02a Verifikasi List** — daftar menunggu/terverifikasi, tap ke detail
- [ ] **S-02b Verifikasi Detail** — nominal diterima (input), rekening masuk (select), 3 tombol sticky: Terima (disabled jika nominal beda), Tolak, Terima dengan catatan
- [ ] **Produksi** — antrean rangkai by tanggal acara, start → in_production (teks terkunci), mark ready
- [ ] **Kurir Antaran** — checklist item (akrilik, easel, bunga, LED), upload foto 1-6, cek saldo nol, status → handed_over
- [ ] **Kurir Jemputan** — checklist kembali, foto, hitung denda otomatis (jam × 25k, max tarif harian), kerusakan (pilih tarif), status → returned
- [ ] **Admin Pesanan** — filter, search, manual input (form sama pelanggan + alasan pengecualian), transition status (dropdown valid T01-T14)
- [ ] **Admin Katalog** — CRUD produk, varian, opsi, gambar (drag-drop, WebP convert, alt wajib), draf/terbit, unit sewa CRUD
- [ ] **Admin Pengaturan** — form semua nilai bisnis (jam batas, ambang DP, penahanan, denda, zona, tarif kerusakan, retensi), log aktivitas read-only
- [ ] **Admin Rekap** — periode harian/mingguan/bulanan, rincian per kategori, hari tersibuk, unduh CSV anonim
- [ ] **Owner Log Aktivitas** — read-only, filter aksi/subjek/waktu

### 5.5 JavaScript (Alpine.js)
- [ ] `catalog.js` — filter kategori, date picker, availability check debounce
- [ ] `order-form.js` — live preview teks (debounce 200ms), char counter, zona ongkir auto, DP toggle, sticky total
- [ ] `otp.js` — 6 input auto-focus, paste split, resend countdown 60s, error inline
- [ ] `payment.js` — countdown timer, copy nominal (toast "Tersalin"), WA link generator
- [ ] `tracking.js` — auto-refresh 60s (Visibility API: pause jika tab hidden)
- [ ] `staff-agenda.js` — card tap expand, bottom nav active state
- [ ] `verification.js` — nominal input validation (Terima disabled jika != nominal_diharapkan)
- [ ] `handover.js` — photo upload preview (max 6), checklist toggle, submit validation
- [ ] `settings.js` — dirty form warning, activity log timestamp format

---

## FASE 6: SPESIFIKASI API (OpenAPI) (Target: 22 Nov)

### 6.1 Buat `docs/api-spec.yaml`
- [ ] **Auth**: POST /api/otp/issue, POST /api/otp/verify, POST /api/otp/resend, POST /api/staff/login, POST /api/staff/logout
- [ ] **Catalog**: GET /api/products, GET /api/products/{slug}, GET /api/products/{slug}/availability?date=...
- [ ] **Orders**: POST /api/orders, GET /api/orders, GET /api/orders/{nomor}, POST /api/orders/{nomor}/cancel, POST /api/orders/{nomor}/request-cancellation
- [ ] **Payments**: GET /api/orders/{nomor}/payment, POST /api/payments/{id}/confirm (WA link)
- [ ] **Staff**: GET /api/staff/agenda, GET /api/staff/orders, PATCH /api/staff/orders/{id}/transition, POST /api/staff/orders/{id}/handover, POST /api/staff/orders/{id}/return
- [ ] **Admin**: CRUD products, variants, options, units, settings, calendar-blocks, capacity
- [ ] **Reports**: GET /api/reports/summary?period=..., GET /api/reports/anonymous-csv

---

## FASE 7: PENGUJIAN (TEST SUITE) (Target: 24-26 Nov)

### 7.1 Unit Tests (tests/Unit/)
- [ ] `OrderServiceTest` — rumus harga (sewa×hari, custom+opsi, siap jual×jumlah, rounding Rp1.000), kode unik 1-999, DP 50% ambang 250k
- [ ] `PenaltyCalculatorTest` — denda telat: jam×25k, max tarif harian (kasus 3,4,5 jam), perpanjangan 50% dibulatkan ke atas Rp1.000
- [ ] `RefundCalculatorTest` — BR-13: min(waktu%, status%), kasus H-2(100%), H-1(50%), hari H(0%) × status Dikonfirmasi(100%), Diproduksi(50%), Siap(0%)
- [ ] `OrderNumberGeneratorTest` — format BK-YYMM-NNNN, unique 50 concurrent
- [ ] `AvailabilityQueryTest` — unit bebas pada tanggal, exclude blokir, exclude nonaktif
- [ ] `DateCalculatorTest` — lead time, cutoff 15:00, hari yang sama rules

### 7.2 Feature Tests (tests/Feature/)
- [ ] `AuthTest` — OTP issue/verify/resend, rate limit 3/15min per nomor+IP, max 5 salah → kode hangus, staff login rate limit 5/min, force password change
- [ ] `OrderTest` — PlaceOrder flow, double booking prevention (50 concurrent → 1 success), kapasitas produksi 20 concurrent sisa 1 → 1 success, expired release scheduler
- [ ] `PaymentTest` — verify exact amount, reject different amount, accept with note, unique kode unik, cash payment, refund recording
- [ ] `StatusTransitionTest` — all T01-T14 valid, invalid rejected, log written, 404 for other's data
- [ ] `CatalogTest` — public access, date search <500ms, custom vs sewa vs siap jual behavior

### 7.3 Concurrency Tests (tests/Concurrency/)
- [ ] `ReservationConcurrencyTest` — 50 requests same unit/date → 1 success, hari_reservasi unique constraint holds
- [ ] `OrderNumberConcurrencyTest` — 50 simultaneous → no duplicates
- [ ] `ProductionCapacityConcurrencyTest` — 20 requests capacity 1 → 1 success

### 7.4 Test Fixtures (tests/fixtures/)
- [ ] `products.php` — sample produk semua jenis
- [ ] `rental_units.php` — 12 unit papan (AKR-001 dst)
- [ ] `settings.php` — all business rules default values
- [ ] `zones.php` — 3 zona antar
- [ ] `damage_tariffs.php` — akrilik, bunga, easel

---

## FASE 8: AUTOMATION & DEPLOYMENT CONFIG (Target: 26-27 Nov)

### 8.1 GitHub Actions CI/CD (`.github/workflows/ci.yml`)
```yaml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env: { MYSQL_ROOT_PASSWORD: secret, MYSQL_DATABASE: siberkah_test }
        ports: [3306:3306]
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.5', extensions: mbstring, pdo_mysql, bcmath }
      - run: composer install --prefer-dist --no-progress
      - run: cp .env.ci .env
      - run: php artisan key:generate
      - run: php artisan migrate --force
      - run: vendor/bin/pint --test --format=github
      - run: php artisan test --compact --parallel
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.5' }
      - run: composer install --prefer-dist --no-progress
      - run: vendor/bin/pint --test --format=github
```

### 8.2 Deploy Script (`deploy.sh`)
```bash
#!/bin/bash
set -e
cd /var/www/siberkah
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
sudo systemctl reload php8.5-fpm nginx
```

### 8.3 Environment & Queue
- [ ] `.env.production` template: APP_ENV=production, APP_DEBUG=false, QUEUE_CONNECTION=database, SESSION_DRIVER=database
- [ ] Supervisor config untuk `queue:work` (3 workers) + `schedule:work`
- [ ] Cron fallback: `* * * * * cd /var/www/siberkah && php artisan schedule:run >> /dev/null 2>&1`

---

## FASE 9: AUDIT KEAMANAN & AKSESIBILITAS (Target: 27 Nov)

### 9.1 Security Audit
- [ ] `composer audit` — cek dependensi rentan, update jika ada
- [ ] **OWASP Top 10 manual check:**
  - A01 Broken Access Control → OwnershipMiddleware (404), RoleMiddleware, policy checks di Action
  - A02 Cryptographic Failures → OTP hash (bcrypt/argon2), password hash, HTTPS only, secure cookies
  - A03 Injection → Eloquent parameter binding, validated request, no raw SQL
  - A04 Insecure Design → rate limit OTP, unique constraints DB, single transition path
  - A05 Security Misconfiguration → .env not public, debug=false, trusted proxies
  - A06 Vulnerable Components → composer audit, npm audit
  - A07 Identity/Auth Failures → OTP 5 menit, hash, max 5 tries, staff 5/min, session 120min idle
  - A08 Software Integrity → signed commits, CI/CD verification
  - A09 Logging/Monitoring Failures → log_aktivitas append-only, no OTP in logs, log rotation 14 hari
  - A10 SSRF → OTP sender interface timeout 5s, validated URLs

### 9.2 Accessibility (WCAG 2.1 AA)
- [ ] Kontras warna: semua teks ≥4.5:1, large text ≥3:1 (Lighthouse + manual)
- [ ] Touch target: minimal 44×44px semua tombol, link, input
- [ ] Keyboard navigation: tab order logis, focus visible, skip link
- [ ] Screen reader: label pada semua input, alt text gambar, aria-live untuk toast/status
- [ ] Reduce motion: `prefers-reduced-motion` disable animasi
- [ ] Form validation: error di bawah field, aria-describedby, announcements
- [ ] Language: `lang="id"`, sapaan "Kak" di WA/konfirmasi, "Anda" di halaman resmi

---

## FASE 10: VERSIONING, CLEANUP & SERAH TERIMA (Target: 28-30 Nov)

### 10.1 Changelog & Versioning
- [ ] Buat `CHANGELOG.md` — format Keep a Changelog
  - v1.1 (16 Nov): SRS final, migrasi 27 tabel, constraints
  - v1.1-code (17-27 Nov): implementasi kode per fase
  - v1.1-release (30 Nov): rilis produksi
- [ ] `git tag -a v1.1 -m "Release SIBERKAH v1.1"`

### 10.2 Commit Natural (Human-style)
- [ ] Commit per fitur/logis, bukan per file
- [ ] Pesan: "feat: tambah PlaceOrder action dengan lock unit", "fix: perbaiki hitung mundur pembayaran", "docs: lengkapi Lampiran B harga produk"

### 10.3 README & Dokumentasi Teknis
- [ ] `README.md` — cara install lokal, env, migrasi, seed, queue, schedule, test, deploy
- [ ] `docs/ARCHITECTURE.md` — ringkasan arsitektur, Action pattern, ERD mermaid
- [ ] `docs/DEPLOYMENT.md` — server requirements, supervisor, nginx, SSL, backup

### 10.4 UAT & Peralihan (jika akses pemilik tersedia)
- [ ] Entri 15-20 produk katalog (foto asli) — 25-27 Nov
- [ ] Latihan pemulihan backup — sebelum 28 Nov
- [ ] Pelatihan staf (Admin, Produksi, Kurir) — 28 Nov
- [ ] UAT skenario lengkap — 28-29 Nov
- [ ] Paralel WhatsApp 1-7 Des — minimal 10 pesanan tanpa bentrok
- [ ] Rencana mundur: kembali ke WA jika galat berat

### 10.5 Pemangkasan Lingkup (jika Must belum selesai 22 Nov)
Jalankan berurutan tanpa perundingan:
1. Sederhanakan opsi per satuan (hilang opsi tipe `satuan`)
2. Pesanan hari yang sama hanya lewat WA
3. Stok siap jual → manual tersedia/habis
4. Hapus konversi WebP (simpan asli)
5. Log aktivitas hanya uang & status
6. Tunai → catatan manual
7. Kapasitas produksi → peringatan saja
8. Arsip produk → flag aktif/nonaktif saja

---

## RISIKO & PENANGANAN (Ringkasan dari SRS R-01 sampai R-10)

| Kode | Risiko | Penanganan |
|------|--------|------------|
| R-01 | Penyedia OTP belum jelas/diblokir | Putuskan 19 Nov, interface `OtpSender` swapable, hindari WA unofficial, sediakan AUTH-11 (tombol hubungi admin) |
| R-02 | Pemilik/admin tak dihubungi | Simpan nilai sebagai pengaturan, siapkan Lembar Validasi 1 halaman, lapor keterbatasan jujur |
| R-03 | Must terlalu banyak 17 hari | Gerbang 16 & 22 Nov + urutan pemangkasan |
| R-04 | Hosting tanpa cron/queue | Pastikan cron tiap menit sebelum beli, alternatif VPS |
| R-05 | Staf enggan pakai sistem | Form sederhana, pelatihan, masa paralel |
| R-06 | Foto asli terlambat | Pakai foto akun resmi dengan izin, bukan stok |
| R-07 | Data uji terlalu sedikit | Uji konkurensi, skenario simulasi, nyatakan batas klaim |
| R-08 | Animasi tidak mulus di in-app browser | Uji NFR-33, animasi ringan, hormati prefers-reduced-motion |
| R-09 | Izin SRS v1 belum dikonfirmasi | Minta izin tertulis Rifa Nadliaul Jannah, cantumkan nama |
| R-10 | Pengembang tunggal sakit | Gunakan hari penyangga 29 Nov + terapkan pemangkasan |

---

## CHECKLIST KELENGKAPAN (Definition of Done)

- [ ] Semua 43 kebutuhan **Must** lulus uji (Unit + Feature + Concurrency)
- [ ] Tidak ada galat berat (error 500, data corrupt, double booking) yang terbuka
- [ ] Basis data build from zero: `php artisan migrate --seed` jalan tanpa error
- [ ] `README.md` menjelaskan seluruh langkah pemasangan lokal & produksi
- [ ] `vendor/bin/pint --format=agent` bersih (0 temuan)
- [ ] `php artisan test --compact` semua hijau
- [ ] Lighthouse mobile Performance ≥85, LCP ≤2.5s, CLS ≤0.1
- [ ] Security audit: composer audit bersih, OWASP Top 10 manual ✓
- [ ] A11y: WCAG 2.1 AA ✓ (kontras, 44px, keyboard, screen reader, reduce motion)
- [ ] Git tag `v1.1` dibuat, CHANGELOG.md terisi

---

## CATATAN PENTING

1. **Semua angka bisnis** (harga, denda, zona, lead time, penahanan, refund, retensi) **HARUS** dari tabel `pengaturan`, bukan hardcoded. Gunakan `Pengaturan::get('kunci', default)`.

2. **Tidak ada logika bisnis di Controller**. Controller hanya: validate request → call Action → return response. Action handle DB transaction.

3. **Satu pintu status**: Semua perpindahan status lewat `TransitionOrderStatus` action yang cek tabel transisi T01-T14 dan write `riwayat_status_pesanan`.

4. **Concurrency aman**: Unique constraint `hari_reservasi (unit_sewa_id, tanggal)` + `pembayaran.nominal_tertunda` + `urutan_nomor_pesanan` row lock.

5. **Tidak ada akun bawaan**. Owner dibuat via artisan command oleh pengembang.

6. **Mode OTP log** (driver `log`) hanya untuk lokal. Di produksi wajib ganti ke penyedia nyata.

7. **Foto produk**: WebP conversion, EXIF strip, alt text wajib, max 5MB, min 800px. Gunakan intervention/image atau spatie/image.

8. **Anonimisasi pelanggan**: `dihapus_pada` set, nama → "Pelanggan Dihapus", nomor_wa → hash, tapi pesanan & pembayaran tetap utuh untuk pembukuan.

---

*Dokumen ini adalah rencana lengkap. Setiap fase dikerjakan berurutan, tapi jika ada yang bisa paralel (misal: test sambil nunggu design token), lakukan. Prioritas: Must dulu, Should kalau waktu cukup, Could/Won't diabaikan versi ini.*