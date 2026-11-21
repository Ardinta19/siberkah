@extends('layouts.guest')
@section('title', 'Ganti kata sandi staf · SIBERKAH')
@section('content')
<form data-pending="Menyimpan sandi…" method="post" action="{{ route('staff.password.change') }}" class="mx-auto my-12 grid max-w-sm gap-4 p-5">
    <x-back-link :href="route('home')">Kembali ke beranda</x-back-link>
@csrf
    <h1 class="text-2xl font-bold">Ganti kata sandi staf</h1>
    <p>Gunakan minimal 12 karakter. Setelah menyimpan, masuk kembali dengan kata sandi baru.</p>
    @if($errors->any()) <p role="alert">{{ $errors->first() }}</p> @endif
    <label>Kata sandi sekarang <input class="block w-full rounded border p-2" type="password" name="current_password" autocomplete="current-password" required></label>
    <label>Kata sandi baru <input class="block w-full rounded border p-2" type="password" name="new_password" autocomplete="new-password" minlength="12" required></label>
    <label>Ulangi kata sandi baru <input class="block w-full rounded border p-2" type="password" name="new_password_confirmation" autocomplete="new-password" required></label>
    <button class="btn-primary">Simpan kata sandi baru</button>
</form>
@endsection
