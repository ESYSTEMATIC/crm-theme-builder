<?php

use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ServeController;
use Illuminate\Support\Facades\Route;

// Preview endpoint - must be before the catch-all
Route::get('/__preview', PreviewController::class);

// Catch-all route for serving microsite pages
Route::get('/{path?}', ServeController::class)->where('path', '.*');
