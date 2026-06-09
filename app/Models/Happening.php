<?php

namespace App\Models;

use App\Events\HappeningBroadcastEvent;
use App\Library\Utility;
use App\Services\Happenings\HappeningAudienceResolver;
use App\Services\Happenings\HappeningBroadcaster;
use App\Services\Happenings\HappeningStatusCalculator;
use App\Services\Resources\ResourceAvailabilityService;
use App\Traits\HasTranslations;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @property-read \App\Models\Resource $resource
 * @property-read User|null $user1
 * @property-read User|null $user2
 */
class Happening extends Model
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    use HasTranslations, HasUuids, MassPrunable, SoftDeletes;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'happenings';

    public $incrementing = false;

    protected $fillable = [
        'user_id_01',
        'user_id_02',
        'resource_id',
        'is_verified',
        'verifier',
        'start',
        'end',
        'reserved_at',
        'verified_at',
        'label',
    ];

    /**
     * @var list<string>
     */
    protected $translatable = [
        'label',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    /**
     * @return BelongsTo<\App\Models\Resource, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_01', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_02', 'id');
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/

    /**
     * Get only happenings that are within the current week.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     *
     * @throws InvalidArgumentException
     */
    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('start', '>=', Carbon::now()->startOfWeek());
    }

    /**
     * Get only happenings belonging to a given user.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUser(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $query) => $query->where('user_id_01', $user->id)
            ->orWhere('user_id_02', $user->id)
            ->orWhere('verifier', Utility::normalizeLoginName($user->name)));
    }

    /**
     * Get only happenings belonging to a given resource group.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeResourceGroup(Builder $query, ResourceGroup $resourceGroup): Builder
    {
        return $query->whereHas('resource', fn (Builder $query) => $query->where('resource_group_id', $resourceGroup->id));
    }

    /**
     * Get only happenings belonging to an active resource.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('resource', fn (Builder $query) => $query->where('is_active', true));
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    /**
     * @return array{verify: bool, edit: bool, delete: bool}
     */
    public function getPermissions(?User $user): array
    {
        return [
            'verify' => $user instanceof User && $user->can('verify', $this),
            'edit' => $user instanceof User && $user->can('update', $this),
            'delete' => $user instanceof User && $user->can('delete', $this),
        ];
    }

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function isBelongingTo(User $user): bool
    {
        return $user->id === $this->user_id_01 || $user->id === $this->user_id_02 || $user->name === $this->verifier;
    }

    public function isPast(): bool
    {
        return $this->end < Utility::getCarbonNow();
    }

    public function isPresent(): bool
    {
        return $this->start < Utility::getCarbonNow() && $this->end > Utility::getCarbonNow();
    }

    /**
     * @return array{
     *   type: 'booking'|'reservation'|'user-booking'|'user-reservation'|'user-to-verify',
     *   user: array{}|array{reservation: string, verification: string}
     * }
     */
    public function getStatus(): array
    {
        $viewer = auth()->user();

        return app(HappeningStatusCalculator::class)->calculate($this, $viewer instanceof User ? $viewer : null);
    }

    /**
     * @return Collection<int, User>
     */
    public function users(): Collection
    {
        return app(HappeningAudienceResolver::class)->resolve($this);
    }

    /**
     * @param  class-string<HappeningBroadcastEvent>  $broadcastEvent
     */
    public function broadcast(string $broadcastEvent): void
    {
        app(HappeningBroadcaster::class)->broadcast($this, $broadcastEvent);
    }

    public function isConcurrent(CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return ($this->start >= $start && $this->start < $end) || ($this->start < $start && $this->end > $start);
    }

    public function isEditableByUser(User $user): bool
    {
        return $user->can('adminUpdate', $this);
    }

    public function isViewableByUser(User $user): bool
    {
        return $user->can('adminView', $this);
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $cleanupDays = config('roomz.happenings.cleanup_days');

        return static::where('end', '<=', now()->subDays(is_int($cleanupDays) ? $cleanupDays : 0));
    }

    public function withAdjustedStartEndTimes(): ?self
    {
        $availabilityService = app(ResourceAvailabilityService::class);
        $start = CarbonImmutable::parse($this->start);
        $end = CarbonImmutable::parse($this->end);

        [, $start, $end] = $availabilityService->findOpen($this->resource, $start, $end);
        [, $start, $end] = $availabilityService->findClosed($this->resource, $start, $end);

        $this->start = Carbon::instance($start->toDateTime());
        $this->end = Carbon::instance($end->toDateTime());

        return $this;
    }

    public function isResourceOpen(): bool
    {
        $availabilityService = app(ResourceAvailabilityService::class);
        $start = CarbonImmutable::parse($this->start);
        $end = CarbonImmutable::parse($this->end);

        [$is_open] = $availabilityService->findOpen($this->resource, $start, $end);
        [$is_closed] = $availabilityService->findClosed($this->resource, $start, $end);

        return $is_open && ! $is_closed;
    }

    protected $casts = [
        'is_verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start' => 'datetime',
        'end' => 'datetime',
        'reserved_at' => 'datetime',
        'verified_at' => 'datetime',
    ];
}
