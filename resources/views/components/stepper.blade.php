<!--
  Stepper Component
  Usage:
  <x-stepper :current-step="2" :steps="['Produk', 'Detail', 'Pembayaran', 'Konfirmasi']" />

  Steps can also be objects:
  <x-stepper :current-step="2" :steps="[
    ['label' => 'Produk', 'icon' => '...'],
    ['label' => 'Detail', 'icon' => '...'],
    ['label' => 'Pembayaran', 'icon' => '...'],
    ['label' => 'Konfirmasi', 'icon' => '...'],
  ]" />
-->

@props([
    'currentStep' => 1,
    'steps' => [],
    'orientation' => 'horizontal', // horizontal, vertical
    'clickable' => false,
    'class' => '',
])

<?php
$currentStep = max(1, min($currentStep, count($steps)));
$stepCount = count($steps);
$isHorizontal = $orientation === 'horizontal';
?>

<div class="stepper {{ $isHorizontal ? 'flex' : 'flex-col' }} {{ $class }}" {{ $attributes }} role="navigation" aria-label="Progress langkah">
    @foreach($steps as $index => $step)
        <?php
        $stepNumber = $index + 1;
        $isActive = $stepNumber === $currentStep;
        $isCompleted = $stepNumber < $currentStep;
        $isFuture = $stepNumber > $currentStep;

        $stepLabel = is_array($step) ? ($step['label'] ?? 'Langkah ' . $stepNumber) : $step;
        $stepIcon = is_array($step) ? ($step['icon'] ?? null) : null;
        $stepUrl = is_array($step) ? ($step['url'] ?? null) : null;
        ?>

        <div class="stepper-item {{ $isActive ? 'active' : '' }} {{ $isCompleted ? 'completed' : '' }}" role="listitem">
            @if($isHorizontal && $index > 0)
                <div class="stepper-line {{ $isCompleted ? 'completed' : '' }}" aria-hidden="true"></div>
            @endif

            <div class="stepper-step {{ $clickable && ($isCompleted || $isActive) ? 'cursor-pointer' : '' }}" {{ $clickable && $stepUrl ? 'x-on:click="$dispatch(\'navigate\', { url: \'' . $stepUrl . '\' })"' : '' }} role="{{ $clickable ? 'button' : 'listitem' }}" {{ $clickable && $stepUrl ? 'tabindex="0" @keydown.enter.prevent="$dispatch(\'navigate\', { url: \'' . $stepUrl . '\' })" @keydown.space.prevent="$dispatch(\'navigate\', { url: \'' . $stepUrl . '\' })"' : '' }} aria-current="{{ $isActive ? 'step' : 'false' }}" aria-label="{{ $stepLabel }}: {{ $isCompleted ? 'Selesai' : ($isActive ? 'Aktif' : 'Belum dimulai') }}">
                <div class="stepper-circle {{ $isActive ? 'active' : '' }} {{ $isCompleted ? 'completed' : '' }}" aria-hidden="true">
                    @if($isCompleted)
                        <!-- Checkmark shown via CSS ::after -->
                    @elseif($stepIcon)
                        <span class="w-4 h-4">{{ $stepIcon }}</span>
                    @else
                        <span class="text-xs">{{ $stepNumber }}</span>
                    @endif
                </div>
                <span class="stepper-label">{{ $stepLabel }}</span>
            </div>

            @if(!$isHorizontal && $index < $stepCount - 1)
                <div class="stepper-line {{ $isCompleted ? 'completed' : '' }}" style="left: 20px; top: 32px; bottom: 0; width: 2px;" aria-hidden="true"></div>
            @endif
        </div>
    @endforeach
</div>

@if($clickable)
    <script>
        document.addEventListener('navigate', e => {
            window.location.href = e.detail.url;
        });
    </script>
@endif