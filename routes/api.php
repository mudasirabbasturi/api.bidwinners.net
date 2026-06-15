<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DirectChatController;
use App\Http\Controllers\ProjectChatController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ClientController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {return $request->user();});
    Route::post('/logout', [AuthController::class, 'logout']);
      
    /*
    |--------------------------------------------------------------------------
    | Project-Chat Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/chat-project-list', [ProjectChatController::class, 'chatProjectList'])->name('chat.project.list');
    Route::get('/chat-project-messages/{project_id}', [ProjectChatController::class, 'getProjectChatMessages'])->name('chat.project.messages');
    Route::post('/chat-project-send-message', [ProjectChatController::class, 'sendProjectMessage'])->name('chat.project.send.message');
    Route::delete('/chat-project-message/{id}', [ProjectChatController::class, 'deleteProjectMessage'])->name('chat.project.message.delete');

    /*
    |--------------------------------------------------------------------------
    | Direct-Chat Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/chat-user-list', [DirectChatController::class, 'chatUserList'])->name('chat.user.list');
    Route::get('/direct-chat-messages/{receiver_id}', [DirectChatController::class, 'getDirectMessages'])->name('direct.chat.messages');
    Route::post('/direct-chat-send-message', [DirectChatController::class, 'sendDirectMessage'])->name('direct.chat.send.message');
    Route::delete('/direct-chat-message/{id}', [DirectChatController::class, 'deleteDirectMessage'])->name('direct.chat.message.delete');

    /*
    |--------------------------------------------------------------------------
    | Project Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/projects', [ProjectController::class, 'Index'])->name('project.index');
    Route::post('/project/create', [ProjectController::class, 'Create'])->name('project.create');
    Route::get('/project-view/{id}', [ProjectController::class, 'View'])->name('project.view');
    Route::get('/project-edit/{id}', [ProjectController::class, 'Edit'])->name('project.edit');
    Route::put('/project-update/{id}', [ProjectController::class, 'Update'])->name('project.update');
    Route::get('project/column/{id}', [ProjectController::class, 'Column'])->name('project.column');
    Route::put('/project/column/update/{id}', [ProjectController::class, 'ColumnUpdate'])->name('project.column.update');
    Route::post('/project/team-member/join/{ProjectId}', [ProjectController::class, 'JoinProject'])->name('JoinProject');
    Route::put('/project/team-member/update/{TeamMemberId}', [ProjectController::class, 'EditJoinProject'])->name('EditJoinProject');
    Route::delete('/project/team-member/delete/{TeamMemberId}', [ProjectController::class, 'DeleteJoinProject'])->name('DeleteJoinProject');
    
    /*
    |--------------------------------------------------------------------------
    | Client Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/client', [ClientController::class, 'Index'])->name('client.index');


    Route::get('/user-permissions', [ProjectChatController::class, 'getUserPermissions']);

});

