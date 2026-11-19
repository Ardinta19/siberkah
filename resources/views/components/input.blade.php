<!--
  Input Component
  Usage:
  <x-input
    type="text"
    name="email"
    label="Email"
    placeholder="Masukkan email"
    :value="old('email')"
    error="$errors->first('email')"
    helper="Kami tidak akan membagikan email Anda"
    required
  />

  Types: text, email, password, tel, number, date, time, datetime-local, search, url, textarea, select, file
-->

@props([
    'type' => 'text',
    'name' => null,
    'id' => null,
    'label' => null,
    'placeholder' => '',
    'value' => null,
    'error' => null,
    'success' => null,
    'helper' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'autocomplete' => 'off',
    'autofocus' => false,
    'maxlength' => null,
    'minlength' => null,
    'min' => null,
    'max' => null,
    'step' => null,
    'pattern' => null,
    'options' => [], // for select
    'multiple' => false, // for select
    'accept' => null, // for file
    'capture' => null, // for file
    'rows' => 4, // for textarea
    'cols' => null, // for textarea
    'counter' => false,
    'icon' => null, // leading icon
    'trailingIcon' => null,
    'class' => '',
])

<?php
$inputId = $id ?? $name ?? 'input-' . uniqid();
$hasError = !empty($error);
$hasSuccess = !empty($success) && !$hasError;
$showCounter = $counter && $maxlength;
?>

<div class="w-full {{ $class }}">
    @if($label)
        <label for="{{ $inputId }}" class="label">
            {{ $label }}
            @if($required)
                <span class="text-danger-500" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-400 dark:text-neutral-500" aria-hidden="true">
                {{ $icon }}
            </div>
        @endif

        @if($type === 'textarea')
            <textarea
                id="{{ $inputId }}"
                name="{{ $name }}"
                class="input {{ $icon ? 'pl-10' : '' }} {{ $trailingIcon ? 'pr-10' : '' }} {{ $hasError ? 'input-error' : '' }} {{ $hasSuccess ? 'input-success' : '' }}"
                placeholder="{{ $placeholder }}"
                @if($disabled) disabled @endif
                @if($readonly) readonly @endif
                @if($required) required @endif
                @if($maxlength) maxlength="{{ $maxlength }}" @endif
                @if($minlength) minlength="{{ $minlength }}" @endif
                @if($rows) rows="{{ $rows }}" @endif
                @if($cols) cols="{{ $cols }}" @endif
                autocomplete="{{ $autocomplete }}"
                @if($autofocus) autofocus @endif
                aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                aria-describedby="{{ $hasError ? $inputId . '-error' : ($helper ? $inputId . '-helper' : ($hasSuccess ? $inputId . '-success' : '')) }}"
                >{{ $value }}</textarea>

        @elseif($type === 'select')
            <div class="relative">
                <select
                    id="{{ $inputId }}"
                    name="{{ $name }}"
                    class="input appearance-none bg-white dark:bg-neutral-900 pr-10 {{ $hasError ? 'input-error' : '' }} {{ $hasSuccess ? 'input-success' : '' }}"
                    @if($disabled) disabled @endif
                    @if($required) required @endif
                    @if($multiple) multiple @endif
                    autocomplete="{{ $autocomplete }}"
                    aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                    aria-describedby="{{ $hasError ? $inputId . '-error' : ($helper ? $inputId . '-helper' : ($hasSuccess ? $inputId . '-success' : '')) }}"
                >
                    @if(!$required || $multiple)
                        <option value="" disabled {{ is_null($value) ? 'selected' : '' }}>-- Pilih --</option>
                    @endif
                    @foreach($options as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" {{ (string)$value === (string)$optionValue ? 'selected' : '' }}>{{ $optionLabel }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none" aria-hidden="true">
                    <svg class="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>

        @elseif($type === 'file')
            <div class="file-input" x-data="{ dragover: false, fileName: '' }" x-init="
                $watch('dragover', value => {
                    $refs.dropzone.classList.toggle('dragover', value);
                });
                $refs.input.addEventListener('change', e => {
                    if (e.target.files.length > 0) {
                        fileName = Array.from(e.target.files).map(f => f.name).join(', ');
                    }
                });
            ">
                <input type="file"
                    id="{{ $inputId }}"
                    name="{{ $name }}"
                    class="file-input-label"
                    @if($disabled) disabled @endif
                    @if($required) required @endif
                    @if($multiple) multiple @endif
                    @if($accept) accept="{{ $accept }}" @endif
                    @if($capture) capture="{{ $capture }}" @endif
                    aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                    aria-describedby="{{ $hasError ? $inputId . '-error' : ($helper ? $inputId . '-helper' : '') }}"
                    x-ref="input"
                >
                <div class="file-input-label" x-ref="dropzone" @dragover.prevent="dragover = true" @dragleave.prevent="dragover = false" @drop.prevent="dragover = false">
                    <svg class="w-10 h-10 text-neutral-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-neutral-700 dark:text-neutral-300">Seret & lepas file di sini, atau klik untuk pilih</p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                        @if($accept)
                            Format: {{ $accept }}
                        @else
                            Semua format file
                        @endif
                        @if($maxlength)
                            , Maks {{ number_format($maxlength / 1024 / 1024) }} MB
                        @endif
                    </p>
                    @if($fileName)
                        <p class="text-sm text-primary-600 dark:text-primary-400 mt-2 font-medium truncate max-w-xs" x-text="fileName"></p>
                    @endif
                </div>
            </div>

        @else
            <input
                type="{{ $type }}"
                id="{{ $inputId }}"
                name="{{ $name }}"
                class="input {{ $icon ? 'pl-10' : '' }} {{ $trailingIcon ? 'pr-10' : '' }} {{ $hasError ? 'input-error' : '' }} {{ $hasSuccess ? 'input-success' : '' }}"
                placeholder="{{ $placeholder }}"
                value="{{ old($name, $value) }}"
                @if($disabled) disabled @endif
                @if($readonly) readonly @endif
                @if($required) required @endif
                @if($maxlength) maxlength="{{ $maxlength }}" @endif
                @if($minlength) minlength="{{ $minlength }}" @endif
                @if($min) min="{{ $min }}" @endif
                @if($max) max="{{ $max }}" @endif
                @if($step) step="{{ $step }}" @endif
                @if($pattern) pattern="{{ $pattern }}" @endif
                autocomplete="{{ $autocomplete }}"
                @if($autofocus) autofocus @endif
                aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                aria-describedby="{{ $hasError ? $inputId . '-error' : ($helper ? $inputId . '-helper' : ($hasSuccess ? $inputId . '-success' : '')) }}"
            >
        @endif

        @if($trailingIcon)
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-neutral-400 dark:text-neutral-500" aria-hidden="true">
                {{ $trailingIcon }}
            </div>
        @endif

        @if($showCounter)
            <div class="char-counter absolute bottom-1 right-2" aria-live="polite">
                <span x-data="{ length: {{ strlen($value ?? '') }} }" x-init="
                    $watch('$refs.input.value.length', v => { length = v; });
                    $refs.input.addEventListener('input', () => { length = $refs.input.value.length; });
                " x-text="length + ' / {{ $maxlength }}'"></span>
                <span class="sr-only">Karakter tersisa: {{ $maxlength - strlen($value ?? '') }}</span>
            </div>
        @endif
    </div>

    @if($hasError)
        <p id="{{ $inputId }}-error" class="mt-1.5 text-sm text-danger-600 dark:text-danger-400" role="alert">{{ $error }}</p>
    @elseif($helper)
        <p id="{{ $inputId }}-helper" class="mt-1.5 text-sm text-neutral-500 dark:text-neutral-400">{{ $helper }}</p>
    @elseif($hasSuccess)
        <p id="{{ $inputId }}-success" class="mt-1.5 text-sm text-success-600 dark:text-success-400">{{ $success }}</p>
    @endif
</div>