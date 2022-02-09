<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientProjectsController;

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

/**
 * Gets project list based on client ID.
 * middleware('client') - ensures user has a valied registration key.
 * 'client' middleware can be modified at: app\Http\Middleware\ValidateRegKey.php
 */
Route::middleware('client')->get('/project', [ClientProjectsController::class, 'getProject']);
Route::middleware('client')->post('/project', [ClientProjectsController::class, 'postProject']);
Route::middleware('client')->patch('/project', [ClientProjectsController::class, 'patchProject']);