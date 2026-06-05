<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ClosableContextRequest;
use App\Http\Requests\Admin\ClosingIdRequest;
use App\Http\Requests\Admin\DeleteClosingRequest;
use App\Http\Requests\Admin\StoreClosingRequest;
use App\Http\Requests\Admin\UpdateClosingRequest;
use App\Models\Closing;
use App\Services\Admin\ClosingAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClosingController extends AdminController
{
    public function __construct(
        private ClosingAdminService $closingAdminService,
    ) {
    }

    public function getClosings(ClosableContextRequest $request): Response
    {
        $closable = $this->closingAdminService->resolveClosable(
            $request->closableType(),
            $request->closableId(),
        );

        $this->authorize('viewAny', [Closing::class, $closable]);

        return Inertia::render('Admin/Closings/Index', $this->closingAdminService->getIndexData(
            $closable,
            $request->closableType(),
        ));
    }

    public function createClosing(ClosableContextRequest $request): Response
    {
        $closable = $this->closingAdminService->resolveClosable(
            $request->closableType(),
            $request->closableId(),
        );

        $this->authorize('create', [Closing::class, $closable]);

        return Inertia::render('Admin/Closings/Form', $this->closingAdminService->getCreateFormData(
            $closable,
            $request->closableType(),
        ));
    }

    public function storeClosing(StoreClosingRequest $request): RedirectResponse
    {
        $closable = $request->closable();

        if ($closable === null) {
            abort(404);
        }

        $this->closingAdminService->store($closable, $request->validated());

        return redirect()->route('admin.closing.index', [
            'closable_id' => $request->closableId(),
            'closable_type' => $request->closableType(),
        ]);
    }

    public function editClosing(ClosingIdRequest $request): Response
    {
        $closing = $request->closing();

        $this->authorize('edit', $closing);

        return Inertia::render('Admin/Closings/Form', $this->closingAdminService->getEditFormData($closing));
    }

    public function updateClosing(UpdateClosingRequest $request): RedirectResponse
    {
        $closing = $request->closing();
        $this->closingAdminService->update($closing, $request->validated());

        return redirect()->route('admin.closing.index', [
            'closable_id' => $request->closableId(),
            'closable_type' => $request->closableType(),
        ]);
    }

    public function deleteClosing(DeleteClosingRequest $request): RedirectResponse
    {
        $closing = $request->closing();
        $redirectData = $this->closingAdminService->redirectData($closing);
        $this->closingAdminService->delete($closing);

        return redirect()->route('admin.closing.index', $redirectData);
    }
}
