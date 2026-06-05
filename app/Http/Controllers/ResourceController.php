<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicResourcesRequest;
use App\Http\Requests\ResourceTimeSlotsRequest;
use App\Services\Http\GetResourceTimeSlotsAction;
use App\Services\Http\ListPublicResourcesAction;
use Illuminate\Http\JsonResponse;

class ResourceController extends Controller
{
    public function __construct(
        private GetResourceTimeSlotsAction $getResourceTimeSlotsAction,
        private ListPublicResourcesAction $listPublicResourcesAction
    ) {
    }

    public function getResources(PublicResourcesRequest $request): JsonResponse
    {
        return response()->json($this->listPublicResourcesAction->execute(
            $request->institutionSlug(),
            $request->resourceGroupSlug(),
            $request->perPage(),
            $request->requestedDate(),
            $request->url()
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function getTimeSlots(ResourceTimeSlotsRequest $request): array
    {
        return $this->getResourceTimeSlotsAction->execute(
            $request->resourceId(),
            $request->happeningId(),
            $request->start(),
            $request->end()
        );
    }
}
