<div class="filter-node border border-outline-variant/30 rounded-xl p-3 bg-white/50 mb-2 relative ml-4 border-l-4" :class="node.logic === 'AND' ? 'border-l-primary/40' : 'border-l-tertiary/40'">
    
    <!-- Group Header -->
    <template x-if="node.type === 'group'">
        <div class="flex items-center justify-between mb-3 bg-surface-container-low p-2 rounded-lg">
            <div class="flex items-center gap-2">
                <div class="flex items-center bg-white border border-outline-variant/50 rounded-md p-0.5 shadow-sm">
                    <button @click.prevent="node.logic = 'AND'" 
                            :class="node.logic === 'AND' ? 'bg-primary/10 text-primary font-bold shadow-sm' : 'text-secondary hover:bg-surface-container-low'"
                            class="px-3 py-1 text-[10px] rounded transition-all">AND</button>
                    <button @click.prevent="node.logic = 'OR'" 
                            :class="node.logic === 'OR' ? 'bg-tertiary/10 text-tertiary font-bold shadow-sm' : 'text-secondary hover:bg-surface-container-low'"
                            class="px-3 py-1 text-[10px] rounded transition-all">OR</button>
                </div>
                <span class="text-[10px] font-label-caps text-secondary uppercase tracking-wider">Group</span>
            </div>
            
            <div class="flex items-center gap-2">
                <button @click.prevent="addRuleToGroup(node)" class="text-primary font-label-caps text-[10px] flex items-center gap-1 hover:bg-primary/10 px-2 py-1 rounded transition-colors">
                    <span class="material-symbols-outlined text-[14px]">add</span> Rule
                </button>
                <button @click.prevent="addGroupToGroup(node)" class="text-tertiary font-label-caps text-[10px] flex items-center gap-1 hover:bg-tertiary/10 px-2 py-1 rounded transition-colors">
                    <span class="material-symbols-outlined text-[14px]">library_add</span> Group
                </button>
                <template x-if="parentList">
                    <button @click.prevent="removeNodeFromList(parentList, index)" class="text-error font-label-caps text-[10px] flex items-center gap-1 hover:bg-error/10 px-2 py-1 rounded transition-colors ml-2 border-l border-outline-variant/30 pl-3">
                        <span class="material-symbols-outlined text-[14px]">delete</span>
                    </button>
                </template>
            </div>
        </div>
    </template>
    
    <!-- Group Children Container -->
    <template x-if="node.type === 'group'">
        <div class="space-y-2 mt-2">
            <template x-for="(child, childIndex) in node.children" :key="child.id || childIndex">
                <div>
                    <!-- Render Leaf -->
                    <template x-if="child.type === 'leaf'">
                        <div class="flex items-center gap-2 bg-white border border-outline-variant p-2 rounded-lg flex-nowrap w-full group hover:border-primary/30 transition-colors shadow-sm">
                            
                            <template x-if="!isOuter">
                                <div class="flex gap-2 w-1/2">
                                    <select x-model="child.model" @change="child.column = ''" class="border-outline-variant rounded p-1.5 text-xs w-1/2 focus:ring-1 focus:ring-primary/20">
                                        <option value="">Select Model...</option>
                                        <template x-for="m in getDictionaryModels()" :key="'fm_'+m.class">
                                            <option :value="m.class" x-text="m.name"></option>
                                        </template>
                                    </select>
                                    <select x-model="child.column" class="border-outline-variant rounded p-1.5 text-xs w-1/2 focus:ring-1 focus:ring-primary/20">
                                        <option value="">Select Column...</option>
                                        <template x-if="child.model">
                                            <template x-for="c in getModelColumns(child.model)" :key="'fc_'+c">
                                                <option :value="c" x-text="c"></option>
                                            </template>
                                        </template>
                                    </select>
                                </div>
                            </template>

                            <template x-if="isOuter">
                                <select x-model="child.column" class="border-outline-variant rounded p-1.5 text-xs w-1/3 focus:ring-1 focus:ring-primary/20">
                                    <option value="">Select Generated Column...</option>
                                    <template x-for="c in generatedColumns" :key="'ofc_'+c">
                                        <option :value="c" x-text="c"></option>
                                    </template>
                                </select>
                            </template>

                            <select x-model="child.operator" class="border-outline-variant rounded p-1.5 text-xs w-20 font-mono font-bold text-center focus:ring-1 focus:ring-primary/20">
                                <option value="=">=</option>
                                <option value="!=">!=</option>
                                <option value=">">&gt;</option>
                                <option value="<">&lt;</option>
                                <template x-if="!isOuter">
                                    <option value="LIKE">LIKE</option>
                                    <option value="IN">IN</option>
                                </template>
                            </select>
                            
                            <input x-model="child.value" type="text" class="border-outline-variant rounded p-1.5 text-xs flex-1 focus:ring-1 focus:ring-primary/20 font-data-tabular" placeholder="Enter value..." />
                            
                            <button @click.prevent="removeNodeFromList(node.children, childIndex)" class="material-symbols-outlined text-outline cursor-pointer hover:text-error hover:bg-error/10 rounded-full p-1.5 text-[16px] transition-colors shrink-0">delete</button>
                        </div>
                    </template>
                    
                    <!-- Render Sub-Group recursively using x-html or another included template.
                         Wait, Alpine doesn't support recursive templates natively without an external component!
                         Actually, we can use a recursive Blade include if we pass the context.
                         Since Alpine state is accessible everywhere, we can just render a fixed depth (e.g. 3 levels) OR use an Alpine component trick.
                         To avoid Alpine recursion issues, we can just include the Blade view 3 times.
                    -->
                    <template x-if="child.type === 'group'">
                        <div x-data="{ node: child, parentList: node.children, index: childIndex }">
                            @if (($depth ?? 0) < config('dynamicreportgenerator.ui.max_filter_depth', 3))
                                @include('admin.partials.filter_node', ['depth' => ($depth ?? 0) + 1])
                            @else
                                <div class="p-2 text-error text-xs">Maximum nesting depth reached for UI.</div>
                            @endif
                        </div>
                    </template>
                </div>
            </template>
            <div x-show="node.children.length === 0" class="text-[11px] text-secondary italic py-2 px-4 bg-black/5 rounded text-center border border-dashed border-outline-variant/40">
                Empty group. Click "Rule" or "Group" above to add conditions.
            </div>
        </div>
    </template>
</div>
