// Geofence vehicle assignment helpers (Select2 + toggle)
document.addEventListener("DOMContentLoaded", function () {
    var vehicleSelectEl = document.getElementById("vehicle_ids");
    var appliesAllEl    = document.getElementById("applies_all");
    var wrapper         = document.getElementById("vehicle-select-wrapper");

    if (!vehicleSelectEl) return;

    // Init Select2 (requires jQuery + Select2 loaded)
    var $vehicleSelect = window.jQuery
        ? window.jQuery(vehicleSelectEl)
        : null;

    if ($vehicleSelect && $vehicleSelect.select2) {
        $vehicleSelect.select2({
            placeholder: "Select specific vehicles…",
            width: "100%",
            allowClear: true
        });
    }

    function syncVehicleSelectorState() {
        if (!appliesAllEl) return;

        var disabled = appliesAllEl.checked;

        if ($vehicleSelect) {
            $vehicleSelect.prop("disabled", disabled);

            // Clear selection if switching back to "all"
            if (disabled) {
                $vehicleSelect.val(null).trigger("change");
            }
        } else {
            vehicleSelectEl.disabled = disabled;
            if (disabled) {
                vehicleSelectEl.selectedIndex = -1;
            }
        }

        if (wrapper) {
            wrapper.style.opacity = disabled ? "0.45" : "1";
        }
    }

    if (appliesAllEl) {
        appliesAllEl.addEventListener("change", syncVehicleSelectorState);
        syncVehicleSelectorState(); // initial
    }
});
