@extends('layouts.guest')

@section('title', 'Kebijakan Privasi - SIBERKAH')

@section('content')
<div class="py-12">
    <div class="bk-customer-panel bk-legal">
        <x-back-link :href="route('home')">Kembali ke beranda</x-back-link>

        <p class="kc-kicker">Berkah Kreatif · Informasi pelanggan</p>
        <h1 class="text-3xl font-bold text-primary-900 mb-6">Kebijakan Privasi</h1>

        @if(filled(config('checkout.privacy_version'))) <p>Versi: {{ config('checkout.privacy_version') }}</p> @endif
        <div class="prose prose-lg">
            <p>Kami menghargai privasi Anda. Kebijakan privasi ini menjelaskan cara kami mengumpulkan, menggunakan, dan melindungi informasi pribadi Anda saat menggunakan layanan SIBERKAH.</p>

            <h2 class="text-xl font-bold text-primary-600 mb-4">Data yang Kami Kumpulkan</h2>
            <ul>
                <li><strong>Nama:</strong> Informasi yang Anda berikan saat pendaftaran</li>
                <li><strong>Nomor WhatsApp:</strong> Digunakan untuk verifikasi dan komunikasi</li>
                <li><strong>Data Transaksi:</strong> Informasi tentang pemesanan, pembayaran, dan status pesanan</li>
            </ul>

            <h2 class="text-xl font-bold text-primary-600 mb-4">Cara Kami Menggunakan Data</h2>
            <p>Kami menggunakan data Anda untuk:</p>
            <ul>
                <li>Memproses pesanan dan pembayaran</li>
                <li>Memverifikasi akun dengan OTP dan menyediakan tautan percakapan pesanan yang dibuka secara manual</li>
                <li>Meningkatkan layanan kami</li>
            </ul>

            <h2 class="text-xl font-bold text-primary-600 mb-4">Lindungi Data Anda</h2>
            <p>Kami tidak menjual data pribadi Anda kepada pihak ketiga. Data hanya digunakan untuk kepentingan operasional SIBERKAH.</p>
        </div>
    </div>
</div>
@endsection