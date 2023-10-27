<?php

namespace App\Models;

use App\Library\Traits\UUIDIsPrimaryKey;
use App\Traits\HasTranslations;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ResourceGroup extends Model
{
    use HasFactory, HasUUID, UUIDIsPrimaryKey, HasTranslations;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'resource_groups';
    protected $uuidFieldName = 'id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'institution_id',
        'title',
        'slug',
        'term_singular',
        'term_plural',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $translatable = [
        'title',
        'term_singular',
        'term_plural',
        'description',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function settings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'settingable');
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true)->whereHas('institution', fn ($query) => $query->active());
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    public function isViewableByUser(User $user): bool
    {
        return $user->can('view', $this);
    }
}
