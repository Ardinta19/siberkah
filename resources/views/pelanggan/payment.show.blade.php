@extends('layouts.customer')

@section('title', 'Pembayaran - SIBERKAH')

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

        <!-- Payment Header -->
        <div class="mb-12 rounded-2xl bg-primary-50/50 dark:bg-primary-950/50 overflow-hidden">
            <div class="px-6 py-8">
                <!-- Payment Status -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-primary-100/30 border border-primary-200/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6l-4-4m4 4l-4-4m-6 7h12m0 0l-4 4m4-4l-4 4m0-6l4 4m-4-4l4-4"/>
                            </path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-display text-xl font-bold text-primary-900 dark:text-primary-50">Pembayaran Berhasil</p>
                            <p class="text-neutral-600 dark:text-neutral-400">Terima kasih telah melakukan pembayaran</p>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="mt-6 p-4 rounded-xl bg-primary-50 dark:bg-primary-950/20">
                        <p class="font-body text-body-sm text-neutral-600 dark:text-neutral-400 mb-2">Ringkasan Pembayaran</p>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-neutral-500 dark:text-neutral-400">Total Bayar</span>
                                <span class="font-medium text-primary-600">Rp {{ number_format($payment->total_bayar, 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-neutral-500 dark:text-neutral-400">Dana Diterima</span>
                                <span class="font-medium text-success-600">Rp {{ number_format($payment->terima, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mt-6 p-3 rounded-xl bg-primary-50/30 border border-primary-200/30">
                        <p class="font-body text-caption text-neutral-600 dark:text-neutral-400 mb-1">Metode Pembayaran</p>
                        <p class="font-medium text-primary-600">{{ $payment->metode ?? 'QRIS' }}</p>
                    </div>
                </div>

                <!-- Countdown Timer -->
                <div class="mb-8 animate-slide-up" style="transition-delay: 100ms;">
                    <div class="max-w-md mx-auto text-center">
                        <h2 class="font-display text-2xl font-bold text-primary-600 dark:text-primary-400 mb-2">Sisa Waktu Pembayaran</h2>
                        <div class="flex justify-center gap-2">
                            <div class="text-center">
                                <div class="font-display text-5xl font-bold text-primary-600" id="countdown-days">00</div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Hari</div>
                            </div>
                            <div class="text-center">
                                <div class="font-display text-5xl font-bold text-primary-600" id="countdown-hours">23</div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Jam</div>
                            </div>
                            <div class="text-center">
                                <div class="font-display text-5xl font-bold text-primary-600" id="countdown-minutes">59</div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Menit</div>
                            </div>
                            <div class="text-center">
                                <div class="font-display text-5xl font-bold text-primary-600" id="countdown-seconds">59</div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Detik</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Instructions -->
                <div class="bg-primary-50/50 dark:bg-primary-950/20 rounded-2xl p-6 border border-primary-200/20">
                    <h3 class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-3">Instruksi Pembayaran</h3>
                    <ul class="space-y-3 text-primary-600 dark:text-primary-400 text-sm">
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined flex-shrink-0 text-primary-400 text-lg">payment</span>
                            <div>
                                <p class="font-medium">Scan QRIS di atas</p>
                                <p class="text-neutral-500 dark:text-neutral-400">Untuk melanjutkan pembayaran</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined flex-shrink-0 text-primary-400 text-lg">qr_code</span>
                            <div>
                                <p class="font-medium">Atau transfer ke</p>
                                <p class="text-neutral-500 dark:text-neutral-400">Rekening BCA norek: 1234567890</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined flex-shrink-0 text-primary-400 text-lg">email</span>
                            <div>
                                <p class="font-medium">Kirim bukti pembayaran</p>
                                <p class="text-neutral-500 dark:text-neutral-400">Ke: payment@berkahkreatif.id</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Action -->
        @if($payment->status === 'pending')
            <div class="pt-6 border-t border-primary-100/20 dark:border-primary-900/20">
                <x-button wire:loading="isLoading" wire:submit="konfirmasi"
                        class="btn btn-primary w-full py-4 rounded-xl transition-all">
                    <span>Konfirmasi Pembayaran</span>
                    @if($isLoading)
                        <span class="sr-only">Memproses...</span>
                    @endif
                </x-button>
            </div>
        @elseif($payment->status === 'expired')
            <div class="pt-6 text-center">
                <p class="font-display text-neutral-600 dark:text-neutral-400 text-lg">Waktu pembayaran telah expired</p>
                <a href="{{ route('pelanggan.catalog.index') }}" class="btn btn-primary mt-4">Kembali ke Katalog</a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Initialize Payment Show interactions
document.addEventListener('DOMContentLoaded', () => {
    // Initialize countdown timer
    initCountdown();

    // Initialize counters if any
    initCounters();
});

// Initialize countdown timer
function initCountdown() {
    const expiresAt = "{{ \Carbon\Carbon::parse($payment->expires_at)->timestamp }}000";
    const endTime = new Date(expiresAt);

    const daysEl = document.getElementById('countdown-days');
    const hoursEl = document.getElementById('countdown-hours');
    const minutesEl = document.getElementById('countdown-minutes');
    const secondsEl = document.getElementById('countdown-seconds');

    if (!daysEl || !hoursEl || !minutesEl || !secondsEl) return;

    const interval = setInterval(() => {
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            clearInterval(interval);
            daysEl.textContent = '00';
            hoursEl.textContent = '00';
            minutesEl.textContent = '00';
            secondsEl.textContent = '00';
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        daysEl.textContent = formatTime(days);
        hoursEl.textContent = formatTime(hours);
        minutesEl.textContent = formatTime(minutes);
        secondsEl.textContent = formatTime(seconds);
    }, 1000);
}

function formatTime(time) {
    return time < 10 ? '0' + time : time + '';
}

// Initialize counters
function initCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.dataset.counter) || 0;
                MicroInteractions.counter.animate(el, target, 2000, { decimals: 0 });
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(el => observer.observe(el));
}
</script>
@endpush