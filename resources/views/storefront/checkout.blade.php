@extends('layouts.storefront')

@section('content')
<section class="max-w-container-max mx-auto px-margin-desktop py-section-gap min-h-[70vh]">
    <h1 class="font-display-lg text-4xl text-on-surface mb-12">Secure Checkout</h1>

    @if(session('error'))
        <div class="bg-error-container text-on-error-container px-6 py-4 rounded-lg mb-8 font-body-md">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <div class="lg:col-span-2 space-y-8">
            <form id="checkout-form" action="{{ route('checkout.process') }}" method="POST" class="space-y-8">
                @csrf
                
                <!-- Shipping Address -->
                <div class="bg-surface-container-low rounded-2xl p-8 border border-outline-variant/30">
                    <h2 class="font-display-md text-2xl text-on-surface mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">local_shipping</span>
                        Shipping Information
                    </h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block font-label-caps text-label-caps text-on-surface mb-2">Full Name</label>
                            <input type="text" value="{{ auth()->user()->name }}" class="w-full bg-white border border-outline-variant rounded-lg px-4 py-3 focus:outline-none focus:border-primary text-on-surface font-body-md" readonly>
                        </div>
                        <div>
                            <label class="block font-label-caps text-label-caps text-on-surface mb-2">Shipping Address</label>
                            <textarea name="shipping_address" rows="3" class="w-full bg-white border border-outline-variant rounded-lg px-4 py-3 focus:outline-none focus:border-primary text-on-surface font-body-md" required placeholder="Enter your full shipping address"></textarea>
                            @error('shipping_address')
                                <span class="text-error text-sm mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-surface-container-low rounded-2xl p-8 border border-outline-variant/30">
                    <h2 class="font-display-md text-2xl text-on-surface mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">payment</span>
                        Payment Method
                    </h2>
                    
                    <div class="space-y-4">
                        <label class="flex items-center gap-4 p-4 border border-primary bg-primary-container/10 rounded-lg cursor-pointer transition-colors">
                            <input type="radio" name="payment_method" value="cash_on_delivery" class="text-primary focus:ring-primary w-5 h-5" checked>
                            <span class="font-body-md text-on-surface font-medium flex-grow">Cash on Delivery</span>
                            <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 0;">payments</span>
                        </label>
                        
                        <label class="flex items-center gap-4 p-4 border border-outline-variant rounded-lg cursor-pointer hover:border-primary transition-colors">
                            <input type="radio" name="payment_method" value="credit_card" class="text-primary focus:ring-primary w-5 h-5">
                            <span class="font-body-md text-on-surface font-medium flex-grow">Credit Card</span>
                            <span class="material-symbols-outlined text-on-surface-variant" style="font-variation-settings: 'FILL' 0;">credit_card</span>
                        </label>

                        <label class="flex items-center gap-4 p-4 border border-outline-variant rounded-lg cursor-pointer hover:border-primary transition-colors">
                            <input type="radio" name="payment_method" value="paypal" class="text-primary focus:ring-primary w-5 h-5">
                            <span class="font-body-md text-on-surface font-medium flex-grow">PayPal</span>
                            <span class="material-symbols-outlined text-on-surface-variant" style="font-variation-settings: 'FILL' 0;">account_balance_wallet</span>
                        </label>
                    </div>
                    @error('payment_method')
                        <span class="text-error text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </form>
        </div>
        
        <div class="bg-surface-container-low rounded-2xl p-8 border border-outline-variant/30 h-fit">
            <h3 class="font-display-md text-2xl text-on-surface mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">receipt_long</span>
                Order Summary
            </h3>
            
            <div class="space-y-4 mb-6 max-h-64 overflow-y-auto pr-2">
                @php $total = 0; @endphp
                @foreach($cart->items as $item)
                    @php $total += $item->product->price * $item->quantity; @endphp
                    <div class="flex justify-between items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white rounded flex-shrink-0 overflow-hidden border border-outline-variant/30">
                                <img src="{{ $item->product->image_url }}" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <p class="text-on-surface font-body-md text-sm truncate max-w-[150px]">{{ $item->product->name }}</p>
                                <p class="text-on-surface-variant text-xs">Qty: {{ $item->quantity }}</p>
                            </div>
                        </div>
                        <span class="font-data-tabular text-sm">${{ number_format($item->product->price * $item->quantity, 2) }}</span>
                    </div>
                @endforeach
            </div>

            <div class="space-y-4 mb-6 border-t border-outline-variant/30 pt-4">
                <div class="flex justify-between text-on-surface-variant font-body-md">
                    <span>Subtotal</span>
                    <span class="font-data-tabular">${{ number_format($total, 2) }}</span>
                </div>
                <div class="flex justify-between text-on-surface-variant font-body-md">
                    <span>Shipping</span>
                    <span class="font-data-tabular">$0.00</span>
                </div>
                <div class="flex justify-between text-on-surface-variant font-body-md">
                    <span>Taxes</span>
                    <span class="font-data-tabular">$0.00</span>
                </div>
                <div class="h-px bg-outline-variant/50 w-full my-4"></div>
                <div class="flex justify-between text-on-surface font-display-md text-xl">
                    <span>Total</span>
                    <span class="font-data-tabular">${{ number_format($total, 2) }}</span>
                </div>
            </div>
            
            <button type="submit" form="checkout-form" class="w-full rose-gold-btn py-4 rounded-full font-label-caps text-label-caps tracking-widest text-on-primary-container uppercase transition-all flex justify-center items-center gap-2 group">
                Place Order
                <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform" style="font-size: 18px;">arrow_forward</span>
            </button>
        </div>
    </div>
</section>

<!-- Script to handle custom radio button styling -->
<script>
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Reset all
            document.querySelectorAll('input[name="payment_method"]').forEach(r => {
                const label = r.closest('label');
                label.classList.remove('border-primary', 'bg-primary-container/10');
                label.classList.add('border-outline-variant');
                label.querySelector('span.material-symbols-outlined').classList.remove('text-primary');
                label.querySelector('span.material-symbols-outlined').classList.add('text-on-surface-variant');
            });
            // Set active
            const activeLabel = this.closest('label');
            activeLabel.classList.add('border-primary', 'bg-primary-container/10');
            activeLabel.classList.remove('border-outline-variant');
            activeLabel.querySelector('span.material-symbols-outlined').classList.add('text-primary');
            activeLabel.querySelector('span.material-symbols-outlined').classList.remove('text-on-surface-variant');
        });
    });
</script>
@endsection
