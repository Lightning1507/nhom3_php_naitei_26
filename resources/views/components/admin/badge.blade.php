@props([
    'variant' => 'neutral',
])

@php
    $variantClass = match ($variant) {
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'danger' => 'border-red-200 bg-red-50 text-red-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
        'info' => 'border-blue-200 bg-blue-50 text-blue-700',
        'manager' => 'border-purple-200 bg-purple-50 text-purple-700',
        'staff' => 'border-blue-200 bg-blue-50 text-primary',
        default => 'border-gray-200 bg-gray-50 text-gray-700',
    };
@endphp

<span {{ $attributes->class("inline-flex items-center rounded-full border px-2.5 py-1 font-inter text-xs font-semibold {$variantClass}") }}>
    {{ $slot }}
</span>
