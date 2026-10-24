@php
    $approvedUntil = \Illuminate\Support\Carbon::make($approvedUntil ?? null);
    $approvalIsActive = $status === 'approved' && $approvedUntil?->isFuture();
    $statusLabel = $approvalIsActive
        ? 'Approved until ' . $approvedUntil->format('H:i')
        : ($status === 'approved' ? 'Expired' : ucfirst(str_replace('_', ' ', $status ?? 'not requested')));
    $statusClass = $approvalIsActive ? 'bg-green-lt' : match ($status) {
        'pending' => 'bg-yellow-lt',
        'approved', 'disapproved', 'rejected' => 'bg-red-lt',
        default => 'bg-secondary-lt',
    };
@endphp

<div class="seller-user-approval-control">
    <span class="badge seller-user-approval-status {{ $statusClass }}"
          title="{{ $approvalIsActive ? 'Approved until ' . $approvedUntil->format('Y-m-d H:i') : $statusLabel }}">
        {{ $statusLabel }}
    </span>
    <button type="button"
            class="btn btn-icon seller-user-approval-edit seller-user-approval-trigger"
            title="Edit login approval status"
            aria-label="Edit login approval status for {{ $userName }}"
            data-bs-toggle="modal"
            data-bs-target="#seller-user-approval-modal"
            data-user-id="{{ $id }}"
            data-user-name="{{ $userName }}"
            data-status="{{ $status }}"
            data-approval-active="{{ $approvalIsActive ? 'true' : 'false' }}"
            data-approved-until="{{ $approvedUntil?->format('Y-m-d H:i') ?? '' }}"
            data-approve-url="{{ route('seller.system-users.approve-login', $id) }}"
            data-disapprove-url="{{ route('seller.system-users.reject-login', $id) }}"
            data-auto-open="{{ $autoOpen ? 'true' : 'false' }}">
        <i class="ti ti-edit" aria-hidden="true"></i>
    </button>
</div>