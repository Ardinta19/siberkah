@extends('layouts.admin')
@section('title','Buat draf produk · SIBERKAH')
@section('content')
<section class="bk-owner-list">
<x-back-link href="{{ route('admin.katalog.index',$context) }}">Kembali ke kelola katalog</x-back-link>
<h1>Buat draf produk</h1>
@include('owner.katalog.editor')
</section>
@endsection
