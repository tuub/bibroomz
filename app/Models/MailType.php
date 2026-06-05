<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailType extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
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

    /**
     * @return HasMany<MailContent, $this>
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function mail_contents(): HasMany
    {
        return $this->hasMany(MailContent::class);
    }
}
