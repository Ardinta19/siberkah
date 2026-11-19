<!--
  Card Component
  Usage:
  <x-card :hover="true" class="p-6">
    <x-slot name="header">
      <h3 class="text-lg font-semibold">Judul Card</h3>
    </x-slot>
    <x-slot name="body">
      Konten card
    </x-slot>
    <x-slot name="footer">
      <x-button>Action</x-button>
    </x-slot>
  </x-card>
-->

@props([
    'hover' => false,
    'padding' => 'md', // true, false, 'none', 'sm', 'md', 'lg'
    'bordered' => true,
    'class' => '',
    'elevation' => true,
])

<?php
$hoverClass = $hover ? 'card-hover' : '';
$borderClass = $bordered ? '' : 'border-0';
$paddingClasses = [
    true => 'p-6',
    false => '',
    'none' => '',
    'sm' => 'p-4',
    'md' => 'p-6',
    'lg' => 'p-8',
];
$paddingClass = $paddingClasses[$padding] ?? $paddingClasses[true];
?>

<div class="card {{ $hoverClass }} {{ $borderClass }} {{ $paddingClass }} {{ $class }}" {{ $attributes }}>
    @if($hover)
        @hover
        <transition name="fade">
            <div class="absolute inset-0 rounded-full bg-primary-500/20 opacity-0 hover:opacity-100 transition-opacity duration-300 ease-out pointer-events-none"></div>
        </transition>
    @endif
    @if(isset($header))
        <div class="card-header {{ $padding !== false && $padding !== 'none' ? '-mx-6 -mt-6 mb-4 px-6 pt-6' : '' }}">
            {{ $header }}
        </div>
    @endif

    @if(isset($body))
        <div class="card-body {{ $padding !== false && $padding !== 'none' ? '-mx-6 -mb-6 px-6 pb-6' : '' }}">
            {{ $body }}
        </div>
    @else
        <div class="{{ $padding !== false && $padding !== 'none' ? '' : 'p-6' }}">
            {{ $slot }}
        </div>
    @endif

    @if(isset($footer))
        <div class="card-footer {{ $padding !== false && $padding !== 'none' ? '-mx-6 -mb-6 px-6 pb-6' : '' }} flex items-center gap-2">
            {{ $footer }}
        </div>
    @endif
</div>