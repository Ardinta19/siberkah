<!--
  Button Component
  Usage:
  <x-button variant="primary" size="md" :loading="false" :disabled="false" wire:click="action">
    Label
  </x-button>

  Variants: primary, secondary, outline, ghost, danger, success, link
  Sizes: sm, md, lg, xl
-->

@props([
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
    'disabled' => false,
    'type' => 'button',
    'fullWidth' => false,
    'icon' => null,
    'iconPosition' => 'left', // left, right
    'class' => '',
])

<?php
$variants = [
    'primary' => 'btn-primary',
    'secondary' => 'btn-secondary',
    'outline' => 'btn-outline',
    'ghost' => 'btn-ghost',
    'danger' => 'btn-danger',
    'success' => 'btn-success',
    'link' => 'btn-link',
];

$sizes = [
    'sm' => 'btn-sm',
    'md' => 'btn-md',
    'lg' => 'btn-lg',
    'xl' => 'btn-xl',
];

$variantClass = $variants[$variant] ?? $variants['primary'];
$sizeClass = $sizes[$size] ?? $sizes['md'];
$widthClass = $fullWidth ? 'w-full' : '';
$loadingClass = $loading ? 'btn-loading' : '';
$disabledClass = $disabled ? 'disabled' : '';
?>

<button
    type="{{ $type }}"
    class="btn {{ $variantClass }} {{ $sizeClass }} {{ $widthClass }} {{ $loadingClass }} {{ $disabledClass }} {{ $class }}"
    {{ $attributes->merge(['disabled' => $disabled || $loading]) }}
    {{ $loading ? 'aria-busy="true"' : '' }}
>
    @if($loading)
        <span class="sr-only">Memproses...</span>
    @elseif($icon && $iconPosition === 'left')
        <span class="flex-shrink-0" aria-hidden="true">{{ $icon }}</span>
    @endif

    <span class="{{ $loading ? 'sr-only' : '' }}">{{ $slot }}</span>

    @if($icon && $iconPosition === 'right' && !$loading)
        <span class="flex-shrink-0" aria-hidden="true">{{ $icon }}</span>
    @endif
</button>