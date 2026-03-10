<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/history', [ChatController::class, 'index']);
Route::post('/history', [ChatController::class, 'store']);
Route::delete('/history', [ChatController::class, 'destroy']);
Route::post('/chat', [ChatController::class, 'chat']);
