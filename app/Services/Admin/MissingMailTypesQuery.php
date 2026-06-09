<?php

namespace App\Services\Admin;

use App\Models\MailContent;
use App\Models\MailType;
use Illuminate\Database\Eloquent\Collection;

class MissingMailTypesQuery
{
    /**
     * @return Collection<int, MailType>
     */
    public function execute(string $institutionId): Collection
    {
        $usedMailTypeIds = MailContent::query()
            ->where('institution_id', $institutionId)
            ->pluck('mail_type_id');

        return MailType::query()
            ->whereNotIn('id', $usedMailTypeIds)
            ->orderBy('key')
            ->get()
            ->values();
    }
}
