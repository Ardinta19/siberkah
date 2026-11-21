@extends('layouts.guest')

@section('title', 'Kontak - SIBERKAH')

@section('content')
<div class="py-12">
    <div class="bk-customer-panel bk-legal">
        <x-back-link :href="route('home')">Kembali ke beranda</x-back-link>

        <p class="kc-kicker">Berkah Kreatif · Informasi pelanggan</p>
        <h1 class="text-3xl font-bold text-primary-900 mb-6">Kontak Kami</h1>

        @if($link = \App\Services\WhatsAppLinks::contact())
            <a href="{{ $link }}" rel="noopener noreferrer" class="text-primary-600 underline">Hubungi Admin lewat WhatsApp</a>
        @else
            <p>Nomor WhatsApp Admin belum tersedia di situs.</p>
        @endif
    </div>
</div>
@endsection
