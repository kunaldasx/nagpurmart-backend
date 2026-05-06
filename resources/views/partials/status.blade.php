<div>
    @if ($status == 'active' || $status == 'approved' || $status == 'visible'|| $status == 'published' || $status == 'verified'|| $status == 'paid' || $status == '')
        <span class="badge bg-green-lt text-uppercase">{{Str::replace("_", " ",$status)}}</span>
    @elseif ($status == 'inactive' || $status == 'rejected' || $status == 'not_approved' || $status == 'draft' || $status == 'pending_verification' || $status == 'pending')
        <span class="badge bg-red-lt text-uppercase">{{Str::replace("_", " ",$status)}}</span>
    @else
        <span class="badge bg-indigo-lt text-uppercase">{{Str::replace("_", " ",$status)}}</span>
    @endif
</div>
