<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DeleteInstitutionRequest;
use App\Http\Requests\Admin\InstitutionIdRequest;
use App\Http\Requests\Admin\InstitutionOrderRequest;
use App\Http\Requests\Admin\InstitutionRequest;
use App\Models\Institution;
use App\Services\Admin\InstitutionAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends AdminController
{
    public function __construct(private InstitutionAdminService $institutionAdminService)
    {
    }

    public function getInstitutions(): Response
    {
        return Inertia::render('Admin/Institutions/Index', $this->institutionAdminService->getIndexData());
    }

    public function orderInstitutions(InstitutionOrderRequest $request): void
    {
        $this->institutionAdminService->reorder($request->rows()->all());
    }

    public function createInstitution(): Response
    {
        $this->authorize('create', Institution::class);

        return Inertia::render('Admin/Institutions/Form', $this->institutionAdminService->getCreateFormData());
    }

    public function storeInstitution(InstitutionRequest $request): RedirectResponse
    {
        $this->authorize('create', Institution::class);

        $this->institutionAdminService->store($request->institutionData(), $request->weekDays());

        return redirect()->route('admin.institution.index');
    }

    public function editInstitution(InstitutionIdRequest $request): Response
    {
        $institution = $request->institution()->load('closings', 'week_days:id');

        $this->authorize('edit', $institution);

        return Inertia::render(
            'Admin/Institutions/Form',
            $this->institutionAdminService->getEditFormData($institution),
        );
    }

    public function updateInstitution(InstitutionRequest $request): RedirectResponse
    {
        $institution = $request->institution();
        $this->institutionAdminService->update($institution, $request->institutionData(), $request->weekDays());

        return redirect()->route('admin.institution.index');
    }

    public function deleteInstitution(DeleteInstitutionRequest $request): RedirectResponse
    {
        $institution = $request->institution();
        $this->institutionAdminService->delete($institution);

        return redirect()->route('admin.institution.index');
    }
}
