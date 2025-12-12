let ws;

function connectWS() {
    ws = new WebSocket("wss://teamtransport.local/ws");

    const pill = document.getElementById("ws-status-pill");

    function setPill(text, cls) {
        pill.textContent = text;
        pill.className = "badge connection-pill " + cls;
    }

    ws.onopen = () => {
        setPill("WS: Connected", "text-bg-success");
    };

    ws.onerror = () => {
        setPill("WS: Error", "text-bg-danger");
    };

    ws.onclose = () => {
        setPill("WS: Reconnecting…", "text-bg-warning");

        // Auto reconnect after 2s
        setTimeout(connectWS, 2000);
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        handleTelemetryUpdate(data);
    };

    // Initial pill state
    setPill("WS: Connecting…", "text-bg-secondary");
}

connectWS();
