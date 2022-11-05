<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controller
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ProjectMemberController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('auth/register', [AuthController::class, 'authRegister']);
Route::post('auth/login', [AuthController::class, 'authLogin']);

Route::middleware('auth:sanctum')->group(function() {
    Route::post('auth/logout', [AuthController::class, 'authLogout']);

    // Profile
    Route::get('profile/get-profile', [ProfileController::class, 'getProfile']);
    Route::post('profile/edit-profile', [ProfileController::class, 'editProfile']);

    // Project
    Route::get('project/my-project', [ProjectController::class, 'myProject']);
    Route::post('project/add-project', [ProjectController::class, 'addProject']);
    Route::post('project/edit-project/{id}', [ProjectController::class, 'editProject']);
    Route::get('project/detail-project/{id}', [ProjectController::class, 'detailProject']);
    Route::post('project/remove-project/{id}', [ProjectController::class, 'removeProject']);
    Route::post('project/set-status/{id}', [ProjectController::class, 'setStatusProject']);

    // Role
    Route::get('role/get-role', [RoleController::class, 'getRole']);

    // Project member
    Route::get('project-member/list-member/{id}', [ProjectMemberController::class, 'listMember']);
    Route::post('project-member/detail-member', [ProjectMemberController::class, 'detailMember']);
    Route::post('project-member/add-member', [ProjectMemberController::class, 'addMember']);
    Route::post('project-member/edit-member', [ProjectMemberController::class, 'editMember']);
    Route::post('project-member/remove-member', [ProjectMemberController::class, 'removeMember']);

    // Project Task
    Route::get('project-task/my-task', [TaskController::class, 'myTask']);
    Route::post('project-task/add-task', [TaskController::class, 'addTask']);
    Route::post('project-task/edit-task/{id}', [TaskController::class, 'editTask']);
    Route::post('project-task/remove-task', [TaskController::class, 'removeTask']);
    Route::post('project-task/set-status/{id}', [TaskController::class, 'setStatusTask']);
    Route::post('project-task/submit-task', [TaskController::class, 'submitTask']);
});