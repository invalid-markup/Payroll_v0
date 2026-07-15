@props(['title'])

<div class="md:flex md:items-center md:justify-between mb-6">
    <div class="flex-1 min-w-0 flex items-center gap-3">
        <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            {{ $title }}
        </h1>
        
        @if(isset($badge))
            {{ $badge }}
        @endif
    </div>
    
    @if(isset($actions))
        <div class="mt-4 flex md:mt-0 md:ml-4 gap-2">
            {{ $actions }}
        </div>
    @endif
</div>

@if(isset($breadcrumb))
    <div class="mb-6">
        {{ $breadcrumb }}
    </div>
@endif
