<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class BadgeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $badges = Badge::latest()->get();
        return view('admin.badges.index', compact('badges'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.badges.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'type' => ['required', Rule::in(['skills', 'goals'])],
            'required_amount' => 'required|integer|min:1',
        ]);

        $data = $request->only(['name', 'description', 'type', 'required_amount']);

        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('badges-attachment'), $filename);
            $data['icon'] = '/badges-attachment/' . $filename;
        }

        Badge::create($data);

        return redirect()->route('admin.badges.index')->with('success', 'Badge created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Not used, using index instead.
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $badge = Badge::findOrFail($id);
        return view('admin.badges.edit', compact('badge'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $badge = Badge::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'type' => ['required', Rule::in(['skills', 'goals'])],
            'required_amount' => 'required|integer|min:1',
        ]);

        $data = $request->only(['name', 'description', 'type', 'required_amount']);

        if ($request->hasFile('icon')) {
            // Delete old icon if it exists
            if ($badge->icon) {
                $oldIconPath = public_path($badge->icon);
                if (File::exists($oldIconPath)) {
                    File::delete($oldIconPath);
                }
            }

            $file = $request->file('icon');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('badges-attachment'), $filename);
            $data['icon'] = '/badges-attachment/' . $filename;
        }

        $badge->update($data);

        return redirect()->route('admin.badges.index')->with('success', 'Badge updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $badge = Badge::findOrFail($id);

        if ($badge->icon) {
            $oldIconPath = public_path($badge->icon);
            if (File::exists($oldIconPath)) {
                File::delete($oldIconPath);
            }
        }

        $badge->delete();

        return redirect()->route('admin.badges.index')->with('success', 'Badge deleted successfully.');
    }
}
