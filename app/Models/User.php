<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use BinaryCabin\LaravelUUID\Traits\HasUUID;

class User extends Authenticatable
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    use HasApiTokens, HasFactory, Notifiable, HasUUID;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected string $uuidFieldName = 'id';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'is_admin',
        'banned_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_admin' => 'boolean',
        'banned_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    /*****************************************************************
     * METHODS
     ****************************************************************/

}
