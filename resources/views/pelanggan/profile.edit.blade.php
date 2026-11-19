@extends('layouts.customer')

@section('title', 'Profil Saya - SIBERKAH')

@section('styles')
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
@endsection

@section('scripts')
    @push('scripts')
        <script src="{{ asset('js/micro-interactions.js') }}"></script>
    @endpush
@endsection

@section('content')
<div class="py-12">
    <div class="container-siberkah">

        <!-- Profile Header -->
        <div class="mb-12 rounded-2xl bg-primary-50/50 dark:bg-primary-950/50 overflow-hidden p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-center sm:text-left">
                    <div class="w-24 h-24 rounded-2xl bg-primary-100/30 border border-primary-200/20 mx-auto mb-4">
                        <svg class="w-12 h-12 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h2 class="font-display text-2xl font-bold text-primary-900 dark:text-primary-50 mb-1">{{ auth('customer')->user->nama_lengkap }}</h2>
                    <p class="text-neutral-600 dark:text-neutral-400">{{ auth('customer')->user->email }}</p>
                </div>

                <!-- Edit Button -->
                <a href="{{ route('pelanggan.profile.edit') }}" class="btn btn-primary btn-sm">
                    <span class="material-symbols-outlined text-[16px]">edit</span>
                    Edit Profil
                </a>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="space-y-6">

            <!-- Nama Lengkap -->
            <div>
                <label for="nama_lengkap" class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">Nama Lengkap</label>
                <div class="input-group">
                    <input
                        type="text"
                        id="nama_lengkap"
                        class="input-group-input w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-900 border border-primary-300 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                        value="{{ old('nama_lengkap', auth('customer')->user->nama_lengkap) }}"
                        aria-label="Nama lengkap"
                    >
                    <label for="nama_lengkap" class="input-label">Nama Lengkap</label>
                </div>
                @error('nama_lengkap')
                    <p class="input-error text-primary-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- No. Telepon -->
            <div>
                <label for="no_telepon" class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">No. Telepon</label>
                <div class="input-group">
                    <input
                        type="tel"
                        id="no_telepon"
                        class="input-group-input w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-900 border border-primary-300 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                        value="{{ old('no_telepon', auth('customer')->user->no_telepon) }}"
                        placeholder="+62 8xx-xxxx-xxxx"
                        aria-label="No. telepon"
                    >
                    <label for="no_telepon" class="input-label">No. telepon</label>
                </div>
                @error('no_telepon')
                    <p class="input-error text-primary-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Alamat -->
            <div>
                <label for="alamat" class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">Alamat Lengkap</label>
                <div class="input-group">
                    <textarea
                        id="alamat"
                        class="input-group-input w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-900 border border-primary-300 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors resize-none"
                        rows="3"
                        placeholder "Alamat lengkap Anda"
                        aria-label="Alamat lengkap"
                    >{{ old('alamat', auth('customer')->user->alamat) }}</textarea>
                    <label for="alamat" class="input-label">Alamat lengkap</label>
                </div>
                @error('alamat')
                    <p class="input-error text-primary-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Metode Pembayaran Favorit -->
            <div>
                <label class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">Metode Pembayaran Favorit</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="btn btn-sm btn-outline rounded-xl py-2 flex items-center gap-2 cursor-pointer"
                           onclick="document.getElementById('qriscash').click()">
                        <span class="material-symbols-outlined text-[14px]">qr_code</span>
                        <span>QRIS</span>
                    </label>
                    <input type="radio" name="metode_pembayaran" id="qriscash" value="QRIS" {{ old('metode_pembayaran') === 'QRIS' ? 'checked' : '' }} class="hidden">
                    <label class="btn btn-sm btn-outline rounded-xl py-2 flex items-center gap-2 cursor-pointer"
                           onclick="document.getElementById('banktransfer').click()">
                        <span class="material-symbols-outlined text-[14px]">payment</span>
                        <span>Bank Transfer</span>
                    </label>
                    <input type="radio" name="metode_pembayaran" id="banktransfer" value="Bank Transfer" {{ old('metode_pembayaran') === 'Bank Transfer' ? 'checked' : '' }} class="hidden">
                </div>
            </div>

            <!-- Tombol Simpan -->
            <div class="pt-6 border-t border-primary-100/20 dark:border-primary-900/20">
                <x-button wire:loading="isSaving" wire:submit="update"
                        class="btn btn-primary w-full py-4 rounded-xl transition-all">
                    <span>Simpan Perubahan</span>
                    @if($isSaving)
                        <span class="sr-only">Menyimpan...</span>
                    @endif
                </x-button>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
// Initialize Profile Form interactions
document.addEventListener('DOMContentLoaded', () => {
    // Initialize float labels
    initFloatLabels();

    // Initialize character counters
    initCharCounters();

    // Initialize format on blur
    initFormatOnBlur();

    // Initialize auto-resize textarea
    initAutoResize();
});

// Initialize float labels
function initFloatLabels() {
    const inputs = document.querySelectorAll('.input-group input, .input-group textarea');

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

// Initialize character counters
function initCharCounters() {
    const maxLength = 100; // Batas maksimum karakter

    const wrapper = document.querySelector('.input-group');
    let counter = wrapper?.querySelector('.char-counter');

    if (!counter) {
        counter = document.createElement('div');
        counter.className = 'char-counter text-right font-mono text-caption text-on-surface-variant/60 mt-1.5';
        wrapper?.appendChild(counter);
    }

    const update = () => {
        const length = input.value.length;
        counter.textContent = `${length}/${maxLength}`;
        counter.classList.toggle('text-warning', length > maxLength * 0.8);
        counter.classList.toggle('text-error', length >= maxLength);
        input.classList.toggle('error', length >= maxLength);
    };

    const input = document.getElementById('nama_lengkap');
    if (input) {
        input.addEventListener('input', update);
        update();
    }
}

// Initialize format on blur
function initFormatOnBlur() {
    const phoneInput = document.getElementById('no_telepon');
    if (!phoneInput) return;

    phoneInput.addEventListener('blur', () => {
        if (phoneInput.value) {
            // Format: (628) xxx-xxxx
            phoneInput.value = phoneInput.value.replace(/[^\d]/g, '').replace(/^(6?)(\d{2})(\d{3})(\d{4})$/, '($2) $3-$4');
        }
    });

    phoneInput.addEventListener('focus', () => {
        phoneInput.dataset.rawValue = phoneInput.value;
        phoneInput.value = phoneInput.value.replace(/[^\d]/g, '');
    });
}

// Initialize auto-resize textarea
function initAutoResize() {
    const textarea = document.getElementById('alamat');
    if (!textarea) return;

    const resize = () => {
        textarea.style.height = 'auto';
        textarea.style.height = `${Math.min(textarea.scrollHeight, 300)}px`;
    };

    textarea.addEventListener('input', resize);
    resize();
}
</script>
@endpush