<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'key',
    'description',
])]
#[Table(name: 'mail_types')]
#[WithoutTimestamps]
class MailType extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    public $incrementing = true;

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
