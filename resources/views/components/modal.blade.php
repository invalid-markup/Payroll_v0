@props(['name', 'title', 'confirmText' => 'Confirm', 'cancelText' => 'Cancel'])

<div 
    x-data="{ show: false }"
    x-show="show"
    @keydown.escape.window="show = false"
    @open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    @close-modal.window="if ($event.detail === '{{ $name }}') show = false"
    style="display: none;"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title" role="dialog" aria-modal="true"
>
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        
        <div 
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" 
            aria-hidden="true"
            @click="show = false"
        ></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div 
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
        >
            <div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                        {{ $title }}
                    </h3>
                    <div class="mt-2 text-left">
                        {{ $slot }}
                    </div>
                </div>
            </div>
            @if(isset($footer))
                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                    {{ $footer }}
                </div>
            @else
                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                    <x-button type="button" @click="show = false" class="w-full sm:ml-3 sm:w-auto">
                        {{ $confirmText }}
                    </x-button>
                    <x-button variant="secondary" type="button" @click="show = false" class="w-full mt-3 sm:mt-0 sm:w-auto">
                        {{ $cancelText }}
                    </x-button>
                </div>
            @endif
        </div>
    </div>
</div>
