/**
 * TEAM TRANSPORT — GEOFENCE EDITOR CORE ENGINE
 * Exports: globalThis.GeofenceEditor.init(config)
 */
(function () {

  function init(config) {
    const mapId = config.mapId;
    const mapEl = document.getElementById(mapId);
    if (!mapEl) {
      console.warn("[GeofenceEditor] Missing map element:", mapId);
      return null;
    }

    const defaultCenter = config.defaultCenter || [45.5017, -73.5673];
    const defaultZoom   = config.defaultZoom ?? 13;

    const typeSelect  = config.typeSelect || null;

    const circleCfg  = config.circle || {};
    const polygonCfg = config.polygon || {};
    const buttonCfg  = config.buttons || {};

    const circleSection  = circleCfg.section  || null;
    const polygonSection = polygonCfg.section || null;

    const latInput    = circleCfg.latInput    || null;
    const lngInput    = circleCfg.lngInput    || null;
    const radiusInput = circleCfg.radiusInput || null;

    const polyInput   = polygonCfg.pointsInput || null;

    const btnDrawCircle  = buttonCfg.circle  || null;
    const btnDrawPolygon = buttonCfg.polygon || null;
    const btnConvert     = buttonCfg.convert || null;
    const btnReset       = buttonCfg.reset   || null;
    const btnUndo        = buttonCfg.undo    || null;
    const btnRedo        = buttonCfg.redo    || null;
    const btnSnap        = buttonCfg.snap    || null;
    const btnGrid        = buttonCfg.grid    || null;

    let shape = null;
    let snapEnabled = true;
    let gridEnabled = true;

    const undoStack = [];
    const redoStack = [];

    const GRID_DEG = 0.00010;
    const SNAP_VERTEX_DIST_M = 15;

    // --- Map ---
    const streetLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 20,
      attribution: "&copy; OpenStreetMap contributors"
    });

    const satelliteLayer = L.tileLayer(
      "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
      { maxZoom: 19, attribution: "&copy; Esri" }
    );

    const map = L.map(mapId, {
      editable: true,
      center: defaultCenter,
      zoom: defaultZoom,
      layers: [streetLayer]
    });

    L.control.layers({ Streets: streetLayer, Satellite: satelliteLayer }, null, { position: "topright" }).addTo(map);

    // --- Grid overlay ---
    const gridLayer = L.gridLayer({ pane: "overlayPane" });
    gridLayer.createTile = () => {
      const tile = document.createElement("canvas");
      tile.width = 256;
      tile.height = 256;

      const ctx = tile.getContext("2d");
      ctx.strokeStyle = "rgba(0, 0, 0, 0.15)";
      ctx.lineWidth = 1;

      const step = 32;
      for (let x = 0; x < 256; x += step) {
        ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, 256); ctx.stroke();
      }
      for (let y = 0; y < 256; y += step) {
        ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(256, y); ctx.stroke();
      }
      return tile;
    };
    map.addLayer(gridLayer);

    // --- Type sections ---
    function refreshTypeSections() {
      if (!typeSelect) return;
      const t = typeSelect.value;
      if (circleSection)  circleSection.style.display  = (t === "circle" ? "block" : "none");
      if (polygonSection) polygonSection.style.display = (t === "polygon" ? "block" : "none");
    }

    refreshTypeSections();
    typeSelect?.addEventListener("change", refreshTypeSections);

    // --- Snap helpers ---
    function snapToGrid(lat, lng) {
      if (!snapEnabled) return [lat, lng];
      const sLat = Math.round(lat / GRID_DEG) * GRID_DEG;
      const sLng = Math.round(lng / GRID_DEG) * GRID_DEG;
      return [sLat, sLng];
    }

    function snapToNearbyVertex(lat, lng, excludeIndex = -1) {
      if (!snapEnabled || !(shape instanceof L.Polygon)) return [lat, lng];
      const pts = shape.getLatLngs()[0];
      let best = null;
      let bestDist = Infinity;

      pts.forEach((p, i) => {
        if (i === excludeIndex) return;
        const d = map.distance([lat, lng], p);
        if (d < bestDist && d <= SNAP_VERTEX_DIST_M) {
          best = p;
          bestDist = d;
        }
      });

      return best ? [best.lat, best.lng] : [lat, lng];
    }

    // --- Undo/Redo snapshots ---
    function getSnapshot() {
      if (!shape) return null;

      if (shape instanceof L.Circle) {
        const c = shape.getLatLng();
        return { type: "circle", center: [c.lat, c.lng], radius: shape.getRadius() };
      }
      if (shape instanceof L.Polygon) {
        return { type: "polygon", points: shape.getLatLngs()[0].map(p => [p.lat, p.lng]) };
      }
      return null;
    }

    function pushUndo() {
      const snap = getSnapshot();
      if (!snap) return;
      undoStack.push(JSON.stringify(snap));
      redoStack.length = 0;
    }

    function applySnapshot(json) {
      if (!json) return;
      let data;
      try { data = JSON.parse(json); } catch { return; }

      if (shape) { map.removeLayer(shape); shape = null; }

      if (data.type === "circle") {
        const [lat, lng] = data.center;
        const r = data.radius;
        shape = L.circle([lat, lng], { radius: r, color: "#ff6600", weight: 2 }).addTo(map);

        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);
        if (radiusInput) radiusInput.value = Math.round(r);
        if (typeSelect) typeSelect.value = "circle";

        refreshTypeSections();
        enableCircleEditing();
        map.setView([lat, lng], 14);
        return;
      }

      if (data.type === "polygon") {
        shape = L.polygon(data.points, { color: "#0066ff", weight: 2, fillOpacity: 0.15 }).addTo(map);

        if (polyInput) polyInput.value = JSON.stringify(data.points);
        if (typeSelect) typeSelect.value = "polygon";

        refreshTypeSections();
        enablePolygonEditing();
        map.fitBounds(shape.getBounds());
      }
    }

    // --- Circle editing ---
    function enableCircleEditing() {
      if (!shape?.enableEdit || !(shape instanceof L.Circle)) return;

      shape.enableEdit();
      shape.off();

      shape.on("editable:dragstart", () => pushUndo());

      shape.on("editable:drag", () => {
        let { lat, lng } = shape.getLatLng();
        [lat, lng] = snapToGrid(lat, lng);
        shape.setLatLng([lat, lng]);

        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);
      });

      shape.on("editable:editing", () => {
        if (radiusInput) radiusInput.value = Math.round(shape.getRadius());
      });

      shape.on("editable:dragend editable:vertex:dragend", () => pushUndo());
    }

    // --- Polygon editing ---
    function enablePolygonEditing() {
      if (!shape?.enableEdit || !(shape instanceof L.Polygon)) return;

      shape.enableEdit();
      shape.off();

      shape.on("editable:vertex:dragstart", () => pushUndo());

      shape.on("editable:vertex:drag", (e) => {
        const v = e.vertex;
        if (!v) return;

        let { lat, lng } = v.latlng;
        [lat, lng] = snapToGrid(lat, lng);
        [lat, lng] = snapToNearbyVertex(lat, lng, v.getIndex());

        v.latlng.lat = lat;
        v.latlng.lng = lng;
        v.update();

        if (polyInput) {
          polyInput.value = JSON.stringify(shape.getLatLngs()[0].map(p => [p.lat, p.lng]));
        }
      });

      ["editable:vertex:dragend", "editable:vertex:new", "editable:vertex:deleted"].forEach(ev => {
        shape.on(ev, () => {
          if (polyInput) polyInput.value = JSON.stringify(shape.getLatLngs()[0].map(p => [p.lat, p.lng]));
          pushUndo();
        });
      });
    }

    // --- Load initial shape from legacy fields ---
    (function loadInitialShape() {
      const polyValue = polyInput?.value?.trim();
      const hasCircle = latInput?.value && lngInput?.value && radiusInput?.value;

      if (polyValue) {
        try {
          const pts = JSON.parse(polyValue);
          if (Array.isArray(pts) && pts.length >= 3) {
            shape = L.polygon(pts, { color: "#0066ff", weight: 2, fillOpacity: 0.15 }).addTo(map);
            map.fitBounds(shape.getBounds());
            enablePolygonEditing();
            pushUndo();
            if (typeSelect) typeSelect.value = "polygon";
            refreshTypeSections();
            return;
          }
        } catch {}
      }

      if (hasCircle) {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        const r   = parseFloat(radiusInput.value);

        if (!isNaN(lat) && !isNaN(lng) && !isNaN(r) && r > 0) {
          shape = L.circle([lat, lng], { radius: r, color: "#ff6600", weight: 2 }).addTo(map);
          map.setView([lat, lng], 15);
          enableCircleEditing();
          pushUndo();
          if (typeSelect) typeSelect.value = "circle";
          refreshTypeSections();
        }
      }
    })();

    // --- Buttons ---
    btnDrawCircle?.addEventListener("click", () => {
      if (shape) { map.removeLayer(shape); shape = null; }

      const c = map.getCenter();
      const lat = c.lat, lng = c.lng;

      if (latInput) latInput.value = lat.toFixed(6);
      if (lngInput) lngInput.value = lng.toFixed(6);
      if (radiusInput) radiusInput.value = "300";
      if (typeSelect) typeSelect.value = "circle";
      refreshTypeSections();

      shape = L.circle([lat, lng], { radius: 300, color: "#ff6600", weight: 2 }).addTo(map);
      enableCircleEditing();
      pushUndo();
    });

    btnDrawPolygon?.addEventListener("click", () => {
      if (shape) { map.removeLayer(shape); shape = null; }

      if (typeSelect) { typeSelect.value = "polygon"; refreshTypeSections(); }
      if (polyInput) polyInput.value = "[]";

      let pts = [];
      let tempPoly = null;
      let tempLine = null;
      let drawing = true;

      map.getContainer().style.cursor = "crosshair";

      function snapDuringDraw(lat, lng) {
        [lat, lng] = snapToGrid(lat, lng);
        [lat, lng] = snapToNearbyVertex(lat, lng);
        return [lat, lng];
      }

      function updatePreview(latlng) {
        if (pts.length === 0) return;
        let { lat, lng } = latlng;
        [lat, lng] = snapDuringDraw(lat, lng);
        if (tempLine) map.removeLayer(tempLine);
        tempLine = L.polyline([pts[pts.length - 1], [lat, lng]], { color: "blue", weight: 1, dashArray: "4" }).addTo(map);
      }

      function clickHandler(e) {
        let { lat, lng } = e.latlng;
        [lat, lng] = snapDuringDraw(lat, lng);

        pts.push([lat, lng]);

        if (tempPoly) map.removeLayer(tempPoly);
        tempPoly = L.polygon(pts, { color: "#0066ff", weight: 2, fillOpacity: 0.10 }).addTo(map);

        if (polyInput) polyInput.value = JSON.stringify(pts);
      }

      function finishHandler() {
        if (pts.length < 3) return;
        cleanup();

        shape = L.polygon(pts, { color: "#0066ff", weight: 2, fillOpacity: 0.15 }).addTo(map);
        enablePolygonEditing();
        pushUndo();
        if (polyInput) polyInput.value = JSON.stringify(pts);
      }

      function cancel() {
        cleanup();
        pts = [];
        if (polyInput) polyInput.value = "";
      }

      function undo() {
        if (pts.length === 0) return;
        pts.pop();
        if (tempPoly) map.removeLayer(tempPoly);
        if (pts.length > 0) tempPoly = L.polygon(pts, { color: "#0066ff", weight: 2, fillOpacity: 0.10 }).addTo(map);
        if (polyInput) polyInput.value = JSON.stringify(pts);
      }

      function keyHandler(e) {
        if (!drawing) return;
        if (e.key === "Escape") cancel();
        if (e.key === "Backspace") { e.preventDefault(); undo(); }
      }

      function moveHandler(e) { updatePreview(e.latlng); }

      function cleanup() {
        drawing = false;
        map.getContainer().style.cursor = "default";
        map.off("click", clickHandler);
        map.off("mousemove", moveHandler);
        map.off("dblclick", finishHandler);
        document.removeEventListener("keydown", keyHandler);
        if (tempPoly) map.removeLayer(tempPoly);
        if (tempLine) map.removeLayer(tempLine);
      }

      map.on("click", clickHandler);
      map.on("mousemove", moveHandler);
      map.on("dblclick", finishHandler);
      document.addEventListener("keydown", keyHandler);
    });

    btnReset?.addEventListener("click", () => {
      if (shape) { map.removeLayer(shape); shape = null; }
      if (latInput) latInput.value = "";
      if (lngInput) lngInput.value = "";
      if (radiusInput) radiusInput.value = "";
      if (polyInput) polyInput.value = "";
      undoStack.length = 0;
      redoStack.length = 0;
    });

    btnConvert?.addEventListener("click", () => {
      if (!shape) return;
      pushUndo();

      if (shape instanceof L.Circle) {
        const c = shape.getLatLng();
        const r = shape.getRadius();
        const steps = 40;
        const pts = [];

        for (let i = 0; i < steps; i++) {
          const a = (i / steps) * 2 * Math.PI;
          pts.push([
            c.lat + (Math.sin(a) * r / 111320),
            c.lng + (Math.cos(a) * r / (111320 * Math.cos(c.lat * Math.PI / 180)))
          ]);
        }

        map.removeLayer(shape);
        shape = L.polygon(pts, { color: "#0066ff", weight: 2, fillOpacity: 0.15 }).addTo(map);

        if (polyInput) polyInput.value = JSON.stringify(pts);
        if (typeSelect) { typeSelect.value = "polygon"; refreshTypeSections(); }

        map.fitBounds(shape.getBounds());
        enablePolygonEditing();
        return;
      }

      if (shape instanceof L.Polygon) {
        const pts = shape.getLatLngs()[0];
        let lat = 0, lng = 0;
        pts.forEach(p => { lat += p.lat; lng += p.lng; });
        lat /= pts.length; lng /= pts.length;

        let maxD = 0;
        pts.forEach(p => { maxD = Math.max(maxD, map.distance([lat, lng], p)); });

        map.removeLayer(shape);
        shape = L.circle([lat, lng], { radius: maxD, color: "#ff6600", weight: 2 }).addTo(map);

        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);
        if (radiusInput) radiusInput.value = Math.round(maxD);
        if (typeSelect) { typeSelect.value = "circle"; refreshTypeSections(); }

        map.setView([lat, lng], 15);
        enableCircleEditing();
      }
    });

    // --- Snap / Grid ---
    function updateSnapLabel() { if (btnSnap) btnSnap.textContent = snapEnabled ? "Snap: ON" : "Snap: OFF"; }
    function updateGridLabel() { if (btnGrid) btnGrid.textContent = gridEnabled ? "Grid: ON" : "Grid: OFF"; }

    btnSnap?.addEventListener("click", () => { snapEnabled = !snapEnabled; updateSnapLabel(); });
    btnGrid?.addEventListener("click", () => {
      gridEnabled = !gridEnabled;
      if (gridEnabled) map.addLayer(gridLayer); else map.removeLayer(gridLayer);
      updateGridLabel();
    });

    updateSnapLabel();
    updateGridLabel();

    // --- Undo / Redo ---
    btnUndo?.addEventListener("click", () => {
      if (undoStack.length <= 1) return;
      redoStack.push(undoStack.pop());
      applySnapshot(undoStack[undoStack.length - 1]);
    });

    btnRedo?.addEventListener("click", () => {
      if (!redoStack.length) return;
      const snap = redoStack.pop();
      undoStack.push(snap);
      applySnapshot(snap);
    });

    setTimeout(() => map.invalidateSize(), 250);
    window.addEventListener("resize", () => map.invalidateSize());

    return { map, getSnapshot };
  }

  // ----------------------------
  // REQUIRED GLOBAL EXPORT
  // ----------------------------
  globalThis.GeofenceEditor = { init };
  console.log("[geofence-core] exported", typeof globalThis.GeofenceEditor, typeof globalThis.GeofenceEditor.init);

})();
