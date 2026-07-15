@props(['label', 'value', 'subLabel' => null, 'color' => 'blue'])

@php
$iconBg = match($color) {
    'blue' => 'bg-blue-100 text-blue-600',
    'green' => 'bg-green-100 text-green-600',
    'amber' => 'bg-amber-100 text-amber-600',
    'red' => 'bg-red-100 text-red-600',
    'purple' => 'bg-purple-100 text-purple-600',
    default => 'bg-gray-100 text-gray-600',
};
@endphp

<div class="bg-white overflow-hidden shadow sm:rounded-lg border border-gray-200">
    <div class="p-5">
        <div class="flex items-center">
            @if(isset($icon))
                <div class="flex-shrink-0">
                    <div class="rounded-md p-3 {{ $iconBg }}">
                        {{ $icon }}
                    </div>
                </div>
            @endif
            <div class="{{ isset($icon) ? 'ml-5' : '' }} w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        {{ $label }}
                    </dt>
                    <dd>
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ $value }}
                        </div>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    @if($subLabel || isset($footer))
        <div class="bg-gray-50 px-5 py-3 border-t border-gray-200">
            <div class="text-sm">
                @if($subLabel)
                    <span class="text-gray-500">{{ $subLabel }}</span>
                @endif
                @if(isset($footer))
                    {{ $footer }}
                @endif
            </div>
        </div>
    @endif
</div>
