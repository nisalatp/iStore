@extends('layouts.storefront')

@section('content')
<section class="min-h-[80vh] flex items-center justify-center py-section-gap px-margin-desktop bg-surface">
    <div class="max-w-md w-full glass-panel ambient-glow rounded-2xl p-8 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary-container/20 to-transparent pointer-events-none"></div>
        <div class="relative z-10">
            <h1 class="font-display-lg text-3xl text-on-surface mb-2 text-center">Join iStore</h1>
            <p class="text-on-surface-variant font-body-md text-center mb-8">Unlock exclusive collections and curated luxury.</p>

            <form method="POST" action="{{ route('register') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="name" class="block font-label-caps text-label-caps text-on-surface mb-2">Full Name</label>
                    <input type="text" name="name" id="name" required class="w-full bg-white/50 border border-outline-variant rounded-lg px-4 py-3 font-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    @error('name') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="email" class="block font-label-caps text-label-caps text-on-surface mb-2">Email Address</label>
                    <input type="email" name="email" id="email" required class="w-full bg-white/50 border border-outline-variant rounded-lg px-4 py-3 font-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    @error('email') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="password" class="block font-label-caps text-label-caps text-on-surface mb-2">Password</label>
                    <input type="password" name="password" id="password" required class="w-full bg-white/50 border border-outline-variant rounded-lg px-4 py-3 font-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    @error('password') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block font-label-caps text-label-caps text-on-surface mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required class="w-full bg-white/50 border border-outline-variant rounded-lg px-4 py-3 font-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                </div>
                <button type="submit" class="w-full rose-gold-btn py-4 rounded-full font-label-caps text-label-caps tracking-widest text-on-primary-container uppercase mt-8">
                    Create Account
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="font-body-md text-on-surface-variant hover:text-primary transition-colors">Already have an account? Log in</a>
            </div>
        </div>
    </div>
</section>
@endsection
