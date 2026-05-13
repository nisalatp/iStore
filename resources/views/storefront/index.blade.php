@extends('layouts.storefront')

@section('content')
<!-- Hero Section -->
<section class="relative w-full h-[80vh] overflow-hidden px-margin-desktop py-8">
    <div class="w-full h-full relative rounded-xl overflow-hidden group">
        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105" data-alt="A high-end editorial beauty photograph featuring a model with luminous skin and soft rose gold makeup tones. The setting is a bright, minimalist studio with warm sunlight filtering through, creating soft shadows. The aesthetic is clean and luxurious, perfectly matching a premium skincare brand's light-mode visual identity with airy space and elegant textures." style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuACFkSeP8dZTbHUP85GlzVe_tOmZTQkYMEhP2CIhFNv1sy6g5lWvrzypgZxenA8suqN4NTRkJcyOCbqLs3Irngfy19tVANvHCHv_St2r-_gs3J4W3smYXM8MoTK9QMULtFwV00BkLSU3n9OVBz4mO89ghGAG-jEDG50gBLKtsOX7L4bc9jJzDC7s507c1AlCzxYRVivPOQDOmP7t-PwVChy2xxBqs-aBePaRhOzidW3cLGyS8hSkOgp08ozPXBxN-pjQ-7F7bG1OIM')">
        </div>
        <div class="absolute inset-0 bg-gradient-to-r from-background/40 to-transparent flex items-center px-16">
            <div class="max-w-xl">
                <span class="font-label-caps text-label-caps text-primary tracking-[0.2em] mb-4 block">LIMITED EDITION</span>
                <h1 class="font-display-lg text-display-lg text-on-surface mb-8">Shop the Summer Collection</h1>
                <button class="rose-gold-btn px-10 py-4 rounded-full text-on-primary-container font-label-caps text-label-caps tracking-widest uppercase">
                    Discover Now
                </button>
            </div>
        </div>
    </div>
</section>
<!-- Product Grid Section -->
<section class="max-w-container-max mx-auto px-margin-desktop py-section-gap">
    <div class="flex flex-col md:flex-row justify-between items-end mb-12">
        <div>
            <h2 class="font-display-md text-display-md text-on-surface">Curated Essentials</h2>
            <p class="text-on-surface-variant mt-2 font-body-md">Refined performance for the modern aesthetic.</p>
        </div>
        <div class="flex gap-4 mt-6 md:mt-0">
            <button class="font-label-caps text-label-caps text-primary border-b border-primary pb-1">View All</button>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
        @foreach(\App\Models\Product::take(4)->get() as $product)
        <!-- Product Card -->
        <div class="group relative bg-white rounded-lg p-4 transition-all duration-500 hover:shadow-[0_30px_60px_rgba(0,0,0,0.04)] border border-white/40">
            <div class="relative aspect-[4/5] overflow-hidden rounded-lg mb-6">
                @php
                    $images = [
                        'https://lh3.googleusercontent.com/aida-public/AB6AXuDvYUKCkX78zegZiKYmhEJEUYY9cFmQbipSJrctd-kypYydGLKML2Yyti_qRYVWHeNsGGbwmOSQghstTslkPJABvaY0dCnLObMNOohxMhJNFjeA471-S92DQdDLQL6EnpGFba3IH_fiXdIMlaaphMK5PbiX9Oig488qfsUojWH0gmGKAGGgitziL7nzUUUrWK7WxjTEAUctboev5WDtTksNg6HBqfIyXlS0KcKJJAD9AuHLcR03m6Lg6WZs8leSQJuBaYbZFWFXUHE',
                        'https://lh3.googleusercontent.com/aida-public/AB6AXuAXzKlzX8oyRc0v_y-rB99Z5lqlCL99m7nDDg7xEBupWJMCLfhKP4SowacxeoBFS7UtTlJW8blPuCDVTFB46PYx8vlPD6sV2Hu9WN3NBVZNBPWpc46xLYBU63UtmFC3RU_tIoAwYZdG2NKfOsKy8D1pSKNtqQQxAKQQxLvNA86hCcbkXD6DZ9ZhAB2ZVeo0-1QTnSAcWfRNE66yuKA4a_a_fMvyq-itI51r8RGrKy-XJ4fOptwjUstcuAbJQ-vKeYJ-d533MRcQPJk',
                        'https://lh3.googleusercontent.com/aida-public/AB6AXuCDqYIFGTlmKgU9ox-CLFBDysKfA37oMpnqfIpZ83utckbKDX6syYItyv4bi9y3xdOhdX43jW4-EDe1_BMlIwFXz4rvliGc2hoj48n4lRbz0LcDqr3107uvO0tkN5ks-PfcjhpnOKfW5_a3FUewqckrIS5B1rhIcmXXih1KZ8qeE1c2HL89shRoJ_q7YeU0H9PoNIL_AdYaFwY-cll2MjPQQ4I2FDMT0MbA913xUd8ffrTrdkwL7SBpxkam-UEaA2Ux1ieh2Z4F3xo',
                        'https://lh3.googleusercontent.com/aida-public/AB6AXuCFe17VUi6ji8ybVFACkFps4_gV47eouQgSuy8_i2z9E0O1kQYnZbEZWseyB_TQLOnfB_binS_voL-nEloGs-C-ykeO7kkj_5z2gYYX9jwBGL9dAXFF7N30gUSH_IkgVCug3iBCWpFnzRHYZDtWuogPbVVWh-tTPGttTkDv0bNqmeM4Jd3pp2mWit8MBTHuu7jrBr_4_gSO3P4wDtEbWqVIPKyJD7yHV7euk0DggUAdkRuHLJsD1NhQC02HUbyll_euf20RUBEbA1w'
                    ];
                @endphp
                <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" src="{{ $images[$loop->index % 4] }}"/>
                @if($loop->first)
                <div class="absolute top-3 left-3">
                    <span class="bg-primary-container/80 backdrop-blur-md px-3 py-1 rounded-full font-label-caps text-[10px] text-on-primary-container">BEST SELLER</span>
                </div>
                @endif
                <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                    <button class="rose-gold-btn w-full py-3 rounded-full font-label-caps text-label-caps text-on-primary-container">Add to Cart</button>
                </div>
            </div>
            <h3 class="font-display-md text-[20px] mb-1">{{ $product->name }}</h3>
            <p class="text-on-surface-variant font-data-tabular">${{ number_format($product->price, 2) }}</p>
        </div>
        @endforeach
    </div>
</section>
<!-- Secondary Promotional Section -->
<section class="bg-surface-container-low py-section-gap">
    <div class="max-w-container-max mx-auto px-margin-desktop grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <div class="relative h-[600px] rounded-2xl overflow-hidden glass-panel ambient-glow p-4">
            <img class="w-full h-full object-cover rounded-xl" data-alt="An artistic arrangement of skincare ingredients like rose petals, clear water swirls, and glass droppers on a bright white background. The composition is airy and editorial, using wide negative space to convey a sense of purity and science-backed beauty. High-key lighting highlights the textures of the ingredients in a sophisticated, minimalist manner." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBVdlXxpn5FDBoVEjWHLOA_gaUDzH8LGEyxnMw1JgkiLHUjvCw-wPDkH2lTtf8U611vUs61VtLXQBWkQKxUNLG1p7kyxZdrCKBobNbX-RRt2ztG9IMWZVxH2tylFLP8Sy4XjlsBAqK_CxJJGeZZHMYLhwFp3-k2dw4_G0i5CE3xdMUMfdsd2VohXcjt2cCs1WCCiQfjCxgZ3I8uuwAxvH4x4dg1ZOSeUDJFnTHCLyAvkGYv8XC1Baw_31RU359YkuUPt9KPcBD-vHQ"/>
        </div>
        <div class="space-y-8">
            <h2 class="font-display-lg text-display-lg text-on-surface leading-tight">The Science of Subtle Luster</h2>
            <p class="text-on-surface-variant text-body-lg max-w-lg">
                Developed in our Parisian laboratories, each iStore formula combines rare botanical extracts with advanced peptide technology to deliver unparalleled results.
            </p>
            <ul class="space-y-4">
                <li class="flex items-center gap-3 text-on-surface">
                    <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <span class="font-body-md">Sustainably sourced ingredients</span>
                </li>
                <li class="flex items-center gap-3 text-on-surface">
                    <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <span class="font-body-md">Dermatologist tested &amp; approved</span>
                </li>
                <li class="flex items-center gap-3 text-on-surface">
                    <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <span class="font-body-md">Cruelty-free &amp; vegan certified</span>
                </li>
            </ul>
            <button class="font-label-caps text-label-caps text-primary flex items-center gap-2 group">
                Learn about our process
                <span class="material-symbols-outlined group-hover:translate-x-2 transition-transform">arrow_forward</span>
            </button>
        </div>
    </div>
</section>
@endsection
