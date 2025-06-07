<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Helpers\BncCryptoHelper;
use App\Helpers\BncHelper;

class BncApiService
{
    public function send(string $endpoint, array $data)
    {
        $workingKey = BncHelper::getWorkingKey();

        if (!$workingKey) {
            throw new \Exception('No se pudo obtener la WorkingKey.');
        }

        $payload = [
            'ClientGUID' => config('app.bnc.client_guid'),
            'Reference' => now()->format('YmdHis'),
            'Value' => BncCryptoHelper::encryptAES($data, $workingKey),
            'Validation' => BncCryptoHelper::encryptSHA256($data),
            'swTestOperation' => false,
        ];

        return Http::post(config('app.bnc.base_url') . $endpoint, $payload);
    }
}
