@extends('layouts.staff')
@section('content')
<section class="p-6">
    <h1 class="mb-4 text-2xl font-bold">{{ $judul }}</h1>
    @if(session('status')) <p role="status">{{ session('status') }}</p> @endif
    @forelse($pesanan as $p)
        <p class="border-b py-3">{{ $p->nomor }} · {{ $p->tanggal_serah->format('d/m/Y') }} · {{ str_replace('_', ' ', $p->status) }}</p>
        @if($produksi ?? false)
            <form method="post" class="flex gap-2 py-2">
                @csrf
                <input type="hidden" name="pesanan_id" value="{{ $p->id }}">
                @if($p->status === 'dikonfirmasi')
                    <button formaction="{{ route('staf.produksi.start') }}" class="btn-outline">Mulai produksi custom</button>
                @endif
                <button formaction="{{ route('staf.produksi.mark-ready') }}" class="btn-primary">Tandai siap</button>
            </form>
        @endif
    @empty
        <p>Tidak ada pesanan dalam antrean.</p>
    @endforelse
    {{ $pesanan->links() }}
</section>
@endsection
