(() => {
  const $ = (id) => document.getElementById(id);

  const pill = $("pill");
  const vehicleIdEl = $("vehicleId");
  const intervalEl = $("intervalSec");
  const endpointEl = $("endpoint");

  const latEl = $("lat");
  const lngEl = $("lng");
  const accEl = $("acc");
  const speedEl = $("speed");
  const headingEl = $("heading");
  const tsEl = $("ts");

  const watchIdEl = $("watchId");
  const lastSentEl = $("lastSent");
  const queueSizeEl = $("queueSize");
  const lastHttpEl = $("lastHttp");
  const lastErrEl = $("lastErr");

  const btnStart = $("btnStart");
  const btnStop = $("btnStop");
  const btnPing = $("btnPing");

  let watchId = null;
  let lastPosition = null;

  const q = [];
  let sendTimer = null;

  // Persist vehicle id for convenience
  vehicleIdEl.value = localStorage.getItem("tt_vehicle_id") || "";

  function setPill(text, cls) {
    pill.className = `badge ${cls}`;
    pill.textContent = text;
  }

  function setError(msg) {
    lastErrEl.textContent = msg || "—";
  }

  function updateQueueUI() {
    queueSizeEl.textContent = String(q.length);
  }

  function formatNum(n, digits = 6) {
    if (n === null || n === undefined || Number.isNaN(n)) return "—";
    return Number(n).toFixed(digits);
  }

  function kmhFromMps(mps) {
    if (mps === null || mps === undefined || Number.isNaN(mps)) return null;
    return mps * 3.6;
  }

  function nowIso() {
    return new Date().toISOString();
  }

  function buildPayload(pos) {
    const vid = parseInt(vehicleIdEl.value, 10);
    if (!vid || vid <= 0) throw new Error("Vehicle ID is required.");

    const c = pos.coords;
    const speedKmh = kmhFromMps(c.speed);
    const heading = (c.heading === null || c.heading === undefined) ? null : c.heading;

    return {
      vehicle_id: vid,
      latitude: c.latitude,
      longitude: c.longitude,
      speed: speedKmh ?? 0,
      heading: heading ?? 0,
      timestamp: nowIso(),
    };
  }

  async function postTelemetry(payload) {
    const url = endpointEl.value || "/telemetry/ingest";

    const controller = new AbortController();
    const t = setTimeout(() => controller.abort(), 6000);

    try {
      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        signal: controller.signal,
        keepalive: true,
      });

      lastHttpEl.textContent = `${res.status} ${res.statusText}`;
      if (!res.ok) {
        const txt = await res.text().catch(() => "");
        throw new Error(`HTTP ${res.status}: ${txt || res.statusText}`);
      }

      lastSentEl.textContent = new Date().toLocaleString();
      setError("");
    } finally {
      clearTimeout(t);
    }
  }

  async function flushQueueOnce() {
    if (q.length === 0) return;

    const payload = q[0];

    try {
      await postTelemetry(payload);
      q.shift();
      updateQueueUI();
    } catch (e) {
      setError(e?.message || String(e));
    }
  }

  function startSenderLoop() {
    stopSenderLoop();
    const sec = Math.max(1, parseInt(intervalEl.value, 10) || 5);
    sendTimer = setInterval(() => {
      flushQueueOnce();
    }, sec * 1000);
  }

  function stopSenderLoop() {
    if (sendTimer) {
      clearInterval(sendTimer);
      sendTimer = null;
    }
  }

  function onPosition(pos) {
    lastPosition = pos;

    const c = pos.coords;
    latEl.textContent = formatNum(c.latitude, 7);
    lngEl.textContent = formatNum(c.longitude, 7);
    accEl.textContent = formatNum(c.accuracy, 1);
    speedEl.textContent = formatNum(kmhFromMps(c.speed), 1);
    headingEl.textContent = formatNum(c.heading, 1);
    tsEl.textContent = new Date().toLocaleString();

    try {
      const payload = buildPayload(pos);
      q.push(payload);
      updateQueueUI();
    } catch (e) {
      setError(e?.message || String(e));
    }
  }

  function onGeoError(err) {
    setPill("GPS: Error", "text-bg-danger");
    setError(err?.message || String(err));
  }

  async function start() {
    const vid = parseInt(vehicleIdEl.value, 10);
    if (!vid || vid <= 0) {
      setError("Enter a valid Vehicle ID first.");
      return;
    }

    localStorage.setItem("tt_vehicle_id", String(vid));

    if (!("geolocation" in navigator)) {
      setError("Geolocation is not available on this device/browser.");
      return;
    }

    setPill("GPS: Starting…", "text-bg-secondary");
    setError("");

    startSenderLoop();

    watchId = navigator.geolocation.watchPosition(onPosition, onGeoError, {
      enableHighAccuracy: true,
      maximumAge: 1000,
      timeout: 15000,
    });

    watchIdEl.textContent = String(watchId);
    setPill("GPS: Tracking", "text-bg-success");
    btnStart.disabled = true;
    btnStop.disabled = false;
  }

  function stop() {
    if (watchId !== null) {
      navigator.geolocation.clearWatch(watchId);
      watchId = null;
    }
    stopSenderLoop();

    watchIdEl.textContent = "—";
    setPill("Ready", "text-bg-info");
    btnStart.disabled = false;
    btnStop.disabled = true;
  }

  async function sendOne() {
    if (!lastPosition) {
      setError("No GPS fix yet. Start tracking first and wait for a location.");
      return;
    }
    try {
      const payload = buildPayload(lastPosition);
      q.push(payload);
      updateQueueUI();
      await flushQueueOnce();
    } catch (e) {
      setError(e?.message || String(e));
    }
  }

  btnStart.addEventListener("click", start);
  btnStop.addEventListener("click", stop);
  btnPing.addEventListener("click", sendOne);

  // If JS loaded successfully, show Ready immediately
  setPill("Ready", "text-bg-info");
})();