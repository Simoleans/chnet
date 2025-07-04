<?php

namespace App\Helpers;

use App\Models\ApiStatus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\BncApiService;
use App\Helpers\BncLogger;

class BncHelper
{
    public static function getWorkingKey(): ?string
    {
        $record = ApiStatus::where('api_name', 'bnc')
            ->whereDate('generated_at', now()->toDateString())
            ->latest()
            ->first();

        if (!$record) {
            Artisan::call('bnc:refresh-working-key');
            $record = ApiStatus::where('api_name', 'bnc')->latest()->first();
        }

        return $record?->working_key;
    }

    public static function getBcvRatesCached(): ?array
    {
        return Cache::remember('bnc_bcv_rate', now()->addMinutes(10), function () {
            // Intentar obtener la tasa desde el endpoint de pydolarve
            $primary = self::getBcvRateFromPydolarve();
            if ($primary) return $primary;

            // Si falla, intentar obtener la tasa desde el endpoint de bnc
            $fallback = self::getBcvRateFromBNC();
            if ($fallback) return $fallback;



            // Si ambos fallan, retornar null
            return null;
        });
    }

    private static function getBcvRateFromPydolarve(): ?array
    {
        try {
            $response = Http::timeout(3)->get('https://pydolarve.org/api/v1/dollar?page=bcv');

            if ($response->ok()) {
                $data = $response->json();

                if (isset($data['monitors']['usd']['price']) && isset($data['monitors']['usd']['last_update'])) {
                    return [
                        'Rate' => floatval($data['monitors']['usd']['price']),
                        'Date' => $data['monitors']['usd']['last_update'],
                        'source' => 'pydolarve',
                    ];
                } else {
                    Log::error('Fallback BCV ❌ Estructura inesperada en pydolarve: ' . json_encode($data));
                }
            } else {
                Log::error('Fallback BCV ❌ HTTP Status inválido de pydolarve — Status: ' . $response->status());
            }
        } catch (\Throwable $e) {
            Log::error('Fallback BCV ❌ Error al conectar con pydolarve: ' . $e->getMessage());
        }

        return null;
    }



    public static function getBcvRateFromBNC(): ?array
    {
        return Cache::remember('bnc_bcv_rate', now()->addMinutes(10), function () {
            $clientGuid = config('app.bnc.client_guid');

            if (!$clientGuid) {
                Log::error('BNC BCV ❌ No hay ClientGUID definido');
                return null;
            }

            try {
                $response = Http::timeout(10)->post(config('app.bnc.base_url') . 'Services/BCVRates', [
                    'ClientGUID' => $clientGuid,
                    'Reference' => now()->format('YmdHis'),
                ]);

                if ($response->ok() && $response['status'] === 'OK') {
                    return [
                        'Rate' => floatval($response['value']['PriceRateBCV']),
                        'Date' => $response['value']['dtRate'],
                        'source' => 'bnc',
                    ];

                }

                Log::error('BNC BCV ❌ Respuesta no OK — Status: ' . $response->status() . ' — Body: ' . $response->body());
            } catch (\Throwable $e) {
                Log::error('BNC BCV ❌ Fallo de conexión: ' . $e->getMessage());
            }

            return null;
        });
    }

    public static function getPositionHistory(string $account): ?array
    {
        try {
            $key = self::getWorkingKey();
            $clientGuid = config('app.bnc.client_guid');
            $clientId = config('app.bnc.client_id'); // asegúrate de tener esto en tu .env

            if (!$key) throw new \Exception('WorkingKey no disponible');

            $body = [
                'ClientID' => $clientId,
                'AccountNumber' => $account,
                'ChildClientID' => '',
                'BranchID' => '',
            ];

            Log::info('BNC HISTORIAL 📤 Enviando (desencriptado): ' . json_encode($body));
            /* Log::info('BNC HISTORIAL 🔑 WorkingKey: ' . substr($key, 0, 10) . '...' . substr($key, -10));
            Log::info('BNC HISTORIAL 🆔 ClientGUID: ' . $clientGuid);
            Log::info('BNC HISTORIAL 🆔 ClientID: ' . $clientId); */

            // Log del payload encriptado antes de enviarlo
            $encryptedValue = BncCryptoHelper::encryptAES($body, $key);
            $validation = BncCryptoHelper::encryptSHA256($body);
            $reference = now()->format('YmdHis');

            $fullPayload = [
                'ClientID' => $clientId,
                'AccountNumber' => $account,
                'ChildClientID' => '',
                'BranchID' => '',
                //'Reference' => $reference,
                //'Value' => $encryptedValue,
                //'Validation' => $validation,
                //'swTestOperation' => false,
            ];

            Log::info('BNC HISTORIAL 📦 Payload completo (encriptado): ' . json_encode($fullPayload,JSON_PRETTY_PRINT));
            Log::info('BNC HISTORIAL 🌐 URL destino: ' . config('app.bnc.base_url') . 'Position/History');

            $response = BncApiService::send('Position/History', $body);

            //Log::info('BNC HISTORIAL 📬 Recibido (crudo): ' . $response->body());
            Log::info('BNC HISTORIAL 📊 Status HTTP: ' . $response->status());
            //Log::info('BNC HISTORIAL 📋 Headers respuesta: ' . json_encode($response->headers()));

            if ($response->ok() || $response->status() === 202) {
                $result = $response->json();

                //if ($result['status'] === 'OK') {
                    $decrypted = BncCryptoHelper::decryptAES($result['value'], $key);
                    Log::info('BNC HISTORIAL ✅ Éxito (desencriptado): ' . json_encode($decrypted,JSON_PRETTY_PRINT));
                    return $decrypted;
                //}

                Log::error('BNC HISTORIAL ❌ Error del API — ' . $result['message']);
            } else {
                Log::error('BNC HISTORIAL ❌ Error HTTP — Status: ' . $response->status());
            }
        } catch (\Throwable $e) {
            Log::error('BNC HISTORIAL ❌ Excepción: ' . $e->getMessage());
            Log::error('BNC HISTORIAL ❌ Stack trace: ' . $e->getTraceAsString());
        }

        return null;
    }

    public static function validateOperationReference(string $reference, string $dateMovement, float $expectedAmount): ?array
    {
        try {
            $key = self::getWorkingKey();
            $clientId = config('app.bnc.client_id');
            $account = config('app.bnc.account');

            $body = array_filter([
                'ClientID' => $clientId,
                'AccountNumber' => $account,
                'Reference' => $reference,
                'Amount' => $expectedAmount,
                'DateMovement' => $dateMovement,
                'ChildClientID' => '',
                'BranchID' => '',
            ], fn($v) => !is_null($v));

            Log::info('BNC VALIDACIÓN REF 📤 Enviando (desencriptado): ' . json_encode($body, JSON_PRETTY_PRINT));

            $response = BncApiService::send('Position/Validate', $body);

            if (in_array($response->status(), [200, 202])) {
                $json = $response->json();

                if (!isset($json['value'])) {
                    Log::error('BNC VALIDACIÓN REF ❌ Respuesta sin campo "value": ' . json_encode($json));
                    return null;
                }

                $decrypted = BncCryptoHelper::decryptAES($json['value'], $key);
                Log::info('BNC VALIDACIÓN REF ✅ Éxito (desencriptado): ' . json_encode($decrypted, JSON_PRETTY_PRINT));

                return $decrypted;
            }

            Log::error('BNC VALIDACIÓN REF ❌ Error HTTP: ' . $response->status() . ' — Body: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('BNC VALIDACIÓN REF ❌ Excepción: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }

        return null;
    }

        public static function getBanks(): ?array
    {
        BncLogger::startOperation('getBanks()');

        try {
            // Paso 1: Obtener WorkingKey
            BncLogger::step(1, 'Obteniendo WorkingKey');
            $key = self::getWorkingKey();

            if (!$key) {
                BncLogger::failure('WorkingKey no disponible - verificar comando bnc:refresh-working-key');
                throw new \Exception('WorkingKey no disponible');
            }

            BncLogger::workingKey('Obtenida exitosamente', [
                'key_length' => strlen($key),
                'key_preview' => substr($key, 0, 8) . '...' . substr($key, -8)
            ]);

            // Paso 2: Obtener configuraciones
            $clientId = config('app.bnc.client_id');
            $baseUrl = config('app.bnc.base_url');

            BncLogger::configuration('Configuraciones obtenidas', [
                'client_id' => $clientId,
                'base_url' => $baseUrl,
                'endpoint_completo' => $baseUrl . 'Services/Banks'
            ]);

            if (empty($clientId)) {
                throw new \Exception('BNC_CLIENT_ID no está configurado');
            }

            // Paso 3: Preparar payload
            $body = [
                'ClientID' => $clientId,
                'ChildClientID' => '',
                'BranchID' => '',
            ];

            BncLogger::step(3, 'Payload preparado', ['body' => $body]);

            // Paso 4: Verificar dependencias de cifrado
            if (!class_exists('App\Helpers\BncCryptoHelper')) {
                throw new \Exception('BncCryptoHelper no encontrado');
            }

            if (!class_exists('phpseclib3\Crypt\AES')) {
                throw new \Exception('phpseclib3 no está instalado - ejecutar composer install');
            }

            BncLogger::step(4, 'Dependencias de cifrado verificadas');

            // Paso 5: Enviar petición
            BncLogger::step(5, 'Enviando petición al BNC');
            BncLogger::apiRequest('Services/Banks', $body);

            $response = BncApiService::send('Services/Banks', $body);

            BncLogger::apiResponse($response->status(), [
                'headers' => $response->headers(),
                'body_length' => strlen($response->body()),
                'body_preview' => substr($response->body(), 0, 200) . '...'
            ]);

            // Paso 6: Procesar respuesta
            if ($response->ok() || $response->status() === 202) {
                $result = $response->json();

                BncLogger::step(6, 'JSON parseado', [
                    'tiene_status' => isset($result['status']),
                    'status_valor' => $result['status'] ?? 'N/A',
                    'tiene_value' => isset($result['value']),
                    'keys_disponibles' => array_keys($result ?? [])
                ]);

                if (!isset($result['value'])) {
                    BncLogger::failure('Respuesta sin campo "value"', [
                        'respuesta_completa' => $result
                    ]);
                    return null;
                }

                // Paso 7: Desencriptar
                BncLogger::step(7, 'Desencriptando respuesta');
                BncLogger::encryption('Iniciando desencriptación');

                $decrypted = BncCryptoHelper::decryptAES($result['value'], $key);

                BncLogger::success('Desencriptación exitosa', [
                    'tipo_resultado' => gettype($decrypted),
                    'es_array' => is_array($decrypted),
                    'cantidad_items' => is_array($decrypted) ? count($decrypted) : 'N/A',
                    'primeros_elementos' => is_array($decrypted) ? array_slice($decrypted, 0, 2) : 'N/A'
                ]);

                return $decrypted;
            }

            // Error HTTP
            BncLogger::failure('Error HTTP en respuesta', [
                'status' => $response->status(),
                'reason' => $response->reason(),
                'body' => $response->body()
            ]);

        } catch (\Throwable $e) {
            BncLogger::exception($e, 'getBanks()');
        }

        BncLogger::warning('Retornando null - proceso falló');
        return null;

        /*
        // Ejemplo de respuesta desencriptada:
        return [
            [
                "Name" => "Banco Central de Venezuela",
                "Code" => "0001",
                "Services" => "TRF"
            ],
            [
                "Name" => "Banco de Venezuela",
                "Code" => "0102",
                "Services" => "TRF, P2P"
            ],
            [
                "Name" => "Banco Venezolano de Crédito",
                "Code" => "0104",
                "Services" => "TRF, P2P"
            ],
            // ...
        ];
        */
    }









}
