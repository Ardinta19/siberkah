@extends('layouts.admin')
@section('content')
<x-back-link href="{{ route('admin.katalog.index',$context) }}">Kembali ke kelola katalog</x-back-link>
<h1>Buat draf produk</h1>
@include('owner.katalog.editor')
@endsection
