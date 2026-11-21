@extends('layouts.guest')
@section('title', 'Masuk pelanggan · SIBERKAH')
@section('content')
<section class="bk-customer-panel bk-login">
<x-back-link :href="route('catalog.index')">Kembali ke katalog</x-back-link>
    <p class="kc-kicker">Berkah Kreatif · Akun pelanggan</p>
    <h1 class="text-2xl font-bold">Masuk dengan WhatsApp</h1>
    @isset($otpError)
        <p role="alert">{{ $otpError }}</p>
        @if($supportLink)<a href="{{ $supportLink }}" rel="noopener noreferrer">Hubungi Admin lewat WhatsApp</a>@endif
    @endisset
    @if($errors->any()) <p role="alert">{{ $errors->first() }}</p> @endif
    @if(session('status')) <p role="status">{{ session('status') }}</p> @endif
    @if(session('otp.challenge'))
        <p>Masukkan kode dari WhatsApp. Jangan berikan kode ini kepada siapa pun.</p>
        <form data-pending="Memeriksa kode…" method="post" action="{{ route('otp.verify') }}" class="grid gap-3">
            @csrf
            <label>Kode enam digit <input required name="kode" minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" class="block w-full border p-2"></label>
            <button class="btn-primary">Verifikasi</button>
        </form>
    @endif
    <form data-pending="Mengirim kode…" method="post" action="{{ route('otp.issue') }}" class="grid gap-3">
        @csrf
        <label>Nomor WhatsApp <input required name="nomor_wa" type="tel" autocomplete="tel" value="{{ old('nomor_wa') }}" placeholder="08…" class="block w-full border p-2"></label>
        <button class="btn-primary">Kirim kode</button>
    </form>
</section>
@endsection
