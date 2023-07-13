<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClosingRequest;
use App\Http\Requests\Admin\UpdateClosingRequest;
use App\Mail\ClosingCreated;
use App\Mail\ClosingUpdated;
use App\Models\Closing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ClosingController extends Controller
{
    public static function getClosings(Request $request)
    {
        $closable = Closing::getClosableModel($request->closable_type)->find($request->closable_id);

        return Inertia::render('Admin/Closings/Index', [
            'closable' => $closable,
            'closable_type' => $request->closable_type,
            'closings' => $closable->closings,
        ]);
    }

    public function createClosing(Request $request): Response
    {
        $closable = Closing::getClosableModel($request->closable_type)->find($request->closable_id);

        return Inertia::render('Admin/Closings/Form', [
            'closing' => '',
            'closable_type' => $request->closable_type,
            'closable' => $closable,
        ]);
    }

    public function storeClosing(StoreClosingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $closable = Closing::getClosableModel($request->closable_type)->find($request->closable_id);
        $closing = Closing::create($validated);

        if ($closable->closings()->save($closing)) {
            $users = $closing->getUsersAffected();

            foreach ($users as $user) {
                $happenings = $closing->getUserHappeningsAffected($user);

                Mail::to($user)->queue(new ClosingCreated($closing, $happenings));
            }
        }

        return redirect()->route('admin.closing.index', [
            'closable_id' => $request->closable_id,
            'closable_type' => $request->closable_type,
        ]);
    }

    public function editClosing(Request $request): Response
    {
        $closing = Closing::find($request->id);
        $closable_type = explode('\\', $closing->closable_type);

        return Inertia::render('Admin/Closings/Form', [
            'closing' => $closing,
            'closable' => $closing->closable,
            'closable_type' => strtolower(end($closable_type)),
        ]);
    }

    public function updateClosing(UpdateClosingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $closing = Closing::find($request->id);

        // get previously affected users/happenings
        $users = $closing->getUsersAffected();
        foreach ($users as $user) {
            $happenings = $closing->getUserHappeningsAffected($user);

            $previously[$user->id] = $happenings;
        }

        if ($closing->update($validated)) {
            // send mails to affected users
            $users = $closing->getUsersAffected();

            foreach ($users as $user) {
                $happenings = $closing->getUserHappeningsAffected($user);

                Mail::to($user)->queue(new ClosingUpdated($closing, $happenings, $previously[$user->id]));
            }
        }

        return redirect()->route('admin.closing.index', [
            'closable_id' => $request->closable_id,
            'closable_type' => $request->closable_type,
        ]);
    }

    public function deleteClosing(Request $request): RedirectResponse
    {
        $closing = Closing::find($request->id);
        $closable_type = explode('\\', $closing->closable_type);
        $closing->delete();

        return redirect()->route('admin.closing.index', [
            'closable_id' => $request->closable_id,
            'closable_type' => strtolower(end($closable_type)),
        ]);
    }
}
