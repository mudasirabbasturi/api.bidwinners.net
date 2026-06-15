<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectChatController;
use App\Http\Controllers\DirectChatController;
Route::get('/', function () {
    return view('welcome');
});
 Route::get('/user-permissions', [ProjectChatController::class, 'getUserPermissions']);
 Route::get('/chat-project-list', [ProjectChatController::class, 'chatProjectList'])->name('chat.project.list');
 Route::get('/chat-user-list', [DirectChatController::class, 'chatUserList'])->name('chat.user.list');