<?php

namespace App\Models;

use App\Contracts\ClosingSubject;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Bkwld\Cloner\Cloneable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @implements ClosingSubject<$this>
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BusinessHour> $business_hours
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Closing> $closings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Happening> $happenings
 * @property-read ResourceGroup $resource_group
 */
class Resource extends Model implements ClosingSubject
{
    /** @use HasFactory<\Database\Factories\ResourceFactory> */
    use HasFactory;
    use HasUuids, Cloneable, HasTranslations;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'resources';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'resource_group_id',
        'title',
        'location',
        'location_uri',
        'description',
        'capacity',
        'is_active',
        'order',
        'is_verification_required',
    ];

    protected $with = ['closings'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verification_required' => 'boolean',
    ];

    /**
     * @var list<string>
     */
    protected $cloneable_relations = [
        'institution',
        'business_hours',
        'closings',
    ];

    /**
     * @var list<string>
     */
    protected $translatable = [
        'title',
        'location',
        'description',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    /**
     * @return BelongsTo<ResourceGroup, $this>
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function resource_group(): BelongsTo
    {
        return $this->belongsTo(ResourceGroup::class);
    }

    /**
     * @return HasMany<Happening, $this>
     */
    public function happenings(): HasMany
    {
        return $this->hasMany(Happening::class);
    }

    /**
     * @return HasMany<BusinessHour, $this>
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function business_hours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }

    /**
     * @return MorphMany<Closing, $this>
     */
    public function closings(): MorphMany
    {
        return $this->morphMany(Closing::class, 'closable');
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/
    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    public function isVerificationRequired(): bool
    {
        return $this->is_verification_required;
    }

    public function institutionForClosings(): Institution
    {
        return $this->resource_group->institution;
    }

    /**
     * @return EloquentCollection<int, Happening>
     */
    public function getHappenings(): EloquentCollection
    {
        return $this->happenings;
    }
}
