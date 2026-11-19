<?php

use App\Http\Controllers\AdminActivityLogController;
use App\Http\Controllers\AdminCatalogController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\TransferReconciliationController;
use App\Http\Controllers\PaidRentalCancellationController;
use App\Http\Controllers\CancellationRequestController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffDeliveryController;
use App\Http\Controllers\RentalCloseoutController;
use App\Http\Controllers\CatalogInventoryController;
use App\Http\Controllers\StoreSaleController;
use App\Http\Controllers\VerifiedRevenueController;
use App\Http\Middleware\RentalStaging;
use App\Http\Controllers\StaffProductionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

/**
 * PUBLIC ROUTES (guest access)
 * --------------------------------------------------------
 * Routes accessible to anyone, including unauthenticated visitors.
 */

/** Catalog - accessible to all visitors */
Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/katalog/{slug}', [CatalogController::class, 'show'])->name('catalog.show');

/** Legal pages */
Route::get('/syarat-sewa', [LegalController::class, 'syaratSewa'])->name('syarat-sewa');
Route::get('/privasi', [LegalController::class, 'kebijakanPrivasi'])->name('privasi');
Route::get('/kontak', [LegalController::class, 'kontak'])->name('kontak');

/** OTP Authentication - guest:customer */
Route::middleware(['guest:customer'])->group(function () {
    Route::get('/masuk', [OtpController::class, 'showForm'])->name('otp.form');
    Route::post('/otp/issue', [OtpController::class, 'issue'])->middleware('throttle:5,1')->name('otp.issue');
    Route::post('/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:10,1')->name('otp.verify');
    Route::post('/otp/resend', [OtpController::class, 'issue'])->middleware('throttle:5,1')->name('otp.resend');
});

/**
 * STAFF AUTH ROUTES (guest:staff)
 * --------------------------------------------------------
 * Routes for staff login (separate guard from customers).
 */

/** Staff authentication */
Route::middleware(['guest:staff'])->group(function () {
    Route::get('/staff/login', [StaffAuthController::class, 'showLogin'])->name('staff.login');
    Route::post('/staff/login', [StaffAuthController::class, 'login'])->middleware('throttle:5,1')->name('staff.login.post');
});

Route::middleware('auth:staff')->group(function () {
    Route::post('/staff/logout', [StaffAuthController::class, 'logout'])->name('staff.logout');
    Route::post('/staff/password-change', [StaffAuthController::class, 'changePassword'])->middleware(['role:owner,admin,staf_produksi,staf_kurir', 'throttle:5,1'])->name('staff.password.change');
});

Route::post('/keluar', [OtpController::class, 'logout'])->middleware('auth:customer')->name('otp.logout');

/**
 * CUSTOMER AUTH ROUTES (auth:customer)
 * --------------------------------------------------------
 * Routes requiring customer authentication via OTP.
 */
Route::middleware(['auth:customer', 'active.customer'])->group(function () {

    /** Customer profile and orders */
    Route::get('/pelanggan/profil', [CustomerProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/pelanggan/profil', [CustomerProfileController::class, 'update'])->name('profile.update');
    Route::delete('/pelanggan/profil', [CustomerProfileController::class, 'destroy'])->middleware('pending.transaction')->name('profile.destroy');

    /** Customer orders */
    Route::get('/pelanggan/pesanan', [CustomerOrderController::class, 'index'])->name('pesanan.index');
    Route::post('/pelanggan/pesanan', [CustomerOrderController::class, 'store'])->middleware('throttle:20,1')->name('pesanan.store');
    Route::get('/pelanggan/pesanan/{nomor}', [CustomerOrderController::class, 'show'])->name('pesanan.show');
    Route::post('/pelanggan/pesanan/{nomor}/cancel', [CustomerOrderController::class, 'cancel'])->middleware(RentalStaging::class)->name('pesanan.cancel');
    Route::get('/pelanggan/pesanan/{nomor}/request-cancellation', [CancellationRequestController::class, 'show'])->name('pesanan.cancellation-status');
    Route::post('/pelanggan/pesanan/{nomor}/request-cancellation', [CancellationRequestController::class, 'submit'])->middleware('throttle:10,1')->name('pesanan.request-cancellation');
});

/**
 * CUSTOMER PAYMENT ROUTES (auth:customer)
 * --------------------------------------------------------
 * Payment-related routes for authenticated customers.
 */
Route::middleware(['auth:customer', 'active.customer'])->group(function () {
    Route::get('/pesanan/{nomor}/bayar', [CustomerPaymentController::class, 'show'])->middleware(RentalStaging::class)->name('payment.show');
    Route::post('/pesanan/{nomor}/bayar/konfirmasi', [CustomerPaymentController::class, 'confirm'])->middleware(RentalStaging::class)->name('payment.confirm');
});

/**
 * ADMIN/OWNER ROUTES (auth:staff + role:owner,admin)
 * --------------------------------------------------------
 * Routes for staff with owner or admin privileges.
 */
Route::middleware(['auth:staff', 'role:owner,admin'])->group(function () {
    /** Dashboard */
    Route::get('/owner', [DashboardController::class, 'hariIni'])->name('dashboard.hari-ini');

    /** Admin Orders */
    Route::prefix('admin')->group(function () {
        Route::post('penjualan-toko', [StoreSaleController::class, 'store'])->middleware(RentalStaging::class)->name('toko.store');
        Route::post('penjualan-toko/{id}/selesai', [StoreSaleController::class, 'complete'])->whereNumber('id')->middleware(RentalStaging::class)->name('toko.selesai');
        Route::get('pesanan', [AdminOrderController::class, 'index'])->name('admin.pesanan.index');
        Route::get('pesanan/{id}', [AdminOrderController::class, 'show'])->whereNumber('id')->name('admin.pesanan.show');
        Route::post('pesanan/manual/sewa', [AdminOrderController::class, 'manualStore'])->middleware(RentalStaging::class)->name('admin.pesanan.manual-store');
        Route::post('pesanan/{id}/transition', [AdminOrderController::class, 'transitionStatus'])->middleware('pending.transaction')->name('admin.pesanan.transition');
        Route::prefix('pesanan/{id}/retur')->where(['id' => '[0-9]+'])->middleware(RentalStaging::class)->group(function () {
            Route::get('inspeksi', [RentalCloseoutController::class, 'evidence']);
            Route::get('foto/{foto}', [RentalCloseoutController::class, 'photo'])->whereNumber('foto');
            Route::post('nilai', [RentalCloseoutController::class, 'assess'])->name('retur.nilai');
            Route::post('setoran', [RentalCloseoutController::class, 'confirmDeposit'])->name('retur.setoran');
            Route::post('selesai', [RentalCloseoutController::class, 'complete'])->name('retur.selesai');
        });
        Route::get('rekonsiliasi', [TransferReconciliationController::class, 'index'])->name('admin.rekonsiliasi.index');
        Route::post('rekonsiliasi/{mutasi}/pengembalian', [TransferReconciliationController::class, 'recordReturn'])
            ->whereNumber('mutasi')->name('admin.rekonsiliasi.pengembalian');
        Route::post('pesanan/{id}/verify-payment', [AdminOrderController::class, 'verifyPayment'])->name('admin.pesanan.verify-payment');
        Route::post('pesanan/{id}/reject-payment', [AdminOrderController::class, 'rejectOrAcceptPayment'])->middleware('pending.transaction')->name('admin.pesanan.reject-payment');
        Route::post('pesanan/{id}/record-cash', [AdminOrderController::class, 'recordCash'])->middleware('pending.transaction')->name('admin.pesanan.record-cash');
        Route::get('permohonan-batal', [CancellationRequestController::class, 'index'])->name('admin.permohonan-batal.index');
        Route::post('pesanan/{id}/approve-cancellation', [PaidRentalCancellationController::class, 'approve'])->whereNumber('id')->name('admin.pesanan.approve-cancellation');
        Route::post('pesanan/{id}/reject-cancellation', [CancellationRequestController::class, 'reject'])->whereNumber('id')->name('admin.pesanan.reject-cancellation');
        Route::post('pesanan/{id}/cancel-paid', [PaidRentalCancellationController::class, 'cancel'])->whereNumber('id')->name('admin.pesanan.cancel-paid');
        Route::post('pesanan/{id}/record-refund', [PaidRentalCancellationController::class, 'record'])->whereNumber('id')->name('admin.pesanan.record-refund');
        Route::post('pesanan/{id}/reactivate-expired', [AdminOrderController::class, 'reactivateExpired'])->whereNumber('id')->middleware(RentalStaging::class)->name('admin.pesanan.reactivate-expired');
    });

    /** Admin Catalog */
    Route::prefix('admin/katalog')->group(function () {
        Route::get('/', [AdminCatalogController::class, 'index'])->middleware('role:owner')->name('admin.katalog.index');
        Route::get('create', [AdminCatalogController::class, 'create'])->middleware('role:owner')->name('admin.katalog.create');
        Route::post('/', [AdminCatalogController::class, 'store'])->middleware('role:owner')->name('admin.katalog.store');
        Route::get('{id}/edit', [AdminCatalogController::class, 'edit'])->middleware('role:owner')->name('admin.katalog.edit');
        Route::put('{id}', [AdminCatalogController::class, 'update'])->middleware('role:owner')->name('admin.katalog.update');
        Route::post('{id}/archive', [AdminCatalogController::class, 'archive'])->middleware('role:owner')->name('admin.katalog.archive');
        Route::post('{id}/publish', [AdminCatalogController::class, 'publish'])->middleware('role:owner')->name('admin.katalog.publish');
        Route::post('{id}/manage-variants', [CatalogInventoryController::class, 'variant'])->middleware('role:owner')->whereNumber('id')->name('admin.katalog.manage-variants');
        Route::post('{id}/manage-options', [CatalogInventoryController::class, 'option'])->middleware('role:owner')->whereNumber('id')->name('admin.katalog.manage-options');
        Route::post('{id}/manage-images', [AdminCatalogController::class, 'manageImages'])->middleware('role:owner')->middleware('pending.transaction')->name('admin.katalog.manage-images');
        Route::post('{id}/manage-units', [CatalogInventoryController::class, 'unit'])->whereNumber('id')->name('admin.katalog.manage-units');
    });

    /** Admin Settings */
    Route::prefix('admin/pengaturan')->group(function () {
        Route::get('/', [AdminSettingsController::class, 'index'])->middleware('role:owner')->name('admin.pengaturan.index');
        Route::post('update', [AdminSettingsController::class, 'update'])->middleware('role:owner')->name('admin.pengaturan.update');
        Route::post('manage-calendar-blocks', [AdminSettingsController::class, 'manageCalendarBlocks'])->name('admin.pengaturan.manage-calendar-blocks');
        Route::post('manage-production-capacity', [AdminSettingsController::class, 'manageProductionCapacity'])->name('admin.pengaturan.manage-production-capacity');
    });

    /** Admin Reports */
    Route::prefix('admin/laporan')->middleware('role:owner')->group(function () {
        Route::get('/', [AdminReportController::class, 'index'])->name('admin.laporan.index');
        Route::get('penerimaan', [VerifiedRevenueController::class, 'index'])->name('laporan.penerimaan');
        Route::get('penerimaan/csv', [VerifiedRevenueController::class, 'export'])->name('laporan.penerimaan.csv');
        Route::get('export-csv', [AdminReportController::class, 'exportAnonymousCsv'])->name('admin.laporan.export-csv');
    });

    /** Admin Activity Log */
    Route::prefix('admin/aktivitas')->middleware('role:owner')->group(function () {
        Route::get('/', [AdminActivityLogController::class, 'index'])->name('admin.aktivitas.index');
    });
});

/**
 * STAFF PRODUKSI & KURIR ROUTES (auth:staff + role:staf_produksi,staf_kurir)
 * --------------------------------------------------------
 * Routes for production staff and delivery personnel.
 */
Route::middleware('auth:staff')->group(function () {
    /** Staff Production */
    Route::prefix('staf/produksi')->middleware('role:owner,admin,staf_produksi')->group(function () {
        Route::get('/', [StaffProductionController::class, 'index'])->name('staf.produksi.index');
        Route::post('/start-production', [StaffProductionController::class, 'startProduction'])->name('staf.produksi.start');
        Route::post('/mark-ready', [StaffProductionController::class, 'markReady'])->name('staf.produksi.mark-ready');
    });

    /** Staff Delivery */
    Route::prefix('staf/kurir')->middleware('role:owner,admin,staf_kurir')->group(function () {
        Route::get('/', [StaffDeliveryController::class, 'index'])->name('staf.kurir.index');
        Route::post('/penjualan-toko/{id}/serah', [StoreSaleController::class, 'handover'])->whereNumber('id')->middleware(RentalStaging::class)->name('toko.serah');
        Route::post('/handover', [StaffDeliveryController::class, 'handover'])->middleware(RentalStaging::class)->name('staf.kurir.handover');
        Route::post('/receive-return', [StaffDeliveryController::class, 'receiveReturn'])->middleware(RentalStaging::class)->name('staf.kurir.receive-return');
    });
});

// Route root home
Route::get('/', [CatalogController::class, 'index'])->name('home');
