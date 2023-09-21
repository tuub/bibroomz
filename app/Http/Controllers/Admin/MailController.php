<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MailContentRequest;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class MailController extends Controller
{
    public function getMails(Request $request)
    {
        $institution = Institution::findOrFail($request->id);

        $mails = MailContent::with(['mail_type', 'institution'])->where('institution_id', $institution->id)->get()
            ->filter->isViewableByUser(auth()->user());

        return Inertia::render('Admin/Mails/Index', [
            'institution' => $institution,
            'mails' => $mails,
        ]);
    }

    public function createMail(Request $request)
    {
        // Get only mail types that are not already present
        $mail_types = MailType::orderBy('name')->get()->filter(function($mail_type) use ($request) {
            return MailContent::where('institution_id', $request->id)
                ->pluck('mail_type_id')
                ->contains($mail_type->id) === false;
        });

        return Inertia::render('Admin/Mails/Form', [
            'institution_id' => $request->id,
            'mail_types' => $mail_types,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function storeMail(MailContentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        MailContent::create($validated);

        return redirect()->route('admin.mail.index', [
            'id' => $request->institution_id,
        ]);
   }

    public function editMail(Request $request): Response
    {
        $mail = MailContent::where('id', $request->id)->with([
            'mail_type',
            'institution',
        ])->firstOrFail();

        // Get only mail types that are not already present
        $mail_types = MailType::orderBy('name')->get()->filter(function($mail_type) use ($mail) {
            return MailContent::where('institution_id', $mail->institution_id)
                ->pluck('mail_type_id')
                ->contains($mail_type->id) === false;
        });

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
        $validated = $request->validated();
        $mail->update($validated);

        return redirect()->route('admin.mail.index', [
            'id' => $request->institution_id,
        ]);
    }

    public function deleteMail(Request $request): RedirectResponse
    {
        $mail = MailContent::findORFail($request->id);
        $institution_id = $mail->institution_id;

        $this->authorize('delete', $mail);
        $mail->delete();

        return redirect()->route('admin.mail.index', [
            'id' => $institution_id,
        ]);
    }
}
