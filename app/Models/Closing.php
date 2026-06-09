<?php

namespace App\Models;

use App\Contracts\ClosingSubject;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @property-read Institution|\App\Models\Resource|null $closable
 */
class Closing extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    use HasTranslations, HasUuids, Prunable, SoftDeletes;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'closings';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'closable_id',
        'closable_type',
        'start',
        'end',
        'description',
    ];

    /**
     * @var list<string>
     */
    protected $translatable = [
        'description',
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
     * @return MorphTo<Model, $this>
     */
    public function closable(): MorphTo
    {
        return $this->morphTo();
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/

    public static function getClosableModel(string $closableType): Institution|Resource
    {
        return match ($closableType) {
            'institution', Institution::class => new Institution,
            'resource', Resource::class => new Resource,
            default => throw new InvalidArgumentException("Unsupported closable type [{$closableType}]."),
        };
    }

    /**
     * @return Institution|\App\Models\Resource
     */
    public function getClosingSubject(): ClosingSubject
    {
        $closable = $this->closable;

        if (! $closable instanceof ClosingSubject) {
            throw new InvalidArgumentException('Unsupported closing subject.');
        }

        return $closable;
    }

    public function getInstitution(): Institution
    {
        return $this->getClosingSubject()->institutionForClosings();
    }

    /**
     * @return Collection<int, Happening>
     */
    public function getHappeningsAffected(): Collection
    {
        return $this->getClosingSubject()->getHappenings()
            ->where('end', '>', $this->start)
            ->where('start', '<', $this->end);
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsersAffected(): Collection
    {
        return $this->getHappeningsAffected()
            ->flatMap(fn (Happening $happening): Collection => $happening->users())
            ->unique('id')
            ->values();
    }

    /**
     * @return Collection<int, Happening>
     */
    public function getUserHappeningsAffected(User $user): Collection
    {
        return $this->getHappeningsAffected()->filter(fn (Happening $happening): bool => $happening->isBelongingTo($user));
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::onlyTrashed();
    }

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];
}
