@extends('layouts.admin')

@section('header_title', 'Virtual Attributes Management')

@section('content')
<div class="h-full overflow-y-auto p-10 relative w-full bg-surface-container-low/30" x-data="vaManager()">
    
    <div class="max-w-[1400px] mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-display text-primary">Virtual Attributes</h1>
                <p class="text-secondary mt-2">Manage generated subqueries and view their dependency usage across saved reports.</p>
            </div>
            <a href="{{ route('admin.virtual_attributes') }}" class="px-6 py-2.5 bg-primary text-white rounded-full font-label-caps hover:bg-primary/90 transition-colors shadow-md flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Create New
            </a>
        </div>

        <!-- Success/Error Message -->
        <div x-show="message" x-transition class="mb-6 px-6 py-4 rounded-xl flex items-center gap-3 text-sm font-medium"
             :class="messageType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" style="display: none;">
            <span class="material-symbols-outlined text-[20px]" x-text="messageType === 'success' ? 'check_circle' : 'error'"></span>
            <span x-text="messageText"></span>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/40 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-lowest border-b border-outline-variant/40 text-secondary text-sm font-label-caps uppercase tracking-wider">
                        <th class="p-4 pl-6 font-medium">Attribute Name</th>
                        <th class="p-4 font-medium">Base Model</th>
                        <th class="p-4 font-medium">Target Models</th>
                        <th class="p-4 font-medium text-center">Usage Count</th>
                        <th class="p-4 pr-6 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30">
                    @forelse($virtualAttributes as $va)
                        <tr class="hover:bg-surface-container-low/30 transition-colors group" id="va-row-{{ $va->id }}">
                            <td class="p-4 pl-6">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary/70 text-[18px]">auto_awesome</span>
                                    <span class="font-medium text-on-surface">va:{{ $va->name }}</span>
                                </div>
                            </td>
                            <td class="p-4 text-sm text-secondary font-data-tabular">
                                {{ class_basename($va->base_model) }}
                            </td>
                            <td class="p-4 text-sm text-secondary font-data-tabular">
                                @if(is_array($va->dependencies))
                                    {{ collect($va->dependencies)->except(['_ast'])->map(fn($d) => is_string($d) ? class_basename($d) : '')->filter()->implode(', ') }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                @if($va->usage_count > 0)
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-primary/10 text-primary font-medium text-xs">
                                        {{ $va->usage_count }} Report(s)
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-surface-container text-secondary text-xs">
                                        Unused
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <button @click="attemptDelete({{ $va->id }}, '{{ $va->name }}')" 
                                        class="p-2 text-secondary hover:text-error hover:bg-error/10 rounded-lg transition-colors"
                                        title="Delete Attribute">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-secondary">
                                <span class="material-symbols-outlined text-[48px] text-secondary/30 mb-3 block">inventory_2</span>
                                <p>No Virtual Attributes registered yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Force Delete Modal -->
    <div x-show="showModal" class="fixed inset-0 z-[100] flex items-center justify-center" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-on-surface/40 backdrop-blur-sm" @click="showModal = false" x-transition.opacity></div>
        
        <div class="relative bg-surface-container-lowest rounded-3xl shadow-2xl border border-outline-variant/30 w-full max-w-md overflow-hidden transform transition-all"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            
            <div class="p-8">
                <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-error text-[24px]">warning</span>
                </div>
                
                <h3 class="text-xl font-display text-on-surface mb-2">Attribute in Use</h3>
                <p class="text-secondary text-sm leading-relaxed mb-8" x-text="modalMessage"></p>
                
                <div class="flex items-center justify-end gap-3">
                    <button @click="showModal = false" 
                            class="px-5 py-2.5 rounded-full border border-outline-variant text-secondary font-label-caps hover:bg-surface-container-low transition-colors text-sm">
                        Cancel
                    </button>
                    <button @click="executeDelete(pendingDeleteId, true)" 
                            :disabled="isDeleting"
                            class="px-5 py-2.5 rounded-full bg-error text-white font-label-caps shadow-md hover:bg-error/90 transition-colors text-sm flex items-center gap-2 disabled:opacity-50">
                        <span x-show="isDeleting" class="material-symbols-outlined animate-spin text-[16px]">sync</span>
                        Force Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('vaManager', () => ({
            message: false,
            messageType: 'success',
            messageText: '',
            
            showModal: false,
            modalMessage: '',
            pendingDeleteId: null,
            isDeleting: false,

            showMessage(text, type = 'success') {
                this.messageText = text;
                this.messageType = type;
                this.message = true;
                setTimeout(() => this.message = false, 5000);
            },

            async attemptDelete(id, name) {
                this.executeDelete(id, false);
            },

            async executeDelete(id, force = false) {
                this.isDeleting = true;
                try {
                    const url = force ? `/admin/virtual-attributes/${id}?force=true` : `/admin/virtual-attributes/${id}`;
                    
                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (response.status === 409 && data.requires_force) {
                        // Conflict - requires force deletion
                        this.pendingDeleteId = id;
                        this.modalMessage = data.message;
                        this.showModal = true;
                        return;
                    }

                    if (data.success) {
                        this.showModal = false;
                        this.showMessage(data.message, 'success');
                        // Remove row from table
                        const row = document.getElementById(`va-row-${id}`);
                        if (row) row.remove();
                    } else {
                        this.showMessage(data.message || 'Failed to delete attribute.', 'error');
                    }
                } catch (e) {
                    this.showMessage('Network error occurred.', 'error');
                } finally {
                    this.isDeleting = false;
                }
            }
        }));
    });
</script>
@endpush
@endsection
