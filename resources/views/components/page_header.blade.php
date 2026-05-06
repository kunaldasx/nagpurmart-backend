@php
    $segments = request()->segments();
    $start = $start ?? 1; // default: skip first segment (index 0 = 'admin')
@endphp

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="card">
                <div class="card-body">
                    <h2 class="page-title pb-1">{{ $title }}</h2>
                    <ol class="breadcrumb breadcrumb-arrows">
                        @for ($i = $start; $i < $start + $step; $i++)
                            @php
                                $segment = ucfirst(str_replace('-', ' ', $segments[$i] ?? ''));
                                $url = url(implode('/', array_slice($segments, 0, $i + 1)));
                            @endphp

                            @if ($i < $start + $step - 1)
                                <li class="breadcrumb-item"><a href="{{ $url }}">{{ $segment }}</a></li>
                            @else
                                <li class="breadcrumb-item active">{{ $title }}</li>
                            @endif
                        @endfor
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
