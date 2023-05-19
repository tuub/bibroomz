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

    public function scopeActive($query)
    {
        return $query->where('end', '>=', Carbon::now());
    }

    // FIXME: needed?
    public function scopeWhereDateBetween($query, $fieldName, $fromDate, $toDate)
    {
        return $query->whereDate($fieldName, '>=', $fromDate)->whereDate($fieldName, '<=', $toDate);
    }

    // See: https://stackoverflow.com/a/25163557/6948765
    // FIXME: needed?
    public function getResourceAttribute()
    {
        return $this->resource()->first();
    }

    public function getStatus()
    {
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

    public static function getAvailableStartTimeSlots($resource_id, $start, $end)
    {
        $response = [];

        $room = Resource::find($resource_id);
        $start = Utility::carbonize($start);
        $end = Utility::carbonize($end);

        $time_slot_interval = Utility::getTimeValuesFromEnvTimeString(env('RESERVATION_TIMESLOT_LENGTH'));
        $alpha = self::getMinTimeSlot($room, $start, $end);
        $omega = self::getMaxTimeSlot($room, $start, $end)->subMinutes($time_slot_interval['minute']);

        $time_slots = CarbonPeriod::create($alpha, $time_slot_interval['minute'] . ' minutes', $omega);

        foreach ($time_slots as $time_slot) {
            $response[$time_slot->format(env('TIME_FORMAT'))] = $time_slot->toIso8601String();
        }

        return $response;
    }

    /**
     * @param $room_id
     * @param $start
     * @param $end
     * @return array
     */
    public static function getAvailableEndTimeSlots($resource_id, $start, $end)
    {
        $response = [];

        $room = Resource::find($resource_id);
        $start = Utility::carbonize($start);
        $end = Utility::carbonize($end);

        $time_slot_interval = Utility::getTimeValuesFromEnvTimeString(env('RESERVATION_TIMESLOT_LENGTH'));
        $alpha = self::getMinTimeSlot($room, $start, $end);
        $omega = self::getMaxTimeSlot($room, $start, $end);

        $alpha->addMinutes($start->diffInMinutes($alpha) + $time_slot_interval['minute']);

        if (auth()->check() && auth()->user()->is_admin === false) {
            $alpha_with_block_hour_quota = $start->clone()->addHours(env('RESERVATION_BLOCK_HOUR_QUOTA'));
            if ($omega->gt($alpha_with_block_hour_quota)) {
                $omega = $alpha_with_block_hour_quota;
            }
        }

        $time_slots = CarbonPeriod::create($alpha, $time_slot_interval['minute'] . ' minutes', $omega);

        foreach ($time_slots as $time_slot) {
            $response[$time_slot->format(env('TIME_FORMAT'))] = $time_slot->toIso8601String();
        }

        return $response;
    }

    /**
     * @param $room
     * @param $start
     * @param $end
     * @return mixed
     */
    public static function getMinTimeSlot($room, $start, $end)
    {
        $this_day_start = $start->clone()->setTime(0,0,0);
        $this_day_end = $this_day_start->clone()->addDay()->subSeconds(1);

        // Are there any reservations for today?
        $this_day_room_reservations = self::getRoomsDayReservations($room, $this_day_start, $this_day_end);

        // If so, give me one with start date the given end date ...
        $previous_reservation = $this_day_room_reservations->filter(function ($item) use($start) {
            return $item->end->lte($start);
        })->first();

        if ($previous_reservation) {
            return $previous_reservation->end;
        }

        // If not, give me the first possible timeslot ...
        // FIXME
        $regular_start = Utility::getTimeValuesFromEnvTimeString(config('reservations.business_hours.' .
            $start->dayOfWeek . '.start'));

        return $this_day_start->addHours($regular_start['hour'])->addMinutes($regular_start['minute']);
    }

    /**
     * @param $room
     * @param $start
     * @param $end
     * @return mixed
     */
    public static function getMaxTimeSlot($room, $start, $end)
    {
        $this_day_start = $start->clone()->setTime(0, 0, 0);
        $this_day_end = $this_day_start->clone()->addDay()->subSeconds(1);

        // Are there any reservations for today?
        $this_day_room_reservations = self::getRoomsDayReservations($room, $this_day_start, $this_day_end);

        // If so, give me one with start date the given end date ...
        $next_reservation = $this_day_room_reservations->sortBy('start')->filter(function ($item) use ($end) {
            return $item->start->gte($end);
        })->first();

        if ($next_reservation) {
            return $next_reservation->start;
        }

        // If not, give me the last possible timeslot ...

        // Until the end of the day if I'm admin ...
        //if (auth()->user()->is_admin) {
        $regular_end = Utility::getTimeValuesFromEnvTimeString(config('reservations.business_hours.' .
            $start->dayOfWeek . '.end'));
        return $this_day_start->addHours($regular_end['hour'])->addMinutes($regular_end['minute']);
        //}

        // Otherwise only within the block quota ...
        //return $start->clone()->addHours(env('RESERVATION_BLOCK_HOUR_QUOTA'));
    }

    public static function getRoomsDayReservations($resource, $day_start, $day_end)
    {
        return self::whereHas('resource', function($query) use ($resource) {
            $query->where('id', $resource->id);
        })
            ->where('start', '>=', $day_start)
            ->where('end', '<=', $day_end)
            ->get();
    }


}
