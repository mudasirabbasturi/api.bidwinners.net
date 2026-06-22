<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DirectChatController;
use App\Http\Controllers\ProjectChatController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ClientController;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\EmailController;

// PUBLIC
Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => function ($request, $next) {
    $token = $request->bearerToken();
    if (!$token) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    $accessToken = PersonalAccessToken::findToken($token);
    if (!$accessToken) {
        return response()->json(['message' => 'Invalid token'], 401);
    }
    $user = $accessToken->tokenable;
    if (!$user) {
        return response()->json(['message' => 'Invalid token'], 401);
    }
    auth()->setUser($user);
    return $next($request);
}], function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/chat-project-list', [ProjectChatController::class, 'chatProjectList']);
    Route::get('/chat-project-messages/{project_id}', [ProjectChatController::class, 'getProjectChatMessages']);
    Route::post('/chat-project-send-message', [ProjectChatController::class, 'sendProjectMessage']);
    Route::delete('/chat-project-message/{id}', [ProjectChatController::class, 'deleteProjectMessage']);

    Route::get('/chat-user-list', [DirectChatController::class, 'chatUserList']);
    Route::get('/direct-chat-messages/{receiver_id}', [DirectChatController::class, 'getDirectMessages']);
    Route::post('/direct-chat-send-message', [DirectChatController::class, 'sendDirectMessage']);
    Route::delete('/direct-chat-message/{id}', [DirectChatController::class, 'deleteDirectMessage']);
    
    Route::get('/projects', [ProjectController::class, 'Index']);
    Route::post('/project/create', [ProjectController::class, 'Create']);
    Route::get('/project-view/{id}', [ProjectController::class, 'View']);
    Route::get('/project-edit/{id}', [ProjectController::class, 'Edit']);
    Route::put('/project-update/{id}', [ProjectController::class, 'Update']);
    Route::get('project/column/{id}', [ProjectController::class, 'Column']);
    Route::put('/project/column/update/{id}', [ProjectController::class, 'ColumnUpdate']);
    Route::post('/project/team-member/join/{ProjectId}', [ProjectController::class, 'JoinProject']);
    Route::put('/project/team-member/update/{TeamMemberId}', [ProjectController::class, 'EditJoinProject']);
    Route::delete('/project/team-member/delete/{TeamMemberId}', [ProjectController::class, 'DeleteJoinProject']);
    
    Route::get('/client', [ClientController::class, 'Index']);

    Route::get('/permissions',  [PermissionController::class, 'Index'])->name('permission.index');
    Route::get('/user-permissions', [PermissionController::class, 'UserPermissions'])->name('UserPermissions');
});

Route::post('/send-contact-email', [EmailController::class, 'sendEmail']);
