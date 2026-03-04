<?php

use App\Http\Controllers\Auth\TokenController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard/Index');
})->middleware(['auth', 'verified'])->name('dashboard');

/**
 * Issues a Sanctum personal access token for the currently authenticated
 * session user. Called from the front-end right after Fortify login so
 * axios can attach a Bearer token for subsequent API requests.
 */
Route::post('auth/token/session', [TokenController::class, 'issueForSession'])
    ->middleware(['auth'])
    ->name('auth.token.session');

// Task routes (authenticated + verified)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tasks', TaskController::class)->except(['show']);
    Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggleComplete'])
        ->name('tasks.toggle');
});

require __DIR__.'/settings.php';
