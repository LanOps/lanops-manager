<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

// Served under /api with the api middleware group (see bootstrap/app.php)
Route::get('/events/', [Api\Events\EventsController::class, 'index']);
Route::get('/events/upcoming', [Api\Events\EventsController::class, 'showUpcoming']);
Route::get('/events/next', [Api\Events\EventsController::class, 'showNext']);
Route::get('/events/{event}', [Api\Events\EventsController::class, 'show']);
Route::get('/events/{event}/timetables', [Api\Events\TimetablesController::class, 'index']);
Route::get('/events/{event}/timetables/default', [Api\Events\TimetablesController::class, 'showPrimary']);
Route::get('/events/{event}/timetables/{timetable}', [Api\Events\TimetablesController::class, 'show']);
