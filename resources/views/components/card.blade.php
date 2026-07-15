@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200']) }}>
    @if($title || isset($header))
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6 bg-gray-50 flex justify-between items-center">
            @if($title)
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ $title }}
                </h3>
            @endif
            @if(isset($header))
                <div>
                    {{ $header }}
                </div>
            @endif
        </div>
    @endif
    
    <div class="p-6">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-4 py-4 sm:px-6 bg-gray-50 border-t border-gray-200">
            {{ $footer }}
        </div>
    @endif
</div>
