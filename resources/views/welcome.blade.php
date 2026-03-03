<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: ui-monospace, 'Cascadia Code', 'Fira Code', Menlo, monospace;
            background: #0a0a0a;
            color: #e5e5e5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container { max-width: 640px; width: 100%; }
        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.25rem;
        }
        .version {
            font-size: 0.75rem;
            color: #737373;
            margin-bottom: 2rem;
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #171717;
            border: 1px solid #262626;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            margin-bottom: 2rem;
        }
        .dot {
            width: 8px; height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .section {
            background: #171717;
            border: 1px solid #262626;
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .section-header {
            padding: 0.75rem 1rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: #a3a3a3;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #262626;
        }
        .row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 1rem;
            border-bottom: 1px solid #1a1a1a;
            font-size: 0.85rem;
        }
        .row:last-child { border-bottom: none; }
        .method {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.4rem;
            border-radius: 3px;
            min-width: 36px;
            text-align: center;
        }
        .post { background: #164e63; color: #22d3ee; }
        .put { background: #422006; color: #fb923c; }
        .get { background: #052e16; color: #4ade80; }
        .path { color: #d4d4d4; }
        .desc { color: #737373; margin-left: auto; font-size: 0.75rem; }
        code { color: #a78bfa; font-size: 0.8rem; }
        .cmd {
            color: #22d3ee;
            background: #0c1a1f;
            padding: 0.15rem 0.45rem;
            border-radius: 3px;
            font-size: 0.78rem;
            white-space: nowrap;
        }
        .links {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.25rem;
        }
        .links a {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 0.85rem;
            background: #171717;
            border: 1px solid #262626;
            border-radius: 6px;
            color: #d4d4d4;
            text-decoration: none;
            font-size: 0.8rem;
            transition: border-color 0.15s, color 0.15s;
        }
        .links a:hover { border-color: #525252; color: #fff; }
        .arrow { font-size: 0.7rem; color: #525252; }
        .footer {
            margin-top: 2rem;
            font-size: 0.7rem;
            color: #525252;
        }
        .footer a { color: #737373; text-decoration: none; }
        .footer a:hover { color: #a3a3a3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ config('app.name') }}</h1>
        <div class="version">Laravel {{ app()->version() }} &middot; PHP {{ PHP_VERSION }}</div>

        <div class="status">
            <span class="dot"></span>
            API operational
        </div>

        <div class="section">
            <div class="section-header">Endpoints</div>
            <div class="row">
                <span class="method post">POST</span>
                <span class="path">/api/flights</span>
                <span class="desc">Create flight</span>
            </div>
            <div class="row">
                <span class="method put">PUT</span>
                <span class="path">/api/flights/{id}</span>
                <span class="desc">Update flight (async)</span>
            </div>
            <div class="row">
                <span class="method get">GET</span>
                <span class="path">/api/flights/{id}</span>
                <span class="desc">Get flight</span>
            </div>
        </div>

        <div class="section">
            <div class="section-header">Authentication</div>
            <div class="row">
                <span>All requests require <code>Api-Key</code> header</span>
            </div>
            <div class="row">
                <span>Updates require <code>Idempotency-Key</code> header</span>
            </div>
        </div>

        <div class="section">
            <div class="section-header">Stack</div>
            <div class="row">
                <span class="path">Queue</span>
                <span class="desc">Redis + Horizon</span>
            </div>
            <div class="row">
                <span class="path">Database</span>
                <span class="desc">MySQL 8.4</span>
            </div>
            <div class="row">
                <span class="path">Rate limit</span>
                <span class="desc">{{ config('services.api.rate_limit', 200) }} req/min per key</span>
            </div>
        </div>

        <div class="section">
            <div class="section-header">Artisan Commands</div>
            <div class="row">
                <code class="cmd">flights:stats</code>
                <span class="desc">Database overview</span>
            </div>
            <div class="row">
                <code class="cmd">flights:inspect {id}</code>
                <span class="desc">Display flight details</span>
            </div>
            <div class="row">
                <code class="cmd">flights:purge-idempotency</code>
                <span class="desc">Clean expired keys</span>
            </div>
        </div>

        <div class="links">
            <a href="/docs">Swagger UI <span class="arrow">&rarr;</span></a>
            <a href="/openapi.json">OpenAPI Spec <span class="arrow">&rarr;</span></a>
            <a href="/horizon">Horizon <span class="arrow">&rarr;</span></a>
        </div>

        <div class="footer">
            <a href="/up">health</a>
        </div>
    </div>
</body>
</html>
