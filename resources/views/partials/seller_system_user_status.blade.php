@php
    $isActive = $status === 'active';
@endphp

<span class="badge {{ $isActive ? 'bg-green-lt' : 'bg-secondary-lt' }}">
    {{ $isActive ? 'Active' : 'Inactive' }}
</span>
