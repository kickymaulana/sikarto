<?php

namespace Tests\Feature;

use App\Models\AcceptableLimit;
use App\Models\Brand;
use App\Models\Capacity;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Instrument;
use App\Models\InstrumentType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Tests\TestCase;

class MatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeUserWithRole(string $role): User
    {
        $user = User::create([
            'nik' => 'D'.str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT),
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'is_approved' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeAcceptableLimit(): AcceptableLimit
    {
        return AcceptableLimit::create([
            'name' => '±5 g',
            'min_correction' => -5,
            'max_correction' => 5,
            'unit' => 'g',
        ]);
    }

    private function makeFactoryAndDept(): array
    {
        $factory = Factory::create(['name' => 'Plant 1']);
        $dept = Department::create(['name' => 'QA', 'factory_id' => $factory->id]);

        return [$factory, $dept];
    }

    private function makeInstrument(array $overrides): Instrument
    {
        [$factory, $dept] = $this->makeFactoryAndDept();
        $limit = $this->makeAcceptableLimit();
        $brand = Brand::create(['name' => 'Merk '.uniqid()]);
        $capacity = Capacity::create(['name' => '100g', 'value' => 100, 'unit' => 'g']);

        return Instrument::create(array_merge([
            'factory_id' => $factory->id,
            'department_id' => $dept->id,
            'acceptable_limit_id' => $limit->id,
            'brand_id' => $brand->id,
            'capacity_id' => $capacity->id,
        ], $overrides));
    }

    public function test_matrix_page_renders_with_default_timbangan_digital_filter(): void
    {
        $user = $this->makeUserWithRole('inspector');
        $timbangan = InstrumentType::create(['name' => 'Timbangan Digital']);
        $caliper = InstrumentType::create(['name' => 'Caliper']);

        $this->makeInstrument(['code' => 'TD-001', 'name' => 'Timbangan A', 'instrument_type_id' => $timbangan->id]);
        $this->makeInstrument(['code' => 'CL-001', 'name' => 'Caliper A', 'instrument_type_id' => $caliper->id]);

        $response = $this->actingAs($user)->get('/masters/matrix');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Masters/Matrix')
            ->where('typeId', $timbangan->id)
            ->has('rows', 1)
            ->where('rows.0.code', 'TD-001')
        );
    }

    public function test_matrix_filters_by_type_id_query(): void
    {
        $user = $this->makeUserWithRole('inspector');
        $timbangan = InstrumentType::create(['name' => 'Timbangan Digital']);
        $caliper = InstrumentType::create(['name' => 'Caliper']);

        $this->makeInstrument(['code' => 'TD-001', 'name' => 'Timbangan A', 'instrument_type_id' => $timbangan->id]);
        $this->makeInstrument(['code' => 'CL-001', 'name' => 'Caliper A', 'instrument_type_id' => $caliper->id]);

        $response = $this->actingAs($user)->get('/masters/matrix?type_id='.$caliper->id);
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('typeId', $caliper->id)
            ->has('rows', 1)
            ->where('rows.0.code', 'CL-001')
        );
    }

    public function test_matrix_sorts_naturally_by_code(): void
    {
        $user = $this->makeUserWithRole('inspector');
        $timbangan = InstrumentType::create(['name' => 'Timbangan Digital']);

        foreach (['W.FL.20', 'W.FL.3', 'W.FL.1', 'W.FL.10', 'W.FL.2'] as $code) {
            $this->makeInstrument([
                'code' => $code,
                'name' => 'Alat '.$code,
                'instrument_type_id' => $timbangan->id,
            ]);
        }

        $response = $this->actingAs($user)->get('/masters/matrix?type_id='.$timbangan->id);
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('rows.0.code', 'W.FL.1')
            ->where('rows.1.code', 'W.FL.2')
            ->where('rows.2.code', 'W.FL.3')
            ->where('rows.3.code', 'W.FL.10')
            ->where('rows.4.code', 'W.FL.20')
        );
    }

    public function test_matrix_export_returns_excel(): void
    {
        $user = $this->makeUserWithRole('admin');
        $timbangan = InstrumentType::create(['name' => 'Timbangan Digital']);
        $this->makeInstrument(['code' => 'W.FL.1', 'name' => 'Alat A', 'instrument_type_id' => $timbangan->id]);

        $response = $this->actingAs($user)->get('/masters/matrix/export?type_id='.$timbangan->id.'&year=2026');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $filename = $response->headers->get('content-disposition');
        $this->assertStringContainsString('Matriks_Uji_Timbangan-Digital-2026.xlsx', $filename);

        $content = $response->streamedContent();
        $tmp = tempnam(sys_get_temp_dir(), 'matrix').'.xlsx';
        file_put_contents($tmp, $content);
        $reader = new Xlsx;
        $sheet = $reader->load($tmp)->getSheet(0);
        $this->assertSame('Kode Alat', $sheet->getCell('A1')->getValue());
        $this->assertSame('W.FL.1', $sheet->getCell('A2')->getValue());
        $this->assertSame('Jan Uji', $sheet->getCell('E1')->getValue());
        $this->assertSame('—', $sheet->getCell('E2')->getValue());
        unlink($tmp);
    }

    public function test_matrix_export_requires_authentication(): void
    {
        $response = $this->get('/masters/matrix/export');
        $response->assertRedirect();
    }
}
