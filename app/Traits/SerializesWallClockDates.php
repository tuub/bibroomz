<?php

declare(strict_types=1);

namespace App\Traits;

use DateTimeInterface;

/**
 * Serialize dates the way this app stores them: without a zone.
 *
 * Eloquent's default is `toJSON()`, which converts to UTC and appends a `Z`.
 * That is wrong for a model whose dates reach the browser, because every other
 * datetime the API sends is wall-clock time in `config('app.timezone')` -
 * `CalendarEntryPresenter` and `UserHappeningPresenter` format theirs by hand -
 * and the frontend reads them all as such (see `resources/js/appTime.ts`). A
 * converted date would arrive an offset early and be rendered an offset early.
 */
trait SerializesWallClockDates
{
    #[\Override]
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
