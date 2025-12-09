// public/assets/js/geofence-editor.js

(function (window) {
    /**
     * GeofenceEditor
     * Re-usable Leaflet + Leaflet.Draw wrapper for circle/polygon geofences.
     *
     * Usage:
     *   GeofenceEditor.init({
     *     mapId: 'geofence-map',
     *     defaultCenter: [45.50, -73.57],
     *     defaultZoom: 13,
     *     typeSelect: document.getElementById('geo-type'),
     *     circle: {
     *       section: document.getElementById('circle-section'),
     *       latInput: document.getElementById('center_lat'),
     *       lngInput: document.getElementById('center_lng'),
     *       radiusInput: document.getElementById('radius_m')
     *     },
     *     polygon: {
     *       section: document.getElementById('polygon-section'),
     *       pointsInput: document.getElementById('polygon_points')
     *     },
     *     buttons: {
     *       circle: document.getElementById('btn-draw-circle'),
     *       polygon: document.getElementById('btn-draw-polygon'),
     *       reset: document.getElementById('btn-reset-shape')
     *     },
     *     initialData: { type: 'circle', center_lat: 45.5, center_lng: -73.5, radius_m: 500 }
     *   });
     */

    function init(options) {
        if (!options || !options.mapId) {
            console.error("GeofenceEditor.init: mapId is required");
            return null;
        }

        const mapEl = document.getElementById(options.mapId);
        if (!mapEl) {
            console.error("GeofenceEditor.init: map element not found:", options.mapId);
            return null;
        }

        const defaultCenter = Array.isArray(options.defaultCenter)
            ? options.defaultCenter
            : [45.50, -73.57];
        const defaultZoom = options.defaultZoom || 13;

        const typeSelect    = options.typeSelect || null;
        const circleCfg     = options.circle  || {};
        const polygonCfg    = options.polygon || {};
        const buttons       = options.buttons || {};
        const initialData   = options.initialData || {};

        // ------------------------
        // Leaflet map + layers
        // ------------------------
        const map = L.map(mapEl).setView(defaultCenter, defaultZoom);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19
        }).addTo(map);

        const drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        // Leaflet.Draw: only edit/remove toolbar (no default draw buttons)
        const drawControl = new L.Control.Draw({
            draw: {
                polygon: false,
                circle: false,
                rectangle: false,
                marker: false,
                polyline: false,
                circlemarker: false
            },
            edit: {
                featureGroup: drawnItems,
                remove: true
            }
        });
        map.addControl(drawControl);

        // Styles
        const circleStyle = {
            color: "#ff6600",
            weight: 2,
            fillColor: "#ffae42",
            fillOpacity: 0.25
        };
        const polygonStyle = {
            color: "#0066ff",
            weight: 2,
            fillOpacity: 0.2
        };

        // ------------------------
        // Helpers
        // ------------------------
        function setType(type) {
            if (typeSelect) typeSelect.value = type;

            if (circleCfg.section) {
                circleCfg.section.style.display = (type === "circle") ? "block" : "none";
            }
            if (polygonCfg.section) {
                polygonCfg.section.style.display = (type === "polygon") ? "block" : "none";
            }
        }

        function clearForm() {
            if (circleCfg.latInput)   circleCfg.latInput.value = "";
            if (circleCfg.lngInput)   circleCfg.lngInput.value = "";
            if (circleCfg.radiusInput) circleCfg.radiusInput.value = "";
            if (polygonCfg.pointsInput) polygonCfg.pointsInput.value = "";
        }

        function syncCircleToForm(layer) {
            const c = layer.getLatLng();
            const r = Math.round(layer.getRadius());

            if (circleCfg.latInput)   circleCfg.latInput.value = c.lat.toFixed(6);
            if (circleCfg.lngInput)   circleCfg.lngInput.value = c.lng.toFixed(6);
            if (circleCfg.radiusInput) circleCfg.radiusInput.value = r;
            if (polygonCfg.pointsInput) polygonCfg.pointsInput.value = "";

            setType("circle");
        }

        function syncPolygonToForm(layer) {
            const latlngs = layer.getLatLngs()[0] || [];
            const points = latlngs.map(p => [p.lat, p.lng]);

            if (polygonCfg.pointsInput) {
                polygonCfg.pointsInput.value = JSON.stringify(points);
            }
            if (circleCfg.latInput)   circleCfg.latInput.value = "";
            if (circleCfg.lngInput)   circleCfg.lngInput.value = "";
            if (circleCfg.radiusInput) circleCfg.radiusInput.value = "";

            setType("polygon");
        }

        function syncFromLayer(layer) {
            if (layer instanceof L.Circle) {
                syncCircleToForm(layer);
            } else if (layer instanceof L.Polygon) {
                syncPolygonToForm(layer);
            }
        }

        function clearShapes() {
            drawnItems.clearLayers();
            clearForm();
        }

        // ------------------------
        // Draw via custom buttons
        // ------------------------
        function enableCircleDraw() {
            if (typeof L.Draw.Circle === "undefined") return;
            new L.Draw.Circle(map, {
                showRadius: true,
                shapeOptions: circleStyle
            }).enable();
        }

        function enablePolygonDraw() {
            if (typeof L.Draw.Polygon === "undefined") return;
            new L.Draw.Polygon(map, {
                allowIntersection: false,
                showArea: true,
                shapeOptions: polygonStyle
            }).enable();
        }

        if (buttons.circle) {
            buttons.circle.addEventListener("click", function (e) {
                e.preventDefault();
                enableCircleDraw();
            });
        }
        if (buttons.polygon) {
            buttons.polygon.addEventListener("click", function (e) {
                e.preventDefault();
                enablePolygonDraw();
            });
        }
        if (buttons.reset) {
            buttons.reset.addEventListener("click", function (e) {
                e.preventDefault();
                clearShapes();
            });
        }

        // ------------------------
        // Leaflet.Draw events
        // ------------------------
        map.on(L.Draw.Event.CREATED, function (e) {
            const layer = e.layer;
            drawnItems.clearLayers();
            drawnItems.addLayer(layer);

            syncFromLayer(layer);

            // optional external callback
            if (typeof options.onShapeChange === "function") {
                options.onShapeChange(layer, e.layerType);
            }

            if (layer.getBounds) {
                map.fitBounds(layer.getBounds().pad(0.4));
            }
        });

        map.on("draw:edited", function (e) {
            e.layers.eachLayer(function (layer) {
                syncFromLayer(layer);
                if (typeof options.onShapeChange === "function") {
                    options.onShapeChange(layer, null);
                }
            });
        });

        map.on("draw:deleted", function (e) {
            clearForm();
            if (typeof options.onShapeChange === "function") {
                options.onShapeChange(null, null);
            }
        });

        // ------------------------
        // Load initial shape (edit mode)
        // ------------------------
        function loadInitial() {
            if (!initialData || !initialData.type) {
                // just sync field visibility
                if (typeSelect) setType(typeSelect.value);
                else setType("circle");
                return;
            }

            if (initialData.type === "circle" &&
                initialData.center_lat != null &&
                initialData.center_lng != null &&
                initialData.radius_m != null) {

                const center = L.latLng(
                    parseFloat(initialData.center_lat),
                    parseFloat(initialData.center_lng)
                );
                const radius = parseFloat(initialData.radius_m);

                const circle = L.circle(center, Object.assign({}, circleStyle, {
                    radius: radius
                }));
                drawnItems.addLayer(circle);
                syncCircleToForm(circle);
                map.fitBounds(circle.getBounds().pad(0.4));
                return;
            }

            if (initialData.type === "polygon" && initialData.polygon_points) {
                let points = [];
                try {
                    const parsed = (typeof initialData.polygon_points === "string")
                        ? JSON.parse(initialData.polygon_points)
                        : initialData.polygon_points;

                    if (Array.isArray(parsed)) {
                        points = parsed.map(p => L.latLng(parseFloat(p[0]), parseFloat(p[1])));
                    }
                } catch (err) {
                    console.error("GeofenceEditor: invalid polygon_points JSON", err);
                }

                if (points.length) {
                    const polygon = L.polygon(points, polygonStyle);
                    drawnItems.addLayer(polygon);
                    syncPolygonToForm(polygon);
                    map.fitBounds(polygon.getBounds().pad(0.4));
                    return;
                }
            }

            // fallback
            if (typeSelect) setType(typeSelect.value);
            else setType("circle");
        }

        // respond to type manual change (optional)
        if (typeSelect) {
            typeSelect.addEventListener("change", function () {
                setType(typeSelect.value);
            });
        }

        loadInitial();

        return {
            map,
            redrawFromForm: function () {
                // Optional: you could implement form→map sync later if needed
            },
            clear: clearShapes
        };
    }

    window.GeofenceEditor = { init };
})(window);
