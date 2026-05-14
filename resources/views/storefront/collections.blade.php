@extends('layouts.storefront')

@section('content')
<section class="relative w-full h-[40vh] overflow-hidden px-margin-desktop py-8">
    <div class="w-full h-full relative rounded-xl overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('images/products/product-10.jpg') }}'); filter: brightness(0.7);">
        </div>
        <div class="absolute inset-0 flex items-center justify-center">
            <h1 class="font-display-lg text-display-lg text-white text-center">Our Collections</h1>
        </div>
    </div>
</section>

<section class="max-w-container-max mx-auto px-margin-desktop py-section-gap">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter">
        <!-- Makeup special card -->
        <a href="{{ route('storefront.category', 'makeup') }}" class="group relative bg-white rounded-lg overflow-hidden transition-all duration-500 hover:shadow-[0_30px_60px_rgba(0,0,0,0.08)] border border-white/40 block">
            <div class="aspect-square relative overflow-hidden">
                <img class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" src="{{ asset('images/products/product-8.jpg') }}"/>
                <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
                <div class="absolute bottom-6 left-6 right-6">
                    <h2 class="font-display-md text-3xl text-white mb-2 group-hover:translate-x-2 transition-transform">Makeup</h2>
                    <p class="text-white/80 font-body-md">A curated selection of premium face, eye, and lip products.</p>
                </div>
            </div>
        </a>

        @foreach($categories as $category)
        <a href="{{ route('storefront.category', $category->slug) }}" class="group relative bg-white rounded-lg overflow-hidden transition-all duration-500 hover:shadow-[0_30px_60px_rgba(0,0,0,0.08)] border border-white/40 block">
            <div class="aspect-square relative overflow-hidden">
                <!-- Using cached local images -->
                <img class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" src="{{ $category->image_url }}"/>
                <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
                <div class="absolute bottom-6 left-6 right-6">
                    <h2 class="font-display-md text-3xl text-white mb-2 group-hover:translate-x-2 transition-transform">{{ $category->name }}</h2>
                    <p class="text-white/80 font-body-md">{{ Str::limit($category->description, 60) }}</p>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</section>
@endsection
