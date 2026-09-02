<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids as HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailContent extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    use HasTranslations, HasUuids;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'mail_contents';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'institution_id',
        'mail_type_id',
        'subject',
        'title',
        'salutation',
        'intro',
        'outro',
        'action_uri',
        'action_uri_label',
        'farewell',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $translatable = [
        'subject',
        'title',
        'salutation',
        'intro',
        'outro',
        'farewell',
    ];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/
    /**
     * @return BelongsTo<Institution, $this>
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * @return BelongsTo<MailType, $this>
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function mail_type(): BelongsTo
    {
        return $this->belongsTo(MailType::class);
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    public function isViewableByUser(User $user): bool
    {
        return $user->can('view', $this);
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
