<?php

echo "🚀 Iniciando deploy...\n";

// Establecer directorio de trabajo
chdir(__DIR__);

// Detectar PHP disponible
exec('which php', $which);
exec('which composer', $which);
$php = $which[0] ?? 'php';
$composer = $which[1] ?? 'composer';
echo "PHP encontrado: $php\n";
echo "Composer encontrado: $composer\n";

// Ejecutar composer install
echo "Ejecutando composer install...\n";
exec("export COMPOSER_HOME=/tmp && $composer install --no-dev --optimize-autoloader 2>&1", $composerOutput);
echo implode("\n", $composerOutput);

// Limpiar cachés
echo "🧹 Limpiando cachés...\n";
exec("$php artisan config:clear 2>&1");
exec("$php artisan route:clear 2>&1");
exec("$php artisan view:clear 2>&1");
exec("$php artisan cache:clear 2>&1");

// Recompilar cachés
echo "🧠 Recompilando cachés...\n";
exec("$php artisan config:cache 2>&1");
exec("$php artisan route:cache 2>&1");
exec("$php artisan view:cache 2>&1");

// Ejecutar migraciones
echo "🛠 Ejecutando migraciones...\n";
exec("$php artisan migrate --force 2>&1", $migrateOutput);
echo implode("\n", $migrateOutput);

echo "✅ Deploy completado correctamente.\n";
?>
