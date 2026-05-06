@php use App\Enums\Attribute\AttributeTypesEnum; @endphp
<div>
    @if($data->attribute->swatche_type === AttributeTypesEnum::COLOR())
        <div style="width: 35px; height: 35px; background-color: {{ $data->swatche_value }}; border-radius: 4px; padding: 2px; border:solid 1px;"></div>
    @elseif($data->attribute->swatche_type === AttributeTypesEnum::IMAGE())
        <a href="{{$data->swatche_value}}" class="" target="_blank"
           data-fslightbox="gallery">
            <img src="{{$data->swatche_value}}" alt="{{"Image"}}" width="100" height="50">
        </a>
    @else
        <span>{{ $data->swatche_value }}</span>
    @endif
</div>
