<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use App\Library\Traits\UUIDIsPrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /*****************************************************************
     * TRAITS
     ****************************************************************/
    use HasApiTokens, HasFactory, Notifiable, HasUUID, UUIDIsPrimaryKey;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'users';
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

    public function happenings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Happening::class, 'user_id_01', 'id');
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'institution_admins');
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * @param Institution|null $institution
     * @return bool
     */
    public function isInstitutionAdmin(Institution $institution = null): bool
    {
        if ($institution) {
            return $this->institutions->contains($institution);
        }

        return $this->institutions->isNotEmpty();
    }

    /** @return Collection<array-key, bool>  */
    public function getUserAdministeredInstitutions()
    {
        return Institution::all()->mapWithKeys(fn ($institution) => [$institution->id => $this->isInstitutionAdmin($institution)]);
    }
}
