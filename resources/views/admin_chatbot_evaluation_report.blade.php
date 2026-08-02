<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.chatbot_eval_report_title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            margin: 0;
            background: {{ !empty($isPdf) ? '#ffffff' : '#f3f4f6' }};
            color: #111827;
        }
        .page {
            max-width: 980px;
            margin: 0 auto;
            background: #fff;
            min-height: {{ !empty($isPdf) ? 'auto' : '100vh' }};
            padding: 28px 32px 40px;
        }
        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .title { font-size: 26px; font-weight: 700; margin: 0 0 6px; }
        .muted { color: #6b7280; font-size: 12px; margin: 3px 0; }
        .actions { display: {{ !empty($isPdf) ? 'none' : 'flex' }}; gap: 10px; margin: 0 0 18px; flex-wrap: wrap; }
        .btn {
            background: #111827; color: #fff; border: none; padding: 10px 16px;
            border-radius: 4px; font-weight: 600; cursor: pointer; text-decoration: none;
            display: inline-block; font-size: 13px;
        }
        .btn.green { background: #059669; }
        .btn.gray { background: #4b5563; }
        .alert {
            background: #fff7ed; border: 1px solid #fdba74; color: #9a3412;
            padding: 12px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 13px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin: 16px 0 20px;
        }
        .card {
            border: 1px solid #d1d5db;
            background: #fafafa;
            text-align: center;
            padding: 14px 10px;
        }
        .card .label { font-size: 11px; text-transform: uppercase; color: #6b7280; letter-spacing: .04em; }
        .card .value { font-size: 26px; font-weight: 700; margin-top: 6px; }
        h2 { font-size: 16px; margin: 22px 0 8px; border-left: 4px solid #111827; padding-left: 10px; }
        p { font-size: 13px; line-height: 1.55; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 7px 8px; text-align: left; }
        th { background: #f3f4f6; font-weight: 700; }
        ul { margin: 8px 0 0 18px; padding: 0; }
        li { font-size: 13px; margin-bottom: 6px; line-height: 1.45; }
        .meta-box {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            padding: 10px 12px;
            font-size: 12px;
            margin-bottom: 8px;
        }
        @media (max-width: 800px) {
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media print {
            body { background: #fff; }
            .actions { display: none !important; }
            .page { padding: 0; }
        }
    </style>
</head>
<body>
@php
    $report = $report ?? [];
    $hasReport = $hasReport ?? (!empty($report));
    $metrics = $report['metrics'] ?? [];
@endphp
<div class="page">
    <div class="header">
        <h1 class="title">{{ __('messages.chatbot_eval_report_title') }}</h1>
        <p class="muted">{{ $report['chatbot_name'] ?? 'Academy Next Top Model Hybrid Chatbot' }}</p>
        <p class="muted">{{ __('messages.chatbot_eval_generated_at') }}: {{ $report['generated_at'] ?? '-' }}</p>
        <p class="muted">{{ __('messages.chatbot_eval_algorithm') }}: {{ $report['algorithm'] ?? 'Multinomial Naive Bayes + TF-IDF' }}</p>
    </div>

    @if(empty($isPdf))
        <div class="actions">
            @if($hasReport)
                <a class="btn green" href="{{ route('admin.chatbot.evaluation-pdf') }}">{{ __('messages.chatbot_download_pdf') }}</a>
                <button class="btn gray" type="button" onclick="window.print()">Print</button>
            @endif
            <a class="btn" href="{{ route('admin.chatbot.management') }}">{{ __('messages.chatbot_back') }}</a>
        </div>
    @endif

    @if(!$hasReport)
        <div class="alert">
            Belum ada hasil evaluasi tersimpan. Kembali ke halaman manajemen chatbot lalu klik
            <strong>Run Evaluation</strong> untuk menjalankan evaluasi baru.
        </div>
    @else
        <div class="grid">
            <div class="card"><div class="label">Accuracy</div><div class="value">{{ number_format($metrics['accuracy'] ?? 0, 2) }}%</div></div>
            <div class="card"><div class="label">Precision</div><div class="value">{{ number_format($metrics['precision'] ?? 0, 2) }}%</div></div>
            <div class="card"><div class="label">Recall</div><div class="value">{{ number_format($metrics['recall'] ?? 0, 2) }}%</div></div>
            <div class="card"><div class="label">F1-Score</div><div class="value">{{ number_format($metrics['f1'] ?? 0, 2) }}%</div></div>
        </div>

        <h2>{{ __('messages.chatbot_eval_summary') }}</h2>
        <p>{{ $report['summary'] ?? '-' }}</p>

        <h2>{{ __('messages.chatbot_eval_details') }}</h2>
        <div class="meta-box">
            CV Mean: {{ number_format($metrics['cv_mean'] ?? 0, 2) }}%
            (±{{ number_format($metrics['cv_std'] ?? 0, 2) }})
            &nbsp;|&nbsp;
            Test samples: {{ $report['dataset']['test_samples'] ?? '-' }}
            &nbsp;|&nbsp;
            Prepared samples: {{ $report['dataset']['prepared_samples'] ?? '-' }}
            &nbsp;|&nbsp;
            Original samples: {{ $report['dataset']['original_samples'] ?? '-' }}
        </div>

        <table>
            <thead>
                <tr>
                    <th>Intent</th>
                    <th>Precision</th>
                    <th>Recall</th>
                    <th>F1</th>
                    <th>Support</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($report['per_class'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['intent'] ?? '-' }}</td>
                        <td>{{ number_format($row['precision'] ?? 0, 2) }}%</td>
                        <td>{{ number_format($row['recall'] ?? 0, 2) }}%</td>
                        <td>{{ number_format($row['f1'] ?? 0, 2) }}%</td>
                        <td>{{ $row['support'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Tidak ada data per-class.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <h2>{{ __('messages.chatbot_eval_recommendations') }}</h2>
        <ul>
            @foreach(($report['recommendations'] ?? []) as $rec)
                <li>{{ $rec }}</li>
            @endforeach
        </ul>
    @endif
</div>
</body>
</html>
