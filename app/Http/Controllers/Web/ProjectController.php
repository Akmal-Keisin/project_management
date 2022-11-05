<?php

namespace App\Http\Controllers\Web;

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
    public function index()
    {
        try {
            $url = url('storage/public');
            $projects = Project::join('project_user_roles', 'project_user_roles.project_id', '=', 'projects.id')
                ->join('roles', 'roles.id', '=', 'project_user_roles.role_id')
                ->join('users', 'users.id', '=', 'project_user_roles.user_id')
                ->join('users as created_by', 'created_by.id', 'projects.created_by')
                ->whereNull('projects.deleted_at')
                ->whereNull('users.deleted_at')
                ->whereNull('roles.deleted_at')
                ->when(request('search'), function($query) {
                    $query->where('projects.name', 'LIKE', '%' . request('search') . '%');
                })
                ->select(
                    'projects.id',
                    'projects.name as project_name',
                    'projects.description as project_description',
                    'projects.status',
                    'created_by.name as created_by',
                    'created_by.email as created_by_email',
                    'roles.name as my_role'
                )
                ->get();
            $totalProject = Project::whereNull('deleted_at')->count();
            $totalProjectDone = Project::whereNull('deleted_at')->whereNotNull('date_end')->count();
        return view('project.index', [
            'projects' => $projects,
            'total_projects' => $totalProject,
            'total_projects_done' => $totalProjectDone
        ]);
        } catch (\Throwable $e) {
            Log::error($e);
        }
    }

    public function create()
    {
        try {
            return view('project.create');
        } catch (\Throwable $e) {
            Log::error($e);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'required',
            'image' => 'required|image',
            'date_start' => 'required',
            'deadline' => 'required',
            'project_manager' => 'required|numeric',
        ]);

        $user = User::find($validated['project_manager']);
        if (!$user) {
            return redirect()->back()->with('failed', 'User for project manager not found');
        }

        DB::transaction(function() use ($validated, $request) {
            $project = Project::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'image' => $request->file('image')->store('project_image'),
                'date_start' => $validated['date_start'],
                'deadline' => $validated['deadline'],
                'created_by' => $validated['project_manager'],
                'status' => 'Prepare',
            ]);

            ProjectUserRole::create([
                'user_id' => $validated['project_manager'],
                'role_id' => 1,
                'project_id' => $project->id
            ]);

        });

        return redirect('project')->with('success', 'Project created successfully');
    }

    public function edit($id)
    {
        $project = Project::find($id);
        return view('project.edit', [
            'project' => $project
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'image' => 'nullable|image',
            'date_start' => 'required',
            'deadline' => 'required',
            'project_manager' => 'required'
        ]);

        $project = Project::find($id);
        $project->name = $validated['name'];
        $project->description = $validated['description'];
        $project->date_start = $validated['date_start'];
        $project->deadline = $validated['deadline'];

        $project_manager = User::find($validated['project_manager']);
        if (!$project_manager) {
            return redirect()->back()->with('failed', 'User for project manager not found');
        }
        $project->created_by = $validated['project_manager'];

        if ($request->file('image')) {
            Storage::delete($project->image);
            $project->image = $request->file('image')->store('project_image');
        }

        $project->save();

        return redirect('project')->with('success', 'Project updated successfully');
    }

    public function destroy($id)
    {
        $project = Project::find($id);
        Storage::delete($project->image);
        $project->delete();

        return redirect()->back()->with('success', 'Project deleted successfully');
    }
}
