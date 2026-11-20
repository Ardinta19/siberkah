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
                Agenda {{ $tanggal }} (WIB)
                @foreach($ringkasan as $tugas => $jumlah)<span class="block">{{ ucfirst($tugas) }}: {{ $jumlah }}</span>@endforeach
            </p>
        </div>
        @if(config('checkout.staging') && app()->environment('testing','staging','local'))
            <a href="{{ route('kas-antar.index', ['tanggal' => $tanggal]) }}">Periksa pembayaran dan setoran antar</a>
        @endif
        @foreach($peringatan as $unit)
            <p role="status">Unit {{ $unit->kode }} belum kembali dari {{ $unit->pesanan_sekarang }}; diperlukan untuk {{ $unit->pesanan_berikut }} pada {{ $unit->tanggal }}.</p>
        @endforeach
    </div>
</div>
@endsection