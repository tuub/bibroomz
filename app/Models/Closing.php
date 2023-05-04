<?php

namespace App\Models;

use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Closing extends Model
{
    use HasFactory, HasUUID;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'closings';
    protected $uuidFieldName = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['closable_id', 'closable_type', 'start', 'end', 'description'];
    protected $dates = ['start', 'end'];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function closable(): MorphTo
    {
        return $this->morphTo();
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/

    public static function getClosableModel(string $closableType)
    {
        $fullModelName = __NAMESPACE__ . '\\' . ucfirst($closableType);
        return new $fullModelName();
    }
}
