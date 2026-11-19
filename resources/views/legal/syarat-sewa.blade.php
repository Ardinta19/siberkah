@extends('layouts.guest')

@section('title', 'Syarat Sewa - SIBERKAH')

@section('content')
<div class="py-12">
    <div class="container-siberkah">

        <h1 class="text-3xl font-bold text-primary-900 dark:text-primary-50 mb-6">Syarat Sewa</h1>

        <div class="prose prose-lg prose-inDark:prose-inDarkDark max-w-xl">
            <p>Silakan baca syarat dan ketentuan sewa di bawah ini sebelum melakukan pemesanan.</p>

            <h2 class="text-xl font-bold text-primary-600 mb-4">Syarat Umum</h2>
            <ul>
                <li>Pelanggan harus berusia minimal 18 tahun</li>
                <li>Pelanggan harus memiliki nomor WhatsApp yang valid</li>
                <li>Pesanan harus dibayar sesuai jatuh tempo</li>
                <li>Barang harus dikembalikan dalam kondisi yang baik</li>
            </ul>

            <h2 class="text-xl font-bold text-primary-600 mb-4">Syarat Pembayaran</h2>
            <ul>
                <li>DP (Down Payment) sebesar 25% dari total harga wajib dibayar sebelum pemesanan dikonfirmasi</li>
                <li>Sisa pembayaran harus dibayar minimal 24 jam sebelum tanggal serah</li>
                <li>Pembayaran terlambat akan dikenakan denda sebesar 5% dari total harga</li>
            </ul>

            <h2 class="text-xl font-bold text-primary-600 mb-4">Syarat Pengembalian</h2>
            <ul>
                <li>Barang harus dikembalikan pada tanggal yang telah disepakati</li>
                <li>Barang harus dalam kondisi baik, tanpa kerusakan</li>
                <li>Biaya penggantian barang yang rusak akan dikenakan sesuai ketentuan</li>
            </ul>
        </div>
    </div>
</div>
@endsection