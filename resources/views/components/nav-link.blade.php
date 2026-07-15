@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 border-r-4 border-blue-700 group transition-colors duration-150'
            : 'flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 border-r-4 border-transparent group transition-colors duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if (isset($icon))
        <div class="{{ ($active ?? false) ? 'text-blue-700' : 'text-gray-400 group-hover:text-gray-500' }}">
            {{ $icon }}
        </div>
    @endif
    {{ $slot }}
</a>
