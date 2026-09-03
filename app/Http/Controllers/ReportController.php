<?php

namespace App\Http\Controllers;

use App\Models\CalibrationTest;
use App\Models\Factory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = CalibrationTest::with(['instrument', 'instrument.type', 'instrument.factory', 'tester', 'items'])
            ->orderBy('test_date', 'desc');

        if ($request->filled('year')) {
            $query->whereYear('test_date', $request->year);
        }
        if ($request->filled('month')) {
            $query->whereMonth('test_date', $request->month);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('factory_id')) {
            $query->whereHas('instrument', fn ($q) => $q->where('factory_id', $request->factory_id));
        }

        $results = $query->limit(500)->get();

        $summary = [
            'total' => $results->count(),
            'ok' => $results->where('status', 'OK')->count(),
            'ng' => $results->where('status', 'NG')->count(),
            'spare' => $results->where('status', 'SPARE')->count(),
            'na' => $results->where('status', 'NA')->count(),
            'service' => $results->where('status', 'SERVICE')->count(),
        ];

        return Inertia::render('Reports/Index', [
            'tests' => $results,
            'filters' => $request->only(['year', 'month', 'status', 'factory_id']),
            'factories' => Factory::orderBy('name')->get(),
            'summary' => $summary,
        ]);
    }
}
