<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Policy' }}</title>
    <link rel="icon" href="{{ !empty($systemSettings['favicon']) ? $systemSettings['favicon'] : "" }}"
          sizes="image/x-icon">
    <style>
        body { margin: 2rem; font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { font-size: 1.5rem; margin-bottom: 1rem; }
        .content { line-height: 1.6; }
    </style>
    <base target="_self">
 </head>
<body>
<div class="container">
    <h1>{{ $title ?? '' }}</h1>
    <div class="content">
        {!! $content !!}
    </div>
    @if(empty($content))
        <p>No content available.</p>
    @endif
    </div>
</body>
</html>
