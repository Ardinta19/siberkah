@extends('layouts.admin')
@section('title','Edit produk katalog · SIBERKAH')
@section('content')
<section class="bk-owner-list">
<x-back-link href="{{ route('admin.katalog.index',$context) }}#kelola-{{ $produk->id }}">Kembali ke kelola katalog</x-back-link>
    @include('owner.katalog.sections',['productId'=>$produk->id])
<h1>Edit produk katalog</h1>
@include('owner.katalog.editor')
</section>
@endsection
