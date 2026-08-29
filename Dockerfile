# =====================================================================
#  Imagen para el POS Librería (PHP 8.3 + Apache)
#  Funciona igual en local (docker compose) y en Railway.app
# =====================================================================
FROM php:8.3-apache

# Extensiones de PHP necesarias para MySQL
RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite \
    # Garantizar que solo se cargue UN MPM (prefork) para evitar el
    # error "AH00534: se ha cargado más de un MPM".
    && a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork

# El document root es la carpeta public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Permitir el uso de .htaccess (AllowOverride All)
RUN printf '<Directory %s>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
    "${APACHE_DOCUMENT_ROOT}" > /etc/apache2/conf-available/z-override.conf \
    && a2enconf z-override

# Copiar el código de la aplicación
COPY . /var/www/html

# Permisos de escritura para las imágenes subidas
RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/public/uploads

# Railway inyecta el puerto en $PORT (por defecto 80 en local).
# Se usa la forma "shell" del CMD para que se expanda la variable.
CMD sed -i "s/^Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
    && sed -i "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf \
    && apache2-foreground
