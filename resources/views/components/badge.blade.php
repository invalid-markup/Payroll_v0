@props(['status'])

@php
$badgeClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wider whitespace-nowrap';
$icon = '';

switch(strtolower($status)) {
    // payroll_run_status
    case 'draft':
        $badgeClasses .= ' bg-gray-100 text-gray-700';
        break;
    case 'validated':
        $badgeClasses .= ' bg-blue-100 text-blue-800';
        $icon = '✓';
        break;
    case 'preview':
        $badgeClasses .= ' bg-amber-100 text-amber-800';
        $icon = '👁';
        break;
    case 'approved':
        $badgeClasses .= ' bg-blue-200 text-blue-900';
        $icon = '⏳';
        break;
    case 'locked':
        $badgeClasses .= ' bg-green-100 text-green-800';
        $icon = '🔒';
        break;
    case 'filed':
        $badgeClasses .= ' bg-emerald-100 text-emerald-900';
        $icon = '📁';
        break;
    case 'amended':
        $badgeClasses .= ' bg-purple-100 text-purple-800';
        $icon = '✏️';
        break;
    case 'reversed':
        $badgeClasses .= ' bg-red-100 text-red-800 line-through';
        $icon = '✕';
        break;
        
    // employee_status / generic active
    case 'active':
        $badgeClasses .= ' bg-green-100 text-green-800';
        break;
    case 'terminated':
        $badgeClasses .= ' bg-gray-200 text-gray-600';
        break;

    // loan_status
    case 'suspended':
        $badgeClasses .= ' bg-amber-100 text-amber-800';
        break;
    case 'completed':
        $badgeClasses .= ' bg-green-100 text-green-800';
        break;
    case 'closed':
        $badgeClasses .= ' bg-gray-200 text-gray-600';
        break;
        
    // processing_status / notification_status
    case 'pending':
        $badgeClasses .= ' bg-gray-100 text-gray-700';
        break;
    case 'calculated':
        $badgeClasses .= ' bg-blue-100 text-blue-800';
        break;
    case 'failed':
        $badgeClasses .= ' bg-red-100 text-red-800';
        break;
    case 'flagged_insufficient_funds':
        $badgeClasses .= ' bg-amber-100 text-amber-800';
        $icon = '⚠';
        break;
        
    // payroll_period_status / others
    case 'open':
    case 'sent':
        $badgeClasses .= ' bg-green-100 text-green-800';
        break;
        
    default:
        $badgeClasses .= ' bg-gray-100 text-gray-800';
        break;
}
@endphp

<span {{ $attributes->merge(['class' => $badgeClasses, 'aria-label' => 'Status: ' . $status]) }}>
    @if($icon)
        <span class="mr-1" aria-hidden="true">{{ $icon }}</span>
    @endif
    {{ str_replace('_', ' ', $status) }}
</span>
