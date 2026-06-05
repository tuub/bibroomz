<?php

namespace App\Services\Closings;

use App\Contracts\ClosingSubject;
use App\Models\Closing;

class CreateClosingAction
{
    public function __construct(
        private ClosingDataSanitizer $dataSanitizer,
        private ClosingEventDispatcher $closingEventDispatcher,
    ) {
    }

    /**
     * @param \App\Models\Institution|\App\Models\Resource $closable
     * @param array<string, mixed> $attributes
     */
    public function execute(ClosingSubject $closable, array $attributes): Closing
    {
        $closing = Closing::create($this->dataSanitizer->sanitize($attributes));

        $closable->closings()->save($closing);
        $closing->setRelation('closable', $closable);
        $this->closingEventDispatcher->dispatchCreated($closing);

        return $closing;
    }
}
