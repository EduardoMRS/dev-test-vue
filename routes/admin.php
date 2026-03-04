<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // ─── Users ───────────────────────────────────────────────────────────────
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Special admin actions
    Route::post('users/{user}/send-password-reset', [UserController::class, 'sendPasswordReset'])
        ->name('users.send-password-reset');

    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->name('users.reset-password');

    Route::post('users/{user}/disable-two-factor', [UserController::class, 'disableTwoFactor'])
        ->name('users.disable-two-factor');

    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->name('users.toggle-active');
});
