# Organigramm-Builder – Architektur & Konzept

> Technische Dokumentation des Vanilla-JS-Builders für das cms-organigramm Plugin.

---

## Übersicht

Der Builder ist eine **clientseitige Single-Page-Anwendung** (ohne Framework), die direkt im Admin-Backend gerendert wird. Er kommuniziert mit dem Backend über JSON+AJAX-Endpunkte.

**Technologie-Stack:**
- Vanilla JavaScript (ES2020+, kein jQuery, kein Framework)
- SVG für die Verbindungslinien (Edges)
- HTML5 Canvas optional für Export
- CSS Custom Properties für Theming

---

## Datei-Struktur

```
cms-organigramm/
├── assets/
│   ├── css/
│   │   ├── builder.css         # Builder-spezifische Stile
│   │   └── organigramm.css     # Öffentliche Ansicht / Shortcode
│   └── js/
│       ├── builder.js          # Haupt-Builder-Modul
│       ├── builder-nodes.js    # Node-Rendering und -Manipulation
│       ├── builder-edges.js    # Edge/Pfeil-Zeichnung (SVG)
│       ├── builder-drag.js     # Drag & Drop Logik
│       ├── builder-toolbar.js  # Toolbar-Aktionen (Zoom, Export, Layout)
│       ├── builder-api.js      # AJAX-Wrapper für alle API-Calls
│       └── viewer.js           # Nur-Lese-Ansicht für Frontend/Shortcode
```

---

## Architektur-Prinzipien

### 1. Daten-Modell im Client

Der Builder hält eine vollständige In-Memory-Kopie des Organigramms:

```javascript
// Globaler State (Singleton-Objekt)
const OrgBuilder = {
    chartId: null,
    nodes: [],    // Array aus Node-Objekten
    edges: [],    // Array aus Edge-Objekten
    selection: null,  // Aktuell selektierter Node
    isDirty: false,   // Nicht gespeicherte Änderungen
};
```

---

### 2. Node-Rendering

Jeder Node wird als positioniertes `<div class="org-node">` gerendert:

```html
<div class="org-node" data-id="42" data-type="person" style="left: 200px; top: 150px;">
    <div class="org-node__photo">
        <img src="..." alt="">
    </div>
    <div class="org-node__content">
        <div class="org-node__label">Max Mustermann</div>
        <div class="org-node__sublabel">CTO</div>
    </div>
    <div class="org-node__actions">
        <button class="org-node__btn-add">+</button>
        <button class="org-node__btn-edit">✏️</button>
        <button class="org-node__btn-delete">🗑️</button>
    </div>
</div>
```

---

### 3. Edge-Rendering (SVG)

Verbindungslinien werden in einem überlagerten `<svg>`-Element gezeichnet, das die gesamte Builder-Fläche abdeckt:

```javascript
// builder-edges.js
function drawEdge(sourceNode, targetNode, edgeType = 'reports_to') {
    const x1 = sourceNode.offsetLeft + sourceNode.offsetWidth / 2;
    const y1 = sourceNode.offsetTop + sourceNode.offsetHeight;
    const x2 = targetNode.offsetLeft + targetNode.offsetWidth / 2;
    const y2 = targetNode.offsetTop;

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    const d = `M ${x1} ${y1} C ${x1} ${(y1 + y2) / 2}, ${x2} ${(y1 + y2) / 2}, ${x2} ${y2}`;
    
    path.setAttribute('d', d);
    path.setAttribute('class', `org-edge org-edge--${edgeType}`);
    path.dataset.sourceId = sourceNode.dataset.id;
    path.dataset.targetId = targetNode.dataset.id;
    
    document.getElementById('org-edges-svg').appendChild(path);
}
```

**Edge-Typen und CSS:**
- `reports_to` → durchgezogene Linie (`stroke-dasharray: none`)
- `dotted_line` → gestrichelte Linie (`stroke-dasharray: 8 4`)
- `project` → gepunktete Linie + andere Farbe

---

### 4. Drag & Drop

```javascript
// builder-drag.js
function initDrag(nodeEl) {
    let startX, startY, origLeft, origTop;
    
    nodeEl.addEventListener('mousedown', (e) => {
        if (e.target.closest('.org-node__actions')) return; // Aktions-Buttons ausschließen
        startX = e.clientX;
        startY = e.clientY;
        origLeft = parseInt(nodeEl.style.left) || 0;
        origTop  = parseInt(nodeEl.style.top)  || 0;
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });

    function onMove(e) {
        nodeEl.style.left = (origLeft + e.clientX - startX) + 'px';
        nodeEl.style.top  = (origTop  + e.clientY - startY) + 'px';
        OrgBuilder.isDirty = true;
        redrawEdges();
    }

    function onUp() {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
        saveNodePosition(nodeEl.dataset.id, nodeEl.style.left, nodeEl.style.top);
    }
}
```

---

### 5. Auto-Layout

Für den initialen Aufbau und den "Layout zurücksetzen"-Button:

```javascript
// Hierarchisches Tree-Layout (Sugiyama-ähnlich, vereinfacht)
function autoLayout(nodes, rootId, direction = 'vertical') {
    const levelWidth  = 200;
    const levelHeight = 150;
    
    function positionNode(nodeId, depth, xOffset) {
        const node = nodes.find(n => n.id === nodeId);
        const children = nodes.filter(n => n.parent_id === nodeId);
        
        const totalWidth = Math.max(1, children.length) * levelWidth;
        node.x = xOffset + totalWidth / 2 - levelWidth / 2;
        node.y = depth * levelHeight;
        
        children.forEach((child, i) => {
            positionNode(child.id, depth + 1, xOffset + i * levelWidth);
        });
    }
    
    positionNode(rootId, 0, 0);
    return nodes;
}
```

---

### 6. AJAX-API-Wrapper

```javascript
// builder-api.js
const OrgApi = {
    csrfToken: null, // Wird vom PHP-View gesetzt

    async saveNode(nodeData) {
        return this._post('org_node_save', nodeData);
    },

    async deleteNode(nodeId) {
        return this._post('org_node_delete', { node_id: nodeId });
    },

    async saveLayout(chartId, positions) {
        return this._post('org_layout_save', { chart_id: chartId, positions });
    },

    async _post(action, data) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', this.csrfToken);
        Object.entries(data).forEach(([k, v]) => {
            formData.append(k, typeof v === 'object' ? JSON.stringify(v) : v);
        });
        const res = await fetch(window.location.href, { method: 'POST', body: formData });
        return res.json();
    }
};
```

---

### 7. Export-Funktionen

| Format | Methode | Beschreibung |
|--------|---------|--------------|
| JSON | `exportJSON()` | Vollständige Chart-Daten als JSON-Download |
| SVG | `exportSVG()` | SVG-Vektor-Grafik (inlineStyles für Kompatibilität) |
| PNG | `exportPNG()` | Via `html2canvas` (Opt-In, Bibliothek muss geladen sein) |

```javascript
function exportJSON(chartId) {
    const data = {
        chart:  { ...OrgBuilder.chartConfig },
        nodes:  OrgBuilder.nodes,
        edges:  OrgBuilder.edges,
    };
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    downloadBlob(blob, `organigramm-${chartId}.json`);
}
```

---

### 8. Viewer (Frontend / Shortcode)

Der `viewer.js` ist eine leichtgewichtige Lese-Version des Builders (kein Drag, kein Edit):

- Lädt Daten per einmaligem AJAX-GET
- Rendert Nodes als statische Boxen
- Unterstützt Zoom via CSS `transform: scale()`
- Mobile: Horizontales Scrollen + Pinch-to-Zoom

---

## Geplante Admin-Menü-Struktur

| Menüpunkt | Seite | Trait |
|-----------|-------|-------|
| Dashboard | `/admin/organigramm.php?page=dashboard` | `trait-page-dashboard.php` |
| Alle Charts | `/admin/organigramm.php?page=charts` | `trait-page-charts.php` |
| Chart-Builder | `/admin/organigramm.php?page=builder&id=X` | `trait-page-builder.php` |
| Einstellungen | `/admin/organigramm.php?page=settings` | `trait-page-settings.php` |

---

## CSS-Variablen des Builders

```css
/* builder.css */
:root {
    --org-node-bg:          #ffffff;
    --org-node-border:      #e2e8f0;
    --org-node-shadow:      0 2px 8px rgba(0,0,0,0.08);
    --org-node-radius:      10px;
    --org-node-selected:    #3b82f6;
    --org-edge-color:       #94a3b8;
    --org-edge-dotted:      #cbd5e1;
    --org-canvas-bg:        #f8fafc;
    --org-canvas-grid:      #e2e8f0;
}
```

---

## Performance-Überlegungen

- Nodes werden als DOM-Elemente gehalten (kein Canvas-Rendering) – ausreichend bis ~200 Nodes
- Bei > 200 Nodes: Virtualize / nur sichtbare Nodes rendern
- SVG-Edges werden bei jeder Drag-Bewegung neu gezeichnet – `requestAnimationFrame` verwenden
- Auto-Save nach 3 Sekunden Inaktivität (Debounce)
