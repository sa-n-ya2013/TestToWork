<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    protected $visible = ['tag'];

    public $timestamps = false;

    public function good()
    {
        return $this->belongsTo(Good::class);
    }
}
