<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TestApiController;
use App\Http\Controllers\{PlanController};
use App\Helpers\BncHelper;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::post('pay-fee', [TestApiController::class, 'testApi'])->name('pay-fee');

//plans
Route::resource('plans', PlanController::class);

Route::get('/api/bcv', function () {
    return response()->json(BncHelper::getBcvRatesCached());
});



Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
