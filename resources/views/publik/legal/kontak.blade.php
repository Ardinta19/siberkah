@extends('layouts.guest')

@section('title', 'Kontak - SIBERKAH')

@section('content')
<div class="py-12">
    <div class="container-siberkah">

        <h1 class="text-3xl font-bold text-primary-900 dark:text-primary-50 mb-6">Kontak Kami</h1>

        @if($link = \App\Services\WhatsAppLinks::contact())
            <a href="{{ $link }}" rel="noopener noreferrer" class="text-primary-600 underline">Hubungi Admin lewat WhatsApp</a>
        @else
            <p>Nomor WhatsApp Admin belum tersedia di situs.</p>
        @endif
    </div>
</div>
@endsection
