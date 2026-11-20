@extends('layouts.admin')
@section('content')
<h1>Kelola katalog</h1><a href="{{ route('admin.katalog.create') }}">Buat draf</a>
@foreach($produk as $p)<article><a href="{{ route('admin.katalog.edit',$p->id) }}">{{ $p->nama }}</a> · {{ $p->aktif ? 'Terbit' : 'Tidak terbit' }}
<form method="post" action="{{ route($p->aktif ? 'admin.katalog.archive' : 'admin.katalog.publish',$p->id) }}">@csrf<button>{{ $p->aktif ? 'Arsipkan' : 'Terbitkan' }}</button></form></article>@endforeach
{{ $produk->links() }}
@endsection
