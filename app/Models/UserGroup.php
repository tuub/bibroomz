<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UserGroup extends Model
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    use HasFactory, hasUuids, HasTranslations;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'user_groups';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'institution_id',
    ];

    protected $translatable = [
        'title',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_group_user')
            ->withPivot('valid_from', 'valid_until')
            ->using(UserGroupUser::class);
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function resource_groups(): BelongsToMany
    {
        return $this->belongsToMany(ResourceGroup::class, 'resource_group_user_group');
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    public function isViewableByUser(User $user): bool
    {
        return $user->can('view', $this);
    }
}
