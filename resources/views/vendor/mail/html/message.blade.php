<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
    <span style="
            font-family: Poppins, Arial, sans-serif;
            font-size: 52px;
            font-weight: 900;
            letter-spacing: -2px;
            color: #333864;
        ">
        nagpur
    </span>
    <span
        style="
            font-family: Poppins, Arial, sans-serif;
            font-size: 52px;
            font-weight: 900;
            letter-spacing: -2px;
            color: #004dff;
        "
    >
        mart.in
    </span>
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('mail.from.name', config('app.name')) }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
