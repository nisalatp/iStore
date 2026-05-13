@extends('layouts.admin')

@section('header_title', 'Governance')

@section('content')
<!-- Main Content Area -->
<main x-data="securityConfig()" class="p-6 min-h-[calc(100vh-80px)] pb-12 bg-surface-container-lowest">
    <!-- Notifications -->
    <div x-show="message" x-transition class="fixed top-20 left-1/2 transform -translate-x-1/2 z-50 px-6 py-2 rounded-full shadow-lg text-white text-xs font-label-caps"
         :class="messageType === 'success' ? 'bg-green-500' : 'bg-red-500'">
        <span x-text="message"></span>
    </div>

    <div class="w-full space-y-4">
        <!-- Dense Header & Controls -->
        <div class="flex items-center justify-between glass-panel px-6 py-3 rounded-2xl border border-white/60 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-[24px]">shield_person</span>
                <h3 class="font-headline-md text-[18px] text-primary">Attribute Matrix</h3>
            </div>
            <!-- Selection Controls -->
            <div class="flex items-center gap-6">
                <!-- Model Selection -->
                <div class="flex items-center gap-2">
                    <label class="font-label-caps text-secondary text-[10px]">Model</label>
                    <div class="relative min-w-[160px]">
                        <select x-model="selectedModel" @change="fetchRestrictions" class="w-full appearance-none !bg-none bg-white/60 px-3 py-1.5 rounded-lg text-xs text-on-surface focus:ring-1 focus:ring-primary/20 outline-none cursor-pointer border border-white/60 shadow-sm">
                            <option value="">Select...</option>
                            <template x-for="model in models" :key="model">
                                <option :value="model" x-text="model.split('\\').pop()"></option>
                            </template>
                        </select>
                        <span class="material-symbols-outlined absolute right-2 top-1.5 pointer-events-none opacity-40 text-[16px]">expand_more</span>
                    </div>
                </div>

                <!-- Subject Type Radio -->
                <div class="flex items-center gap-1 bg-white/40 border border-white/60 p-1 rounded-lg shadow-inner">
                    <label class="cursor-pointer px-3 py-1 rounded text-[10px] font-label-caps transition-all" :class="subjectType === 'Role' ? 'bg-primary text-white shadow-sm' : 'text-secondary hover:bg-white/50'">
                        <input type="radio" x-model="subjectType" value="Role" class="hidden" @change="selectedSubjectId = ''; fetchRestrictions()">
                        Role
                    </label>
                    <label class="cursor-pointer px-3 py-1 rounded text-[10px] font-label-caps transition-all" :class="subjectType === 'User' ? 'bg-primary text-white shadow-sm' : 'text-secondary hover:bg-white/50'">
                        <input type="radio" x-model="subjectType" value="User" class="hidden" @change="selectedSubjectId = ''; fetchRestrictions()">
                        User
                    </label>
                </div>

                <!-- Subject Selection -->
                <div class="flex items-center gap-2">
                    <label class="font-label-caps text-secondary text-[10px]" x-text="subjectType === 'Role' ? 'Role' : 'User'"></label>
                    <div class="relative min-w-[180px]">
                        <select x-model="selectedSubjectId" @change="fetchRestrictions" class="w-full appearance-none !bg-none bg-white/60 px-3 py-1.5 rounded-lg text-xs text-on-surface focus:ring-1 focus:ring-primary/20 outline-none cursor-pointer border border-white/60 shadow-sm">
                            <option value="">Select...</option>
                            <template x-if="subjectType === 'Role'">
                                <template x-for="role in roles" :key="'role_'+role.id">
                                    <option :value="role.id" x-text="role.name"></option>
                                </template>
                            </template>
                            <template x-if="subjectType === 'User'">
                                <template x-for="user in users" :key="'user_'+user.id">
                                    <option :value="user.id" x-text="user.name + ' (' + user.email + ')'"></option>
                                </template>
                            </template>
                        </select>
                        <span class="material-symbols-outlined absolute right-2 top-1.5 pointer-events-none opacity-40 text-[16px]">expand_more</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex justify-center items-center py-20">
            <span class="material-symbols-outlined animate-spin text-primary text-4xl">sync</span>
        </div>

        <!-- The Security Configuration Panel -->
        <section x-show="!loading && selectedModel && selectedSubjectId" x-cloak class="glass-panel rounded-2xl overflow-hidden shadow-sm border border-white/60 transition-all duration-500">
            
            <!-- Global Reportable Checkbox -->
            <div class="px-6 py-3 border-b border-white/40 bg-white/30 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-primary">Model Visibility</h4>
                </div>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <span class="text-xs font-label-caps text-secondary" x-text="isReportable ? 'Enabled' : 'Hidden'"></span>
                    <div class="relative">
                        <input type="checkbox" x-model="isReportable" class="peer sr-only">
                        <div class="w-8 h-4 bg-outline-variant/30 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-green-500"></div>
                    </div>
                </label>
            </div>

            <div class="overflow-x-auto max-h-[600px] overflow-y-auto" :class="{'opacity-50 pointer-events-none': !isReportable}">
                <table class="w-full border-collapse text-left relative text-sm">
                    <thead class="sticky top-0 z-10 shadow-sm">
                        <tr class="bg-surface-container-low backdrop-blur-md border-b border-outline-variant/30">
                            <th class="px-6 py-2 font-label-caps text-[10px] text-secondary sticky left-0 bg-surface-container-low">Attribute Name</th>
                            <th class="px-6 py-2 font-label-caps text-[10px] text-secondary text-right">Access Level</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <template x-for="attr in attributes" :key="attr.name">
                            <tr class="hover:bg-white/40 transition-colors group">
                                <td class="px-6 py-2 sticky left-0 bg-surface-bright/50 backdrop-blur-sm group-hover:bg-white/80 transition-colors w-1/2">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[16px]" :class="attr.type === 'virtual' ? 'text-primary/60' : 'text-secondary/40'" x-text="attr.type === 'virtual' ? 'auto_awesome' : 'fingerprint'"></span>
                                        <span class="font-data-tabular font-medium" :class="attr.type === 'virtual' ? 'italic text-primary' : ''" x-text="attr.name"></span>
                                        <span x-show="attr.type === 'virtual'" class="bg-primary/10 text-primary text-[9px] px-1.5 py-0.5 rounded-full">Virtual</span>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-2 w-1/2 text-right">
                                    <div class="flex justify-end">
                                        <div class="flex bg-surface-container-low rounded-lg p-0.5 gap-0.5 border border-white/40 shadow-sm">
                                            <!-- Full Button -->
                                            <button @click="attr.restriction = 'unrestricted'" 
                                                    class="px-3 py-1 rounded flex items-center gap-1.5 transition-all duration-200"
                                                    :class="attr.restriction === 'unrestricted' ? 'bg-white shadow-sm text-tertiary scale-105' : 'hover:bg-white/50 opacity-60 hover:opacity-100'">
                                                <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                                                <span class="font-label-caps text-[10px]">Full</span>
                                            </button>
                                            <!-- Masked Button -->
                                            <button @click="attr.restriction = 'masked'"
                                                    class="px-3 py-1 rounded flex items-center gap-1.5 transition-all duration-200"
                                                    :class="attr.restriction === 'masked' ? 'bg-white shadow-sm text-yellow-600 scale-105' : 'hover:bg-white/50 opacity-60 hover:opacity-100'">
                                                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                                                <span class="font-label-caps text-[10px]">Masked</span>
                                            </button>
                                            <!-- Blocked Button -->
                                            <button @click="attr.restriction = 'blocked'"
                                                    class="px-3 py-1 rounded flex items-center gap-1.5 transition-all duration-200"
                                                    :class="attr.restriction === 'blocked' ? 'bg-white shadow-sm text-error scale-105' : 'hover:bg-white/50 opacity-60 hover:opacity-100'">
                                                <span class="w-2 h-2 rounded-full bg-error"></span>
                                                <span class="font-label-caps text-[10px]">Blocked</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-3 flex justify-between items-center bg-surface-container-lowest/50 backdrop-blur-md border-t border-white/40">
                <div class="flex gap-6">
                    <!-- Legend -->
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-green-500 text-[14px]">lock_open</span>
                        <span class="font-label-caps text-[10px] opacity-60">Full</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-yellow-500 text-[14px]">visibility_off</span>
                        <span class="font-label-caps text-[10px] opacity-60">Masked</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-error text-[14px]">block</span>
                        <span class="font-label-caps text-[10px] opacity-60">Blocked</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="fetchRestrictions" class="px-4 py-1.5 rounded-lg font-label-caps text-[10px] text-secondary border border-outline-variant hover:bg-white transition-all shadow-sm">Discard</button>
                    <button @click="saveRestrictions" class="px-6 py-1.5 rounded-lg font-label-caps text-[10px] text-white bg-primary hover:bg-primary/90 flex items-center gap-2 shadow-sm transition-all" :class="{'opacity-50 pointer-events-none': saving}">
                        <span x-show="saving" class="material-symbols-outlined animate-spin text-[14px]">sync</span>
                        <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                    </button>
                </div>
            </div>
        </section>
        
        <!-- Empty State -->
        <div x-show="!loading && (!selectedModel || !selectedSubjectId)" class="flex flex-col items-center justify-center py-20 glass-panel border border-white/40 rounded-2xl">
            <span class="material-symbols-outlined text-[48px] text-primary/20 mb-3">admin_panel_settings</span>
            <p class="text-secondary text-sm text-center max-w-sm">Select a Model and a User/Role from the top bar to configure attributes.</p>
        </div>
    </div>
</main>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('securityConfig', () => ({
            models: @json($models ?? []),
            roles: @json($roles ?? []),
            users: @json($users ?? []),
            
            selectedModel: '',
            subjectType: 'Role',
            selectedSubjectId: '',
            
            isReportable: true,
            attributes: [],
            
            loading: false,
            saving: false,
            message: '',
            messageType: 'success',

            showMessage(msg, type = 'success') {
                this.message = msg;
                this.messageType = type;
                setTimeout(() => { this.message = ''; }, 3000);
            },

            async fetchRestrictions() {
                if (!this.selectedModel || !this.selectedSubjectId) {
                    this.attributes = [];
                    return;
                }
                
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        model_class: this.selectedModel,
                        subject_type: this.subjectType,
                        subject_id: this.selectedSubjectId
                    });
                    
                    const response = await fetch(`{{ route('admin.security.matrix') }}?${params.toString()}`);
                    const data = await response.json();
                    
                    if (!data.error) {
                        this.isReportable = data.is_reportable;
                        this.attributes = data.attributes;
                    } else {
                        this.showMessage(data.error, 'error');
                    }
                } catch (e) {
                    this.showMessage('Failed to load restrictions.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            async saveRestrictions() {
                if (!this.selectedModel || !this.selectedSubjectId) return;
                
                this.saving = true;
                
                // Build payload: { attrName: 'type' }
                const payloadAttributes = {};
                this.attributes.forEach(attr => {
                    payloadAttributes[attr.name] = attr.restriction;
                });

                try {
                    const response = await fetch('{{ route('admin.security.save') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            model_class: this.selectedModel,
                            subject_type: this.subjectType,
                            subject_id: this.selectedSubjectId,
                            is_reportable: this.isReportable,
                            attributes: payloadAttributes
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.showMessage('Security rules applied successfully.');
                    } else {
                        this.showMessage(data.error || 'Failed to save rules.', 'error');
                    }
                } catch (e) {
                    this.showMessage('Network error occurred.', 'error');
                } finally {
                    this.saving = false;
                }
            }
        }));
    });
</script>
@endpush
@endsection
