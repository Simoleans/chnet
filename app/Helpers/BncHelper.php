<?php

namespace App\Helpers;

use App\Models\ApiStatus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

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

}
