<?php

namespace App\Http\Controllers;

use App\Services\ReportDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Display aggregated chart data for the reports page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $validated = $request->validate([
            'range' => ['sometimes', 'nullable', Rule::in(['week', 'month', 'year'])],
            'category_range' => ['sometimes', 'nullable', Rule::in(['week', 'month', 'year'])],
        ]);

        $range = $validated['range'] ?? 'month';
        $categoryRange = $validated['category_range'] ?? 'month';

        $service = new ReportDataService();

        return Inertia::render('Reports/Index', [
            'range' => $range,
            'categoryRange' => $categoryRange,
            'monthly' => $service->incomeExpense($user, $range),
            'categoryExpense' => $service->categoryExpense($user, $categoryRange),
            'monthLabel' => Carbon::now()->format('F Y'),
        ]);
    }
}