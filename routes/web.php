<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('admin/users', [UserController::class, 'index'])
        ->middleware('permission:view_users')
        ->name('admin.users.index');

    Route::get('admin/users/create', [UserController::class, 'create'])
        ->middleware('permission:create_users')
        ->name('admin.users.create');

    Route::post('admin/users', [UserController::class, 'store'])
        ->middleware('permission:create_users')
        ->name('admin.users.store');

    Route::get('admin/users/{user}', [UserController::class, 'show'])
        ->middleware('permission:view_users')
        ->name('admin.users.show');

    Route::get('admin/users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:edit_users')
        ->name('admin.users.edit');

    Route::match(['put', 'patch'], 'admin/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:edit_users')
        ->name('admin.users.update');

    Route::delete('admin/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:delete_users')
        ->name('admin.users.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
