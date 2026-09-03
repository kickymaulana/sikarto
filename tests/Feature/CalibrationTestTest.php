<?php

namespace Tests\Feature;

use App\Models\AcceptableLimit;
use App\Models\Brand;
use App\Models\CalibrationTest;
use App\Models\Capacity;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Instrument;
use App\Models\InstrumentType;
use App\Models\StandardTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalibrationTestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeInstrument(): Instrument
    {
        $factory = Factory::create(['name' => 'KIM']);
        $dept = Department::create(['factory_id' => $factory->id, 'name' => 'Filling']);
        $type = InstrumentType::create(['name' => 'Timbangan']);
        StandardTemplate::create(['instrument_type_id' => $type->id, 'standard_value' => 500, 'sort_order' => 0]);
        StandardTemplate::create(['instrument_type_id' => $type->id, 'standard_value' => 700, 'sort_order' => 1]);
        $brand = Brand::create(['name' => 'DICSON']);
        $capacity = Capacity::create(['name' => '3 KG', 'value' => 3, 'unit' => 'kg']);
        $limit = AcceptableLimit::create(['name' => '±5 gr', 'min_correction' => -5, 'max_correction' => 5, 'unit' => 'gr']);

        return Instrument::create([
            'code' => 'W.FL.5',
            'factory_id' => $factory->id,
            'department_id' => $dept->id,
            'instrument_type_id' => $type->id,
            'brand_id' => $brand->id,
            'capacity_id' => $capacity->id,
            'acceptable_limit_id' => $limit->id,
        ]);
    }

    private function inspector(): User
    {
        $user = User::create(['name' => 'Inspector', 'email' => 'inspector@test', 'password' => 'password']);
        $user->assignRole('inspector');

        return $user;
    }

    public function test_pass_when_all_corrections_within_limit(): void
    {
        $instrument = $this->makeInstrument();

        $response = $this->actingAs($this->inspector())->post('/tests', [
            'instrument_id' => $instrument->id,
            'test_date' => '2026-08-01',
            'items' => [
                ['standard_value' => 500, 'reading_value' => 502],
                ['standard_value' => 700, 'reading_value' => 704],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $test = CalibrationTest::first();
        $this->assertNotNull($test);
        $this->assertEquals('OK', $test->status);
        $this->assertEquals('2026-09-01', $test->next_test_date->toDateString());
        $this->assertEquals(2, $test->items()->count());
        $this->assertTrue($test->items()->first()->is_within_limit);
        $this->assertNotNull($test->avg_correction);
    }

    public function test_fail_when_avg_exceeds_limit(): void
    {
        $instrument = $this->makeInstrument();

        $response = $this->actingAs($this->inspector())->post('/tests', [
            'instrument_id' => $instrument->id,
            'test_date' => '2026-08-10',
            'items' => [
                ['standard_value' => 500, 'reading_value' => 502],
                ['standard_value' => 700, 'reading_value' => 720],
            ],
        ]);

        $test = CalibrationTest::first();
        $this->assertNotNull($test);
        $this->assertEquals('NG', $test->status);
        $this->assertEquals('2026-09-10', $test->next_test_date->toDateString());
        $this->assertTrue($test->items()->get()[0]->is_within_limit);
        $this->assertFalse($test->items()->get()[1]->is_within_limit);
        $this->assertEquals(11.0, $test->avg_correction);
    }

    public function test_pass_even_with_one_point_outside_when_avg_within(): void
    {
        $instrument = $this->makeInstrument();

        $response = $this->actingAs($this->inspector())->post('/tests', [
            'instrument_id' => $instrument->id,
            'test_date' => '2026-08-15',
            'items' => [
                ['standard_value' => 500, 'reading_value' => 505],
                ['standard_value' => 700, 'reading_value' => 702],
                ['standard_value' => 800, 'reading_value' => 799],
                ['standard_value' => 1000, 'reading_value' => 998],
                ['standard_value' => 1200, 'reading_value' => 1196],
                ['standard_value' => 1300, 'reading_value' => 1295],
                ['standard_value' => 1500, 'reading_value' => 1494],
            ],
        ]);

        $test = CalibrationTest::first();
        $this->assertNotNull($test);
        $this->assertEquals('OK', $test->status);
        // titik 1494 → -6 → NOK per titik, tapi rata-rata ~ -1.57 → OK
        $this->assertFalse($test->items()->get()[6]->is_within_limit);
        $this->assertEquals(-1.5714, $test->avg_correction);
    }

    public function test_manual_status_allows_empty_items(): void
    {
        $instrument = $this->makeInstrument();

        $response = $this->actingAs($this->inspector())->post('/tests', [
            'instrument_id' => $instrument->id,
            'test_date' => '2026-08-20',
            'status' => 'SPARE',
        ]);

        $test = CalibrationTest::first();
        $this->assertNotNull($test);
        $this->assertEquals('SPARE', $test->status);
        $this->assertNull($test->avg_correction);
        $this->assertEquals(0, $test->items()->count());
    }

    public function test_permission_middleware_blocks_inspector_from_report_export(): void
    {
        $this->actingAs($this->inspector())
            ->get('/reports')
            ->assertForbidden();
    }
}
