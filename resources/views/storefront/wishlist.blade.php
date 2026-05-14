@extends('layouts.storefront')

@section('content')
<section class="max-w-container-max mx-auto px-margin-desktop py-section-gap min-h-[70vh]">
    <h1 class="font-display-lg text-4xl text-on-surface mb-12">Your Wishlist</h1>

    @if(session('success'))
        <div class="bg-tertiary-container/30 text-on-tertiary-container px-6 py-4 rounded-lg mb-8 font-body-md">
            {{ session('success') }}
        </div>
    @endif

    @if($wishlist->items->isEmpty())
        <div class="text-center py-20 bg-surface-container-low rounded-2xl border border-outline-variant/30">
            <span class="material-symbols-outlined text-6xl text-outline-variant mb-4" style="font-variation-settings: 'FILL' 0;">favorite</span>
            <h2 class="font-display-md text-2xl text-on-surface mb-4">Your wishlist is empty</h2>
            <p class="text-on-surface-variant font-body-md mb-8">Save items you love to revisit them later.</p>
            <a href="{{ route('storefront.index') }}" class="rose-gold-btn px-8 py-3 rounded-full font-label-caps text-label-caps text-on-primary-container inline-block uppercase tracking-wider">Discover Products</a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
            @foreach($wishlist->items as $item)
            <div class="group relative bg-white rounded-lg p-4 transition-all duration-500 hover:shadow-[0_30px_60px_rgba(0,0,0,0.04)] border border-outline-variant/30">
                <div class="relative aspect-[4/5] overflow-hidden rounded-lg mb-6 bg-surface-container-low">
                    <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" src="{{ $item->product->image_url }}"/>
                    
                    <div class="absolute top-3 right-3 z-10">
                        <form action="{{ route('wishlist.toggle') }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                            <button type="submit" class="w-10 h-10 rounded-full bg-white/80 backdrop-blur flex items-center justify-center text-primary shadow-sm hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">favorite</span>
                            </button>
                        </form>
                    </div>

                    <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                        <form action="{{ route('cart.add') }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                            <button type="submit" class="rose-gold-btn w-full py-3 rounded-full font-label-caps text-label-caps text-on-primary-container">Move to Cart</button>
                        </form>
                    </div>
                </div>
                <h3 class="font-display-md text-[20px] mb-1">{{ $item->product->name }}</h3>
                <p class="text-on-surface-variant font-data-tabular">${{ number_format($item->product->price, 2) }}</p>
            </div>
            @endforeach
        </div>
    @endif
</section>
@endsection
