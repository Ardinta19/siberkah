<!--
  Badge Component
  Usage:
  <x-badge variant="primary" :dot="true">Aktif</x-badge>

  Variants: primary, secondary, success, warning, danger, info, neutral
  Dot: true/false (adds colored indicator dot)
  Size: sm, md, lg
-->

@props([
    'variant' => 'neutral',
    'dot' => false,
    'size' => 'md',
    'class' => '',
    'icon' => null,
])

<?php
$variants = [
    'primary' => 'badge-primary',
    'secondary' => 'badge-secondary',
    'success' => 'badge-success',
    'warning' => 'badge-warning',
    'danger' => 'badge-danger',
    'info' => 'badge-info',
    'neutral' => 'badge-neutral',
];

$dotVariants = [
    'primary' => 'badge-dot-primary',
    'secondary' => 'badge-dot-secondary',
    'success' => 'badge-dot-success',
    'warning' => 'badge-dot-warning',
    'danger' => 'badge-dot-danger',
    'info' => 'badge-dot-info',
    'neutral' => 'badge-dot-neutral',
];

$sizes = [
    'sm' => 'px-2 py-0.5 text-[11px]',
    'md' => 'px-2.5 py-0.5 text-xs',
    'lg' => 'px-3 py-1 text-sm',
];

$variantClass = $variants[$variant] ?? $variants['neutral'];
$dotClass = $dot ? 'badge-dot ' . ($dotVariants[$variant] ?? $dotVariants['neutral']) : '';
$sizeClass = $sizes[$size] ?? $sizes['md'];
?>

<span class="badge {{ $variantClass }} {{ $dotClass }} {{ $sizeClass }} {{ $class }}" {{ $attributes }}>
    @if($icon && !$dot)
        <span class="inline-flex items-center justify-center mr-1.5" aria-hidden="true">{{ $icon }}</span>
    @endif
    {{ $slot }}
</span>