<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MailContentRequest;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MailController extends Controller
{
    public function getMails(Request $request)
    {
        $institution = Institution::findOrFail($request->id);

        $this->authorize('viewAny', [MailContent::class, $institution]);

        $mails = MailContent::with(['mail_type', 'institution'])->where('institution_id', $institution->id)->get()
            ->filter->isViewableByUser(auth()->user());

        return Inertia::render('Admin/Mails/Index', [
            'institution' => $institution,
            'mails' => $mails,
        ]);
    }

    public function createMail(Request $request)
    {
        $institution = Institution::findOrFail($request->id);
        $this->authorize('create', [MailContent::class, $institution]);

        $mail_types = $this->getMissingMailTypes($institution->id);

        return Inertia::render('Admin/Mails/Form', [
            'institution_id' => $institution->id,
            'mail_types' => $mail_types,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function storeMail(MailContentRequest $request): RedirectResponse
    {
        $institution = Institution::findOrFail($request->institution_id);
        $this->authorize('create', [MailContent::class, $institution]);

        $validated = $request->validated();
        MailContent::create($validated);

        return redirect()->route('admin.mail.index', [
            'id' => $institution->id,
        ]);
    }

    public function editMail(Request $request): Response
    {
        $mail = MailContent::where('id', $request->id)->with(['mail_type', 'institution'])->firstOrFail();

        $this->authorize('edit', $mail);

        $mail_types = $this->getMissingMailTypes($mail->institution_id);

        return Inertia::render('Admin/Mails/Form', [
            'mail' => $mail,
            'institution_id' => $mail->institution_id,
            'mail_types' => $mail_types,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function updateMail(MailContentRequest $request): RedirectResponse
    {
        $mail = MailContent::find($request->id);

        $this->authorize('edit', $mail);

        $validated = $request->validated();
        $mail->update($validated);

        return redirect()->route('admin.mail.index', [
            'id' => $request->institution_id,
        ]);
    }

    public function deleteMail(Request $request): RedirectResponse
    {
        $mail = MailContent::findOrFail($request->id);

        $this->authorize('delete', $mail);
        $mail->delete();

        return redirect()->route('admin.mail.index', [
            'id' => $mail->institution_id,
        ]);
    }


    private function getMissingMailTypes(String $institution_id)
    {
        return MailType::orderBy('key')->get()->filter(function ($mail_type) use ($institution_id) {
            return MailContent::where('institution_id', $institution_id)
                ->pluck('mail_type_id')
                ->contains($mail_type->id) === false;
        });
    }
}
