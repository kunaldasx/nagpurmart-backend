@extends('layouts.seller.app', ['page' => $menuSeller['tax_rates']['active'] ?? ""])

@section('title', __('labels.tax_rates'))

@section('header_data')
    @php
        $page_title = __('labels.tax_rates');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('seller.dashboard')],
        ['title' => __('labels.tax_rates'), 'url' => '']
    ];
@endphp

@section('seller-content')
    <div class="row row-cards mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.tax_groups') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                <button class="btn btn-outline-primary refresh-table">
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
                        <x-datatable id="tax-group-table" :columns="$classColumns"
                                     route="{{ route('seller.tax-classes.datatable') }}"
                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script>
    document.addEventListener("DOMContentLoaded", function () {
        let el;
        window.TomSelect &&
        new TomSelect((el = document.getElementById("select-tax-rate")), {
            valueField: 'value',
            labelField: 'title',
            searchField: 'title',
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            render: {
                item: function (data, escape) {
                    return "<div>" + escape(data.title) + "</div>";
                },
                option: function (data, escape) {
                    return "<div>" + escape(data.title) + "</div>";
                }
            },
            load: function (query, callback) {
                // You'd replace the fetch URL with your own API endpoint
                fetch(base_url + '/admin/tax-rates/search?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(json => {
                        // json should be an array of { value: "...", text: "..." }
                        callback(json);
                    }).catch(() => {
                    callback();
                });
            }
        });
    });
</script>
