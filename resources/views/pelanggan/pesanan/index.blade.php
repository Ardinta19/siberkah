@extends('layouts.customer')
@section('content')
<section class="container-siberkah py-8">
    <h1 class="mb-4 text-2xl font-bold">Riwayat pesanan</h1>
    @forelse($pesanan as $p)
        <a class="block border-b py-3" href="{{ route('pesanan.show', $p->nomor) }}">{{ $p->nomor }} · {{ str_replace('_', ' ', $p->status) }}</a>
    @empty
        <p>Belum ada pesanan.</p>
    @endforelse
    {{ $pesanan->links('partials.pagination') }}
</section>
@endsection
