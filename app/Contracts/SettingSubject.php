<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Institution;

interface SettingSubject
{
    public function institutionForSettings(): Institution;
}
