<?php

namespace App\Models;

use App\Library\Traits\UUIDIsPrimaryKey;
use App\Traits\HasTranslations;
use BinaryCabin\LaravelUUID\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailType extends Model
{
    use HasFactory;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'mail_types';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    public function mail_contents(): hasMany
    {
        return $this->hasMany(MailContent::class);
    }
}
