<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Attribute Builder</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Alpine JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Inter', -apple-system, sans-serif; }
        .sql-textarea {
            font-family: monospace;
            background-color: #1e1e1e;
            color: #d4d4d4;
        }
        .btn-remove {
            padding: 0.1rem 0.4rem;
            font-size: 0.75rem;
        }
    </style>
</head>
<body x-data="vaBuilder()" class="bg-light text-dark py-4">
    
    <div class="container max-w-5xl mx-auto px-4">
        
        <div class="mb-4">
            <a href="{{ route('demo.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Back to Dashboard</a>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-5 fw-bold text-indigo" style="color: #4f46e5;">Virtual Attribute Playground</h1>
                <p class="text-muted">Interactively define and register powerful SQL subqueries directly into the registry.</p>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 border-top border-primary border-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 fw-bold">VA Builder</h5>
                    </div>
                    <div class="card-body">
                        
                        <div x-show="alert.show" :class="'alert alert-' + alert.type" role="alert" x-text="alert.message"></div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Attribute Name</label>
                            <input type="text" class="form-control form-control-lg" placeholder="e.g. total_order_value" x-model="payload.name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Base Model (The model this VA belongs to)</label>
                            <select class="form-select" x-model="payload.baseModel">
                                <option value="">-- Select Base Model --</option>
                                <template x-for="model in models" :key="model.class">
                                    <option :value="model.class" x-text="model.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label text-muted small fw-bold mb-0">Configuration Mode</label>
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="vabtnradio" id="vabtnradio1" autocomplete="off" value="visual" x-model="mode">
                                <label class="btn btn-outline-primary" for="vabtnradio1">Visual Builder</label>
                                
                                <input type="radio" class="btn-check" name="vabtnradio" id="vabtnradio2" autocomplete="off" value="advanced" x-model="mode">
                                <label class="btn btn-outline-primary" for="vabtnradio2">Advanced (SQL)</label>
                            </div>
                        </div>

                        <!-- Visual Mode -->
                        <div x-show="mode === 'visual'" class="bg-light p-4 rounded border mb-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-primary">1. What do you want to aggregate?</label>
                                <select class="form-select mb-2" x-model="visual.targetModel">
                                    <option value="">-- Select Target Model --</option>
                                    <template x-for="model in models" :key="model.class">
                                        <option :value="model.class" x-text="model.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="row g-2 mb-4" x-show="visual.targetModel" x-transition>
                                <div class="col-4">
                                    <label class="form-label small fw-bold text-secondary">Operation</label>
                                    <select class="form-select" x-model="visual.operation">
                                        <option value="">-- Function --</option>
                                        <option value="COUNT">COUNT</option>
                                        <option value="SUM">SUM</option>
                                        <option value="AVG">AVG</option>
                                        <option value="MAX">MAX</option>
                                        <option value="MIN">MIN</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold text-secondary">Target Column</label>
                                    <select class="form-select" x-model="visual.targetColumn">
                                        <option value="">-- Column --</option>
                                        <template x-for="col in getColumnsForModel(visual.targetModel)" :key="col">
                                            <option :value="col" x-text="col"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold text-secondary">Multiply By (Optional)</label>
                                    <select class="form-select" x-model="visual.multiplyColumn">
                                        <option value="">-- None --</option>
                                        <template x-for="col in getColumnsForModel(visual.targetModel)" :key="col">
                                            <option :value="col" x-text="col"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-2" x-show="visual.targetModel && payload.baseModel" x-transition>
                                <label class="form-label small fw-bold text-primary">2. How are they related?</label>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="small fw-bold">Where</span>
                                    <span class="badge bg-secondary" x-text="getModelName(visual.targetModel)"></span>
                                    <select class="form-select form-select-sm w-auto" x-model="visual.targetModelKey">
                                        <option value="">-- Foreign Key --</option>
                                        <template x-for="col in getColumnsForModel(visual.targetModel)" :key="col">
                                            <option :value="col" x-text="col"></option>
                                        </template>
                                    </select>
                                    <span class="fw-bold">=</span>
                                    <span class="badge bg-secondary" x-text="getModelName(payload.baseModel)"></span>
                                    <select class="form-select form-select-sm w-auto" x-model="visual.baseModelKey">
                                        <option value="">-- Local Key --</option>
                                        <template x-for="col in getColumnsForModel(payload.baseModel)" :key="col">
                                            <option :value="col" x-text="col"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Mode -->
                        <div x-show="mode === 'advanced'" class="bg-light p-4 rounded border mb-4">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">SQL Fragment</label>
                                <p class="text-muted small mb-2">Write the raw SQL subquery. Remember to wrap it in parentheses if it's a subselect.</p>
                                <textarea class="form-control sql-textarea" rows="6" placeholder="(SELECT SUM(quantity * unit_price) FROM order_items WHERE order_items.order_id = t0.id)" x-model="payload.sqlFragment"></textarea>
                            </div>

                            <div class="mb-2">
                                <label class="form-label text-muted small fw-bold">Dependencies (Target Models)</label>
                                <p class="text-muted small mb-2">If your SQL fragment joins other tables, list those models here so the engine knows to join them.</p>
                                <select class="form-select mb-2" x-model="temp.dependency" @change="addDependency()">
                                    <option value="">+ Add Dependency...</option>
                                    <template x-for="model in models" :key="model.class">
                                        <option :value="model.class" x-text="model.name"></option>
                                    </template>
                                </select>
                                
                                <ul class="list-group list-group-flush border rounded" x-show="payload.dependencies.length > 0">
                                    <template x-for="(dep, idx) in payload.dependencies" :key="idx">
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                            <span x-text="dep.split('\\').pop()"></span>
                                            <button class="btn btn-outline-danger btn-remove" @click="payload.dependencies.splice(idx, 1)">x</button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <hr class="my-4">

                        <button class="btn btn-primary w-100 py-3 fw-bold" @click="registerVa()" :disabled="loading" style="background-color: #4f46e5; border-color: #4f46e5;">
                            <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            <span x-text="loading ? 'Registering...' : 'Save & Register Virtual Attribute'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">Registered VAs</h5>
                        <button class="btn btn-sm btn-outline-secondary" @click="window.location.reload()">Refresh</button>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($existingVAs as $va)
                                <li class="list-group-item p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 fw-bold text-success">{{ $va->name }}</h6>
                                        <span class="badge bg-secondary">{{ class_basename($va->base_model) }}</span>
                                    </div>
                                    <div class="bg-light p-2 rounded small text-muted" style="font-family: monospace; font-size: 0.8rem;">
                                        {{ Str::limit($va->sql_fragment, 100) }}
                                    </div>
                                </li>
                            @empty
                                <li class="list-group-item text-center text-muted p-4">
                                    No Virtual Attributes registered yet.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function vaBuilder() {
            return {
                mode: 'visual', // 'visual' or 'advanced'
                models: @json($modelOptions),
                loading: false,
                alert: { show: false, type: '', message: '' },
                temp: {
                    dependency: ''
                },
                visual: {
                    targetModel: '',
                    operation: '',
                    targetColumn: '',
                    multiplyColumn: '',
                    baseModelKey: 'id',
                    targetModelKey: ''
                },
                payload: {
                    name: '',
                    baseModel: '',
                    sqlFragment: '',
                    dependencies: []
                },
                getColumnsForModel(modelClass) {
                    if (!modelClass) return [];
                    const m = this.models.find(m => m.class === modelClass);
                    return m ? m.columns : [];
                },
                getModelName(modelClass) {
                    if (!modelClass) return '';
                    const m = this.models.find(m => m.class === modelClass);
                    return m ? m.name : '';
                },
                addDependency() {
                    if (this.temp.dependency && !this.payload.dependencies.includes(this.temp.dependency)) {
                        this.payload.dependencies.push(this.temp.dependency);
                    }
                    this.temp.dependency = '';
                },
                compileVisualSql() {
                    if (!this.visual.targetModel || !this.visual.operation || !this.visual.targetColumn || !this.visual.targetModelKey || !this.visual.baseModelKey) {
                        return false;
                    }
                    
                    const target = this.models.find(m => m.class === this.visual.targetModel);
                    let aggCol = this.visual.targetColumn;
                    
                    if (this.visual.multiplyColumn) {
                        aggCol = `${aggCol} * ${this.visual.multiplyColumn}`;
                    }
                    
                    this.payload.sqlFragment = `(SELECT ${this.visual.operation}(${aggCol}) FROM ${target.table} WHERE ${target.table}.${this.visual.targetModelKey} = t0.${this.visual.baseModelKey})`;
                    this.payload.dependencies = [this.visual.targetModel];
                    return true;
                },
                async registerVa() {
                    if (this.mode === 'visual') {
                        if (!this.compileVisualSql()) {
                            this.showAlert('danger', 'Please complete all visual configuration fields.');
                            return;
                        }
                    }

                    if (!this.payload.name || !this.payload.baseModel || !this.payload.sqlFragment) {
                        this.showAlert('danger', 'Please fill in the Name, Base Model, and SQL configuration.');
                        return;
                    }
                    
                    this.loading = true;
                    this.alert.show = false;
                    
                    try {
                        const response = await fetch('{{ route("va_builder.register") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.payload)
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.showAlert('success', data.message);
                            // Reset form fields
                            this.payload.name = '';
                            this.payload.sqlFragment = '';
                            this.payload.dependencies = [];
                        } else {
                            this.showAlert('danger', data.error || 'Failed to register Virtual Attribute.');
                        }
                    } catch (e) {
                        this.showAlert('danger', e.message);
                    } finally {
                        this.loading = false;
                    }
                },
                showAlert(type, message) {
                    this.alert.type = type;
                    this.alert.message = message;
                    this.alert.show = true;
                }
            }
        }
    </script>
</body>
</html>
