let ws = null;
let reconnectTimer = null;

function wsUrl() {
  const proto = (location.protocol === "https:") ? "wss" : "ws";
  return `${proto}://${location.host}/ws`;
}

function connectWS() {
  // prevent duplicate loops
  if (reconnectTimer) {
    clearTimeout(reconnectTimer);
    reconnectTimer = null;
  }

  // close any existing socket
  if (ws && (ws.readyState === WebSocket.OPEN || ws.readyState === WebSocket.CONNECTING)) {
    try { ws.close(); } catch {}
  }

  const pill = document.getElementById("ws-status-pill");
  const setPill = (text, cls) => {
    if (!pill) return; // do not crash if pill not present
    pill.textContent = text;
    pill.className = "badge connection-pill " + cls;
  };

  setPill("WS: Connecting…", "text-bg-secondary");

  ws = new WebSocket(wsUrl());

  ws.onopen = () => {
      setPill("WS: Connected", "text-bg-success");

    // Only if your server expects it:
    ws.send(JSON.stringify({ type: "subscribe", channel: "telemetry" }));
  };

  ws.onerror = () => {
    setPill("WS: Error", "text-bg-danger");
  };

  ws.onclose = () => {
    setPill("WS: Reconnecting…", "text-bg-warning");
    reconnectTimer = setTimeout(connectWS, 2000);
  };

  ws.onmessage = (event) => {
        // Helpful while debugging:
        // console.log("[WS] raw:", event.data);

        let data;
        try {
            data = JSON.parse(event.data);
        } catch (e) {
            // If your server ever sends plain-text pings/hello messages,
            // you’re currently dropping them silently.
            console.warn("[WS] non-JSON frame ignored:", event.data);
            return;
        }

        if (typeof handleTelemetryUpdate !== "function") {
            console.warn("[WS] handleTelemetryUpdate() not available yet. Dropping:", data);
            return;
        }

        try {
            handleTelemetryUpdate(data);
        } catch (err) {
            console.error("[WS] handleTelemetryUpdate() crashed:", err, "payload:", data);
        }
    };

    ws.onerror = (e) => {
        console.error("[WS] error event:", e);
        setPill("WS: Error", "text-bg-danger");
    };

    ws.onclose = (e) => {
        console.warn("[WS] closed:", { code: e.code, reason: e.reason, wasClean: e.wasClean });
        setPill("WS: Reconnecting…", "text-bg-warning");
        reconnectTimer = setTimeout(connectWS, 2000);
    };
}

connectWS();
