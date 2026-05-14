@extends('layouts.storefront')

@section('content')
<section class="max-w-container-max mx-auto px-margin-desktop py-section-gap min-h-[70vh]">
    <div class="mb-12">
        <h1 class="font-display-lg text-display-lg text-on-surface mb-4">{{ $category->name }}</h1>
        <p class="text-on-surface-variant font-body-md max-w-2xl">{{ $category->description ?? 'Discover our premium ' . $category->name . ' products.' }}</p>
    </div>

    @if($products->isEmpty())
        <div class="text-center py-20 bg-surface-container-low rounded-2xl border border-outline-variant/30">
            <span class="material-symbols-outlined text-6xl text-outline-variant mb-4" style="font-variation-settings: 'FILL' 0;">inventory_2</span>
            <h2 class="font-display-md text-2xl text-on-surface mb-4">No products found</h2>
            <p class="text-on-surface-variant font-body-md mb-8">We are currently updating our {{ $category->name }} collection.</p>
            <a href="{{ route('storefront.collections') }}" class="rose-gold-btn px-8 py-3 rounded-full font-label-caps text-label-caps text-on-primary-container inline-block uppercase tracking-wider">View All Collections</a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
            @foreach($products as $product)
            <div class="group relative bg-white rounded-lg p-4 transition-all duration-500 hover:shadow-[0_30px_60px_rgba(0,0,0,0.04)] border border-white/40">
                <div class="relative aspect-[4/5] overflow-hidden rounded-lg mb-6">
                    <!-- Dynamic placeholder image using cached local images -->
                    <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" src="{{ $product->image_url }}"/>
                    
                    <div class="absolute top-3 right-3 z-10">
                        <form action="{{ route('wishlist.toggle') }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            @php
                                $inWishlist = auth()->check() && auth()->user()->wishlist && auth()->user()->wishlist->items->contains('product_id', $product->id);
                            @endphp
                            <button type="submit" class="w-10 h-10 rounded-full bg-white/80 backdrop-blur flex items-center justify-center text-primary shadow-sm hover:scale-110 transition-transform" title="{{ $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' }}">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' {{ $inWishlist ? '1' : '0' }};">favorite</span>
                            </button>
                        </form>
                    </div>
                    <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                        <form action="{{ route('cart.add') }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <button type="submit" class="rose-gold-btn w-full py-3 rounded-full font-label-caps text-label-caps text-on-primary-container">Add to Cart</button>
                        </form>
                    </div>
                </div>
                <h3 class="font-display-md text-[20px] mb-1">{{ $product->name }}</h3>
                <p class="text-on-surface-variant font-data-tabular">${{ number_format($product->price, 2) }}</p>
            </div>
            @endforeach
        </div>
    @endif
</section>
@endsection
