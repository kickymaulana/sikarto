<?php

namespace App\Http\Controllers;

use App\Models\AcceptableLimit;
use App\Models\Brand;
use App\Models\CalibrationTest;
use App\Models\Capacity;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Instrument;
use App\Models\InstrumentType;
use App\Models\Specification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterController extends Controller
{
    private array $entities = [
        'factories' => [
            'model' => Factory::class,
            'label' => 'Factory',
            'columns' => [['key' => 'name', 'label' => 'Nama']],
            'fields' => [['key' => 'name', 'label' => 'Nama', 'type' => 'text']],
            'rules' => ['name' => 'required|string|max:255'],
        ],
        'departments' => [
            'model' => Department::class,
            'label' => 'Departemen',
            'columns' => [['key' => 'name', 'label' => 'Nama'], ['key' => 'factory', 'label' => 'Factory']],
            'fields' => [
                ['key' => 'factory_id', 'label' => 'Factory', 'type' => 'select', 'options' => 'factories'],
                ['key' => 'name', 'label' => 'Nama', 'type' => 'text'],
            ],
            'rules' => [
                'factory_id' => 'required|exists:factories,id',
                'name' => 'required|string|max:255',
            ],
        ],
        'types' => [
            'model' => InstrumentType::class,
            'label' => 'Jenis Alat',
            'columns' => [['key' => 'name', 'label' => 'Nama']],
            'fields' => [['key' => 'name', 'label' => 'Nama', 'type' => 'text']],
            'rules' => ['name' => 'required|string|max:255'],
        ],
        'brands' => [
            'model' => Brand::class,
            'label' => 'Merk',
            'columns' => [['key' => 'name', 'label' => 'Nama']],
            'fields' => [['key' => 'name', 'label' => 'Nama', 'type' => 'text']],
            'rules' => ['name' => 'required|string|max:255'],
        ],
        'capacities' => [
            'model' => Capacity::class,
            'label' => 'Kapasitas',
            'columns' => [['key' => 'name', 'label' => 'Nama'], ['key' => 'value', 'label' => 'Nilai'], ['key' => 'unit', 'label' => 'Satuan']],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama', 'type' => 'text'],
                ['key' => 'value', 'label' => 'Nilai', 'type' => 'number', 'step' => '0.0001'],
                ['key' => 'unit', 'label' => 'Satuan', 'type' => 'text'],
            ],
            'rules' => [
                'name' => 'required|string|max:255',
                'value' => 'required|numeric',
                'unit' => 'required|string|max:20',
            ],
        ],
        'limits' => [
            'model' => AcceptableLimit::class,
            'label' => 'Acceptable Limit',
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'min_correction', 'label' => 'Min'],
                ['key' => 'max_correction', 'label' => 'Max'],
                ['key' => 'unit', 'label' => 'Satuan'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama (contoh: ±5 gr)', 'type' => 'text'],
                ['key' => 'min_correction', 'label' => 'Koreksi Min', 'type' => 'number', 'step' => '0.0001'],
                ['key' => 'max_correction', 'label' => 'Koreksi Max', 'type' => 'number', 'step' => '0.0001'],
                ['key' => 'unit', 'label' => 'Satuan', 'type' => 'text'],
            ],
            'rules' => [
                'name' => 'required|string|max:255',
                'min_correction' => 'required|numeric',
                'max_correction' => 'required|numeric|gte:min_correction',
                'unit' => 'required|string|max:20',
            ],
        ],
        'specifications' => [
            'model' => Specification::class,
            'label' => 'Spesifikasi',
            'columns' => [['key' => 'name', 'label' => 'Nama']],
            'fields' => [['key' => 'name', 'label' => 'Nama', 'type' => 'text']],
            'rules' => ['name' => 'required|string|max:255'],
        ],
    ];

    public function matrix(Request $request)
    {
        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $months = range(1, 12);

        $tests = CalibrationTest::with('instrument')
            ->whereYear('test_date', $year)
            ->orderBy('test_date')
            ->get(['id', 'instrument_id', 'test_date', 'next_test_date', 'status']);

        $instruments = Instrument::with('type')->orderBy('code')->get();

        $rows = [];
        foreach ($instruments as $instrument) {
            $testCell = [];
            $nextCell = [];
            foreach ($months as $m) {
                $testCell[$m] = ['day' => '', 'status' => 'none'];
                $nextCell[$m] = ['day' => '', 'status' => 'none'];
            }
            foreach ($tests->where('instrument_id', $instrument->id) as $test) {
                $testMonth = (int) $test->test_date->format('n');
                $nextMonth = $test->next_test_date ? (int) $test->next_test_date->format('n') : null;
                $testCell[$testMonth] = [
                    'day' => (string) (int) $test->test_date->format('j'),
                    'status' => $test->status,
                ];
                if ($nextMonth && $nextMonth !== $testMonth) {
                    $nextCell[$nextMonth] = [
                        'day' => (string) (int) $test->next_test_date->format('j'),
                        'status' => $test->status,
                    ];
                }
            }
            $rows[] = [
                'code' => $instrument->code,
                'type' => $instrument->type?->name,
                'test_cell' => $testCell,
                'next_cell' => $nextCell,
            ];
        }

        return Inertia::render('Masters/Matrix', [
            'year' => $year,
            'rows' => $rows,
            'counts' => $this->counts(),
        ]);
    }

    public function index(Request $request, string $entity)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $query = $config['model']::query();

        if ($entity === 'departments') {
            $query->with('factory');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($config, $search) {
                foreach ($config['columns'] as $column) {
                    if ($column['key'] === 'factory') {
                        $q->orWhereHas('factory', fn ($f) => $f->where('name', 'like', "%{$search}%"));
                    } else {
                        $q->orWhere($column['key'], 'like', "%{$search}%");
                    }
                }
            });
        }

        $items = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Masters/Index', [
            'entity' => $entity,
            'config' => $config,
            'items' => $items,
            'options' => $this->options($entity),
            'counts' => $this->counts(),
        ]);
    }

    public function create(string $entity)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];

        return Inertia::render('Masters/Form', [
            'entity' => $entity,
            'config' => $config,
            'item' => null,
            'options' => $this->options($entity),
            'counts' => $this->counts(),
        ]);
    }

    public function edit(string $entity, int $id)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $item = $config['model']::query();

        if ($entity === 'capacities') {
            $item->with('standards');
        }

        $item = $item->findOrFail($id);

        return Inertia::render('Masters/Form', [
            'entity' => $entity,
            'config' => $config,
            'item' => $item,
            'options' => $this->options($entity),
            'counts' => $this->counts(),
        ]);
    }

    public function store(Request $request, string $entity)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $data = $request->validate($config['rules']);

        $record = $config['model']::create($data);

        if ($entity === 'capacities') {
            $this->syncStandards($record, $request->input('standards'));
        }

        return redirect()->route('masters.index', $entity)
            ->with('flash', ['success' => "{$config['label']} berhasil ditambahkan."]);
    }

    public function update(Request $request, string $entity, int $id)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $data = $request->validate($config['rules']);

        $record = $config['model']::findOrFail($id);
        $record->update($data);

        if ($entity === 'capacities') {
            $this->syncStandards($record, $request->input('standards'));
        }

        return redirect()->route('masters.index', $entity)
            ->with('flash', ['success' => "{$config['label']} berhasil diperbarui."]);
    }

    public function destroy(string $entity, int $id)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $config['model']::findOrFail($id)->delete();

        return redirect()->route('masters.index', $entity)
            ->with('flash', ['success' => "{$config['label']} berhasil dihapus."]);
    }

    private function syncStandards($capacity, $standards): void
    {
        $capacity->standards()->delete();

        $values = collect($standards ?? [])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->values();

        foreach ($values as $i => $value) {
            $capacity->standards()->create([
                'standard_value' => $value,
                'sort_order' => $i,
            ]);
        }
    }

    private function counts(): array
    {
        return [
            'factories' => Factory::count(),
            'departments' => Department::count(),
            'types' => InstrumentType::count(),
            'brands' => Brand::count(),
            'capacities' => Capacity::count(),
            'limits' => AcceptableLimit::count(),
            'specifications' => Specification::count(),
            'instruments' => Instrument::count(),
        ];
    }

    private function options(string $entity): array
    {
        $options = [];
        foreach ($this->entities as $key => $config) {
            foreach ($config['fields'] as $field) {
                if (($field['type'] ?? '') === 'select') {
                    $options[$field['options']] = $this->entities[$field['options']]['model']::orderBy('name')->get();
                }
            }
        }
        if ($entity === 'departments') {
            $options['factories'] = Factory::orderBy('name')->get();
        }

        return $options;
    }
}
