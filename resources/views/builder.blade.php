<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Report Builder</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Alpine JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Inter', -apple-system, sans-serif; }
        .sql-box {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            color: #059669;
            overflow-x: auto;
            border: 1px solid #dee2e6;
        }
        .btn-remove {
            padding: 0.1rem 0.4rem;
            font-size: 0.75rem;
        }
    </style>
</head>
<body x-data="reportBuilder()" class="bg-light text-dark">
    
    <div class="d-flex align-items-start" style="min-height: 100vh;">
        <!-- Main Workspace -->
        <div class="flex-grow-1 p-4" style="transition: width 0.3s; max-height: 100vh; overflow-y: auto;">
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <h1 class="display-5 fw-bold text-primary">Dynamic Report Playground</h1>
                <p class="text-muted mb-0">Visually orchestrate queries against the nisalatp/dynamicreportgenerator engine.</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-outline-info fw-bold shadow-sm me-2" @click="astPanelOpen = !astPanelOpen">
                    <span x-text="astPanelOpen ? 'Hide AST' : '🔍 Inspect AST'"></span>
                </button>
                <button class="btn btn-outline-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#savedReportsModal" @click="loadSavedReports()">
                    📚 Saved Reports Library
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Sidebar / Configurator -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Query Builder</h5>
                    </div>
                    <div class="card-body">
                        
                        <div class="accordion mb-4" id="queryBuilderAccordion">
                            
                            <!-- Base Model -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingBaseModel">
                                    <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBaseModel" aria-expanded="true" aria-controls="collapseBaseModel">
                                        Base Model
                                    </button>
                                </h2>
                                <div id="collapseBaseModel" class="accordion-collapse collapse show" aria-labelledby="headingBaseModel" data-bs-parent="#queryBuilderAccordion">
                                    <div class="accordion-body">
                                        <select class="form-select" x-model="payload.baseModel">
                                            <option value="">-- Select Base Model --</option>
                                            @foreach($modelOptions as $model)
                                                <option value="{{ $model['class'] }}">{{ $model['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Target Models -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTargetModels">
                                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTargetModels" aria-expanded="false" aria-controls="collapseTargetModels">
                                        Target Models (Joins)
                                    </button>
                                </h2>
                                <div id="collapseTargetModels" class="accordion-collapse collapse" aria-labelledby="headingTargetModels" data-bs-parent="#queryBuilderAccordion">
                                    <div class="accordion-body">
                                        <select class="form-select mb-2" x-model="temp.targetModel" @change="addTargetModel()">
                                            <option value="">+ Add Join Target...</option>
                                            @foreach($modelOptions as $model)
                                                <option value="{{ $model['class'] }}">{{ $model['name'] }}</option>
                                            @endforeach
                                        </select>
                                        
                                        <ul class="list-group list-group-flush border rounded">
                                            <template x-for="(tm, idx) in payload.targetModels" :key="idx">
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                    <span x-text="tm.split('\\').pop()"></span>
                                                    <button class="btn btn-outline-danger btn-remove" @click="payload.targetModels.splice(idx, 1)">x</button>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Inner Query Attributes -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingColumns">
                                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseColumns" aria-expanded="false" aria-controls="collapseColumns">
                                        Select Columns (Raw Data)
                                    </button>
                                </h2>
                                <div id="collapseColumns" class="accordion-collapse collapse" aria-labelledby="headingColumns" data-bs-parent="#queryBuilderAccordion">
                                    <div class="accordion-body">
                                        <p class="text-muted small mb-2">Define the columns to retrieve from the database.</p>
                                        
                                        <div class="row g-2 mb-2">
                                            <div class="col-4">
                                                <select class="form-select" x-model="temp.selModel" @change="temp.selName = ''">
                                                    <option value="">Model</option>
                                                    <template x-for="model in getActiveModels()" :key="model.class">
                                                        <option :value="model.class" x-text="model.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" x-model="temp.selName" :disabled="!temp.selModel">
                                                    <option value="">-- Column --</option>
                                                    <template x-for="col in getColumnsForModel(temp.selModel)" :key="col">
                                                        <option :value="col" x-text="col"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <input type="text" class="form-control" placeholder="Alias (optional)" x-model="temp.selAlias">
                                            </div>
                                            <div class="col-12 mt-2">
                                                <button class="btn btn-outline-primary w-100" @click="addSelectedAttribute()">+ Add Column</button>
                                            </div>
                                        </div>

                                        <ul class="list-group list-group-flush border rounded mt-3" x-show="payload.selectedAttributes.length > 0">
                                            <template x-for="(attr, idx) in payload.selectedAttributes" :key="'attr'+idx">
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                    <span>
                                                        <span x-text="attr.model.split('\\').pop() + '.' + attr.column"></span>
                                                        <span x-show="attr.alias" class="text-muted small ms-1" x-text="'as ' + attr.alias"></span>
                                                    </span>
                                                    <button class="btn btn-outline-danger btn-remove" @click="payload.selectedAttributes.splice(idx, 1)">x</button>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Outer Query Aggregates -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingAggregates">
                                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAggregates" aria-expanded="false" aria-controls="collapseAggregates">
                                        Groupings & Aggregates
                                    </button>
                                </h2>
                                <div id="collapseAggregates" class="accordion-collapse collapse" aria-labelledby="headingAggregates" data-bs-parent="#queryBuilderAccordion">
                                    <div class="accordion-body">
                                        <p class="text-muted small mb-2">Define grouping dimensions and calculations.</p>
                                        
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <select class="form-select" x-model="temp.aggModel" @change="temp.aggColName = ''">
                                                    <option value="">Model</option>
                                                    <template x-for="model in getActiveModels()" :key="model.class">
                                                        <option :value="model.class" x-text="model.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <select class="form-select" x-model="temp.aggColName" :disabled="!temp.aggModel">
                                                    <option value="">-- Column --</option>
                                                    <template x-for="col in getColumnsForModel(temp.aggModel)" :key="col">
                                                        <option :value="col" x-text="col"></option>
                                                    </template>
                                                </select>
                                            </div>

                                            <div class="col-6">
                                                <select class="form-select" x-model="temp.aggFunc">
                                                    <option value="">-- Function --</option>
                                                    <option value="count">COUNT</option>
                                                    <option value="sum">SUM</option>
                                                    <option value="avg">AVG</option>
                                                    <option value="min">MIN</option>
                                                    <option value="max">MAX</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <input type="text" class="form-control" placeholder="Alias (e.g. total)" x-model="temp.aggAlias">
                                            </div>

                                            <div class="col-12 mt-2">
                                                <button class="btn btn-outline-success w-100" @click="addAggregate()">+ Aggregate</button>
                                            </div>

                                            <div class="col-12 mt-3 pt-3 border-top">
                                                <button class="btn btn-outline-info w-100" @click="addGroupBy()">+ Group By (Uses only Model & Column)</button>
                                            </div>
                                        </div>

                                        <ul class="list-group list-group-flush border rounded mt-3" x-show="payload.groupBys.length > 0 || payload.aggregates.length > 0">
                                            <template x-for="(gb, idx) in payload.groupBys" :key="'gb'+idx">
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-2 border-info border-start border-3">
                                                    <span><small class="text-info me-2 fw-bold">Group By:</small> <span x-text="gb.model.split('\\').pop() + '.' + gb.column"></span></span>
                                                    <button class="btn btn-outline-danger btn-remove" @click="payload.groupBys.splice(idx, 1)">x</button>
                                                </li>
                                            </template>
                                            <template x-for="(agg, idx) in payload.aggregates" :key="'agg'+idx">
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-2 border-success border-start border-3">
                                                    <span><small class="text-success me-2 fw-bold">Agg:</small> <span x-text="agg.function.toUpperCase() + '(' + agg.column + ')'"></span> as <span x-text="agg.alias"></span></span>
                                                    <button class="btn btn-outline-danger btn-remove" @click="payload.aggregates.splice(idx, 1)">x</button>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Inner Filters -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingInnerFilters">
                                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInnerFilters" aria-expanded="false" aria-controls="collapseInnerFilters">
                                        Inner Filters (WHERE)
                                    </button>
                                </h2>
                                <div id="collapseInnerFilters" class="accordion-collapse collapse" aria-labelledby="headingInnerFilters" data-bs-parent="#queryBuilderAccordion">
                                    <div class="accordion-body">
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <select class="form-select" x-model="temp.filterModel" @change="temp.filterColumn = ''">
                                                    <option value="">Model</option>
                                                    <template x-for="model in getActiveModels()" :key="model.class">
                                                        <option :value="model.class" x-text="model.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <select class="form-select" x-model="temp.filterColumn" :disabled="!temp.filterModel">
                                                    <option value="">-- Column --</option>
                                                    <template x-for="col in getColumnsForModel(temp.filterModel)" :key="col">
                                                        <option :value="col" x-text="col"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-2 mb-2">
                                            <div class="col-4">
                                                <select class="form-select" x-model="temp.filterOp">
                                                    <option value="=">=</option>
                                                    <option value="!=">!=</option>
                                                    <option value=">">&gt;</option>
                                                    <option value=">=">&gt;=</option>
                                                    <option value="<">&lt;</option>
                                                    <option value="<=">&lt;=</option>
                                                    <option value="like">LIKE</option>
                                                    <option value="in">IN</option>
                                                    <option value="is null">IS NULL</option>
                                                    <option value="is not null">IS NOT NULL</option>
                                                </select>
                                            </div>
                                            <div class="col-8">
                                                <input type="text" class="form-control" placeholder="Value" x-model="temp.filterValue">
                                            </div>
                                        </div>
                                        
                                        <button class="btn btn-outline-warning w-100 mb-3" @click="addInnerFilter()">+ Add Filter</button>

                                        <ul class="list-group list-group-flush border rounded">
                                            <template x-for="(f, idx) in payload.innerFilters" :key="'inf'+idx">
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-2 border-warning border-start border-3">
                                                    <span x-text="f.model.split('\\').pop() + '.' + f.column + ' ' + f.operator + ' ' + f.value"></span>
                                                    <button class="btn btn-outline-danger btn-remove" @click="payload.innerFilters.splice(idx, 1)">x</button>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Outer Filters -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOuterFilters">
                                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOuterFilters" aria-expanded="false" aria-controls="collapseOuterFilters">
                                        Outer Filters (HAVING)
                                    </button>
                                </h2>
                                <div id="collapseOuterFilters" class="accordion-collapse collapse" aria-labelledby="headingOuterFilters" data-bs-parent="#queryBuilderAccordion">
                                    <div class="accordion-body">
                                        <p class="text-muted small mb-2">Filter based on aggregate aliases (e.g., adult_count > 1).</p>
                                        
                                        <div class="row g-2 mb-2">
                                            <div class="col-4">
                                                <template x-if="payload.groupBys.length > 0 || payload.aggregates.length > 0">
                                                    <select class="form-select" x-model="temp.outerFilterColumn">
                                                        <option value="">-- Select Alias --</option>
                                                        <template x-for="col in getAvailableOuterColumns()" :key="col">
                                                            <option :value="col" x-text="col"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="payload.groupBys.length === 0 && payload.aggregates.length === 0">
                                                    <input type="text" class="form-control" placeholder="Alias Name" x-model="temp.outerFilterColumn">
                                                </template>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" x-model="temp.outerFilterOp">
                                                    <option value="=">=</option>
                                                    <option value="!=">!=</option>
                                                    <option value=">">&gt;</option>
                                                    <option value=">=">&gt;=</option>
                                                    <option value="<">&lt;</option>
                                                    <option value="<=">&lt;=</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <input type="text" class="form-control" placeholder="Value" x-model="temp.outerFilterValue">
                                            </div>
                                        </div>
                                        
                                        <button class="btn btn-outline-warning w-100 mb-3" @click="addOuterFilter()">+ Add Having Filter</button>

                                        <ul class="list-group list-group-flush border rounded" x-show="payload.outerFilters.length > 0">
                                            <template x-for="(f, idx) in payload.outerFilters" :key="'outf'+idx">
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-2 border-warning border-start border-3">
                                                    <span x-text="f.column + ' ' + f.operator + ' ' + f.value"></span>
                                                    <button class="btn btn-outline-danger btn-remove" @click="payload.outerFilters.splice(idx, 1)">x</button>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Sorts -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingSorts">
                                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSorts" aria-expanded="false" aria-controls="collapseSorts">
                                        Sorting (ORDER BY)
                                    </button>
                                </h2>
                                <div id="collapseSorts" class="accordion-collapse collapse" aria-labelledby="headingSorts" data-bs-parent="#queryBuilderAccordion">
                                    <div class="accordion-body">
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <template x-if="payload.groupBys.length > 0 || payload.aggregates.length > 0">
                                                    <select class="form-select" x-model="temp.sortColumn">
                                                        <option value="">-- Select Alias --</option>
                                                        <template x-for="col in getAvailableOuterColumns()" :key="col">
                                                            <option :value="col" x-text="col"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="payload.groupBys.length === 0 && payload.aggregates.length === 0">
                                                    <select class="form-select" x-model="temp.sortColumn">
                                                        <option value="">-- Select Column --</option>
                                                        <template x-for="attr in payload.selectedAttributes" :key="attr.column">
                                                            <option :value="attr.alias || attr.column" x-text="attr.alias || attr.column"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                            </div>
                                            <div class="col-6">
                                                <select class="form-select" x-model="temp.sortDirection">
                                                    <option value="ASC">Ascending (A-Z, 0-9)</option>
                                                    <option value="DESC">Descending (Z-A, 9-0)</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <button class="btn btn-outline-info w-100 mb-3" @click="addSort()">+ Add Sort</button>

                                        <ul class="list-group list-group-flush border rounded" x-show="payload.sorts && payload.sorts.length > 0">
                                            <template x-for="(s, idx) in payload.sorts" :key="'sort'+idx">
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-2 border-info border-start border-3">
                                                    <span x-text="s.column + ' ' + s.direction"></span>
                                                    <button class="btn btn-outline-danger btn-remove" @click="payload.sorts.splice(idx, 1)">x</button>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-primary py-2 fw-bold" @click="generateReport()" :disabled="loading">
                                <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                <span x-text="loading ? 'Compiling...' : 'Run Query Engine'"></span>
                            </button>
                            <button class="btn btn-success py-2 fw-bold" @click="exportCsv()" :disabled="loading || !payload.baseModel">
                                📊 Export to CSV
                            </button>
                            <button class="btn btn-outline-secondary py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#saveReportModal" :disabled="!payload.baseModel">
                                💾 Save Configuration
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results View -->
            <div class="col-lg-8">
                
                <div class="card shadow-sm mb-4" x-show="joinPlan && joinPlan.length > 0">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 fw-bold">BFS Relational Graph Resolution (Join Plan)</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <template x-for="(step, idx) in joinPlan" :key="'jp'+idx">
                                <li class="list-group-item d-flex align-items-center py-3">
                                    <span class="badge bg-secondary rounded-pill me-3" x-text="idx + 1"></span>
                                    <div>
                                        <div class="fw-bold text-dark">
                                            <span class="text-primary" x-text="step.fromModel.split('\\').pop()"></span>
                                            <span class="mx-2 text-muted">→</span>
                                            <span class="text-success" x-text="step.toModel.split('\\').pop()"></span>
                                        </div>
                                        <div class="small text-muted mt-1" style="font-family: monospace;">
                                            <span x-text="step.joinType.toUpperCase()"></span> JOIN 
                                            <span x-text="step.remoteTableAlias"></span> ON 
                                            <span x-text="step.localTableAlias + '.' + step.localKey"></span> = 
                                            <span x-text="step.remoteTableAlias + '.' + step.foreignKey"></span>
                                            <span class="badge bg-light text-dark ms-2 border" x-text="step.relationType.split('\\').pop()"></span>
                                        </div>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 fw-bold">Compiled SQL</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="sql-box m-3" x-text="sql || 'Awaiting query execution...'"></div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">Result Data</h5>
                        <div x-show="pagination" class="text-muted small">
                            Showing page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span> (Total rows: <span x-text="pagination.total"></span>)
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <div x-show="error" class="alert alert-danger" role="alert" x-text="error"></div>

                        <div x-show="results.length > 0 && !error" class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <template x-for="key in Object.keys(results[0] || {})" :key="key">
                                            <th x-text="key"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in results" :key="Math.random()">
                                        <tr>
                                            <template x-for="key in Object.keys(row)" :key="key">
                                                <td x-text="row[key]"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        
                        <div x-show="results.length === 0 && !error && sql" class="text-center text-muted py-4">
                            No rows returned.
                        </div>

                        <div x-show="results.length === 0 && !error && !sql" class="text-center text-muted py-4">
                            Run a query to see results.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Persistent Live AST Panel -->
    <div style="width: 450px; min-width: 450px; height: 100vh; position: sticky; top: 0;" class="bg-white border-start shadow-sm" :class="astPanelOpen ? 'd-flex flex-column' : 'd-none'">
        <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-primary">Real-Time AST Payload</h5>
            <button type="button" class="btn-close" @click="astPanelOpen = false" aria-label="Close"></button>
        </div>
        <div class="flex-grow-1 overflow-auto p-3 bg-light">
            <pre class="m-0 text-dark" style="font-size: 0.85rem; font-family: monospace;" x-text="JSON.stringify(payload, null, 2)"></pre>
        </div>
    </div>
</div>

    <!-- Save Report Modal -->
    <div class="modal fade" id="saveReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Save Report Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Report Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" x-model="saveMeta.name" placeholder="e.g. Monthly Active Users">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" x-model="saveMeta.description" placeholder="Optional description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary fw-bold" @click="saveReport()" :disabled="!saveMeta.name || loading">
                        <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Save Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Saved Reports Library Modal -->
    <div class="modal fade" id="savedReportsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">📚 Saved Reports Library</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        <template x-for="report in savedReports" :key="report.id">
                            <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h6 class="mb-1 fw-bold text-primary" x-text="report.name"></h6>
                                    <p class="mb-1 text-muted small" x-text="report.description || 'No description provided.'"></p>
                                    <small class="text-muted">Created: <span x-text="new Date(report.created_at).toLocaleString()"></span></small>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" @click="loadReportToEditor(report.id)" data-bs-dismiss="modal" title="Load into Builder">
                                        ✏️ Load
                                    </button>
                                    <button class="btn btn-sm btn-success fw-bold" @click="executeSavedReport(report.id)" data-bs-dismiss="modal" title="Execute Query Immediately">
                                        ▶️ Run
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div x-show="savedReports.length === 0" class="text-center py-5 text-muted">
                            <p class="mb-0">No saved reports found.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const modelData = @json($modelOptions);

        function reportBuilder() {
            return {
                astPanelOpen: false,
                loading: false,
                sql: '',
                joinPlan: [],
                error: null,
                results: [],
                models: modelData,
                getActiveModels() {
                    const activeClasses = [this.payload.baseModel, ...this.payload.targetModels].filter(Boolean);
                    return this.models.filter(m => activeClasses.includes(m.class));
                },
                getColumnsForModel(modelClass) {
                    const model = this.models.find(m => m.class === modelClass);
                    return model ? model.columns : [];
                },
                getAvailableOuterColumns() {
                    let cols = [];
                    this.payload.groupBys.forEach(gb => {
                        let sel = this.payload.selectedAttributes.find(s => s.model === gb.model && s.column === gb.column);
                        let name = sel && sel.alias ? sel.alias : gb.column;
                        cols.push(name);
                    });
                    this.payload.aggregates.forEach(agg => {
                        if (agg.alias) {
                            cols.push(agg.alias);
                        } else {
                            let sel = this.payload.selectedAttributes.find(s => s.model === agg.model && s.column === agg.column);
                            let innerColName = sel && sel.alias ? sel.alias : agg.column;
                            let generatedAlias = (agg.function + '_' + innerColName.replace(/\s+/g, '_')).toLowerCase();
                            cols.push(generatedAlias);
                        }
                    });
                    return [...new Set(cols)];
                },
                temp: {
                    targetModel: '',
                    selModel: '',
                    selName: '',
                    selAlias: '',
                    aggModel: '',
                    aggColName: '',
                    aggFunc: '',
                    aggAlias: '',
                    filterModel: '',
                    filterColumn: '',
                    filterOp: '=',
                    filterValue: '',
                    outerFilterColumn: '',
                    outerFilterOp: '=',
                    outerFilterValue: '',
                    sortColumn: '',
                    sortDirection: 'ASC'
                },
                payload: {
                    baseModel: '',
                    targetModels: [],
                    selectedAttributes: [],
                    groupBys: [],
                    aggregates: [],
                    innerFilters: [],
                    outerFilters: [],
                    sorts: []
                },
                pagination: null,
                saveMeta: {
                    name: '',
                    description: ''
                },
                savedReports: [],
                async loadSavedReports() {
                    try {
                        const response = await fetch('/builder/saved');
                        const data = await response.json();
                        if (data.success) {
                            this.savedReports = data.reports;
                        }
                    } catch (e) {
                        console.error('Failed to load reports', e);
                    }
                },
                async saveReport() {
                    if (!this.saveMeta.name) return;
                    this.loading = true;
                    try {
                        const response = await fetch('/builder/save', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                name: this.saveMeta.name,
                                description: this.saveMeta.description,
                                payload: this.payload
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            alert(data.message);
                            this.saveMeta.name = '';
                            this.saveMeta.description = '';
                            
                            // Close modal gracefully
                            const modalEl = document.getElementById('saveReportModal');
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    } catch (e) {
                        alert('Error: ' + e.message);
                    } finally {
                        this.loading = false;
                    }
                },
                async loadReportToEditor(id) {
                    try {
                        const response = await fetch(`/builder/saved/${id}/load`);
                        const data = await response.json();
                        if (data.success) {
                            let ast = data.payload;
                            if (typeof ast === 'string') {
                                ast = JSON.parse(ast);
                            }
                            
                            // Map AST selectedAttributes back to UI format
                            const selAttrs = (ast.selectedAttributes || []).map(a => ({
                                model: a.modelClass,
                                column: a.column,
                                type: a.type,
                                alias: a.alias || null
                            }));

                            // Map AST groupBys back to UI format
                            const gbs = (ast.groupBys || []).map(gb => ({
                                model: gb.attribute.modelClass,
                                column: gb.attribute.column,
                                type: gb.attribute.type
                            }));

                            // Map AST aggregates back to UI format
                            const aggs = (ast.aggregates || []).map(agg => ({
                                model: agg.attribute.modelClass,
                                column: agg.attribute.column,
                                type: agg.attribute.type,
                                function: agg.function,
                                alias: agg.alias || null
                            }));

                            // Helper to flatten filter trees back to UI format
                            const flattenFilters = (node) => {
                                if (!node) return [];
                                if (node.type === 'leaf') {
                                    return [{
                                        model: node.attribute.modelClass,
                                        column: node.attribute.column,
                                        type: node.attribute.type,
                                        operator: node.operator,
                                        value: node.value
                                    }];
                                }
                                if (node.type === 'group' && node.children) {
                                    let flat = [];
                                    node.children.forEach(child => {
                                        flat = flat.concat(flattenFilters(child));
                                    });
                                    return flat;
                                }
                                return [];
                            };

                            this.payload = {
                                baseModel: ast.baseModel || '',
                                targetModels: ast.targetModels || [],
                                selectedAttributes: selAttrs,
                                groupBys: gbs,
                                aggregates: aggs,
                                innerFilters: flattenFilters(ast.innerFilters),
                                outerFilters: flattenFilters(ast.outerFilters),
                                sorts: (ast.sorts || []).map(s => ({
                                    model: s.attribute.modelClass,
                                    column: s.attribute.column,
                                    type: s.attribute.type,
                                    direction: s.direction
                                }))
                            };
                            this.sql = '';
                            this.joinPlan = [];
                            this.results = [];
                            this.pagination = null;
                        } else {
                            alert('Error loading report: ' + data.error);
                        }
                    } catch (e) {
                        alert('Error: ' + e.message);
                    }
                },
                async executeSavedReport(id) {
                    this.loading = true;
                    this.error = null;
                    try {
                        const response = await fetch(`/builder/saved/${id}/execute`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.sql = data.sql;
                            this.joinPlan = data.join_plan || [];
                            this.results = data.results;
                            this.pagination = data.pagination || null;
                        } else {
                            this.error = data.error;
                            this.sql = '';
                            this.joinPlan = [];
                            this.results = [];
                            this.pagination = null;
                        }
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                },
                addTargetModel() {
                    if (this.temp.targetModel && !this.payload.targetModels.includes(this.temp.targetModel)) {
                        this.payload.targetModels.push(this.temp.targetModel);
                    }
                    this.temp.targetModel = '';
                },
                addSelectedAttribute() {
                    if (this.temp.selModel && this.temp.selName) {
                        this.payload.selectedAttributes.push({
                            model: this.temp.selModel,
                            column: this.temp.selName,
                            type: 'string',
                            alias: this.temp.selAlias || null
                        });
                        this.temp.selName = '';
                        this.temp.selAlias = '';
                    }
                },
                addGroupBy() {
                    if (this.temp.aggModel && this.temp.aggColName) {
                        this.payload.groupBys.push({
                            model: this.temp.aggModel,
                            column: this.temp.aggColName,
                            type: 'string'
                        });
                        this.temp.aggColName = '';
                    }
                },
                addAggregate() {
                    if (this.temp.aggModel && this.temp.aggColName && this.temp.aggFunc) {
                        this.payload.aggregates.push({
                            model: this.temp.aggModel,
                            column: this.temp.aggColName,
                            type: 'integer',
                            function: this.temp.aggFunc,
                            alias: this.temp.aggAlias || null
                        });
                        this.temp.aggFunc = '';
                        this.temp.aggAlias = '';
                    }
                },
                addInnerFilter() {
                    if (this.temp.filterModel && this.temp.filterColumn && this.temp.filterOp) {
                        this.payload.innerFilters.push({
                            model: this.temp.filterModel,
                            column: this.temp.filterColumn,
                            type: 'string',
                            operator: this.temp.filterOp,
                            value: this.temp.filterValue
                        });
                        this.temp.filterValue = '';
                    }
                },
                addOuterFilter() {
                    if (this.temp.outerFilterColumn && this.temp.outerFilterOp) {
                        this.payload.outerFilters.push({
                            model: this.payload.baseModel, // Implicitly use base model for the alias reference
                            column: this.temp.outerFilterColumn,
                            type: 'integer',
                            operator: this.temp.outerFilterOp,
                            value: this.temp.outerFilterValue
                        });
                        this.temp.outerFilterColumn = '';
                        this.temp.outerFilterValue = '';
                    }
                },
                addSort() {
                    if (this.temp.sortColumn) {
                        this.payload.sorts.push({
                            model: this.payload.baseModel,
                            column: this.temp.sortColumn,
                            type: 'string', // Doesn't matter much for sorting
                            direction: this.temp.sortDirection
                        });
                        this.temp.sortColumn = '';
                    }
                },
                async exportCsv() {
                    if (!this.payload.baseModel) return;
                    
                    try {
                        const response = await fetch('/builder/export', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.payload)
                        });

                        if (response.ok) {
                            const blob = await response.blob();
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'dynamic_report_export.csv';
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                        } else {
                            const data = await response.json();
                            alert('Export failed: ' + data.error);
                        }
                    } catch (e) {
                        alert('Error: ' + e.message);
                    }
                },
                async generateReport() {
                    if (!this.payload.baseModel) {
                        alert('Please select a Base Model');
                        return;
                    }
                    
                    this.loading = true;
                    this.error = null;
                    
                    try {
                        const response = await fetch('/builder/generate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.payload)
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.sql = data.sql;
                            this.joinPlan = data.join_plan || [];
                            this.results = data.results;
                            this.pagination = data.pagination || null;
                        } else {
                            this.error = data.error;
                            this.sql = '';
                            this.joinPlan = [];
                            this.results = [];
                            this.pagination = null;
                        }
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
