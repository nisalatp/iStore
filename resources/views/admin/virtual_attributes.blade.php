@extends('layouts.admin')

@section('header_title', 'Virtual Attributes Designer')

@section('content')
<div x-data="vaDesigner()" class="absolute inset-0 flex flex-col overflow-hidden bg-surface-container-low/30">
    <!-- Main Content -->
    <main class="flex-1 flex overflow-hidden relative">
        <!-- Main Builder Canvas -->
        <section class="flex-1 w-full h-full relative">
            
            <div class="h-full overflow-y-auto p-10 pb-32 relative w-full">
            
            <!-- Error/Success Notification -->
            <div x-show="message" x-transition class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 px-6 py-3 rounded-full shadow-lg text-white font-label-caps"
                 :class="messageType === 'success' ? 'bg-green-500' : 'bg-red-500'">
                <span x-text="message"></span>
            </div>

            <!-- Header Section (Toggle Buttons Only) -->
            <div class="mb-10 flex justify-end">
                <div class="flex bg-white/10 p-1 rounded-lg border border-outline-variant/30 bg-white">
                    <button @click.prevent="form.builderMode = 'visual'" class="px-4 py-1.5 rounded-md text-sm font-label-caps transition-all"
                        :class="form.builderMode === 'visual' ? 'bg-primary text-white shadow-md' : 'text-secondary hover:bg-surface-container-low'">
                        <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">account_tree</span> Visual Builder</span>
                    </button>
                    <button @click.prevent="switchToSqlMode" class="px-4 py-1.5 rounded-md text-sm font-label-caps transition-all"
                        :class="form.builderMode === 'sql' ? 'bg-[#333] text-white shadow-md' : 'text-secondary hover:bg-surface-container-low'">
                        <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px]">code</span> Raw SQL</span>
                    </button>
                </div>
            </div>

            <!-- Selection Area: Metadata & Models -->
            <div class="grid grid-cols-12 gap-6 mb-6">
                <div class="col-span-12 lg:col-span-4 space-y-3">
                    <label class="font-label-caps text-secondary">Attribute Name</label>
                    <input x-model="form.name" class="w-full bg-white border border-outline-variant/40 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary/20 outline-none font-data-tabular" placeholder="e.g., total_orders_value" type="text" />
                </div>
                <div class="col-span-12 lg:col-span-4 space-y-3">
                    <label class="font-label-caps text-secondary">Base Model</label>
                    <div class="relative">
                        <select x-model="form.baseModel" class="w-full bg-white border border-outline-variant/40 rounded-xl px-4 py-3 appearance-none !bg-none focus:ring-2 focus:ring-primary/20 outline-none">
                            <option value="" disabled>Select Base Model</option>
                            <template x-for="model in availableModels" :key="model.class">
                                <option :value="model.class" x-text="model.name"></option>
                            </template>
                        </select>
                        <span class="material-symbols-outlined absolute right-4 top-3.5 text-secondary pointer-events-none">expand_more</span>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-4 space-y-3">
                    <label class="font-label-caps text-secondary">Dependencies (Target Models)</label>
                    <div class="flex flex-wrap gap-2 p-2 bg-white border border-outline-variant/40 rounded-xl min-h-[50px] items-center">
                        <template x-for="(dep, index) in form.dependencies" :key="index">
                            <span class="px-3 py-1 bg-primary/10 text-primary rounded-full text-xs font-semibold flex items-center gap-1">
                                <span x-text="dep.split('\\').pop()"></span> 
                                <span @click="removeDependency(index)" class="material-symbols-outlined text-[14px] cursor-pointer">close</span>
                            </span>
                        </template>
                        <select x-model="newDependency" @change="addDependency" class="border-none bg-transparent text-outline text-xs px-2 cursor-pointer focus:ring-0 outline-none max-w-[150px]">
                            <option value="">+ Add Dependency</option>
                            <template x-for="model in availableModels" :key="'target_' + model.class">
                                <option :value="model.class" x-text="model.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SQL Editor Mode -->
            <div x-show="form.builderMode === 'sql'" class="relative flex-grow min-h-[400px]" x-cloak>
                <textarea x-model="form.sqlFragment"
                    class="w-full h-full absolute inset-0 bg-[#0f0f0f] text-[#d4d4d4] font-mono p-6 rounded-lg resize-none outline-none focus:ring-1 focus:ring-primary-fixed-dim text-[14px] leading-relaxed"
                    spellcheck="false" placeholder="-- Enter SQL Select fragment here..."></textarea>
            </div>

            <!-- Visual Builder Blocks -->
            <div x-show="form.builderMode === 'visual'" class="space-y-4" x-cloak x-data="{ activeAccordion: 1 }">
                
                <!-- 1. Inner Filters (WHERE) -->
                <div class="glass-panel rounded-2xl shadow-sm overflow-hidden border border-white/40">
                    <button @click="activeAccordion = activeAccordion === 1 ? null : 1" class="w-full flex justify-between items-center p-4 bg-white/30 hover:bg-white/50 transition-colors">
                        <h3 class="font-headline-lg text-[16px] text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">filter_alt</span> 1. Inner Filters (WHERE)
                        </h3>
                        <span class="material-symbols-outlined text-secondary" x-text="activeAccordion === 1 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeAccordion === 1" class="p-5 border-t border-white/40 bg-white/10 flex flex-col gap-6" x-collapse>
                        <div x-data="{ node: form.ast.innerFilters, parentList: null, index: null, isOuter: false }">
                            @include('admin.partials.filter_node')
                        </div>
                    </div>
                </div>

                <!-- 2. Subquery Aggregation -->
                <div class="glass-panel rounded-2xl shadow-sm overflow-hidden border border-white/40">
                    <button @click="activeAccordion = activeAccordion === 2 ? null : 2" class="w-full flex justify-between items-center p-4 bg-white/30 hover:bg-white/50 transition-colors">
                        <h3 class="font-headline-lg text-[16px] text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">functions</span> 2. Subquery Aggregation
                        </h3>
                        <span class="material-symbols-outlined text-secondary" x-text="activeAccordion === 2 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeAccordion === 2" class="p-5 border-t border-white/40 bg-white/10" x-collapse>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-label-caps text-secondary mb-1 block">Function</label>
                                <select x-model="form.ast.aggregateFunction" class="w-full bg-white border border-outline-variant/50 rounded-lg px-3 py-2 text-sm outline-none focus:border-primary">
                                    <option value="SUM">SUM</option>
                                    <option value="COUNT">COUNT</option>
                                    <option value="AVG">AVG</option>
                                    <option value="MAX">MAX</option>
                                    <option value="MIN">MIN</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-label-caps text-secondary mb-1 block">Target Column</label>
                                <select x-model="form.ast.aggregateColumn" class="w-full bg-white border border-outline-variant/50 rounded-lg px-3 py-2 text-sm outline-none focus:border-primary font-mono">
                                    <option value="">Select Column</option>
                                    <template x-for="m in getDictionaryModels()" :key="'agg_m_'+m.class">
                                        <template x-for="c in m.columns" :key="'agg_c_'+m.class+'_'+c">
                                            <option :value="c" x-text="m.name + ' - ' + c"></option>
                                        </template>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 3. Outer Filters (HAVING) -->
                <div class="glass-panel rounded-2xl shadow-sm overflow-hidden border border-white/40">
                    <button @click="activeAccordion = activeAccordion === 3 ? null : 3" class="w-full flex justify-between items-center p-4 bg-white/30 hover:bg-white/50 transition-colors">
                        <h3 class="font-headline-lg text-[16px] text-tertiary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">filter_list</span> 3. Outer Filters (HAVING)
                        </h3>
                        <span class="material-symbols-outlined text-secondary" x-text="activeAccordion === 3 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeAccordion === 3" class="p-5 border-t border-white/40 bg-white/10 flex flex-col gap-6" x-collapse>
                        <div x-data="{ node: form.ast.outerFilters, parentList: null, index: null, isOuter: true }">
                            @include('admin.partials.filter_node')
                        </div>
                    </div>
                </div>
            </div>
            
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="fixed bottom-0 right-0 w-[calc(100%-18rem)] h-20 border-t border-outline-variant/30 bg-surface-container-lowest/90 backdrop-blur-md px-10 flex justify-between items-center z-[60]">
        <div class="flex gap-8 items-center">
            <span class="font-label-caps text-secondary">© 2024 Luxury Beauty Enterprise. All rights reserved.</span>
        </div>
        <div class="flex items-center gap-4">
            <button class="px-8 py-3 rounded-full border border-primary text-primary font-label-caps hover:bg-primary/5 hover:scale-[1.02] transition-all">
                Validate SQL
            </button>
            <button @click="saveVirtualAttribute" :disabled="isSaving"
                class="px-8 py-3 rounded-full bg-gradient-to-r from-[#7e5352] to-[#dda7a5] text-white font-label-caps shadow-lg shadow-primary/20 hover:scale-[1.02] transition-all disabled:opacity-50">
                <span x-show="isSaving" class="material-symbols-outlined animate-spin text-[18px]">sync</span>
                <span x-show="!isSaving" class="material-symbols-outlined text-[20px]">save</span>
                <span x-text="isSaving ? 'Saving...' : 'Save Virtual Attribute'"></span>
            </button>
        </div>
    </footer>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vaDesigner', () => ({
                availableModels: @json($modelOptions),
                generatedColumns: [],
                form: {
                    name: '',
                    baseModel: '',
                    sqlFragment: '',
                    dependencies: [],
                    builderMode: 'visual',
                    ast: {
                        targetModel: '',
                        aggregateFunction: 'SUM',
                        aggregateColumn: '',
                        innerFilters: { type: 'group', logic: 'AND', children: [] },
                        outerFilters: { type: 'group', logic: 'AND', children: [] }
                    }
                },
                newDependency: '',
                isSaving: false,
                isCompiling: false,
                message: '',
                messageType: 'success',

                addDependency() {
                    if (this.newDependency && !this.form.dependencies.includes(this.newDependency)) {
                        this.form.dependencies.push(this.newDependency);
                    }
                    this.newDependency = '';
                },

                removeDependency(index) {
                    this.form.dependencies.splice(index, 1);
                },

                getDictionaryModels() {
                    if (!this.form.baseModel) return [];
                    const models = [this.form.baseModel, ...this.form.dependencies];
                    return this.availableModels.filter(m => models.includes(m.class));
                },

                getModelColumns(modelClass) {
                    const model = this.availableModels.find(m => m.class === modelClass);
                    return model ? model.columns : [];
                },

                addRuleToGroup(group) {
                    group.children.push({
                        id: Date.now() + Math.random(),
                        type: 'leaf',
                        model: '',
                        column: '',
                        operator: '=',
                        value: ''
                    });
                },

                addGroupToGroup(group) {
                    group.children.push({
                        id: Date.now() + Math.random(),
                        type: 'group',
                        logic: 'AND',
                        children: []
                    });
                },

                removeNodeFromList(list, index) {
                    list.splice(index, 1);
                },

                showMessage(msg, type = 'success') {
                    this.message = msg;
                    this.messageType = type;
                    setTimeout(() => { this.message = ''; }, 3000);
                },

                async switchToSqlMode() {
                    if (this.form.builderMode === 'sql') return;
                    
                    if (!this.form.baseModel || this.form.dependencies.length === 0) {
                        this.showMessage('Please select a Base Model and at least one Target Model (Dependency) to compile SQL.', 'error');
                        return;
                    }

                    this.isCompiling = true;
                    this.form.sqlFragment = 'Compiling...';
                    this.form.builderMode = 'sql';
                    
                    try {
                        const response = await fetch('{{ route('va_builder.compile') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.form)
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.form.sqlFragment = data.sql;
                        } else {
                            this.form.sqlFragment = '-- Error compiling SQL: \n-- ' + data.error;
                            this.showMessage('Error compiling SQL', 'error');
                        }
                    } catch (e) {
                        this.form.sqlFragment = '-- Network error occurred';
                    } finally {
                        this.isCompiling = false;
                    }
                },

                async saveVirtualAttribute() {
                    if (!this.form.name || !this.form.baseModel || (this.form.builderMode === 'sql' && !this.form.sqlFragment)) {
                        this.showMessage('Please fill all required fields.', 'error');
                        return;
                    }

                    this.isSaving = true;
                    try {
                        const response = await fetch('{{ route('va_builder.register') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showMessage(data.message);
                            // Reset form
                            this.form.name = '';
                            this.form.sqlFragment = '';
                            this.form.dependencies = [];
                        } else {
                            this.showMessage(data.error || 'Failed to save virtual attribute.', 'error');
                        }
                    } catch (error) {
                        this.showMessage('Network error occurred.', 'error');
                    } finally {
                        this.isSaving = false;
                    }
                }
            }));
        });
    </script>
    @endpush
</div>
@endsection