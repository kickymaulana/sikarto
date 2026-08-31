<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandardTemplate extends Model
{
    protected $fillable = ['capacity_id', 'standard_value', 'sort_order'];

    public function capacity()
    {
        return $this->belongsTo(Capacity::class);
    }
}
