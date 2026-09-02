<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids as HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read UserGroupUser|null $pivot
 */
#[Fillable([
    'title',
    'institution_id',
])]
#[Table(name: 'user_groups')]
#[WithoutIncrementing]
#[WithoutTimestamps]
class UserGroup extends Model
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    use HasTranslations, HasUuids;

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
