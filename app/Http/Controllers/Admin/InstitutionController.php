<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Resource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    public static function getInstitutions(Request $request)
    {
        return Inertia::render('Admin/Institutions/Index', [
            'institutions' => Institution::with(['resources', 'closings'])->get()
        ]);
    }

    public static function getFormInstitutions()
    {
        return Institution::get(['id', 'title']);
    }

    public function createInstitution(Request $request)
    {
        return Inertia::render('Admin/Institutions/Form');
    }

    public function storeInstitution(Request $request)
    {
        // Validate
        $attributes = $request->validate([
            'title' => 'required',
            'short_title' => 'required',
            'slug' => 'required',
            'location' => 'required',
            'is_active' => 'required',
        ]);
        // Create
        Institution::create($attributes);
        // Redirect
        return redirect('/admin/institutions');
    }

    public function editInstitution(Request $request)
    {
        $institution = Institution::where('id', $request->id)->with('closings')->firstOrFail();
        return Inertia::render('Admin/Institutions/Form', $institution);
    }

    public function updateInstitution(Request $request)
    {
        $resource = Institution::find($request->id);

        // Validate
        $attributes = $request->validate([
            'title' => 'required',
            'short_title' => 'required',
            'slug' => 'required',
            'location' => 'required',
            'is_active' => 'required',
        ]);
        // Update
        $resource->update($attributes);
        // Redirect
        return redirect()->route('admin.institution.index');
    }
}
