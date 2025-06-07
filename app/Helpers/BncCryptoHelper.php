<?php

namespace App\Helpers;

use phpseclib3\Crypt\AES;
use phpseclib3\Exception\BadDecryptionException;

class BncCryptoHelper
{
    public static function encryptAES(array $data, string $encryptionKey): string
    {
        $salt = hex2bin('4976616e204d65647665646576'); // "Ivan Medvedev"
        $iterations = 1000;

        $keyMaterial = hash_pbkdf2('sha1', $encryptionKey, $salt, $iterations, 48, true);
        $key = substr($keyMaterial, 0, 32);
        $iv  = substr($keyMaterial, 32, 16);

        $aes = new AES('cbc');
        $aes->setKey($key);
        $aes->setIV($iv);

        return base64_encode($aes->encrypt(mb_convert_encoding(json_encode($data), 'UTF-16LE')));
    }

    public static function decryptAES(string $cipherText, string $encryptionKey): array
    {
        $salt = hex2bin('4976616e204d65647665646576');
        $iterations = 1000;

        $keyMaterial = hash_pbkdf2('sha1', $encryptionKey, $salt, $iterations, 48, true);
        $key = substr($keyMaterial, 0, 32);
        $iv  = substr($keyMaterial, 32, 16);

        $aes = new AES('cbc');
        $aes->setKey($key);
        $aes->setIV($iv);

        try {
            $decrypted = $aes->decrypt(base64_decode($cipherText));
            return json_decode(mb_convert_encoding($decrypted, 'UTF-8', 'UTF-16LE'), true);
        } catch (BadDecryptionException $e) {
            return ['error' => 'Decryption failed'];
        }
    }

    public static function encryptSHA256(array $data): string
    {
        return hash('sha256', json_encode($data));
    }
}
