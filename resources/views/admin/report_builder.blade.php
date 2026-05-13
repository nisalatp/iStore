@extends('layouts.admin')

@section('header_title', 'Visual Report Designer')

@section('content')
<div x-data="reportBuilder()" class="flex flex-col h-full overflow-hidden">
    <!-- Main Content -->
    <main class="flex-1 flex overflow-hidden relative">
        <!-- Left Data Dictionary Sidebar -->
        <section class="h-full border-r border-outline-variant/30 glass-panel flex flex-col shrink-0 transition-all duration-300" :class="sidebarOpen ? 'w-80' : 'w-16'">
            <div class="p-6 border-b border-outline-variant/30 shrink-0" x-show="sidebarOpen">
                <div class="flex justify-between items-center mb-4">
                    <span class="font-label-caps text-secondary block">Data Dictionary</span>
                    <button @click="sidebarOpen = false" class="text-secondary hover:text-primary transition-colors flex items-center justify-center p-1 rounded hover:bg-primary/5" title="Collapse Panel">
                        <span class="material-symbols-outlined text-[20px]">first_page</span>
                    </button>
                </div>
                <div class="relative">
                    <input x-model="dictSearch" class="w-full pl-3 pr-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-lg text-data-tabular outline-none focus:border-primary transition-colors" placeholder="Filter columns..." type="text"/>
                    <button x-show="dictSearch" @click="dictSearch = ''" class="absolute right-2 top-2 material-symbols-outlined text-[18px] text-secondary hover:text-primary transition-colors cursor-pointer p-0.5 rounded-full hover:bg-surface-container-high">close</button>
                </div>
            </div>
            
            <!-- Collapsed state header -->
            <div class="py-6 flex justify-center items-center shrink-0 border-b border-outline-variant/30 hover:bg-primary/5 cursor-pointer transition-colors" x-show="!sidebarOpen" @click="sidebarOpen = true" title="Expand Data Dictionary">
                <span class="material-symbols-outlined text-secondary">last_page</span>
            </div>

            <div class="p-4 space-y-4 flex-1 overflow-y-auto" x-data="{ activeModelDict: null }" x-show="sidebarOpen" x-transition>
                <template x-for="model in getDictionaryModels()" :key="model.class">
                    <div>
                        <button @click="activeModelDict = activeModelDict === model.class ? null : model.class" class="flex items-center justify-between w-full py-2 px-2 text-primary font-semibold hover:bg-primary/5 rounded transition-all">
                            <span class="flex items-center gap-2">
                                <span class="material-symbols-outlined">table_chart</span>
                                <span class="font-label-caps text-sm" x-text="model.name"></span>
                            </span>
                            <span class="material-symbols-outlined" x-text="(activeModelDict === model.class || dictSearch) ? 'expand_less' : 'expand_more'"></span>
                        </button>
                        <div x-show="activeModelDict === model.class || dictSearch" class="mt-2 space-y-1 ml-4" x-collapse>
                            <template x-for="col in model.displayColumns" :key="col">
                                <div class="flex items-center justify-between p-2 rounded cursor-pointer text-sm transition-colors" 
                                     :class="isAttributeSelected(model.class, col) ? 'bg-primary/10 border-l-2 border-primary text-primary font-semibold' : 'hover:bg-white/40 text-on-surface-variant'" 
                                     @click="addAttribute(model.class, col)">
                                    <span :class="col.startsWith('va:') ? 'italic text-primary' : ''" x-text="col.startsWith('va:') ? col.substring(3) : col"></span>
                                    <template x-if="col.startsWith('va:')">
                                        <span class="material-symbols-outlined text-[18px] text-primary" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
                                    </template>
                                    <template x-if="!col.startsWith('va:')">
                                        <span class="material-symbols-outlined text-[16px] text-opacity-50" :class="isAttributeSelected(model.class, col) ? 'text-primary' : 'text-outline'" x-text="isAttributeSelected(model.class, col) ? 'check' : 'add'"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </section>
        
        <!-- Right Builder Canvas -->
        <section class="flex-1 h-full overflow-y-auto bg-surface-container-low/30 p-6 relative pb-32">
            
            <!-- Error/Success Notification -->
            <div x-show="message" x-transition class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 px-6 py-3 rounded-full shadow-lg text-white font-label-caps"
                 :class="messageType === 'success' ? 'bg-green-500' : 'bg-red-500'">
                <span x-text="message"></span>
            </div>

            <!-- Selection Area: Base & Target Models -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="space-y-3">
                    <label class="font-label-caps text-secondary">Base Model</label>
                    <div class="relative">
                        <select x-model="payload.baseModel" @change="payload.selectedAttributes = []; payload.targetModels = []; payload.innerFilters.children = []; payload.outerFilters.children = [];" class="w-full bg-white border border-outline-variant/40 rounded-xl px-4 py-3 appearance-none !bg-none focus:ring-2 focus:ring-primary/20 outline-none">
                            <option value="" disabled>Select Base Model</option>
                            <template x-for="model in availableModels" :key="model.class">
                                <option :value="model.class" x-text="model.name"></option>
                            </template>
                        </select>
                        <span class="material-symbols-outlined absolute right-4 top-3.5 text-secondary pointer-events-none">expand_more</span>
                    </div>
                </div>
                <div class="space-y-3">
                    <label class="font-label-caps text-secondary">Target Models (Multi-Select)</label>
                    <div class="flex flex-wrap gap-2 p-2 bg-white border border-outline-variant/40 rounded-xl min-h-[50px] items-center">
                        <template x-for="(tm, index) in payload.targetModels" :key="index">
                            <span class="px-3 py-1 bg-primary/10 text-primary rounded-full text-xs font-semibold flex items-center gap-1">
                                <span x-text="getModelName(tm)"></span> 
                                <span @click="removeTargetModel(index)" class="material-symbols-outlined text-[14px] cursor-pointer">close</span>
                            </span>
                        </template>
                        <select x-model="newTargetModel" @change="addTargetModel" class="border-none bg-transparent text-outline text-xs px-2 cursor-pointer focus:ring-0 outline-none max-w-[150px]">
                            <option value="">+ Add Model</option>
                            <template x-for="model in availableModels" :key="'target_' + model.class">
                                <option :value="model.class" x-text="model.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Builder Blocks -->
            <div x-show="payload.baseModel" class="space-y-4" x-cloak x-data="{ activeAccordion: 1 }">
                <!-- 1. Selected Columns -->
                <div class="glass-panel rounded-2xl shadow-sm overflow-hidden border border-white/40">
                    <button @click="activeAccordion = activeAccordion === 1 ? null : 1" class="w-full flex justify-between items-center p-4 bg-white/30 hover:bg-white/50 transition-colors">
                        <h3 class="font-headline-lg text-[16px] text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">view_column</span> 1. Selected Columns
                        </h3>
                        <span class="material-symbols-outlined text-secondary" x-text="activeAccordion === 1 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeAccordion === 1" class="p-5 border-t border-white/40 bg-white/10" x-collapse>
                        <div class="flex flex-wrap gap-4">
                            <template x-for="(attr, index) in payload.selectedAttributes" :key="index">
                                <div class="bg-white pl-5 pr-3 py-2.5 rounded-full border border-primary/20 shadow-sm flex items-center gap-3">
                                    <span class="font-label-caps text-secondary/70 text-xs" x-text="getModelName(attr.model)"></span>
                                    <span class="font-data-tabular font-medium text-sm text-on-surface" x-text="attr.column.startsWith('va:') ? attr.column.substring(3) : attr.column"></span>
                                    <span class="font-label-caps text-secondary/50 text-[11px] mx-1">AS</span>
                                    <input type="text" x-model="attr.alias" class="font-data-tabular text-primary text-sm bg-surface-container-lowest border border-outline-variant/40 rounded-full px-3 py-1.5 w-40 focus:ring-1 focus:ring-primary/30 outline-none" placeholder="Alias..." />
                                    <span @click="removeAttribute(index)" class="material-symbols-outlined text-error text-[20px] cursor-pointer ml-1 hover:bg-error/10 rounded-full p-1 transition-colors">close</span>
                                </div>
                            </template>
                            <div x-show="payload.selectedAttributes.length === 0" class="text-sm text-secondary italic">
                                Click columns in the Data Dictionary to add them here.
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 2. Select Filters (WHERE) -->
                <div class="glass-panel rounded-2xl shadow-sm overflow-hidden border border-white/40">
                    <button @click="activeAccordion = activeAccordion === 2 ? null : 2" class="w-full flex justify-between items-center p-4 bg-white/30 hover:bg-white/50 transition-colors">
                        <h3 class="font-headline-lg text-[16px] text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">filter_alt</span> 2. Select Filters (WHERE)
                        </h3>
                        <span class="material-symbols-outlined text-secondary" x-text="activeAccordion === 2 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeAccordion === 2" class="p-5 border-t border-white/40 bg-white/10 flex flex-col gap-6" x-collapse>
                        <div x-data="{ node: payload.innerFilters, parentList: null, index: null, isOuter: false }">
                            @include('admin.partials.filter_node')
                        </div>
                    </div>
                </div>

                <!-- 3. Grouping Accordion -->
                <div class="glass-panel rounded-2xl shadow-sm overflow-hidden border border-white/40">
                    <button @click="activeAccordion = activeAccordion === 3 ? null : 3" class="w-full flex justify-between items-center p-4 bg-white/30 hover:bg-white/50 transition-colors">
                        <h3 class="font-headline-lg text-[16px] text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">functions</span> 3. Grouping & Aggregations
                        </h3>
                        <span class="material-symbols-outlined text-secondary" x-text="activeAccordion === 3 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeAccordion === 3" class="p-5 border-t border-white/40 bg-white/10 flex flex-col gap-6" x-collapse>
                        
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-label-caps text-secondary text-xs">Group By</h4>
                                <button @click="addGroupBy" class="text-primary font-label-caps text-[10px] flex items-center gap-1 hover:underline">
                                    <span class="material-symbols-outlined text-[14px]">add_circle</span> Add Group
                                </button>
                            </div>
                            <div class="space-y-2">
                                <template x-for="(gb, index) in payload.groupBys" :key="index">
                                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-outline-variant/20 flex-nowrap w-full">
                                        <select x-model="gb.model" @change="gb.column = ''" class="border-outline-variant rounded p-1.5 text-xs w-1/2">
                                            <option value="">Model</option>
                                            <template x-for="m in getDictionaryModels()" :key="'gm_'+m.class">
                                                <option :value="m.class" x-text="m.name"></option>
                                            </template>
                                        </select>
                                        <select x-model="gb.column" class="border-outline-variant rounded p-1.5 text-xs flex-1">
                                            <option value="">Column</option>
                                            <template x-if="gb.model">
                                                <template x-for="c in getModelColumns(gb.model)" :key="'gc_'+c">
                                                    <option :value="c" x-text="c"></option>
                                                </template>
                                            </template>
                                        </select>
                                        <span @click="removeGroupBy(index)" class="material-symbols-outlined text-error text-[16px] cursor-pointer hover:bg-error/10 rounded-full p-1">delete</span>
                                    </div>
                                </template>
                                <div x-show="payload.groupBys.length === 0" class="text-xs text-secondary italic">No groupings.</div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-label-caps text-secondary text-xs">Aggregations</h4>
                                <button @click="addAggregate" class="text-primary font-label-caps text-[10px] flex items-center gap-1 hover:underline">
                                    <span class="material-symbols-outlined text-[14px]">add_circle</span> Add Agg
                                </button>
                            </div>
                            <div class="space-y-2">
                                <template x-for="(agg, index) in payload.aggregates" :key="index">
                                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-outline-variant/20 flex-nowrap w-full">
                                        <select x-model="agg.function" class="border-outline-variant rounded p-1.5 text-xs w-1/6">
                                            <option value="SUM">SUM</option>
                                            <option value="COUNT">COUNT</option>
                                            <option value="AVG">AVG</option>
                                            <option value="MAX">MAX</option>
                                            <option value="MIN">MIN</option>
                                        </select>
                                        <select x-model="agg.model" @change="agg.column = ''" class="border-outline-variant rounded p-1.5 text-xs w-1/4">
                                            <option value="">Model</option>
                                            <template x-for="m in getDictionaryModels()" :key="'am_'+m.class">
                                                <option :value="m.class" x-text="m.name"></option>
                                            </template>
                                        </select>
                                        <select x-model="agg.column" class="border-outline-variant rounded p-1.5 text-xs w-1/4">
                                            <option value="">Column</option>
                                            <template x-if="agg.model">
                                                <template x-for="c in getModelColumns(agg.model)" :key="'ac_'+c">
                                                    <option :value="c" x-text="c"></option>
                                                </template>
                                            </template>
                                        </select>
                                        <input x-model="agg.alias" type="text" class="border-outline-variant rounded p-1.5 text-xs flex-1" placeholder="Alias" />
                                        <span @click="removeAggregate(index)" class="material-symbols-outlined text-error text-[16px] cursor-pointer hover:bg-error/10 rounded-full p-1">delete</span>
                                    </div>
                                </template>
                                <div x-show="payload.aggregates.length === 0" class="text-xs text-secondary italic">No aggregations.</div>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- 4. Having Accordion -->
                <div class="glass-panel rounded-2xl shadow-sm overflow-hidden border border-white/40">
                    <button @click="activeAccordion = activeAccordion === 4 ? null : 4" class="w-full flex justify-between items-center p-4 bg-white/30 hover:bg-white/50 transition-colors">
                        <h3 class="font-headline-lg text-[16px] text-tertiary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">filter_list</span> 4. Having (Post-Filters)
                        </h3>
                        <span class="material-symbols-outlined text-secondary" x-text="activeAccordion === 4 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeAccordion === 4" class="p-5 border-t border-white/40 bg-white/10 flex flex-col gap-6" x-collapse>

                        <!-- Post-Aggregation Filters (HAVING) -->
                        <div x-data="{ node: payload.outerFilters, parentList: null, index: null, isOuter: true }">
                            @include('admin.partials.filter_node')
                        </div>

                    </div>
                </div>

                <!-- 5. Order By Accordion -->
                <div class="glass-panel rounded-2xl shadow-sm overflow-hidden border border-white/40">
                    <button @click="activeAccordion = activeAccordion === 5 ? null : 5" class="w-full flex justify-between items-center p-4 bg-white/30 hover:bg-white/50 transition-colors">
                        <h3 class="font-headline-lg text-[16px] text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">sort</span> 5. Order By
                        </h3>
                        <span class="material-symbols-outlined text-secondary" x-text="activeAccordion === 5 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeAccordion === 5" class="p-5 border-t border-white/40 bg-white/10" x-collapse>
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="font-label-caps text-secondary text-xs">Sort Instructions</h4>
                            <button @click="addSort" class="text-primary font-label-caps text-[10px] flex items-center gap-1 hover:underline">
                                <span class="material-symbols-outlined text-[14px]">add_circle</span> Add Sort
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(sort, index) in payload.sorts" :key="index">
                                <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-outline-variant/20 flex-nowrap w-full">
                                    <select x-model="sort.column" class="border-outline-variant rounded p-1.5 text-xs flex-1">
                                        <option value="">Generated Column</option>
                                        <template x-for="c in generatedColumns" :key="'sc_'+c">
                                            <option :value="c" x-text="c"></option>
                                        </template>
                                    </select>
                                    <select x-model="sort.direction" class="border-outline-variant rounded p-1.5 text-xs w-1/4">
                                        <option value="ASC">ASC</option>
                                        <option value="DESC">DESC</option>
                                    </select>
                                    <span @click="removeSort(index)" class="material-symbols-outlined text-error text-[16px] cursor-pointer hover:bg-error/10 rounded-full p-1">delete</span>
                                </div>
                            </template>
                            <div x-show="payload.sorts.length === 0" class="text-xs text-secondary italic">No sorts applied.</div>
                        </div>
                    </div>
                </div>

                <!-- 6. Data Preview Accordion -->
                <div x-show="rawSql !== ''" class="glass-panel rounded-2xl shadow-sm overflow-hidden border border-white/40 mb-10">
                    <button @click="activeAccordion = activeAccordion === 6 ? null : 6" class="w-full flex justify-between items-center p-4 bg-white/30 hover:bg-white/50 transition-colors">
                        <h3 class="font-headline-lg text-[16px] text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">table_chart</span> 6. Data Preview
                        </h3>
                        <span class="material-symbols-outlined text-secondary" x-text="activeAccordion === 6 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeAccordion === 6" class="p-5 border-t border-white/40 bg-white/10" x-collapse>
                        <div x-show="results && results.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-surface-container-low border-b border-outline-variant/30">
                                        <template x-for="col in resultColumns" :key="col">
                                            <th class="p-3 font-label-caps text-secondary" x-text="col"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, i) in results" :key="i">
                                        <tr class="border-b border-outline-variant/10 hover:bg-white/40">
                                            <template x-for="col in resultColumns" :key="col">
                                                <td class="p-3 font-data-tabular text-sm" x-text="row[col]"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div x-show="!results || results.length === 0" class="flex flex-col items-center justify-center py-12">
                            <span class="material-symbols-outlined text-[48px] text-secondary/30 mb-3">search_off</span>
                            <p class="text-secondary text-sm">No results match the selected filters.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer Toolbar -->
    <footer class="fixed bottom-0 right-0 w-[calc(100%-18rem)] px-10 flex justify-between items-center h-20 shrink-0 bg-surface-container-lowest/90 border-t border-outline-variant/30 backdrop-blur-md z-40">
        <div class="flex items-center gap-6 text-secondary text-sm">
            <template x-if="payload.baseModel">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        <span class="font-label-caps">AST Ready</span>
                    </span>
                    <button @click="showDebugger = true" class="px-4 py-1.5 rounded-full border border-outline-variant/60 text-secondary text-xs font-label-caps hover:bg-white hover:text-primary shadow-sm flex items-center gap-1.5 transition-all">
                        <span class="material-symbols-outlined text-[16px]">account_tree</span> View Inspector
                    </button>
                </div>
            </template>
        </div>
        <div class="flex items-center gap-4" x-show="payload.baseModel">
            <button @click="generatePreview" :disabled="isGenerating" class="px-8 py-3 rounded-full border border-primary text-primary font-label-caps hover:bg-primary/5 transition-all disabled:opacity-50 flex items-center gap-2">
                <span x-show="isGenerating" class="material-symbols-outlined animate-spin text-[18px]">sync</span>
                <span x-text="isGenerating ? 'Loading...' : 'Preview Data'"></span>
            </button>
            <button @click="saveReport" :disabled="isGenerating || isSaving" class="px-10 py-3 rounded-full bg-gradient-to-r from-primary to-[#a17c7b] text-white font-label-caps shadow-lg shadow-primary/20 hover:scale-[1.02] transition-transform flex items-center gap-2 disabled:opacity-50">
                <span x-show="isSaving" class="material-symbols-outlined animate-spin text-[18px]">sync</span>
                <span x-show="!isSaving" class="material-symbols-outlined text-[20px]">save</span>
                <span x-text="isSaving ? 'Saving...' : 'Save Report'"></span>
            </button>
        </div>
    </footer>

    <!-- Debugger Off-Canvas Panel -->
    <div x-show="showDebugger" class="fixed inset-0 z-[100] overflow-hidden" x-cloak>
        <div class="absolute inset-0 bg-on-primary-container/20 backdrop-blur-sm" @click="showDebugger = false" x-transition.opacity></div>
        <div class="absolute inset-y-0 right-0 w-[550px] bg-surface-container-lowest shadow-2xl flex flex-col border-l border-outline-variant/30 transform transition-transform"
             x-transition:enter="translate-x-full" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="translate-x-0" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
             
            <div class="flex justify-between items-center p-6 border-b border-outline-variant/30 bg-surface-container-low/50">
                <h3 class="font-headline-lg text-[18px] text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined">account_tree</span> Engine Inspector
                </h3>
                <button @click="showDebugger = false" class="text-secondary hover:text-primary rounded-full p-1 hover:bg-surface-container-lowest shadow-sm transition-colors border border-transparent hover:border-outline-variant/30">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1 space-y-4 bg-surface-container-lowest" x-data="{ activeInspectorAccordion: 1 }">
                <!-- 1. AST Payload Accordion -->
                <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
                    <button @click="activeInspectorAccordion = activeInspectorAccordion === 1 ? null : 1" class="w-full flex justify-between items-center p-4 hover:bg-surface-container-highest transition-colors">
                        <h4 class="font-label-caps text-secondary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">data_object</span> 1. AST Payload
                        </h4>
                        <span class="material-symbols-outlined text-secondary" x-text="activeInspectorAccordion === 1 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeInspectorAccordion === 1" class="p-4 border-t border-outline-variant/30 bg-white" x-collapse>
                        <pre class="text-[11px] text-[#d4d4d4] font-mono bg-[#1e1e1e] p-5 rounded-2xl overflow-x-auto shadow-inner" x-text="JSON.stringify(payload, null, 2)"></pre>
                    </div>
                </div>

                <!-- 2. Generated SQL Accordion -->
                <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
                    <button @click="activeInspectorAccordion = activeInspectorAccordion === 2 ? null : 2" class="w-full flex justify-between items-center p-4 hover:bg-surface-container-highest transition-colors">
                        <h4 class="font-label-caps text-secondary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">database</span> 2. Generated SQL
                        </h4>
                        <span class="material-symbols-outlined text-secondary" x-text="activeInspectorAccordion === 2 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeInspectorAccordion === 2" class="p-4 border-t border-outline-variant/30 bg-white" x-collapse>
                        <div class="text-[11px] text-[#d4d4d4] font-mono bg-[#1e1e1e] p-5 rounded-2xl overflow-x-auto whitespace-pre-wrap shadow-inner leading-relaxed" x-text="rawSql"></div>
                    </div>
                </div>

                <!-- 3. Join Plan Accordion -->
                <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm" x-show="joinPlan && joinPlan.length > 0">
                    <button @click="activeInspectorAccordion = activeInspectorAccordion === 3 ? null : 3" class="w-full flex justify-between items-center p-4 hover:bg-surface-container-highest transition-colors">
                        <h4 class="font-label-caps text-secondary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">route</span> 3. BFS Join Strategy
                        </h4>
                        <span class="material-symbols-outlined text-secondary" x-text="activeInspectorAccordion === 3 ? 'expand_less' : 'expand_more'"></span>
                    </button>
                    <div x-show="activeInspectorAccordion === 3" class="p-4 border-t border-outline-variant/30 bg-white" x-collapse>
                        <div class="space-y-3">
                            <template x-for="(step, i) in joinPlan" :key="i">
                                <div class="bg-surface-container-low border border-outline-variant/30 p-4 rounded-2xl flex items-start gap-4 hover:shadow-md transition-shadow">
                                    <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-sm" x-text="i+1"></div>
                                    <div class="flex-1">
                                        <div class="font-data-tabular text-sm font-bold text-primary tracking-wide" x-text="step.joinType + ' JOIN'"></div>
                                        <div class="text-[12px] text-on-surface-variant mt-1.5 font-mono bg-white px-2 py-1 rounded inline-block border border-outline-variant/20">
                                            <span class="text-primary font-bold" x-text="step.table"></span> 
                                            <span class="text-secondary/70 mx-1">ON</span> 
                                            <span x-text="step.foreignKey"></span> = <span x-text="step.ownerKey"></span>
                                        </div>
                                        <div class="text-[10px] text-secondary mt-2 font-label-caps flex items-center gap-1">
                                            <span class="px-1.5 py-0.5 bg-black/5 rounded" x-text="step.fromModel.split('\\').pop()"></span>
                                            <span class="material-symbols-outlined text-[12px]">arrow_forward</span>
                                            <span class="px-1.5 py-0.5 bg-primary/10 text-primary rounded" x-text="step.toModel.split('\\').pop()"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reportBuilder', () => ({
            availableModels: @json($modelOptions),
            newTargetModel: '',
            isGenerating: false,
            isSaving: false,
            message: '',
            messageType: 'success',
            
            payload: {
                baseModel: '',
                targetModels: [],
                selectedAttributes: [],
                innerFilters: { type: 'group', logic: 'AND', children: [] },
                groupBys: [],
                aggregates: [],
                outerFilters: { type: 'group', logic: 'AND', children: [] },
                sorts: []
            },

            results: [],
            resultColumns: [],
            rawSql: '',
            joinPlan: [],
            showDebugger: false,
            sidebarOpen: true,
            dictSearch: '',
            generatedColumns: [],

            init() {
                this.$watch('payload.selectedAttributes', () => this.simulateColumns(), { deep: true });
                this.$watch('payload.groupBys', () => this.simulateColumns(), { deep: true });
                this.$watch('payload.aggregates', () => this.simulateColumns(), { deep: true });
            },

            async simulateColumns() {
                if (!this.payload.baseModel) {
                    this.generatedColumns = [];
                    return;
                }
                
                try {
                    const response = await fetch('{{ route('builder.simulate_columns') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.payload)
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.generatedColumns = data.columns || [];
                    }
                } catch (e) {
                    console.error('Failed to simulate columns', e);
                }
            },

            showMessage(msg, type = 'success') {
                this.message = msg;
                this.messageType = type;
                setTimeout(() => { this.message = ''; }, 3000);
            },

            isAttributeSelected(modelClass, col) {
                return this.payload.selectedAttributes.some(a => a.model === modelClass && a.column === col);
            },

            getModelName(modelClass) {
                const model = this.availableModels.find(m => m.class === modelClass);
                return model ? model.name : modelClass;
            },

            getModelColumns(modelClass) {
                const model = this.availableModels.find(m => m.class === modelClass);
                return model ? model.columns : [];
            },

            getDictionaryModels() {
                if (!this.payload.baseModel) return [];
                const models = [this.payload.baseModel, ...this.payload.targetModels];
                return this.availableModels
                    .filter(m => models.includes(m.class))
                    .map(m => {
                        const filteredCols = this.dictSearch 
                            ? m.columns.filter(c => c.toLowerCase().includes(this.dictSearch.toLowerCase()))
                            : m.columns;
                        return { ...m, displayColumns: filteredCols };
                    })
                    .filter(m => m.displayColumns.length > 0);
            },

            addTargetModel() {
                if (this.newTargetModel && !this.payload.targetModels.includes(this.newTargetModel)) {
                    this.payload.targetModels.push(this.newTargetModel);
                }
                this.newTargetModel = '';
            },

            removeTargetModel(index) {
                const removedModel = this.payload.targetModels[index];
                this.payload.targetModels.splice(index, 1);
                
                // Remove attributes associated with this model
                this.payload.selectedAttributes = this.payload.selectedAttributes.filter(a => a.model !== removedModel);
                
                // Notice: We cannot easily filter innerFilters and outerFilters as they are now nested tree structures.
                // It is recommended the user manually deletes the rules that became invalid.
            },

            addAttribute(modelClass, column) {
                if (this.payload.selectedAttributes.some(a => a.model === modelClass && a.column === column)) return;
                
                const modelName = this.getModelName(modelClass).toLowerCase().replace(/[^a-z0-9_]/g, '');
                const rawColumn = column.startsWith('va:') ? column.substring(3) : column;
                
                this.payload.selectedAttributes.push({
                    model: modelClass,
                    column: column,
                    type: 'string',
                    alias: `${modelName}_${rawColumn}`
                });
            },

            removeAttribute(index) {
                this.payload.selectedAttributes.splice(index, 1);
            },

            // Recursive Filter Handlers
            addRuleToGroup(groupNode) {
                groupNode.children.push({
                    id: Date.now() + Math.random(),
                    type: 'leaf',
                    model: this.payload.baseModel,
                    column: '',
                    operator: '=',
                    value: '',
                    dataType: 'string'
                });
            },
            addGroupToGroup(groupNode) {
                groupNode.children.push({
                    id: Date.now() + Math.random(),
                    type: 'group',
                    logic: 'AND',
                    children: []
                });
            },
            removeNodeFromList(list, index) {
                list.splice(index, 1);
            },
            
            addAggregate() {
                this.payload.aggregates.push({
                    model: this.payload.baseModel,
                    column: '',
                    function: 'SUM',
                    type: 'number',
                    alias: ''
                });
            },
            removeAggregate(index) {
                this.payload.aggregates.splice(index, 1);
            },
            
            addGroupBy() {
                this.payload.groupBys.push({
                    model: this.payload.baseModel,
                    column: '',
                    type: 'string'
                });
            },
            removeGroupBy(index) {
                this.payload.groupBys.splice(index, 1);
            },

            addSort() {
                this.payload.sorts.push({
                    model: this.payload.baseModel,
                    column: '',
                    direction: 'ASC',
                    type: 'string'
                });
            },
            removeSort(index) {
                this.payload.sorts.splice(index, 1);
            },

            async generatePreview() {
                this.isGenerating = true;
                this.results = [];
                this.rawSql = '';

                try {
                    const response = await fetch('{{ route('builder.generate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.payload)
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.results = data.results;
                        if (this.results && this.results.length > 0) {
                            this.resultColumns = Object.keys(this.results[0]);
                        }
                        this.activeAccordion = 6;
                        this.rawSql = data.sql;
                        this.joinPlan = data.join_plan;
                        this.showMessage('Preview generated successfully.');
                    } else {
                        this.showMessage(data.error || 'Failed to generate preview.', 'error');
                    }
                } catch (error) {
                    this.showMessage('Network error occurred.', 'error');
                } finally {
                    this.isGenerating = false;
                }
            },

            async saveReport() {
                const name = prompt("Enter a name for this report:");
                if (!name) return;

                this.isSaving = true;
                try {
                    const response = await fetch('{{ route('builder.save') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            name: name,
                            description: 'Created via Visual Designer',
                            payload: this.payload
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showMessage(data.message);
                    } else {
                        this.showMessage(data.error || 'Failed to save report.', 'error');
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
@endsection
