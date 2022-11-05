<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::whereNull('deleted_at')->when( request('search') ,function($query) {
            $query->where('name', 'like', '%' . request('search') . '%');
        })->get();
        $users_total = User::whereNull('deleted_at')->count();
        return view('user.index', [
            'users' => $users,
            'users_total' => $users_total
        ]);
    }

    public function create()
    {
        return view('user.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|unique:users|email',
            'password' => 'required'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect('user')->with('success', 'User created successfully');
    }

    public function edit($id)
    {
        $user = User::find($id);
        return view('user.edit', [
            'user' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => [
                Rule::unique('users')->ignore($id)
            ],
            'password' => 'nullable'
        ]);

        $user = User::find($id);
        $user->name = $request->name;
        $user->email = $request->email;

        if (!Hash::check($request->password, $user->password)) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect('user')->with('success', 'User updated successfully');
    }

    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully');
    }
}
