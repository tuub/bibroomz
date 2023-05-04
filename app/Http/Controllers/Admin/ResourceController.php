<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResourceController extends Controller
{
    public static function getResources(Request $request)
    {
        return Inertia::render('Admin/Resources/Index', [
            'resources' => Resource::with(['institution', 'closings'])->get()
        ]);
    }

    public static function getFormResources()
    {
        return Resource::get(['id', 'title']);
    }

    public function createResource(Request $request)
    {
        return Inertia::render('Admin/Resources/Form');
    }

    public function storeResource(Request $request)
    {
        // Validate
        $attributes = $request->validate([
            'institution_id' => 'required',
            'title' => 'required',
            'location' => 'required',
            'description' => 'required',
            'capacity' => ['numeric', 'gt:0'],
            'opens_at' => 'required',
            'closes_at' => 'required',
            'is_active' => 'required',
        ]);
        // Create
        Resource::create($attributes);
        // Redirect
        return redirect('/admin/resources');
    }

    public function editResource(Request $request)
    {
        $resource = Resource::where('id', $request->id)->with('closings')->firstOrFail();
        return Inertia::render('Admin/Resources/Form', $resource);
    }

    public function updateResource(Request $request)
    {
        $resource = Resource::find($request->id);

        // Validate
        $attributes = $request->validate([
            'institution_id' => 'required',
            'title' => 'required',
            'location' => 'required',
            'description' => 'required',
            'capacity' => ['numeric', 'gt:0'],
            'opens_at' => 'required',
            'closes_at' => 'required',
            'is_active' => 'required',
        ]);
        // Update
        $resource->update($attributes);
        // Redirect
        return redirect('/admin/resources');
    }
}
