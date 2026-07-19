#!/bin/bash
set -e

# Ensure writable config files exist (named volume starts empty)
if [ ! -f /var/www/html/config/Conexion.php ]; then
    echo "<?php
class Conexion
{
    static private \$instancia = null;

    static public function conectar()
    {
        if (self::\$instancia === null) {
            try {
                \$host = getenv('DB_HOST') ?: 'localhost';
                \$dbname = getenv('DB_NAME') ?: 'asistencia_db';
                \$user = getenv('DB_USER') ?: 'root';
                \$pass = getenv('DB_PASSWORD') ?: '';
                self::\$instancia = new PDO(
                    \"mysql:host=\$host;dbname=\$dbname;charset=utf8\",
                    \$user,
                    \$pass
                );
                self::\$instancia->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::\$instancia->exec(\"set names utf8\");
            } catch (PDOException \$e) {
                die(\"Error de conexi\u00f3n a la base de datos. Contacte al administrador.\");
            }
        }
        return self::\$instancia;
    }
}
?>" > /var/www/html/config/Conexion.php
fi

# Create default smtp.json if missing
if [ ! -f /var/www/html/config/smtp.json ]; then
    echo '{
    "smtp_host": "",
    "smtp_port": 587,
    "smtp_secure": "tls",
    "smtp_username": "",
    "smtp_password": "",
    "from_email": "noreply@sibca.edu",
    "from_name": "SIBCA - Sistema de Asistencia"
}' > /var/www/html/config/smtp.json
fi

# Create default access_time.json if missing
if [ ! -f /var/www/html/config/access_time.json ]; then
    echo '{
    "dias": [1, 2, 3, 4, 5],
    "hora_inicio": 8,
    "hora_fin": 13,
    "habilitado": false,
    "tiempo_inactividad": 5
}' > /var/www/html/config/access_time.json
fi

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
for i in $(seq 1 120); do
    if mysqladmin ping -h "$DB_HOST" -u root --silent 2>/dev/null || mysqladmin ping -h "$DB_HOST" -u root -p"${MYSQL_ROOT_PASSWORD}" --silent 2>/dev/null; then
        echo "MySQL is ready!"
        sleep 3
        break
    fi
    echo "Attempt $i: MySQL not ready yet, waiting 2s..."
    sleep 2
done

# Ensure proper ownership
chown -R www-data:www-data /var/www/html/config \
    /var/www/html/tmp \
    /var/www/html/foto_perfil \
    /var/www/html/adjuntos_justificaciones \
    /var/www/html/backups

exec "$@"
