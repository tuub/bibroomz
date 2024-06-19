<?php

namespace App\Models;

use App\Events\HappeningsChangedEvent;
use App\Library\Utility;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use App\Library\Traits\UUIDIsPrimaryKey;
use App\Traits\HasTranslations;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class Happening extends Model
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    use HasFactory, HasUUID, UUIDIsPrimaryKey, SoftDeletes, MassPrunable, HasTranslations;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'happenings';
    protected $uuidFieldName = 'id';
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

    protected $casts = [
        'is_verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start' => 'datetime',
        'end' => 'datetime',
        'reserved_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $translatable = [
        'label',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function user1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_01', 'id');
    }

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
     * @param Builder $query
     * @return Builder
     * @throws InvalidArgumentException
     */
    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('start', '>=', Carbon::now()->startOfWeek());
    }

    /**
     * Get only happenings belonging to a given user.
     *
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public function scopeUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            return $query->where('user_id_01', $user->id)
                ->orWhere('user_id_02', $user->id)
                ->orWhere('verifier', Utility::normalizeLoginName($user->name));
        });
    }

    /**
     * Get only happenings belonging to a given resource group.
     *
     * @param Builder $query
     * @param ResourceGroup $resourceGroup
     * @return Builder
     */
    public function scopeResourceGroup(Builder $query, ResourceGroup $resourceGroup): Builder
    {
        return $query->whereHas('resource', function (Builder $query) use ($resourceGroup) {
            return $query->where('resource_group_id', $resourceGroup->id);
        });
    }

    /**
     * Get only happenings belonging to an active resource.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('resource', function (Builder $query) {
            return $query->where('is_active', true);
        });
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    protected static function booted(): void
    {
        static::softDeleted(function (Happening $happening) {
            $happening->update([
                'verifier' => null,
            ]);
        });
    }

    public function getPermissions(?User $user): array
    {
        return [
            'verify' => $user ? $user->can('verify', $this) : false,
            'edit' => $user ? $user->can('update', $this) : false,
            'delete' => $user ? $user->can('delete', $this) : false,
        ];
    }

    private function isMine(): bool
    {
        return auth()->user()->id === $this->user_id_01 || auth()->user()->id === $this->user_id_02;
    }

    private function isMyToVerify(): bool
    {
        return auth()->user()->name === $this->verifier;
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

    public function getStatus(): array
    {
        $status = [
            'user' => [],
        ];

        if (auth()->check()) {
            if ($this->isVerified()) {
                if ($this->isMine()) {
                    $status['type'] = 'user-booking';
                    $status['user']['reservation'] = $this->user1->name;
                    $status['user']['verification'] = $this->user2?->name;
                } else {
                    $status['type'] = 'booking';
                }
            } else {
                if ($this->isMine()) {
                    $status['type'] = 'user-reservation';
                    $status['user']['reservation'] = $this->user1->name;
                    $status['user']['verification'] = $this->verifier;
                } elseif ($this->isMyToVerify()) {
                    $status['type'] = 'user-to-verify';
                    $status['user']['reservation'] = $this->user1->name;
                    $status['user']['verification'] = $this->verifier;
                } else {
                    $status['type'] = 'reservation';
                }
            }
        } else {
            if ($this->isVerified()) {
                $status['type'] = 'booking';
            } else {
                $status['type'] = 'reservation';
            }
        }

        return $status;
    }

    public function users()
    {
        $users = collect();

        $user1 = $this->user1;
        $user2 = $this->user2;

        if ($user1) {
            $users->push($user1);
        }

        if ($user2) {
            $users->push($user2);
        }

        $verifier = User::where('name', $this->verifier)->first();

        if ($verifier) {
            $users->push($verifier);
        }

        return $users;
    }

    public function broadcast(string $broadcastEvent): void
    {
        foreach ($this->users() as $user) {
            $broadcastEvent::dispatch($this, $user);
        }

        HappeningsChangedEvent::dispatch();
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

    public function prunable(): Builder
    {
        return static::where('end', '<=', now()->subDays(config('roomz.happenings.cleanup_days')));
    }

    public function withAdjustedStartEndTimes(): ?self
    {
        $start = CarbonImmutable::parse($this->start);
        $end = CarbonImmutable::parse($this->end);

        [, $start, $end] = $this->resource->findOpen($start, $end);
        [, $start, $end] = $this->resource->findClosed($start, $end);

        $this->start = $start;
        $this->end = $end;

        return $this;
    }

    public function isResourceOpen(): bool
    {
        $start = CarbonImmutable::parse($this->start);
        $end = CarbonImmutable::parse($this->end);

        [$is_open] = $this->resource->findOpen($start, $end);
        [$is_closed] = $this->resource->findClosed($start, $end);

        return $is_open && !$is_closed;
    }
}
