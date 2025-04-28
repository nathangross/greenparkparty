@props(['flag' => false, 'dark' => false])
<div
    {{ $attributes->merge()->class([
            'text-green-dark rounded-full px-4 py-2 text-lg font-bold italic',
            'hover:bg-green/10' => !$dark,
            'hover:bg-green-dark/10' => $dark,
        ]) }}>
    <div class="flex items-center gap-1">
        <span>{{ $slot }}</span>
        @if ($flag)
            <span class="flex size-2 rounded-full border border-orange-600 bg-orange-500"></span>
        @endif
    </div>
</div>
