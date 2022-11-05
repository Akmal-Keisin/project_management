<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        if ($request->search) {
            $admins = Admin::where('name', 'like', '%' . $request->search . '%')->get();
        } else {
            $admins = Admin::all();
        }
        $total_admin = Admin::all();
        return view('admin.index', [
            'admins' => $admins,
            'total_admin' => count($total_admin)
        ]);
    }

    public function create()
    {
        return view('admin.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:admins',
            'password' => 'required'
        ]);

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'])
        ]);

        return redirect('admin')->with('success', 'Admin created successfully');
    }

    public function edit($id)
    {
        $admin = Admin::find($id);
        return view('admin.edit', ['admin' => $admin]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => [
                Rule::unique('admins')->ignore($id)
            ],
            'password' => 'nullable'
        ]);

        $admin = Admin::find($id);
        $admin->name = $validated['name'];
        $admin->email = $validated['email'];

        if (!Hash::check($request->password, $admin->password)) {
            $admin->password = Hash::make($request->password);
        }
        $admin->save();

        return redirect('admin')->with('success', 'Admin updated successfully');
    }

    public function destroy($id)
    {
        $admin = Admin::find($id);
        $admin->delete();
        return redirect()->back()->with('success', 'Admin deleted successfully');
    }
}
