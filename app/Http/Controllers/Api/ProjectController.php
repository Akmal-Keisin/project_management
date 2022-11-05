<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectUserRole;
use App\Models\User;
use App\Models\Task;
use App\Helpers\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function myProject()
    {
        try {
            $url = url('storage');
            $projects = Project::join('project_user_roles', 'project_user_roles.project_id', '=', 'projects.id')
                ->join('roles', 'roles.id', '=', 'project_user_roles.role_id')
                ->join('users', 'users.id', '=', 'project_user_roles.user_id')
                ->join('users as created_by', 'created_by.id', 'projects.created_by')
                ->whereNull('projects.deleted_at')
                ->whereNull('users.deleted_at')
                ->whereNull('roles.deleted_at')
                ->whereNotIn('status', ['Done', 'Late'])
                ->where('users.id', Auth::user()->id)
                ->select(
                    'projects.id',
                    'projects.name as project_name',
                    'projects.description as project_description',
                    DB::raw("CONCAT('$url', '/', projects.image) as project_image"),
                    'projects.date_start',
                    'projects.deadline',
                    'created_by.name as created_by',
                    'roles.name as my_role'
                )
                ->get();
            // dd($projects);
            return Response::success($projects, 'Projects obtained successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error($e);
        }
    }

    public function addProject(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'project_name' => 'required|max:255',
                'project_description' => 'required',
                'project_image' => 'required|image',
                'date_start' => 'required|date_format:Y-m-d',
                'deadline' => 'required|date_format:Y-m-d',
            ]);

            if ($validate->fails()) {
                return Response::failed($validate->errors(), 'Validation error');
            }

            $validated = $validate->validated();
            $validated['project_image'] = $request->file('project_image')->store('project_image');
            $validated['status'] = 'Prepare';
            $validated['created_by'] = Auth::user()->id;

            $projectTransaction = DB::transaction(function() use ($validated) {

                $project = new Project();
                $project->name = $validated['project_name'];
                $project->description = $validated['project_description'];
                $project->image = $validated['project_image'];
                $project->status = $validated['status'];
                $project->created_by = $validated['created_by'];
                $project->deadline = $validated['deadline'];
                $project->date_start = $validated['date_start'];
                $project->save();

                ProjectUserRole::create([
                    'user_id' => Auth::user()->id,
                    'project_id' => $project->id,
                    'role_id' => 1
                ]);
                return $project;
            });

            $validated['project_image'] = url('storage/public/' . $validated['project_image']);
            $validated['id'] = $projectTransaction->id; 
            $validated['my_role'] = 'Project Manager';
            return Response::success($validated, 'Project created successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();            
        }
    }

    public function editProject(Request $request, $id)
    {
        try {
             $validate = Validator::make($request->all(), [
                    'name' => 'required|max:255',
                    'description' => 'required',
                    'image' => 'nullable|image',
                    'date_start' => 'required|date_format:Y-m-d',
                    'deadline' => 'required|date_format:Y-m-d',
                    'date_end' => 'nullable|date_format:Y-m-d',
                    'status' => ['required', Rule::in(['Prepare', 'Progress', 'Late', 'Done']
                )]
            ]);

            if ($validate->fails()) {
                return Response::failed($validate->errors(), 'Validation error');
            }

            // Find project
            $project = Project::find($id);
            if (!$project) {
                return Response::failed(null, 'Project not found');
            }

            // Check user role in project
            $projectUserRole = Project::join('project_user_roles', 'projects.id', '=', 'project_user_roles.project_id')
                ->join('users', 'project_user_roles.user_id', '=', 'users.id')
                ->join('roles', 'project_user_roles.role_id', '=', 'roles.id')
                ->where('users.id', Auth::user()->id)
                ->where('projects.id', $id)
                ->select(
                    'users.id as user_id',
                    'roles.name as role_name',
                    'roles.id as role_id'
                )
                ->first();

            if ($projectUserRole->role_name != 'Project Manager') {
                return Response::failed(null, 'User does not have permission to edit this project');
            }

            $validated = $validate->validated();

            // Check image
            if ($request->file('image')) {
                Storage::delete($project->image);
                $validated['image'] = $request->file('image')->store('project_image');
            } else {
                $validated['image'] = $project->image;
            }

            $project->update($validated);
            $project->image = url('storage/public/' . $project->image);
            return Response::success($project, 'Project updated successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();
        }
    }

    public function detailProject($id)
    {
        try {
            $url = url('storage');
            $project = Project::join('project_user_roles', 'projects.id', '=', 'project_user_roles.project_id')
                ->join('users as created_by', 'created_by.id', '=', 'projects.created_by')
                ->join('users', 'project_user_roles.user_id', '=', 'users.id')
                ->join('roles', 'project_user_roles.role_id', '=', 'roles.id')
                ->where('projects.id', $id)
                ->select(
                    'projects.id as project_id',
                    'projects.name as project_name',
                    'projects.date_start',
                    DB::raw("CONCAT('$url', '/', projects.image) as project_image"),
                    'projects.date_end',
                    'projects.deadline',
                    'projects.description',
                    'projects.status',
                    'created_by.name as created_by',
                    'created_by.id as created_by_id'
                )
                ->first();
            if (!$project) {
                return Response::failed(null, 'Project not found');
            }

            $taskAll = Task::where('project_id', $id)->count();
            $taskDone = Task::where('project_id', $id)->whereNotNull('date_end')->count();

            if ($taskAll != 0 && $taskDone != 0) {
                $project['progress'] = ($taskDone / $taskAll * 100) . '%';
            } else {
                $project['progress'] = 0 . '%';
            }

            return Response::success($project, 'Project obtained successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();
        }
    }

    public function removeProject($id)
    {
        try {
            // Check user role in project
            $projectUserRole = Project::join('project_user_roles', 'projects.id', '=', 'project_user_roles.project_id')
                ->join('users', 'project_user_roles.user_id', '=', 'users.id')
                ->join('roles', 'project_user_roles.role_id', '=', 'roles.id')
                ->where('users.id', Auth::user()->id)
                ->where('projects.id', $id)
                ->select(
                    'users.id as user_id',
                    'roles.name as role_name',
                    'roles.id as role_id'
                )
                ->first();

            if ($projectUserRole->role_name != 'Project Manager') {
                return Response::failed(null, 'User does not have permission to edit this project');
            }

            $project = Project::whereNull('deleted_at')->where('id', $id)->first();
            if ($project) {
                $project->delete();
                return Response::success(null, 'Project deleted successfully');
            }
            return Response::failed(null, 'Project not found');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();
        }
    }

    public function setStatusProject($id)
    {
        try {
            // Check user role in project
            $projectUserRole = Project::join('project_user_roles', 'projects.id', '=', 'project_user_roles.project_id')
                ->join('users', 'project_user_roles.user_id', '=', 'users.id')
                ->join('roles', 'project_user_roles.role_id', '=', 'roles.id')
                ->where('users.id', Auth::user()->id)
                ->where('projects.id', $id)
                ->select(
                    'users.id as user_id',
                    'roles.name as role_name',
                    'roles.id as role_id'
                )
                ->first();

            if ($projectUserRole->role_name != 'Project Manager') {
                return Response::failed(null, 'User does not have permission to edit this project');
            }

            $project = Project::find($id);
            if (!$project) {
                return Response::failed(null, 'Project not found');
            }

            if ($project->status == 'Done' && $project->status == 'Late') {
                return Response::failed(null, 'This status already done');
            }

            $project->status = ($project->status == 'Prepare') ? 'Progress' : 'Prepare';
            $project->save();

            return Response::success($project, 'Project status updated');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();            
        }

    }
}
