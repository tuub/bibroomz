<?php

namespace App\Services\Admin;

use App\Models\Closing;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\AdminLoggingService;
use App\Services\Closings\CreateClosingAction;
use App\Services\Closings\DeleteClosingAction;
use App\Services\Closings\ListClosingsAction;
use App\Services\Closings\UpdateClosingAction;
use Carbon\Carbon;

class ClosingAdminService
{
    public function __construct(
        private readonly AdminLoggingService $adminLoggingService,
        private readonly ClosableResolver $closableResolver,
        private readonly ListClosingsAction $listClosingsAction,
        private readonly CreateClosingAction $createClosingAction,
        private readonly UpdateClosingAction $updateClosingAction,
        private readonly DeleteClosingAction $deleteClosingAction,
    ) {}

    public function resolveClosable(string $closableType, string $closableId): Institution|Resource
    {
        return $this->closableResolver->resolve($closableType, $closableId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getIndexData(Institution|Resource $closable, string $closableType): array
    {
        return [
            'closable' => $closable->withoutRelations(),
            'closable_type' => $closableType,
            ...$this->resolveClosableContext($closable),
            'closings' => $this->listClosingsAction->execute($closable),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCreateFormData(Institution|Resource $closable, string $closableType): array
    {
        return [
            'closable' => $closable,
            'closable_type' => $closableType,
            ...$this->resolveClosableContext($closable),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditFormData(Closing $closing): array
    {
        $closable = $this->resolveClosingClosable($closing);

        return [
            'closing' => [
                ...$closing->toArray(),
                'start_date' => Carbon::parse($closing->start)->format('d.m.Y'),
                'start_time' => Carbon::parse($closing->start)->format('H:i'),
                'end_date' => Carbon::parse($closing->end)->format('d.m.Y'),
                'end_time' => Carbon::parse($closing->end)->format('H:i'),
            ],
            'closable' => $closable,
            'closable_type' => $this->closableResolver->typeForModel($closable),
            ...$this->resolveClosableContext($closable),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @return array{institution: Institution|null, resource_group: ResourceGroup|null}
     */
    private function resolveClosableContext(Institution|Resource $closable): array
    {
        if (! $closable instanceof Resource) {
            return ['institution' => null, 'resource_group' => null];
        }

        $closable->loadMissing('resource_group.institution');

        return [
            'institution' => $closable->resource_group->institution,
            'resource_group' => $closable->resource_group,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(Institution|Resource $closable, array $attributes): Closing
    {
        $closing = $this->createClosingAction->execute($closable, $attributes);

        $this->adminLoggingService->log('created', $closing);

        return $closing;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Closing $closing, array $attributes): Closing
    {
        $updatedClosing = $this->updateClosingAction->execute($closing, $attributes);

        $this->adminLoggingService->log('updated', $updatedClosing);

        return $updatedClosing;
    }

    public function delete(Closing $closing): void
    {
        $this->deleteClosingAction->execute($closing);

        $this->adminLoggingService->log('deleted', $closing);
    }

    /**
     * @return array<string, mixed>
     */
    public function redirectData(Closing $closing): array
    {
        $closable = $this->resolveClosingClosable($closing);

        return [
            'closable_id' => $closing->closable_id,
            'closable_type' => $this->closableResolver->typeForModel($closable),
        ];
    }

    private function resolveClosingClosable(Closing $closing): Institution|Resource
    {
        $closable = $closing->closable;
        abort_unless($closable instanceof Institution || $closable instanceof Resource, 500);

        return $closable;
    }
}
