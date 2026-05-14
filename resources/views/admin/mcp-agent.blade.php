@extends('layouts.admin')

@section('header_title', 'Agentic Reporting Interface')

@section('content')
<div class="flex h-[calc(100vh-80px)] overflow-hidden" x-data="mcpAgent()">
    <!-- Chat Sidebar (Left) -->
    <div class="w-[500px] bg-white/50 backdrop-blur-md border-r border-outline-variant/30 flex flex-col relative shadow-lg shrink-0">
        <!-- Chat Header -->
        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface/50 backdrop-blur shrink-0 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shadow-inner">
                    <span class="material-symbols-outlined">smart_toy</span>
                </div>
                <div>
                    <h3 class="font-headline-lg text-lg text-primary leading-tight">Data Agent</h3>
                    <p class="font-label-caps text-xs text-secondary opacity-80 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Online
                    </p>
                </div>
            </div>
            
            <button @click="clearChat" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-black/5 text-secondary transition-colors" title="Clear Chat">
                <span class="material-symbols-outlined text-[20px]">delete_sweep</span>
            </button>
        </div>

        <!-- Chat History -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6" id="chat-messages">
            <template x-for="(msg, index) in messages" :key="index">
                <div>
                    <template x-if="msg.role === 'status'">
                        <div class="flex justify-center w-full my-4">
                            <div class="px-4 py-1.5 bg-black/5 text-secondary rounded-full text-[11px] font-label-caps tracking-wider border border-outline-variant/30 flex items-center gap-1.5 shadow-sm">
                                <span class="material-symbols-outlined text-[14px]">info</span>
                                <span x-text="msg.content"></span>
                            </div>
                        </div>
                    </template>
                    <template x-if="msg.role !== 'status'">
                        <div class="flex flex-col max-w-[95%] mb-6" :class="msg.role === 'user' ? 'ml-auto items-end' : 'mr-auto items-start'">
                            <div class="flex items-center gap-2 mb-1 px-1">
                                <span class="material-symbols-outlined text-[14px] text-secondary opacity-70" x-text="msg.role === 'user' ? 'person' : 'smart_toy'"></span>
                                <span class="font-label-caps text-[10px] text-secondary opacity-70" x-text="msg.role === 'user' ? 'You' : 'Agent'"></span>
                            </div>
                            <div class="px-5 py-3 rounded-2xl shadow-sm text-[15px] leading-relaxed"
                                 :class="msg.role === 'user' ? 'bg-primary text-white rounded-br-sm' : 'bg-white border border-outline-variant/20 text-on-surface rounded-bl-sm'">
                                <div class="markdown-body" x-html="renderMarkdown(msg.content)"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
            
            <!-- Loading Indicator -->
            <div x-show="isLoading" class="flex flex-col mr-auto max-w-[85%]">
                 <div class="flex items-center gap-2 mb-1 px-1">
                    <span class="material-symbols-outlined text-[14px] text-secondary opacity-70">smart_toy</span>
                    <span class="font-label-caps text-[10px] text-secondary opacity-70">Agent</span>
                </div>
                <div class="px-5 py-4 bg-white border border-outline-variant/20 rounded-2xl rounded-bl-sm shadow-sm flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary/40 animate-bounce" style="animation-delay: 0ms;"></span>
                    <span class="w-2 h-2 rounded-full bg-primary/60 animate-bounce" style="animation-delay: 150ms;"></span>
                    <span class="w-2 h-2 rounded-full bg-primary/80 animate-bounce" style="animation-delay: 300ms;"></span>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white/80 backdrop-blur-md border-t border-outline-variant/20 shrink-0">
            <form @submit.prevent="sendMessage" class="relative">
                <textarea 
                    x-model="input" 
                    @keydown.enter.prevent="if(!isLoading && input.trim()) { sendMessage() }"
                    rows="1"
                    placeholder="Ask about your data..."
                    class="w-full bg-surface-container/50 border border-outline-variant/50 rounded-2xl pl-5 pr-14 py-3 text-body-md focus:ring-2 focus:ring-primary/20 focus:border-primary/40 outline-none resize-none transition-all shadow-inner"
                    style="min-height: 52px; max-height: 120px;"
                ></textarea>
                <button type="submit" 
                        :disabled="isLoading || !input.trim()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-300"
                        :class="(isLoading || !input.trim()) ? 'bg-outline-variant/20 text-secondary cursor-not-allowed' : 'rose-gold-btn text-white shadow-md hover:shadow-lg'">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">send</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Inspector Panels (Right) -->
    <div class="flex-1 flex flex-col p-6 gap-6 overflow-y-auto bg-surface-container-low/30 relative">
        <!-- Initial Empty State -->
        <div x-show="!currentData && !currentSql && !currentAst && !isLoading" class="absolute inset-0 flex flex-col items-center justify-center opacity-40 pointer-events-none">
            <span class="material-symbols-outlined text-[80px] text-primary mb-4">analytics</span>
            <p class="font-display-md text-2xl text-primary font-bold">Waiting for Query</p>
            <p class="font-body-md text-secondary mt-2">Ask a question to generate a report.</p>
        </div>

        <!-- Data Table Panel -->
        <div x-show="currentData" x-cloak class="bento-card glass p-6 rounded-2xl flex-1 flex flex-col min-h-[400px]">
            <div class="flex justify-between items-center mb-6 shrink-0">
                <h4 class="font-label-caps text-primary tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">table_chart</span> RESULT SET
                </h4>
                <div class="flex items-center gap-3">
                    <span class="font-data-tabular text-xs text-secondary px-3 py-1 bg-black/5 rounded-full" x-text="currentData ? currentData.length + ' records' : ''"></span>
                    <button class="px-4 py-1.5 rounded-full border border-primary/30 text-primary font-label-caps hover:bg-primary/5 transition-colors text-[10px]">EXPORT CSV</button>
                    <button @click="isInspectorOpen = true" x-show="currentSql || currentAst" class="px-4 py-1.5 rounded-full border border-outline-variant/30 text-secondary font-label-caps hover:bg-black/5 transition-colors text-[10px] flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">code</span> INSPECTOR
                    </button>
                </div>
            </div>
            
            <div class="flex-1 overflow-auto rounded-xl border border-outline-variant/30 shadow-inner bg-white/40">
                <template x-if="currentData && currentData.length > 0">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead class="sticky top-0 bg-white/90 backdrop-blur shadow-sm z-10">
                            <tr>
                                <template x-for="col in dataColumns" :key="col">
                                    <th class="px-6 py-4 text-xs font-label-caps text-secondary tracking-wider" x-text="col"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <template x-for="(row, i) in currentData" :key="i">
                                <tr class="hover:bg-white/60 transition-colors">
                                    <template x-for="col in dataColumns" :key="col">
                                        <td class="px-6 py-3 font-data-tabular text-[13px] text-on-surface">
                                            <span x-text="formatCell(row[col], col)" :class="row[col] === '***' ? 'text-orange-500 font-bold' : ''"></span>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>
                
                <template x-if="currentData && currentData.length === 0">
                    <div class="flex flex-col items-center justify-center h-full min-h-[200px] text-secondary opacity-70">
                        <span class="material-symbols-outlined text-[48px] mb-3">search_off</span>
                        <p class="font-body-md text-sm">No records returned for this query.</p>
                        <p class="text-xs mt-1">Try adjusting the filters or time period.</p>
                    </div>
                </template>
            </div>
        </div>

    </div>

    <!-- Off-canvas Inspector Drawer -->
    <div x-show="isInspectorOpen" x-cloak class="absolute inset-0 z-50 flex justify-end" style="background: rgba(0,0,0,0.2); backdrop-filter: blur(2px);" @click.self="isInspectorOpen = false">
        <div class="w-[600px] h-full bg-surface shadow-2xl border-l border-outline-variant/30 flex flex-col"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-x-full"
             x-transition:enter-end="transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-x-0"
             x-transition:leave-end="transform translate-x-full">
             
             <div class="px-6 py-4 border-b border-outline-variant/20 flex justify-between items-center bg-white/50 backdrop-blur shrink-0">
                 <h3 class="font-headline-lg text-lg text-primary flex items-center gap-2">
                     <span class="material-symbols-outlined">code</span> Technical Inspector
                 </h3>
                 <button @click="isInspectorOpen = false" class="w-8 h-8 rounded-full hover:bg-black/5 flex items-center justify-center text-secondary transition-colors">
                     <span class="material-symbols-outlined">close</span>
                 </button>
             </div>
             
             <div class="flex-1 overflow-y-auto p-6 flex flex-col gap-6 bg-surface-container-low/50">
                <!-- SQL Viewer -->
                <div x-show="currentSql" class="flex flex-col">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-label-caps text-secondary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">database</span> GENERATED SQL
                        </h4>
                        <button @click="copyToClipboard(currentSql)" class="text-secondary hover:text-primary transition-colors" title="Copy SQL">
                            <span class="material-symbols-outlined text-[18px]">content_copy</span>
                        </button>
                    </div>
                    <div class="bg-[#1E1E1E] rounded-xl p-4 shadow-inner">
                        <pre class="font-data-tabular text-[13px] text-[#D4D4D4] whitespace-pre-wrap leading-relaxed" x-text="currentSql"></pre>
                    </div>
                </div>

                <!-- AST Viewer -->
                <div x-show="currentAst" class="flex flex-col">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-label-caps text-secondary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">account_tree</span> AST PAYLOAD
                        </h4>
                        <button @click="copyToClipboard(JSON.stringify(currentAst, null, 2))" class="text-secondary hover:text-primary transition-colors" title="Copy AST">
                            <span class="material-symbols-outlined text-[18px]">content_copy</span>
                        </button>
                    </div>
                    <div class="bg-[#1E1E1E] rounded-xl p-4 shadow-inner">
                        <pre class="font-data-tabular text-[13px] text-[#9CDCFE] whitespace-pre-wrap leading-relaxed" x-text="JSON.stringify(currentAst, null, 2)"></pre>
                    </div>
                </div>
             </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Include marked.js for Markdown parsing -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mcpAgent', () => ({
        input: '',
        isLoading: false,
        isInspectorOpen: false,
        messages: [
            { role: 'assistant', content: "Hello! I'm your Data Agent. Ask me to generate reports, and I'll automatically join the tables, apply Attribute Level Security, and visualize the results." }
        ],
        currentData: null,
        currentSql: null,
        currentAst: null,
        dataColumns: [],

        async sendMessage() {
            if (!this.input.trim()) return;

            const userMsg = this.input.trim();
            this.input = '';
            this.messages.push({ role: 'user', content: userMsg });
            this.isLoading = true;
            
            // Clear previous results while loading
            this.currentData = null;
            this.currentSql = null;
            this.currentAst = null;
            this.dataColumns = [];

            // Scroll to bottom
            this.scrollToBottom();

            try {
                // Build history payload for context (last 10 messages)
                const history = this.messages.slice(-10);

                const response = await fetch('{{ route("admin.mcp_agent.chat") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        message: userMsg,
                        history: history,
                        role_id: {{ auth()->check() ? auth()->user()->role_id : 1 }}
                    })
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const result = await response.json();

                // Append Agent Reply
                if (result.reply) {
                    this.messages.push({ role: 'assistant', content: result.reply });
                }

                // Append Tool Status Messages
                if (result.tools_used && result.tools_used.length > 0) {
                    result.tools_used.forEach(tool => {
                        if (tool.name === 'register_virtual_attribute' && tool.success) {
                            const name = tool.args.name || 'unknown';
                            const model = tool.args.model || 'unknown';
                            this.messages.push({ role: 'status', content: `Created virtual attribute ${name} attached to ${model}.` });
                        }
                        if (tool.name === 'save_report' && tool.success) {
                            const name = tool.args.name || 'unknown';
                            this.messages.push({ role: 'status', content: `Saved report as "${name}".` });
                        }
                    });
                }

                // Update Inspectors
                if (result.data) {
                    this.currentData = result.data;
                    this.dataColumns = result.data.length > 0 ? Object.keys(result.data[0]) : [];
                }
                
                if (result.sql) this.currentSql = result.sql;
                if (result.ast) this.currentAst = result.ast;

            } catch (error) {
                console.error("Agent Error:", error);
                this.messages.push({ role: 'assistant', content: "⚠️ Sorry, I encountered an error communicating with the orchestration engine." });
            } finally {
                this.isLoading = false;
                this.scrollToBottom();
            }
        },

        clearChat() {
            this.messages = [
                { role: 'assistant', content: "Chat cleared. How can I help you?" }
            ];
            this.currentData = null;
            this.currentSql = null;
            this.currentAst = null;
            this.isInspectorOpen = false;
            this.dataColumns = [];
        },

        renderMarkdown(content) {
            return marked.parse(content);
        },

        formatCell(value, column) {
            if (value === '***') return '***';
            if (value === null || value === undefined) return '-';
            
            // Format currency if column sounds like money
            const colLower = column.toLowerCase();
            const isCurrency = (colLower.includes('amount') || colLower.includes('price') || colLower.includes('revenue') || colLower.includes('total')) 
                            && !colLower.includes('quantity') && !colLower.includes('qty') && !colLower.includes('count');
            
            if (isCurrency && !isNaN(value)) {
                return '$' + Number(value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
            
            return value;
        },

        scrollToBottom() {
            setTimeout(() => {
                const chatContainer = document.getElementById('chat-messages');
                if (chatContainer) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            }, 50);
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            // Could add a toast notification here
        }
    }));
});
</script>

<style>
/* Markdown styling */
.markdown-body h3 { font-size: 1.1em; font-weight: bold; margin-top: 10px; margin-bottom: 5px; }
.markdown-body p { margin-bottom: 8px; }
.markdown-body p:last-child { margin-bottom: 0; }
.markdown-body ul { list-style-type: disc; padding-left: 20px; margin-bottom: 10px; }
.markdown-body table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 10px; font-size: 0.9em; }
.markdown-body th, .markdown-body td { border: 1px solid rgba(0,0,0,0.1); padding: 6px 10px; text-align: left; }
.markdown-body th { background: rgba(0,0,0,0.05); font-weight: 600; }
.markdown-body code { background: rgba(0,0,0,0.05); padding: 2px 4px; border-radius: 4px; font-family: monospace; font-size: 0.9em; }
[x-cloak] { display: none !important; }
</style>
@endpush
