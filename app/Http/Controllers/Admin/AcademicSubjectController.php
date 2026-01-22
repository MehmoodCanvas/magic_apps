<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use Illuminate\Http\Request;

class AcademicSubjectController extends Controller
{
    public function index()
    {
        $subjects = AcademicSubject::latest()->paginate(10);
        return view('admin.academic_subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('admin.academic_subjects.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('subjects'), $filename);
            $data['image'] = '/subjects/' . $filename;
        }

        AcademicSubject::create($data);

        return redirect()->route('admin.academic-subjects.index')->with('success', 'Academic Subject created successfully.');
    }

    public function edit(AcademicSubject $academicSubject)
    {
        return view('admin.academic_subjects.edit', compact('academicSubject'));
    }

    public function update(Request $request, AcademicSubject $academicSubject)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            if ($academicSubject->image && file_exists(public_path($academicSubject->image))) {
                unlink(public_path($academicSubject->image));
            }
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('subjects'), $filename);
            $data['image'] = '/subjects/' . $filename;
        }

        $academicSubject->update($data);

        return redirect()->route('admin.academic-subjects.index')->with('success', 'Academic Subject updated successfully.');
    }

    public function destroy(AcademicSubject $academicSubject)
    {
        if ($academicSubject->image && file_exists(public_path($academicSubject->image))) {
            unlink(public_path($academicSubject->image));
        }
        $academicSubject->delete();
        return redirect()->route('admin.academic-subjects.index')->with('success', 'Academic Subject deleted successfully.');
    }
}
