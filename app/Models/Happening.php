<?php

namespace App\Models;

use App\Library\Utility;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Psr\Log\NullLogger;

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

    // FIXME: a happening belongs to 1 resource
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

    public function getUser1()
    {
        if ( ! $this->getAttribute('user_id_01')) {
            return null;
        }
        return $this->getAttribute('user1');
    }

    public function getUser2()
    {
        if ( ! $this->getAttribute('user_id_02')) {
            return null;
        }
        return $this->getAttribute('user2');
    }

    /*****************************************************************
     * SCOPES
     ****************************************************************/

    // Get only happenings that are not in the past
    public function scopeCurrent($query)
    {
        return $query->where('end', '>=', Carbon::now());
    }

    // FIXME: Get only happenings where resources are active and not affected by business hours and closings
    public function scopeActive($query)
    {
        return NULL;
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
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if ($user->getKey() === $this->user1->getKey()) {
            return true;
        }

        return false;
    }

    private function isMyToConfirm(): bool
    {
        return auth()->user()->name === $this->confirmer;
    }

    private function isMyConfirmed(): bool
    {
        return auth()->user()->id === $this->user_id_02;
    }

    private function isConfirmed(): bool
    {
        return $this->is_confirmed;
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
                    $status['user']['confirmation'] = $this->user2->name;
                } elseif ($this->isMyConfirmed()) {
                    $status['type'] = 'user-confirmed';
                    $status['user']['reservation'] = $this->user1->name;
                    $status['user']['confirmation'] = $this->user2->name;
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
}
