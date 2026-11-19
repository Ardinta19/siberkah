<!--
  Toast Component
  Usage:
  @push('toast')
    <x-toast
      type="success"
      title="Berhasil"
      message="Pesanan berhasil dibuat"
      :autoDismiss="3000"
    />
  @endpush

  Or via JS:
  toast.success("Berhasil", "Pesan");
  toast.error("Error", "Pesan error");
  toast.warning("Peringatan", "Pesan peringatan");
  toast.info("Informasi", "Pesan info");
-->

@props([
    'type' => 'success', // success, error, warning, info
    'title' => 'Sukses',
    'message' => 'Aksi berhasil dilakukan',
    'autoDismiss' => true,
    'duration' => 3000,
    'class' => '',
])

<?php
$typeClasses = [
    'success' => 'toast-success',
    'error' => 'toast-error',
    'warning' => 'toast-warning',
    'info' => 'toast-info',
];

$iconMap = [
    'success' => '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l13 13-13 13M5 8l13-13M17 5l13 13M21 9l-5 5L8 21"/></svg>',
    'error' => '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 0l-2.784 2.213m.032 1.875c.94 1.195 1.834 1.958 2.784 1.253m0 0l-2.784-2.213m.032-1.875c-.94-1.195-1.834-1.958-2.784-1.253M12 3v2m0 0l-2.784 2.213m.032 1.875c.94 1.195 1.834 1.958 2.784 1.253m0 0l-2.784-2.213m.032-1.875c-.94-1.195-1.834-1.958-2.784-1.253M12 21v2m0 0l-2.784 2.213m.032 1.875c.94 1.195 1.834 1.958 2.784 1.253m0 0l-2.784-2.213m.032-1.875c-.94-1.195-1.834-1.958-2.784-1.253"/path></svg>',
    'warning' => '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 0l-.752 2.473m0.752 2.473m7.502 0a14.95 14.95 0 012.472-5.925L10.29 3.572a6 6 0 016.472 5.33m0 0l-5.05 4.96m0 0l-5.05-4.96m7.502 0a14.95 14.95 0 01-2.472 5.925L13.71 20.428a6 6 0 01-6.472 5.33m0 0l5.05 4.96M12 3v2m0 0l-2.784 2.213m.032 1.875c.94 1.195 1.834 1.958 2.784 1.253m0 0l-2.784-2.213m.032-1.875c-.94-1.195-1.834-1.958-2.784-1.253"/path></svg>',
    'info' => '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7a6 6 0 016 6v1h-3v-3zm0 0v-3m3.597.054a1 1 0 01.289.015l1.586 1.585m2.094 7.054a1 1 0 01.289.015l1.586 1.585M12 10a4 4 1 10-4 4m0-10a4 4 1 01-4 4m0 0a4 4 0 014-4"/path></svg>',
];

$toastClass = 'toast ' . ($typeClasses[$type] ?? $toastClasses['success']);
$iconHtml = $type !== 'info' ? $iconMap[$type] ?? '' : '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7a6 6 0 016 6v1h-3v-3zm0 0v-3m3.597.054a1 1 0 01.289.015l1.586 1.585m2.094 7.054a1 1 0 01.289.015l1.586 1.585M12 10a4 4 1 10-4 4m0-10a4 4 1 01-4 4m0 0a4 4 0 014-4"/path></svg>';
?>

<div class="toast-item {{ $toastClass }} touch-target" role="alert" aria-live="polite" aria-atomic="true">
    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center" style="background: rgba(255,255,255,0.1);">
            {!! $iconHtml !!}
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-medium text-sm">{{ $title }}</p>
            <p class="text-xs">{{ $message }}</p>
        </div>
        <button
            type="button"
            class="ml-auto p-1 rounded-lg hover:bg-white/20 transition-colors-150"
            @if(!$autoDismiss)
                @click.stop="dismiss"
            @endif
            aria-label="Tutup notifikasi">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>

@push('scripts')
<script>
    // Initialize toast
    document.addEventListener('DOMContentLoaded', function() {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) return;

        // Check if inline toast exists
        const inlineToast = toastContainer.querySelector('.toast-item');
        if (inlineToast) {
            // Auto dismiss
            if (!inlineToast.dataset.autoDismiss) {
                inlineToast.dataset.autoDismiss = '{{ $autoDismiss ? $duration : 0 }}';
            }

            // Auto dismiss timer
            const duration = parseInt(inlineToast.dataset.autoDismiss);
            if (duration > 0) {
                setTimeout(() => {
                    inlineToast.style.transition = 'opacity 0.3s ease-out';
                    inlineToast.style.opacity = '0';
                    setTimeout(() => inlineToast.remove(), 300);
                }, duration);
            }

            // Manual dismiss on click
            inlineToast.querySelector('button').addEventListener('click', function(e) {
                e.stopPropagation();
                inlineToast.style.transition = 'opacity 0.3s ease-out';
                inlineToast.style.opacity = '0';
                setTimeout(() => inlineToast.remove(), 300);
            });

            // Keyboard accessible
            inlineToast.setAttribute('role', 'status');
            inlineToast.setAttribute('aria-live', 'polite');
        }
    });

    // Global toast function
    window.toast = {
        success: function(title, message, duration) {
            showToast('success', title, message, duration);
        },
        error: function(title, message, duration) {
            showToast('error', title, message, duration);
        },
        warning: function(title, message, duration) {
            showToast('warning', title, message, duration);
        },
        info: function(title, message, duration) {
            showToast('info', title, message, duration);
        }
    };

    function showToast(type, title, message, duration) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const durationVal = duration || 3000;
        const toastHtml = `
            <div class="toast-item toast-${type} touch-target" role="alert" aria-live="polite" aria-atomic="true">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center" style="background: rgba(255,255,255,0.1);">
                        ${type === 'success' ? '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l13 13-13 13M5 8l13-13M17 5l13 13M21 9l-5 5L8 21"/></svg>' :
                        type === 'error' ? '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 0l-2.784 2.213m.032 1.875c.94 1.195 1.834 1.958 2.784 1.253m0 0l-2.784-2.213m.032-1.875c-.94-1.195-1.834-1.958-2.784-1.253M12 3v2m0 0l-2.784 2.213m.032 1.875c.94 1.195 1.834 1.958 2.784 1.253m0 0l-2.784-2.213m.032-1.875c-.94-1.195-1.834-1.958-2.784-1.253"/path></svg>' :
                        type === 'warning' ? '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 0l-.752 2.473m0.752 2.473m7.502 0a14.95 14.95 0 012.472-5.925L10.29 3.572a6 6 0 016.472 5.33m0 0l-5.05 4.96m0 0l-5.05-4.96m7.502 0a14.95 14.95 0 01-2.472 5.925L13.71 20.428a6 6 0 01-6.472 5.33m0 0l5.05 4.96M12 3v2m0 0l-2.784 2.213m.032 1.875c.94 1.195 1.834 1.958 2.784 1.253m0 0l-2.784-2.213m.032-1.875c-.94-1.195-1.834-1.958-2.784-1.253"/path></svg>' :
                        '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7a6 6 0 016 6v1h-3v-3zm0 0v-3m3.597.054a1 1 0 01.289.015l1.586 1.585m2.094 7.054a1 1 0 01.289.015l1.586 1.585M12 10a4 4 1 10-4 4m0-10a4 4 1 01-4 4m0 0a4 4 0 014-4"/path></svg>'
                    }
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm">{{ title }}</p>
                        <p class="text-xs">{{ message }}</p>
                    </div>
                    <button type="button" class="ml-auto p-1 rounded-lg hover:bg-white/20 transition-colors-150" @click.stop="dismiss" aria-label="Tutup notifikasi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('afterbegin', toastHtml);
        const toastEl = container.firstElementChild;

        // Auto dismiss
        if (durationVal > 0) {
            setTimeout(() => {
                toastEl.style.transition = 'opacity 0.3s ease-out';
                toastEl.style.opacity = '0';
                setTimeout(() => toastEl.remove(), 300);
            }, durationVal);
        }

        // Manual dismiss
        toastEl.querySelector('button').addEventListener('click', function(e) {
            e.stopPropagation();
            toastEl.style.transition = 'opacity 0.3s ease-out';
            toastEl.style.opacity = '0';
            setTimeout(() => toastEl.remove(), 300);
        });

        // Keyboard accessible
        toastEl.setAttribute('role', 'status');
        toastEl.setAttribute('aria-live', 'polite');

        // Remove from DOM after animation
        toastEl.addEventListener('transitionend', function() {
            if (toastEl.style.opacity === '0') {
                toastEl.remove();
            }
        });
    }
</script>
@endpush