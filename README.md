<h1 align="center">
  Flight Signals API
</h1>

<p align="center">
  A production-ready REST API for managing flights with nested legs and segments.<br>
  Built with <strong>Laravel 12</strong>, <strong>Horizon</strong>, <strong>Redis</strong>, and <strong>MySQL</strong>.
</p>

<p align="center">
  <a href="https://github.com/khaledalam/flight-signals/actions/workflows/ci.yml"><img src="https://github.com/khaledalam/flight-signals/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <img src="https://img.shields.io/badge/php-%3E%3D8.5-8892BF?logo=php&logoColor=white" alt="PHP >= 8.5">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/tests-57%20passing-brightgreen?logo=pestphp" alt="Tests">
  <img src="https://img.shields.io/badge/coverage-100%25-brightgreen" alt="Coverage">
  <img src="https://img.shields.io/badge/code%20style-Pint-orange?logo=laravel" alt="Pint">
  <img src="https://img.shields.io/badge/license-MIT-blue" alt="License">
</p>

<p align="center">
  <a href="#quickstart">Quickstart</a> &middot;
  <a href="#api-endpoints">API</a> &middot;
  <a href="#admin-dashboard">Admin</a> &middot;
  <a href="#testing">Testing</a> &middot;
  <a href="#performance--load-testing">Performance</a> &middot;
  <a href="#architecture">Architecture</a>
</p>

<br>

<p align="center">
  <img src="docs/media/homepage.png" width="49%" alt="Homepage">
  <img src="docs/media/admin.png" width="49%" alt="Admin Dashboard">
</p>

---

## Features

| | |
|---|---|
| **3 API endpoints** | Create, Update (async), and Get flights with nested legs & segments |
| **Idempotent updates** | `Idempotency-Key` header guarantees exactly-once processing |
| **Async queue** | Updates dispatched to Redis, processed by Horizon workers |
| **API key auth** | All endpoints protected via `Api-Key` header |
| **Rate limiting** | Configurable per-key throttle (default 200 req/min) |
| **Admin dashboard** | Stats, flights, env & system info at `/admin` |
| **OpenAPI 3.0** | Interactive Swagger UI at `/docs` |
| **57 Pest tests** | Unit, feature, architecture, performance (100% coverage) |
| **k6 load tests** | Smoke, load, and spike scenarios |

---

## Quickstart

> **Prerequisites:** [Docker Desktop](https://www.docker.com/products/docker-desktop/) and [Composer](https://getcomposer.org/)

```bash
git clone https://github.com/khaledalam/flight-signals.git && cd flight-signals
composer install
cp .env.example .env
php artisan key:generate

# With Make
make up        # Start app + MySQL + Redis + Horizon
make migrate   # Run migrations

# Without Make
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

The API is now live at **http://localhost:8080**.

> The port is controlled by `APP_PORT` in `.env` (default: `8080`).

| Service | URL | Auth |
|---------|-----|------|
| Homepage | http://localhost:8080 | -- |
| API | http://localhost:8080/api/flights | `Api-Key` header |
| Swagger UI | http://localhost:8080/docs | -- |
| Admin Dashboard | http://localhost:8080/admin | `admin` / `admin` |
| Horizon | http://localhost:8080/horizon | -- |
| Health check | http://localhost:8080/up | -- |

<details>
<summary><strong>Shut down</strong></summary>

```bash
make down
# or
./vendor/bin/sail down
```

</details>

---

## API Endpoints

| Method | Path | Description | Auth | Status |
|--------|------|-------------|------|--------|
| `POST` | `/api/flights` | Create a flight | `Api-Key` | `201` |
| `PUT` | `/api/flights/{flightId}` | Update a flight (async) | `Api-Key` + `Idempotency-Key` | `204` |
| `GET` | `/api/flights/{flightId}` | Get a flight | `Api-Key` | `200` |

Interactive Swagger UI at **http://localhost:8080/docs** &middot; OpenAPI spec at [`openapi/openapi.json`](openapi/openapi.json)

**Postman:** Import `openapi/openapi.json` via File &rarr; Import to get the full collection.

<p align="center">
  <img src="docs/media/postman.png" width="40%" alt="Postman Collection">
</p>

<details>
<summary><strong>Postman screenshots</strong></summary>
<br>

| Create Flight (`201`) | Update Flight (`204`) |
|:---:|:---:|
| <img src="docs/media/postman-create-flight.png" width="100%" alt="Create Flight"> | <img src="docs/media/postman-update-flight.png" width="100%" alt="Update Flight"> |

| Flight Not Found (`404`) | Missing Api-Key (`401`) |
|:---:|:---:|
| <img src="docs/media/postman-flight-not-found.png" width="100%" alt="Flight Not Found"> | <img src="docs/media/postman-missing-api-key.png" width="100%" alt="Missing Api-Key"> |

</details>

<details>
<summary><strong>POST /api/flights</strong> -- Create a flight (full example with 2 legs)</summary>

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
```

Response `201`:
```json
{ "flightId": "019cb527-4564-73da-b8c2-65b369738eda" }
```

</details>

<details>
<summary><strong>PUT /api/flights/{flightId}</strong> -- Update a flight (partial -- first leg only)</summary>

```bash
curl -s -X PUT http://localhost:8080/api/flights/019cb527-4564-73da-b8c2-65b369738eda \
  -H "Content-Type: application/json" \
  -H "Api-Key: your-secret-api-key-here" \
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
```

Response: `204 No Content`

</details>

<details>
<summary><strong>GET /api/flights/{flightId}</strong> -- Get a flight</summary>

```bash
curl -s http://localhost:8080/api/flights/019cb527-4564-73da-b8c2-65b369738eda \
  -H "Api-Key: your-secret-api-key-here" | jq .
```

Response `200`:
```json
{
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
}
```

</details>

---

## Idempotency & Rate Limiting

**Idempotency** -- The `PUT /api/flights/{flightId}` endpoint requires an `Idempotency-Key` header. The system guarantees exactly-once processing:

1. First request -- key stored in `idempotent_requests` table, update job dispatched to Redis.
2. Replay with same key -- stored `204` returned immediately, no duplicate job.

This protects against retries, network timeouts, and concurrent submissions.

**Rate Limiting** -- All API endpoints are throttled to **200 req/min** per `Api-Key` (configurable via `API_RATE_LIMIT`). Exceeding the limit returns `429 Too Many Requests`.

---

## Admin Dashboard

<p align="center">
  <img src="docs/media/admin.png" width="85%" alt="Admin Dashboard">
</p>

| | |
|---|---|
| **URL** | `/admin` |
| **Username** | `admin` |
| **Password** | `admin` |

The dashboard displays:

- **Stats** -- flights, legs, segments, idempotency records, pending/failed jobs
- **Recent flights** -- last 10 with route summary and timestamps
- **Environment** -- app config, database, queue, cache, rate limit
- **System info** -- PHP/Laravel version, OS, memory limits, timezone, Horizon prefix

---

## Testing

57 [Pest](https://pestphp.com/) tests with **100% code coverage**. Tests use SQLite in-memory -- no Docker needed.

```bash
composer test              # Run all tests
composer test:coverage     # With coverage report
composer test:perf         # Performance tests only
make test                  # Via Sail
make cover                 # Coverage via Sail
```

<details>
<summary><strong>Sample test output</strong></summary>

```
   PASS  Tests\Unit\RouteSignatureTest
  ✓ it builds a signature from a single segment
  ✓ it builds a signature from multiple segments
  ✓ it preserves segment order in signature
  ✓ it handles single-character codes
  ✓ it returns empty string for empty segments

   PASS  Tests\Unit\FlightServiceTest
  ✓ it creates a flight with legs and segments in a transaction
  ✓ it assigns correct position indices
  ✓ it maps camelCase input to snake_case columns
  ✓ it updates matching leg segments
  ✓ it skips unmatched legs without error
  ✓ it handles inverse route as a different leg

   PASS  Tests\Unit\IdempotentRequestTest
  ✓ it creates and retrieves an idempotent request
  ✓ it enforces unique key + route constraint
  ✓ it allows same key on different routes
  ✓ it casts response_body as array

   PASS  Tests\Unit\UpdateFlightJobTest
  ✓ it returns early when flight is not found
  ✓ it delegates to flight service

   PASS  Tests\Unit\HorizonGateTest
  ✓ it allows access in local environment
  ✓ it denies access in non-local environment

   PASS  Tests\Feature\AuthenticationTest
  ✓ it rejects requests without api key
  ✓ it rejects requests with invalid api key
  ✓ it rejects all endpoints without auth

   PASS  Tests\Feature\CreateFlightTest
  ✓ it creates a flight and returns uuid
  ✓ it persists legs and segments
  ✓ it validates required fields
  ✓ it validates arrival is after departure
  ✓ it requires at least one leg
  ✓ it requires at least one segment per leg

   PASS  Tests\Feature\GetFlightTest
  ✓ it returns a flight with legs and segments
  ✓ it returns 404 for non-existent flight

   PASS  Tests\Feature\UpdateFlightTest
  ✓ it dispatches update job and returns 204
  ✓ it requires idempotency key header
  ✓ it returns 404 for non-existent flight
  ✓ it validates update payload
  ✓ it actually updates segment data when job runs

   PASS  Tests\Feature\IdempotencyTest
  ✓ it returns same response on replay
  ✓ it dispatches job only once
  ✓ it handles concurrent duplicate via unique constraint
  ✓ it allows same key on different flights

   PASS  Tests\Feature\CommandsTest
  ✓ flights:stats displays table with counts
  ✓ flights:stats shows zero state
  ✓ flights:inspect shows flight details
  ✓ flights:inspect returns error for missing flight
  ✓ flights:purge-idempotency deletes old records
  ✓ flights:purge-idempotency respects hours option
  ✓ flights:purge-idempotency skips when nothing to purge
  ✓ flights:purge-idempotency force skips confirmation

   PASS  Tests\Feature\RateLimitingTest
  ✓ it returns 429 when rate limit exceeded

   PASS  Tests\Feature\PerformanceTest
  ✓ create flight responds within 200ms
  ✓ get flight responds within 100ms
  ✓ update flight dispatch responds within 200ms
  ✓ sustained create throughput stays under budget
  ✓ get flight with large payload responds within 100ms
  ✓ idempotency replay is faster than first request

   PASS  Tests\Feature\ArchitectureTest
  ✓ controllers do not extend base controller
  ✓ models extend eloquent model
  ✓ jobs implement should queue
  ✓ services are not invokable

  Tests:    57 passed (149 assertions)
  Duration: 1.84s
```

</details>

<details>
<summary><strong>Sample coverage output</strong></summary>

```
   PASS  Tests\Unit\RouteSignatureTest              5 / 5 (100%)
   PASS  Tests\Unit\FlightServiceTest               6 / 6 (100%)
   PASS  Tests\Unit\IdempotentRequestTest           4 / 4 (100%)
   PASS  Tests\Unit\UpdateFlightJobTest             2 / 2 (100%)
   PASS  Tests\Unit\HorizonGateTest                 2 / 2 (100%)
   PASS  Tests\Feature\AuthenticationTest           3 / 3 (100%)
   PASS  Tests\Feature\CreateFlightTest             6 / 6 (100%)
   PASS  Tests\Feature\GetFlightTest                2 / 2 (100%)
   PASS  Tests\Feature\UpdateFlightTest             5 / 5 (100%)
   PASS  Tests\Feature\IdempotencyTest              4 / 4 (100%)
   PASS  Tests\Feature\CommandsTest                 8 / 8 (100%)
   PASS  Tests\Feature\RateLimitingTest             1 / 1 (100%)
   PASS  Tests\Feature\PerformanceTest              6 / 6 (100%)
   PASS  Tests\Feature\ArchitectureTest             4 / 4 (100%)

  Tests:    57 passed (149 assertions)
  Duration: 2.13s

  Console/Commands/FlightInspect ............... 100.0%
  Console/Commands/FlightsStats ................ 100.0%
  Console/Commands/PurgeIdempotencyKeys ........ 100.0%
  Http/Controllers/FlightController ............ 100.0%
  Http/Middleware/AuthenticateApiKey ............ 100.0%
  Http/Requests/CreateFlightRequest ............ 100.0%
  Http/Requests/UpdateFlightRequest ............ 100.0%
  Jobs/UpdateFlightJob ......................... 100.0%
  Models/Flight ................................ 100.0%
  Models/IdempotentRequest ..................... 100.0%
  Models/Leg ................................... 100.0%
  Models/Segment ............................... 100.0%
  Providers/HorizonServiceProvider ............. 100.0%
  Services/FlightService ....................... 100.0%

  Total Coverage ............................... 100.0%
```

</details>

<details>
<summary><strong>Test suites breakdown</strong></summary>

| Suite | Tests | Covers |
|-------|-------|--------|
| `RouteSignatureTest` | 5 | Route signature building, ordering, edge cases |
| `FlightServiceTest` | 6 | Create, positions, camelCase mapping, partial update, unmatched leg |
| `IdempotentRequestTest` | 4 | CRUD, unique constraint, key-per-route, JSON casting |
| `UpdateFlightJobTest` | 2 | Flight-not-found early return, successful processing |
| `HorizonGateTest` | 2 | Local access allowed, non-local denied |
| `AuthenticationTest` | 3 | Missing/invalid Api-Key on all endpoints |
| `CreateFlightTest` | 6 | Happy path, validation errors, data persistence |
| `GetFlightTest` | 2 | Retrieval + 404 handling |
| `UpdateFlightTest` | 5 | Job dispatch, 204 response, actual data update, validation |
| `IdempotencyTest` | 4 | Replay, exactly-once dispatch, concurrent race |
| `CommandsTest` | 8 | flights:stats, flights:inspect, flights:purge-idempotency |
| `RateLimitingTest` | 1 | 429 after exceeding threshold |
| `PerformanceTest` | 6 | Endpoint latency budgets, P95 regression, large payloads |
| `ArchitectureTest` | 4 | Layer boundaries (controllers, models, jobs, services) |

</details>

---

## Performance & Load Testing

### Latency budgets (Pest)

| Endpoint | Budget | Checks |
|----------|--------|--------|
| `POST /api/flights` | < 200ms | Single create + 50-request sustained throughput (avg + P95) |
| `GET /api/flights/{id}` | < 100ms | Normal + 10-leg/30-segment large payload |
| `PUT /api/flights/{id}` | < 200ms | Dispatch latency + idempotency replay faster than first request |

### k6 load testing

Three scenarios in `tests/Load/k6-flights.js`:

| Scenario | VUs | Duration | Purpose |
|----------|-----|----------|---------|
| **Smoke** | 1 | 10s | Sanity check, baseline latency |
| **Load** | 0 &rarr; 10 &rarr; 0 | 50s | Moderate sustained concurrency |
| **Spike** | 0 &rarr; 30 &rarr; 0 | 20s | Sudden burst handling |

```bash
brew install k6             # Install k6
make load                   # All scenarios (auto-adjusts rate limit)
make load-smoke             # Quick smoke test (1 VU, 10s)
```

> `make load` sets `API_RATE_LIMIT=10000` before running and restores `200` when done.

<details>
<summary><strong>Sample k6 output</strong></summary>

```
╔══════════════════════════════════════════════╗
║        Flight Signals -- Load Test Report    ║
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
│  Client │────▶│  Middleware │────▶│  Controller  │────▶│ MySQL │
│         │     │  (Api-Key)  │     │              │     └───────┘
└─────────┘     │  (Throttle) │     │  ┌────────┐  │
                └─────────────┘     │  │Service │  │     ┌───────┐
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

**Leg matching on update:** Legs are matched by **route signature** -- the ordered `origin→destination` chain of segments (e.g., `BCN>LON|LON>JFK`). This allows partial updates while correctly identifying which leg to modify.

**Async updates:** The update endpoint validates input synchronously, stores the idempotency record, then dispatches an `UpdateFlightJob` to Redis. The job runs in Horizon with 3 retries and exponential backoff (5s, 30s, 60s).

**Thin controllers:** Controllers handle HTTP concerns only. Business logic lives in `FlightService`.

</details>

---

## Artisan Commands

```bash
make shell                  # Shell into the container
# or: ./vendor/bin/sail shell
```

<details>
<summary><strong><code>flights:stats</code></strong> -- Database overview</summary>

```bash
$ sail artisan flights:stats

  INFO  Flight Signals -- Database Stats.

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

</details>

<details>
<summary><strong><code>flights:inspect {id}</code></strong> -- Display a flight</summary>

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

</details>

<details>
<summary><strong><code>flights:purge-idempotency</code></strong> -- Clean expired records</summary>

```bash
# Interactive
$ sail artisan flights:purge-idempotency --hours=48

 Delete 12 idempotency records older than 48h? (yes/no) [no]:
 > yes

  INFO  Purged 12 idempotency records.

# Force (no confirmation -- for cron/scheduler)
$ sail artisan flights:purge-idempotency --hours=24 --force

  INFO  Purged 5 idempotency records.
```

</details>

<details>
<summary><strong>Other useful commands</strong></summary>

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

## Environment Variables

| Variable | Description | Default |
|---|---|---|
| `APP_PORT` | Host port | `8080` |
| `API_KEY` | Secret for `Api-Key` header | `your-secret-api-key-here` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` | Database host | `mysql` |
| `DB_DATABASE` | Database name | `laravel` |
| `DB_USERNAME` | Database user | `sail` |
| `DB_PASSWORD` | Database password | `password` |
| `QUEUE_CONNECTION` | Queue driver | `redis` |
| `REDIS_HOST` | Redis host | `redis` |
| `API_RATE_LIMIT` | Max requests/min per key | `200` |

All variables are in `.env.example`. Never commit `.env`.

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
| Admin Dashboard | Stats, env, flights | http://localhost:8080/admin |
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

## Project Structure

```
app/
├── Console/Commands/
│   ├── FlightInspect.php
│   ├── FlightsStats.php
│   └── PurgeIdempotencyKeys.php
├── Http/
│   ├── Controllers/{FlightController,AdminController}.php
│   ├── Middleware/{AuthenticateApiKey,BasicAuthAdmin}.php
│   └── Requests/{Create,Update}FlightRequest.php
├── Jobs/UpdateFlightJob.php
├── Models/{Flight,Leg,Segment,IdempotentRequest}.php
├── Providers/{App,Horizon}ServiceProvider.php
└── Services/FlightService.php
database/migrations/
docs/media/                 # Screenshots (homepage.png, admin.png)
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
