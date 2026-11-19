@extends('layouts.admin')

@section('title', 'SIBERKAH - Edit Produk')

@section('content')
<div class="py-8">
    <div class="container-siberkah">

        <!-- Header -->
        <div class="mb-8 animate-slide-up">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-display text-2xl font-bold text-primary-900 sm:text-3xl dark:text-primary-50">Edit Produk</h2>
                    <p class="text-neutral-600 dark:text-neutral-400">Perbarui informasi produk sewa</p>
                </div>

                <div>
                    <x-button
                        variant="secondary"
                        size="sm"
                        href="{{ route('admin.katalog.index') }}"
                    >
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Batal
                    </x-button>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="grid lg:grid-cols-3 gap-8">

            <!-- Form Section -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Informasi Produk -->
                <div class="rounded-2xl bg-white dark:bg-neutral-900 p-6 border border-primary-200/20 backdrop-blur-sm lifted">
                    <h3 class="font-display text-lg font-medium text-primary-900 dark:text-primary-50 mb-4">Informasi Produk</h3>
                    <form id="editProdukForm" action="{{ route('admin.katalog.update', $produk->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Nama Produk -->
                        <div class="mb-4">
                            <label for="nama" class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">Nama Produk</label>
                            <input
                                type="text"
                                id="nama"
                                name="nama"
                                class="input-group-input w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-900 border border-primary-300 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                                :value="$produk->nama"
                                placeholder="Masukkan nama produk"
                                required
                            >
                            <label for="nama" class="input-label float-label">Nama Produk</label>
                        </div>

                        <!-- Slug -->
                        <div class="mb-4">
                            <label for="slug" class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">Slug URL</label>
                            <input
                                type="text"
                                id="slug"
                                name="slug"
                                class="input-group-input w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-900 border border-primary-300 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                                :value="$produk->slug"
                                readonly
                                placeholder="Masukkan slug URL (otomatis dihasilkan dari nama)"
                                aria-label="Slug URL"
                            >
                            <label for="slug" class="input-label float-label">Slug URL</label>
                        </div>

                        <!-- Kategori -->
                        <div class="mb-4">
                            <label for="kategori_id" class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">Kategori</label>
                            <select
                                id="kategori_id"
                                name="kategori_id"
                                class="appearance-none w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-900 border border-primary-300 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                                required
                            >
                                <option value="" disabled>-- Pilih kategori --</option>
                                @foreach($kategoris as $kategori)
                                    <option value="{{ $kategori->id }}" @if($kategori->id === $produk->kategori_id) selected @endif>
                                        {{ $kategori->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Jenis Produk -->
                        <div class="mb-4">
                            <label for="jenis" class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">Jenis Produk</label>
                            <select
                                id="jenis"
                                name="jenis"
                                class="appearance-none w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-900 border border-primary-300 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                                required
                            >
                                <option value="sewa" @if($produk->jenis === 'sewa') selected @endif>Sewa</option>
                                <option value="jual" @if($produk->jenis === 'jual') selected @endif>Jual</option>
                                <option value="custom" @if($produk->jenis === 'custom') selected @endif>Custom</option>
                            </select>
                            <p class="mt-1.5 text-neutral-500 dark:text-neutral-400 text-sm">Jenis akses produk: sewa, jual, atau custom</p>
                        </div>

                        <!-- Kelompok Kapasitas -->
                        <div class="mb-4">
                            <label for="kelompok_kapasitas" class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">Kelompok Kapasitas</label>
                            <input
                                type="text"
                                id="kelompok_kapasitas"
                                name="kelompok_kapasitas"
                                class="input-group-input w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-900 border border-primary-300 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                                :value="$produk->kelompok_kapasitas ?? ''"
                                placeholder="Contoh: Akrilik Sederhana, Akrilik Premium"
                                helper="Kategori kapasitas produksi untuk kalkulasi"
                            >
                            <label for="kelompok_kapasitas" class="input-label float-label">Kelompok Kapasitas</label>
                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-4">
                            <label for="deskripsi" class="font-display text-label-md font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">Deskripsi</label>
                            <textarea
                                id="deskripsi"
                                name="deskripsi"
                                rows="4"
                                class="input-group-input w-full px-4 py-3 rounded-xl bg-white dark:bg-neutral-900 border border-primary-300 placeholder:text-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors resize-none"
                                placeholder="Jelaskan fitur dan detail produk"
                            >{{ $produk->deskripsi ?? '' }}</textarea>
                            <label for="deskripsi" class="input-label float-label">Deskripsi</label>
                        </div>
                    </form>
                </div>

                <!-- Harga -->
                <div class="rounded-2xl bg-white dark:bg-neutral-900 p-6 border border-primary-200/20 backdrop-blur-sm lifted">
                    <h3 class="font-display text-lg font-medium text-primary-900 dark:text-primary-50 mb-4">Harga</h3>
                    <form id="editHargaForm" class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label for="harga_dasar" class="label font-display text-sm font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">Harga Dasar (Rp)</label>
                            <input
                                type="number"
                                id="harga_dasar"
                                name="harga_dasar"
                                class="input w-full bg-primary-50 dark:bg-neutral-900/50 border border-primary-200/20 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                                :value="$produk->harga_dasar ?? ''"
                                placeholder="Masukkan harga dasar"
                            />
                        </div>
                        <div class="flex items-end">
                            <div class="w-full">
                                <p class="label mb-2 font-display text-sm font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider">Aksi Revisi</p>
                                <input
                                    type="text"
                                    name="revisi"
                                    class="input w-full bg-primary-50 dark:bg-neutral-900/50 border border-primary-200/20 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-primary-500/30 transition-colors"
                                    placeholder="Catatan revisi (opsional)"
                                    aria-label="Catatan revisi harga"
                                />
                                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 font-display text-xs">Catat alasan perubahan harga</p>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Foto Utama -->
                <div class="rounded-2xl bg-white dark:bg-neutral-900 p-6 border border-primary-200/20 backdrop-blur-sm lifted">
                    <h3 class="font-display text-lg font-medium text-primary-900 dark:text-primary-50 mb-4">Foto Utama</h3>
                    <div class="mb-4">
                        @if($produk->foto_utama)
                            <img
                                src="{{ asset('storage/' . $produk->foto_utama) }}"
                                alt="{{ $produk->nama }}"
                                class="w-full h-48 object-cover rounded-lg border border-primary-200 dark:border-primary-700"
                            />
                        @else
                            <p class="text-neutral-500 dark:text-neutral-400 text-sm">Belum ada foto utama</p>
                        @endif
                    </div>

                    <form id="editFotoForm" enctype="multipart/form-data">
                        <div class="file-input" x-data="{ dragover: false, fileName: '' }" x-init="
                            $watch('dragover', value => {
                                $refs.dropzone.classList.toggle('dragover', value);
                            });
                            $refs.input.addEventListener('change', e => {
                                if (e.target.files.length > 0) {
                                    fileName = Array.from(e.target.files).map(f => f.name).join(', ');
                                }
                            });
                        ">
                            <input
                                type="file"
                                id="foto_utama"
                                name="foto_utama"
                                class="file-input-label hidden"
                                accept="image/jpeg,image/png,image/jpg"
                                required
                            >
                            <div
                                class="file-input-label border-2 border-dashed border-primary-300 dark:border-primary-600 rounded-xl p-8 text-center cursor-pointer transition-colors"
                                @dragover.prevent="dragover = true"
                                @dragleave.prevent="dragover = false"
                                @drop.prevent="dragover = false"
                            >
                                <svg class="w-12 h-12 text-primary-400 mb-3 fill-current" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="text-primary-600 dark:text-primary-400">Seret & lepas file di sini, atau klik untuk pilih</p>
                                <p class="text-neutral-500 dark:text-neutral-400 mt-1 text-xs">
                                    Format: JPEG, PNG, JPG<br>
                                    Maks: 5 MB
                                </p>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Sidebar - Status -->
            <div class="lg:col-span-1 space-y-6">
                <div class="rounded-2xl bg-white dark:bg-neutral-900 p-6 border border-primary-200/20 backdrop-blur-sm lifted">
                    <h3 class="font-display text-lg font-medium text-primary-900 dark:text-primary-50 mb-4">Status Produk</h3>
                    <form id="statusForm" action="{{ route('admin.katalog.update', $produk->id) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Status Aktif -->
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="aktif"
                                    @if($produk->aktif === 1) checked @endif
                                    class="w-5 h-5 text-primary-600 rounded focus:ring-primary-500"
                                />
                                <span class="text-neutral-900 dark:text-neutral-100 font-display font-medium">Produk Aktif</span>
                            </label>
                            <p class="text-neutral-500 dark:text-neutral-400 text-sm">Produk akan muncul di katalog pelanggan saat aktif</p>
                        </div>

                        <!-- Slug Preview -->
                        <div class="pt-4 border-t border-primary-100/20 dark:border-primary-900/20">
                            <p class="font-display text-sm font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-2">URL Produk</p>
                            <div class="flex items-center gap-2">
                                <span class="text-neutral-400 dark:text-neutral-600">/produk/</span>
                                <code class="bg-primary-50 dark:bg-neutral-900/50 px-3 py-1.5 rounded text-sm text-primary-600 dark:text-primary-400 truncate">{{ $produk->slug }}</code>
                            </div>
                        </div>

                        <!-- Tombol Simpan -->
                        <div class="pt-4">
                            <x-button
                                variant="primary"
                                size="lg"
                                type="submit"
                                class="w-full"
                            >
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Simpan Perubahan
                            </x-button>
                        </div>
                    </form>

                    <!-- Produk Info -->
                    <div class="pt-4 border-t border-primary-100/20 dark:border-primary-900/20">
                        <p class="font-display text-sm font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider mb-3">Informasi Produk</p>
                        <ul class="space-y-2 text-neutral-500 dark:text-neutral-400 text-sm">
                            <li class="flex justify-between">
                                <span class="text-neutral-500 dark:text-neutral-400">ID:</span>
                                <span class="text-neutral-900 dark:text-neutral-100">{{ $produk->id }}</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-neutral-500 dark:text-neutral-400">Slug:</span>
                                <span class="text-neutral-900 dark:text-neutral-100">{{ $produk->slug }}</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-neutral-500 dark:text-neutral-400">Jenis:</span>
                                <span class="text-neutral-900 dark:text-neutral-100 capitalize">{{ $produk->jenis }}</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-neutral-500 dark:text-neutral-400">Kategori:</span>
                                <span class="text-neutral-900 dark:text-neutral-100">{{ $produk->kategori->nama ?? 'Belum ada' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

@push('scripts')
<script>
// Initialize form interactions
document.addEventListener('DOMContentLoaded', () => {
    // Initialize float labels
    initFloatLabels();

    // Initialize file drag & drop visual
    initFileDragDrop();

    // Format currency on blur
    formatCurrencyOnBlur();
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

// Initialize file drag & drop visual
function initFileDragDrop() {
    const dropZone = document.querySelector('.file-input-label');
    const fileInput = document.getElementById('foto_utama');

    if (!dropZone || !fileInput) return;

    ;['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefault);
    });

    ;['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'));
    });

    ;['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'));
    });

    dropZone.addEventListener('drop', e => {
        const dt = e.dataTransfer;
        const files = dt ? dt.files : [];

        if (files.length) {
            fileInput.files = files;
            updateFileName(files);
        }
    });

    fileInput.addEventListener('change', e => {
        updateFileName(e.target.files);
    });
}

function updateFileName(files) {
    const fileNames = Array.from(files).map(f => f.name).join(', ');
    const dropZone = document.querySelector('.file-input-label');

    if (dropZone) {
        const paragraph = dropZone.querySelector('p');
        if (paragraph) {
            paragraph.textContent = `Dipilih: ${fileNames}`;
        }
    }
}

// Format currency on blur
function formatCurrencyOnBlur() {
    const hargaInput = document.getElementById('harga_dasar');
    if (!hargaInput) return;

    hargaInput.addEventListener('blur', () => {
        if (hargaInput.value) {
            // Remove non-numeric characters except dots and commas
            let value = hargaInput.value.replace(/[^\d.,]/g, '');
            // Format: keep as-is, user will format manually
            // Could add: value = value.replace(/\D/g, '').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.')
        }
    });
}
</script>
@endpush