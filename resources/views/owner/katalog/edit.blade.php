@extends('layouts.admin')
@section('content')
<x-back-link :href="route('admin.katalog.index')">Kembali ke kelola katalog</x-back-link>
<h1>Produk katalog</h1>
@include('owner.katalog.editor')
@endsection
