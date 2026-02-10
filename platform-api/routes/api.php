<?php

use App\Http\Controllers\DraftController;
use App\Http\Controllers\PreviewSessionController;
use App\Http\Controllers\Public\LeadController;
use App\Http\Controllers\Public\PropertyController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Private API Routes (auth stubbed for MVP)
|--------------------------------------------------------------------------
*/

Route::get('/themes', [ThemeController::class, 'index']);

Route::post('/sites', [SiteController::class, 'store']);
Route::get('/sites/{id}', [SiteController::class, 'show']);

Route::get('/sites/{id}/draft', [DraftController::class, 'show']);
Route::put('/sites/{id}/draft', [DraftController::class, 'update']);

Route::post('/sites/{id}/preview-session', [PreviewSessionController::class, 'store']);
Route::post('/sites/{id}/publish', [PublishController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Public API Routes (called from microsite JS)
|--------------------------------------------------------------------------
*/

Route::post('/public/leads', [LeadController::class, 'store']);
Route::get('/public/properties', [PropertyController::class, 'index']);
Route::get('/public/properties/{id}', [PropertyController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Internal API Routes (called from Nuxt theme servers)
|--------------------------------------------------------------------------
*/

Route::get('/internal/site-payload/{siteId}', [\App\Http\Controllers\Internal\SiteResolveController::class, 'payload']);
