<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    public $timestamps = false;

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    public function resources(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
