<?php

namespace Database\Seeders;

use App\Models\AcceptableLimit;
use App\Models\Brand;
use App\Models\CalibrationTest;
use App\Models\CalibrationTestItem;
use App\Models\Capacity;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Instrument;
use App\Models\InstrumentType;
use App\Models\StandardTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $factories = collect([
            ['name' => 'PT Karya Inti Mandiri'],
            ['name' => 'Pabrik Dalu 1'],
            ['name' => 'Pabrik Dalu 2'],
        ])->map(fn ($f) => Factory::create($f));

        foreach ($factories as $factory) {
            foreach (['Filling', 'Packing', 'Maintenance'] as $name) {
                Department::create([
                    'factory_id' => $factory->id,
                    'name' => $name,
                ]);
            }
        }

        $typeStandards = [
            'Timbangan' => [500, 700, 800, 1000],
            'Caliper' => [10, 20, 30, 50],
            'Rol Siku' => [90, 100, 150],
            'Thermo Hunter' => [30, 60, 90],
            'Stopwatch' => [60, 300, 600],
            'Laser' => [1, 5, 10],
        ];

        foreach ($typeStandards as $name => $standards) {
            $type = InstrumentType::create(['name' => $name]);
            foreach ($standards as $i => $standard) {
                StandardTemplate::create([
                    'instrument_type_id' => $type->id,
                    'standard_value' => $standard,
                    'sort_order' => $i,
                ]);
            }
        }

        foreach (['DICSON', 'Mitutoyo', 'Krisbow'] as $name) {
            Brand::create(['name' => $name]);
        }

        foreach ([
            ['name' => '3 KG', 'value' => 3, 'unit' => 'kg'],
            ['name' => '500 KG', 'value' => 500, 'unit' => 'kg'],
            ['name' => '150 MM', 'value' => 150, 'unit' => 'mm'],
            ['name' => '2 KG', 'value' => 2, 'unit' => 'kg'],
        ] as $c) {
            Capacity::create($c);
        }

        foreach ([
            ['name' => '±5 gr', 'min_correction' => -5, 'max_correction' => 5, 'unit' => 'gr'],
            ['name' => '±200 gr', 'min_correction' => -200, 'max_correction' => 200, 'unit' => 'gr'],
            ['name' => '±2 kg', 'min_correction' => -2, 'max_correction' => 2, 'unit' => 'kg'],
            ['name' => '±0.5 mm', 'min_correction' => -0.5, 'max_correction' => 0.5, 'unit' => 'mm'],
        ] as $l) {
            AcceptableLimit::create($l);
        }

        $adminMaster = User::create([
            'name' => 'Admin Master',
            'email' => 'admin.master@sikarto.test',
            'password' => 'password',
            'factory_id' => $factories->first()->id,
            'nik' => 'KD220004',
            'is_approved' => true,
        ]);
        $adminMaster->assignRole('admin_master');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@sikarto.test',
            'password' => 'password',
            'factory_id' => $factories->first()->id,
            'nik' => 'D260065',
            'is_approved' => true,
        ]);
        $admin->assignRole('admin');

        $inspector = User::create([
            'name' => 'Inspector QC',
            'email' => 'inspector@sikarto.test',
            'password' => 'password',
            'factory_id' => $factories->first()->id,
            'nik' => 'K190327',
            'is_approved' => true,
        ]);
        $inspector->assignRole('inspector');

        $firstDept = Department::first();
        $timbangan = InstrumentType::where('name', 'Timbangan')->first();
        $caliper = InstrumentType::where('name', 'Caliper')->first();

        Instrument::create([
            'code' => 'W.FL.5',
            'factory_id' => $factories->first()->id,
            'department_id' => $firstDept->id,
            'instrument_type_id' => $timbangan->id,
            'brand_id' => Brand::first()->id,
            'capacity_id' => Capacity::first()->id,
            'acceptable_limit_id' => AcceptableLimit::first()->id,
            'notes' => 'Timbangan contoh',
        ]);

        Instrument::create([
            'code' => 'W.PC.12',
            'factory_id' => $factories->first()->id,
            'department_id' => Department::where('name', 'Packing')->first()->id,
            'instrument_type_id' => $caliper->id,
            'brand_id' => Brand::where('name', 'Mitutoyo')->first()->id,
            'capacity_id' => Capacity::where('value', 150)->first()->id,
            'acceptable_limit_id' => AcceptableLimit::where('name', '±0.5 mm')->first()->id,
        ]);

        // Sample calibration test (PASS) for W.FL.5, jatuh tempo bulan ini
        $instrument = Instrument::where('code', 'W.FL.5')->first();
        $test = CalibrationTest::create([
            'instrument_id' => $instrument->id,
            'test_date' => now()->subMonth()->toDateString(),
            'next_test_date' => now()->toDateString(),
            'tester_id' => $inspector->id,
            'status' => 'PASS',
        ]);
        foreach ($instrument->type->standards as $s) {
            CalibrationTestItem::create([
                'calibration_test_id' => $test->id,
                'standard_value' => $s->standard_value,
                'reading_value' => $s->standard_value + 1,
                'correction' => 1,
                'is_within_limit' => true,
            ]);
        }
    }
}
