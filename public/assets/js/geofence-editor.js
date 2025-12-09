/**
 * TEAM TRANSPORT – GEOFENCE EDITOR
 *
 * Used by:
 *  - views/admin/geofences/create.php
 *  - views/admin/geofences/edit.php
 *
 * Features:
 *  - Circle & polygon geofences
 *  - Drag / resize / reshape
 *  - Snap to grid (toggle)
 *  - Snap polygon vertices to nearby vertices
 *  - Prevent polygon self-intersections
 *  - Undo / Redo
 *  - Circle ⇄ Polygon convert
 *  - Grid overlay toggle
 *  - Autosave drafts (localStorage)
 */

document.addEventListener("DOMContentLoaded", function () {
    const mapContainer = document.getElementById("edit-map");
    if (!mapContainer) return; // Not on geofence editor page

    // -----------------------------
    // FORM ELEMENTS
    // -----------------------------
    const form      = document.getElementById("geofence-form");
    const idInput   = document.getElementById("geofence-id");
    const nameInput = document.getElementById("name");
    const descInput = document.getElementById("description");

    const typeSelect  = document.getElementById("type");
    const latInput    = document.getElementById("center_lat");
    const lngInput    = document.getElementById("center_lng");
    const radiusInput = document.getElementById("radius_m");
    const polyInput   = document.getElementById("polygon_points");

    const circleSection  = document.getElementById("circle-section");
    const polygonSection = document.getElementById("polygon-section");

    const btnDrawCircle  = document.getElementById("btn-draw-circle");
    const btnDrawPolygon = document.getElementById("btn-draw-polygon");
    const btnConvert     = document.getElementById("btn-convert");
    const btnReset       = document.getElementById("btn-reset");
    const btnUndo        = document.getElementById("btn-undo");
    const btnRedo        = document.getElementById("btn-redo");
    const btnSnap        = document.getElementById("btn-toggle-snap");
    const btnGrid        = document.getElementById("btn-toggle-grid");

    // -----------------------------
    // SNAP & GRID STATE
    // -----------------------------
    let snapEnabled = true;
    let gridEnabled = true;

    // Snap step in degrees (roughly ~11m in latitude)
    const GRID_DEG = 0.0001;
    // Max distance (meters) for snapping to nearby vertices
    const SNAP_VERTEX_DIST_M = 15;

    // -----------------------------
    // MAP & LAYERS
    // -----------------------------
    const defaultCenter = [45.50, -73.57];
    const defaultZoom   = 13;

    const streetLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 20,
        attribution: "&copy; OpenStreetMap contributors"
    });

    const satelliteLayer = L.tileLayer(
        "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
        {
            maxZoom: 19,
            attribution: "&copy; Esri & contributors"
        }
    );

    const map = L.map("edit-map", {
        editable: true,
        center: defaultCenter,
        zoom: defaultZoom,
        layers: [streetLayer]
    });

    const baseLayers = {
        "Streets": streetLayer,
        "Satellite": satelliteLayer
    };

    L.control.layers(baseLayers, null, { position: "topright" }).addTo(map);

    // Grid overlay (visual only)
    const gridLayer = L.gridLayer({
        pane: "overlayPane"
    });

    gridLayer.createTile = function () {
        const tile = document.createElement("canvas");
        tile.width = 256;
        tile.height = 256;
        const ctx = tile.getContext("2d");

        ctx.strokeStyle = "rgba(0,0,0,0.15)";
        ctx.lineWidth = 1;

        const step = 32; // pixels
        for (let x = 0; x < 256; x += step) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, 256);
            ctx.stroke();
        }
        for (let y = 0; y < 256; y += step) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(256, y);
            ctx.stroke();
        }

        return tile;
    };

    map.addLayer(gridLayer);

    // -----------------------------
    // SHAPE STATE & UNDO/REDO
    // -----------------------------
    let shape = null; // current circle or polygon
    const undoStack = [];
    const redoStack = [];

    function refreshTypeSections() {
        if (!typeSelect) return;
        const type = typeSelect.value;
        if (circleSection) {
            circleSection.style.display = (type === "circle") ? "block" : "none";
        }
        if (polygonSection) {
            polygonSection.style.display = (type === "polygon") ? "block" : "none";
        }
    }

    function snapToGrid(lat, lng) {
        if (!snapEnabled) return [lat, lng];
        const sLat = Math.round(lat / GRID_DEG) * GRID_DEG;
        const sLng = Math.round(lng / GRID_DEG) * GRID_DEG;
        return [sLat, sLng];
    }

    function snapToNearbyVertex(lat, lng, excludeIndex = -1) {
        if (!snapEnabled) return [lat, lng];
        if (!(shape instanceof L.Polygon)) return [lat, lng];
        const pts = shape.getLatLngs()[0];
        let best = null;
        let bestDist = Infinity;

        pts.forEach((p, idx) => {
            if (idx === excludeIndex) return;
            const d = map.distance([lat, lng], p);
            if (d < bestDist && d <= SNAP_VERTEX_DIST_M) {
                bestDist = d;
                best = p;
            }
        });

        return best ? [best.lat, best.lng] : [lat, lng];
    }

    // Segment intersection detection for self-intersection prevention
    function segmentsIntersect(p1, p2, p3, p4) {
        function ccw(a, b, c) {
            return (c[1] - a[1]) * (b[0] - a[0]) > (b[1] - a[1]) * (c[0] - a[0]);
        }
        return (ccw(p1, p3, p4) !== ccw(p2, p3, p4)) && (ccw(p1, p2, p3) !== ccw(p1, p2, p4));
    }

    function polygonHasSelfIntersections(points) {
        const n = points.length;
        for (let i = 0; i < n; i++) {
            const a1 = points[i];
            const a2 = points[(i + 1) % n];

            for (let j = i + 1; j < n; j++) {
                const b1 = points[j];
                const b2 = points[(j + 1) % n];

                // ignore same or adjacent edges
                if (i === j || (i + 1) % n === j || i === (j + 1) % n) continue;

                if (segmentsIntersect(a1, a2, b1, b2)) {
                    return true;
                }
            }
        }
        return false;
    }

    function getShapeSnapshot() {
        if (!shape) return null;

        if (shape instanceof L.Circle) {
            const c = shape.getLatLng();
            return {
                type: "circle",
                center: [c.lat, c.lng],
                radius: shape.getRadius()
            };
        }

        if (shape instanceof L.Polygon) {
            const pts = shape.getLatLngs()[0].map(p => [p.lat, p.lng]);
            return {
                type: "polygon",
                points: pts
            };
        }

        return null;
    }

    function pushUndo() {
        const snap = getShapeSnapshot();
        if (!snap) return;
        undoStack.push(JSON.stringify(snap));
        // Once we change, redo is invalid
        redoStack.length = 0;
    }

    function applySnapshot(json) {
        let data;
        try {
            data = JSON.parse(json);
        } catch {
            return;
        }
        if (shape) {
            map.removeLayer(shape);
            shape = null;
        }

        if (data.type === "circle" && Array.isArray(data.center)) {
            const center = data.center;
            const radius = data.radius || 300;
            shape = L.circle(center, {
                radius,
                color: "#ff6600",
                weight: 2
            }).addTo(map);
            map.setView(center, 15);

            latInput.value    = center[0].toFixed(6);
            lngInput.value    = center[1].toFixed(6);
            radiusInput.value = Math.round(radius);

            if (typeSelect) typeSelect.value = "circle";
            refreshTypeSections();
            enableCircleEditing();

        } else if (data.type === "polygon" && Array.isArray(data.points)) {
            const pts = data.points;
            shape = L.polygon(pts, {
                color: "#0066ff",
                weight: 2,
                fillOpacity: 0.15
            }).addTo(map);
            map.fitBounds(shape.getBounds());

            polyInput.value = JSON.stringify(pts);
            if (typeSelect) typeSelect.value = "polygon";
            refreshTypeSections();
            enablePolygonEditing();
        }

        saveDraft();
    }

    // -----------------------------
    // AUTOSAVE DRAFT
    // -----------------------------
    function draftKey() {
        const id = idInput && idInput.value ? idInput.value : "new";
        return "geofence_draft_" + id;
    }

    function saveDraft() {
        if (!form) return;
        const payload = {
            name:  nameInput?.value || "",
            description: descInput?.value || "",
            type:  typeSelect?.value || "circle",
            center_lat:  latInput?.value || "",
            center_lng:  lngInput?.value || "",
            radius_m:    radiusInput?.value || "",
            polygon_points: polyInput?.value || ""
        };
        try {
            localStorage.setItem(draftKey(), JSON.stringify(payload));
        } catch (e) {
            // ignore
        }
    }

    function loadDraftIntoFormIfEmpty() {
        let hasGeometry = false;
        if (latInput && latInput.value && lngInput && lngInput.value) {
            hasGeometry = true;
        }
        if (polyInput && polyInput.value.trim().length) {
            hasGeometry = true;
        }

        if (!hasGeometry) {
            try {
                const raw = localStorage.getItem(draftKey());
                if (!raw) return;
                const data = JSON.parse(raw);

                if (data.name && nameInput) nameInput.value = data.name;
                if (data.description && descInput) descInput.value = data.description;
                if (data.type && typeSelect) typeSelect.value = data.type;
                if (latInput) latInput.value = data.center_lat || "";
                if (lngInput) lngInput.value = data.center_lng || "";
                if (radiusInput) radiusInput.value = data.radius_m || "";
                if (polyInput) polyInput.value = data.polygon_points || "";
            } catch {
                // ignore
            }
        }
    }

    // Hook autosave on inputs
    ["name", "description", "center_lat", "center_lng", "radius_m", "polygon_points"].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener("input", saveDraft);
        }
    });
    if (typeSelect) {
        typeSelect.addEventListener("change", function () {
            refreshTypeSections();
            rebuildShapeFromForm();
            saveDraft();
        });
    }

    // -----------------------------
    // CIRCLE EDITING
    // -----------------------------
    function enableCircleEditing() {
        if (!shape || !(shape instanceof L.Circle)) return;

        shape.enableEdit();

        shape.off(); // clear prior handlers

        shape.on("editable:dragstart", () => {
            pushUndo();
        });

        shape.on("editable:drag", () => {
            const c = shape.getLatLng();
            let lat = c.lat;
            let lng = c.lng;

            [lat, lng] = snapToGrid(lat, lng);
            shape.setLatLng([lat, lng]);

            if (latInput) latInput.value = lat.toFixed(6);
            if (lngInput) lngInput.value = lng.toFixed(6);

            saveDraft();
        });

        shape.on("editable:editing", () => {
            const r = shape.getRadius();
            if (radiusInput) radiusInput.value = Math.round(r);
            saveDraft();
        });

        shape.on("editable:dragend editable:vertex:dragend", () => {
            // final snapshot
            pushUndo();
        });
    }

    // -----------------------------
    // POLYGON EDITING
    // -----------------------------
    function syncPolygonToFormAndValidate() {
        if (!shape || !(shape instanceof L.Polygon)) return true;

        const pts = shape.getLatLngs()[0].map(p => [p.lat, p.lng]);
        // Check self-intersections
        if (polygonHasSelfIntersections(pts)) {
            alert("Polygon self-intersection not allowed. Reverting last change.");
            // revert to last snapshot if any
            if (undoStack.length) {
                const prev = undoStack[undoStack.length - 1];
                applySnapshot(prev);
            }
            return false;
        }

        if (polyInput) polyInput.value = JSON.stringify(pts);
        saveDraft();
        return true;
    }

    function enablePolygonEditing() {
        if (!shape || !(shape instanceof L.Polygon)) return;

        shape.enableEdit();

        shape.off(); // clear prior handlers

        shape.on("editable:vertex:dragstart", () => {
            pushUndo();
        });

        shape.on("editable:vertex:drag", (e) => {
            const vertex = e.vertex;
            if (!vertex) return;

            let { lat, lng } = vertex.latlng;
            // snap to grid
            [lat, lng] = snapToGrid(lat, lng);
            // snap to nearby vertex
            const snapped = snapToNearbyVertex(lat, lng, vertex.getIndex());
            lat = snapped[0];
            lng = snapped[1];

            vertex.latlng.lat = lat;
            vertex.latlng.lng = lng;
            vertex.update();

            const pts = shape.getLatLngs()[0].map(p => [p.lat, p.lng]);
            if (polyInput) polyInput.value = JSON.stringify(pts);
        });

        // Called after dragging vertex or adding/removing
        const finalEvents = [
            "editable:vertex:dragend",
            "editable:vertex:new",
            "editable:vertex:deleted"
        ];
        finalEvents.forEach(ev => {
            shape.on(ev, () => {
                if (syncPolygonToFormAndValidate()) {
                    pushUndo();
                }
            });
        });

        // Right-click vertex to delete
        shape.on("contextmenu", (e) => {
            if (e.vertex && e.vertex.delete) {
                pushUndo();
                e.vertex.delete();
                syncPolygonToFormAndValidate();
            }
        });
    }

    // -----------------------------
    // BUILD SHAPE FROM FORM / DB / DRAFT
    // -----------------------------
    function rebuildShapeFromForm() {
        if (shape) {
            map.removeLayer(shape);
            shape = null;
        }

        const type = typeSelect ? typeSelect.value : "circle";

        if (type === "circle") {
            let lat = parseFloat(latInput?.value || "0");
            let lng = parseFloat(lngInput?.value || "0");
            let r   = parseFloat(radiusInput?.value || "0");

            if (!lat || !lng) {
                lat = defaultCenter[0];
                lng = defaultCenter[1];
            }
            if (!r || r <= 0) {
                r = 300;
            }

            shape = L.circle([lat, lng], {
                radius: r,
                color: "#ff6600",
                weight: 2
            }).addTo(map);

            map.setView([lat, lng], 15);

            if (latInput) latInput.value = lat.toFixed(6);
            if (lngInput) lngInput.value = lng.toFixed(6);
            if (radiusInput) radiusInput.value = Math.round(r);

            pushUndo();
            enableCircleEditing();
        } else {
            let pts = [];
            try {
                if (polyInput && polyInput.value.trim().length) {
                    pts = JSON.parse(polyInput.value);
                }
            } catch {
                pts = [];
            }

            if (!Array.isArray(pts) || !pts.length) {
                pts = [
                    [defaultCenter[0] - 0.005, defaultCenter[1] - 0.005],
                    [defaultCenter[0] + 0.005, defaultCenter[1] - 0.005],
                    [defaultCenter[0] + 0.005, defaultCenter[1] + 0.005],
                    [defaultCenter[0] - 0.005, defaultCenter[1] + 0.005]
                ];
                if (polyInput) {
                    polyInput.value = JSON.stringify(pts);
                }
            }

            shape = L.polygon(pts, {
                color: "#0066ff",
                weight: 2,
                fillOpacity: 0.15
            }).addTo(map);

            map.fitBounds(shape.getBounds());

            pushUndo();
            enablePolygonEditing();
        }

        saveDraft();
    }

    // -----------------------------
    // DRAW BUTTONS
    // -----------------------------
    if (btnDrawCircle) {
        btnDrawCircle.addEventListener("click", function () {
            if (typeSelect) typeSelect.value = "circle";
            refreshTypeSections();
            // Place circle at map center
            const center = map.getCenter();
            if (latInput) latInput.value = center.lat.toFixed(6);
            if (lngInput) lngInput.value = center.lng.toFixed(6);
            if (radiusInput) radiusInput.value = "300";
            rebuildShapeFromForm();
        });
    }

    if (btnDrawPolygon) {
        btnDrawPolygon.addEventListener("click", function () {
            if (typeSelect) typeSelect.value = "polygon";
            refreshTypeSections();

            const center = map.getCenter();
            const offset = 0.003;
            const pts = [
                [center.lat - offset, center.lng - offset],
                [center.lat + offset, center.lng - offset],
                [center.lat + offset, center.lng + offset],
                [center.lat - offset, center.lng + offset]
            ];
            if (polyInput) polyInput.value = JSON.stringify(pts);
            rebuildShapeFromForm();
        });
    }

    if (btnReset) {
        btnReset.addEventListener("click", function () {
            if (shape) {
                map.removeLayer(shape);
                shape = null;
            }
            if (latInput) latInput.value = "";
            if (lngInput) lngInput.value = "";
            if (radiusInput) radiusInput.value = "";
            if (polyInput) polyInput.value = "";

            undoStack.length = 0;
            redoStack.length = 0;

            // create sensible default shape for current type
            rebuildShapeFromForm();
        });
    }

    if (btnConvert) {
        btnConvert.addEventListener("click", function () {
            if (!shape) return;
            pushUndo();

            if (shape instanceof L.Circle) {
                // Circle -> polygon approximation
                const c = shape.getLatLng();
                const r = shape.getRadius();
                const steps = 40;
                const pts = [];

                for (let i = 0; i < steps; i++) {
                    const angle = (i / steps) * 2 * Math.PI;
                    const lat = c.lat + (Math.sin(angle) * r / 111320);
                    const lng = c.lng + (Math.cos(angle) * r / (111320 * Math.cos(c.lat * Math.PI / 180)));
                    pts.push([lat, lng]);
                }

                map.removeLayer(shape);
                shape = L.polygon(pts, {
                    color: "#0066ff",
                    weight: 2,
                    fillOpacity: 0.15
                }).addTo(map);

                if (polyInput) polyInput.value = JSON.stringify(pts);
                if (typeSelect) typeSelect.value = "polygon";
                refreshTypeSections();
                map.fitBounds(shape.getBounds());
                enablePolygonEditing();
                saveDraft();
            } else if (shape instanceof L.Polygon) {
                // Polygon -> approx minimal circle
                const pts = shape.getLatLngs()[0];
                if (!pts.length) return;

                let lat = 0, lng = 0;
                pts.forEach(p => {
                    lat += p.lat;
                    lng += p.lng;
                });
                lat /= pts.length;
                lng /= pts.length;

                let maxDist = 0;
                pts.forEach(p => {
                    maxDist = Math.max(maxDist, map.distance([lat, lng], p));
                });

                map.removeLayer(shape);
                shape = L.circle([lat, lng], {
                    radius: maxDist,
                    color: "#ff6600",
                    weight: 2
                }).addTo(map);

                if (latInput) latInput.value = lat.toFixed(6);
                if (lngInput) lngInput.value = lng.toFixed(6);
                if (radiusInput) radiusInput.value = Math.round(maxDist);
                if (typeSelect) typeSelect.value = "circle";
                refreshTypeSections();
                map.setView([lat, lng], 15);
                enableCircleEditing();
                saveDraft();
            }
        });
    }

    // -----------------------------
    // UNDO / REDO BUTTONS
    // -----------------------------
    if (btnUndo) {
        btnUndo.addEventListener("click", function () {
            if (undoStack.length <= 1) return;
            const current = undoStack.pop();
            redoStack.push(current);
            const prev = undoStack[undoStack.length - 1];
            applySnapshot(prev);
        });
    }

    if (btnRedo) {
        btnRedo.addEventListener("click", function () {
            if (!redoStack.length) return;
            const json = redoStack.pop();
            undoStack.push(json);
            applySnapshot(json);
        });
    }

    // -----------------------------
    // SNAP & GRID TOGGLES
    // -----------------------------
    function updateSnapButton() {
        if (!btnSnap) return;
        btnSnap.textContent = snapEnabled ? "Snap: ON" : "Snap: OFF";
    }

    function updateGridButton() {
        if (!btnGrid) return;
        btnGrid.textContent = gridEnabled ? "Grid: ON" : "Grid: OFF";
    }

    if (btnSnap) {
        btnSnap.addEventListener("click", function () {
            snapEnabled = !snapEnabled;
            updateSnapButton();
        });
    }

    if (btnGrid) {
        btnGrid.addEventListener("click", function () {
            gridEnabled = !gridEnabled;
            if (gridEnabled) {
                map.addLayer(gridLayer);
            } else {
                map.removeLayer(gridLayer);
            }
            updateGridButton();
        });
    }

    updateSnapButton();
    updateGridButton();

    // -----------------------------
    // INIT
    // -----------------------------
    refreshTypeSections();
    loadDraftIntoFormIfEmpty();
    rebuildShapeFromForm();

    setTimeout(() => {
        map.invalidateSize();
    }, 300);

    window.addEventListener("resize", () => {
        map.invalidateSize();
    })
});


