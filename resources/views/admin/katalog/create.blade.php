@extends('layouts.admin')

@section('title', 'SIBERKAH - Tambah Produk')

@section('content')
<div class="py-8">
    <div class="container-siberkah">

        <!-- Header -->
        <div class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        onclick="history.back()"
                        class="btn btn-ghost btn-sm"
                    >
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Kembali
                    </button>
                    <div>
                        <h2 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Tambah Produk</h2>
                        <p class="text-neutral-500 dark:text-neutral-400">Lengkapi data produk baru</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="card card-hover animate-slide-up">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Informasi Produk</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.katalog.store') }}" method="POST" enctype="multipart/form-data" id="produkForm">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column: Main Info & Images -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Basic Info -->
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wide">Informasi Dasar</h4>

                                <!-- Nama Produk -->
                                <div>
                                    <label for="nama" class="label label-required">Nama Produk</label>
                                    <input
                                        type="text"
                                        id="nama"
                                        name="nama"
                                        value="{{ old('nama') }}"
                                        class="input"
                                        placeholder="Masukkan nama produk"
                                        required
                                        maxlength="200"
                                    >
                                    <p class="char-counter" id="namaCounter">{{ strlen(old('nama')) }} / 200</p>
                                </div>

                                <!-- Deskripsi -->
                                <div>
                                    <label for="deskripsi" class="label">Deskripsi</label>
                                    <textarea
                                        id="deskripsi"
                                        name="deskripsi"
                                        rows="4"
                                        class="input"
                                        placeholder="Deskripsikan produk secara lengkap..."
                                        maxlength="1000"
                                    >{{ old('deskripsi') }}</textarea>
                                    <p class="char-counter" id="deskripsiCounter">{{ strlen(old('deskripsi')) ?? 0 }} / 1000</p>
                                </div>

                                <!-- Kategori -->
                                <div>
                                    <label for="kategori_id" class="label label-required">Kategori</label>
                                    <select
                                        id="kategori_id"
                                        name="kategori_id"
                                        class="input"
                                        required
                                    >
                                        <option value="">Pilih kategori...</option>
                                        @foreach($kategori as $k)
                                        <option value="{{ $k->id }}" {{ old('kategori_id') == $k->id ? 'selected' : '' }}>
                                            {{ $k->nama }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Harga Sewa & Harga Jual -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="harga_sewa" class="label label-required">Harga Sewa per Jam</label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400">Rp</span>
                                            <input
                                                type="number"
                                                id="harga_sewa"
                                                name="harga_sewa"
                                                value="{{ old('harga_sewa') }}"
                                                class="input pl-10"
                                                placeholder="0"
                                                min="0"
                                                required
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <label for="harga_jual" class="label">Harga Jual/Uang Muka</label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400">Rp</span>
                                            <input
                                                type="number"
                                                id="harga_jual"
                                                name="harga_jual"
                                                value="{{ old('harga_jual') }}"
                                                class="input pl-10"
                                                placeholder="0"
                                                min="0"
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Stok -->
                                <div>
                                    <label for="stok" class="label">Stok</label>
                                    <input
                                        type="number"
                                        id="stok"
                                        name="stok"
                                        value="{{ old('stok', 0) }}"
                                        class="input"
                                        placeholder="0"
                                        min="0"
                                    >
                                    <p class="text-xs text-neutral-400 mt-1">Maksimal: 1024</p>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="label">Status</label>
                                    <div class="flex flex-wrap gap-4 mt-3">
                                        <label class="flex items-center gap-2 cursor-pointer touch-target">
                                            <input
                                                type="radio"
                                                name="status"
                                                value="draft"
                                                {{ old('status', 'draft') === 'draft' ? 'checked' : '' }}
                                                class="radio"
                                            >
                                            <span class="text-sm text-neutral-700 dark:text-neutral-300">Draft (Belum terbit)</span>
                                        </label>

                                        <label class="flex items-center gap-2 cursor-pointer touch-target">
                                            <input
                                                type="radio"
                                                name="status"
                                                value="tersedia"
                                                {{ old('status', 'draft') === 'tersedia' ? 'checked' : '' }}
                                                class="radio"
                                            >
                                            <span class="text-sm text-neutral-700 dark:text-neutral-300">Tersedia</span>
                                        </label>

                                        <label class="flex items-center gap-2 cursor-pointer touch-target">
                                            <input
                                                type="radio"
                                                name="status"
                                                value="reservasi"
                                                {{ old('status', 'draft') === 'reservasi' ? 'checked' : '' }}
                                                class="radio"
                                            >
                                            <span class="text-sm text-neutral-700 dark:text-neutral-300">Reservasi</span>
                                        </label>

                                        <label class="flex items-center gap-2 cursor-pointer touch-target">
                                            <input
                                                type="radio"
                                                name="status"
                                                value="habis"
                                                {{ old('status', 'draft') === 'habis' ? 'checked' : '' }}
                                                class="radio"
                                            >
                                            <span class="text-sm text-neutral-700 dark:text-neutral-300">Habis</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Variants Section -->
                            <div class="divider divider-text">
                                <span>Varian Produk</span>
                            </div>

                            <div class="divide-y divide-neutral-200 dark:divide-neutral-800 space-y-4" id="varianContainer">
                                <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-4">
                                    Tambah varian untuk produk (ukuran, warna, material, dll). Klik tombol "Tambah Varian" untuk menambahkan baris baru.
                                </p>

                                <!-- Dynamic Variants -->
                                @foreach(old('varian', []) as $index => $varian)
                                <div class="varian-row flex flex-col sm:flex-row gap-4 items-start sm:items-center animate-fade-in">
                                    <div class="flex-1 w-full">
                                        <label class="label text-xs">Nama Varian ({{ $index + 1 }})</label>
                                        <input
                                            type="text"
                                            name="varian[{{ $index }}][nama]"
                                            value="{{ $varian['nama'] ?? '' }}"
                                            class="input text-sm"
                                            placeholder="Contoh: Merah, M, XL"
                                        >
                                    </div>
                                    <div class="flex-1 w-full">
                                        <label class="label text-xs">Harga Tambahan</label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400 text-xs">Rp</span>
                                            <input
                                                type="number"
                                                name="varian[{{ $index }}][harga]"
                                                value="{{ $varian['harga'] ?? '' }}"
                                                class="input text-sm pl-9"
                                                placeholder="0"
                                                min="0"
                                            >
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 pt-4">
                                        <button
                                            type="button"
                                            onclick="hapusVarianRow(this)"
                                            class="btn btn-sm btn-danger"
                                            aria-label="Hapus varian"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <button
                                type="button"
                                onclick="tambahVarianRow()"
                                class="btn btn-sm btn-outline"
                            >
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Tambah Varian
                            </button>

                            <!-- Options Section -->
                            <div class="divider divider-text">
                                <span>Opsi Produk</span>
                            </div>

                            <div class="divide-y divide-neutral-200 dark:divide-neutral-800 space-y-4" id="opsiContainer">
                                <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-4">
                                    Tambah opsi untuk produk (lampu, kabel, dll). Klik tombol "Tambah Opsi" untuk menambahkan baris baru.
                                </p>

                                <!-- Dynamic Options -->
                                @foreach(old('opsi', []) as $index => $opsi)
                                <div class="opsi-row flex flex-col sm:flex-row gap-4 items-start sm:items-center animate-fade-in">
                                    <div class="flex-1 w-full">
                                        <label class="label text-xs">Nama Opsi ({{ $index + 1 }})</label>
                                        <input
                                            type="text"
                                            name="opsi[{{ $index }}][nama]"
                                            value="{{ $opsi['nama'] ?? '' }}"
                                            class="input text-sm"
                                            placeholder="Contoh: Lampu Aksesoris"
                                        >
                                    </div>
                                    <div class="flex-1 w-full">
                                        <label class="label text-xs">Kode Opsi</label>
                                        <input
                                            type="text"
                                            name="opsi[{{ $index }}][kode]"
                                            value="{{ $opsi['kode'] ?? '' }}"
                                            class="input text-sm"
                                            placeholder="Contoh: L-001"
                                        >
                                    </div>
                                    <div class="flex-1 w-full">
                                        <label class="label text-xs">Default Qty</label>
                                        <input
                                            type="number"
                                            name="opsi[{{ $index }}][qty]"
                                            value="{{ $opsi['qty'] ?? '0' }}"
                                            class="input text-sm"
                                            placeholder="0"
                                            min="0"
                                        >
                                    </div>
                                    <div class="flex items-center gap-2 pt-4">
                                        <button
                                            type="button"
                                            onclick="hapusOpsiRow(this)"
                                            class="btn btn-sm btn-danger"
                                            aria-label="Hapus opsi"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <button
                                type="button"
                                onclick="tambahOpsiRow()"
                                class="btn btn-sm btn-outline"
                            >
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Tambah Opsi
                            </button>
                        </div>

                        <!-- Right Column: Images -->
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wide mb-4">Gambar Produk</h4>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-4">
                                    Upload semua gambar produk dalam satu kali upload. Sistem akan menentukan gambar utama. Semua gambar harus dalam format WebP.
                                </p>
                            </div>

                            <div class="file-input" id="gambarContainer">
                                <input
                                    type="file"
                                    id="gambar"
                                    name="gambar[]"
                                    accept="image/webp, image/gif, image/jpeg, image/png"
                                    multiple
                                    required
                                    onchange="fileInputHandler(event)"
                                >
                                <label for="gambar" class="file-input-label h-64">
                                    <div class="flex flex-col items-center justify-center h-full">
                                        <div class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                            Klik untuk upload gambar
                                        </p>
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                            WebP, GIF, JPEG, PNG<br>Max. 5MB per file
                                        </p>
                                    </div>
                                </label>
                            </div>

                            <!-- Selected Images Preview -->
                            <div id="selectedImages" class="space-y-2">
                                @if(old('gambar_names'))
                                    @foreach(explode(',', old('gambar_names')) as $imgName)
                                        <div class="flex items-center gap-2 p-3 bg-neutral-100 dark:bg-neutral-800 rounded-lg animate-fade-in">
                                            <img
                                                src="{{ asset('storage/' . $imgName) }}"
                                                alt="Preview: {{ $imgName }}"
                                                class="w-12 h-12 rounded object-cover"
                                            >
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs text-neutral-700 dark:text-neutral-300 truncate">{{ $imgName }}</p>
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400">Preview</p>
                                            </div>
                                            <input
                                                type="hidden"
                                                name="gambar_hidden[]"
                                                value="{{ $imgName }}"
                                            >
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <!-- Placeholder for dynamic file previews -->
                            <div id="filePreviews" class="space-y-2"></div>

                            <!-- Image Guidelines -->
                            <div class="bg-neutral-50 dark:bg-neutral-800/50 rounded-xl p-4">
                                <h5 class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-2">
                                    <svg class="w-4 h-4 inline mr-1 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Panduan Gambar
                                </h5>
                                <ul class="text-xs text-neutral-600 dark:text-neutral-400 space-y-1 list-disc list-inside">
                                    <li>Gunakan fokus pada produk utama</li>
                                    <li>Background harus bersih & terang</li>
                                    <li>Sertakan gambar profil (paling atas)</li>
                                    <li>Format gambar: .webp (direkomendasikan)</li>
                                    <li>Max. ukuran: 5MB file</li>
                                    <li>Tersedia gambar: <span id="availableSlots">5</span> slot</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer flex flex-col sm:flex-row gap-3 justify-end">
                <button
                    type="button"
                    onclick="history.back()"
                    class="btn btn-outline w-full sm:w-auto"
                >
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Batal
                </button>
                <button
                    type="submit"
                    form="produkForm"
                    class="btn btn-primary w-full sm:w-auto"
                >
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Produk
                    <span class="hidden sm:inline">(TEMPOH)</span>
                </button>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Character counter for textarea and input
    document.addEventListener('DOMContentLoaded', function() {
        const namaInput = document.getElementById('nama');
        const deskripsiInput = document.getElementById('deskripsi');

        if (namaInput) {
            namaInput.addEventListener('input', function() {
                document.getElementById('namaCounter').textContent = this.value.length + ' / 200';
            });
        }

        if (deskripsiInput) {
            deskripsiInput.addEventListener('input', function() {
                document.getElementById('deskripsiCounter').textContent = this.value.length + ' / 1000';
            });
        }
    });

    // Dynamic file input handlers
    let selectedFiles = [];
    const maxSlots = 5;

    function fileInputHandler(event) {
        const files = Array.from(event.target.files);
        const previewsContainer = document.getElementById('filePreviews');
        const availableSlotsEl = document.getElementById('availableSlots');
        const remainingSlots = maxSlots - selectedFiles.length;

        for (const file of files) {
            if (selectedFiles.length >= maxSlots) {
                alert('Maksimal 5 gambar per produk');
                break;
            }

            const fileError = validateFile(file);
            if (fileError) {
                alert(fileError);
                continue;
            }

            // Check file size (5MB)
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('File terlalu besar! Maksimal 5MB.');
                continue;
            }

            // Add file to selection
            selectedFiles.push({
                file: file,
                previewUrl: URL.createObjectURL(file)
            });

            // Remove from input to avoid duplicate processing
            event.target.value = '';
            break;
        }

        renderFilePreviews();
        updateAvailableSlots();
    }

    function validateFile(file) {
        const validTypes = ['image/webp', 'image/gif', 'image/jpeg', 'image/png'];
        const fileName = file.name.toLowerCase();

        if (!validTypes.includes(file.type)) {
            return `Format file ${file.name} tidak didukung. Gunakan WebP, GIF, JPEG, atau PNG.`;
        }

        if (fileName.endsWith('.webp') || fileName.endsWith('.gif')) {
            return `File ${file.name} akan berubah menjadi WebP setelah dikonversi.`;
        }

        return '';
    }

    function renderFilePreviews() {
        const previewsContainer = document.getElementById('filePreviews');
        previewsContainer.innerHTML = '';

        selectedFiles.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'relative flex items-center gap-2 p-3 bg-neutral-100 dark:bg-neutral-800 rounded-lg animate-fade-in';
            div.innerHTML = `
                <img
                    src="${item.previewUrl}"
                    alt="Preview: ${item.file.name}"
                    class="w-12 h-12 rounded object-cover"
                >
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-neutral-700 dark:text-neutral-300 truncate">${item.file.name}</p>
                    <p class="text-xs ${formatFileSize(item.file.size)}">${formatFileSize(item.file.size)}</p>
                </div>
                <button
                    type="button"
                    onclick="removeFile(${index})"
                    class="p-1 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                    aria-label="Hapus file"
                >
                    <svg class="w-4 h-4 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `;
            previewsContainer.appendChild(div);
        });
    }

    function removeFile(index) {
        URL.revokeObjectURL(selectedFiles[index].previewUrl);
        selectedFiles.splice(index, 1);
        renderFilePreviews();
        updateAvailableSlots();
    }

    function updateAvailableSlots() {
        const remaining = maxSlots - selectedFiles.length;
        document.getElementById('availableSlots').textContent = remaining;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        const value = (bytes / Math.pow(k, i)).toFixed(2);
        return `${value} ${sizes[i]}`;
    }

    // Dynamic varian rows
    function tambahVarianRow() {
        const container = document.getElementById('varianContainer');
        const index = container.querySelectorAll('.varian-row').length + 1;

        const newRow = document.createElement('div');
        newRow.className = 'varian-row flex flex-col sm:flex-row gap-4 items-start sm:items-center animate-fade-in';
        newRow.dataset.index = index;
        newRow.innerHTML = `
            <div class="flex-1 w-full">
                <label class="label text-xs">Nama Varian (${index})</label>
                <input
                    type="text"
                    name="varian[${index}][nama]"
                    class="input text-sm"
                    placeholder="Contoh: Merah, M, XL"
                >
            </div>
            <div class="flex-1 w-full">
                <label class="label text-xs">Harga Tambahan</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400 text-xs">Rp</span>
                    <input
                        type="number"
                        name="varian[${index}][harga]"
                        class="input text-sm pl-9"
                        placeholder="0"
                        min="0"
                    >
                </div>
            </div>
            <div class="flex items-center gap-2 pt-4">
                <button
                    type="button"
                    onclick="hapusVarianRow(this)"
                    class="btn btn-sm btn-danger"
                    aria-label="Hapus varian"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Hapus
                </button>
            </div>
        `;

        container.appendChild(newRow);
    }

    function hapusVarianRow(button) {
        const row = button.closest('.varian-row');
        row.remove();

        // Renumber rows
        const rows = container.querySelectorAll('.varian-row');
        container.querySelectorAll('.varian-row').forEach((row, index) => {
            row.dataset.index = index + 1;
            const inputs = row.querySelectorAll('input');
            const label = inputs[0].previousElementSibling;
            if (label) {
                label.textContent = label.textContent.replace(/\(\d+\)/, `(${index + 1})`);
            }
        });
    }

    // Dynamic opsi rows
    function tambahOpsiRow() {
        const container = document.getElementById('opsiContainer');
        const index = container.querySelectorAll('.opsi-row').length + 1;

        const newRow = document.createElement('div');
        newRow.className = 'opsi-row flex flex-col sm:flex-row gap-4 items-start sm:items-center animate-fade-in';
        newRow.dataset.index = index;
        newRow.innerHTML = `
            <div class="flex-1 w-full">
                <label class="label text-xs">Nama Opsi (${index})</label>
                <input
                    type="text"
                    name="opsi[${index}][nama]"
                    class="input text-sm"
                    placeholder="Contoh: Lampu Aksesoris"
                >
            </div>
            <div class="flex-1 w-full">
                <label class="label text-xs">Kode Opsi</label>
                <input
                    type="text"
                    name="opsi[${index}][kode]"
                    class="input text-sm"
                    placeholder="Contoh: L-001"
                >
            </div>
            <div class="flex-1 w-full">
                <label class="label text-xs">Default Qty</label>
                <input
                    type="number"
                    name="opsi[${index}][qty]"
                    class="input text-sm"
                    placeholder="0"
                    min="0"
                >
            </div>
            <div class="flex items-center gap-2 pt-4">
                <button
                    type="button"
                    onclick="hapusOpsiRow(this)"
                    class="btn btn-sm btn-danger"
                    aria-label="Hapus opsi"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Hapus
                </button>
            </div>
        `;

        container.appendChild(newRow);
    }

    function hapusOpsiRow(button) {
        const row = button.closest('.opsi-row');
        row.remove();

        // Renumber rows
        const container = document.getElementById('opsiContainer');
        container.querySelectorAll('.opsi-row').forEach((row, index) => {
            row.dataset.index = index + 1;
            const inputs = row.querySelectorAll('input');
            const label = inputs[0].previousElementSibling;
            if (label) {
                label.textContent = label.textContent.replace(/\(\d+\)/, `(${index + 1})`);
            }
        });
    }

    // Form validation before submit
    document.getElementById('produkForm').addEventListener('submit', function(e) {
        // Check if files were uploaded
        const fileInput = document.getElementById('gambar');

        if (fileInput.files.length === 0 && selectedFiles.length === 0) {
            alert('Mohon upload setidaknya 1 gambar produk.');
            e.preventDefault();
            return;
        }

        // Check file count
        const totalFiles = fileInput.files.length + selectedFiles.length;
        if (totalFiles > maxSlots) {
            alert(`Maksimal ${maxSlots} gambar per produk.`);
            e.preventDefault();
            return;
        }

        // Validate varian
        const varianInputs = document.querySelectorAll('input[name^="varian["][name$="[nama]"]');
        let hasVarian = false;
        varianInputs.forEach(input => {
            if (input.value.trim()) {
                hasVarian = true;
            }
        });

        // Validate opsi
        const opsiInputs = document.querySelectorAll('input[name^="opsi["][name$="[nama]"]');
        let hasOpsi = false;
        opsiInputs.forEach(input => {
            if (input.value.trim()) {
                hasOpsi = true;
            }
        });
    });
</script>
@endpush