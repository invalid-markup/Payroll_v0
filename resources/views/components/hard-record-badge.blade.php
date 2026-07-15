@props(['label' => 'Locked Record'])

<span
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-500']) }}
    title="This is a permanent financial record. It cannot be edited or deleted. Corrections require a formal amendment workflow."
>
    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11V7a4 4 0 10-8 0v4m14 0V7a4 4 0 10-8 0v4m-6 0h16v10H2V11z" />
    </svg>
    <span>{{ $label }}</span>
</span>
