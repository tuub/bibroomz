<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionGroup extends Model
{
    use HasFactory, HasUuids, HasTranslations;

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
