# SIBERKAH - Complete Business Logic Analysis

## Project Overview
SIBERKAH is a Laravel 13.x (PHP 8.3+) application for a rental/sales/custom-order business (Berkah Kreatif). It handles:
- Product catalog (rental, sale, custom orders)
- Order management with complex state machine (T01-T14)
- Payment processing with DP/pelunasan/penuh/refund
- Production scheduling with capacity management
- Delivery/return with inspections
- Calendar blocking & availability
- OTP-based customer authentication
- Staff authentication with role-based access

---

## 1. ORDER FLOW (K-01 to K-06, BR-01 to BR-20)

### Customer-Facing Order Flow

**K-01: Beranda/Katalog**
- Public grid display (2-col mobile, 4-col desktop)
- Filters: category, date availability, product type (sewa/jual/custom)
- Controller: `CatalogController::index()`

**K-02: Detail Produk**
- Photo gallery, variants, date picker, options, live total calculation
- Availability check via `hari_reservasi` for rental products
- Controller: `CatalogController::show()`

**K-03: Masuk OTP (2-step)**
- Phone → 6-digit code, 60s countdown, 5 attempt limit
- Creates/updates `pelanggan` on successful verification
- Controller: `OtpController` (issue, verify, resend)

**K-04: Isi Pesanan**
- Form: product summary, custom text (for custom products), delivery method, address+zone+time, notes
- Validation: `tanggal_serah`, `cara_serah`, `alamat_antar` (if antar), `zona_antar_id`, `jam_serah`
- On submit: `PlaceOrder` action (not yet implemented)
- Creates: `pesanan` + `item_pesanan` + `opsi_item_pesanan` + `hari_reservasi` (for sewa)
- Generates: `nomor` (BK-YYMM-NNNN via `urutan_nomor_pesanan`), `kode_unik` (1-999), `tahan_hingga_pada` (60/30 min)
- Controller: `CustomerOrderController::create()` / `store()`

**K-05: Pembayaran**
- Countdown timer (60 min DP, 30 min pelunasan)
- Shows: total + kode_unik = `nominal_diharapkan`
- Payment methods: bank transfer (rekening + QRIS), "Salin nominal", "Kirim konfirmasi WA"
- Creates `pembayaran` record with `nominal_tertunda` (unique constraint)
- Controller: `CustomerPaymentController::show()`

**K-06: Lacak Pesanan**
- Timeline status display, order details, schedule
- Auto-refresh 60s on "Pesanan Saya" page
- Controller: `CustomerOrderController::show()`

### Business Rules (BR-01 to BR-20)

| Rule | Description | Implementation Status |
|------|-------------|----------------------|
| BR-01 | Order number format: BK-YYMM-NNNN | Schema: `urutan_nomor_pesanan` table |
| BR-02 | Unique constraint on unit+date (`hari_reservasi`) | Migration: `uniq_unit_tanggal` |
| BR-03 | Same-day order allowed for jual/sewa only | `ProductType::allowsSameDayOrder()` |
| BR-04 | Total < 250k → wajib lunas; ≥ 250k → DP 50% or lunas | `PaymentType::canBeCash()`, `Pembayaran::bisaLunas()` |
| BR-05 | Kode unik 1-999 per tagihan | `Pembayaran::kode_unik`, unique `nominal_tertunda` |
| BR-06 | Hold time: 60 min (DP), 30 min (pelunasan) | `pesanan.tahan_hingga_pada` |
| BR-07 | Payment verified manually by admin/owner | `Pembayaran::status` enum, `AdminOrderController::verifyPayment()` |
| BR-08 | Cash allowed for penuh & refund only | `PaymentType::canBeCash()` |
| BR-09 | DP 50% of total (configurable) | `Pengaturan` table |
| BR-10 | Pelunasan deadline: configurable days before delivery | `pesanan.batas_pelunasan_pada` |
| BR-11 | Jaminan (deposit) collected at handover, not uploaded | `pesanan.jenis_jaminan`, `nominal_jaminan` |
| BR-12 | Jaminan returned after inspection | `pesanan.jaminan_kembali_pada` |
| BR-13 | Refund %: min(time-based, status-based) | `persen_refund`, `nominal_refund` fields |
| BR-14 | Custom text for custom products | `item_pesanan.tulisan_kustom` |
| BR-15 | Options: required/optional with price add-ons | `opsi_produk.wajib`, `nilai_opsi.harga_tambahan` |
| BR-16 | Variants have stock (jual) or units (sewa) | `varian_produk.stok`, `unit_sewa` |
| BR-17 | Delivery zones with max km & ongkir | `zona_antar` table, `DeliveryType::requiresAddress()` |
| BR-18 | Daily production capacity per group | `kapasitas_produksi_harian` table |
| BR-19 | Calendar blocking (full store or per product) | `blokir_kalender` table |
| BR-20 | Late return penalty: jam × 25k, max daily rate | `StaffDeliveryController::receiveReturn()` TODO |

---

## 2. PAYMENT FLOW (BYR-01 to BYR-09)

### Payment Types (`PaymentType` enum)
- **DP** (dp): Down payment 50%
- **PELUNASAN** (pelunasan): Final payment
- **PENUH** (penuh): Full payment (for totals < 250k)
- **REFUND** (refund): Refund to customer

### Payment Status (`PaymentStatus` enum)
- **MENUNGGU**: Awaiting verification
- **TERVERIFIKASI**: Verified by admin
- **DITOLAK**: Rejected by admin
- **KEDALUWARSA**: Expired (hold time passed)

### Key Payment Logic

**Unique Code System (BYR-01, BYR-02)**
- Each payment gets `kode_unik` (1-999)
- `nominal_diharapkan = tagihan + kode_unik`
- `nominal_tertunda` has UNIQUE constraint → prevents duplicate pending amounts
- Customer must transfer exact amount including code

**Verification Flow (BYR-03 to BYR-05)**
1. Customer transfers & clicks "Konfirmasi" → creates `pembayaran` with `dikonfirmasi_pelanggan_pada`
2. Admin views in `AdminOrderController::verifyPayment()`
3. Admin accepts/rejects → updates `status`, `diverifikasi_pada`, `catatan_verifikasi`
4. If accepted: order status → `dikonfirmasi` (T02), `dikonfirmasi_pada` set

**Hold Expiry (BYR-06, BYR-07)**
- Scheduler should check: `status = menunggu_pembayaran` AND `tahan_hingga_pada < now`
- Action: release units, set status → `kedaluwarsa` (T03), create new `pembayaran` with status `kedaluwarsa`
- Not yet implemented (TODO in `AdminOrderController::reactivateExpired()`)

**Refund Calculation (BYR-08, BYR-09)**
- Based on BR-13: minimum of time-based % and status-based %
- `pesanan.persen_refund`, `nominal_refund` stored
- Creates `pembayaran` with `jenis = refund`, `status = terverifikasi`
- Creates `penyesuaian_pesanan` with `jenis = biaya_admin_refund` or similar

---

## 3. PRODUCTION/DELIVERY FLOW (PRD-01 to PRD-08, DEL-01 to DEL-07)

### Production Flow

**PRD-01: Start Production (T05)**
- Role: `staf_produksi`, `owner`, `admin`
- `status: dikonfirmasi` → `sedang_diproduksi`
- Custom products: text locked, no changes allowed
- Controller: `StaffProductionController::startProduction()` (TODO: use TransitionOrderStatus action)

**PRD-02: Mark Ready (T06/T07)**
- Role: `staf_produksi`, `owner`, `admin`
- `dikonfirmasi` → `siap` (T06) OR `sedang_diproduksi` → `siap` (T07)
- Controller: `StaffProductionController::markReady()` (TODO)

**PRD-03: Daily Capacity Check (KAP-01 to KAP-04)**
- Custom products have `kelompok_kapasitas`
- `kapasitas_produksi_harian` sets daily limit per group
- Must check before confirming orders for custom products

**PRD-04: Production Agenda (S-01)**
- Dashboard for production staff
- Cards: pending production, delivery, verification
- Controller: `StaffProductionController::index()`

### Delivery/Return Flow

**DEL-01: Handover / Antaran (T08)**
- Role: `staf_kurir`, `owner`, `admin`
- Checklist: akrilik, easel, bunga, LED
- Upload 1-6 photos (sequence 1-6)
- **CRITICAL**: Check `saldo == 0` before handover (BR-26)
- Creates `inspeksi_sewa` with `tahap = serah`
- Status: `siap` → `sudah_diserahkan`
- Controller: `StaffDeliveryController::handover()` (TODO)

**DEL-02: Return / Jemputan (T09)**
- Role: `staf_kurir`, `owner`, `admin`
- Checklist for return items
- Upload 1-6 photos
- **Late penalty**: hours_late × 25,000 (max daily rate) - AUTO CALCULATED
- **Damage**: select from `tarif_kerusakan` table
- Creates `inspeksi_sewa` with `tahap = kembali`, `kondisi`
- Status: `sudah_diserahkan` → `sudah_dikembalikan`
- Controller: `StaffDeliveryController::receiveReturn()` (TODO)

**DEL-03: Complete Order (T10/T11)**
- Role: `owner`, `admin` only
- `sudah_diserahkan` → `selesai` (T10, for sale products)
- `sudah_dikembalikan` → `selesai` (T11, for rental)
- Controller: Not yet implemented

---

## 4. RENTAL/RETURN FLOW (SEW-01 to SEW-07)

### Rental-Specific Logic

**SEW-01: Unit-Based Availability**
- Each physical unit tracked in `unit_sewa` (kode, status: aktif/perawatan/rusak/pensiun)
- `hari_reservasi`: one row per unit per date (`uniq_unit_tanggal` prevents double booking)
- Availability = total units - reserved units for date

**SEW-02: Multi-Day Rental**
- `item_pesanan.tanggal_mulai` to `tanggal_selesai`
- Creates `hari_reservasi` rows for each date in range
- Each date consumes one unit-day

**SEW-03: Handover Inspection (Serah)**
- `inspeksi_sewa.tahap = 'serah'`
- Condition: baik/cacat_ringan/rusak
- 6 photos required (sequence 1-6)
- Stores baseline condition

**SEW-04: Return Inspection (Kembali)**
- `inspeksi_sewa.tahap = 'kembali'`
- Compare with serah inspection
- Calculate: late penalty + damage charges
- Creates `penyesuaian_pesanan` for adjustments

**SEW-05: Security Deposit (Jaminan)**
- Collected at handover (not uploaded)
- `pesanan.jenis_jaminan`, `nominal_jaminan`
- Returned after return inspection: `jaminan_kembali_pada`

**SEW-06: Unit Status Management**
- Units can be: aktif, perawatan, rusak, pensiun
- Only `aktif` units available for reservation
- Managed via `AdminCatalogController::manageUnits()`

**SEW-07: Extension (Perpanjangan)**
- Creates `penyesuaian_pesanan` with `jenis = perpanjangan`
- Additional `hari_reservasi` rows for extended dates
- Additional charge calculated

---

## 5. CAPACITY MANAGEMENT (KAP-01 to KAP-04)

### Daily Production Capacity

**KAP-01: Capacity Groups**
- Products with `kelompok_kapasitas` (e.g., "buket_custom", "papan_sewa")
- Set via `AdminSettingsController::manageProductionCapacity()`
- Stored in `kapasitas_produksi_harian` (date + group + limit)

**KAP-02: Capacity Check on Order**
- Before confirming custom orders, check capacity for delivery date
- Sum of confirmed custom orders for group ≤ daily limit
- Not yet implemented in `PlaceOrder` action

**KAP-03: Override Per Date**
- Admin can set specific limits per date (e.g., holiday higher capacity)
- `kapasitas_produksi_harian` supports per-date overrides

**KAP-04: Capacity Dashboard**
- View in admin settings
- Shows utilization per date/group

---

## 6. CALENDAR BLOCKING (BLK-01 to BLK-03)

### Calendar Blocking Logic

**BLK-01: Full Store Closure**
- `blokir_kalender` with `produk_id = NULL`
- Blocks ALL products for that date
- Created via `AdminSettingsController::manageCalendarBlocks()`

**BLK-02: Product-Specific Blocking**
- `blokir_kalender` with specific `produk_id`
- Only blocks that product (e.g., custom products on holiday)
- Allowed even if other products have orders

**BLK-03: Conflict Prevention**
- Before blocking: check for active orders on that date
- If full-store block & active orders exist → REJECT
- If product-specific block & that product has orders → REJECT
- Implemented in `AdminSettingsController::manageCalendarBlocks()`

---

## 7. OTP AUTHENTICATION

### Customer OTP Flow

**Issue OTP (`OtpController::issue()`)**
- Input: Indonesian phone (08xx, 628xx, +628xx)
- Normalizes to +628xxxxxxxxxx
- Generates 6-digit code, hashes with bcrypt
- Expiry: 5 minutes (`kedaluwarsa_pada`)
- Reuses existing valid OTP if within 5 min
- Rate limit: 60 seconds between resends (`canResend()`)
- Stores: `nomor_wa`, `kode_hash`, `alamat_ip`, `percobaan`, `status_kirim`

**Verify OTP (`OtpController::verify()`)**
- Max 5 attempts (`percobaan` counter)
- On 5 failures: `status_kirim = 'gagal'`
- On success: finds/creates `pelanggan`, marks OTP `digunakan`
- Sets `nomor_terverifikasi_pada`, `masuk_terakhir_pada`
- Logs `LogAktivitas::create(['aksi' => 'login_otp'])`
- Redirects to customer dashboard

**Resend OTP (`OtpController::resend()`)**
- Invalidates old OTP (`status_kirim = 'dibatalkan'`)
- Generates new code
- Respects 60s cooldown

### Staff Authentication
- Email/password login (`StaffAuthController`)
- Guard: `staff` with `Pengguna` model
- First login forces password change
- Roles: owner, admin, staf_produksi, staf_kurir
- Role-based permissions via `UserRole` enum methods

---

## 8. ACTIVITY LOGGING

### Log Structure (`LogAktivitas` model)
```php
- pengguna_id (nullable) - staff who performed action
- pelanggan_id (nullable) - customer who performed action
- aksi - action identifier (e.g., 'login_otp', 'pesanan_created', 'status_transition')
- subjek_tabel - table name (e.g., 'pesanan', 'pembayaran')
- subjek_id - record ID
- sebelum - JSON of old values
- sesudah - JSON of new values
- alamat_ip - request IP
- dibuat_pada - timestamp
```

### Key Events Logged
- OTP login (`login_otp`)
- Order creation
- Status transitions (T01-T14) - should log `kode_transisi`
- Payment verification (accept/reject)
- Manual order input by admin
- Calendar block creation
- Capacity changes
- Settings updates
- Handover/return inspections

### Read-Only Access
- Only `owner` role can view (`AdminActivityLogController`)
- Paginated display, filterable

---

## 9. STATE MACHINE - ORDER STATUS TRANSITIONS (T01-T14)

### Status Enum (`OrderStatus`)
| Status | Value | Active? | Final? | Customer Label |
|--------|-------|---------|--------|----------------|
| MENUNGGU_PEMBAYARAN | menunggu_pembayaran | ✓ | ✗ | Payment pending |
| KEDALUWARSA | kedaluwarsa | ✗ | ✗ | Expired |
| DIKONFIRMASI | dikonfirmasi | ✓ | ✗ | Payment confirmed |
| SEDANG_DIPRODUKSI | sedang_diproduksi | ✓ | ✗ | In production |
| SIAP | siap | ✓ | ✗ | Ready for delivery |
| SUDAH_DISERAHKAAN | sudah_diserahkan | ✓ | ✗ | Delivered |
| SUDAH_DIKEMBALIKAN | sudah_dikembalikan | ✓ | ✗ | Returned |
| SELESAI | selesai | ✗ | ✓ | Completed |
| DIBATALKAN | dibatalkan | ✗ | ✓ | Cancelled |

### Valid Transitions (from `OrderStatus::validTransitions()`)

```
MENUNGGU_PEMBAYARAN → DIKONFIRMASI (T02), KEDALUWARSA (T03), DIBATALKAN (T04)
KEDALUWARSA → DIKONFIRMASI (T13), KEDALUWARSA (T14 - refund)
DIKONFIRMASI → SEDANG_DIPRODUKSI (T05), SIAP (T06), DIBATALKAN (T12)
SEDANG_DIPRODUKSI → SIAP (T07), DIBATALKAN (T12)
SIAP → SUDAH_DISERAHKAAN (T08), DIBATALKAN (T12)
SUDAH_DISERAHKAAN → SUDAH_DIKEMBALIKAN (T09), SELESAI (T10)
SUDAH_DIKEMBALIKAN → SELESAI (T11)
SELESAI → (none)
DIBATALKAN → (none)
```

### Transition Codes (`TransitionCode` enum)
| Code | From → To | Actor | Notes |
|------|-----------|-------|-------|
| T01 | (new) → MENUNGGU_PEMBAYARAN | System | Order created |
| T02 | MENUNGGU → DIKONFIRMASI | Admin | Payment verified |
| T03 | MENUNGGU → KEDALUWARSA | Scheduler | Hold expired |
| T04 | MENUNGGU → DIBATALKAN | Admin/Customer | Cancel before payment |
| T05 | DIKONFIRMASI → SEDANG_DIPRODUKSI | Production | Start production |
| T06 | DIKONFIRMASI → SIAP | Production | Ready (no production needed) |
| T07 | SEDANG_DIPRODUKSI → SIAP | Production | Production done |
| T08 | SIAP → SUDAH_DISERAHKAAN | Kurir | Handover (saldo=0 check) |
| T09 | SUDAH_DISERAHKAAN → SUDAH_DIKEMBALIKAN | Kurir | Return received |
| T10 | SUDAH_DISERAHKAAN → SELESAI | Admin | Sale product complete |
| T11 | SUDAH_DIKEMBALIKAN → SELESAI | Admin | Rental complete |
| T12 | Any active → DIBATALKAN | Admin | Cancel with refund calc |
| T13 | KEDALUWARSA → DIKONFIRMASI | Admin | Reactivate (payment received late) |
| T14 | KEDALUWARSA → DIBATALKAN | Admin | Refund for expired |

### Transition Enforcement
- **NOT YET IMPLEMENTED**: `TransitionOrderStatus` action class
- Controllers currently do direct `$pesanan->status = ...` 
- Should validate: `current->canTransitionTo(target)`
- Should log: `RiwayatStatusPesanan` with `kode_transisi`, `pelaku`, `pengguna_id`, `catatan`

---

## 10. STATUS ENUMS & USAGE SUMMARY

### PaymentStatus
| Value | Label | Badge | Allows New Bill? | Final? |
|-------|-------|-------|------------------|--------|
| MENUNGGU | Menunggu verifikasi | warning | ✗ | ✗ |
| TERVERIFIKASI | Terverifikasi | success | ✗ | ✓ |
| DITOLAK | Ditolak | danger | ✓ | ✓ |
| KEDALUWARSA | Kedaluwarsa | secondary | ✓ | ✓ |

### PaymentType
| Value | Label | Requires Code? | Can Be Cash? |
|-------|-------|----------------|--------------|
| DP | DP 50% | ✓ | ✗ |
| PELUNASAN | Pelunasan | ✓ | ✗ |
| PENUH | Lunas | ✓ | ✓ |
| REFUND | Refund | ✗ | ✓ |

### ProductType
| Value | Label | Uses Units? | Has Stock? | Custom? | Lead Time? | Capacity? |
|-------|-------|-------------|------------|---------|------------|-----------|
| SEWA | Disewakan | ✓ | ✗ | ✗ | ✓ | ✗ |
| JUAL | Dijual | ✗ | ✓ | ✗ | ✗ | ✗ |
| CUSTOM | Custom | ✗ | ✗ | ✓ | ✓ | ✓ |

### DeliveryType
| Value | Label | Needs Address? | Needs Time? |
|-------|-------|----------------|-------------|
| ANTAR | Diantar | ✓ | ✓ |
| AMBIL | Ambil di Toko | ✗ | ✓ |

### UserRole Permissions Matrix
| Permission | Owner | Admin | Staf Produksi | Staf Kurir |
|------------|-------|-------|---------------|------------|
| Admin Panel | ✓ | ✓ | ✗ | ✗ |
| Manage Catalog | ✓ | ✗ | ✗ | ✗ |
| Manage Settings | ✓ | ✗ | ✗ | ✗ |
| View Reports | ✓ | ✗ | ✗ | ✗ |
| View Logs | ✓ | ✗ | ✗ | ✗ |
| Manual Order | ✓ | ✓ | ✗ | ✗ |
| Transition Status | ✓ | ✓ | ✗ | ✗ |
| Start Production | ✓ | ✓ | ✓ | ✗ |
| Mark Ready | ✓ | ✓ | ✓ | ✗ |
| Handover | ✓ | ✓ | ✗ | ✓ |
| Receive Return | ✓ | ✓ | ✗ | ✓ |
| Complete Order | ✓ | ✓ | ✗ | ✗ |
| Cancel Order | ✓ | ✓ | ✗ | ✗ |
| Verify Payment | ✓ | ✓ | ✗ | ✗ |
| Record Payment | ✓ | ✓ | ✗ | ✗ |
| Calendar/Capacity | ✓ | ✓ | ✗ | ✗ |
| View Agenda | ✓ | ✓ | ✓ | ✓ |

---

## 11. MISSING / INCOMPLETE IMPLEMENTATIONS

### Critical Missing Actions (Need Implementation)
1. **PlaceOrder** - Order creation with unit locking, reservation, billing
2. **TransitionOrderStatus** - State machine enforcement + history logging
3. **RecordRefund** - BR-13 refund calculation + adjustment recording
4. **ExpireStaleOrders** - Scheduler: check `tahan_hingga_pada` → release units, set kedaluwarsa
5. **Handover Validation** - Saldo=0 check, checklist, photo upload, inspection creation
6. **Return Processing** - Late penalty calc, damage selection, inspection creation
7. **Capacity Check** - On order placement for custom products
8. **Availability Check** - Real-time on katalog for date selection

### TODO Items in Controllers
- `CustomerOrderController::store()` - Full PlaceOrder logic
- `CustomerPaymentController::confirm()` - Payment confirmation processing
- `AdminOrderController::transitionStatus()` - Use TransitionOrderStatus action
- `AdminOrderController::verifyPayment()` - Full verification flow
- `AdminOrderController::recordCash()` - Cash payment recording
- `AdminOrderController::recordRefund()` - Refund recording
- `AdminOrderController::reactivateExpired()` - Scheduler logic
- `StaffProductionController::startProduction()` - Use T05 transition
- `StaffProductionController::markReady()` - Use T06/T07 transition
- `StaffDeliveryController::handover()` - Full handover with checklist/photos/inspection
- `StaffDeliveryController::receiveReturn()` - Full return with penalties/damage

### Database Constraints (Good!)
- `hari_reservasi.uniq_unit_tanggal` - Prevents double booking at DB level
- `pembayaran.uniq_nominal_tertunda` - Prevents duplicate pending payments
- `inspeksi_sewa.uniq_inspeksi_tahap` - One inspection per phase per item
- `foto_inspeksi.uniq_foto_urutan` - 6 photos max per inspection
- `kapasitas_produksi_harian.uniq_kapasitas_tanggal` - One capacity setting per date/group

---

## 12. CONFIGURATION SETTINGS (Pengaturan Table)

Expected keys (from BR & code references):
| Key | Type | Description | Default |
|-----|------|-------------|---------|
| `batas_dp_persen` | integer | DP percentage (50) | 50 |
| `batas_dp_minimal` | integer | Min total for DP (250000) | 250000 |
| `hold_time_dp_menit` | integer | DP hold time (60) | 60 |
| `hold_time_pelunasan_menit` | integer | Pelunasan hold time (30) | 30 |
| `batas_pelunasan_hari` | integer | Days before delivery for pelunasan | 3 |
| `denda_telat_per_jam` | integer | Late penalty per hour (25000) | 25000 |
| `denda_telat_maks_harian` | boolean | Cap at daily rate | true |
| `refund_persen_per_hari` | json | Time-based refund % schedule | {...} |
| `refund_persen_per_status` | json | Status-based refund % | {...} |
| `kode_unik_min` | integer | Min unique code | 1 |
| `kode_unik_max` | integer | Max unique code | 999 |
| `otp_kedaluwarsa_menit` | integer | OTP expiry minutes | 5 |
| `otp_maks_percobaan` | integer | Max OTP attempts | 5 |
| `otp_resend_cooldown_detik` | integer | Resend cooldown | 60 |

---

## 13. RECOMMENDED NEXT STEPS

### Priority 1: Core Actions
1. Create `PlaceOrder` action class (handles K-04 fully)
2. Create `TransitionOrderStatus` action class (enforces T01-T14)
3. Create `RecordRefund` action class (BR-13)
4. Create `ExpireStaleOrders` scheduler command

### Priority 2: Staff Operations
5. Complete `StaffDeliveryController::handover()` with all validations
6. Complete `StaffDeliveryController::receiveReturn()` with penalty/damage calc
7. Complete `StaffProductionController` with proper transitions

### Priority 3: Admin Features
8. Complete `AdminOrderController` payment verification & manual flows
9. Add capacity check to order placement
10. Add real-time availability to catalog

### Priority 4: Polish
11. Add comprehensive tests for state machine
12. Add OTP rate limiting per IP
13. Add email notifications for status changes
14. Implement anonymous CSV export fully

---

## 14. FILE STRUCTURE REFERENCE

### Key Models
```
app/Models/
├── Enums/           # All business enums (9 files)
├── Pesanan.php      # Order (central)
├── Pembayaran.php   # Payment
├── ItemPesanan.php  # Order items
├── Produk.php       # Product
├── VarianProduk.php # Variants
├── UnitSewa.php     # Rental units
├── HariReservasi.php# Unit-date reservations
├── InspeksiSewa.php # Inspections
├── FotoInspeksi.php # Inspection photos
├── BlokirKalender.php
├── KapasitasProduksiHarian.php
├── Pelanggan.php    # Customer
├── Pengguna.php     # Staff user
├── LogAktivitas.php
├── Pengaturan.php   # Settings
├── KodeOtp.php
├── ZonaAntar.php
├── RekeningPembayaran.php
├── TarifKerusakan.php
└── ... (others)
```

### Key Controllers
```
app/Http/Controllers/
├── CustomerOrderController.php
├── CustomerPaymentController.php
├── CustomerProfileController.php
├── AdminOrderController.php
├── AdminCatalogController.php
├── AdminSettingsController.php
├── AdminReportController.php
├── AdminActivityLogController.php
├── StaffProductionController.php
├── StaffDeliveryController.php
├── StaffAuthController.php
├── OtpController.php
├── CatalogController.php
├── DashboardController.php
└── LegalController.php
```

### Migrations (in order)
```
1. 0001_01_01_000000_buat_tabel_sesi
2. 0001_01_01_000001_create_cache_table
3. 0001_01_01_000002_create_jobs_table
4. 2026_11_16_000001_buat_tabel_identitas
5. 2026_11_16_000002_buat_tabel_katalog
6. 2026_11_16_000003_buat_tabel_acuan
7. 2026_11_16_000004_buat_tabel_unit_sewa
8. 2026_11_16_000005_buat_tabel_pesanan
9. 2026_11_16_000006_buat_tabel_hari_reservasi
10. 2026_11_16_000007_buat_tabel_pembayaran_dan_inspeksi
11. 2026_11_16_000008_buat_tabel_log_aktivitas
12. 2026_11_16_000009_tambah_batasan_cek
13. 2026_11_16_000010_buat_tabel_gambar_produk
```