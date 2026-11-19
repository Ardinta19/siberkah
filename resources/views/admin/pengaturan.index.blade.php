@extends('layouts.admin')

@section('title', 'SIBERKAH - Pengaturan Bisnis')

@section('content')
<div class="py-8">
    <div class="container-siberkah">

        <!-- Header -->
        <div class="mb-8 animate-slide-up">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-display text-2xl font-bold text-primary-900 sm:text-3xl dark:text-primary-50">Pengaturan Bisnis</h2>
                    <p class="text-neutral-600 dark:text-neutral-400">Kelola nilai-nilai bisnis utama</p>
                </div>

                <div>
                    <x-button
                        variant="primary"
                        size="sm"
                        @click="bukaModalTambah"
                    >
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Pengaturan
                    </x-button>
                </div>
            </div>
        </div>

        <!-- Settings Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6">

            <!-- Setting: Harga Sewa Default -->
            <div class="rounded-2xl bg-white dark:bg-neutral-900 p-6 border border-primary-200/20 backdrop-blur-sm lifted">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-neutral-500 text-sm mb-1">Harga Sewa Default</p>
                        <p class="font-display text-2xl font-bold text-primary-900 dark:text-primary-50" id="harga-sewa-default">Rp 0</p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-icon-sm btn-ghost text-primary-500 hover:text-primary-400 flex items-center gap-1.5"
                        onclick="editSetting('harga_sewa_default')"
                    >
                        <svg class="w-3 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H8"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Setting: DP Minimum -->
            <div class="rounded-2xl bg-white dark:bg-neutral-900 p-6 border border-primary-200/20 backdrop-blur-sm lifted">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-neutral-500 text-sm mb-1">DP Minimum (%)</p>
                        <p class="font-display text-2xl font-bold text-primary-900 dark:text-primary-50" id="dp-minimum">50%</p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-icon-sm btn-ghost text-primary-500 hover:text-primary-400 flex items-center gap-1.5"
                        onclick="editSetting('dp_minimum')"
                    >
                        <svg class="w-3 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H8"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Setting: Batas Waktu Konfirmasi -->
            <div class="rounded-2xl bg-white dark:bg-neutral-900 p-6 border border-primary-200/20 backdrop-blur-sm lifted">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-neutral-500 text-sm mb-1">Batas Waktu Konfirmasi (menit)</p>
                        <p class="font-display text-2xl font-bold text-primary-900 dark:text-primary-50" id="batas-waktu">60 menit</p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-icon-sm btn-ghost text-primary-500 hover:text-primary-400 flex items-center gap-1.5"
                        onclick="editSetting('batas_waktu_konfirmasi')"
                    >
                        <svg class="w-3 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H8"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Setting: Denda Telat -->
            <div class="rounded-2xl bg-white dark:bg-neutral-900 p-6 border border-primary-200/20 backdrop-blur-sm lifted">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-neutral-500 text-sm mb-1">Denda Telat per Jam</p>
                        <p class="font-display text-2xl font-bold text-primary-900 dark:text-primary-50" id="denda-telat">Rp 25.000</p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-icon-sm btn-ghost text-primary-500 hover:text-primary-400 flex items-center gap-1.5"
                        onclick="editSetting('denda_telat')"
                    >
                        <svg class="w-3 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m-4 4h12a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8m-4 4v1a4 4 0 004 4m0-4V4a4 4 0 00-4-4H5a4 4 0 00-4 4v1"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Setting: Konfirmasi Otp Expired -->
            <div class="rounded-2xl bg-white dark:bg-neutral-900 p-6 border border-primary-200/20 backdrop-blur-sm lifted">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-neutral-500 text-sm mb-1">OTP Expired (menit)</p>
                        <p class="font-display text-2xl font-bold text-primary-900 dark:text-primary-50" id="otp-expired">5 menit</p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-icon-sm btn-ghost text-primary-500 hover:text-primary-400 flex items-center gap-1.5"
                        onclick="editSetting('otp_expired')"
                    >
                        <svg class="w-3 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m-4 4h12a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8m-4 4v1a4 4 0 004 4m0-4V4a4 4 0 00-4-4H5a4 4 0 00-4 4v1"/>
                        </svg>
                    </button>
                </div>
            </div>

        </div>

    </div>
</div>

@push('scripts')
<script>
// Initialize settings interactions
document.addEventListener('DOMContentLoaded', () => {
    // Initialize float labels
    initFloatLabels();
});

// Initialize float labels
function initFloatLabels() {
    const inputs = document.querySelectorAll('.input-group input');

    inputs.forEach(input => {
        const wrapper = input.closest('.input-group');
        const label = wrapper?.querySelector('.input-label');

        if (!label) return;

        const update = () => {
            const hasValue = input.value.length > 0;
            const isFocused = document.activeElement === input;
            label.classList.toggle('floating', hasValue || isFocused);
        };

        input.addEventListener('focus', update);
        input.addEventListener('blur', update);
        input.addEventListener('input', update);
        update();
    });
}

// Edit setting function
window.editSetting = function(settingKey) {
    const currentValues = {
        'harga_sewa_default': 'Rp 0',
        'dp_minimum': '50',
        'batas_waktu_konfirmasi': '60',
        'denda_telat': 'Rp 25.000',
        'otp_expired': '5'
    };

    const currentValue = prompt('Masukkan nilai baru untuk ' + settingKey + ':\n\nCurrent: ' + currentValues[settingKey], '');

    if (currentValue === null) return;

    // Kirim ke API
    fetch('/admin/pengaturan/edit', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            setting_key: settingKey,
            setting_value: currentValue
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toast.success('Berhasil', data.message, 3000);
            // Refresh halaman
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            toast.error('Gagal', data.message || 'Terjadi kesalahan', 3000);
        }
    })
    .catch(error => {
        toast.error('Error', 'Gagal terkirim ke server', 3000);
    });
}
</script>
@endpush