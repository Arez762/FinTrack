<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>FinTrack Transaction Report</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 2px;
            color: #0369a1;
        }
        .subtitle {
            font-size: 11px;
            color: #64748b;
            margin: 0 0 16px;
        }
        .meta {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }
        .meta td {
            padding: 2px 0;
            font-size: 10px;
            color: #475569;
        }
        .meta td.label {
            width: 90px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 9px;
        }
        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin: 0 -8px 16px;
        }
        .summary td {
            width: 33%;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
        }
        .summary .caption {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .summary .value {
            font-size: 15px;
            font-weight: bold;
        }
        .income { color: #059669; }
        .expense { color: #dc2626; }
        .balance { color: #0369a1; }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th {
            background: #f0f9ff;
            border-bottom: 1px solid #bae6fd;
            color: #075985;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 6px 8px;
        }
        table.data td {
            border-bottom: 1px solid #f1f5f9;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.data td.amount {
            text-align: right;
            white-space: nowrap;
        }
        .empty {
            padding: 24px;
            text-align: center;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <h1>FinTrack</h1>
    <p class="subtitle">Transaction Report</p>

    <table class="meta">
        <tr>
            <td class="label">Period</td>
            <td>
                {{ $period['from'] ?? '-' }}
                &mdash;
                {{ $period['to'] ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">Generated</td>
            <td>{{ $generatedAt->format('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Rows</td>
            <td>{{ $transactions->count() }}</td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>
                <div class="caption">Total Income</div>
                <div class="value income">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="caption">Total Expense</div>
                <div class="value expense">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="caption">Balance</div>
                <div class="value balance">Rp {{ number_format($totalIncome - $totalExpense, 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    @if ($transactions->isEmpty())
        <div class="empty">No transactions match the selected filters.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Account</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction['date'] }}</td>
                        <td>{{ $transaction['account'] ?? '-' }}</td>
                        <td>{{ $transaction['category'] ?? '-' }}</td>
                        <td>{{ ucfirst($transaction['type']) }}</td>
                        <td class="amount">Rp {{ number_format($transaction['amount'], 0, ',', '.') }}</td>
                        <td>{{ $transaction['description'] ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
