@extends('layouts.admin')

@section('content')
<!-- TopAppBar -->
<header class="flex justify-between items-center px-10 ml-72 w-[calc(100%-18rem)] z-30 h-20 sticky top-0 bg-white/30 dark:bg-black/30 backdrop-blur-lg border-b border-white/40 dark:border-white/10 shadow-sm">
    <div class="flex items-center gap-4">
        <h2 class="font-headline-lg text-headline-lg font-semibold text-primary">Luxury Analytics</h2>
    </div>
    <div class="flex items-center gap-8">
        <div class="relative group">
            <input class="bg-surface-container/50 border-none rounded-full px-6 py-2 w-64 focus:ring-1 focus:ring-primary/30 transition-all font-body-md text-body-md" placeholder="Search resources..." type="text"/>
            <span class="material-symbols-outlined absolute right-4 top-2 text-on-surface-variant opacity-50">search</span>
        </div>
        <div class="flex items-center gap-4">
            <button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-primary/5 transition-colors">
                <span class="material-symbols-outlined">notifications</span>
            </button>
            <button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-primary/5 transition-colors">
                <span class="material-symbols-outlined">account_circle</span>
            </button>
        </div>
    </div>
</header>
<!-- Main Content Canvas -->
<main x-data="modelsConfig()" class="ml-72 p-10 min-h-[calc(100vh-80px)] relative pb-24">
    <!-- Error/Success Notification -->
    <div x-show="message" x-transition class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 px-6 py-3 rounded-full shadow-lg text-white font-label-caps"
         :class="messageType === 'success' ? 'bg-green-500' : 'bg-red-500'">
        <span x-text="message"></span>
    </div>

    <!-- Header Section -->
    <section class="mb-12">
        <div class="flex justify-between items-end">
            <div>
                <span class="text-primary font-label-caps text-label-caps tracking-widest block mb-2">MODEL ARCHITECTURE</span>
                <h3 class="font-display-md text-display-md text-on-surface">Data Model Configuration</h3>
                <p class="text-secondary font-body-md mt-2 max-w-2xl">Toggle the accessibility of core system models for the Luxury Analytics engine. Restricted models are excluded from cross-department reporting feeds.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="font-label-caps text-on-secondary-fixed-variant bg-secondary-fixed px-3 py-1 rounded-full">Live Sync Active</span>
            </div>
        </div>
    </section>

    <!-- Bento Grid Layout for Models -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
        <template x-for="model in models" :key="model.class">
            <div class="bento-card glass bg-white/40 backdrop-blur-xl border border-white/40 rounded-xl p-8 relative overflow-hidden group hover:shadow-xl transition-all duration-500">
                <!-- Dimmed Overlay for Restricted Models -->
                <div x-show="model.is_restricted" class="absolute inset-0 dimmed-overlay bg-surface-bright/60 backdrop-grayscale z-10 pointer-events-none opacity-100 transition-opacity"></div>
                
                <div class="relative z-20">
                    <div class="flex justify-between items-start mb-8">
                        <div class="h-12 w-12 rounded-lg flex items-center justify-center transition-colors"
                             :class="model.is_restricted ? 'bg-secondary-container text-on-secondary-container' : 'bg-primary-container text-on-primary-container'">
                            <span class="material-symbols-outlined" x-text="getIconForModel(model.name)"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span x-show="model.is_restricted" class="bg-error text-white font-label-caps text-[10px] px-2 py-0.5 rounded-full transition-all">RESTRICTED</span>
                            <label class="relative inline-flex items-center cursor-pointer pointer-events-auto">
                                <input class="sr-only ios-toggle" type="checkbox" :checked="!model.is_restricted" @change="toggleRestriction(model)"/>
                                <div class="toggle-bg w-11 h-6 bg-secondary/20 rounded-full transition-colors relative">
                                    <div class="toggle-dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform shadow-sm"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <h4 class="font-headline-lg text-headline-lg mb-2" :class="model.is_restricted ? 'text-on-surface-variant' : 'text-on-surface'" x-text="model.name"></h4>
                    <p class="font-body-md mb-6 leading-relaxed" :class="model.is_restricted ? 'text-secondary/60' : 'text-secondary'" x-text="model.description"></p>
                    <div class="flex gap-4 pt-6 border-t border-white/20">
                        <div class="flex flex-col">
                            <span class="font-label-caps text-[10px] text-outline">FIELDS</span>
                            <span class="font-data-tabular text-data-tabular" x-text="model.columns_count + ' Params'"></span>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-label-caps text-[10px] text-outline">VISIBILITY</span>
                            <span class="font-data-tabular text-data-tabular" :class="model.is_restricted ? 'text-error' : 'text-tertiary'" x-text="model.is_restricted ? 'Locked' : 'Global'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Visual Decorative Area -->
    <section class="mt-section-gap grid grid-cols-12 gap-gutter items-center">
        <div class="col-span-7 h-[400px] rounded-2xl overflow-hidden relative shadow-2xl">
            <img alt="Premium workspace" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC0HW2Kf4Z7ciLHCiXyr4RmFhz9Jr6DEQi6fufxb5lTa6Czj73bTrziDszdAlEv4NVltN-J5qA8wzMEa-jjn7rkuRNODdt9pgkZuFGsZMecBbq9TRpJi_qYLHZvBXdFRz3gAErtQ7vq_7ORY0heSCXN19Mchzqim4xd7mLcNgzB9Irfs6fDyNG9mZTFlRX30tt6AUEdnruIFnxr61Jd3enoGnri3_nOpDa8QRqocLIIefrOETsnETCB0VHGwv4kyMPlz0_D4cuNJSs"/>
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
            <div class="absolute bottom-8 left-8">
                <p class="text-white font-headline-lg text-headline-lg max-w-md">Precision meets prestige.</p>
            </div>
        </div>
        <div class="col-span-5 px-10">
            <h5 class="font-display-md text-display-md text-primary mb-4">Architecture Insight</h5>
            <p class="text-secondary font-body-lg mb-8">Our proprietary data modeling engine ensures that your enterprise BI environment remains clean, performant, and secure by allowing granular control over which system modules are exposed to the reporting layer.</p>
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <span class="material-symbols-outlined text-primary">auto_fix</span>
                    <span class="font-body-md text-on-surface">Auto-balancing schema generation</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="material-symbols-outlined text-primary">verified_user</span>
                    <span class="font-body-md text-on-surface">SOC2 Compliant data masking</span>
                </div>
            </div>
        </div>
    </section>
</main>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('modelsConfig', () => ({
            models: @json($models ?? []),
            message: '',
            messageType: 'success',

            showMessage(msg, type = 'success') {
                this.message = msg;
                this.messageType = type;
                setTimeout(() => { this.message = ''; }, 3000);
            },

            getIconForModel(name) {
                const map = {
                    'User': 'person_outline',
                    'Product': 'inventory_2',
                    'Order': 'shopping_cart',
                    'Payment': 'payments',
                    'Category': 'category',
                    'Role': 'admin_panel_settings',
                };
                return map[name] || 'table_chart';
            },

            async toggleRestriction(model) {
                // If currently restricted, we are un-restricting it (because checkbox was unchecked and now clicked to check)
                // The bound model.is_restricted is true if restricted. The checkbox is bound to !model.is_restricted.
                // When they click it, they want to flip the state.
                const newRestrictionState = !model.is_restricted;
                
                // Optimistic UI update
                model.is_restricted = newRestrictionState;

                try {
                    const response = await fetch('{{ route('admin.models.toggle') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            model_class: model.class,
                            is_restricted: newRestrictionState
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.showMessage(data.message);
                    } else {
                        // Revert on failure
                        model.is_restricted = !newRestrictionState;
                        this.showMessage(data.error || 'Failed to update restriction.', 'error');
                    }
                } catch (error) {
                    model.is_restricted = !newRestrictionState;
                    this.showMessage('Network error occurred.', 'error');
                }
            }
        }));
    });
</script>
@endpush
@endsection
