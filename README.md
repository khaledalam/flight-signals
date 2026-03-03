<h1 align="center">
  Flight Signals API
</h1>

<p align="center">
  A production-ready REST API for managing flights with nested legs and segments.<br>
  Built with <strong>Laravel 12</strong>, <strong>Horizon</strong>, <strong>Redis</strong>, and <strong>MySQL</strong>.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/php-%3E%3D8.5-8892BF?logo=php&logoColor=white" alt="PHP >= 8.5">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/tests-43%20passing-brightgreen?logo=pestphp" alt="Tests">
  <img src="https://img.shields.io/badge/code%20style-Pint-orange?logo=laravel" alt="Pint">
  <img src="https://img.shields.io/badge/license-MIT-blue" alt="License">
</p>

<p align="center">
  <a href="#-quickstart">Quickstart</a> ·
  <a href="#how-to-run--test-locally">Run & Test</a> ·
  <a href="#-api-documentation">API Docs</a> ·
  <a href="#-testing">Testing</a> ·
  <a href="#-performance--load-testing">Performance</a> ·
  <a href="#-artisan-commands">Commands</a> ·
  <a href="#-architecture">Architecture</a>
</p>

---

## Features

- **3 endpoints** — Create, Update (async), and Get flights with nested legs/segments
- **Idempotent updates** — `Idempotency-Key` header prevents duplicate processing
- **Async processing** — Updates dispatched via Redis queues, managed by Horizon
- **API key auth** — All endpoints protected with `Api-Key` header
- **Rate limiting** — 200 requests/minute per API key (configurable via `API_RATE_LIMIT`)
- **OpenAPI 3.0 spec** — Swagger UI at `/docs`
- **43 Pest tests** — Unit, feature, performance, architecture
- **Load testing** — k6 scripts with smoke, load, and spike scenarios

---

## Quickstart

<details>
<summary><strong>Prerequisites</strong></summary>

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [Composer](https://getcomposer.org/) (host machine, for initial install)

</details>

```bash
# Clone and install
git clone <repo-url> flight-signals && cd flight-signals
composer install

# Configure
cp .env.example .env
php artisan key:generate

# Start everything (app + MySQL + Redis + Horizon worker)
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate
```

The API is now live at **http://localhost:8080** and Swagger UI at **http://localhost:8080/docs**.

> **Note:** The port is controlled by `APP_PORT` in `.env` (default: `8080`). All URLs below use port `8080` — adjust if you changed it.

<details>
<summary><strong>Horizon Dashboard</strong></summary>

Visit **http://localhost:8080/horizon** to monitor queues, jobs, and failed jobs in real time.

</details>

<details>
<summary><strong>Shut down</strong></summary>

```bash
./vendor/bin/sail down
```

</details>

---

## How to Run & Test Locally

### 1. Start the Application

After completing the [Quickstart](#-quickstart) steps above, verify everything is running:

```bash
./vendor/bin/sail ps
```

You should see containers for **app**, **mysql**, and **redis**.

### 2. Run the Test Suite

Tests use SQLite in-memory, so **no Docker is required** to run them:

```bash
# Run all Pest tests
composer test

# Run with coverage report
composer test:coverage
```

Or run tests inside the Sail container:

```bash
make test
make cover
```

### 3. Test the API Manually

The default API key is configured in `.env` as `API_KEY` (default: `your-secret-api-key-here`). Use it in the `Api-Key` header.

**Create a flight:**

```bash
curl -s -X POST http://localhost:8080/api/flights \
  -H "Content-Type: application/json" \
  -H "Api-Key: your-secret-api-key-here" \
  -d '{
    "legs": [{
      "segments": [{
        "origin": "BCN", "destination": "LON",
        "departure": "2026-06-09T06:45:00", "arrival": "2026-06-09T10:55:00",
        "cabinClass": "Y", "airline": "UA", "flightNumber": "101"
      }]
    }]
  }' | jq .
```

**Get a flight:**

```bash
curl -s http://localhost:8080/api/flights/{flightId} \
  -H "Api-Key: your-secret-api-key-here" | jq .
```

**Update a flight (async):**

```bash
curl -s -X PUT http://localhost:8080/api/flights/{flightId} \
  -H "Content-Type: application/json" \
  -H "Api-Key: your-secret-api-key-here" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{
    "legs": [{
      "segments": [{
        "origin": "BCN", "destination": "LON",
        "departure": "2026-06-09T07:00:00", "arrival": "2026-06-09T11:00:00",
        "cabinClass": "Y", "airline": "UA", "flightNumber": "101"
      }]
    }]
  }'
# => 204 No Content
```

### 4. Useful Makefile Shortcuts

```bash
make up         # Start services
make down       # Stop services
make fresh      # Rebuild + migrate
make test       # Run Pest tests via Sail
make cover      # Tests + coverage
make lint       # Check code style
make fix        # Auto-fix code style
make shell      # Shell into container
make logs       # Tail app logs
```

---

## Environment Variables

<details>
<summary>Click to expand</summary>

| Variable | Description | Default |
|---|---|---|
| `APP_PORT` | Host port for the application | `8080` |
| `API_KEY` | Secret key for the `Api-Key` header | `your-secret-api-key-here` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` | Database host | `mysql` |
| `DB_DATABASE` | Database name | `laravel` |
| `DB_USERNAME` | Database user | `sail` |
| `DB_PASSWORD` | Database password | `password` |
| `QUEUE_CONNECTION` | Queue driver | `redis` |
| `REDIS_HOST` | Redis host | `redis` |
| `API_RATE_LIMIT` | Max requests per minute per API key | `200` |

All variables are in `.env.example`. Never commit `.env`.

</details>

---

## API Documentation

Interactive Swagger UI is available at **http://localhost:8080/docs** when running locally.

The OpenAPI 3.0 spec lives at [`openapi/openapi.json`](openapi/openapi.json).

### Endpoints

| Method | Path | Description | Auth | Status |
|--------|------|-------------|------|--------|
| `POST` | `/api/flights` | Create a flight | `Api-Key` | `201` |
| `PUT` | `/api/flights/{flightId}` | Update a flight (async) | `Api-Key` + `Idempotency-Key` | `204` |
| `GET` | `/api/flights/{flightId}` | Get a flight | `Api-Key` | `200` |

<details>
<summary><strong>curl examples</strong></summary>

**Create a flight:**

```bash
curl -s -X POST http://localhost:8080/api/flights \
  -H "Content-Type: application/json" \
  -H "Api-Key: my-secret-api-key" \
  -d '{
    "legs": [{
      "segments": [{
        "origin": "BCN", "destination": "LON",
        "departure": "2026-06-09T06:45:00", "arrival": "2026-06-09T10:55:00",
        "cabinClass": "Y", "airline": "UA", "flightNumber": "101"
      }, {
        "origin": "LON", "destination": "JFK",
        "departure": "2026-06-09T11:55:00", "arrival": "2026-06-09T14:55:00",
        "cabinClass": "Y", "airline": "UA", "flightNumber": "102"
      }]
    }, {
      "segments": [{
        "origin": "JFK", "destination": "LON",
        "departure": "2026-06-25T06:45:00", "arrival": "2026-06-25T10:55:00",
        "cabinClass": "Y", "airline": "UA", "flightNumber": "101"
      }, {
        "origin": "LON", "destination": "BCN",
        "departure": "2026-06-25T11:55:00", "arrival": "2026-06-25T13:55:00",
        "cabinClass": "Y", "airline": "UA", "flightNumber": "102"
      }]
    }]
  }' | jq .
# => { "flightId": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" }
```

**Update a flight (partial — only first leg):**

```bash
curl -s -X PUT http://localhost:8080/api/flights/{flightId} \
  -H "Content-Type: application/json" \
  -H "Api-Key: my-secret-api-key" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{
    "legs": [{
      "segments": [{
        "origin": "BCN", "destination": "LON",
        "departure": "2026-06-09T06:40:00", "arrival": "2026-06-09T10:50:00",
        "cabinClass": "Y", "airline": "UA", "flightNumber": "101"
      }, {
        "origin": "LON", "destination": "JFK",
        "departure": "2026-06-09T11:55:00", "arrival": "2026-06-09T14:55:00",
        "cabinClass": "Y", "airline": "UA", "flightNumber": "102"
      }]
    }]
  }'
# => 204 No Content
```

**Get a flight:**

```bash
curl -s http://localhost:8080/api/flights/{flightId} \
  -H "Api-Key: my-secret-api-key" | jq .
```

</details>

---

## Idempotency & Rate Limiting

### Idempotency

The `PUT /api/flights/{flightId}` endpoint requires an `Idempotency-Key` header. The system guarantees exactly-once processing:

1. On the first request, the key is stored in the `idempotent_requests` table and the update job is dispatched to Redis.
2. On any replay with the same key, the stored `204` response is returned immediately — no job is re-dispatched.

This protects against duplicate updates from retries, timeouts, or concurrent submissions.

### Rate Limiting

All API endpoints are rate-limited to **200 requests per minute** per `Api-Key` (configurable via `API_RATE_LIMIT` env variable). Exceeding the limit returns `429 Too Many Requests` with standard `Retry-After` and `X-RateLimit-*` headers.

---

## Testing

This project uses [Pest](https://pestphp.com/) with 43 tests across 11 suites.

```bash
# Run all tests (SQLite in-memory — no Docker needed)
composer test

# Run with coverage
composer test:coverage

# Run performance tests only (with profiling)
composer test:perf

# Show slowest tests
composer test:profile

# Via Sail
make test
make cover
make perf
```

<details>
<summary><strong>Test suites</strong></summary>

| Suite | Tests | What it covers |
|-------|-------|----------------|
| **Unit** | | |
| `RouteSignatureTest` | 5 | Route signature building, ordering, edge cases |
| `FlightServiceTest` | 5 | Create, positions, camelCase mapping, partial update, unmatched leg |
| `IdempotentRequestTest` | 4 | CRUD, unique constraint, key-per-route, JSON casting |
| **Feature** | | |
| `AuthenticationTest` | 3 | Missing/invalid Api-Key on all endpoints |
| `CreateFlightTest` | 6 | Happy path, validation errors, data persistence |
| `GetFlightTest` | 2 | Retrieval + 404 handling |
| `UpdateFlightTest` | 5 | Job dispatch, 204 response, actual data update, validation |
| `IdempotencyTest` | 2 | Replay returns same response, job dispatched exactly once |
| `RateLimitingTest` | 1 | 429 after exceeding threshold |
| `PerformanceTest` | 6 | Endpoint latency budgets, P95 regression, large payloads |
| `ArchitectureTest` | 4 | Layer boundaries (controllers, models, jobs, services) |

</details>

---

## Performance & Load Testing

### Latency tests (Pest)

The `PerformanceTest` suite enforces latency budgets on every endpoint:

| Endpoint | Budget | What it checks |
|----------|--------|---------------|
| `POST /api/flights` | < 200ms | Single create + 50-request sustained throughput (avg + P95) |
| `GET /api/flights/{id}` | < 100ms | Normal + 10-leg/30-segment large payload |
| `PUT /api/flights/{id}` | < 200ms | Dispatch latency + idempotency replay is faster than first request |

```bash
composer test:perf          # Run perf tests with profiling
make perf                   # Same via Sail
```

### Load testing (k6)

A full [k6](https://k6.io/) load test script is included at `tests/Load/k6-flights.js` with three scenarios:

| Scenario | VUs | Duration | Purpose |
|----------|-----|----------|---------|
| **Smoke** | 1 | 10s | Sanity check, baseline latency |
| **Load** | 0→10→0 | 50s | Moderate sustained concurrency |
| **Spike** | 0→30→0 | 20s | Sudden burst handling |

**Custom metrics tracked:** `flight_create_latency`, `flight_get_latency`, `flight_update_latency`, `error_rate`.

**Thresholds enforced:** P95 create < 500ms, P95 get < 200ms, error rate < 5%.

```bash
# Install k6
brew install k6

# Start the API
./vendor/bin/sail up -d && ./vendor/bin/sail artisan migrate

# Run all scenarios
make load

# Quick smoke test (1 VU, 10s)
make load-smoke

# Custom run
k6 run --vus 20 --duration 30s tests/Load/k6-flights.js
k6 run --env BASE_URL=http://localhost:8080 --env API_KEY=my-secret-api-key tests/Load/k6-flights.js
```

<details>
<summary><strong>Sample k6 output</strong></summary>

```
╔══════════════════════════════════════════════╗
║        Flight Signals — Load Test Report     ║
╚══════════════════════════════════════════════╝

  Create P95           142.3ms
  Create P99           287.1ms
  Get P95              28.4ms
  Get P99              61.2ms
  Update P95           95.7ms
  Update P99           183.4ms
  Error Rate           0.00%
  Flights Created      847
```

</details>

---

## Architecture

```
┌─────────┐     ┌─────────────┐     ┌──────────────┐     ┌───────┐
│  Client  │────▶│  Middleware  │────▶│  Controller  │────▶│ MySQL │
│          │     │  (Api-Key)  │     │              │     └───────┘
└─────────┘     │  (Throttle) │     │  ┌────────┐  │
                └─────────────┘     │  │ Service │  │     ┌───────┐
                                    │  └────┬───┘  │────▶│ Redis │
                                    │       │      │     └───┬───┘
                                    └───────┼──────┘         │
                                            │           ┌────▼────┐
                                            │           │ Horizon │
                                            │           │  Worker │
                                            │           └────┬────┘
                                            └────────────────┘
                                          (UpdateFlightJob)
```

<details>
<summary><strong>Key design decisions</strong></summary>

**Data model:** `Flight → Legs → Segments` with positional ordering. Flights use UUIDs.

**Leg matching on update:** Legs are matched by their **route signature** — the ordered `origin→destination` chain of segments (e.g., `BCN>LON|LON>JFK`). This allows partial updates while correctly identifying which leg to modify.

**Async updates:** The update endpoint validates input synchronously, stores the idempotency record, then dispatches an `UpdateFlightJob` to Redis. The job runs in Horizon with 3 retries and exponential backoff (5s, 30s, 60s).

**Thin controllers:** Controllers only handle HTTP concerns (validation, response formatting). Business logic lives in `FlightService`.

</details>

---

## Developer Experience

<details>
<summary><strong>Tooling</strong></summary>

| Tool | Purpose | Command |
|------|---------|---------|
| [Pint](https://laravel.com/docs/pint) | Code style | `composer fix` / `composer lint` |
| [Pest](https://pestphp.com/) | Testing + perf | `composer test` / `composer test:perf` |
| [k6](https://k6.io/) | Load testing | `make load` / `make load-smoke` |
| [Horizon](https://laravel.com/docs/horizon) | Queue dashboard | http://localhost:8080/horizon |
| [Swagger UI](https://swagger.io/tools/swagger-ui/) | API docs | http://localhost:8080/docs |
| Pre-commit hook | Auto-lint on commit | `bash scripts/install-hooks.sh` |

</details>

<details>
<summary><strong>Makefile targets</strong></summary>

```bash
make help       # Show all targets
make up         # Start services
make down       # Stop services
make fresh      # Rebuild + migrate
make test       # Run Pest via Sail
make cover      # Tests + coverage
make perf       # Performance tests + profiling
make profile    # All tests with slowest highlighted
make load       # k6 load test (all scenarios)
make load-smoke # k6 smoke test (1 VU, 10s)
make lint       # Check code style
make fix        # Auto-fix code style
make shell      # Shell into container
make logs       # Tail app logs
```

</details>

---

## Artisan Commands

Open a shell inside the container to run artisan commands directly:

```bash
# Shell into the app container
./vendor/bin/sail shell
# or
make shell
```

From inside the shell (or prefixed with `./vendor/bin/sail artisan` from the host):

### `flights:stats` — Database overview

```bash
$ sail artisan flights:stats

  INFO  Flight Signals — Database Stats.

+---------------------+----------------+
| Metric              | Value          |
+---------------------+----------------+
| Flights             | 1              |
| Legs                | 2              |
| Segments            | 4              |
| Idempotency records | 1              |
| Avg legs/flight     | 2              |
| Avg segments/leg    | 2              |
| Last created        | 27 minutes ago |
+---------------------+----------------+
```

### `flights:inspect {id}` — Display a flight with legs and segments

```bash
$ sail artisan flights:inspect 019cb527-4564-73da-b8c2-65b369738eda

  INFO  Flight 019cb527-4564-73da-b8c2-65b369738eda.

  Created: 2026-03-03 19:22:55
  Updated: 2026-03-03 19:22:55

  Leg 1 ............................................ 2 segment(s)
+------+-----+------------------+------------------+--------+-------+
| From | To  | Departure        | Arrival          | Flight | Cabin |
+------+-----+------------------+------------------+--------+-------+
| BCN  | LON | 2026-06-09 06:40 | 2026-06-09 10:50 | UA 101 | Y     |
| LON  | JFK | 2026-06-09 11:55 | 2026-06-09 14:55 | UA 102 | Y     |
+------+-----+------------------+------------------+--------+-------+
  Leg 2 ............................................ 2 segment(s)
+------+-----+------------------+------------------+--------+-------+
| From | To  | Departure        | Arrival          | Flight | Cabin |
+------+-----+------------------+------------------+--------+-------+
| JFK  | LON | 2026-06-25 06:45 | 2026-06-25 10:55 | UA 101 | Y     |
| LON  | BCN | 2026-06-25 11:55 | 2026-06-25 13:55 | UA 102 | Y     |
+------+-----+------------------+------------------+--------+-------+
```

### `flights:purge-idempotency` — Clean expired idempotency records

```bash
# Delete records older than 48 hours (interactive)
$ sail artisan flights:purge-idempotency --hours=48

 Delete 12 idempotency records older than 48h? (yes/no) [no]:
 > yes

  INFO  Purged 12 idempotency records.

# Force mode (no confirmation — useful in cron/scheduler)
$ sail artisan flights:purge-idempotency --hours=24 --force

  INFO  Purged 5 idempotency records.
```

<details>
<summary><strong>Other useful built-in commands</strong></summary>

```bash
sail artisan migrate              # Run migrations
sail artisan migrate:fresh        # Reset database
sail artisan horizon              # Start Horizon worker
sail artisan route:list           # Show all routes
sail artisan queue:failed         # List failed jobs
sail artisan queue:retry all      # Retry all failed jobs
```

</details>

---

## Project Structure

```
app/
├── Console/Commands/
│   ├── FlightInspect.php
│   ├── FlightsStats.php
│   └── PurgeIdempotencyKeys.php
├── Http/
│   ├── Controllers/FlightController.php
│   ├── Middleware/AuthenticateApiKey.php
│   └── Requests/{Create,Update}FlightRequest.php
├── Jobs/UpdateFlightJob.php
├── Models/{Flight,Leg,Segment,IdempotentRequest}.php
├── Providers/{App,Horizon}ServiceProvider.php
└── Services/FlightService.php
database/migrations/
openapi/openapi.json
tests/
├── Unit/{RouteSignature,FlightService,IdempotentRequest}Test.php
├── Feature/{Authentication,CreateFlight,GetFlight,UpdateFlight,Idempotency,RateLimiting}Test.php
├── Feature/{Performance,Architecture}Test.php
└── Load/k6-flights.js
```

---

## Author

**Khaled Alam**

- [khaledalam.net](https://khaledalam.net/)
- [LinkedIn](https://www.linkedin.com/in/khaledalam)
- [khaledalam.net@gmail.com](mailto:khaledalam.net@gmail.com)

---

## License

[MIT](LICENSE)
