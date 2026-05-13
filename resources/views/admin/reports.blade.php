@extends('layouts.admin')

@section('header_title', 'Saved Reports')

@section('content')
<div class="p-8 max-w-[1440px] mx-auto min-h-[calc(100vh-80px)] relative overflow-hidden" x-data="reportAssignments()">
    <!-- Ambient Background Gradients -->
    <div class="fixed top-[-20%] left-[-10%] w-[50%] h-[50%] bg-primary/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="fixed bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-tertiary-fixed/30 rounded-full blur-[150px] pointer-events-none"></div>

    <header class="mb-12 relative z-10">
        <h1 class="text-display-lg text-on-background mb-4">Saved Reports</h1>
        <p class="text-body-lg text-on-surface-variant max-w-2xl leading-relaxed">
            Manage saved reports and assign viewing permissions to specific users. Only assigned users will be able to execute these reports.
        </p>
    </header>

    @if (session('success'))
        <div class="bg-tertiary-fixed text-on-tertiary-fixed-variant p-4 rounded-xl mb-8 flex items-center gap-3 border border-tertiary/20 shadow-sm">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-data-tabular">{{ session('success') }}</span>
        </div>
    @endif

    <div class="glass p-8 rounded-3xl relative z-10 border border-white/50 ambient-glow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-outline-variant/30">
                        <th class="p-4 font-label-caps text-secondary text-sm">Report Name</th>
                        <th class="p-4 font-label-caps text-secondary text-sm">Description</th>
                        <th class="p-4 font-label-caps text-secondary text-sm">Created By</th>
                        <th class="p-4 font-label-caps text-secondary text-sm">Assigned Users</th>
                        <th class="p-4 font-label-caps text-secondary text-sm text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr class="border-b border-outline-variant/10 hover:bg-white/40 transition-colors">
                            <td class="p-4 font-body-md text-primary font-bold">{{ $report->name }}</td>
                            <td class="p-4 text-sm text-on-surface-variant max-w-xs truncate" title="{{ $report->description }}">{{ $report->description ?: 'No description' }}</td>
                            <td class="p-4 text-sm text-secondary">User ID: {{ $report->user_id ?? 'System' }}</td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-2">
                                    @php
                                        $assignedRoleIds = $report->payload['assigned_roles'] ?? [];
                                        $assignedRoles = collect($assignedRoleIds)->map(function($id) use ($roles) {
                                            return $roles->firstWhere('id', $id);
                                        })->filter();
                                    @endphp
                                    @foreach($assignedRoles as $role)
                                        <span class="px-2 py-1 bg-tertiary/10 text-tertiary rounded-md text-xs font-label-caps border border-tertiary/20">
                                            Role: {{ $role->name }}
                                        </span>
                                    @endforeach
                                    @foreach($report->assignedUsers as $user)
                                        <span class="px-2 py-1 bg-primary/10 text-primary rounded-md text-xs font-label-caps border border-primary/20">
                                            {{ $user->name }}
                                        </span>
                                    @endforeach
                                    @if($report->assignedUsers->isEmpty() && $assignedRoles->isEmpty())
                                        <span class="text-xs text-secondary italic">No assignments</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 text-right">
                                <button @click="openModal({{ $report->id }}, '{{ addslashes($report->name) }}', {{ $report->assignedUsers->pluck('id') }}, {{ json_encode($assignedRoleIds) }})" class="rose-gold-btn px-4 py-2 rounded-lg text-white font-label-caps text-xs shadow-md inline-flex items-center gap-2 hover:shadow-lg">
                                    <span class="material-symbols-outlined text-[16px]">manage_accounts</span> Assign Access
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-secondary italic font-body-md">
                                No saved reports found in the system.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div x-show="isModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-surface-container-lowest w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border border-white/60 p-8 relative transform transition-all" @click.away="closeModal()" x-transition.scale.95>
            
            <button @click="closeModal()" class="absolute top-6 right-6 text-secondary hover:text-primary transition-colors bg-surface-container-highest p-1.5 rounded-full hover:bg-primary-container/30">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>

            <h2 class="text-headline-lg-mobile font-display-md text-primary mb-2">Assign Access</h2>
            <p class="text-sm text-on-surface-variant mb-4">Select users or roles to grant access to <span class="font-bold text-primary" x-text="currentReportName"></span>.</p>

            <div class="flex border-b border-outline-variant/30 mb-4">
                <button type="button" @click="activeTab = 'users'" class="px-4 py-2 font-label-caps text-sm border-b-2 transition-colors" :class="activeTab === 'users' ? 'border-primary text-primary' : 'border-transparent text-secondary hover:text-primary'">Users</button>
                <button type="button" @click="activeTab = 'roles'" class="px-4 py-2 font-label-caps text-sm border-b-2 transition-colors" :class="activeTab === 'roles' ? 'border-tertiary text-tertiary' : 'border-transparent text-secondary hover:text-tertiary'">Roles</button>
            </div>

            <form :action="'{{ url('/admin/reports') }}/' + currentReportId + '/assign'" method="POST" id="assignForm">
                @csrf
                
                <!-- Users Tab -->
                <div x-show="activeTab === 'users'" class="max-h-64 overflow-y-auto space-y-2 mb-8 pr-2 py-2">
                    @foreach($users as $user)
                        <label class="flex items-center justify-between p-3 rounded-xl border border-outline-variant/30 cursor-pointer hover:bg-surface-container-low transition-colors" :class="{'bg-primary-container/20 border-primary/50': selectedUsers.includes({{ $user->id }})}">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs uppercase">
                                    {{ substr($user->name, 0, 2) }}
                                </div>
                                <span class="font-data-tabular text-sm text-on-surface">{{ $user->name }}</span>
                            </div>
                            <div class="relative flex items-center">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" x-model="selectedUsers" class="peer sr-only">
                                <div class="w-5 h-5 rounded border border-outline-variant peer-checked:bg-primary peer-checked:border-primary flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-[14px] text-white opacity-0 peer-checked:opacity-100 transition-opacity">check</span>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <!-- Roles Tab -->
                <div x-show="activeTab === 'roles'" class="max-h-64 overflow-y-auto space-y-2 mb-8 pr-2 py-2" style="display: none;">
                    @foreach($roles as $role)
                        <label class="flex items-center justify-between p-3 rounded-xl border border-outline-variant/30 cursor-pointer hover:bg-surface-container-low transition-colors" :class="{'bg-tertiary-container/20 border-tertiary/50': selectedRoles.includes({{ $role->id }})}">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-tertiary/10 text-tertiary flex items-center justify-center font-bold text-xs">
                                    <span class="material-symbols-outlined text-[16px]">group</span>
                                </div>
                                <div>
                                    <span class="font-data-tabular text-sm text-on-surface block">{{ $role->name }}</span>
                                    <span class="text-xs text-secondary">{{ $role->description }}</span>
                                </div>
                            </div>
                            <div class="relative flex items-center">
                                <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" x-model="selectedRoles" class="peer sr-only">
                                <div class="w-5 h-5 rounded border border-outline-variant peer-checked:bg-tertiary peer-checked:border-tertiary flex items-center justify-center transition-colors">
                                    <span class="material-symbols-outlined text-[14px] text-white opacity-0 peer-checked:opacity-100 transition-opacity">check</span>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
                
                <div class="flex justify-end gap-3 border-t border-outline-variant/20 pt-4">
                    <button type="button" @click="closeModal()" class="px-5 py-2.5 rounded-xl text-secondary font-label-caps hover:bg-surface-container-highest transition-colors">Cancel</button>
                    <button type="submit" class="rose-gold-btn px-6 py-2.5 rounded-xl text-white font-label-caps shadow-md hover:shadow-lg flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">save</span> Save Assignments
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reportAssignments', () => ({
            isModalOpen: false,
            activeTab: 'users',
            currentReportId: null,
            currentReportName: '',
            selectedUsers: [],
            selectedRoles: [],

            openModal(id, name, assignedUsers, assignedRoles) {
                this.currentReportId = id;
                this.currentReportName = name;
                this.selectedUsers = Array.isArray(assignedUsers) ? assignedUsers : Object.values(assignedUsers);
                this.selectedRoles = Array.isArray(assignedRoles) ? assignedRoles : Object.values(assignedRoles);
                this.isModalOpen = true;
                this.activeTab = 'users';
                document.body.style.overflow = 'hidden';
            },

            closeModal() {
                this.isModalOpen = false;
                this.currentReportId = null;
                this.currentReportName = '';
                this.selectedUsers = [];
                this.selectedRoles = [];
                document.body.style.overflow = 'auto';
            }
        }));
    });
</script>
@endpush
@endsection
