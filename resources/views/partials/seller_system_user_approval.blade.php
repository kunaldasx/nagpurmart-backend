@if($status === 'pending')
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-yellow-lt">Pending</span>
        <form method="POST" action="{{ route('seller.system-users.approve-login', $id) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-success">Approve</button>
        </form>
        <form method="POST" action="{{ route('seller.system-users.reject-login', $id) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
        </form>
    </div>
@elseif($status === 'approved')
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-green-lt" title="Approved until {{ $approvedUntil?->format('Y-m-d H:i') }}">
            Approved until {{ $approvedUntil?->format('H:i') }}
        </span>
        <form method="POST" action="{{ route('seller.system-users.reject-login', $id) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger">Disapprove</button>
        </form>
    </div>
@else
    <span class="badge {{ in_array($status, ['rejected', 'disapproved'], true) ? 'bg-red-lt' : 'bg-secondary-lt' }}">
        {{ ucfirst(str_replace('_', ' ', $status ?? 'not requested')) }}
    </span>
@endif