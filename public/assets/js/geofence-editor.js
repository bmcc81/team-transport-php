// /assets/js/geofence-editor.js
document.addEventListener("DOMContentLoaded", () => {
  const mapContainer = document.getElementById("edit-map");
  if (!mapContainer) return;

  if (!window.GeofenceEditor || typeof window.GeofenceEditor.init !== "function") {
    console.error("[geofence-editor] GeofenceEditor.init missing. Core did not export correctly.");
    return;
  }

  window._geofenceEditorInstance = window.GeofenceEditor.init({
    mapId: "edit-map",
    defaultCenter: [45.5017, -73.5673],
    defaultZoom: 13,

    typeSelect: document.getElementById("type"),

    circle: {
      section: document.getElementById("circle-section"),
      latInput: document.getElementById("center_lat"),
      lngInput: document.getElementById("center_lng"),
      radiusInput: document.getElementById("radius_m"),
    },

    polygon: {
      section: document.getElementById("polygon-section"),
      pointsInput: document.getElementById("polygon_points"),
    },

    buttons: {
      circle: document.getElementById("btn-draw-circle"),
      polygon: document.getElementById("btn-draw-polygon"),
      convert: document.getElementById("btn-convert"),
      reset: document.getElementById("btn-reset"),
      undo: document.getElementById("btn-undo"),
      redo: document.getElementById("btn-redo"),
      snap: document.getElementById("btn-toggle-snap"),
      grid: document.getElementById("btn-toggle-grid"),
    },
  });
});
