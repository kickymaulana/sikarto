<?php

namespace App\Http\Controllers;

use App\Models\CalibrationTest;
use App\Models\Instrument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TestController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'in:20,50,100'],
        ]);

        $query = CalibrationTest::with(['instrument', 'instrument.type', 'instrument.factory', 'tester'])
            ->orderBy('test_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->whereHas('instrument', fn ($q) => $q->where('code', 'like', "%{$request->search}%"));
        }

        return Inertia::render('Tests/Index', [
            'tests' => $query->paginate($request->integer('per_page', 20))->withQueryString(),
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tests/Create', [
            'instruments' => Instrument::with(['capacity.groups.standards', 'factory', 'department', 'type', 'brand', 'acceptableLimit', 'specification'])
                ->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'instrument_id' => 'required|integer|exists:instruments,id',
            'test_date' => 'required|date|before_or_equal:today',
            'status' => 'nullable|in:OK,NG,SPARE,NA,SERVICE',
            'notes' => 'nullable|string',
        ]);
        $selectedStatus = $data['status'] ?? null;
        $isManual = in_array($selectedStatus, ['SPARE', 'NA', 'SERVICE'], true);
        $items = $isManual ? [] : $request->validate([
            'items' => 'required|array|min:1',
            'items.*.standard_template_id' => 'required|integer|distinct',
            'items.*.reading_value' => 'required|numeric|decimal:0,4|between:-99999999.9999,99999999.9999',
        ])['items'];

        $test = DB::transaction(function () use ($request, $data, $selectedStatus, $isManual, $items) {
            $instrument = Instrument::with(['acceptableLimit', 'capacity.groups.standards'])->lockForUpdate()->findOrFail($data['instrument_id']);
            $limit = $instrument->acceptableLimit;
            $computedStatus = null;
            $avgCorrection = null;
            $testItems = [];

            if (! $isManual) {
                $groups = $instrument->capacity?->groups ?? collect();
                $templates = $groups->flatMap(fn ($group) => $group->standards);
                $expectedIds = $templates->pluck('id')->sort()->values()->all();
                $submittedIds = collect($items)->pluck('standard_template_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                if (empty($expectedIds) || $expectedIds !== $submittedIds || $groups->contains(fn ($group) => $group->standards->isEmpty())) {
                    throw ValidationException::withMessages(['items' => 'Isi semua titik uji kapasitas tepat satu kali tanpa titik tambahan.']);
                }
                if (! $limit) {
                    throw ValidationException::withMessages(['instrument_id' => 'Batas toleransi alat tidak tersedia.']);
                }
                Validator::make($limit->toArray(), [
                    'min_correction' => 'required|numeric|decimal:0,4|between:-99999999.9999,99999999.9999',
                    'max_correction' => 'required|numeric|decimal:0,4|between:-99999999.9999,99999999.9999|gte:min_correction',
                    'unit' => 'required|string|max:20',
                ])->validate();
                $readings = collect($items)->keyBy('standard_template_id');
                foreach ($groups as $groupOrder => $group) {
                    foreach ($group->standards as $pointOrder => $standard) {
                        Validator::make(['standard_value' => $standard->standard_value], [
                            'standard_value' => 'required|numeric|decimal:0,4|between:-99999999.9999,99999999.9999',
                        ])->validate();
                        $reading = $readings[$standard->id]['reading_value'];
                        $correction = round((float) $reading - (float) $standard->standard_value, 4);
                        if (! is_finite($correction) || abs($correction) > 99999999.9999) {
                            throw ValidationException::withMessages(['items' => 'Koreksi melampaui rentang penyimpanan.']);
                        }
                        $testItems[] = [
                            'standard_value' => $standard->standard_value,
                            'reading_value' => $reading,
                            'correction' => $correction,
                            'is_within_limit' => $limit->isWithin($correction),
                            'group_order' => $groupOrder,
                            'group_name' => $group->name,
                            'reference_media' => $group->reference_media,
                            'unit' => $limit->unit,
                            'point_order' => $pointOrder,
                        ];
                    }
                }
                $avgCorrection = round(array_sum(array_column($testItems, 'correction')) / count($testItems), 4);
                $computedStatus = $limit->isWithin($avgCorrection) ? 'OK' : 'NG';
            }

            $testDate = Carbon::parse($data['test_date']);
            $test = CalibrationTest::create([
                'instrument_id' => $instrument->id,
                'test_date' => $testDate->toDateString(),
                'next_test_date' => $testDate->addMonth()->toDateString(),
                'tester_id' => $request->user()->id,
                'status' => $selectedStatus ?? $computedStatus,
                'selected_status' => $selectedStatus,
                'computed_status' => $computedStatus,
                'avg_correction' => $avgCorrection,
                'min_correction_snapshot' => $limit?->min_correction,
                'max_correction_snapshot' => $limit?->max_correction,
                'notes' => $data['notes'] ?? null,
            ]);
            $test->items()->createMany($testItems);

            return $test;
        });

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
