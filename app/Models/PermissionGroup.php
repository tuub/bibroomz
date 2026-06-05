<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionGroup extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;
    use HasUuids, HasTranslations;

    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * @var list<string>
     */
    protected $translatable = [
        'name',
        'description',
    ];

    /**
     * @return HasMany<Permission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
