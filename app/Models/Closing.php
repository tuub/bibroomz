<?php

namespace App\Models;

use App\Library\Traits\UUIDIsPrimaryKey;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Closing extends Model
{
    use HasFactory, HasUUID, UUIDIsPrimaryKey;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'closings';
    protected $uuidFieldName = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['closable_id', 'closable_type', 'start', 'end', 'description'];
    protected $dates = ['start', 'end'];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function closable(): MorphTo
    {
        return $this->morphTo();
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/

    public static function getClosableModel(string $closableType)
    {
        $fullModelName = __NAMESPACE__ . '\\' . ucfirst($closableType);
        return new $fullModelName();
    }

    public function getHappeningsAffected()
    {
        return $this->closable->happenings
            ->where('end', '>', $this->start)
            ->where('start', '<', $this->end);
    }

    public function getUsersAffected()
    {
        return $this->getHappeningsAffected()->flatMap(function ($happening) {
            return $happening->users();
        })->unique();
    }

    public function getUserHappeningsAffected(User $user)
    {
        return $this->getHappeningsAffected()->filter(function ($happening) use ($user) {
            return $happening->isBelongingTo($user);
        });
    }
}
