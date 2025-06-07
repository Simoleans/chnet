<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ApiStatus;
use App\Helpers\BncCryptoHelper;

class RefreshWorkingKey extends Command
{
    protected $signature = 'bnc:refresh-working-key';
    protected $description = 'Autentica con la API del BNC y guarda la WorkingKey diaria en la base de datos.';

    public function handle()
    {
        $clientGuid = config('app.bnc.client_guid');
        $masterKey = config('app.bnc.master_key');

        if (!$clientGuid || !$masterKey) {
            $message = 'Faltan las variables BNC_CLIENT_GUID o BNC_MASTER_KEY en el .env';
            $this->error($message);
            Log::error("BNC LOGON ❌ $message");
            return;
        }

        $payload = ['ClientGUID' => $clientGuid];
        $value = BncCryptoHelper::encryptAES($payload, $masterKey);
        $validation = BncCryptoHelper::encryptSHA256($payload);

        $response = Http::post(config('app.bnc.base_url') . 'Auth/LogOn', [
            'ClientGUID' => $clientGuid,
            'Reference' => now()->format('YmdHis'),
            'Value' => $value,
            'Validation' => $validation,
            'swTestOperation' => false,
        ]);

        if (!isset($response['status']) || $response['status'] !== 'OK') {
            $message = 'La API del BNC devolvió un estado no exitoso.';
            $this->error($message);
            Log::error("BNC LOGON ❌ $message — Status: {$response->status()} — Body: " . $response->body());
            return;
        }


        if (!isset($response['value'])) {
            $message = 'No se recibió el campo "value" en la respuesta.';
            $this->error($message);
            Log::error("BNC LOGON ❌ $message — Respuesta: " . $response->body());
            return;
        }

        $decrypted = BncCryptoHelper::decryptAES($response['value'], $masterKey);

        if (isset($decrypted['WorkingKey'])) {
            ApiStatus::updateOrCreate(
                ['api_name' => 'bnc'],
                [
                    'working_key' => $decrypted['WorkingKey'],
                    'generated_at' => now()
                ]
            );

            $message = 'WorkingKey actualizada y guardada correctamente.';
            $this->info($message);
            Log::info("BNC LOGON ✅ $message");
        } else {
            $message = 'No se pudo desencriptar correctamente la WorkingKey.';
            $this->error($message);
            Log::error("BNC LOGON ❌ $message — Encriptado recibido: " . $response['value']);
        }
    }
}
