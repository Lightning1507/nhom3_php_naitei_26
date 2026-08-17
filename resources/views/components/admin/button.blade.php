@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
])

@php
    $variantClass = match ($variant) {
        'secondary' => 'admin-btn-secondary',
        'danger' => 'admin-btn-danger',
        'ghost' => 'admin-btn-ghost',
        default => 'admin-btn-primary',
    };

    $classes = "admin-btn {$variantClass}";
@endphp

@if ($href)
    <a
        href="{{ $disabled ? '#' : $href }}"
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif
        {{ $attributes->class($classes) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        @disabled($disabled)
        {{ $attributes->class($classes) }}
    >
        {{ $slot }}
    </button>
@endif
