<?php

namespace App\Services\Closings;

use App\Contracts\ClosingSubject;
use App\Models\Closing;
use App\Models\Institution;

class ClosingInstitutionResolver
{
    public function resolveForClosing(Closing $closing): Institution
    {
        return $closing->getInstitution();
    }

    /**
     * @param  Institution|\App\Models\Resource  $closable
     */
    public function resolveForClosable(ClosingSubject $closable): Institution
    {
        return $closable->institutionForClosings();
    }
}
