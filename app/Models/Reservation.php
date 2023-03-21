<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BinaryCabin\LaravelUUID\Traits\HasUUID;

class Reservation extends Model
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    use HasFactory, HasUUID;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'reservations';
    protected $uuidFieldName = 'id';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['user_id_01', 'user_id_02', 'is_confirmed', 'confirmer', 'start', 'end', 'reserved_at', 'booked_at'];

    /**
     * The attributes that should be cast as datetimes.
     *
     * @var array<string, string>
     */
    protected $dates = ['created_at', 'updated_at', 'start', 'end', 'reserved_at', 'booked_at'];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    public function resource()
    {
        return $this->belongsToMany(Resource::class, 'reservation_resource');
    }

    public function user1()
    {
        return $this->belongsTo(User::class, 'user_id_01', 'id');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user_id_02', 'id');
    }

    public function scopeWhereDateBetween($query, $fieldName, $fromDate, $toDate)
    {
        return $query->whereDate($fieldName, '>=', $fromDate)->whereDate($fieldName, '<=', $toDate);
    }

    public function getUser2()
    {
        if ( ! $this->getAttribute('user_id_02')) {
            return null;
        }
        return $this->getAttribute('user2');
    }

    // See: https://stackoverflow.com/a/25163557/6948765
    public function getRoomAttribute()
    {
        return $this->room()->first();
    }




}
