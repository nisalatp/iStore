<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    // Public pages
    public function collections()
    {
        $categories = \App\Models\Category::all();
        return view('storefront.collections', compact('categories'));
    }

    public function category($slug)
    {
        if ($slug === 'makeup') {
            // Makeup includes Face, Eyes, Lips
            $category = (object) [
                'name' => 'Makeup',
                'description' => 'A curated selection of premium face, eye, and lip products.',
            ];
            $products = \App\Models\Product::whereHas('category', function($q) {
                $q->whereIn('slug', ['face', 'eyes', 'lips']);
            })->get();
        } else {
            $category = \App\Models\Category::where('slug', $slug)->firstOrFail();
            $products = $category->products;
        }

        return view('storefront.category', compact('category', 'products'));
    }

    // Cart actions
    public function viewCart()
    {
        $cart = \Illuminate\Support\Facades\Auth::user()->cart()->with('items.product')->firstOrCreate([]);
        return view('storefront.cart', compact('cart'));
    }

    public function addToCart(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $cart = \Illuminate\Support\Facades\Auth::user()->cart()->firstOrCreate([]);
        
        $item = $cart->items()->where('product_id', $request->product_id)->first();
        if ($item) {
            $item->increment('quantity');
        } else {
            $cart->items()->create(['product_id' => $request->product_id, 'quantity' => 1]);
        }

        return redirect()->back()->with('success', 'Added to cart.');
    }

    public function removeFromCart(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $cart = \Illuminate\Support\Facades\Auth::user()->cart()->firstOrCreate([]);
        $cart->items()->where('product_id', $request->product_id)->delete();

        return redirect()->back()->with('success', 'Removed from cart.');
    }

    // Wishlist actions
    public function viewWishlist()
    {
        $wishlist = \Illuminate\Support\Facades\Auth::user()->wishlist()->with('items.product')->firstOrCreate([]);
        return view('storefront.wishlist', compact('wishlist'));
    }

    public function toggleWishlist(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $wishlist = \Illuminate\Support\Facades\Auth::user()->wishlist()->firstOrCreate([]);
        
        $item = $wishlist->items()->where('product_id', $request->product_id)->first();
        if ($item) {
            $item->delete();
        } else {
            $wishlist->items()->create(['product_id' => $request->product_id]);
        }

        return redirect()->back()->with('success', 'Wishlist updated.');
    }

    // Checkout actions
    public function checkout()
    {
        $cart = \Illuminate\Support\Facades\Auth::user()->cart()->with('items.product')->firstOrCreate([]);
        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }
        return view('storefront.checkout', compact('cart'));
    }

    public function processCheckout(Request $request)
    {
        $request->validate([
            'shipping_address' => 'required|string|max:255',
            'payment_method' => 'required|string|in:cash_on_delivery,credit_card,paypal',
        ]);

        $user = \Illuminate\Support\Facades\Auth::user();
        $cart = $user->cart()->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Calculate total
        $total = $cart->items->sum(function($item) {
            return $item->product->price * $item->quantity;
        });

        // Create Order
        $order = \App\Models\Order::create([
            'user_id' => $user->id,
            'total_amount' => $total,
            'status' => 'pending',
            'shipping_address' => $request->shipping_address,
        ]);

        // Create Order Items
        foreach ($cart->items as $item) {
            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->product->price,
            ]);
        }

        // Create Payment
        \App\Models\Payment::create([
            'order_id' => $order->id,
            'amount' => $total,
            'status' => 'pending',
            'method' => $request->payment_method,
        ]);

        // Clear Cart
        $cart->items()->delete();

        return redirect()->route('storefront.index')->with('success', 'Order placed successfully! Thank you for shopping with iStore.');
    }
}
