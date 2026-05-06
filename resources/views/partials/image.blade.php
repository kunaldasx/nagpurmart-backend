<div>
    <!-- Photo -->
    @if($image !== null)
        <a href="{{$image}}" class="img-box-60" target="_blank"
           data-fslightbox="gallery">
            <img src="{{$image}}" alt="{{$title ?? "Image"}}" width="100" height="50">
        </a>
    @else
        <span class="text-muted">No Image</span>
    @endif
</div>
