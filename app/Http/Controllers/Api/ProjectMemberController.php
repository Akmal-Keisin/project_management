<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Role;
use App\Models\ProjectUserRole;
use App\Models\User;
use App\Models\Task;
use App\Helpers\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProjectMemberController extends Controller
{
    public function listMember($id)
    {
        try {
            $url = url('storage');
            $members = Project::join('project_user_roles', 'project_user_roles.project_id', '=', 'projects.id')
                ->join('users', 'users.id', '=', 'project_user_roles.user_id')
                ->join('roles', 'roles.id', '=', 'project_user_roles.role_id')
                ->where('projects.id', $id)
                ->whereNull('project_user_roles.deleted_at')
                ->whereNull('projects.deleted_at')
                ->whereNull('users.deleted_at')
                ->whereNull('roles.deleted_at')
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    'users.email as user_email',
                    DB::raw("CASE WHEN users.image IS NOT NULL THEN CONCAT('$url', '/', users.image) ELSE null END AS user_image"),
                    'projects.id as project_id',
                    'projects.name as project_name',
                    'roles.id as role_id',
                    'roles.name as role_name'
                )
                ->get();
            return Response::success($members, 'Members obtained successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();            
        }
    }

    public function detailMember(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'project_id' => 'required|numeric',
                'member_id' => 'required|numeric'
            ]);

            if ($validate->fails()) {
                return Response::failed($validate->errors(), 'Validation error');
            }

            $validated = $validate->validated();

            $url = url('storage');
            $member = Project::join('project_user_roles', 'projects.id', '=', 'project_user_roles.project_id')
                ->join('roles', 'roles.id', '=', 'project_user_roles.role_id')
                ->join('users', 'users.id', '=', 'project_user_roles.user_id')
                ->join('tasks', 'tasks.project_id', '=', 'projects.id')
                ->where('users.id', $validated['member_id'])
                ->where('projects.id', $validated['project_id'])
                ->select(
                    'users.name',
                    'users.email',
                    DB::raw("CASE WHEN users.image IS NOT NULL THEN CONCAT('$url', '/', users.image) ELSE null END AS user_image")
                )
                ->first()
                ->toArray();

            $data = [];
            $data['task_done'] = Task::join('projects', 'projects.id', '=', 'tasks.project_id')
                ->join('users as task_from', 'task_from.id', '=', 'tasks.task_from_id')
                ->where('tasks.user_id', $validated['member_id'])
                ->where('projects.id', $validated['project_id'])
                ->whereIn('tasks.status', ['Done', 'Late'])
                ->select(
                    'tasks.id',
                    'tasks.name',
                    'tasks.description',
                    'tasks.date_start',
                    'tasks.deadline',
                    'tasks.task_from_id',
                    'projects.name as in_project'
                );

            $data['task_progress'] = Task::join('projects', 'projects.id', '=', 'tasks.project_id')
                ->join('users as task_from', 'task_from.id', '=', 'tasks.task_from_id')
                ->where('tasks.user_id', $validated['member_id'])
                ->where('projects.id', $validated['project_id'])
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
            $member['task_done'] = $data['task_done']->count();
            $member['task_progress'] = $data['task_progress']->count();
            $member['list_task_done'] = $data['task_done']->get()->toArray();
            $member['list_task_progress'] = $data['task_progress']->get()->toArray();

            return Response::success($member, 'Members obtained successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();            
        }
    }

    public function addMember(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'user_id' => 'required|numeric',
                'role_id' => 'required|numeric',
                'project_id' => 'required|numeric'
            ]);

            if ($validate->fails()) {
                return Response::failed($validate->errors(), 'Validation error');
            }

            $validated = $validate->validated();

            // Check user role in project
            $projectUserRole = Project::join('project_user_roles', 'projects.id', '=', 'project_user_roles.project_id')
                ->join('users', 'project_user_roles.user_id', '=', 'users.id')
                ->join('roles', 'project_user_roles.role_id', '=', 'roles.id')
                ->whereNull('project_user_roles.deleted_at')
                ->whereNull('project_user_roles.deleted_at')
                ->whereNull('users.deleted_at')
                ->whereNull('roles.deleted_at')
                ->where('users.id', Auth::user()->id)
                ->where('projects.id', $validated['project_id'])
                ->select(
                    'users.id as user_id',
                    'roles.name as role_name',
                    'roles.id as role_id'
                )
                ->first();

            if ($projectUserRole->role_name != 'Project Manager') {
                return Response::failed(null, 'User does not have permission to edit this project');
            }

            // Check if the project exist
            $project = Project::find($validated['project_id']);
            if (!$project) {
                return Response::failed(null, 'Project not found');
            }

            // Check if the role exist
            $role = Role::find($validated['role_id']);
            if (!$role) {
                return Response::failed(null, 'Role not found'); 
            }

            // Check if the user exist 
            $user = User::whereNull('deleted_at')->where('id', $validated['user_id'])->first();
            if (!$user) {
                return Response::failed(null, "User not found");
            }

            // Check if the user already in project or not
            if (ProjectUserRole::where('user_id', $user->id)->where('project_id', $validated['project_id'])->first()) {
                return Response::failed(null, "User already in project");
            }

            // Adding user to project
            ProjectUserRole::create($validated);
            $user['project_name'] = $project->name;
            $user['role'] = $role->name;
            return Response::success($user, 'Member added successfully');

        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();
        }
    }

    public function editMember(Request $request) 
    {
        try {
            $validate = Validator::make($request->all(), [
                'user_id' => 'required|numeric',
                'role_id' => 'required|numeric',
                'project_id' => 'required|numeric'
            ]);

            if ($validate->fails()) {
                return Response::failed($validate->errors(), 'Validation error');
            }

            $validated = $validate->validated();

            // Check user role in project
            $projectUserRole = Project::join('project_user_roles', 'projects.id', '=', 'project_user_roles.project_id')
                ->join('users', 'project_user_roles.user_id', '=', 'users.id')
                ->join('roles', 'project_user_roles.role_id', '=', 'roles.id')
                ->whereNull('project_user_roles.deleted_at')
                ->whereNull('users.deleted_at')
                ->whereNull('roles.deleted_at')
                ->where('users.id', Auth::user()->id)
                ->where('projects.id', $validated['project_id'])
                ->select(
                    'users.id as user_id',
                    'roles.name as role_name',
                    'roles.id as role_id'
                )
                ->first();

            if ($projectUserRole->role_name != 'Project Manager') {
                return Response::failed(null, 'User does not have permission to edit this project');
            }

            // Check if the project exist
            $project = Project::find($validated['project_id']);
            if (!$project) {
                return Response::failed(null, 'Project not found');
            }

            // Check if the role exist
            $role = Role::find($validated['role_id']);
            if (!$role) {
                return Response::failed(null, 'Role not found'); 
            }

            // Check if the user exist 
            $user = User::whereNull('deleted_at')->where('id', $validated['user_id'])->first();
            if (!$user) {
                return Response::failed(null, "User not found");
            }

            // Check user in project
            $userProject = ProjectUserRole::where('project_id', $validated['project_id'])->where('user_id', $validated['user_id'])->first();
            if (!$userProject) {
                return Response::failed(null, 'User not in project');
            }

            $userProject->role_id = (int) $validated['role_id'];
            $userProject->save();

            $url = url('storage');
            $data = ProjectUserRole::join('users', 'users.id', '=', 'project_user_roles.user_id')
                ->join('projects', 'projects.id', '=', 'project_user_roles.project_id')
                ->join('roles', 'roles.id', '=', 'project_user_roles.role_id')
                ->whereNull('users.deleted_at')
                ->whereNull('projects.deleted_at')
                ->whereNull('roles.deleted_at')
                ->where('projects.id', $validated['project_id'])
                ->where('users.id', $validated['user_id'])
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    'users.email as user_email',
                    DB::raw("CASE WHEN users.image IS NOT NULL THEN CONCAT('$url', '/', users.image) ELSE null END AS user_image"),
                    'projects.id as project_id',
                    'projects.name as project_name',
                    'roles.id as role_id',
                    'roles.name as role_name'
                )
                ->first();

            return Response::success($data, 'Member updated successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();
        }
    }

    public function removeMember(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
            'project_id' => 'required|numeric'
        ]);

        if ($validate->fails()) {
            return Response::failed($validate->errors(), 'Validation error');
        }

        $validated = $validate->validated();

        // Check user role in project
        $projectUserRole = Project::join('project_user_roles', 'projects.id', '=', 'project_user_roles.project_id')
            ->join('users', 'project_user_roles.user_id', '=', 'users.id')
            ->join('roles', 'project_user_roles.role_id', '=', 'roles.id')
            ->whereNull('project_user_roles.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereNull('roles.deleted_at')
            ->where('users.id', Auth::user()->id)
            ->where('projects.id', $validated['project_id'])
            ->select(
                'users.id as user_id',
                'roles.name as role_name',
                'roles.id as role_id'
            )
            ->first();

        if ($projectUserRole->role_name != 'Project Manager') {
            return Response::failed(null, 'User does not have permission to edit this project');
        }

        // Check if the project exist
        $project = Project::find($validated['project_id']);
        if (!$project) {
            return Response::failed(null, 'Project not found');
        }

        // Check if the user exist 
        $user = User::whereNull('deleted_at')->where('id', $validated['user_id'])->first();
        if (!$user) {
            return Response::failed(null, "User not found");
        }

        // Check user in project
        $userProject = ProjectUserRole::where('project_id', $validated['project_id'])->where('user_id', $validated['user_id'])->first();
        if (!$userProject) {
            return Response::failed(null, 'User not in project');
        }

        $userProject = ProjectUserRole::where('user_id', $validated['user_id'])->where('project_id', $validated['project_id'])->first();
        $userProject->delete();

        return Response::success(null, 'Member removed successfully');
    }
}
