/**
 * k6 load test for Flight Signals API
 *
 * Install: brew install k6  (or https://k6.io/docs/getting-started/installation/)
 *
 * Before running, increase the rate limit to avoid 429s during load testing:
 *   Set API_RATE_LIMIT=10000 in .env, then restart Sail.
 *
 * Run:
 *   k6 run tests/Load/k6-flights.js
 *   k6 run --env BASE_URL=http://localhost:8080 tests/Load/k6-flights.js
 *
 * Make sure the API is running: make up && make migrate
 */

import http from "k6/http";
import { check, sleep } from "k6";
import { uuidv4 } from "https://jslib.k6.io/k6-utils/1.4.0/index.js";
import { Trend, Rate, Counter } from "k6/metrics";

// ── Custom metrics ──────────────────────────────────────────
const createLatency = new Trend("flight_create_latency", true);
const getLatency = new Trend("flight_get_latency", true);
const updateLatency = new Trend("flight_update_latency", true);
const errorRate = new Rate("error_rate");
const flightsCreated = new Counter("flights_created");

// ── Configuration ───────────────────────────────────────────
const BASE_URL = __ENV.BASE_URL || "http://localhost:8080";
const API_KEY = __ENV.API_KEY || "my-secret-api-key";

export const options = {
  scenarios: {
    smoke: {
      executor: "constant-vus",
      vus: 1,
      duration: "10s",
      tags: { scenario: "smoke" },
    },
    load: {
      executor: "ramping-vus",
      startVUs: 0,
      stages: [
        { duration: "10s", target: 10 },
        { duration: "30s", target: 10 },
        { duration: "10s", target: 0 },
      ],
      startTime: "15s",
      tags: { scenario: "load" },
    },
    spike: {
      executor: "ramping-vus",
      startVUs: 0,
      stages: [
        { duration: "5s", target: 30 },
        { duration: "10s", target: 30 },
        { duration: "5s", target: 0 },
      ],
      startTime: "70s",
      tags: { scenario: "spike" },
    },
  },
  thresholds: {
    flight_create_latency: ["p(95)<500", "p(99)<1000"],
    flight_get_latency: ["p(95)<200", "p(99)<500"],
    flight_update_latency: ["p(95)<500", "p(99)<1000"],
    error_rate: ["rate<0.1"],
    http_req_failed: ["rate<0.1"],
  },
};

const HEADERS = {
  "Content-Type": "application/json",
  "Api-Key": API_KEY,
  Accept: "application/json",
};

function createPayload() {
  return JSON.stringify({
    legs: [
      {
        segments: [
          {
            origin: "BCN",
            destination: "LON",
            departure: "2026-06-09T06:45:00",
            arrival: "2026-06-09T10:55:00",
            cabinClass: "Y",
            airline: "UA",
            flightNumber: "101",
          },
          {
            origin: "LON",
            destination: "JFK",
            departure: "2026-06-09T11:55:00",
            arrival: "2026-06-09T14:55:00",
            cabinClass: "Y",
            airline: "UA",
            flightNumber: "102",
          },
        ],
      },
      {
        segments: [
          {
            origin: "JFK",
            destination: "LON",
            departure: "2026-06-25T06:45:00",
            arrival: "2026-06-25T10:55:00",
            cabinClass: "Y",
            airline: "UA",
            flightNumber: "101",
          },
          {
            origin: "LON",
            destination: "BCN",
            departure: "2026-06-25T11:55:00",
            arrival: "2026-06-25T13:55:00",
            cabinClass: "Y",
            airline: "UA",
            flightNumber: "102",
          },
        ],
      },
    ],
  });
}

function updatePayload() {
  return JSON.stringify({
    legs: [
      {
        segments: [
          {
            origin: "BCN",
            destination: "LON",
            departure: "2026-06-09T06:40:00",
            arrival: "2026-06-09T10:50:00",
            cabinClass: "Y",
            airline: "UA",
            flightNumber: "101",
          },
          {
            origin: "LON",
            destination: "JFK",
            departure: "2026-06-09T11:55:00",
            arrival: "2026-06-09T14:55:00",
            cabinClass: "Y",
            airline: "UA",
            flightNumber: "102",
          },
        ],
      },
    ],
  });
}

/**
 * Safely parse JSON from a response, returns null on failure.
 */
function safeJson(response, field) {
  try {
    return field ? response.json(field) : response.json();
  } catch (_) {
    return null;
  }
}

// ── Main scenario ───────────────────────────────────────────
export default function () {
  // 1. Create a flight
  const createRes = http.post(`${BASE_URL}/api/flights`, createPayload(), {
    headers: HEADERS,
  });

  if (createRes.status === 0 || createRes.status === 429) {
    errorRate.add(createRes.status !== 429); // 429 is expected under load, not a real error
    sleep(1);
    return;
  }

  createLatency.add(createRes.timings.duration);
  flightsCreated.add(1);

  const flightId = safeJson(createRes, "flightId");

  const createOk = check(createRes, {
    "create: status 201": (r) => r.status === 201,
    "create: has flightId": () => !!flightId,
  });

  if (!createOk || !flightId) {
    errorRate.add(true);
    return;
  }

  errorRate.add(false);

  // 2. Get the flight
  const getRes = http.get(`${BASE_URL}/api/flights/${flightId}`, {
    headers: HEADERS,
  });

  if (getRes.status === 0 || getRes.status === 429) {
    sleep(1);
    return;
  }

  getLatency.add(getRes.timings.duration);

  check(getRes, {
    "get: status 200": (r) => r.status === 200,
    "get: has legs": () => {
      const legs = safeJson(getRes, "legs");
      return Array.isArray(legs) && legs.length >= 1;
    },
  });

  // 3. Update the flight (with unique idempotency key)
  const idempotencyKey = uuidv4();
  const updateHeaders = Object.assign({}, HEADERS, {
    "Idempotency-Key": idempotencyKey,
  });

  const updateRes = http.put(
    `${BASE_URL}/api/flights/${flightId}`,
    updatePayload(),
    { headers: updateHeaders }
  );

  if (updateRes.status !== 0 && updateRes.status !== 429) {
    updateLatency.add(updateRes.timings.duration);
  }

  check(updateRes, {
    "update: status 204": (r) => r.status === 204,
  });

  // 4. Idempotency replay (same key — should also return 204)
  const replayRes = http.put(
    `${BASE_URL}/api/flights/${flightId}`,
    updatePayload(),
    { headers: updateHeaders }
  );

  check(replayRes, {
    "replay: status 204": (r) => r.status === 204,
  });

  sleep(0.3);
}

// ── Summary ─────────────────────────────────────────────────
export function handleSummary(data) {
  const lines = [
    "",
    "╔══════════════════════════════════════════════╗",
    "║        Flight Signals — Load Test Report     ║",
    "╚══════════════════════════════════════════════╝",
    "",
  ];

  const metrics = [
    ["Create P95", data.metrics.flight_create_latency?.values?.["p(95)"]],
    ["Create P99", data.metrics.flight_create_latency?.values?.["p(99)"]],
    ["Get P95", data.metrics.flight_get_latency?.values?.["p(95)"]],
    ["Get P99", data.metrics.flight_get_latency?.values?.["p(99)"]],
    ["Update P95", data.metrics.flight_update_latency?.values?.["p(95)"]],
    ["Update P99", data.metrics.flight_update_latency?.values?.["p(99)"]],
    ["Error Rate", data.metrics.error_rate?.values?.rate],
    ["Flights Created", data.metrics.flights_created?.values?.count],
  ];

  for (const [label, value] of metrics) {
    if (value !== undefined) {
      const formatted =
        typeof value === "number" && label !== "Flights Created"
          ? label.includes("Rate")
            ? `${(value * 100).toFixed(2)}%`
            : `${value.toFixed(1)}ms`
          : `${value}`;
      lines.push(`  ${label.padEnd(20)} ${formatted}`);
    }
  }

  lines.push("");
  console.log(lines.join("\n"));

  return {
    stdout: lines.join("\n"),
  };
}
