<?php

namespace App\Models;

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
        'key',
        'description',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function mail_contents(): hasMany
    {
        return $this->hasMany(MailContent::class);
    }
}
