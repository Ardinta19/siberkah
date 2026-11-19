@extends('layouts.guest')
@section('title', 'Masuk pelanggan · SIBERKAH')
@section('content')
<section class="mx-auto my-12 grid max-w-md gap-5 p-5">
    <h1 class="text-2xl font-bold">Masuk dengan WhatsApp</h1>
    @if($errors->any()) <p role="alert">{{ $errors->first() }}</p> @endif
    @if(session('status')) <p role="status">{{ session('status') }}</p> @endif
    @if(session('otp.challenge'))
        <form method="post" action="{{ route('otp.verify') }}" class="grid gap-3">
            @csrf
            <label>Kode enam digit <input required name="kode" maxlength="6" inputmode="numeric" autocomplete="one-time-code" class="block w-full border p-2"></label>
            <button class="btn-primary">Verifikasi</button>
        </form>
    @endif
    <form method="post" action="{{ route('otp.issue') }}" class="grid gap-3">
        @csrf
        <label>Nomor WhatsApp <input required name="nomor_wa" type="tel" autocomplete="tel" placeholder="08…" class="block w-full border p-2"></label>
        <button class="btn-primary">Kirim kode</button>
    </form>
</section>
@endsection
