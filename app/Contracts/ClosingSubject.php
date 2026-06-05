<?php

namespace App\Contracts;

use App\Models\Happening;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * @template TModel of Model
 */
interface ClosingSubject
{
    /**
     * @return MorphMany<\App\Models\Closing, TModel>
     */
    public function closings(): MorphMany;

    /**
     * @return Collection<int, Happening>
     */
    public function getHappenings(): Collection;

    public function institutionForClosings(): Institution;
}
