@php
    $approvedUntil = \Illuminate\Support\Carbon::make($approvedUntil ?? null);
@endphp

@if($status === 'pending')
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-yellow-lt">Pending</span>
        <button type="button" class="btn btn-sm btn-success seller-user-approval-trigger"
                data-approval-action="approve" data-action-url="{{ route('seller.system-users.approve-login', $id) }}"
                data-user-name="{{ $userName }}" data-auto-open="{{ $autoOpen ? 'true' : 'false' }}">Approve</button>
        <button type="button" class="btn btn-sm btn-outline-danger seller-user-approval-trigger"
                data-approval-action="disapprove" data-action-url="{{ route('seller.system-users.reject-login', $id) }}"
                data-user-name="{{ $userName }}">Reject</button>
    </div>
@elseif($status === 'approved')
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-green-lt" title="Approved until {{ $approvedUntil?->format('Y-m-d H:i') ?? 'unknown' }}">
            Approved until {{ $approvedUntil?->format('H:i') ?? 'unknown' }}
        </span>
        <button type="button" class="btn btn-sm btn-outline-danger seller-user-approval-trigger"
            data-approval-action="disapprove" data-action-url="{{ route('seller.system-users.reject-login', $id) }}"
            data-user-name="{{ $userName }}">Disapprove</button>
    </div>
@else
    <div class="d-flex align-items-center gap-2">
        <span class="badge {{ in_array($status, ['rejected', 'disapproved'], true) ? 'bg-red-lt' : 'bg-secondary-lt' }}">
            {{ ucfirst(str_replace('_', ' ', $status ?? 'not requested')) }}
        </span>
        <button type="button" class="btn btn-sm btn-success seller-user-approval-trigger"
            data-approval-action="approve" data-action-url="{{ route('seller.system-users.approve-login', $id) }}"
            data-user-name="{{ $userName }}" data-auto-open="{{ $autoOpen ? 'true' : 'false' }}">Approve</button>
    </div>
@endif