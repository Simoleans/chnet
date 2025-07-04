<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\BncLogger;
use App\Helpers\BncHelper;

class TestBncLogging extends Command
{
    protected $signature = 'bnc:test-logging';
    protected $description = 'Prueba el sistema de logging de BNC para verificar que funciona correctamente';

    public function handle()
    {
        $this->info('=== INICIANDO PRUEBA DE LOGGING BNC ===');

        // 1. Probar diferentes tipos de logs
        BncLogger::info('Comando de prueba iniciado');
        BncLogger::startOperation('test-logging');

        // 2. Verificar configuraciones
        $configs = [
            'client_guid' => config('app.bnc.client_guid'),
            'master_key' => config('app.bnc.master_key'),
            'base_url' => config('app.bnc.base_url'),
            'client_id' => config('app.bnc.client_id'),
        ];

        BncLogger::configuration('Configuraciones verificadas', [
            'client_guid_presente' => !empty($configs['client_guid']),
            'master_key_presente' => !empty($configs['master_key']),
            'base_url_presente' => !empty($configs['base_url']),
            'client_id_presente' => !empty($configs['client_id']),
        ]);

        // 3. Probar steps
        for ($i = 1; $i <= 3; $i++) {
            BncLogger::step($i, "Paso de prueba número $i", ['test_data' => "valor_$i"]);
        }

        // 4. Probar working key
        BncLogger::workingKey('Probando log de WorkingKey', ['test' => true]);

        // 5. Probar logs de cifrado
        BncLogger::encryption('Probando log de cifrado', ['algorithm' => 'AES-256']);

        // 6. Probar logs de API
        BncLogger::apiRequest('test/endpoint', ['test_param' => 'test_value']);
        BncLogger::apiResponse(200, ['success' => true, 'message' => 'Test response']);

        // 7. Probar logs de éxito/fallo
        BncLogger::success('Operación de prueba exitosa');
        BncLogger::warning('Este es un warning de prueba');

        // 8. Probar excepción controlada
        try {
            throw new \Exception('Esta es una excepción de prueba controlada');
        } catch (\Exception $e) {
            BncLogger::exception($e, 'test-logging');
        }

        // 9. Verificar archivo de log
        $logFile = storage_path('logs/bnc.log');

        if (file_exists($logFile)) {
            $this->info("✅ Archivo de log BNC creado: $logFile");
            $fileSize = filesize($logFile);
            $this->info("📊 Tamaño del archivo: " . number_format($fileSize) . " bytes");

            if ($fileSize > 0) {
                $this->info("✅ El archivo contiene datos");

                // Mostrar las últimas líneas del log
                $this->info("\n🔍 Últimas líneas del log BNC:");
                $this->line(str_repeat('-', 60));

                $lines = file($logFile);
                $lastLines = array_slice($lines, -5);

                foreach ($lastLines as $line) {
                    $this->line(trim($line));
                }

                $this->line(str_repeat('-', 60));
            } else {
                $this->error("❌ El archivo está vacío - verificar permisos de escritura");
            }
        } else {
            $this->error("❌ No se pudo crear el archivo de log BNC");
            $this->error("   Verificar permisos en: " . dirname($logFile));
        }

        // 10. Verificar permisos
        $logDir = storage_path('logs');
        $this->info("\n📁 Información de permisos:");
        $this->info("Directorio logs: $logDir");
        $this->info("Existe: " . (is_dir($logDir) ? 'SÍ' : 'NO'));
        $this->info("Escribible: " . (is_writable($logDir) ? 'SÍ' : 'NO'));

        if (file_exists($logFile)) {
            $this->info("Archivo escribible: " . (is_writable($logFile) ? 'SÍ' : 'NO'));
        }

        BncLogger::success('Comando de prueba completado');

        $this->info("\n✅ Prueba de logging BNC completada");
        $this->info("📋 Revisa el archivo storage/logs/bnc.log para ver todos los logs");
    }
}
