<?php

namespace App\Models;

use App\Events\HappeningsChanged;
use App\Library\Utility;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class Happening extends Model
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    use HasFactory, HasUUID;

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
        'booked_at',
    ];
    protected $dates = [
        'created_at',
        'updated_at',
        'start',
        'end',
        'reserved_at',
        'booked_at',
    ];
    protected $casts = [
        'is_verified' => 'boolean',
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

    // /**
    //  * Get only happenings that are not in the past.
    //  *
    //  * @param Builder $query
    //  * @return Builder
    //  * @throws InvalidArgumentException
    //  */
    // public function scopeCurrent(Builder $query): Builder
    // {
    //     return $query->where('end', '>=', Carbon::now());
    // }

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
     * @param User|null $user
     * @return array
     * @throws BindingResolutionException
     */
    public function getPermissions(User|null $user): array
    {
        return [
            'verify' => $user ? $user->can('verify', $this) : false,
            'edit' => $user ? $user->can('update', $this) : false,
            'delete' => $user ? $user->can('delete', $this) : false,
        ];
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    private function isMine(): bool
    {
        return auth()->user()->id === $this->user_id_01;
    }

    private function isMyToVerify(): bool
    {
        return auth()->user()->name === $this->verifier;
    }

    private function isMyVerified(): bool
    {
        return auth()->user()->id === $this->user_id_02;
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
                } elseif ($this->isMyVerified()) {
                    $status['type'] = 'user-verified';
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

    public function users(bool $is_verifier_included = true)
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

        if ($is_verifier_included) {
            $verifier = User::where('name', $this->verifier)->first();

            if ($verifier) {
                $users->push($verifier);
            }
        }

        return $users;
    }

    public function broadcast(String $broadcastEvent): void
    {
        foreach ($this->users() as $user) {
            $broadcastEvent::dispatch($this, $user);
        }

        HappeningsChanged::dispatch();
    }
}
