@extends('layouts.storefront')

@section('content')
<section class="max-w-container-max mx-auto px-margin-desktop py-section-gap min-h-[70vh]">
    <h1 class="font-display-lg text-4xl text-on-surface mb-12">Your Shopping Bag</h1>

    @if(session('success'))
        <div class="bg-tertiary-container/30 text-on-tertiary-container px-6 py-4 rounded-lg mb-8 font-body-md">
            {{ session('success') }}
        </div>
    @endif

    @if($cart->items->isEmpty())
        <div class="text-center py-20 bg-surface-container-low rounded-2xl border border-outline-variant/30">
            <span class="material-symbols-outlined text-6xl text-outline-variant mb-4" style="font-variation-settings: 'FILL' 0;">shopping_bag</span>
            <h2 class="font-display-md text-2xl text-on-surface mb-4">Your bag is empty</h2>
            <p class="text-on-surface-variant font-body-md mb-8">Discover our premium collections to fill your bag.</p>
            <a href="{{ route('storefront.index') }}" class="rose-gold-btn px-8 py-3 rounded-full font-label-caps text-label-caps text-on-primary-container inline-block uppercase tracking-wider">Continue Shopping</a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <div class="lg:col-span-2 space-y-6">
                @php $total = 0; @endphp
                @foreach($cart->items as $item)
                    @php $total += $item->product->price * $item->quantity; @endphp
                    <div class="flex gap-6 p-6 bg-white rounded-2xl border border-outline-variant/30 items-center">
                        <div class="w-24 h-24 bg-surface-container-low rounded-lg overflow-hidden flex-shrink-0">
                            <!-- Image loaded from local cache via accessor -->
                            <img src="{{ $item->product->image_url }}" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-grow">
                            <h3 class="font-display-md text-xl text-on-surface">{{ $item->product->name }}</h3>
                            <p class="text-on-surface-variant font-body-md text-sm">{{ $item->product->category->name ?? 'Luxury Skincare' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-data-tabular text-on-surface text-lg mb-2">${{ number_format($item->product->price, 2) }} <span class="text-sm text-outline">x {{ $item->quantity }}</span></p>
                            <form action="{{ route('cart.remove') }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                                <button type="submit" class="text-error hover:text-error-container text-sm font-label-caps uppercase tracking-wider">Remove</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="bg-surface-container-low rounded-2xl p-8 border border-outline-variant/30 h-fit">
                <h3 class="font-display-md text-2xl text-on-surface mb-6">Order Summary</h3>
                <div class="space-y-4 mb-6">
                    <div class="flex justify-between text-on-surface-variant font-body-md">
                        <span>Subtotal</span>
                        <span class="font-data-tabular">${{ number_format($total, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-on-surface-variant font-body-md">
                        <span>Complimentary Shipping</span>
                        <span class="font-data-tabular">$0.00</span>
                    </div>
                    <div class="h-px bg-outline-variant/50 w-full my-4"></div>
                    <div class="flex justify-between text-on-surface font-display-md text-xl">
                        <span>Total</span>
                        <span class="font-data-tabular">${{ number_format($total, 2) }}</span>
                    </div>
                </div>
                <a href="{{ route('checkout.index') }}" class="w-full rose-gold-btn py-4 rounded-full font-label-caps text-label-caps tracking-widest text-on-primary-container uppercase inline-block text-center mt-6">
                    Secure Checkout
                </a>
            </div>
        </div>
    @endif
</section>
@endsection
