<?php

namespace App\Http\Controllers;

use App\Models\AcceptableLimit;
use App\Models\Brand;
use App\Models\Capacity;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Instrument;
use App\Models\InstrumentType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstrumentController extends Controller
{
    public function index()
    {
        $instruments = Instrument::with(['factory', 'department', 'type', 'brand', 'capacity', 'acceptableLimit', 'latestTest'])
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Instruments/Index', [
            'instruments' => $instruments,
        ]);
    }

    public function create()
    {
        return Inertia::render('Instruments/Form', [
            'instrument' => null,
            'options' => $this->options(),
        ]);
    }

    public function edit(Instrument $instrument)
    {
        return Inertia::render('Instruments/Form', [
            'instrument' => $instrument,
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:instruments,code',
            'factory_id' => 'required|exists:factories,id',
            'department_id' => 'required|exists:departments,id',
            'instrument_type_id' => 'required|exists:instrument_types,id',
            'brand_id' => 'required|exists:brands,id',
            'capacity_id' => 'required|exists:capacities,id',
            'acceptable_limit_id' => 'required|exists:acceptable_limits,id',
            'notes' => 'nullable|string',
        ]);

        Instrument::create($data);

        return redirect()->route('instruments.index')
            ->with('flash', ['success' => 'Alat ukur berhasil ditambahkan.']);
    }

    public function update(Request $request, Instrument $instrument)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:instruments,code,'.$instrument->id,
            'factory_id' => 'required|exists:factories,id',
            'department_id' => 'required|exists:departments,id',
            'instrument_type_id' => 'required|exists:instrument_types,id',
            'brand_id' => 'required|exists:brands,id',
            'capacity_id' => 'required|exists:capacities,id',
            'acceptable_limit_id' => 'required|exists:acceptable_limits,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $instrument->update($data);

        return redirect()->route('instruments.index')
            ->with('flash', ['success' => 'Alat ukur berhasil diperbarui.']);
    }

    public function destroy(Instrument $instrument)
    {
        $instrument->delete();

        return redirect()->route('instruments.index')
            ->with('flash', ['success' => 'Alat ukur berhasil dinonaktifkan.']);
    }

    private function options(): array
    {
        return [
            'factories' => Factory::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'types' => InstrumentType::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'capacities' => Capacity::orderBy('name')->get(),
            'limits' => AcceptableLimit::orderBy('name')->get(),
        ];
    }
}
