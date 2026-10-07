@php
    $approvedUntil = \Illuminate\Support\Carbon::make($approvedUntil ?? null);
    $statusLabel = ucfirst(str_replace('_', ' ', $status ?? 'not requested'));
    $statusClass = match ($status) {
        'pending' => 'bg-yellow-lt',
        'approved' => 'bg-green-lt',
        'disapproved', 'rejected' => 'bg-red-lt',
        default => 'bg-secondary-lt',
    };
@endphp

<div class="d-flex align-items-center gap-2">
    <span class="badge {{ $statusClass }}" title="{{ $status === 'approved' ? 'Approved until ' . ($approvedUntil?->format('Y-m-d H:i') ?? 'unknown') : $statusLabel }}">
        {{ $status === 'approved' ? 'Approved until ' . ($approvedUntil?->format('H:i') ?? 'unknown') : $statusLabel }}
    </span>
    <button type="button"
            class="btn btn-icon btn-sm btn-outline-primary seller-user-approval-trigger"
            title="Edit login approval status"
            aria-label="Edit login approval status for {{ $userName }}"
            data-bs-toggle="modal"
            data-bs-target="#seller-user-approval-modal"
            data-user-id="{{ $id }}"
            data-user-name="{{ $userName }}"
            data-status="{{ $status }}"
            data-approved-until="{{ $approvedUntil?->format('Y-m-d H:i') ?? '' }}"
            data-approve-url="{{ route('seller.system-users.approve-login', $id) }}"
            data-disapprove-url="{{ route('seller.system-users.reject-login', $id) }}"
            data-auto-open="{{ $autoOpen ? 'true' : 'false' }}">
        <i class="ti ti-edit" aria-hidden="true"></i>
    </button>
</div>