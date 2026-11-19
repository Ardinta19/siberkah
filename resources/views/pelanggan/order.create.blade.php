@extends('layouts.customer')

@section('title', 'SIBERKAH - Buat Pesanan')

@section('styles')
    @push('styles')
        <!-- Order Form Specific Styles -->
        <style>
            /* Form Styles */
            .form-section {
                @apply mb-6;
            }

            .form-section:last-child {
                @apply mb-0;
            }

            .section-title {
                @apply text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-4 border-b border-neutral-200 dark:border-neutral-700 pb-3;
            }

            /* Input Group */
            .input-group {
                @apply mb-4;
            }

            .input-group-label {
                @apply block text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-2;
            }

            /* Select Styles */
            .select-group {
                @relative flex items-center px-4 py-3 bg-white dark:bg-neutral-800 rounded-xl border border-neutral-300 dark:border-neutral-600 cursor-default select-none focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:text-white dark:focus:ring-primary-400/20 transition-all-150;
            }

            .select-group::after {
                @apply content-absolute right-3 top-1/2 -translate-y-1/2 w-3 h-3 border-2 border-neutral-300 dark:border-neutral-600 rounded-full pointer-events-none select-none transition-colors-150;
            }

            .select-group:hover {
                @border-color-primary-500 dark:border-primary-400;
            }

            .select-group:focus {
                @ring-primary-500;
            }

            /* Select Options */
            .select-option {
                @apply py-2 px-3 text-neutral-700 dark:text-neutral-300 hover:bg-primary-50 dark:hover:bg-primary-900/20 hover:text-primary-600 transition-colors-150;
            }

            /* Textarea Styles */
            .textarea-group {
                @relative;
            }

            .textarea-group textarea {
                @apply w-full px-4 py-3 bg-white dark:bg-neutral-800 rounded-xl border border-neutral-300 dark:border-neutral-600 resize-none focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:text-white dark:focus:ring-primary-400/20 transition-all-150 shadow-sm;
                @min-h-[100px];
                @resize-y;
            }

            /* Textarea Placeholder */
            .textarea-group textarea::placeholder {
                @apply text-neutral-400 dark:text-neutral-500;
            }

            /* Character Counter */
            .char-counter {
                @apply text-right text-xs text-neutral-500 mt-1 dark:text-neutral-400;
            }

            .char-counter.warning {
                @apply text-warning-600 dark:text-warning-400;
            }

            .char-counter.error {
                @apply text-danger-600 dark:text-danger-400;
            }

            /* Sticky Total */
            .sticky-total {
                @apply fixed bottom-0 left-0 right-0 bg-white dark:bg-neutral-900 border-t border-neutral-200 dark:border-neutral-700 px-4 py-3 shadow-xl z-50;
            }

            /* Button Styles */
            .btn-primary-full {
                @apply w-full py-3 bg-primary-600 text-white rounded-xl text-lg font-medium hover:bg-primary-700 focus:ring-primary-500 focus:ring-offset-2 focus:ring-primary-500/20 transition-all-150 touch-target;
            }

            .btn-primary-full:disabled {
                @apply opacity-50 cursor-not-allowed;
            }

            /* Alert Styles */
            .alert {
                @apply flex items-start gap-3 p-4 rounded-xl border;
            }

            .alert-success {
                @apply bg-success-50 border-success-200 text-success-800;
            }

            .alert-error {
                @apply bg-danger-50 border-danger-200 text-danger-800;
            }

            /* Toast Container */
            #toast-container {
                @apply fixed bottom-6 right-6 z-[var(--z-toast)] flex flex-col gap-2;
            }
        </style>
    @endpush

@section('content')
<div class="min-h-screen">
    <a href="{{ route('pelanggan.katalog') }}" class="fixed top-6 left-6 flex items-center gap-2 px-4 py-2 bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-700 shadow-sm hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors-150" aria-label="Kembali ke Katalog">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7M5 12h14M12 3v7l4-4"/>
        </svg>
        Kembali
    </a>

    <div class="container-siberkah">
        <!-- Hidden Product Data -->
        <input type="hidden" id="product-id" value="{{ $produk->id }}">
        <input type="hidden" id="product-name" value="{{ $produk->nama }}">
        <input type="hidden" id="product-price" value="{{ $produk->harga_sewa ?? 0 }}">

        <!-- Form Order -->
        <form id="orderForm" class="max-w-2xl mx-auto">
            <!-- Section 1: Ringkasan Produk -->
            <div class="form-section">
                <h3 class="section-title">Ringkasan Produk</h3>

                <div class="flex items-center justify-between mb-3">
                    <span class="text-neutral-600 dark:text-neutral-400">Produk</span>
                    <span class="font-medium text-primary-600">{{ $produk->nama }}</span>
                </div>

                <p class="text-neutral-500 dark:text-neutral-400 text-sm mb-2">{{ $produk->deskripsi }}</p>

                <!-- Opsi & Varian -->
                @if($produk->opsi_produk->isNotEmpty() || $produk->varians->isNotEmpty())
                <div class="mb-3">
                    <p class="text-neutral-600 text-sm font-medium mb-1">Opsi & Varian</p>
                    <div class="grid grid-cols-2 gap-2">
                        @if($produk->opsi_produk->isNotEmpty())
                        @foreach($produk->opsi_produk as $opsi)
                        <span class="px-2 py-1 text-xs font-medium bg-primary-50 rounded text-primary-600 dark:bg-primary-900/30 dark:text-primary-300">
                            {{ $opsi->nama }}{{ ($opsi->nilai_opsi->tipe ?? '') === 'satuan' ? ' (' . ($opsi->nilai_opsi->nilai ?? '') . ' ' . ($opsi->nilai_opsi->satuan ?? '') . ')' : '' }}
                        </span>
                        @endforeach
                        @endif
                        @if($produk->varians->isNotEmpty())
                        @foreach($produk->varians as $varian)
                        <span class="px-2 py-1 text-xs font-medium bg-secondary-50 rounded text-secondary-600 dark:bg-secondary-900/30 dark:text-secondary-300">
                            {{ $varian->nama }}
                        </span>
                        @endforeach
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Section 2: Tanggal Serah -->
            <div class="form-section">
                <h3 class="section-title">Tanggal Serah</h3>

                <div class="input-group">
                    <label for="tgl-serah" class="input-group-label">Tanggal Serah</label>
                    <input type="date" id="tgl-serah" name="tgl_serah" class="input" min="{{ date('Y-m-d') }}" required aria-label="Tanggal serah produk">
                </div>
            </div>

            <!-- Section 3: Cara Serah -->
            <div class="form-section">
                <h3 class="section-title">Cara Serah</h3>

                <div class="input-group">
                    <select id="cara-serah" name="cara_serah" class="input">
                        <option value="antar" selected>Antar (ke alamat)</option>
                        <option value="ambil">Ambil (dipotong di tempat)</option>
                    </select>
                </div>
            </div>

            <!-- Section 4: Alamat & Zona -->
            <div class="grid grid-cols-2 gap-4">
                <div class="form-section">
                    <h3 class="section-title hidden">Alamat</h3>
                    <div class="input-group">
                        <label for="alamat" class="input-group-label">Alamat Lengkap</label>
                        <textarea id="alamat" name="alamat" rows="3" class="input" placeholder="Alam lengkap termasuk RT/RW"></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title hidden">Zona Antar</h3>
                    <div class="input-group">
                        <select id="zona-antar" name="zona_antar_id" class="input">
                            <option value="" disabled>-- Pilih zona --</option>
                            @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" data-tarif="{{ $zone->tarif }}">{{ $zone->nama_zona }} - Rp {{ number_format($zone->tarif, 0, ',', '.') }} ongkir</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 5: Ringkasan Biaya (Sticky) -->
            <div id="biaya-sticky" class="sticky-total">
                <p class="text-neutral-600 text-sm mb-2">Ringkasan Biaya</p>
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div>
                        <span class="font-medium">Harga Produk</span>
                        <span class="text-primary-600" id="harga-produk">Rp {{ number_format($produk->harga_sewa ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="font-medium">Ongkir</span>
                        <span class="text-neutral-500" id="ongkir">Rp 0</span>
                    </div>
                    <div>
                        <span class="font-medium text-primary-600">Total</span>
                        <span class="text-primary-600 font-bold" id="total-biaya">Rp {{ number_format($produk->harga_sewa ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Section 6: Tombol Aksi -->
            <div class="pt-4 border-t border-neutral-100 dark:border-neutral-800">
                <button type="submit" id="btn-order" class="btn-primary-full w-full">
                    @if($produk->status === 'tersedia')
                        Lanjut Pesan
                    @else
                        <span class="text-neutral-500">Habis/Tidak Tersedia</span>
                    @endif
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Format Rupiah
    function formatRupiah(angka) {
        return angka.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' });
    }

    // Calculate ongkir based on zone
    function calculateOngkir() {
        const zoneSelect = document.getElementById('zona-antar');
        const ongkirEl = document.getElementById('ongkir');
        const hargaProdukEl = document.getElementById('harga-produk');
        const totalBiayaEl = document.getElementById('total-biaya');

        if (!zoneSelect || !ongkirEl || !hargaProdukEl || !totalBiayaEl) return;

        const selectedOption = zoneSelect.options[zoneSelect.selectedIndex];
        const tarif = parseFloat(selectedOption.getAttribute('data-tarif') || '0');

        ongkirEl.textContent = tarif > 0 ? formatRupiah(tarif) : 'Rp 0';

        const hargaProduk = parseFloat(hargaProdukEl.textContent.replace(/[^0-9]/g, '') || '0');
        const total = hargaProduk + tarif;
        totalBiayaEl.textContent = formatRupiah(total);
    }

    // Zone change handler
    document.getElementById('zona-antar')?.addEventListener('change', debounce(calculateOngkir, 300));

    // Initial calculation
    calculateOngkir();

    // Form submit handler
    document.getElementById('orderForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        // Ambil data form
        const formData = new FormData(this);
        formData.append('produk_id', document.getElementById('product-id').value);
        formData.append('produk_nama', document.getElementById('product-name').value);
        formData.append('harga_sewa', document.getElementById('product-price').value);

        // Show loading state
        const btn = document.getElementById('btn-order');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Menyimpan...';
        btn.disabled = true;

        fetch('/pesanan/store', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toast.success('Berhasil', data.message, 3000);
                setTimeout(() => {
                    window.location.href = '/pesanan/' + data.nomor_pesanan;
                }, 2000);
            } else {
                toast.error('Gagal', data.message || 'Terjadi kesalahan', 3000);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            toast.error('Error', 'Gagal menyimpan pesanan', 3000);
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
</script>
@endpush