<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0b0f1a;
            color: #c9d1d9;
            min-height: 100vh;
        }

        /* Top nav */
        .topbar {
            background: rgba(13, 17, 28, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #1e2536;
            padding: 0.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-left { display: flex; align-items: center; gap: 1rem; }
        .topbar-title {
            font-weight: 700;
            font-size: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .topbar-badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
            background: rgba(102, 126, 234, 0.15);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 99px;
            color: #667eea;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .topbar-links { display: flex; gap: 0.5rem; }
        .topbar-links a {
            font-size: 0.78rem;
            color: #8b949e;
            text-decoration: none;
            padding: 0.35rem 0.7rem;
            border-radius: 6px;
            transition: all 0.15s;
        }
        .topbar-links a:hover { color: #c9d1d9; background: #161b27; }

        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }

        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: linear-gradient(145deg, #131825, #0f1320);
            border: 1px solid #1e2536;
            border-radius: 12px;
            padding: 1.25rem;
            transition: border-color 0.2s, transform 0.2s;
        }
        .stat-card:hover { border-color: #2d3a54; transform: translateY(-2px); }
        .stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #8b949e;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-value.green {
            background: linear-gradient(135deg, #34d399, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-value.amber {
            background: linear-gradient(135deg, #fbbf24, #d97706);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-value.red {
            background: linear-gradient(135deg, #f87171, #dc2626);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Sections */
        .section {
            background: #131825;
            border: 1px solid #1e2536;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .section-header {
            padding: 1rem 1.25rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #c9d1d9;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #1e2536;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-header .icon { font-size: 1rem; }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }
        th {
            text-align: left;
            padding: 0.7rem 1rem;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #8b949e;
            font-weight: 600;
            border-bottom: 1px solid #1e2536;
            background: rgba(13, 17, 28, 0.5);
        }
        td {
            padding: 0.65rem 1rem;
            border-bottom: 1px solid #141927;
            color: #c9d1d9;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(102, 126, 234, 0.03); }

        /* Env table */
        .env-key {
            font-family: ui-monospace, 'Cascadia Code', 'Fira Code', Menlo, monospace;
            color: #667eea;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .env-val {
            font-family: ui-monospace, 'Cascadia Code', 'Fira Code', Menlo, monospace;
            color: #e2e8f0;
            font-size: 0.8rem;
        }

        /* Flight details */
        .flight-id {
            font-family: ui-monospace, 'Cascadia Code', 'Fira Code', Menlo, monospace;
            font-size: 0.75rem;
            color: #667eea;
        }
        .badge {
            display: inline-block;
            font-size: 0.62rem;
            font-weight: 700;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .badge-legs { background: rgba(102, 126, 234, 0.15); color: #667eea; }
        .badge-segments { background: rgba(52, 211, 153, 0.15); color: #34d399; }

        .route-tag {
            display: inline-block;
            font-size: 0.72rem;
            font-family: ui-monospace, 'Cascadia Code', Menlo, monospace;
            color: #a78bfa;
            background: rgba(167, 139, 250, 0.08);
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            margin-right: 0.35rem;
            margin-bottom: 0.2rem;
        }

        .timestamp {
            font-size: 0.75rem;
            color: #8b949e;
        }

        /* Two column layout for env + queue */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        @media (max-width: 768px) {
            .two-col { grid-template-columns: 1fr; }
            .container { padding: 1rem; }
        }

        .empty-state {
            padding: 2rem;
            text-align: center;
            color: #8b949e;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-left">
            <span class="topbar-title">{{ config('app.name') }}</span>
            <span class="topbar-badge">Admin</span>
        </div>
        <div class="topbar-links">
            <a href="/">Home</a>
            <a href="/docs">API Docs</a>
            <a href="/horizon">Horizon</a>
            <a href="/up">Health</a>
        </div>
    </div>

    <div class="container">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Flights</div>
                <div class="stat-value">{{ number_format($stats['flights']) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Legs</div>
                <div class="stat-value">{{ number_format($stats['legs']) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Segments</div>
                <div class="stat-value green">{{ number_format($stats['segments']) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Idempotency Records</div>
                <div class="stat-value">{{ number_format($stats['idempotency_records']) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending Jobs</div>
                <div class="stat-value {{ $pendingJobs > 0 ? 'amber' : 'green' }}">{{ number_format($pendingJobs) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Failed Jobs</div>
                <div class="stat-value {{ $failedJobs > 0 ? 'red' : 'green' }}">{{ number_format($failedJobs) }}</div>
            </div>
        </div>

        <!-- Recent Flights -->
        <div class="section">
            <div class="section-header">Recent Flights (latest 10)</div>
            @if($recentFlights->isEmpty())
                <div class="empty-state">No flights yet.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Flight ID</th>
                            <th>Legs</th>
                            <th>Segments</th>
                            <th>Routes</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentFlights as $flight)
                            <tr>
                                <td><span class="flight-id">{{ $flight->id }}</span></td>
                                <td><span class="badge badge-legs">{{ $flight->legs->count() }} legs</span></td>
                                <td><span class="badge badge-segments">{{ $flight->legs->sum(fn ($l) => $l->segments->count()) }} seg</span></td>
                                <td>
                                    @foreach($flight->legs as $leg)
                                        <span class="route-tag">{{ $leg->segments->pluck('origin')->first() }} &rarr; {{ $leg->segments->pluck('destination')->last() }}</span>
                                    @endforeach
                                </td>
                                <td><span class="timestamp">{{ $flight->created_at->diffForHumans() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="two-col">
            <!-- Environment -->
            <div class="section">
                <div class="section-header">Environment</div>
                <table>
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($env as $key => $value)
                            <tr>
                                <td><span class="env-key">{{ $key }}</span></td>
                                <td><span class="env-val">{{ $value }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Queue & System -->
            <div class="section">
                <div class="section-header">System Info</div>
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="env-key">PHP</span></td>
                            <td><span class="env-val">{{ PHP_VERSION }}</span></td>
                        </tr>
                        <tr>
                            <td><span class="env-key">Laravel</span></td>
                            <td><span class="env-val">{{ app()->version() }}</span></td>
                        </tr>
                        <tr>
                            <td><span class="env-key">Server OS</span></td>
                            <td><span class="env-val">{{ php_uname('s').' '.php_uname('r') }}</span></td>
                        </tr>
                        <tr>
                            <td><span class="env-key">Memory Limit</span></td>
                            <td><span class="env-val">{{ ini_get('memory_limit') }}</span></td>
                        </tr>
                        <tr>
                            <td><span class="env-key">Max Execution</span></td>
                            <td><span class="env-val">{{ ini_get('max_execution_time') }}s</span></td>
                        </tr>
                        <tr>
                            <td><span class="env-key">Timezone</span></td>
                            <td><span class="env-val">{{ config('app.timezone') }}</span></td>
                        </tr>
                        <tr>
                            <td><span class="env-key">Horizon Prefix</span></td>
                            <td><span class="env-val">{{ config('horizon.prefix') }}</span></td>
                        </tr>
                        <tr>
                            <td><span class="env-key">Pending Jobs</span></td>
                            <td><span class="env-val">{{ $pendingJobs }}</span></td>
                        </tr>
                        <tr>
                            <td><span class="env-key">Failed Jobs</span></td>
                            <td><span class="env-val">{{ $failedJobs }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
