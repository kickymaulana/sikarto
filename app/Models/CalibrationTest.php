<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalibrationTest extends Model
{
    protected $fillable = [
        'instrument_id', 'test_date', 'next_test_date', 'tester_id', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'test_date' => 'date',
            'next_test_date' => 'date',
        ];
    }

    public function instrument()
    {
        return $this->belongsTo(Instrument::class);
    }

    public function tester()
    {
        return $this->belongsTo(User::class, 'tester_id');
    }

    public function items()
    {
        return $this->hasMany(CalibrationTestItem::class);
    }
}
