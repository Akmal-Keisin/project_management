<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\Response;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function getProfile(Request $request)
    {
        try {
            $url = url('storage');
            $user = User::find(Auth::user()->id)->toArray();
            if (!is_null($user['image'])) {
                $user['image'] = $url . '/' . $user['image'];
            }
                // ->select(
                //     'users.name',
                //     'users.email',
                //     DB::raw("CASE WHEN users.image IS NOT NULL THEN CONCAT('$url', '/', users.image) ELSE null END AS user_image")
                // );

            $data = [];

            $data['project_progress'] = Project::join('project_user_roles', 'project_user_roles.project_id', '=', 'projects.id')
                ->join('users', 'users.id', '=', 'project_user_roles.user_id')
                ->where('users.id', Auth::user()->id)
                ->whereIn('projects.status', ['Prepare', 'Progress'])
                ->whereNull('projects.date_end')
                ->count();

            $data['project_done'] = Project::join('project_user_roles', 'project_user_roles.project_id', '=', 'projects.id')
                ->join('users', 'users.id', '=', 'project_user_roles.user_id')
                ->where('users.id', Auth::user()->id)
                ->whereIn('projects.status', ['Done', 'Late'])
                ->whereNotNull('projects.date_end')
                ->get()
                ->toArray();

            $data['task_progress'] = Task::join('projects', 'projects.id', '=', 'tasks.project_id')
                ->join('users as task_from', 'task_from.id', '=', 'tasks.task_from_id')
                ->where('tasks.user_id', Auth::user()->id)
                ->whereIn('tasks.status', ['Prepare', 'Progress'])
                ->select(
                    'tasks.id',
                    'tasks.name as task_title',
                    'tasks.description as task_description',
                    'tasks.date_start',
                    'tasks.deadline',
                    'task_from.name as task_from',
                    'projects.name as in_project'
                );

            $user['project_progress'] = $data['project_progress'];
            $user['task_progress'] = $data['task_progress']->count();
            $user['list_task_progress'] = $data['task_progress']->get()->toArray();
            $user['list_project_done'] = $data['project_done'];

            return Response::success($user, 'Profile obtained successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();            
        }
    }

    public function editProfile(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'name' => 'required',
                'email' => [
                    'required',
                    Rule::unique('users')->ignore(Auth::user()->id)
                ],
                'image' => 'nullable|image',
                'password' => 'nullable',
                'confirm_password' => 'required_with:password|same:password'
            ]);

            if ($validate->fails()) {
                return Response::failed($validate->errors(), 'Validation error');
            }

            $user = User::find(Auth::user()->id);
            if (!$user) {
                return Response::failed(null, 'User not found');
            }

            if ($request->file('image') && !is_null($user->image)) {
                Storage::delete($user->image);
                $user->image = $request->file('image')->store('user_image');
            } elseif ($request->file('image') && is_null($user->image)) {
                $user->image = $request->file('image')->store('user_image');
            } 

            $user->name = $request->name;
            $user->email = $request->email;

            if ($request->password) {
                $user->password = Hash::make($request->password);
            }

            $user->save();
            $user->image = url('storage/public/' . $user->image);
            return Response::success($user, 'Profile edited successfully');

        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();
        }
    }
}
