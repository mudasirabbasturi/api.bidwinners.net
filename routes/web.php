<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectChatController;
use App\Http\Controllers\DirectChatController;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/user-lists-to-chat', function () {
    $authId = 2;
    $users = DB::table('users')
        ->where('id', '!=', $authId)
        ->where('allow_direct_chat', 1)
        ->select('id', 'name')
        ->orderBy('name')
        ->get();

    return response()->json(['users' => $users]);
});
