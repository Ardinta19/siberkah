@extends('layouts.admin')

@section('title', 'SIBERKAH - Laporan')

@section('content')
<div class="py-8">
    <div class="container-siberkah">

        <!-- Header -->
        <div class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Laporan</h2>
                    <p class="text-neutral-500 dark:text-neutral-400">Ringkasan dan ekspor data</p>
                </div>

                <!-- Filter Periode -->
                <div>
                    <select id="periode-filter" class="input w-48">
                        <option value="harian">Harian</option>
                        <option value="mingguan" selected>Mingguan</option>
                        <option value="bulanan">Bulanan</option>
                    </select>
                    <button id="ekspor-csv" class="btn-primary-sm ml-4">Ekspor CSV</button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <!-- Total Pesanan -->
                <div class="bg-white dark:bg-neutral-900 rounded-2xl p-6 shadow-sm border border-neutral-200 dark:border-neutral-700 text-center">
                    <p class="text-neutral-500 text-sm dark:text-neutral-400 mb-2">Total Pesanan</p>
                    <p class="text-3xl font-bold text-primary-600" id="total-pesanan">0</p>
                </div>

                <!-- Total Terverifikasi -->
                <div class="bg-white dark:bg-neutral-900 rounded-2xl p-6 shadow-sm border border-neutral-200 dark:border-neutral-700 text-center">
                    <p class="text-neutral-500 text-sm dark:text-neutral-400 mb-2">Total Terverifikasi</p>
                    <p class="text-3xl font-bold text-success-600" id="total-terverifikasi">0</p>
                </div>

                <!-- Total Pendapatan -->
                <div class="bg-white dark:bg-neutral-900 rounded-2xl p-6 shadow-sm border border-neutral-200 dark:border-neutral-700 text-center">
                    <p class="text-neutral-500 text-sm dark:text-neutral-400 mb-2">Total Pendapatan</p>
                    <p class="text-3xl font-bold text-primary-600" id="total-pendapatan">Rp 0</p>
                </div>

                <!-- Pesona Terverifikasi -->
                <div class="bg-white dark:bg-neutral-900 rounded-2xl p-6 shadow-sm border border-neutral-200 dark:border-neutral-700 text-center">
                    <p class="text-neutral-500 text-sm dark:text-neutral-400 mb-2">Pesanan Terverifikasi</p>
                    <p class="text-3xl font-bold text-info-600" id="total-anket">0</p>
                </div>
            </div>

            <!-- Tabel Laporan -->
            <div class="overflow-x-auto rounded-2xl bg-white dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-700">
                <table class="table w-full">
                    <thead>
                        <tr class="text-neutral-600 dark:text-neutral-400">
                            <th class="px-4 py-3 text-left">No</th>
                            <th class="px-4 py-3 text-left">Kode Pesanan</th>
                            <th class="px-4 py-3 text-left">Nama Pelanggan</th>
                            <th class="px-4 py-3 text-left">Produk</th>
                            <th class="px-4 py-3 text-left">Total Bayar</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </thead>
                        <tbody id="data-laporan">
                            <!-- Data akan dimuat oleh JavaScript -->
                        </tbody>
                    </table>
                </table>
            </div>

            <!-- Ekspor CSV -->
            <div class="mt-8 p-4 bg-neutral-50 dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-700 text-center">
                <p class="text-neutral-500 text-sm dark:text-neutral-400 mb-2">Ekspor data untuk riset (tanpa identitas pelanggan)</p>
                <p class="text-xs text-neutral-400 dark:text-neutral-300">Data akan dianonimisasi: nama → "Pelanggan Dihapus", WA → hash, nomor pesanan → kode acak</p>
                <button id="btn-ekspor-csv" class="btn-primary-sm mt-2">Ekspor CSV Sekarang</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // InitializeVariabels
    document.addEventListener('DOMContentLoaded', function() {
        // Load data laporan
        loadLaporan();

        // Filter periode
        document.getElementById('periode-filter')?.addEventListener('change', loadLaporan);

        // Ekspor CSV
        document.getElementById('ekspor-csv')?.addEventListener('click', eksporCSV);
        document.getElementById('btn-ekspor-csv')?.addEventListener('click', eksporCSV);

        // Fungsi load laporan
        function loadLaporan() {
            const periode = document.getElementById('periode-filter')?.value || 'mingguan';

            fetch(`/admin/laporan?periode=${periode}`)
            .then(response => response.json())
            .then(data => {
                // Update summary cards
                document.getElementById('total-pesanan').textContent = data.summary.total_pesanan || 0;
                document.getElementById('total-terverifikasi').textContent = data.summary.total_terverifikasi || 0;
                document.getElementById('total-pendapatan').textContent = data.summary.total_pendapatan ? formatRupiah(data.summary.total_pendapatan) : 'Rp 0';
                document.getElementById('total-anket').textContent = data.summary.total_anket || 0;

                // Update tabel
                const tbody = document.getElementById('data-laporan');
                tbody.innerHTML = '';

                data.pesanan.forEach((pesanan, index) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-4 py-3">${index + 1}</td>
                        <td class="px-4 py-3 font-medium">${pesanan.kode_pesanan}</td>
                        <td class="px-4 py-3">${pesanan.nama_pelanggan_anonim}</td>
                        <td class="px-4 py-3">${pesanan.nama_produk}</td>
                        <td class="px-4 py-3">Rp ${Number(pesanan.total_bayar).toLocaleString('id-ID')}</td>
                        <td class="px-4 py-3">
                            <span class="status-badge status-${pesanan.status_terverifikasi ? 'success' : 'warning'}">
                                ${pesanan.status_terverifikasi ? 'Terverifikasi' : 'Belum diverifikasi'}
                            </span>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(error => {
                console.error('Gagal load laporan', error);
            });
        }

        // Fungsi format rupiah
        function formatRupiah(angka) {
            return angka.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' });
        }

        // Ekspor CSV
        function eksporCSV() {
            const periode = document.getElementById('periode-filter')?.value || 'mingguan';

            fetch(`/admin/laporan/ekspor-csv?periode=${periode}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/csv'
                }
            })
            .then(response => {
                if (response.ok) {
                    // Buat link untuk download
                    const blob = response.body;
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `laporan-${periode}-${new Date().toISOString().slice(0, 10)}.csv`;
                    a.click();
                    window.URL.revokeObjectURL(url);
                    toast.success('Berhasil', 'File CSV berhasil diunduh', 3000);
                } else {
                    throw new Error('Gagal ekspor');
                }
            })
            .catch(error => {
                toast.error('Error', 'Gagal mengekspor CSV', 3000);
            });
        }
    });
</script>
@endpush