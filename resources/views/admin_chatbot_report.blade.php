<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Activity Report Academy Next Top Model</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f3f4f6; color: #111827; }
        .page { max-width: 1280px; margin: 0 auto; background: #fff; min-height: 100vh; padding: 24px 28px 40px; }
        .topbar { display: flex; justify-content: space-between; align-items: start; gap: 16px; }
        .muted { color: #6b7280; }
        .small { font-size: 12px; }
        .title { font-size: 34px; font-weight: 700; margin: 0; }
        .subtitle { font-size: 18px; margin: 8px 0 0; }
        .rule { margin: 20px 0 16px; border: none; border-top: 2px solid #111827; }
        .actions { display: flex; gap: 10px; justify-content: end; margin: 8px 0 20px; }
        .btn { background: #111827; color: #fff; border: none; padding: 10px 16px; border-radius: 4px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 16px; }
        .card { border: 1px solid #d1d5db; background: #fafafa; text-align: center; padding: 16px 12px; }
        .card .label { font-size: 12px; text-transform: uppercase; color: #6b7280; margin-bottom: 6px; }
        .card .value { font-size: 34px; font-weight: 700; }
        .card .value.smallish { font-size: 24px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        tbody td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; }
        .nowrap { white-space: nowrap; }
        @media print {
            body { background: #fff; }
            .page { padding: 0; }
            .actions { display: none; }
        }
        @media (max-width: 900px) {
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .topbar { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div>
            <h1 class="title">Chatbot Activity Report Academy Next Top Model</h1>
            <p class="subtitle muted">Model Portfolio Management Information System</p>
            <p class="small muted">Report Period: {{ $report['period'] }}</p>
            <p class="small muted">Displaying a maximum of 100 latest logs to maintain report performance.</p>
        </div>
        <div class="small muted">Timestamp: {{ $report['generated_at'] }}</div>
    </div>

    <hr class="rule">

    <div class="actions">
        <button class="btn" onclick="window.print()">Print / Save PDF</button>
        <a href="{{ url('/admin/chatbot-management') }}" class="btn">Back</a>
    </div>

    <div class="grid">
        <div class="card">
            <div class="label">Total Conversations</div>
            <div class="value">{{ $report['stats']['total_conversations'] ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="label">Fallback Responses</div>
            <div class="value">{{ $report['stats']['fallback_count'] ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="label">Whatsapp Handoffs</div>
            <div class="value">{{ $report['stats']['handoff_count'] ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="label">Top Intent</div>
            <div class="value smallish">{{ $report['stats']['top_intent'] ?? 'N/A' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Timestamp</th>
                <th>User Message</th>
                <th>Intent</th>
                <th>Conf.</th>
                <th>Source</th>
                <th>Feedback</th>
                <th>Final Response</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $index => $row)
                <tr>
                    <td class="nowrap">{{ $index + 1 }}</td>
                    <td class="nowrap">{{ $row['timestamp'] ?? '-' }}</td>
                    <td>{{ $row['user_message'] ?? '-' }}</td>
                    <td class="nowrap">{{ $row['intent'] ?? '-' }}</td>
                    <td class="nowrap">
                        @if (isset($row['confidence']))
                            {{ number_format(((float) $row['confidence']) * 100, 1) }}%
                        @else
                            -
                        @endif
                    </td>
                    <td class="nowrap">{{ $row['source'] ?? '-' }}</td>
                    <td class="nowrap">{{ !empty($row['handoff']) ? 'handoff' : '-' }}</td>
                    <td>{{ $row['bot_response'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #6b7280;">Belum ada data chat logs untuk ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
