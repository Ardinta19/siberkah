@extends('layouts.customer')
@section('title','Profil saya · SIBERKAH')
@section('content')
@php
    $complete = $pelanggan->isOnboarded();
    $version = config('checkout.privacy_version');
    $canConsent = is_string($version) && filled($version) && mb_strlen($version) <= 40;
@endphp
<section class="bk-profile container-siberkah py-8" aria-labelledby="profile-title">
<x-back-link :href="route('catalog.index')">Kembali ke katalog</x-back-link>
    <header><p class="kc-kicker">Berkah Kreatif · Akun pelanggan</p><h1 id="profile-title">Profil saya</h1>
        <p>{{ $complete ? 'Kelola nama yang digunakan untuk pesanan Anda.' : 'Lengkapi nama dan persetujuan privasi sebelum memesan.' }}</p></header>
    @if(session('status'))<p role="status" class="bk-notice">{{ session('status') }}</p>@endif
    <form data-profile-editor data-saved-name="{{ $pelanggan->nama }}" data-pending="Menyimpan profil…" method="post" action="{{ route('profile.update') }}">
        @csrf @method('put')
        @if($pending)<p>Setelah profil tersimpan, Anda akan kembali ke checkout. Pesanan belum dibuat.</p><input type="hidden" name="lanjut" value="{{ $pending['token'] }}">@endif
        <label for="profile-name">Nama pelanggan</label>
        <input id="profile-name" required autocomplete="name" maxlength="100" name="nama" value="{{ old('nama', $pelanggan->nama) }}" aria-describedby="name-help @error('nama') name-error @enderror" @error('nama') aria-invalid="true" @enderror>
        <small id="name-help">Maksimal 100 karakter.</small>
        @error('nama')<p id="name-error" role="alert">{{ $message }}</p>@enderror
        <section class="bk-profile-policy" aria-labelledby="policy-title"><h2 id="policy-title">Persetujuan privasi</h2>
            @if($complete)
                <p>Kebijakan versi <strong>{{ $pelanggan->versi_kebijakan }}</strong> sudah disetujui.</p>
                <a href="{{ route('privasi') }}">Baca kebijakan privasi</a>
            @elseif($canConsent)
                <input type="hidden" name="versi_privasi" value="{{ $version }}">
                <p><a href="{{ route('privasi') }}" target="_blank" rel="noopener">Baca kebijakan privasi (tab baru)</a> sebelum memberikan persetujuan.</p>
                <label><input type="checkbox" required name="setuju_privasi" value="1" @checked(old('versi_privasi') === $version && old('setuju_privasi') == 1)> Saya menyetujui kebijakan privasi versi {{ $version }}.</label>
                @error('setuju_privasi')<p role="alert">{{ $message }}</p>@enderror
                @error('versi_privasi')<p role="alert">Versi kebijakan berubah. Baca versi yang berlaku dan berikan persetujuan kembali.</p>@enderror
            @else
                <p role="alert">Versi kebijakan privasi belum tersedia. Profil belum dapat diselesaikan; hubungi Admin.</p>
            @endif
        </section>
        <div class="bk-profile-actions"><button data-save-profile class="btn-primary" @disabled(!$complete && !$canConsent)>Simpan profil</button>
            <button hidden type="button" data-undo-profile class="bk-secondary">Batalkan perubahan</button></div>
        <p role="status" data-profile-state></p>
    </form>
</section>
@endsection
