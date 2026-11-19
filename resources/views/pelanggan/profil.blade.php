@extends('layouts.customer')
@section('content')
<form method="post" action="{{ route('profile.update') }}" class="mx-auto grid max-w-md gap-4 p-6">
    @csrf @method('put')
    <h1 class="text-2xl font-bold">Profil saya</h1>
    @if(session('status')) <p role="status">{{ session('status') }}</p> @endif
    @error('nama') <p role="alert">{{ $message }}</p> @enderror
    <label>Nama <input class="block w-full border p-2" required maxlength="100" name="nama" value="{{ old('nama', $pelanggan->nama) }}"></label>
    <button class="btn-primary">Simpan</button>
</form>
@endsection
