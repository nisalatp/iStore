<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>iStore Admin - Business Intelligence Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet"/>
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
        .glass {
            background: rgba(255, 248, 247, 0.4);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
        }
        .bento-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .bento-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(126, 83, 82, 0.08);
            border-color: rgba(255, 255, 255, 0.8);
        }
        .ios-toggle:checked + .toggle-bg {
            background-color: #dda7a5;
        }
        .ios-toggle:checked + .toggle-bg .toggle-dot {
            transform: translateX(1.5rem);
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
        .nav-item {
            position: relative;
            overflow: hidden;
        }
        .nav-item::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(90deg, rgba(221,167,165,0.15) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        .nav-item:hover::before {
            opacity: 1;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-background text-on-background font-body-md">
    <!-- Sidebar Navigation Shell -->
    <nav class="fixed left-0 top-0 h-full flex flex-col z-40 bg-surface-container/60 backdrop-blur-2xl border-r border-white/40 {{ request()->routeIs('admin.mcp_agent') ? 'w-20' : 'w-72' }} transition-all duration-300 shadow-[30px_0_60px_rgba(0,0,0,0.04)]">
        <div class="p-8 {{ request()->routeIs('admin.mcp_agent') ? 'px-4' : '' }} flex flex-col items-center">
            <h1 class="font-display-md {{ request()->routeIs('admin.mcp_agent') ? 'text-2xl text-center' : 'text-display-md' }} font-bold text-primary tracking-tighter">{{ request()->routeIs('admin.mcp_agent') ? 'iA' : 'iStore Admin' }}</h1>
            <p class="font-label-caps text-label-caps text-on-surface-variant opacity-70 mt-1 {{ request()->routeIs('admin.mcp_agent') ? 'hidden' : '' }}">Enterprise Suite</p>
        </div>
        <div class="flex-1 mt-4">
            <a href="{{ route('admin.dashboard') }}" class="nav-item flex items-center gap-3 {{ request()->routeIs('admin.dashboard') ? 'bg-primary-container/30 text-on-primary-container border-l-4 border-primary' : 'text-on-surface-variant hover:bg-white/20' }} {{ request()->routeIs('admin.mcp_agent') ? 'justify-center px-0' : 'px-6' }} py-4 transition-colors duration-200" title="Overview">
                <span class="material-symbols-outlined" data-icon="dashboard">dashboard</span>
                <span class="font-label-caps text-label-caps uppercase tracking-widest {{ request()->routeIs('admin.mcp_agent') ? 'hidden' : '' }}">Overview</span>
            </a>
            <a href="{{ route('admin.report_builder') }}" class="nav-item flex items-center gap-3 {{ request()->routeIs('admin.report_builder') ? 'bg-primary-container/30 text-on-primary-container border-l-4 border-primary' : 'text-on-surface-variant hover:bg-white/20' }} {{ request()->routeIs('admin.mcp_agent') ? 'justify-center px-0' : 'px-6' }} py-4 transition-colors duration-200" title="Report Builder">
                <span class="material-symbols-outlined" data-icon="analytics" style="font-variation-settings: 'FILL' 1;">analytics</span>
                <span class="font-label-caps text-label-caps uppercase tracking-widest {{ request()->routeIs('admin.mcp_agent') ? 'hidden' : '' }}">Report Builder</span>
            </a>
            <a href="{{ route('admin.reports.index') }}" class="nav-item flex items-center gap-3 {{ request()->routeIs('admin.reports.index') ? 'bg-primary-container/30 text-on-primary-container border-l-4 border-primary' : 'text-on-surface-variant hover:bg-white/20' }} {{ request()->routeIs('admin.mcp_agent') ? 'justify-center px-0' : 'px-6' }} py-4 transition-colors duration-200" title="Saved Reports">
                <span class="material-symbols-outlined" data-icon="save">save</span>
                <span class="font-label-caps text-label-caps uppercase tracking-widest {{ request()->routeIs('admin.mcp_agent') ? 'hidden' : '' }}">Saved Reports</span>
            </a>
            <a href="{{ route('admin.security') }}" class="nav-item flex items-center gap-3 {{ request()->routeIs('admin.security') ? 'bg-primary-container/30 text-on-primary-container border-l-4 border-primary' : 'text-on-surface-variant hover:bg-white/20' }} {{ request()->routeIs('admin.mcp_agent') ? 'justify-center px-0' : 'px-6' }} py-4 transition-colors duration-200" title="Governance">
                <span class="material-symbols-outlined" data-icon="admin_panel_settings">admin_panel_settings</span>
                <span class="font-label-caps text-label-caps uppercase tracking-widest {{ request()->routeIs('admin.mcp_agent') ? 'hidden' : '' }}">Governance</span>
            </a>
            <a href="{{ route('admin.mcp_agent') }}" class="nav-item flex items-center gap-3 {{ request()->routeIs('admin.mcp_agent') ? 'bg-primary-container/30 text-on-primary-container border-l-4 border-primary' : 'text-on-surface-variant hover:bg-white/20' }} {{ request()->routeIs('admin.mcp_agent') ? 'justify-center px-0' : 'px-6' }} py-4 transition-colors duration-200" title="AI Agent">
                <span class="material-symbols-outlined" data-icon="smart_toy">smart_toy</span>
                <span class="font-label-caps text-label-caps uppercase tracking-widest {{ request()->routeIs('admin.mcp_agent') ? 'hidden' : '' }}">AI Agent</span>
            </a>
            <a href="{{ route('admin.virtual_attributes.list') }}" class="nav-item flex items-center gap-3 {{ request()->routeIs('admin.virtual_attributes*') ? 'bg-primary-container/30 text-on-primary-container border-l-4 border-primary' : 'text-on-surface-variant hover:bg-white/20' }} {{ request()->routeIs('admin.mcp_agent') ? 'justify-center px-0' : 'px-6' }} py-4 transition-colors duration-200" title="Virtual Attrs">
                <span class="material-symbols-outlined" data-icon="auto_awesome">auto_awesome</span>
                <span class="font-label-caps text-label-caps uppercase tracking-widest {{ request()->routeIs('admin.mcp_agent') ? 'hidden' : '' }}">Virtual Attrs</span>
            </a>
            <a href="{{ route('storefront.index') }}" class="nav-item flex items-center gap-3 text-on-surface-variant {{ request()->routeIs('admin.mcp_agent') ? 'justify-center px-0' : 'px-6' }} py-4 hover:bg-white/20 transition-colors duration-200" title="Storefront">
                <span class="material-symbols-outlined" data-icon="storefront">storefront</span>
                <span class="font-label-caps text-label-caps uppercase tracking-widest {{ request()->routeIs('admin.mcp_agent') ? 'hidden' : '' }}">Storefront</span>
            </a>
        </div>
        <div class="p-6 {{ request()->routeIs('admin.mcp_agent') ? 'px-2' : '' }} border-t border-white/20 flex flex-col items-center">
            <div class="flex items-center gap-3 mb-6 {{ request()->routeIs('admin.mcp_agent') ? 'justify-center' : '' }}">
                <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-lg shrink-0">
                    {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>
                <div class="{{ request()->routeIs('admin.mcp_agent') ? 'hidden' : '' }}">
                    <p class="font-label-caps text-label-caps text-primary">{{ auth()->user()->name ?? 'Admin User' }}</p>
                    <p class="text-[11px] text-on-surface-variant">{{ auth()->user()->role->name ?? 'Administrator' }}</p>
                </div>
            </div>
            <a href="{{ route('admin.report_builder') }}" class="w-full block text-center py-3 bg-primary text-white rounded-lg font-label-caps text-label-caps hover:scale-[1.02] transition-transform shadow-lg shadow-primary/10" title="New Report">
                {{ request()->routeIs('admin.mcp_agent') ? '+' : 'New Report' }}
            </a>
        </div>
    </nav>

    <div class="{{ request()->routeIs('admin.mcp_agent') ? 'ml-20' : 'ml-72' }} flex flex-col min-h-screen transition-all duration-300">
        <!-- Global TopAppBar Shell -->
        <header class="flex justify-between items-center px-10 z-30 h-20 shrink-0 bg-white/30 backdrop-blur-lg border-b border-white/40 shadow-sm sticky top-0">
            <h2 class="font-headline-lg text-headline-lg font-semibold text-primary">
                @yield('header_title', 'iStore Admin')
            </h2>
            <div class="flex items-center gap-8">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
                    <input class="pl-10 pr-4 py-2 bg-white/50 border border-outline-variant rounded-full text-body-md focus:ring-2 focus:ring-primary/20 outline-none w-64 transition-all hover:bg-white focus:bg-white" placeholder="Search..." type="text"/>
                </div>
                <div class="flex items-center gap-4">
                    <button class="w-10 h-10 rounded-full flex items-center justify-center text-secondary hover:bg-primary/10 transition-colors">
                        <span class="material-symbols-outlined">notifications</span>
                    </button>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="w-10 h-10 rounded-full flex items-center justify-center text-error hover:bg-error/10 transition-colors" title="Log Out">
                            <span class="material-symbols-outlined">logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 relative">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
