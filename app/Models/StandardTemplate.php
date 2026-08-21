<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandardTemplate extends Model
{
    protected $fillable = ['instrument_type_id', 'standard_value', 'sort_order'];

    public function type()
    {
        return $this->belongsTo(InstrumentType::class, 'instrument_type_id');
    }
}
