@extends('layouts.admin')

@section('title', 'Dashboard Owner - SIBERKAH')

@section('content')
<div class="py-8">
    <div class="container-siberkah">
        <h1 class="text-2xl font-bold mb-6">Dashboard Owner</h1>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-neutral-900 rounded-xl p-6 shadow-sm border border-neutral-200 dark:border-neutral-700">
            <p class="text-neutral-600 dark:text-neutral-400">
                Selamat datang di Dashboard Owner. Kelola semua aspek bisnis SIBERKAH di sini.
            </p>
        </div>
    </div>
</div>
@endsection