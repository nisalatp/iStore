@extends('layouts.admin')

@section('content')
<!-- Main Content Area -->
<main x-data="dashboardManager()" class="min-h-[calc(100vh-80px)] p-margin-desktop relative pb-24">
    <!-- Header -->
    <header class="flex justify-between items-center mb-12">
        <div>
            <h2 class="font-display-md text-display-md text-on-surface">Overview</h2>
            <nav class="flex gap-2 text-on-surface-variant mt-2">
                <span class="font-label-caps text-label-caps">Admin</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="font-label-caps text-label-caps text-primary">Dashboard</span>
            </nav>
        </div>
    </header>

    <!-- Error/Success Notification -->
    <div x-show="message" x-transition class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 px-6 py-3 rounded-full shadow-lg text-white font-label-caps"
         :class="messageType === 'success' ? 'bg-green-500' : 'bg-red-500'" x-cloak>
        <span x-text="message"></span>
    </div>

    <!-- View C: Saved Reports -->
    <section class="mb-section-gap">
        <header class="flex justify-between items-end mb-8">
            <div>
                <h3 class="font-display-md text-headline-lg text-on-surface">Saved Reports</h3>
                <p class="text-on-surface-variant mt-2">Access and manage your team's catalog of BI assets.</p>
            </div>
            <div class="flex gap-3">
                <button class="px-6 py-2 rounded-full border border-primary text-primary font-label-caps text-label-caps hover:bg-primary/5 transition-colors">Export All</button>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-4">
            <template x-for="report in reports" :key="report.id">
                <!-- Report Row -->
                <div class="bento-card glass p-6 rounded-xl flex items-center justify-between hover:border-primary/40 transition-all cursor-pointer group">
                    <div class="flex items-center gap-6">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                             :class="getRandomColor(report.id)">
                            <span class="material-symbols-outlined">bar_chart</span>
                        </div>
                        <div>
                            <h5 class="font-body-md font-bold text-on-surface" x-text="report.name"></h5>
                            <div class="flex gap-4 mt-1">
                                <span class="text-xs text-on-surface-variant font-medium" x-text="'ID: ' + report.id"></span>
                                <span class="text-xs text-on-surface-variant font-medium text-primary italic" x-text="report.description"></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click.stop="runReport(report.id)" class="p-2 text-on-surface-variant hover:text-primary transition-transform hover:scale-110" title="Run Report">
                            <span class="material-symbols-outlined">play_circle</span>
                        </button>
                    </div>
                </div>
            </template>

            <div x-show="reports.length === 0" class="text-center py-12 glass-panel rounded-3xl" x-cloak>
                <span class="material-symbols-outlined text-[48px] text-primary/30 mb-4">analytics</span>
                <p class="font-body-md text-secondary">No saved reports found.</p>
                <a href="{{ route('admin.report_builder') }}" class="inline-block mt-4 text-primary font-label-caps hover:underline">Create your first report</a>
            </div>
        </div>
    </section>

    <!-- Report Execution Modal -->
    <div x-show="runner.isOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-black/40 backdrop-blur-sm" @keydown.escape.window="closeRunner()">
        <div class="bg-surface w-full max-w-7xl max-h-[90vh] rounded-3xl shadow-2xl flex flex-col overflow-hidden" @click.stop>
            <!-- Modal Header -->
            <div class="px-8 py-6 border-b border-outline-variant/30 flex justify-between items-center bg-[#F4E3E2]/50">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined">terminal</span>
                    </div>
                    <div>
                        <h3 class="font-headline-lg text-lg text-primary">Report Execution Runner</h3>
                        <p class="text-sm text-secondary font-data-tabular">Processing via DynamicReportGenerator</p>
                    </div>
                </div>
                <button @click="closeRunner()" class="w-10 h-10 rounded-full hover:bg-black/5 flex items-center justify-center text-secondary transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8 relative bg-white/50">
                <!-- Loading State -->
                <div x-show="runner.loading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm">
                    <span class="material-symbols-outlined animate-spin text-primary text-4xl mb-4">sync</span>
                    <p class="font-label-caps text-primary tracking-widest animate-pulse">EXECUTING QUERY PLAN...</p>
                </div>

                <div x-show="!runner.loading && runner.error" class="bg-red-50 text-red-700 p-6 rounded-xl border border-red-200" x-cloak>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-symbols-outlined">error</span>
                        <h4 class="font-bold">Execution Failed</h4>
                    </div>
                    <p class="font-body-md" x-text="runner.error"></p>
                </div>

                <div x-show="!runner.loading && !runner.error && runner.results" x-cloak>
                    <!-- Data Preview Table -->
                    <div class="glass-panel rounded-2xl overflow-hidden border border-white/60 shadow-sm">
                        <div class="px-6 py-4 border-b border-outline-variant/20 flex justify-between items-center bg-[#F4E3E2]/20">
                            <h4 class="font-label-caps text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">table_view</span>
                                Result Set
                            </h4>
                            <span class="font-data-tabular text-sm text-secondary" x-text="(runner.results ? runner.results.length : 0) + ' records'"></span>
                        </div>
                        <div class="overflow-x-auto max-h-[50vh]">
                            <table class="w-full text-left border-collapse whitespace-nowrap">
                                <thead class="sticky top-0 bg-white shadow-sm z-10">
                                    <tr>
                                        <template x-for="col in runner.columns" :key="col">
                                            <th class="px-6 py-4 text-xs font-label-caps text-secondary tracking-wider" x-text="col"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/20 bg-white/40">
                                    <template x-for="(row, index) in runner.results" :key="index">
                                        <tr class="hover:bg-white/60 transition-colors">
                                            <template x-for="col in runner.columns" :key="col">
                                                <td class="px-6 py-4 font-data-tabular text-sm text-on-surface" x-text="row[col]"></td>
                                            </template>
                                        </tr>
                                    </template>
                                    <tr x-show="runner.results && runner.results.length === 0">
                                        <td :colspan="runner.columns.length" class="px-6 py-8 text-center text-secondary font-body-md">
                                            No data returned for this report.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dashboardManager', () => ({
            reports: @json($reports ?? []),
            message: '',
            messageType: 'success',

            runner: {
                isOpen: false,
                loading: false,
                error: null,
                results: null,
                columns: [],
            },

            showMessage(msg, type = 'success') {
                this.message = msg;
                this.messageType = type;
                setTimeout(() => { this.message = ''; }, 3000);
            },

            getRandomColor(id) {
                const colors = [
                    'bg-primary-container/20 text-primary',
                    'bg-tertiary-container/20 text-tertiary',
                    'bg-secondary-container/20 text-secondary',
                    'bg-blue-100 text-blue-700',
                    'bg-purple-100 text-purple-700'
                ];
                return colors[id % colors.length];
            },

            async runReport(id) {
                this.runner.isOpen = true;
                this.runner.loading = true;
                this.runner.error = null;
                this.runner.results = null;
                this.runner.columns = [];

                try {
                    const response = await fetch(`/builder/saved/${id}/execute`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        this.runner.results = data.results;
                        if (data.results && data.results.length > 0) {
                            this.runner.columns = Object.keys(data.results[0]);
                        }
                        this.showMessage('Report executed successfully.');
                    } else {
                        this.runner.error = data.error || 'Execution failed.';
                    }
                } catch (e) {
                    this.runner.error = 'Network error occurred while executing report.';
                } finally {
                    this.runner.loading = false;
                }
            },

            closeRunner() {
                this.runner.isOpen = false;
            }
        }));
    });
</script>
@endpush
@endsection
