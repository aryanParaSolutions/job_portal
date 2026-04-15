<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DogService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DogServiceController extends Controller
{
    /**
     * Display a listing of the services.
     */
    public function index()
    {
        $services = DogService::all();
        return view('admin.dog-services.index', compact('services'));
    }

    /**
     * Show the form for creating a new service.
     */
    public function create()
    {
        return view('admin.dog-services.create');
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(Request $request)
    {
        // dd($request);
        $request->validate([
            'service_name' => 'required|string|max:100',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_active' => 'string|in:0,1'
        ]);

        $data = $request->only('service_name');

        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('services', 'public');
        }

        DogService::create($data);

        return redirect()->route('dog-services.index')->with('success', 'Service added successfully.');
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit($id)
    {
        $service = DogService::findOrFail($id);
        return view('admin.dog-services.edit', compact('service'));
    }

    /**
     * Update the specified service in storage.
     */
    public function update(Request $request, $id)
    {
        $service = DogService::findOrFail($id);
        $request->validate([
            'service_name' => 'required|string|max:100',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_active' => 'string|in:0,1',
        ]);

        $data = $request->only(['service_name', 'is_active']);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        if ($request->hasFile('thumbnail')) {
            if ($service->thumbnail) {
                Storage::disk('public')->delete($service->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('services', 'public');
        }

        $service->update($data);

        return redirect()->route('dog-services.index')->with('success', 'Service updated successfully.');
    }

    public function destroy($id)
    {
        try {
            $service = DogService::findOrFail($id);


            if ($service->thumbnail) {
                Storage::disk('public')->delete($service->thumbnail);
            }


            $service->delete();

            return redirect()->route('dog-services.index')
                ->with('success', 'Service deleted successfully.');
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return redirect()->route('dog-services.index')
                    ->with('error', 'This service cannot be deleted because it is already in use.');
            }

            return redirect()->route('dog-services.index')
                ->with('error', 'An unexpected error occurred while deleting the service.');
        }
    }
}
