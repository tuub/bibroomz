<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read UserGroupUser|null $pivot
 */
class UserGroup extends Model
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;
    use HasUuids, HasTranslations;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'user_groups';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'institution_id',
    ];

    /**
     * @var list<string>
     */
    protected $translatable = [
        'title',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    /**
     * @return BelongsTo<Institution, $this>
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * @return BelongsToMany<User, $this, UserGroupUser>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_group_user')
            ->withPivot('valid_from', 'valid_until')
            ->using(UserGroupUser::class);
    }

    /**
     * @return BelongsToMany<ResourceGroup, $this>
     */
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
