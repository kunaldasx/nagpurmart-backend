<div class="btn-list">
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveDepositModal"
            data-id="{{$id}}"
            data-username="{{$userName }}" data-amount="{{ $amount }}">
        {{ __('labels.complete') }}
    </button>
    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectDepositModal"
            data-id="{{$id}}" data-username="{{$userName }}" data-amount="{{ $amount }}">
        {{ __('labels.fail') }}
    </button>
</div>
