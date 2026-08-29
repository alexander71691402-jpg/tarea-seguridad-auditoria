#!/bin/sh
# =====================================================================
#  Arranque de Apache.
#  Railway asigna el puerto en $PORT; en local se usa 80.
#  Se regenera la configuracion desde la plantilla en cada arranque,
#  de modo que reiniciar el contenedor sea idempotente.
# =====================================================================
set -eu

PORT="${PORT:-80}"

echo "Listen ${PORT}" > /etc/apache2/ports.conf
sed "s/__PORT__/${PORT}/g" /etc/apache2/vhost.conf.tpl \
    > /etc/apache2/sites-available/000-default.conf

echo "[entrypoint] Apache escuchando en el puerto ${PORT}"
echo "[entrypoint] MPM cargado: $(ls /etc/apache2/mods-enabled/ | grep '^mpm_.*\.load$' | tr '\n' ' ')"

exec apache2-foreground "$@"
