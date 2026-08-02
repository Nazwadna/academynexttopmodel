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
        .btn { background: #111827; color: #fff; border: none; padding: 10px 16px; border-radius: 4px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-secondary { background: #6b7280; }
        .btn-accent { background: #047857; }
        .filter-box { border: 1px solid #d1d5db; background: #fafafa; padding: 16px; margin-bottom: 20px; }
        .filter-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; align-items: end; }
        .filter-field label { display: block; font-size: 11px; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; font-weight: 600; }
        .filter-field input, .filter-field select { width: 100%; box-sizing: border-box; padding: 7px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px; }
        .filter-actions { display: flex; gap: 8px; margin-top: 12px; justify-content: end; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        tbody td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; }
        .nowrap { white-space: nowrap; }
        .detail-btn { background: #2563eb; color: #fff; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(17,24,39,.6); z-index: 50; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: #fff; width: 90%; max-width: 640px; max-height: 85vh; overflow-y: auto; border-radius: 6px; padding: 24px; }
        .modal-box h3 { margin-top: 0; }
        .modal-row { display: grid; grid-template-columns: 160px 1fr; gap: 8px; padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
        .modal-row .k { font-weight: 600; color: #374151; }
        .modal-close { float: right; background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280; }
        @media print {
            body { background: #fff; }
            .page { padding: 0; }
            .actions, .filter-box, .detail-btn { display: none; }
        }
        @media (max-width: 900px) {
            .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
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
            <p class="small muted">Displaying a maximum of 100 latest logs matching the active filter.</p>
        </div>
        <div class="small muted">Timestamp: {{ $report['generated_at'] }}</div>
    </div>

    <hr class="rule">

    <div class="actions">
        <button class="btn" onclick="window.print()">Print / Save PDF</button>
        <a href="{{ url('/admin/chatbot-management') }}" class="btn btn-secondary">Back</a>
    </div>

    <form method="GET" action="{{ route('admin.chatbot.report') }}" class="filter-box">
        <div class="filter-grid">
            <div class="filter-field">
                <label for="date_from">Tanggal Mulai</label>
                <input type="date" id="date_from" name="date_from" value="{{ $report['filters']['date_from'] }}">
            </div>
            <div class="filter-field">
                <label for="date_to">Tanggal Akhir</label>
                <input type="date" id="date_to" name="date_to" value="{{ $report['filters']['date_to'] }}">
            </div>
            <div class="filter-field">
                <label for="intent">Intent</label>
                <select id="intent" name="intent">
                    <option value="">Semua Intent</option>
                    @foreach ($report['intents'] as $intentOption)
                        <option value="{{ $intentOption }}" @selected($report['filters']['intent'] === $intentOption)>{{ $intentOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="handoff">Status Handoff</label>
                <select id="handoff" name="handoff">
                    <option value="">Semua Status</option>
                    <option value="yes" @selected($report['filters']['handoff'] === 'yes')>Handoff</option>
                    <option value="no" @selected($report['filters']['handoff'] === 'no')>Tidak Handoff</option>
                </select>
            </div>
            <div class="filter-field">
                <label for="keyword">Kata Kunci</label>
                <input type="text" id="keyword" name="keyword" placeholder="cari pesan / respons..." value="{{ $report['filters']['keyword'] }}">
            </div>
        </div>
        <div class="filter-actions">
            <a href="{{ route('admin.chatbot.report') }}" class="btn btn-secondary">Reset</a>
            <a href="{{ route('admin.chatbot.report.export', array_filter($report['filters'])) }}" class="btn btn-accent">Export CSV</a>
            <button type="submit" class="btn">Filter</button>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Timestamp</th>
                <th>User Message</th>
                <th>Intent</th>
                <th>Conf.</th>
                <th>Source</th>
                <th>Handoff</th>
                <th>Final Response</th>
                <th>Aksi</th>
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
                    <td class="nowrap">
                        <button type="button" class="detail-btn"
                            data-id="{{ $row['id'] ?? '-' }}"
                            data-timestamp="{{ $row['timestamp'] ?? '-' }}"
                            data-user-message="{{ $row['user_message'] ?? '-' }}"
                            data-bot-response="{{ $row['bot_response'] ?? '-' }}"
                            data-intent="{{ $row['intent'] ?? '-' }}"
                            data-confidence="{{ isset($row['confidence']) ? number_format(((float) $row['confidence']) * 100, 1) . '%' : '-' }}"
                            data-source="{{ $row['source'] ?? '-' }}"
                            data-handoff="{{ !empty($row['handoff']) ? 'Ya' : 'Tidak' }}"
                            data-session-id="{{ $row['session_id'] ?? '-' }}"
                            data-response-time="{{ isset($row['response_time']) ? $row['response_time'] . ' detik' : '-' }}"
                            data-feedback-rating="{{ $row['feedback_rating'] ?? '-' }}"
                            data-feedback-comment="{{ $row['feedback_comment'] ?? '-' }}"
                            data-resolved="{{ isset($row['resolved']) ? ($row['resolved'] ? 'Ya' : 'Tidak') : '-' }}"
                            onclick="showChatDetail(this)">Lihat Detail</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #6b7280;">Belum ada data chat logs untuk ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="chatDetailModal" onclick="if(event.target===this) closeChatDetail()">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeChatDetail()">&times;</button>
        <h3>Detail Percakapan</h3>
        <div id="chatDetailBody"></div>
    </div>
</div>

<script>
    function showChatDetail(btn) {
        var d = btn.dataset;
        var fields = [
            ['ID', d.id],
            ['Timestamp', d.timestamp],
            ['Session ID', d.sessionId],
            ['Pesan Pengguna', d.userMessage],
            ['Respons Bot', d.botResponse],
            ['Intent', d.intent],
            ['Confidence', d.confidence],
            ['Source', d.source],
            ['Handoff', d.handoff],
            ['Response Time', d.responseTime],
            ['Feedback Rating', d.feedbackRating],
            ['Feedback Comment', d.feedbackComment],
            ['Resolved', d.resolved],
        ];
        var body = document.getElementById('chatDetailBody');
        body.innerHTML = '';
        fields.forEach(function (f) {
            var row = document.createElement('div');
            row.className = 'modal-row';

            var k = document.createElement('div');
            k.className = 'k';
            k.textContent = f[0];

            var v = document.createElement('div');
            v.textContent = f[1] || '-';

            row.appendChild(k);
            row.appendChild(v);
            body.appendChild(row);
        });
        document.getElementById('chatDetailModal').classList.add('open');
    }

    function closeChatDetail() {
        document.getElementById('chatDetailModal').classList.remove('open');
    }
</script>
</body>
</html>
