<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DeleteMailContentRequest;
use App\Http\Requests\Admin\InstitutionContextRequest;
use App\Http\Requests\Admin\MailContentIdRequest;
use App\Http\Requests\Admin\MailContentRequest;
use App\Models\MailContent;
use App\Services\Admin\MailAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MailController extends AdminController
{
    public function __construct(private readonly MailAdminService $mailAdminService) {}

    public function getMails(InstitutionContextRequest $request): Response
    {
        $institution = $request->institution();

        $this->authorize('viewAny', [MailContent::class, $institution]);

        return Inertia::render('Admin/Mails/Index', $this->mailAdminService->getIndexData($institution));
    }

    public function createMail(InstitutionContextRequest $request): Response
    {
        $institution = $request->institution();

        $this->authorize('create', [MailContent::class, $institution]);

        return Inertia::render('Admin/Mails/Form', $this->mailAdminService->getCreateFormData($institution));
    }

    public function storeMail(MailContentRequest $request): RedirectResponse
    {
        $institution = $request->institution();
        $this->mailAdminService->store($request->validated());

        return redirect()->route('admin.mail.index', [
            'institution_id' => $institution->id,
        ]);
    }

    public function editMail(MailContentIdRequest $request): Response
    {
        $mail = $request->mailContent()->load(['mail_type', 'institution']);

        $this->authorize('edit', $mail);

        return Inertia::render('Admin/Mails/Form', $this->mailAdminService->getEditFormData($mail));
    }

    public function updateMail(MailContentRequest $request): RedirectResponse
    {
        $mail = $request->mailContent();
        $this->mailAdminService->update($mail, $request->validated());

        return redirect()->route('admin.mail.index', [
            'institution_id' => $request->institution()->id,
        ]);
    }

    public function deleteMail(DeleteMailContentRequest $request): RedirectResponse
    {
        $mail = $request->mailContent();
        $this->mailAdminService->delete($mail);

        return redirect()->route('admin.mail.index', [
            'institution_id' => $mail->institution_id,
        ]);
    }
}
