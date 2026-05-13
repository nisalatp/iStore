<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>iStore - Luxury Cosmetics</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Alpine JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-primary-container": "#633b3a",
                        "on-secondary-fixed-variant": "#3d4759",
                        "on-error": "#ffffff",
                        "surface-tint": "#7e5352",
                        "surface-container-lowest": "#ffffff",
                        "tertiary-fixed": "#c4ebd7",
                        "tertiary-fixed-dim": "#a9cfbc",
                        "error": "#ba1a1a",
                        "secondary-fixed": "#d9e3f9",
                        "on-primary-fixed-variant": "#643c3b",
                        "on-secondary-container": "#596376",
                        "surface-container-low": "#fcf1f0",
                        "surface": "#fff8f7",
                        "on-tertiary": "#ffffff",
                        "primary-fixed": "#ffdad8",
                        "surface-container": "#f6eceb",
                        "on-tertiary-fixed-variant": "#2b4e3f",
                        "tertiary": "#426656",
                        "outline": "#837373",
                        "on-primary-fixed": "#311212",
                        "primary": "#7e5352",
                        "on-secondary": "#ffffff",
                        "on-background": "#1f1b1a",
                        "error-container": "#ffdad6",
                        "surface-bright": "#fff8f7",
                        "outline-variant": "#d5c2c1",
                        "secondary-container": "#d6e0f6",
                        "primary-container": "#dda7a5",
                        "surface-container-high": "#f0e6e5",
                        "background": "#fff8f7",
                        "on-error-container": "#93000a",
                        "secondary": "#555f71",
                        "inverse-on-surface": "#f9eeed",
                        "secondary-fixed-dim": "#bdc7dc",
                        "surface-container-highest": "#eae0df",
                        "inverse-primary": "#f1b9b7",
                        "surface-variant": "#eae0df",
                        "on-tertiary-fixed": "#002115",
                        "surface-dim": "#e1d8d7",
                        "inverse-surface": "#342f2f",
                        "on-surface": "#1f1b1a",
                        "primary-fixed-dim": "#f1b9b7",
                        "tertiary-container": "#97bdaa",
                        "on-primary": "#ffffff",
                        "on-surface-variant": "#514443",
                        "on-tertiary-container": "#2a4d3e",
                        "on-secondary-fixed": "#121c2c"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "section-gap": "64px",
                        "margin-mobile": "16px",
                        "unit": "8px",
                        "gutter": "24px",
                        "margin-desktop": "40px",
                        "container-max": "1440px"
                    },
                    "fontFamily": {
                        "display-md": ["Playfair Display"],
                        "body-lg": ["Outfit"],
                        "data-tabular": ["Outfit"],
                        "label-caps": ["Outfit"],
                        "body-md": ["Outfit"],
                        "display-lg": ["Playfair Display"],
                        "headline-lg-mobile": ["Playfair Display"],
                        "headline-lg": ["Playfair Display"]
                    },
                    "fontSize": {
                        "display-md": ["36px", {"lineHeight": "1.2", "fontWeight": "600"}],
                        "body-lg": ["18px", {"lineHeight": "1.6", "fontWeight": "400"}],
                        "data-tabular": ["14px", {"lineHeight": "1.4", "letterSpacing": "0.01em", "fontWeight": "500"}],
                        "label-caps": ["12px", {"lineHeight": "1.2", "letterSpacing": "0.1em", "fontWeight": "600"}],
                        "body-md": ["16px", {"lineHeight": "1.6", "fontWeight": "400"}],
                        "display-lg": ["48px", {"lineHeight": "1.2", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "headline-lg-mobile": ["24px", {"lineHeight": "1.3", "fontWeight": "600"}],
                        "headline-lg": ["28px", {"lineHeight": "1.3", "fontWeight": "600"}]
                    }
                }
            }
        }
    </script>
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .ambient-glow {
            box-shadow: 0 30px 60px rgba(31, 27, 26, 0.05);
        }
        .rose-gold-btn {
            background: linear-gradient(135deg, #dda7a5 0%, #f1b9b7 100%);
            transition: all 0.3s ease;
        }
        .rose-gold-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 20px rgba(221, 167, 165, 0.3);
        }
    </style>
</head>
<body class="bg-background text-on-surface font-body-md">
    <!-- TopNavBar -->
    <nav class="fixed top-0 left-0 w-full z-50 bg-surface/80 backdrop-blur-xl border-b border-white/40 shadow-[0_20px_50px_rgba(31,27,26,0.05)]">
        <div class="flex justify-between items-center w-full px-margin-desktop py-4 max-w-container-max mx-auto">
            <!-- Navigation Links -->
            <div class="hidden md:flex gap-8 items-center">
                <a class="font-medium text-on-surface-variant hover:scale-105 transition-transform duration-300 hover:text-primary" href="#">Collections</a>
                <a class="font-medium text-on-surface-variant hover:scale-105 transition-transform duration-300 hover:text-primary" href="#">Skincare</a>
                <a class="font-medium text-on-surface-variant hover:scale-105 transition-transform duration-300 hover:text-primary" href="#">Makeup</a>
            </div>
            <!-- Centered Logo -->
            <div class="absolute left-1/2 -translate-x-1/2">
                <a href="{{ route('storefront.index') }}" class="font-display-lg text-primary text-3xl font-bold tracking-tight">iStore</a>
            </div>
            <!-- Trailing Actions -->
            <div class="flex items-center gap-6">
                <a href="{{ route('admin.dashboard') }}" class="text-primary hover:scale-105 transition-transform duration-300 flex items-center gap-1">
                    <span class="material-symbols-outlined">shield_person</span>
                    <span class="font-label-caps text-label-caps hidden lg:inline">Admin</span>
                </a>
                <button class="text-primary hover:scale-105 transition-transform duration-300 flex items-center gap-1">
                    <span class="material-symbols-outlined">person</span>
                    <span class="font-label-caps text-label-caps hidden lg:inline">Login</span>
                </button>
                <button class="text-primary hover:scale-105 transition-transform duration-300 flex items-center gap-1">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    <span class="font-label-caps text-label-caps hidden lg:inline">Cart</span>
                </button>
            </div>
        </div>
    </nav>
    <main class="pt-20">
        @yield('content')
    </main>
    <!-- Footer -->
    <footer class="bg-surface-container-lowest border-t border-outline-variant/30">
        <div class="w-full py-section-gap px-margin-desktop flex flex-col md:flex-row justify-between items-start max-w-container-max mx-auto gap-12">
            <div class="max-w-xs">
                <div class="font-display-md text-display-md text-primary mb-6">iStore</div>
                <p class="text-on-surface-variant font-body-md mb-8">Elevating everyday beauty through performance software and luxury formulations.</p>
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full border border-outline-variant flex items-center justify-center text-on-surface-variant hover:border-primary hover:text-primary cursor-pointer transition-colors">
                        <span class="material-symbols-outlined text-sm">language</span>
                    </div>
                    <div class="w-10 h-10 rounded-full border border-outline-variant flex items-center justify-center text-on-surface-variant hover:border-primary hover:text-primary cursor-pointer transition-colors">
                        <span class="material-symbols-outlined text-sm">share</span>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-12 w-full md:w-auto">
                <div>
                    <h4 class="font-label-caps text-label-caps text-on-surface mb-6">Shop</h4>
                    <ul class="space-y-4 text-on-surface-variant font-body-md">
                        <li><a class="hover:text-primary transition-colors" href="#">Best Sellers</a></li>
                        <li><a class="hover:text-primary transition-colors" href="#">New Arrivals</a></li>
                        <li><a class="hover:text-primary transition-colors" href="#">Skincare</a></li>
                        <li><a class="hover:text-primary transition-colors" href="#">Makeup</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-label-caps text-label-caps text-on-surface mb-6">Enterprise</h4>
                    <ul class="space-y-4 text-on-surface-variant font-body-md">
                        <li><a class="hover:text-primary transition-colors" href="#">Enterprise API</a></li>
                        <li><a class="hover:text-primary transition-colors" href="#">Retail Partnerships</a></li>
                        <li><a class="hover:text-primary transition-colors" href="#">Luxury Support</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-label-caps text-label-caps text-on-surface mb-6">Legal</h4>
                    <ul class="space-y-4 text-on-surface-variant font-body-md">
                        <li><a class="hover:text-primary transition-colors" href="#">Privacy Policy</a></li>
                        <li><a class="hover:text-primary transition-colors" href="#">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="w-full py-8 border-t border-outline-variant/10 px-margin-desktop max-w-container-max mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            <span class="text-on-surface-variant font-label-caps text-[10px] tracking-widest uppercase">© 2024 iStore Enterprise. Luxury performance software.</span>
            <div class="flex gap-8">
                <span class="text-on-surface-variant font-label-caps text-[10px] tracking-widest uppercase cursor-pointer hover:text-primary">English (US)</span>
                <span class="text-on-surface-variant font-label-caps text-[10px] tracking-widest uppercase cursor-pointer hover:text-primary">USD ($)</span>
            </div>
        </div>
    </footer>
</body>
</html>
