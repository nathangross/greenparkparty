@props(['id' => null])
<h2 @if ($id) id="{{ $id }}" @endif {{ $attributes->merge(['class' => 'my-4 text-6xl font-black italic']) }}>
    {{ $slot }}
</h2>
