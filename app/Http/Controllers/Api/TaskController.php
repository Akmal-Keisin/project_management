<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Role;
use App\Models\ProjectUserRole;
use App\Models\TaskDocument;
use App\Models\User;
use App\Models\Task;
use App\Models\SubmitTask;
use App\Helpers\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function myTask()
    {
        try {
            $tasks = Task::join('projects', 'projects.id', '=', 'tasks.project_id')
                ->join('users', 'users.id', '=', 'tasks.task_from_id')
                ->where('user_id', Auth::user()->id)
                ->whereNull('tasks.deleted_at')
                ->whereNotIn('tasks.status', ['Done', 'Late'])
                ->select(
                    'tasks.id',
                    'projects.name as project_name',
                    'users.name as task_from',
                    'tasks.name as task_title',
                    'tasks.description as task_description',
                    'tasks.date_start',
                    'tasks.deadline',
                    'tasks.status'
                )
                ->get();

            return Response::success($tasks, 'Task obtained successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();    
        }
    }   

    public function addTask(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                    'user_id' => 'required|numeric',
                    'project_id' => 'required|numeric',
                    'task_title' => 'required|max:255',
                    'status' => [
                        'nullable',
                         Rule::in(['Prepare', 'Process', 'Done', 'Late'])
                     ],
                    'task_description' => 'required',
                    'date_start' => 'required|date_format:Y-m-d',
                    'deadline' => 'required|date_format:Y-m-d'
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

            if ($projectUserRole->role_name != 'Project Manager' && $projectUserRole->role_name != 'Leader') {
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

            $task = Task::create([
                'project_id' => $validated['project_id'],
                'user_id' => $validated['user_id'],
                'task_from_id' => Auth::user()->id,
                'name' => $validated['task_title'],
                'description' => $validated['task_description'],
                'date_start' => $validated['date_start'],
                'deadline' => $validated['deadline'],
                'status' => 'Prepare'
            ]);

            return Response::success($task, 'Task created successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();            
        }
    }

    public function editTask(Request $request, $id)
    {
        try {
            
            $validate = Validator::make($request->all(), [
                    'user_id' => 'required|numeric',
                    'project_id' => 'required|numeric',
                    'task_title' => 'required|max:255',
                    'status' => [
                        'nullable',
                         Rule::in(['Prepare', 'Process', 'Done', 'Late'])
                     ],
                    'task_description' => 'required',
                    'date_start' => 'required|date_format:Y-m-d',
                    'deadline' => 'required|date_format:Y-m-d'
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

            if ($projectUserRole->role_name != 'Project Manager' && $projectUserRole->role_name != 'Leader') {
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

            // Check task 
            $task = Task::find($id);
            if (!$task) {
                return Response::failed(null, 'Task not found');
            }

            $validated['date_end'] = (isset($validated['date_end'])) ? $validated['date_end'] : $task->date_end;
            $validated['status'] = (isset($validated['status'])) ? $validated['status'] : $task->status;

            $task->update([
                'project_id' => $validated['project_id'],
                'user_id' => $validated['user_id'],
                'task_from_id' => Auth::user()->id,
                'name' => $validated['task_title'],
                'description' => $validated['task_description'],
                'date_start' => $validated['date_start'],
                'deadline' => $validated['deadline'],
                'date_end' => (is_null($validated['date_end']) && ($validated['status'] != 'Prepare' || $validated['status'] != 'Progress')) ? date('Y-m-d') : $validated['date_end'],
                'status' => $validated['status']
            ]);
            return Response::success($task, 'Task updated successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();
        }
    }

    public function removeTask(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'user_id' => 'required|numeric',
                'project_id' => 'required|numeric',
                'task_id' => 'required|numeric'
            ]);

            if ($validate->fails()) {
                return Response::failed($validate->errors());
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

            if ($projectUserRole->role_name != 'Project Manager' && $projectUserRole->role_name != 'Leader') {
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

            // Check task 
            $task = Task::find($validated['task_id']);
            if (!$task) {
                return Response::failed(null, 'Task not found');
            }

            $task->delete();
            return Response::success(null, 'Task removed successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();   
        }
    }

    public function setStatusTask($id)
    {
        try {
            $task = Task::find($id);
            if (!$task) {
                return Response::failed(null, 'Task not found');
            }

            if ($task->user_id != Auth::user()->id) {
                return Response::failed(null, 'User does not have permission to edit this task');
            }

            if ($task->status == 'Done' && $task->status == 'Late') {
                return Response::failed(null, 'This task has been submitted');
            }

            $task->status = ($task->status == 'Prepare') ? 'Progress' : 'Prepare';
            $task->save();

            return Response::success($task, 'Task status updated successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();            
        }
    }
}
