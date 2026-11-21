@extends('layouts.admin')
@section('content')
<x-back-link href="{{ route('admin.katalog.index',request()->integer('page')>1?['page'=>request()->integer('page')]:[]) }}">Kembali ke kelola katalog</x-back-link>
<h1>Produk katalog</h1>
@include('owner.katalog.editor')
@endsection
