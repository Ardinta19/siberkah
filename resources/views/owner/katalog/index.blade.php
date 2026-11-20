@extends('layouts.admin')
@section('content')
@if(session('success'))<p role="status" class="bk-notice">{{ session('success') }}</p>@endif
<h1>Kelola katalog</h1><a href="{{ route('admin.katalog.create') }}">Buat draf</a>
@foreach($produk as $p)<article><a href="{{ route('admin.katalog.edit',$p->id) }}">{{ $p->nama }}</a> · {{ $p->aktif ? 'Terbit' : 'Tidak terbit' }}
<details class="bk-confirm"><summary>{{ $p->aktif ? 'Arsipkan' : 'Terbitkan' }}</summary><p>{{ $p->nama }} {{ $p->aktif ? 'akan disembunyikan dari katalog publik.' : 'akan ditampilkan di katalog publik.' }}</p><form data-pending="Menyimpan status…" method="post" action="{{ route($p->aktif ? 'admin.katalog.archive' : 'admin.katalog.publish',$p->id) }}">@csrf<button>Ya, {{ $p->aktif ? 'Arsipkan' : 'Terbitkan' }}</button></form></details></article>@endforeach
{{ $produk->links('partials.pagination') }}
@endsection
