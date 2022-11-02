<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Role;
use App\Models\ProjectUserRole;
use App\Models\User;
use App\Helpers\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function getRole() 
    {
        try {
            $role = Role::all();
            return Response::success($role, 'Role obtained successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            return Response::error();
        }
    }
}
