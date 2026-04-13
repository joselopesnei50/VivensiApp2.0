<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $banner->title }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@php
    $fonts = ['Inter','Roboto','Poppins','Montserrat','Open Sans','Lato','Nunito','Raleway','Oswald','Playfair Display'];
    $usedFont = $banner->settings['font_family'] ?? 'Inter';
    $googleFont = str_replace(' ', '+', $usedFont);
@endphp
<link href="https://fonts.googleapis.com/css2?family={{ $googleFont }}:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  * { margin:0;padding:0;box-sizing:border-box; }
  body { background:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh; }
  .banner-wrap {
    width:{{ $banner->width }}px;
    height:{{ $banner->height }}px;
    background:{{ $banner->settings['bg_color'] ?? '#ffffff' }};
    font-family:'{{ $usedFont }}',sans-serif;
    overflow:hidden;
    position:relative;
  }
</style>
</head>
<body>
<div class="banner-wrap">
@foreach($banner->sections as $section)
@php $c = $section->content ?? []; @endphp
@include('banners.partials.section_html', ['c' => $c, 'type' => $section->type, 'banner' => $banner])
@endforeach
</div>
</body>
</html>
