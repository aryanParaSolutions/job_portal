<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalkDuration;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class WalkDurationController extends Controller
{
    public function index()
    {
        $walkdurations = WalkDuration::all();
        return view('admin.walk-durations.index', compact('walkdurations'));
    }

    public function create()
    {
        return view('admin.walk-durations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            "duration" => "required|string|max:255",
            "price" => "required|integer",
        ]);

        $data=$request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        WalkDuration::create($data);

        return redirect()->route('walk-duration.index')->with('success', 'Service Duration added successfully');
    }

    public function edit($id)
    {
        $walkduration = WalkDuration::findOrFail($id);
        return view('admin.walk-durations.edit', compact('walkduration'));
    }

    public function update(Request $request, $id)
    {
        $walkduration = WalkDuration::findOrFail($id);
        $request->validate([
            'duration' => 'required|string|max:255',
            'price' => 'required|integer',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        // dd($data);

        $walkduration->update($data);

        return redirect()->route('walk-duration.index')->with('success','Service duration updated sucessfully');
    }

    public function destroy($id)
{
    try {
        $duration = WalkDuration::findOrFail($id);
        $duration->delete();

        return redirect()->route('walk-duration.index')
            ->with('success', 'Duration deleted successfully.');
    } catch (QueryException $e) {
        if ($e->getCode() == '23000') {
            return redirect()->route('walk-duration.index')
                ->with('error', 'This duration cannot be deleted because it is already in use.');
        }

        return redirect()->route('walk-duration.index')
            ->with('error', 'An unexpected error occurred.');
    }
}

}
