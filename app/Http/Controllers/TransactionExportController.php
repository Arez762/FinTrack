<?php

namespace App\Http\Controllers;

use App\Services\TransactionFilterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionExportController extends Controller
{
    private const HEADERS = ['Date', 'Account', 'Category', 'Type', 'Amount', 'Description'];

    public function __construct(private readonly TransactionFilterService $filters) {}

    /**
     * Download the filtered transactions as a CSV file.
     */
    public function csv(Request $request): StreamedResponse
    {
        $validated = $this->filters->validated($request);

        $query = $this->filters->query($request, $validated);

        $filename = $this->filename('csv', $validated, $this->filters->summary($query));

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, self::HEADERS);

            $query->reorder()
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->chunk(500, function ($transactions) use ($handle) {
                    foreach ($transactions as $transaction) {
                        $row = $this->filters->row($transaction);

                        fputcsv($handle, [
                            $row['date'],
                            $row['account'] ?? '',
                            $row['category'] ?? '',
                            $row['type'],
                            number_format($row['amount'], 2, '.', ''),
                            $row['description'] ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Download the filtered transactions as a PDF report.
     */
    public function pdf(Request $request): HttpResponse
    {
        $validated = $this->filters->validated($request);

        $query = $this->filters->query($request, $validated);

        $summary = $this->filters->summary($query);

        $transactions = $query->reorder()
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        return Pdf::loadView('exports.transactions-pdf', [
            'transactions' => $transactions->map(fn ($transaction) => $this->filters->row($transaction)),
            'totalIncome' => round((float) $summary->total_income, 2),
            'totalExpense' => round((float) $summary->total_expense, 2),
            'period' => $this->filters->period($validated, $summary),
            'generatedAt' => now(),
        ])->download($this->filename('pdf', $validated, $summary));
    }

    /**
     * Build an informative filename, e.g. fintrack-transactions-2026-01-01-to-2026-01-31.csv
     */
    private function filename(string $extension, array $filters, object $summary): string
    {
        $period = $this->filters->period($filters, $summary);

        $from = $period['from'] ?? now()->toDateString();
        $to = $period['to'] ?? now()->toDateString();

        return sprintf('fintrack-transactions-%s-to-%s.%s', $from, $to, $extension);
    }
}
