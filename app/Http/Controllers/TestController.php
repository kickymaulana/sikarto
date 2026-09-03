<?php

namespace App\Http\Controllers;

use App\Models\CalibrationTest;
use App\Models\CalibrationTestItem;
use App\Models\Instrument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TestController extends Controller
{
    public function index(Request $request)
    {
        $query = CalibrationTest::with(['instrument', 'instrument.type', 'instrument.factory', 'tester'])
            ->orderBy('test_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->whereHas('instrument', fn ($q) => $q->where('code', 'like', "%{$request->search}%"));
        }

        return Inertia::render('Tests/Index', [
            'tests' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tests/Create', [
            'instruments' => Instrument::with(['capacity.standards', 'factory', 'department', 'type', 'brand', 'acceptableLimit', 'specification'])
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'instrument_id' => 'required|exists:instruments,id',
            'test_date' => 'required|date|before_or_equal:today',
            'status' => 'nullable|in:OK,NG,SPARE,NA,SERVICE',
            'items' => 'nullable|array',
            'items.*.standard_value' => 'required|numeric',
            'items.*.reading_value' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $manualStatuses = ['SPARE', 'NA', 'SERVICE'];
        $isManual = isset($data['status']) && in_array($data['status'], $manualStatuses, true);

        $instrument = Instrument::with('acceptableLimit')->findOrFail($data['instrument_id']);
        $limit = $instrument->acceptableLimit;

        $corrections = [];
        $testItems = [];

        if ($isManual) {
            $status = $data['status'];
            $avgCorrection = null;
            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $correction = (float) $item['reading_value'] - (float) $item['standard_value'];
                    $within = $limit->isWithin($correction);
                    $corrections[] = $correction;
                    $testItems[] = [
                        'standard_value' => $item['standard_value'],
                        'reading_value' => $item['reading_value'],
                        'correction' => round($correction, 4),
                        'is_within_limit' => $within,
                    ];
                }
                $avgCorrection = round(array_sum($corrections) / count($corrections), 4);
            }
        } else {
            if (empty($data['items'])) {
                throw ValidationException::withMessages(['items' => 'Isi penunjukan minimal 1 titik untuk menghitung status OK/NG.']);
            }

            foreach ($data['items'] as $item) {
                $correction = (float) $item['reading_value'] - (float) $item['standard_value'];
                $within = $limit->isWithin($correction);
                $corrections[] = $correction;
                $testItems[] = [
                    'standard_value' => $item['standard_value'],
                    'reading_value' => $item['reading_value'],
                    'correction' => round($correction, 4),
                    'is_within_limit' => $within,
                ];
            }

            $avgCorrection = round(array_sum($corrections) / count($corrections), 4);
            $status = $limit->isWithin($avgCorrection) ? 'OK' : 'NG';
        }

        $testDate = Carbon::parse($data['test_date']);
        $test = CalibrationTest::create([
            'instrument_id' => $instrument->id,
            'test_date' => $testDate->toDateString(),
            'next_test_date' => $testDate->addMonth()->toDateString(),
            'tester_id' => $request->user()->id,
            'status' => $status,
            'avg_correction' => $avgCorrection,
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($testItems as $item) {
            CalibrationTestItem::create([
                'calibration_test_id' => $test->id,
                ...$item,
            ]);
        }

        return redirect()->route('tests.show', $test)
            ->with('flash', ['success' => "Pengujian tersimpan. Status: {$test->status}"]);
    }

    public function show(CalibrationTest $test)
    {
        $test->load(['instrument', 'instrument.type', 'instrument.factory', 'instrument.department',
            'instrument.brand', 'instrument.capacity', 'instrument.acceptableLimit', 'instrument.specification', 'tester', 'items']);

        return Inertia::render('Tests/Show', ['test' => $test]);
    }
}
