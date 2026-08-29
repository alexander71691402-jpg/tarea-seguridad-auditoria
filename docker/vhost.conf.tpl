# =====================================================================
#  VirtualHost del POS Libreria.
#  __PORT__ lo reemplaza docker/entrypoint.sh con el valor de $PORT
#  (Railway inyecta un puerto aleatorio; en local es 80).
# =====================================================================
<VirtualHost *:__PORT__>
    ServerName localhost
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # El codigo de la aplicacion nunca debe servirse directamente.
    <Directory /var/www/html/app>
        Require all denied
    </Directory>

    # Logs a la salida estandar para que Railway los muestre.
    ErrorLog /dev/stderr
    CustomLog /dev/stdout combined
</VirtualHost>
