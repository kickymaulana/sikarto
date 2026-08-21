<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalibrationTestItem extends Model
{
    protected $fillable = [
        'calibration_test_id', 'standard_value', 'reading_value', 'correction', 'is_within_limit',
    ];

    protected $casts = [
        'is_within_limit' => 'boolean',
    ];

    public function test()
    {
        return $this->belongsTo(CalibrationTest::class, 'calibration_test_id');
    }
}
