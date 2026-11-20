@extends('layouts.customer')
@section('content')
<form data-pending="Menyimpan profil…" method="post" action="{{ route('profile.update') }}" class="mx-auto grid max-w-md gap-4 p-6">
    @csrf @method('put')
    <h1 class="text-2xl font-bold">Profil saya</h1>
    @if(session('status')) <p role="status">{{ session('status') }}</p> @endif
    @if($errors->any()) <p role="alert">{{ $errors->first() }}</p> @endif
    <label>Nama <input class="block w-full border p-2" required maxlength="100" name="nama" value="{{ old('nama', $pelanggan->nama) }}"></label>
    @unless($pelanggan->isOnboarded())
        @if(filled(config('checkout.privacy_version')))
            <input type="hidden" name="versi_privasi" value="{{ config('checkout.privacy_version') }}">
            <label><input type="checkbox" required name="setuju_privasi" value="1"> Saya menyetujui <a href="{{ route('privasi') }}">kebijakan privasi</a> versi {{ config('checkout.privacy_version') }}.</label>
        @else
            <p role="alert">Versi kebijakan privasi belum tersedia. Profil belum dapat diselesaikan; hubungi admin.</p>
        @endif
    @endunless
    <button class="btn-primary">Simpan</button>
</form>
@endsection
