@extends('layouts.customer')

@section('title', 'SIBERKAH - Verifikasi OTP')

@section('styles')
    @push('styles')
        <style>
            /* Verification Page Styles */
            .verify-container {
                @apply min-h-screen bg-gradient-to-br from-primary-50 via-secondary-50 to-neutral-50 dark:from-primary-950 dark:via-secondary-950 dark:neutral-950 flex items-center justify-center py-8;
            }

            .verify-card {
                @apply w-full max-w-md bg-white dark:bg-neutral-900 rounded-2xl p-8 shadow-2xl border border-neutral-200 dark:border-neutral-700 transform hover:translate-y-1 transition-transform-200;
            }

            .verify-card:hover {
                @apply shadow-lg;
            }

            .verify-icon {
                @apply w-12 h-12 mx-auto mb-6 flex items-center justify-center;
            }

            .verify-icon svg {
                width: 48px;
                height: 48px;
            }

            .verify-title {
                @apply text-2xl font-bold text-neutral-900 dark:text-neutral-100 mb-2 text-center;
            }

            .verify-description {
                @apply text-neutral-500 dark:text-neutral-400 mb-6 text-center;
            }

            /* Verification Input Styles */
            .verify-input {
                @apply relative py-8;
            }

            .verify-input input {
                @apply w-full max-w-md text-center text-3xl font-bold bg-white dark:bg-neutral-800 border-2 border-neutral-300 rounded-xl focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none transition-all-150 dark:text-white dark:focus:border-primary-400 dark:focus:ring-primary-400/20 shadow-lg shadow-primary-500/25;
                @input-prefix: false;
                @input-suffix: false;
            }

            .verify-input input:focus {
                @apply scale-105 shadow-lg shadow-primary-500/25;
            }

            .verify-input input.success {
                @apply border-success-500 bg-success-50 dark:bg-success-900/20;
            }

            .verify-input input.error {
                @apply border-danger-500 bg-danger-50 animate-shake dark:bg-danger-900/20;
            }

            /* Verification Status */
            .verify-status {
                @apply mt-8 p-4 rounded-xl text-center;
            }

            .verify-status.success {
                @apply bg-success-50 border-success-200 text-success-800;
            }

            .verify-status.error {
                @apply bg-danger-50 border-danger-200 text-danger-800;
            }

            .verify-status.info {
                @apply bg-info-50 border-info-200 text-info-800;
            }

            .verify-icon-large {
                @apply w-16 h-16 mx-auto mb-4 text-primary-500;
            }

            .verify-icon-large svg {
                width: 48px;
                height: 48px;
            }

            /* Resend Link */
            .resend-link {
                @apply mt-4 text-primary-600 text-sm hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-400 transition-colors-150 underline cursor-pointer;
            }

            /* Success Animation */
            @keyframes checkmark {
                0% { stroke-dashoffset: 48; }
                100% { stroke-dashoffset: 0; }
            }

            .checkmark-path {
                @apply stroke-dasharray 48 stroke-linecap round;
                animation: checkmark 0.3s ease-out forwards;
            }
        </style>
    @endpush

@section('content')
<div class="verify-container">
    <div class="verify-card">
        <div class="verify-icon">
            <svg class="verify-icon-large" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path class="checkmark-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l13 13-13 13M5 8l13-13M17 5l13 13M21 9l-5 5L8 21"/>
            </svg>
        </div>

        <h2 class="verify-title" id="verifyTitle">Verifikasi OTP</h2>
        <p class="verify-description" id="verifyDescription">Masukkan kode 6 digit yang dikirim ke nomor WA Anda</p>

        <form id="verifyForm" class="verify-input">
            <div class="otp-grid" id="verifyGrid">
                <!-- OTP inputs akan dibuat oleh JavaScript -->
            </form>
            </div>
        </form>

        <div class="verify-status" id="verifyStatus" style="display: none;">
            <svg class="w-6 h-6 mx-auto mb-2 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 12l2 2 4-4m6.25-5.858a3.535 3.535 0 115 5.158m5.158-5.158a3.535 3.535 0 01-5 5.158M2.105 5.5a3.535 3.535 0 115 5.158m5.158-5.158a3.535 3.535 0 01-5 5.158m1.414-9.999a3.535 3.535 0 000 5.006L19.99 19.99"/>
            </svg>
            <p class="text-neutral-600 mt-2" id="verifyStatusText">Kode berhasil diverifikasi</p>
        </div>

        <div class="verify-status error" id="verifyError" style="display: none;">
            <svg class="w-6 h-6 mx-auto mb-2 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
            <p class="text-neutral-600 mt-2" id="verifyErrorText">Kode salah atau kadaluarsa, coba lagi</p>
        </div>

        <div class="mt-6 text-center">
            <p class="text-neutral-500 text-sm dark:text-neutral-400">Tidak menerima kode?</p>
            <a href="javascript:void(0)" class="resend-link" id="resendVerifyLink">Kirim ulang kode OTP</a>
        </p>
    </div>
</div>

@push('scripts')
<script>
    // OTP Input Initialization for Verification Page
    document.addEventListener('DOMContentLoaded', function() {
        const verifyInputs = document.querySelectorAll('#verifyGrid input');

        // Initialize inputs
        verifyInputs.forEach((input, index) => {
            input.setAttribute('maxlength', '1');
            input.setAttribute('inputmode', 'numeric');

            // Auto-focus ke input selanjutnya
            input.addEventListener('input', function(e) {
                const value = e.target.value.replace(/\D/g, '').substring(0, 1);
                e.target.value = value;

                if (value && index < verifyInputs.length - 1) {
                    verifyInputs[index + 1].focus();
                }
            });

            // Handle Backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    verifyInputs[index - 1].focus();
                }
            });
        });

        // Set first input focus
        verifyInputs[0].focus();

        // Form Submit Handler
        const verifyForm = document.getElementById('verifyForm');
        if (verifyForm) {
            verifyForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Ambil nilai dari semua input
                const codes = Array.from(verifyInputs).map(input => input.value.trim());
                const code = codes.join('');

                // Validasi
                if (code.length !== 6) {
                    // Tandai input yang kosong
                    verifyInputs.forEach(input => {
                        if (!input.value) {
                            input.classList.add('error');
                        }
                    });
                    return;
                }

                // Kirim verifikasi ke server
                fetch('/otp/verify', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ code: code })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success state
                        document.getElementById('verifyTitle').textContent = 'Verifikasi Berhasil';
                        document.getElementById('verifyDescription').textContent = data.message || 'Akun berhasil diakses';

                        // Show success status
                        document.getElementById('verifyStatus').style.display = 'block';
                        document.getElementById('verifyError').style.display = 'none';

                        // Redirect after delay
                        setTimeout(() => {
                            window.location.href = '/pelanggan/pesanan';
                        }, 2000);
                    } else {
                        // Show error state
                        document.getElementById('verifyTitle').textContent = 'Verifikasi Gagal';
                        document.getElementById('verifyDescription').textContent = data.message || 'Kode salah atau kadaluarsa';

                        // Show error status
                        document.getElementById('verifyStatus').style.display = 'none';
                        document.getElementById('verifyError').style.display = 'block';

                        // Tandai input salah
                        verifyInputs.forEach(input => input.classList.add('error'));
                    }
                })
                .catch(error => {
                    toast.error('Error', 'Gagal terhubung ke server', 3000);
                });
            });
        }
    });
</script>
@endpush