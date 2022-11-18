<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientProjectsController;
use App\Http\Controllers\ClientModulesController;
use App\Http\Controllers\ModuleBugs;
use App\Http\Controllers\BugAttachmentsController;
use App\Http\Controllers\ClientEnvironmentsController;
use App\Http\Controllers\GeneratePdf;

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
 * middleware('client') - ensures user has a valied registration key.
 * 'client' middleware can be modified at: app\Http\Middleware\ValidateRegKey.php
 */

// Project.
Route::middleware('client')->get('/project', [ClientProjectsController::class, 'getProject']);
Route::middleware('client')->post('/project', [ClientProjectsController::class, 'postProject']);
Route::middleware('client')->patch('/project', [ClientProjectsController::class, 'patchProject']);
Route::middleware('client')->delete('/project', [ClientProjectsController::class, 'deleteProject']);
Route::middleware('client')->patch('/project-status', [ClientProjectsController::class, 'patchProjectStatus']);


// Module.
Route::middleware('client')->post('/module', [ClientModulesController::class, 'postModule']);
Route::middleware('client')->get('/module', [ClientModulesController::class, 'getModule']);
Route::middleware('client')->patch('/module', [ClientModulesController::class, 'patchModule']);
Route::middleware('client')->delete('/module', [ClientModulesController::class, 'deleteModule']);

// Bug.
Route::middleware('client')->post('/bug', [ModuleBugs::class, 'postBug']);
Route::middleware('client')->get('/bug', [ModuleBugs::class, 'getBugList']);
Route::middleware('client')->get('/bug-details', [ModuleBugs::class, 'getBugDetails']);
Route::middleware('client')->get('/bug-global-search', [ModuleBugs::class, 'getGlobalSearch']);
Route::middleware('client')->get('/bug-status-list', [ModuleBugs::class, 'getBugStatusList']);
Route::middleware('client')->patch('/bug', [ModuleBugs::class, 'patchBug']);
Route::middleware('client')->patch('/bug-status', [ModuleBugs::class, 'patchBugStatus']);
Route::middleware('client')->patch('/bug-screenshot', [ModuleBugs::class, 'patchBugScreenshot']);


// Environment.
Route::middleware('client')->post('/environment', [ClientEnvironmentsController::class, 'postEnvironment']);
Route::middleware('client')->get('/environment', [ClientEnvironmentsController::class, 'getEnvironment']);
Route::middleware('client')->delete('/environment', [ClientEnvironmentsController::class, 'deleteEnvironment']);
Route::middleware('client')->patch('/environment', [ClientEnvironmentsController::class, 'patchEnvironment']);

// Attachment.
Route::middleware('client')->post('/attachment', [BugAttachmentsController::class, 'postAttachment']);
Route::middleware('client')->delete('/temp_attachment', [BugAttachmentsController::class, 'deleteTempAttachment']);
Route::middleware('client')->delete('/attachment', [BugAttachmentsController::class, 'deleteBugAttachment']);

// Generate PDF.
Route::post('/generate_pdf', [GeneratePdf::class, 'generatePdf']);

// Get screenshot as blob.
Route::middleware('client')->get('/screenshot-blob', [ModuleBugs::class, 'getScreenshotAsBlob']);

// TEST
Route::middleware('client')->post('/jira-issue', [ModuleBugs::class, 'createJiraIssue']);




