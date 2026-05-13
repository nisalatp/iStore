# Explainer 04: Virtual Attribute Registration

This document traces the logic inside the "Virtual Attribute Playground" tab, where Admin users build the UX Abstraction layer.

## The Goal
A Virtual Attribute takes a complex 3D relational graph (e.g., `Users` -> `Orders` -> `LineItems`) and flattens it into a 2D scalar value (e.g., `Total Lifetime Spend`) using a SQL subquery.

## 1. Visual Mode Construction

If an Admin does not want to write raw SQL, the frontend builds the subquery for them based on visual dropdowns.

```javascript
compileVisualSql() {
    // We append 's' assuming standard Laravel table names
    const targetTable = this.vaForm.visual.targetModel.toLowerCase() + 's';
    const baseTable = this.vaForm.baseModel.toLowerCase() + 's';
    
    const agg = this.vaForm.visual.aggregation;
    const col = this.vaForm.visual.targetColumn;

    // We generate the raw Subquery Pushdown string
    return `(SELECT ${agg}(${col}) FROM ${targetTable} WHERE ${targetTable}.${this.vaForm.baseModel.toLowerCase()}_id = ${baseTable}.id)`;
}
```
*Note: In a true production environment, we would use the backend PHP Reflection API to dynamically fetch the table names and foreign keys rather than guessing them with `.toLowerCase() + 's'` on the frontend.*

## 2. Registering the Attribute

Once the SQL fragment is generated (either visually or typed manually in Advanced Mode), it is submitted to the backend.

```javascript
async registerVirtualAttribute() {
    const fragment = this.vaForm.mode === 'visual' 
        ? this.compileVisualSql() 
        : this.vaForm.advanced.sqlFragment;

    await fetch('/va-builder/register', {
        method: 'POST',
        body: JSON.stringify({
            name: this.vaForm.name,
            base_model: this.vaForm.baseModel,
            sql_fragment: fragment,
            
            // We MUST pass dependencies so the Engine knows what tables 
            // to run the BFS Graph algorithm against!
            dependencies: [this.vaForm.visual.targetModel]
        })
    });
}
```

Once registered, the backend `VirtualAttributeRegistry` Singleton caches this SQL fragment. When an End-User subsequently requests this attribute in a report, the Engine automatically injects this SQL fragment into the Laravel Query Builder, achieving O(1) memory efficiency.
