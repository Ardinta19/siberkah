@extends('layouts.guest')

@section('title', 'Kontak - SIBERKAH')

@section('content')
<div class="py-12">
    <div class="container-siberkah">

        <h1 class="text-3xl font-bold text-primary-900 dark:text-primary-50 mb-6">Kontak Kami</h1>

        <div class="grid md:grid-cols-3 gap-6">

            <div class="bg-white dark:bg-neutral-900 rounded-xl p-6 shadow-sm border border-neutral-200 dark:border-neutral-800">
                <h3 class="text-xl font-bold text-primary-600 mb-3">Alamat</h3>
                <p class="text-neutral-600 dark:text-neutral-400">
                    Jl. Contoh No. 123<br>
                    Kota Contoh, Indonesia
                </p>
            </div>

            <div class="bg-white dark:bg-neutral-900 rounded-xl p-6 shadow-sm border border-neutral-200 dark:border-neutral-800">
                <h3 class="text-xl font-bold text-primary-600 mb-3">WhatsApp</h3>
                <p class="text-neutral-600 dark:text-neutral-400">
                    <a href="https://wa.me/6281234567890" class="text-primary-600 underline">6281234567890</a>
                </p>
            </div>

            <div class="bg-white dark:bg-neutral-900 rounded-xl p-6 shadow-sm border border-neutral-200 dark:border-neutral-800">
                <h3 class="text-xl font-bold text-primary-600 mb-3">Email</h3>
                <p class="text-neutral-600 dark:text-neutral-400">
                    contact@siberkah.com
                </p>
            </div>

        </div>
    </div>
</div>
@endsection