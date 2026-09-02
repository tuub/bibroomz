<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids as HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'key',
    'name',
    'description',
])]
#[WithoutIncrementing]
class Permission extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    use HasTranslations, HasUuids;

    /**
     * @var list<string>
     */
    protected $translatable = [
        'name',
        'description',
    ];

    /**
     * @return BelongsToMany<Role, $this>
     *
     * @codeCoverageIgnore
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * @return BelongsTo<PermissionGroup, $this>
     *
     * @codeCoverageIgnore
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class);
    }
}
