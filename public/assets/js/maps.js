/**
 * TEAM TRANSPORT – Live Fleet Map
 * --------------------------------
 * Includes:
 *  - WebSocket telemetry
 *  - Clusters (if plugin loaded)
 *  - Heatmap (if plugin loaded)
 *  - Trails
 *  - Geofences from DB (geojson)
 *  - Enter/exit alerts
 *  - Playback + Vehicle modal
 *  - Leaflet.draw -> Create Geofence modal -> POST store
 */
(function () {
  "use strict";

  // ---------- Guards ----------
  if (!window.L) {
    console.error("[maps] Leaflet (window.L) not found.");
    return;
  }

  const mapEl = document.getElementById("live-map");
  if (!mapEl) {
    console.warn("[maps] #live-map not found; aborting.");
    return;
  }

  // ---------- Map ----------
  const initialCenter = (window.INITIAL_CENTER && Array.isArray(window.INITIAL_CENTER))
    ? window.INITIAL_CENTER
    : [45.50, -73.57];

  const map = L.map("live-map").setView(initialCenter, 14);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 20 }).addTo(map);

  // Cluster plugin optional
  const clusterGroup = (typeof L.markerClusterGroup === "function")
    ? L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 60,
        iconCreateFunction: function (cluster) {
          const count = cluster.getChildCount();
          let c = "marker-cluster-small";
          if (count > 25) c = "marker-cluster-large";
          else if (count > 10) c = "marker-cluster-medium";
          return new L.DivIcon({
            html: "<div><span>" + count + "</span></div>",
            className: "marker-cluster " + c,
            iconSize: new L.Point(40, 40)
          });
        }
      })
    : L.layerGroup();

  map.addLayer(clusterGroup);

  // Heat plugin optional
  const heatLayer = (typeof L.heatLayer === "function")
    ? L.heatLayer([], { radius: 25, blur: 15, maxZoom: 18 }).addTo(map)
    : null;

  // Data stores
  const vehicleMarkers = {};
  const vehicleTrails = {};
  const trailPolylines = {};
  const telemetryHistory = {};
  const MAX_TRAIL_POINTS = 120;

  function getSpeedColor(speed) {
    if (speed < 5) return "#6c757d";
    if (speed < 40) return "#0d6efd";
    if (speed < 80) return "#ffc107";
    return "#dc3545";
  }

  // Fit all
  const btnFitAll = document.getElementById("btn-fit-all");
  if (btnFitAll) {
    btnFitAll.addEventListener("click", () => {
      const ids = Object.keys(vehicleMarkers);
      if (!ids.length) return;
      const group = L.featureGroup(ids.map(id => vehicleMarkers[id]));
      map.fitBounds(group.getBounds().pad(0.2));
    });
  }

  // ---------- Filters ----------
  const filterShowStopped = document.getElementById("filter-show-stopped");
  const filterShowHeatmap = document.getElementById("filter-show-heatmap");
  const filterShowTrails = document.getElementById("filter-show-trails");
  const filterShowClusters = document.getElementById("filter-show-clusters");
  const filterMinSpeed = document.getElementById("filter-min-speed");
  const filterMinSpeedValue = document.getElementById("filter-min-speed-value");

  function applyFilters() {
    const minSpeed = filterMinSpeed ? parseInt(filterMinSpeed.value, 10) : 0;
    const showStopped = filterShowStopped ? filterShowStopped.checked : true;
    const showHeatmap = filterShowHeatmap ? filterShowHeatmap.checked : true;
    const showTrails = filterShowTrails ? filterShowTrails.checked : true;
    const showClusters = filterShowClusters ? filterShowClusters.checked : true;

    Object.entries(vehicleMarkers).forEach(([id, marker]) => {
      const hist = telemetryHistory[id];
      if (!hist || !hist.length) return;
      const last = hist[hist.length - 1];

      let visible = true;
      if (!showStopped && last.speed < 5) visible = false;
      if (last.speed < minSpeed) visible = false;

      if (showClusters && visible) {
        if (clusterGroup.addLayer) clusterGroup.addLayer(marker);
        else if (!map.hasLayer(marker)) map.addLayer(marker);
      } else {
        if (clusterGroup.removeLayer) clusterGroup.removeLayer(marker);
        else if (map.hasLayer(marker)) map.removeLayer(marker);
      }
    });

    Object.values(trailPolylines).forEach(poly => {
      if (showTrails) {
        if (!map.hasLayer(poly)) map.addLayer(poly);
      } else {
        if (map.hasLayer(poly)) map.removeLayer(poly);
      }
    });

    if (heatLayer) {
      if (showHeatmap) {
        if (!map.hasLayer(heatLayer)) map.addLayer(heatLayer);
      } else {
        if (map.hasLayer(heatLayer)) map.removeLayer(heatLayer);
      }
    }
  }

  if (filterMinSpeed) {
    filterMinSpeed.addEventListener("input", () => {
      if (filterMinSpeedValue) filterMinSpeedValue.textContent = filterMinSpeed.value;
      applyFilters();
    });
  }

  [filterShowStopped, filterShowHeatmap, filterShowTrails, filterShowClusters]
    .filter(Boolean)
    .forEach(el => el.addEventListener("change", applyFilters));

  // ---------- Sidebar table ----------
  const tableBody = document.getElementById("active-vehicles-body");
  const lastUpdateLabel = document.getElementById("last-update-label");
  const playbackSelect = document.getElementById("playback-vehicle");

  function updateVehicleRow(id, latlng, speed, timestamp) {
    if (!tableBody) return;

    const rowId = "vehicle-row-" + id;
    let row = document.getElementById(rowId);

    const posText = latlng[0].toFixed(5) + ", " + latlng[1].toFixed(5);
    const speedText = speed + " km/h";

    if (!row) {
      row = document.createElement("tr");
      row.id = rowId;
      row.innerHTML = `
        <td class="veh-id">${id}</td>
        <td class="veh-pos">${posText}</td>
        <td class="veh-speed">${speedText}</td>
        <td class="veh-time">${timestamp}</td>
      `;
      row.addEventListener("click", () => openVehicleModal(id));
      tableBody.appendChild(row);

      if (playbackSelect) {
        const opt = document.createElement("option");
        opt.value = id;
        opt.textContent = "Vehicle " + id;
        playbackSelect.appendChild(opt);
      }
    } else {
      row.querySelector(".veh-pos").textContent = posText;
      row.querySelector(".veh-speed").textContent = speedText;
      row.querySelector(".veh-time").textContent = timestamp;
    }
  }

  // ---------- Geofence alerts ----------
  const alertsBox = document.getElementById("geofence-alerts");
  const btnClearAlerts = document.getElementById("btn-clear-alerts");
  if (btnClearAlerts && alertsBox) {
    btnClearAlerts.addEventListener("click", () => {
      alertsBox.innerHTML = '<div class="text-muted">No alerts yet.</div>';
    });
  }
  function logGeofenceAlert(msg) {
    if (!alertsBox) return;
    const div = document.createElement("div");
    div.textContent = new Date().toISOString().slice(11, 19) + " — " + msg;
    alertsBox.prepend(div);
  }

  // ---------- Geofences from DB (geojson) ----------
  const geoLayers = {};
  const geoInside = {};

  function pointInPolygon(latlng, poly) {
    const x = latlng.lng;
    const y = latlng.lat;
    let inside = false;
    for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
      const xi = poly[i].lng, yi = poly[i].lat;
      const xj = poly[j].lng, yj = poly[j].lat;
      const intersect = ((yi > y) !== (yj > y)) && (x < ((xj - xi) * (y - yi)) / ((yj - yi) || 1) + xi);
      if (intersect) inside = !inside;
    }
    return inside;
  }

  function renderGeofences() {
    if (!Array.isArray(window.GEOFENCES)) return;

    window.GEOFENCES.forEach(g => {
      if (!g.geojson) return;

      let feature;
      try {
        feature = (typeof g.geojson === "string") ? JSON.parse(g.geojson) : g.geojson;
      } catch (e) {
        console.error("[geofences] invalid geojson", g.id, e);
        return;
      }

      const geom = feature.geometry || feature;
      const props = feature.properties || {};

      if (!geom || !geom.type) return;

      if (geom.type === "Point") {
        const [lng, lat] = geom.coordinates || [];
        const radius = Number(props.radius_m || props.radius || 0);
        if (!Number.isFinite(lat) || !Number.isFinite(lng) || !Number.isFinite(radius) || radius <= 0) return;

        const layer = L.circle([lat, lng], {
          radius,
          color: "#ff6600",
          weight: 2,
          fillColor: "#ffae42",
          fillOpacity: 0.20
        }).addTo(map).bindPopup(`<strong>${g.name}</strong><br>Radius: ${Math.round(radius)} m`);

        geoLayers[g.id] = { id: g.id, name: g.name, type: "circle", center: L.latLng(lat, lng), radius, layer };
        return;
      }

      if (geom.type === "Polygon") {
        const ring = geom.coordinates?.[0];
        if (!Array.isArray(ring) || ring.length < 4) return;

        const latlngs = ring.map(([lng, lat]) => [lat, lng]);
        const layer = L.polygon(latlngs, { color: "#0066ff", weight: 2, fillOpacity: 0.15 })
          .addTo(map)
          .bindPopup(`<strong>${g.name}</strong><br>Polygon Zone`);

        geoLayers[g.id] = { id: g.id, name: g.name, type: "polygon", layer };
      }
    });

    console.log("[geofences] loaded:", geoLayers);
  }

  function checkGeofences(vehicleId, latlng) {
    if (!geoInside[vehicleId]) geoInside[vehicleId] = {};

    Object.values(geoLayers).forEach(g => {
      let isInside = false;

      if (g.type === "circle") {
        isInside = map.distance(latlng, g.center) <= g.radius;
      } else if (g.type === "polygon") {
        isInside = g.layer.getBounds().contains(latlng) && pointInPolygon(latlng, g.layer.getLatLngs()[0]);
      }

      const wasInside = !!geoInside[vehicleId][g.id];

      if (isInside && !wasInside) {
        geoInside[vehicleId][g.id] = true;
        logGeofenceAlert(`Vehicle ${vehicleId} ENTERED ${g.name}`);
      } else if (!isInside && wasInside) {
        geoInside[vehicleId][g.id] = false;
        logGeofenceAlert(`Vehicle ${vehicleId} EXITED ${g.name}`);
      }
    });
  }

  renderGeofences();

  // ---------- Draw -> Modal -> Save ----------
  const drawnItems = new L.FeatureGroup();
  map.addLayer(drawnItems);

  const btnDrawCircle = document.getElementById("btn-draw-circle");
  const btnDrawPolygon = document.getElementById("btn-draw-polygon");

  const modalEl = document.getElementById("geofenceCreateModal");
  const formEl = document.getElementById("geofence-create-form");

  // Modal fields (match your HTML)
  const typeEl = document.getElementById("geo-type");
  const latEl = document.getElementById("geo-center-lat");
  const lngEl = document.getElementById("geo-center-lng");
  const radEl = document.getElementById("geo-radius");
  const polyEl = document.getElementById("geo-polygon");      // IMPORTANT
  const geojsonEl = document.getElementById("geojson-modal");  // IMPORTANT

  function setGeojsonCircle(lat, lng, radius) {
    if (!geojsonEl) return;
    geojsonEl.value = JSON.stringify({
      type: "Feature",
      properties: { radius_m: radius },
      geometry: { type: "Point", coordinates: [lng, lat] }
    });
  }

  function setGeojsonPolygon(points /* [{lat,lng}, ...] */) {
    if (!geojsonEl) return;
    const ring = points.map(p => [p.lng, p.lat]);
    const first = ring[0];
    const last = ring[ring.length - 1];
    if (first && last && (first[0] !== last[0] || first[1] !== last[1])) ring.push(first);

    geojsonEl.value = JSON.stringify({
      type: "Feature",
      properties: {},
      geometry: { type: "Polygon", coordinates: [ring] }
    });
  }

  function openModalWithShape(shape) {
    if (!modalEl || !formEl) return;
    if (!window.bootstrap?.Modal) {
      console.error("[maps] bootstrap.Modal not found (bootstrap bundle not loaded).");
      return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    if (typeEl) typeEl.value = shape.type;

    if (shape.type === "circle") {
      if (latEl) latEl.value = String(shape.lat);
      if (lngEl) lngEl.value = String(shape.lng);
      if (radEl) radEl.value = String(Math.round(shape.radius));
      if (polyEl) polyEl.value = "";
      setGeojsonCircle(shape.lat, shape.lng, shape.radius);
    }

    if (shape.type === "polygon") {
      if (polyEl) polyEl.value = JSON.stringify(shape.points);
      if (latEl) latEl.value = "";
      if (lngEl) lngEl.value = "";
      if (radEl) radEl.value = "";
      setGeojsonPolygon(shape.points);
    }

    modal.show();
  }

  // Bind submit once
  if (formEl && modalEl) {
    formEl.addEventListener("submit", async (e) => {
      e.preventDefault();

      // Fallback if geojson empty
      if (geojsonEl && !geojsonEl.value.trim()) {
        const type = (typeEl?.value || "").trim();

        if (type === "circle") {
          const lat = parseFloat(latEl?.value || "");
          const lng = parseFloat(lngEl?.value || "");
          const rad = parseFloat(radEl?.value || "");
          if (!Number.isFinite(lat) || !Number.isFinite(lng) || !Number.isFinite(rad) || rad <= 0) {
            alert("Circle requires center + radius.");
            return;
          }
          setGeojsonCircle(lat, lng, rad);
        }

        if (type === "polygon") {
          let pts;
          try { pts = JSON.parse((polyEl?.value || "").trim()); } catch { pts = null; }
          if (!Array.isArray(pts) || pts.length < 3) {
            alert("Polygon requires at least 3 points.");
            return;
          }

          const normalized = (pts[0] && typeof pts[0] === "object" && "lat" in pts[0])
            ? pts.map(p => ({ lat: Number(p.lat), lng: Number(p.lng) }))
            : pts.map(p => ({ lat: Number(p[0]), lng: Number(p[1]) }));

          setGeojsonPolygon(normalized);
        }
      }

      const payload = new FormData(formEl);

      let res;
      try {
        res = await fetch("/admin/geofences/store", { method: "POST", body: payload });
      } catch (err) {
        console.error("[maps] geofence store failed", err);
        alert("Error saving geofence.");
        return;
      }

      if (!res.ok) {
        alert("Failed to save geofence.");
        return;
      }

      bootstrap.Modal.getInstance(modalEl)?.hide();
      location.reload();
    });
  }

  // Draw tools
  const hasDraw = (typeof L.Draw !== "undefined") && L.Draw && L.Draw.Event;
  if (hasDraw) {
    const drawControl = {
      circle: new L.Draw.Circle(map, { showRadius: true, shapeOptions: { color: "#ff6600", weight: 2 } }),
      polygon: new L.Draw.Polygon(map, { allowIntersection: false, showArea: true, shapeOptions: { color: "#0066ff", weight: 2 } })
    };

    if (btnDrawCircle) btnDrawCircle.addEventListener("click", () => drawControl.circle.enable());
    if (btnDrawPolygon) btnDrawPolygon.addEventListener("click", () => drawControl.polygon.enable());

    map.on(L.Draw.Event.CREATED, function (e) {
      const layer = e.layer;
      drawnItems.addLayer(layer);

      if (e.layerType === "circle") {
        const center = layer.getLatLng();
        const radius = layer.getRadius();
        openModalWithShape({ type: "circle", lat: center.lat, lng: center.lng, radius });
        return;
      }

      if (e.layerType === "polygon") {
        const points = layer.getLatLngs()[0].map(p => ({ lat: p.lat, lng: p.lng }));
        openModalWithShape({ type: "polygon", points });
        return;
      }
    });
  } else {
    console.warn("[maps] Leaflet.draw not loaded; draw tools disabled.");
  }

  // ---------- Telemetry handler ----------
  let activeModalVehicleId = null;
  let followVehicleId = null;

  function handleTelemetryUpdate(data) {
    const { vehicle_id, latitude, longitude, speed, heading, timestamp } = data || {};
    if (vehicle_id === undefined || latitude === undefined || longitude === undefined) return;

    const id = String(vehicle_id);
    const lat = Number(latitude);
    const lng = Number(longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

    const spd = Number(speed) || 0;
    const latlng = [lat, lng];

    const tsDate = new Date(String(timestamp || "").replace(" ", "T") + "Z");
    if (!telemetryHistory[id]) telemetryHistory[id] = [];
    telemetryHistory[id].push({
      lat, lng,
      speed: spd,
      heading: (typeof heading === "number") ? heading : null,
      tsDate
    });
    if (telemetryHistory[id].length > MAX_TRAIL_POINTS) telemetryHistory[id].shift();

    // marker
    if (!vehicleMarkers[id]) {
      const marker = L.circleMarker(latlng, {
        radius: 8,
        weight: 1,
        color: "#000",
        fillColor: getSpeedColor(spd),
        fillOpacity: 0.9
      });
      marker.on("click", () => openVehicleModal(id));
      vehicleMarkers[id] = marker;

      if (clusterGroup.addLayer) clusterGroup.addLayer(marker);
      else marker.addTo(map);
    } else {
      const marker = vehicleMarkers[id];
      marker.setLatLng(latlng);
      marker.setStyle({ fillColor: getSpeedColor(spd) });
    }

    // trail
    if (!vehicleTrails[id]) vehicleTrails[id] = [];
    vehicleTrails[id].push(latlng);
    if (vehicleTrails[id].length > MAX_TRAIL_POINTS) vehicleTrails[id].shift();

    if (!trailPolylines[id]) {
      trailPolylines[id] = L.polyline(vehicleTrails[id], { color: getSpeedColor(spd), weight: 3, opacity: 0.6 }).addTo(map);
    } else {
      trailPolylines[id].setLatLngs(vehicleTrails[id]);
      trailPolylines[id].setStyle({ color: getSpeedColor(spd) });
    }

    // heatmap
    if (heatLayer) {
      const heatPoints = [];
      Object.values(telemetryHistory).forEach(list => {
        const last = list[list.length - 1];
        if (last) heatPoints.push([last.lat, last.lng, 0.6]);
      });
      heatLayer.setLatLngs(heatPoints);
    }

    updateVehicleRow(id, latlng, spd, String(timestamp || ""));
    if (lastUpdateLabel) lastUpdateLabel.textContent = new Date().toISOString().slice(11, 19) + " UTC";

    checkGeofences(id, L.latLng(lat, lng));

    if (followVehicleId === id) map.panTo(latlng);
    if (activeModalVehicleId === id) renderVehicleModal(id);

    applyFilters();
  }

  // Expose for debugging if you want:
  window.handleTelemetryUpdate = handleTelemetryUpdate;

  // ---------- Vehicle modal ----------
  const vehicleModalEl = document.getElementById("vehicleModal");
  const modalIdEl = document.getElementById("modal-veh-id");
  const modalStatusEl = document.getElementById("modal-current-status");
  const modalStatsEl = document.getElementById("modal-stats");
  const modalPointsEl = document.getElementById("modal-last-points");
  const modalFollowBtn = document.getElementById("modal-follow-btn");
  const modalExportBtn = document.getElementById("modal-export-btn");

  if (vehicleModalEl) {
    vehicleModalEl.addEventListener("hidden.bs.modal", () => {
      if (followVehicleId === activeModalVehicleId) followVehicleId = null;
      activeModalVehicleId = null;
    });
  }

  function haversineKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(lat1 * Math.PI / 180) *
      Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }

  let miniMap = null, miniTrail = null, miniMarker = null;

  function renderVehicleModal(id) {
    if (!modalIdEl) return;
    const hist = telemetryHistory[id] || [];
    if (!hist.length) return;

    const last = hist[hist.length - 1];

    modalIdEl.textContent = id;
    if (modalStatusEl) modalStatusEl.innerHTML = "";
    if (modalStatsEl) modalStatsEl.innerHTML = "";
    if (modalPointsEl) modalPointsEl.innerHTML = "";

    const liSpeed = document.createElement("li");
    liSpeed.innerHTML = `<strong>Speed:</strong> ${last.speed} km/h`;
    const liPos = document.createElement("li");
    liPos.innerHTML = `<strong>Position:</strong> ${last.lat.toFixed(5)}, ${last.lng.toFixed(5)}`;
    const liTime = document.createElement("li");
    liTime.innerHTML = `<strong>Last ping (UTC):</strong> ${last.tsDate.toISOString().slice(11, 19)}`;

    if (modalStatusEl) {
      modalStatusEl.appendChild(liSpeed);
      modalStatusEl.appendChild(liPos);
      modalStatusEl.appendChild(liTime);
      if (last.heading !== null && last.heading !== undefined) {
        const liHeading = document.createElement("li");
        liHeading.innerHTML = `<strong>Heading:</strong> ${last.heading}°`;
        modalStatusEl.appendChild(liHeading);
      }
    }

    const STAT_POINTS = 30;
    const windowPoints = hist.slice(-STAT_POINTS);
    if (windowPoints.length && modalStatsEl) {
      const totalSpeed = windowPoints.reduce((s, p) => s + p.speed, 0);
      const maxSpeed = windowPoints.reduce((m, p) => Math.max(m, p.speed), 0);

      let totalDist = 0;
      for (let i = 1; i < windowPoints.length; i++) {
        const p1 = windowPoints[i - 1];
        const p2 = windowPoints[i];
        totalDist += haversineKm(p1.lat, p1.lng, p2.lat, p2.lng);
      }

      const liAvg = document.createElement("li");
      liAvg.innerHTML = `<strong>Avg speed:</strong> ${(totalSpeed / windowPoints.length).toFixed(1)} km/h`;
      const liMax = document.createElement("li");
      liMax.innerHTML = `<strong>Max speed:</strong> ${maxSpeed.toFixed(0)} km/h`;
      const liDist = document.createElement("li");
      liDist.innerHTML = `<strong>Distance:</strong> ${totalDist.toFixed(2)} km`;

      modalStatsEl.appendChild(liAvg);
      modalStatsEl.appendChild(liMax);
      modalStatsEl.appendChild(liDist);
    }

    if (modalPointsEl) {
      hist.slice(-10).reverse().forEach(p => {
        const li = document.createElement("li");
        li.textContent = `${p.tsDate.toISOString().slice(11, 19)} — ${p.lat.toFixed(5)}, ${p.lng.toFixed(5)} (${p.speed} km/h)`;
        modalPointsEl.appendChild(li);
      });
    }

    const miniDiv = document.getElementById("modal-mini-map");
    if (!miniDiv) return;

    if (!miniMap) {
      miniMap = L.map(miniDiv, { zoomControl: false }).setView([last.lat, last.lng], 15);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 19 }).addTo(miniMap);
    } else {
      miniMap.invalidateSize();
      miniMap.setView([last.lat, last.lng], 15);
    }

    if (miniTrail) miniMap.removeLayer(miniTrail);
    if (miniMarker) miniMap.removeLayer(miniMarker);

    miniTrail = L.polyline(hist.slice(-10).map(p => [p.lat, p.lng]), { color: "#0d6efd", weight: 3 }).addTo(miniMap);
    miniMarker = L.circleMarker([last.lat, last.lng], { radius: 7, weight: 2, color: "#000", fillColor: "#0d6efd", fillOpacity: 0.9 }).addTo(miniMap);

    if (modalFollowBtn) {
      if (followVehicleId === id) {
        modalFollowBtn.classList.remove("btn-outline-primary");
        modalFollowBtn.classList.add("btn-primary");
        modalFollowBtn.innerHTML = '<i class="bi bi-person-bounding-box"></i> Following';
      } else {
        modalFollowBtn.classList.add("btn-outline-primary");
        modalFollowBtn.classList.remove("btn-primary");
        modalFollowBtn.innerHTML = '<i class="bi bi-person-bounding-box"></i> Follow';
      }
    }
  }

  function openVehicleModal(id) {
    if (!vehicleModalEl || !window.bootstrap?.Modal) return;
    activeModalVehicleId = id;
    renderVehicleModal(id);
    bootstrap.Modal.getOrCreateInstance(vehicleModalEl).show();
  }

  if (modalFollowBtn) {
    modalFollowBtn.addEventListener("click", () => {
      if (!activeModalVehicleId) return;
      followVehicleId = (followVehicleId === activeModalVehicleId) ? null : activeModalVehicleId;
      renderVehicleModal(activeModalVehicleId);
    });
  }

  if (modalExportBtn) {
    modalExportBtn.addEventListener("click", () => {
      if (!activeModalVehicleId) return;
      const id = activeModalVehicleId;
      const hist = telemetryHistory[id] || [];
      if (!hist.length) return;

      let csv = "time_utc,latitude,longitude,speed_kmh,heading\n";
      hist.forEach(p => {
        const t = p.tsDate.toISOString();
        const h = (p.heading === null || p.heading === undefined) ? "" : p.heading;
        csv += `${t},${p.lat},${p.lng},${p.speed},${h}\n`;
      });

      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `vehicle_${id}_telemetry.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    });
  }

  // ---------- Playback ----------
  const btnPlayback = document.getElementById("btn-playback");
  const playbackProgress = document.getElementById("playback-progress");
  let playbackTimer = null;

  if (btnPlayback && playbackSelect) {
    btnPlayback.addEventListener("click", () => {
      const vid = playbackSelect.value;
      if (!vid) return;
      startPlayback(vid);
    });
  }

  function startPlayback(id) {
    if (playbackTimer) clearInterval(playbackTimer);
    playbackTimer = null;

    const hist = telemetryHistory[id] || [];
    if (!hist.length) return;

    const cutoff = new Date(hist[hist.length - 1].tsDate.getTime() - 2 * 60 * 1000);
    const windowPoints = hist.filter(p => p.tsDate >= cutoff);
    if (windowPoints.length < 2) return;

    let idx = 0;
    const ghost = L.circleMarker([windowPoints[0].lat, windowPoints[0].lng], {
      radius: 9, weight: 2, color: "#000", fillColor: "#20c997", fillOpacity: 0.9
    }).addTo(map);

    playbackTimer = setInterval(() => {
      idx++;
      if (idx >= windowPoints.length) {
        clearInterval(playbackTimer);
        playbackTimer = null;
        if (playbackProgress) playbackProgress.style.width = "0%";
        map.removeLayer(ghost);
        return;
      }
      const p = windowPoints[idx];
      ghost.setLatLng([p.lat, p.lng]);
      if (playbackProgress) {
        playbackProgress.style.width = (idx / (windowPoints.length - 1) * 100).toFixed(0) + "%";
      }
    }, 300);
  }

  // ---------- WebSocket ----------
  const wsPill = document.getElementById("ws-status-pill");
  function setPill(text, cls) {
    if (!wsPill) return;
    wsPill.textContent = text;
    wsPill.className = "badge connection-pill " + cls;
  }

  function connectWS() {
    const url = (location.protocol === "https:" ? "wss://" : "ws://") + location.host + "/ws";
    const ws = new WebSocket(url);

    setPill("WS: Connecting…", "text-bg-secondary");

    ws.onopen = () => setPill("WS: Connected", "text-bg-success");
    ws.onerror = () => setPill("WS: Error", "text-bg-danger");
    ws.onclose = () => {
      setPill("WS: Reconnecting…", "text-bg-warning");
      setTimeout(connectWS, 2000);
    };
    ws.onmessage = (event) => {
      try {
        handleTelemetryUpdate(JSON.parse(event.data));
      } catch {
        // ignore non-JSON
      }
    };
  }

  connectWS();
})();
