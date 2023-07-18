<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
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
    protected $fillable = ['user_id_01', 'user_id_02', 'resource_id', 'is_confirmed', 'confirmer', 'start', 'end', 'reserved_at', 'booked_at'];
    protected $dates = ['created_at', 'updated_at', 'start', 'end', 'reserved_at', 'booked_at'];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function user1()
    {
        return $this->belongsTo(User::class, 'user_id_01', 'id');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user_id_02', 'id');
    }

    // public function getUser1()
    // {
    //     if (!$this->getAttribute('user_id_01')) {
    //         return null;
    //     }
    //     return $this->getAttribute('user1');
    // }

    // public function getUser2()
    // {
    //     if (!$this->getAttribute('user_id_02')) {
    //         return null;
    //     }
    //     return $this->getAttribute('user2');
    // }

    /*****************************************************************
     * SCOPES
     ****************************************************************/

    /**
     * Get only happenings that are not in the past.
     *
     * @param Builder $query
     * @return Builder
     * @throws InvalidArgumentException
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('end', '>=', Carbon::now());
    }

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

    public function getPermissions($user)
    {
        return [
            'confirm' => $user ? $user->can('confirm', $this) : false,
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

    private function isMyToConfirm(): bool
    {
        return auth()->user()->name === $this->confirmer;
    }

    private function isMyConfirmed(): bool
    {
        return auth()->user()->id === $this->user_id_02;
    }

    public function isConfirmed(): bool
    {
        return $this->is_confirmed;
    }

    public function isBelongingTo(User $user): bool
    {
        return $user->id === $this->user_id_01 || $user->id === $this->user_id_02 || $user->name === $this->confirmer;
    }

    public function getStatus(): array
    {
        $status = [
            'user' => [],
        ];

        if (auth()->check()) {
            if ($this->isConfirmed()) {
                if ($this->isMine()) {
                    $status['type'] = 'user-booking';
                    $status['user']['reservation'] = $this->user1->name;
                    $status['user']['confirmation'] = $this->user2?->name;
                } elseif ($this->isMyConfirmed()) {
                    $status['type'] = 'user-confirmed';
                    $status['user']['reservation'] = $this->user1->name;
                    $status['user']['confirmation'] = $this->user2?->name;
                } else {
                    $status['type'] = 'booking';
                }
            } else {
                if ($this->isMine()) {
                    $status['type'] = 'user-reservation';
                    $status['user']['reservation'] = $this->user1->name;
                    $status['user']['confirmation'] = $this->confirmer;
                } elseif ($this->isMyToConfirm()) {
                    $status['type'] = 'user-to-confirm';
                    $status['user']['reservation'] = $this->user1->name;
                    $status['user']['confirmation'] = $this->confirmer;
                } else {
                    $status['type'] = 'reservation';
                }
            }
        } else {
            if ($this->isConfirmed()) {
                $status['type'] = 'booking';
            } else {
                $status['type'] = 'reservation';
            }
        }

        return $status;
    }

    public function users(bool $is_confirmer_included = true)
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

        if ($is_confirmer_included) {
            $confirmer = User::where('name', $this->confirmer)->first();

            if ($confirmer) {
                $users->push($confirmer);
            }
        }

        return $users;
    }

    public function usersWithConfirmer()
    {
        return $this->users(is_confirmer_included: true);
    }

    public function usersWithoutConfirmer()
    {
        return $this->users(is_confirmer_included: false);
    }
}
