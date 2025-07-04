<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TestApiController;
use App\Http\Controllers\{PlanController,UserController,ZoneController,PaymentController};
use App\Helpers\BncHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
/* use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
 */
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

// Ruta especial para pago rápido desde login (sin autenticación)
Route::post('/quick-payment', [PaymentController::class, 'store'])->name('quick-payment.store');


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

Route::get('/api/bnc/validate-reference/{reference}', [App\Http\Controllers\PaymentController::class, 'validateReference'])->middleware(['auth']);

Route::get('/api/users/search/{code}', [App\Http\Controllers\UserController::class, 'searchByCode']);

Route::get('/api/banks', [App\Http\Controllers\PaymentController::class, 'getBanks']);

// Endpoint de prueba temporal para verificar errores
Route::get('/api/test-simple', function () {
    try {
        return response()->json([
            'success' => true,
            'message' => 'Endpoint de prueba funcionando',
            'timestamp' => now(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

// Prueba 1: Verificar BncLogger
Route::get('/api/test-logger', function () {
    try {
        \App\Helpers\BncLogger::info('Test desde endpoint');
        return response()->json(['success' => true, 'message' => 'BncLogger funciona']);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Error en BncLogger: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

// Prueba 2: Verificar BncHelper sin llamar métodos
Route::get('/api/test-helper-class', function () {
    try {
        $className = \App\Helpers\BncHelper::class;
        return response()->json(['success' => true, 'message' => 'BncHelper class exists', 'class' => $className]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Error loading BncHelper: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

// Prueba 3: Verificar dependencias una por una
Route::get('/api/test-dependencies', function () {
    try {
        $dependencies = [
            'ApiStatus' => class_exists('App\Models\ApiStatus'),
            'BncApiService' => class_exists('App\Services\BncApiService'),
            'BncCryptoHelper' => class_exists('App\Helpers\BncCryptoHelper'),
            'BncLogger' => class_exists('App\Helpers\BncLogger'),
            'phpseclib3_AES' => class_exists('phpseclib3\Crypt\AES'),
        ];

        return response()->json([
            'success' => true,
            'dependencies' => $dependencies,
            'missing' => array_filter($dependencies, fn($exists) => !$exists)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Error checking dependencies: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

// Prueba 4: Verificar configuraciones BNC
Route::get('/api/test-config', function () {
    try {
        $config = [
            'client_guid' => config('app.bnc.client_guid') ? 'SET' : 'NOT SET',
            'master_key' => config('app.bnc.master_key') ? 'SET' : 'NOT SET',
            'base_url' => config('app.bnc.base_url') ? 'SET' : 'NOT SET',
            'client_id' => config('app.bnc.client_id') ? 'SET' : 'NOT SET',
        ];

        return response()->json(['success' => true, 'config' => $config]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Error checking config: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

// Prueba 5: Verificar método getWorkingKey específicamente
Route::get('/api/test-working-key', function () {
    try {
        \App\Helpers\BncLogger::info('Probando getWorkingKey desde endpoint');
        $key = \App\Helpers\BncHelper::getWorkingKey();

        return response()->json([
            'success' => true,
            'key_exists' => !is_null($key),
            'key_length' => $key ? strlen($key) : 0
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Error en getWorkingKey: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

// Prueba 6: Diagnosticar BncLogger específicamente
Route::get('/api/test-bnc-logger-detail', function () {
    try {
        // Verificar si la clase existe
        if (!class_exists('App\Helpers\BncLogger')) {
            return response()->json(['success' => false, 'error' => 'BncLogger class not found']);
        }

        // Verificar si el canal 'bnc' está configurado
        $channels = config('logging.channels');
        if (!isset($channels['bnc'])) {
            return response()->json(['success' => false, 'error' => 'BNC logging channel not configured']);
        }

        // Verificar permisos del directorio de logs
        $logDir = storage_path('logs');
        if (!is_writable($logDir)) {
            return response()->json(['success' => false, 'error' => 'Logs directory not writable']);
        }

        // Intentar crear el archivo de log manualmente
        $logFile = storage_path('logs/bnc.log');
        if (!file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        if (!is_writable($logFile)) {
            return response()->json(['success' => false, 'error' => 'BNC log file not writable']);
        }

        // Intentar usar el canal directamente
        Log::channel('bnc')->info('Test directo del canal BNC');

        return response()->json([
            'success' => true,
            'message' => 'Canal BNC funciona directamente',
            'log_file_size' => filesize($logFile)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Error en diagnóstico BncLogger: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Prueba 7: Usar logging normal en lugar de BncLogger
Route::get('/api/test-without-bnc-logger', function () {
    try {
                // Usar Log normal en lugar de BncLogger
        Log::info('Test sin BncLogger - iniciando getBanks');

        $key = \App\Helpers\BncHelper::getWorkingKey();

        Log::info('Test sin BncLogger - working key obtenida', [
            'key_exists' => !is_null($key),
            'key_length' => $key ? strlen($key) : 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Funciona sin BncLogger',
            'key_exists' => !is_null($key),
            'key_length' => $key ? strlen($key) : 0
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Error sin BncLogger: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
