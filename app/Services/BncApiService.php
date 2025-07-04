<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Helpers\BncCryptoHelper;
use App\Helpers\BncHelper;
use Illuminate\Support\Facades\Log;

class BncApiService
{
    public static function send(string $endpoint, array $data)
    {
        $workingKey = BncHelper::getWorkingKey();

        if (!$workingKey) {
            throw new \Exception('No se pudo obtener la WorkingKey.');
        }

        $payload = [
            'ClientGUID' => config('app.bnc.client_guid'),
            //'Reference' => now()->format('YmdHis'),
            'Value' => BncCryptoHelper::encryptAES($data, $workingKey),
            'Validation' => BncCryptoHelper::encryptSHA256($data),
            //'swTestOperation' => false,
            'MasterKey' => config('app.bnc.master_key'),
            'WorkingKey' => $workingKey,
        ];

        //Log::info('BNC API SERVICE 📤 Enviando (desencriptado): ' . json_encode($payload,JSON_PRETTY_PRINT));

        return Http::post(config('app.bnc.base_url') . $endpoint, $payload);
    }


}
