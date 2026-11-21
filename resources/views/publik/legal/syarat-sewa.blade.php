@extends('layouts.guest')

@section('title', 'Syarat Sewa - SIBERKAH')

@section('content')
<div class="py-12">
    <div class="bk-customer-panel bk-legal">
        <x-back-link :href="route('home')">Kembali ke beranda</x-back-link>

        <p class="kc-kicker">Berkah Kreatif · Informasi pelanggan</p>
        <h1 class="text-3xl font-bold text-primary-900 mb-6">Syarat Sewa</h1>

        <div class="prose prose-lg max-w-xl">
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
                <li>Opsi tunai penuh saat serah tidak memerlukan transfer atau QRIS. Di bawah Rp{{ number_format(\App\Support\BusinessSettings::value('ambang_dp_rupiah'), 0, ',', '.') }}, pembayaran harus lunas. Mulai ambang tersebut, bila tenggat pelunasan belum lewat, Anda dapat membayar lunas atau DP 50%.</li>
                <li>Untuk skema DP, perhatikan tenggat pada tagihan. Jika memilih sisa tunai, bayar sisanya saat serah; jangan mentransfer bagian tunai.</li>
                <li>Transfer yang terlambat diperiksa oleh Admin; transfer tidak otomatis mengaktifkan kembali reservasi.</li>
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