<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

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

    public function scopeWhereDateBetween($query, $fieldName, $fromDate, $toDate)
    {
        return $query->whereDate($fieldName, '>=', $fromDate)->whereDate($fieldName, '<=', $toDate);
    }

    // See: https://stackoverflow.com/a/25163557/6948765
    public function getResourceAttribute()
    {
        return $this->resource()->first();
    }

    public function getStatus()
    {
        //var_dump($this->user1);
        $user = [
            'reservation' => ['id' => $this->user1->id, 'name' => $this->user1->name],
            'confirmation' => ['id' => $this->user2->id ?? null, 'name' => $this->user2->name ?? null],
        ];

        $status = [
            'title' => 'info',
            'roles' => [],
            'css' => 'info'
        ];

        // We have a login ...
        if (auth()->check()) {
            // Happening is confirmed ...
            if ($this->is_confirmed)
            {
                // ... and belongs to you
                if (in_array(auth()->user()->id, [$this->user1->id, $this->user2->id], true))
                {
                    $status['title'] = 'user-booking';
                    $status['roles'] = ['UPDATE', 'DELETE'];
                    $status['css'] = 'user-booking';

                    if (auth()->user()->id === $this->user1->id) {
                        $user['reservation']['id'] = auth()->user()->id;
                        $user['reservation']['name'] = auth()->user()->name;
                        $user['confirmation']['id'] = $this->user2->id;
                        $user['confirmation']['name'] = $this->user2->name;
                    } else {
                        $user['reservation']['id'] = $this->user1->id;
                        $user['reservation']['name'] = $this->user1->name;
                        $user['confirmation']['id'] = auth()->user()->id;
                        $user['confirmation']['name'] = auth()->user()->name;
                    }
                }
                // ... and belongs to somebody else
                else
                {
                    $status['title'] = 'info';
                    $status['css'] = 'booking';
                }
            }
            // Happening is unconfirmed ...
            else
            {
                // ... and belongs to you
                if (auth()->user()->id === $this->user1->id) {
                    $status['title'] = 'user-reservation';
                    $status['roles'] = ['UPDATE', 'DELETE'];
                    $status['css'] = 'user-reservation';
                    $user['reservation']['id'] = auth()->user()->id;
                    $user['reservation']['name'] = auth()->user()->name;
                    $user['confirmation']['uid'] = $this->confirmer;
                }
                // ... and you are the confirmer
                elseif (auth()->user()->name === $this->confirmer)
                {
                    $status['title'] = 'user-confirmation';
                    $status['roles'] = ['CONFIRM'];
                    $status['css'] = 'user-confirmation';
                    $user['reservation']['id'] = $this->user1->id;
                    $user['reservation']['name'] = $this->user1->name;
                    $user['confirmation']['id'] = auth()->user()->id;
                    $user['confirmation']['name'] = auth()->user()->name;
                }
                // ... and you are not the confirmer
                elseif (auth()->user()->uid !== $this->confirmer)
                {
                    $status['title'] = 'info';
                    $status['css'] = 'reservation';
                }
                // ... and has nothing to do with you
                else
                {
                    $status['title'] = 'reservation';
                }
            }
        }
        // No login
        else
        {
            // ... and is confirmed
            if ($this->is_confirmed) {
                $status['title'] = 'info';
                $status['css'] = 'booking';
                // ... and is not confirmed
            } else {
                $status['title'] = 'info';
                $status['css'] = 'reservation';
            }
        }
        $status = Arr::add($status, 'user', $user);

        return $status;
    }



}
