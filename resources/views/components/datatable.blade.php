<table id="{{ $id }}" data-datatable data-route="{{ $route }}" data-columns='@json($columns)'
       data-options='@json($options ?? [])'
       class="{{ $class ?? 'table table-striped table-bordered table-vcenter text-nowrap w-100' }} data-table">
    <thead class="table-light">
    <tr>
{{--        @dd($columns)--}}
        @foreach ($columns as $column)
            <th>{{ $column['label'] ?? '' }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    </tbody>
</table>
