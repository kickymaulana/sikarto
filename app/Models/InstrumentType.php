<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstrumentType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function standards()
    {
        return $this->hasMany(StandardTemplate::class)->orderBy('sort_order');
    }
}
