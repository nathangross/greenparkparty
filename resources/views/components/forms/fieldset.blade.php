@props([
    'legend' => null,
    'description' => null,
])

<fieldset {{ $attributes->merge(['class' => 'rounded-sm border border-green-dark/10 bg-green-dark/5 p-4']) }}>

    @if ($legend)
        <legend class="border border-green-dark/10 bg-white px-3 text-xl font-bold">{{ $legend }}</legend>
    @endif

    @if ($description)
        <span class="max-w-[65ch] text-gray-700">{{ $description }}</span>
    @endif

    {{ $slot }}
</fieldset>
