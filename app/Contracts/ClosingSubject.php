<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * @template TModel of Model
 */
interface ClosingSubject
{
    /**
     * @return MorphMany<Closing, TModel>
     */
    public function closings(): MorphMany;

    /**
     * Happenings overlapping [$start, $end), pre-filtered in SQL.
     *
     * @return Collection<int, Happening>
     */
    public function getHappenings(DateTimeInterface $start, DateTimeInterface $end): Collection;

    public function institutionForClosings(): Institution;
}
