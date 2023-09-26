<?php

namespace App\Models;

use App\Library\Traits\UUIDIsPrimaryKey;
use App\Traits\HasTranslations;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionGroup extends Model
{
    use HasFactory, HasUUID, UUIDIsPrimaryKey, HasTranslations;

    protected string $uuidFieldName = 'id';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
    ];

    protected $translatable = [
        'name',
        'description',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
