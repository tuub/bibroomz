<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;
    use HasUuids, HasTranslations;

    public $incrementing = false;

    protected $fillable = [
        'key',
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
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * @return BelongsTo<PermissionGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class);
    }
}
