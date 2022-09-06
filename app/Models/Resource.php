<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    public $timestamps = false;
    protected $fillable = ['institution_id', 'title', 'location', 'description', 'capacity', 'is_active'];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    public function institution(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
