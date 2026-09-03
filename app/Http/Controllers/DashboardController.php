<?php

namespace App\Http\Controllers;

use App\Models\CalibrationTest;
use App\Models\Instrument;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $totalInstruments = Instrument::count();
        $dueCount = Instrument::query()
            ->whereDoesntHave('tests')
            ->orWhereHas('tests', fn ($q) => $q->where('next_test_date', '<=', $today))
            ->count();
        $overdueCount = Instrument::query()
            ->whereHas('tests', fn ($q) => $q->where('next_test_date', '<', $today))
            ->count();

        $monthTests = CalibrationTest::whereYear('test_date', now()->year)
            ->whereMonth('test_date', now()->month)
            ->get();
        $passCount = $monthTests->where('status', 'OK')->count();
        $failCount = $monthTests->where('status', 'NG')->count();

        $dueInstruments = Instrument::with(['type', 'factory', 'department', 'latestTest'])
            ->whereDoesntHave('tests')
            ->orWhereHas('tests', fn ($q) => $q->where('next_test_date', '<=', $today))
            ->orderBy('code')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'code' => $i->code,
                'type' => $i->type?->name,
                'factory' => $i->factory?->name,
                'department' => $i->department?->name,
                'latest_test_date' => $i->latestTest?->test_date?->toDateString(),
                'next_test_date' => $i->latestTest?->next_test_date?->toDateString(),
            ]);

        // Matriks 12 bulan per alat: status uji terakhir tiap bulan
        $monthlyMatrix = $this->monthlyMatrix();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => $totalInstruments,
                'due' => $dueCount,
                'overdue' => $overdueCount,
                'pass' => $passCount,
                'fail' => $failCount,
            ],
            'dueInstruments' => $dueInstruments,
            'monthlyMatrix' => $monthlyMatrix,
        ]);
    }

    private function monthlyMatrix(): array
    {
        $year = now()->year;
        $months = range(1, 12);

        $tests = CalibrationTest::with('instrument')
            ->whereYear('test_date', $year)
            ->orderBy('test_date')
            ->get(['id', 'instrument_id', 'test_date', 'status']);

        $rows = [];
        foreach (Instrument::with('type')->orderBy('code')->get() as $instrument) {
            $cells = [];
            foreach ($months as $month) {
                $cells[$month] = 'none'; // belum ada uji
            }
            foreach ($tests->where('instrument_id', $instrument->id) as $test) {
                $cells[(int) $test->test_date->format('n')] = $test->status;
            }
            $rows[] = [
                'code' => $instrument->code,
                'type' => $instrument->type?->name,
                'cells' => $cells,
            ];
        }

        return ['year' => $year, 'rows' => $rows];
    }
}
