@php use App\Enums\Order\OrderItemStatusEnum; @endphp
<div class="d-flex justify-content-start align-items-center">
    {{--        @if($editPermission)--}}
    <a
        class="btn btn-outline-blue me-2 p-1"
        data-id="{{$id}}"
        data-title="{{$title}}"
        title="{{$title}}"
        href="{{ $route }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="icon icon-tabler icons-tabler-outline icon-tabler-edit m-0">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/>
            <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/>
            <path d="M16 5l3 3"/>
        </svg>
        More Information
    </a>
    {{--    @endif--}}
    @if($panel === 'admin')
        <a
            class="btn btn-outline-blue me-2 p-1"
            data-id="{{$uuid}}"
            data-title="View Invoice"
            title="View Invoice"
            href="{{ url('admin/orders/invoice?id=' . $uuid) }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 class="icon icon-tabler icons-tabler-outline icon-tabler-invoice me-0">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                <path
                    d="M19 12v7a1.78 1.78 0 0 1 -3.1 1.4a1.65 1.65 0 0 0 -2.6 0a1.65 1.65 0 0 1 -2.6 0a1.65 1.65 0 0 0 -2.6 0a1.78 1.78 0 0 1 -3.1 -1.4v-14a2 2 0 0 1 2 -2h7l5 5v4.25"/>
            </svg>
            Invoice
        </a>
    @endif
</div>
@if($editPermission)

    @if(empty($status) || $status === OrderItemStatusEnum::AWAITING_STORE_RESPONSE())
        <div class="d-flex me-2 mt-2">
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#acceptModel" data-id="{{$id}}">
                Accept
            </button>
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModel"
                    data-id="{{$id}}">
                Reject
            </button>
        </div>
    @endif
    @if(!empty($status) && $status == OrderItemStatusEnum::ACCEPTED())
        <div class="d-flex me-2 mt-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#preparingModel" data-id="{{$id}}">
                Mark
                as Preparing
            </button>
        </div>
    @endif
@endif
