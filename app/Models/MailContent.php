<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailContent extends Model
{
    use HasFactory, HasUuids, HasTranslations;

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

    protected $casts = [
        'is_active' => 'boolean',
    ];

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
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

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
}
