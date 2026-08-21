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
    ];

    public function menu()
    {
        return Inertia::render('Masters/Menu', [
            'counts' => [
                'factories' => Factory::count(),
                'departments' => Department::count(),
                'types' => InstrumentType::count(),
                'brands' => Brand::count(),
                'capacities' => Capacity::count(),
                'limits' => AcceptableLimit::count(),
                'instruments' => Instrument::count(),
            ],
        ]);
    }

    public function index(string $entity)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $query = $config['model']::query();

        if ($entity === 'departments') {
            $query->with('factory');
        }

        $items = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Masters/Index', [
            'entity' => $entity,
            'config' => $config,
            'items' => $items,
            'options' => $this->options($entity),
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
        ]);
    }

    public function edit(string $entity, int $id)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $item = $config['model']::findOrFail($id);

        return Inertia::render('Masters/Form', [
            'entity' => $entity,
            'config' => $config,
            'item' => $item,
            'options' => $this->options($entity),
        ]);
    }

    public function store(Request $request, string $entity)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $data = $request->validate($config['rules']);

        $config['model']::create($data);

        return redirect()->route('masters.index', $entity)
            ->with('flash', ['success' => "{$config['label']} berhasil ditambahkan."]);
    }

    public function update(Request $request, string $entity, int $id)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $data = $request->validate($config['rules']);

        $config['model']::findOrFail($id)->update($data);

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
