<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0b0f1a;
            color: #c9d1d9;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated gradient orbs background */
        .bg-glow {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }
        .bg-glow::before, .bg-glow::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.12;
            animation: float 20s ease-in-out infinite alternate;
        }
        .bg-glow::before {
            width: 600px; height: 600px;
            background: #667eea;
            top: -200px; left: -100px;
        }
        .bg-glow::after {
            width: 500px; height: 500px;
            background: #764ba2;
            bottom: -150px; right: -100px;
            animation-delay: -10s;
        }
        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(80px, 40px) scale(1.15); }
        }

        .wrapper {
            position: relative;
            z-index: 1;
            max-width: 760px;
            margin: 0 auto;
            padding: 4rem 2rem 3rem;
        }

        /* Hero */
        .hero { text-align: center; margin-bottom: 3rem; }
        .hero-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px; height: 56px;
            background: linear-gradient(135deg, rgba(102,126,234,0.2), rgba(118,75,162,0.2));
            border: 1px solid rgba(102,126,234,0.25);
            border-radius: 16px;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
            background: linear-gradient(135deg, #fff 0%, #a5b4fc 50%, #c4b5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
        }
        .subtitle {
            font-size: 1.05rem;
            color: #8b949e;
            line-height: 1.6;
            max-width: 520px;
            margin: 0 auto;
        }
        .version-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1.25rem;
            flex-wrap: wrap;
        }
        .pill {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.3rem 0.7rem;
            border-radius: 99px;
            letter-spacing: 0.02em;
        }
        .pill-purple { background: rgba(102,126,234,0.12); color: #a5b4fc; border: 1px solid rgba(102,126,234,0.2); }
        .pill-green { background: rgba(52,211,153,0.12); color: #6ee7b7; border: 1px solid rgba(52,211,153,0.2); }

        /* Status beacon */
        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: #6ee7b7;
            background: rgba(52,211,153,0.08);
            border: 1px solid rgba(52,211,153,0.18);
            padding: 0.4rem 0.9rem;
            border-radius: 99px;
            margin-top: 1.5rem;
        }
        .beacon {
            width: 8px; height: 8px;
            background: #34d399;
            border-radius: 50%;
            position: relative;
        }
        .beacon::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: #34d399;
            opacity: 0;
            animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        @keyframes ping {
            75%, 100% { transform: scale(2.5); opacity: 0; }
        }

        /* Cards */
        .card {
            background: rgba(19, 24, 37, 0.7);
            backdrop-filter: blur(8px);
            border: 1px solid #1e2536;
            border-radius: 14px;
            margin-bottom: 1rem;
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            border-color: #2d3a54;
            box-shadow: 0 4px 24px rgba(102,126,234,0.06);
        }
        .card-header {
            padding: 0.85rem 1.25rem;
            font-size: 0.68rem;
            font-weight: 700;
            color: #8b949e;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1px solid #1e2536;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card-header .icon { font-size: 0.85rem; }

        /* Endpoint rows */
        .endpoint-row {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid rgba(30, 37, 54, 0.5);
            transition: background 0.15s;
        }
        .endpoint-row:last-child { border-bottom: none; }
        .endpoint-row:hover { background: rgba(102, 126, 234, 0.03); }
        .method {
            font-family: ui-monospace, 'Cascadia Code', Menlo, monospace;
            font-size: 0.62rem;
            font-weight: 800;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            min-width: 40px;
            text-align: center;
            letter-spacing: 0.03em;
        }
        .post { background: rgba(34,211,238,0.1); color: #22d3ee; border: 1px solid rgba(34,211,238,0.15); }
        .put { background: rgba(251,191,36,0.1); color: #fbbf24; border: 1px solid rgba(251,191,36,0.15); }
        .get { background: rgba(74,222,128,0.1); color: #4ade80; border: 1px solid rgba(74,222,128,0.15); }
        .path {
            font-family: ui-monospace, 'Cascadia Code', Menlo, monospace;
            font-size: 0.85rem;
            color: #e2e8f0;
        }
        .desc {
            color: #6b7280;
            margin-left: auto;
            font-size: 0.78rem;
            white-space: nowrap;
        }

        /* Info rows */
        .info-row {
            display: flex;
            align-items: center;
            padding: 0.65rem 1.25rem;
            border-bottom: 1px solid rgba(30, 37, 54, 0.5);
            font-size: 0.85rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #8b949e; }
        .info-value {
            margin-left: auto;
            font-family: ui-monospace, 'Cascadia Code', Menlo, monospace;
            font-size: 0.82rem;
            color: #c9d1d9;
        }
        .info-value.highlight { color: #a78bfa; }

        /* Command rows */
        .cmd-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1.25rem;
            border-bottom: 1px solid rgba(30, 37, 54, 0.5);
            font-size: 0.85rem;
        }
        .cmd-row:last-child { border-bottom: none; }
        .cmd {
            font-family: ui-monospace, 'Cascadia Code', Menlo, monospace;
            font-size: 0.8rem;
            color: #22d3ee;
            background: rgba(34,211,238,0.06);
            border: 1px solid rgba(34,211,238,0.1);
            padding: 0.2rem 0.55rem;
            border-radius: 5px;
            white-space: nowrap;
        }
        .cmd-desc { color: #6b7280; font-size: 0.78rem; margin-left: auto; }

        /* Quick links */
        .links {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .links a {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.6rem 1rem;
            background: rgba(19, 24, 37, 0.7);
            backdrop-filter: blur(8px);
            border: 1px solid #1e2536;
            border-radius: 10px;
            color: #c9d1d9;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .links a:hover {
            border-color: rgba(102,126,234,0.4);
            color: #fff;
            background: rgba(102,126,234,0.06);
            transform: translateY(-1px);
        }
        .arrow {
            font-size: 0.72rem;
            color: #4b5563;
            transition: color 0.2s, transform 0.2s;
        }
        .links a:hover .arrow { color: #667eea; transform: translateX(2px); }

        /* Footer */
        .footer {
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #1e2536;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.72rem;
            color: #4b5563;
        }
        .footer a { color: #6b7280; text-decoration: none; transition: color 0.15s; }
        .footer a:hover { color: #a5b4fc; }

        @media (max-width: 600px) {
            .wrapper { padding: 2.5rem 1.25rem 2rem; }
            h1 { font-size: 1.75rem; }
            .endpoint-row { flex-wrap: wrap; gap: 0.4rem; }
            .desc { margin-left: 0; margin-top: 0.15rem; }
        }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <div class="wrapper">
        <!-- Hero -->
        <div class="hero">
            <div class="hero-icon">&#9992;</div>
            <h1>{{ config('app.name') }}</h1>
            <p class="subtitle">
                Production-ready REST API for managing flights with nested legs and segments.
                Built for reliability, speed, and developer happiness.
            </p>
            <div class="version-row">
                <span class="pill pill-purple">Laravel {{ app()->version() }}</span>
                <span class="pill pill-purple">PHP {{ PHP_VERSION }}</span>
                <span class="pill pill-green">Horizon + Redis</span>
            </div>
            <div class="status">
                <span class="beacon"></span>
                API operational
            </div>
        </div>

        <!-- Endpoints -->
        <div class="card">
            <div class="card-header"><span class="icon">&#128268;</span> API Endpoints</div>
            <div class="endpoint-row">
                <span class="method post">POST</span>
                <span class="path">/api/flights</span>
                <span class="desc">Create flight</span>
            </div>
            <div class="endpoint-row">
                <span class="method put">PUT</span>
                <span class="path">/api/flights/{id}</span>
                <span class="desc">Update flight (async)</span>
            </div>
            <div class="endpoint-row">
                <span class="method get">GET</span>
                <span class="path">/api/flights/{id}</span>
                <span class="desc">Get flight</span>
            </div>
        </div>

        <!-- Auth -->
        <div class="card">
            <div class="card-header"><span class="icon">&#128274;</span> Authentication</div>
            <div class="info-row">
                <span class="info-label">All requests require</span>
                <span class="info-value highlight">Api-Key</span>
            </div>
            <div class="info-row">
                <span class="info-label">Updates require</span>
                <span class="info-value highlight">Idempotency-Key</span>
            </div>
        </div>

        <!-- Stack -->
        <div class="card">
            <div class="card-header"><span class="icon">&#9881;</span> Stack</div>
            <div class="info-row">
                <span class="info-label">Queue</span>
                <span class="info-value">Redis + Horizon</span>
            </div>
            <div class="info-row">
                <span class="info-label">Database</span>
                <span class="info-value">MySQL 8.4</span>
            </div>
            <div class="info-row">
                <span class="info-label">Rate limit</span>
                <span class="info-value">{{ config('services.api.rate_limit', 200) }} req/min</span>
            </div>
        </div>

        <!-- Artisan Commands -->
        <div class="card">
            <div class="card-header"><span class="icon">&#9000;</span> Artisan Commands</div>
            <div class="cmd-row">
                <span class="cmd">flights:stats</span>
                <span class="cmd-desc">Database overview</span>
            </div>
            <div class="cmd-row">
                <span class="cmd">flights:inspect {id}</span>
                <span class="cmd-desc">Display flight details</span>
            </div>
            <div class="cmd-row">
                <span class="cmd">flights:purge-idempotency</span>
                <span class="cmd-desc">Clean expired keys</span>
            </div>
        </div>

        <!-- Links -->
        <div class="links">
            <a href="/docs">Swagger UI <span class="arrow">&rarr;</span></a>
            <a href="/openapi.json">OpenAPI Spec <span class="arrow">&rarr;</span></a>
            <a href="/horizon">Horizon <span class="arrow">&rarr;</span></a>
            <a href="/admin">Admin (admin/admin) <span class="arrow">&rarr;</span></a>
        </div>

        <div class="footer">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
            <a href="/up">health check</a>
        </div>
    </div>
</body>
</html>
