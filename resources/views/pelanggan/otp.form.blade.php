@extends('layouts.guest')

@section('title', 'Login OTP WhatsApp — SIBERKAH')

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

        <!-- Login Card -->
        <div class="rounded-2xl bg-primary-50/50 dark:bg-primary-950/50 p-8 sm:p-10 border border-primary-200/20 backdrop-blur-sm max-w-md mx-auto animate-slide-up">
            <div class="text-center sm:text-left mb-8">
                <h2 class="font-display text-2xl font-bold text-primary-900 dark:text-primary-50 mb-2">Login OTP WhatsApp</h2>
                <p class="text-neutral-600 dark:text-neutral-400">Masuk dengan mengirim kode OTP ke nomor WhatsApp Anda</p>
            </div>

            <!-- OTP Form -->
            <form role="form" class="space-y-6">

                <!-- Nomor Telepon -->
                <div>
                    <label for="no_telepon" class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">No. Telepon</label>
                    <div class="input-group">
                        <input
                            type="tel"
                            id="no_telepon"
                            class="input-group-input w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-900 border border-primary-300 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                            placeholder="+62 8xx-xxxx-xxxx"
                            aria-label="No. telepon"
                            maxlength="15"
                        >
                        <label for="no_telepon" class="input-label float-label">No. telepon</label>
                    </div>
                    @error('no_telepon')
                        <p class="input-error text-primary-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kode OTP -->
                <div>
                    <label for="kode_otp" class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">Kode OTP (6 digit)</label>
                    <div class="input-group">
                        <input
                            type="text"
                            id="kode_otp"
                            class="input-group-input w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-900 border border-primary-300 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                            placeholder="123456"
                            aria-label="Kode OTP"
                            maxlength="6"
                            pattern="[0-9]{6}"
                        >
                        <label for="kode_otp" class="input-label float-label">Kode OTP</label>
                    </div>
                    @error('kode_otp')
                        <p class="input-error text-primary-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tombol Kirim OTP -->
                <div>
                    <x-button type="submit" wire:loading="isLoading" wire:submit="kirimOtp"
                            class="btn btn-primary w-full py-3 rounded-xl transition-all">
                        <span>Kirim Kode OTP</span>
                        @if($isLoading)
                            <span class="sr-only">Memproses...</span>
                        @endif
                    </x-button>
                </div>

            </form>

        </div>

    </div>
</div>

@push('scripts')
<script>
// Initialize OTP Form interactions
document.addEventListener('DOMContentLoaded', () => {
    // Initialize float labels
    initFloatLabels();

    // Initialize character counter
    initCharCounter();

    // Initialize format on blur
    initFormatOnBlur();
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

// Initialize character counter
function initCharCounter() {
    const inputs = document.querySelectorAll('.input-group input[pattern]');
    const wrapper = inputs[0]?.closest('.input-group');

    if (!wrapper) return;

    let counter = wrapper?.querySelector('.char-counter');
    if (!counter) {
        counter = document.createElement('div');
        counter.className = 'char-counter text-right font-mono text-caption text-on-surface-variant/60 mt-1.5';
        wrapper?.appendChild(counter);
    }

    const update = () => {
        const length = inputs[0]?.value.length || 0;
        counter.textContent = `${length}/6`;
        counter.classList.toggle('text-warning', length > 4);
        counter.classList.toggle('text-error', length >= 6);
        inputs[0]?.classList.toggle('error', length >= 6);
    };

    inputs.forEach(input => {
        input.addEventListener('input', update);
    });
    update();
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
</script>
@endpush