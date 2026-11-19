<!--
  Modal Component
  Usage:
  <x-modal title="Konfirmasi" size="md">
    <p>Apakah Anda yakin ingin menghapus data ini?</p>
    <x-slot name="actions">
      <x-button variant="ghost" @click="close">Batal</x-button>
      <x-button variant="danger" @click="confirm">Hapus</x-button>
    </x-slot>
  </x-modal>

  Or use with emit:
  @push('scripts')
    <script>
      window.addEventListener('confirm-modal', e => {
        // Handle confirmation
      });
    </script>
  @endpush
-->

@props([
    'title' => 'Konfirmasi',
    'size' => 'md', // sm, md, lg, xl, full
    'showClose' => true,
    'closeOnOverlayClick' => true,
    'class' => '',
    'emit' => null, // Event name to emit on confirm
    'cancelText' => 'Batal',
    'confirmText' => 'Ya, Lanjutkan',
    'confirmVariant' => 'danger',
])

<div x-data="{ open: false, submitting: false }" class="fixed inset-0 z-[var(--z-modal-back)] hidden"
     @click.away="open = false"
     x-show="open"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     aria-modal="true"
     aria-labelledby="modal-title-{{ uniqid() }}">

    @if($showClose)
        <button type="button" class="absolute top-3 right-3 rounded-full p-1 hover:bg-white/20 dark:hover:bg-neutral-900/30 transition-colors-150" aria-label="Tutup modal" @click="open = false">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18M6 6l12 12"/>
            </svg>
        </button>
    @endif

    @if($size === 'full')
        $maxW = 'max-w-full';
        $maxH = 'max-h-full';
        $overflow = 'overflow-y-auto';
    @elseif($size === 'xl')
        $maxW = 'max-w-3xl';
        $maxH = '';
        $overflow = '';
    @elseif($size === 'lg')
        $maxW = 'max-w-2xl';
        $maxH = '';
        $overflow = '';
    @elseif($size === 'sm')
        $maxW = 'max-w-sm';
        $maxH = '';
        $overflow = '';
    @else
        $maxW = 'max-w-lg';
        $maxH = '';
        $overflow = '';
    @endif

    <div class="modal-overlay" @click="open = false" x-show="open && $closeOnOverlayClick"></div>

    <div class="modal-content {{ $overflow }} {{ $maxW }}" role="dialog" aria-modal="true" aria-labelledby="modal-title-{{ uniqid() }}">
        <div class="sm:rounded-xl bg-white dark:bg-neutral-900 shadow-lg">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700 flex items-center justify-between">
                <h3 id="modal-title-{{ uniqid() }}" class="text-lg font-medium text-neutral-900 dark:text-neutral-100">{{ $title }}</h3>
                @if($showClose)
                    <button type="button" class="rounded-full p-1 hover:bg-white/20 dark:hover:bg-neutral-900/30 transition-colors-150" aria-label="Tutup modal" @click="open = false">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18M6 6l12 12"/>
                        </svg>
                    </button>
                @endif
            </div>

            <!-- Body -->
            <div class="px-6 py-4 sm:p-6" id="modal-body">
                @yield('body', '<p class="text-sm text-neutral-600 dark:text-neutral-400">Isi isi di sini</p>')
            </div>

            <!-- Footer -->
            <div class="px-6 py-3 border-t border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-950 flex justify-end gap-2">
                <x-button variant="ghost" @click="open = false" class="hidden sm:inline-block">{{ $cancelText }}</x-button>
                <x-button
                    variant="{{ $confirmVariant }}"
                    @click="confirmSubmit"
                    class="inline-flex sm:inline-block"
                    :disabled="submitting"
                    aria-busy="submitting">
                    @if($submitPending)
                        <svg class="w-4 h-4 animate-spin mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="4" /></svg>
                        <span>Menyimpan...</span>
                    @else
                        <span>{{ $confirmText }}</span>
                    @endif
                </x-button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Initialize modal with Alpine
    document.addEventListener('DOMContentLoaded', function() {
        const modalElements = document.querySelectorAll('[x-data*="open"]');
        modalElements.forEach(el => {
            const isOpen = el.getAttribute('x-show')?.includes('open');
            if (isOpen) {
                // Reset state on open
                el._x_dataContext().submitting = false;
            }
        });
    });

    // Handle confirm event emission
    document.addEventListener('confirm-modal', function(e) {
        const event = new CustomEvent('{{ $emit ?? 'confirmed' }}', {
            detail: { confirmed: true }
        });
        document.dispatchEvent(event);
    });
</script>
@endpush