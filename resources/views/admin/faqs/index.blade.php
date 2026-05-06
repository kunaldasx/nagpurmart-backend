@php use App\Enums\ActiveInactiveStatusEnum; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['faqs']['active'] ?? ""])

@section('title', __('labels.faqs'))
@section('header_data')
    @php
        $page_title =  __('labels.faqs');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' =>  __('labels.faqs'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <!-- Page body -->
    <div class="page-body">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __('labels.faqs') }}</h3>
                            <x-breadcrumb :items="$breadcrumbs"/>
                        </div>
                        <div class="card-actions">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <select class="form-select" id="statusFilter">
                                        <option value="">{{ __('labels.status') }}</option>
                                        @foreach(ActiveInactiveStatusEnum::values() as $type)
                                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto">
                                    @if($createPermission ?? false)
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#faq-modal">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                                 viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                 stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <line x1="12" y1="5" x2="12" y2="19"/>
                                                <line x1="5" y1="12" x2="19" y2="12"/>
                                            </svg>
                                            {{ __('labels.add_faq') }}
                                        </button>
                                    @endif
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-outline-primary" id="refresh">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                             stroke-linecap="round" stroke-linejoin="round"
                                             class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                        </svg>
                                        {{ __('labels.refresh') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-table">
                        <div class="row w-full p-3">
                            <x-datatable id="faqs-table" :columns="$columns"
                                         route="{{ route('admin.faqs.datatable') }}"
                                         :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(($createPermission ?? false) || ($editPermission ?? false))

        <!-- Product FAQ Modal -->
        <div class="modal modal-blur fade" id="faq-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="faq-modal-title">{{ __('labels.add_faq') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form class="form-submit" id="faq-form" method="POST" action="{{ route('admin.faqs.store') }}">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('labels.question') }}</label>
                                        <textarea class="form-control" id="question" name="question" rows="3"
                                                  placeholder="{{ __('labels.enter_question') }}" required></textarea>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('labels.answer') }}</label>
                                        <textarea class="form-control" id="answer" name="answer" rows="4"
                                                  placeholder="{{ __('labels.enter_answer') }}" required></textarea>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('labels.status') }}</label>
                                        <select class="form-select text-capitalize" id="status" name="status">
                                            @foreach(ActiveInactiveStatusEnum::values() as $status)
                                                <option value="{{$status}}">{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="#" class="btn" data-bs-dismiss="modal">
                                {{ __('labels.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary ms-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                     viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                {{ __('labels.submit') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
