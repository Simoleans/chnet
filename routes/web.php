<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TestApiController;
use App\Http\Controllers\{PlanController,UserController,ZoneController,PaymentController};
use App\Helpers\BncHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return Inertia::render('auth/Login');
})->name('home');

Route::post('pay-fee', [TestApiController::class, 'testApi'])->name('pay-fee');

//plans
Route::resource('plans', PlanController::class);

//users
Route::resource('users', UserController::class);

//zones
Route::resource('zones', ZoneController::class);

//payments
Route::resource('payments', PaymentController::class)->middleware('auth');


Route::get('/api/bcv', function () {
    return response()->json(BncHelper::getBcvRatesCached());
});



Route::get('/api/bnc/history', function (Request $request) {
    $account = $request->query('account');

    if (!$account) {
        return response()->json([
            'success' => false,
            'error' => 'Falta el número de cuenta',
            'data' => null,
        ], 422);
    }

    try {
        $data = BncHelper::getPositionHistory($account);

        return response()->json([
            'success' => true,
            'error' => null,
            'data' => $data,
        ]);
    } catch (\Throwable $e) {
        Log::error('BNC HISTORY ❌ Error al consultar historial — ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'error' => 'Error al consultar el historial',
            'data' => null,
        ]);
    }
});





Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
