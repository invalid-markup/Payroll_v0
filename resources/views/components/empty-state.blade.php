@props(['title', 'description' => null, 'icon' => 'folder'])

<div class="text-center p-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
        @if($icon === 'folder')
            <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
        @else
            {{ $iconSlot ?? '' }}
        @endif
    </div>
    
    <h3 class="mt-4 text-sm font-semibold text-gray-900">{{ $title }}</h3>
    
    @if($description)
        <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
    @endif
    
    @if(isset($action))
        <div class="mt-6">
            {{ $action }}
        </div>
    @endif
</div>
