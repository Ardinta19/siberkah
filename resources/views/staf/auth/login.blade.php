@extends('layouts.guest')
@section('title', 'Masuk staf · SIBERKAH')
@section('content')
<form data-pending="Memeriksa akun…" method="post" action="{{ route('staff.login.post') }}" class="mx-auto my-12 grid max-w-sm gap-4 p-5">
    @csrf
    <h1 class="text-2xl font-bold">Masuk staf</h1>
    @if($errors->any()) <p role="alert">{{ $errors->first() }}</p> @endif
    <label>Email <input required class="block w-full rounded border p-2" type="email" name="email" value="{{ old('email') }}" autocomplete="username"></label>
    <label>Kata sandi <input required class="block w-full rounded border p-2" type="password" name="password" autocomplete="current-password"></label>
    <button class="btn-primary">Masuk</button>
</form>
@endsection
