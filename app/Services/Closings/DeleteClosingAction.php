<?php

declare(strict_types=1);

namespace App\Services\Closings;

use App\Models\Closing;

class DeleteClosingAction
{
    public function __construct(private readonly ClosingEventDispatcher $closingEventDispatcher) {}

    public function execute(Closing $closing): bool
    {
        if (! $closing->delete()) {
            return false;
        }

        $this->closingEventDispatcher->dispatchDeleted($closing);

        return true;
    }
}
