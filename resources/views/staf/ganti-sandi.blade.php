@extends('layouts.guest')
@section('content')
<form method="post" action="{{ route('staff.password.change') }}" class="mx-auto my-12 grid max-w-sm gap-4 p-5">
    @csrf
    <h1>Ganti kata sandi staf</h1>
    <p>Gunakan minimal 12 karakter. Setelah menyimpan, masuk kembali dengan kata sandi baru.</p>
    @if($errors->any()) <p role="alert">{{ $errors->first() }}</p> @endif
    <label>Kata sandi sekarang <input type="password" name="current_password" autocomplete="current-password" required></label>
    <label>Kata sandi baru <input type="password" name="new_password" autocomplete="new-password" minlength="12" required></label>
    <label>Ulangi kata sandi baru <input type="password" name="new_password_confirmation" autocomplete="new-password" required></label>
    <button>Simpan</button>
</form>
@endsection
