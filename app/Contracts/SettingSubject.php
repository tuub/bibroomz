<?php

namespace App\Contracts;

use App\Models\Institution;

interface SettingSubject
{
    public function institutionForSettings(): Institution;
}
