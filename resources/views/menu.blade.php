<x-layouts.app>
    <section class="px-4 py-12 lg:py-24 flex items-start flex-col gap-4">
        <a href="{{route('welcome')}}#details" class="text-lg font-bold italic text-green hover:underline">Back</a>
        <h1 class="text-4xl font-bold">Menu - Claybourne Grille</h1>
        <img src="{{Vite::asset('resources/images/food-images.jpeg')}}" class="w-full max-w-[700px]" alt="Food images">
        <img src="{{Vite::asset('resources/images/menu.jpeg')}}" class="w-full max-w-[700px]" alt="Menu">
        <a href="{{route('welcome')}}#details" class="text-lg font-bold italic text-green hover:underline">Back</a>
    </section>
</x-layouts.app>