<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DeleteHappeningRequest;
use App\Http\Requests\Admin\HappeningIdRequest;
use App\Http\Requests\Admin\StoreHappeningRequest;
use App\Http\Requests\Admin\UpdateHappeningRequest;
use App\Models\Happening;
use App\Services\Admin\HappeningAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HappeningController extends AdminController
{
    public function __construct(
        private HappeningAdminService $happeningAdminService,
    ) {
    }

    public function getHappenings(): Response
    {
        return Inertia::render(
            'Admin/Happenings/Index',
            $this->happeningAdminService->getIndexData($this->authenticatedUser()),
        );
    }

    public function createHappening(): Response
    {
        return Inertia::render('Admin/Happenings/Form', $this->happeningAdminService->getCreateFormData(
            $this->authenticatedUser(),
        ));
    }

    public function storeHappening(StoreHappeningRequest $request): RedirectResponse
    {
        $this->happeningAdminService->store($request->sanitized());

        return redirect()->route('admin.happening.index');
    }

    public function editHappening(HappeningIdRequest $request): Response
    {
        /** @var Happening $happening */
        $happening = $request->happening();

        $this->authorize('adminUpdate', $happening);

        return Inertia::render('Admin/Happenings/Form', $this->happeningAdminService->getEditFormData($happening));
    }

    public function updateHappening(UpdateHappeningRequest $request): RedirectResponse
    {
        $happening = $request->happening();
        $this->happeningAdminService->update(
            $happening,
            $request->sanitized(),
        );

        return redirect()->route('admin.happening.index');
    }

    public function deleteHappening(DeleteHappeningRequest $request): RedirectResponse
    {
        $happening = $request->happening();
        $this->happeningAdminService->delete($happening);

        return redirect()->route('admin.happening.index');
    }
}
