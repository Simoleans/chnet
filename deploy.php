<?php

echo "🚀 Iniciando deploy...\n";

// Establecer directorio de trabajo en chnet
chdir(__DIR__);

// Ejecutar composer install
echo "Ejecutando composer install...\n";
exec('export COMPOSER_HOME=/tmp && ea-php82 /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader 2>&1', $composerOutput);


echo implode("\n", $composerOutput);

// Limpiar cachés
echo "Limpiando cachés...\n";
exec('ea-php82 artisan config:clear 2>&1');
exec('ea-php82 artisan route:clear 2>&1');
exec('ea-php82 artisan view:clear 2>&1');
exec('ea-php82 artisan cache:clear 2>&1');

// Recompilar cachés
echo "🧠 Recompilando cachés...\n";
exec('ea-php82 artisan config:cache 2>&1');
exec('ea-php82 artisan route:cache 2>&1');
exec('ea-php82 artisan view:cache 2>&1');

// Ejecutar migraciones
echo "🛠 Ejecutando migraciones...\n";
exec('ea-php82 artisan migrate --force 2>&1', $migrateOutput);
echo implode("\n", $migrateOutput);

echo "✅ Deploy completado correctamente.\n";
?>
