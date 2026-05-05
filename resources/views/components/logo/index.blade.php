@php
    $svg = file_get_contents(resource_path('views/components/logo/universal-green.svg'));
    $svg = preg_replace('/<svg\b/', '<svg ' . $attributes->toHtml(), $svg, 1);
@endphp

{!! $svg !!}
