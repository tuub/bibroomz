<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Closing extends Model
{
    use HasFactory, HasUuids, HasTranslations, Prunable, SoftDeletes;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'closings';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'closable_id',
        'closable_type',
        'start',
        'end',
        'description',
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    protected $translatable = [
        'description',
    ];

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
        $modelName = array_map('ucfirst', explode('_', $closableType));
        $fullModelName = __NAMESPACE__ . '\\' . implode('', $modelName);

        return new $fullModelName();
    }

    public function getHappeningsAffected()
    {
        return $this->closable->getHappenings()
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

    public function prunable(): Builder
    {
        return static::onlyTrashed();
    }
}
