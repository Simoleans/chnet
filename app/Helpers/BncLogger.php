<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class BncLogger
{
    /**
     * Canal de logging específico para BNC
     */
    private static function getLogger()
    {
        return Log::channel('bnc');
    }

    /**
     * Log de información general
     */
    public static function info(string $message, array $context = [])
    {
        self::getLogger()->info("[BNC INFO] $message", $context);
    }

    /**
     * Log de error
     */
    public static function error(string $message, array $context = [])
    {
        self::getLogger()->error("[BNC ERROR] $message", $context);
    }

    /**
     * Log de advertencia
     */
    public static function warning(string $message, array $context = [])
    {
        self::getLogger()->warning("[BNC WARNING] $message", $context);
    }

    /**
     * Log de debug
     */
    public static function debug(string $message, array $context = [])
    {
        self::getLogger()->debug("[BNC DEBUG] $message", $context);
    }

    // Métodos específicos para BNC

    public static function startOperation(string $operation)
    {
        self::info("=== INICIANDO $operation ===");
    }

    public static function step(int $stepNumber, string $description, array $context = [])
    {
        self::info("PASO $stepNumber: $description", $context);
    }

    public static function success(string $message, array $context = [])
    {
        self::info("EXITO - $message", $context);
    }

    public static function failure(string $message, array $context = [])
    {
        self::error("FALLO - $message", $context);
    }

    public static function apiRequest(string $endpoint, array $payload = [])
    {
        self::info("PETICION API - Endpoint: $endpoint", [
            'endpoint' => $endpoint,
            'payload_size' => count($payload),
            'payload' => $payload
        ]);
    }

    public static function apiResponse(int $statusCode, array $response = [])
    {
        $level = $statusCode >= 200 && $statusCode < 300 ? 'info' : 'error';

        self::getLogger()->$level("RESPUESTA API - Status: $statusCode", [
            'status_code' => $statusCode,
            'response_size' => count($response),
            'response' => $response
        ]);
    }

    public static function workingKey(string $action, array $context = [])
    {
        self::info("WORKING KEY - $action", $context);
    }

    public static function encryption(string $action, array $context = [])
    {
        self::info("CIFRADO - $action", $context);
    }

    public static function configuration(string $message, array $config = [])
    {
        self::info("CONFIGURACION - $message", $config);
    }

    public static function exception(\Throwable $e, string $context = '')
    {
        self::error("EXCEPCION CAPTURADA - $context", [
            'mensaje' => $e->getMessage(),
            'archivo' => $e->getFile(),
            'linea' => $e->getLine(),
            'clase' => get_class($e),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
