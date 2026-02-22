# Asunto: Estamos encima del 502 — te comparto diagnóstico apenas lo tenga

Hola [Nombre],

Gracias por avisar y entiendo la urgencia (más aún en plena campaña). Ya estoy revisando el servidor para identificar la causa del 502 intermitente.

Normalmente, este error aparece cuando el servidor web (Nginx/Apache) no logra comunicarse a tiempo con el backend que procesa PHP (PHP-FPM/Apache), o cuando hay saturación de recursos en momentos de pico.

## Acciones actuales:
- Revisar logs del gateway y del backend (Nginx/Apache y PHP-FPM)
- Comprobar estado de servicios y recursos (CPU/RAM/IO)
- Aislar si el fallo viene de la aplicación (WordPress/plugins/checkout), de infraestructura o del proveedor/pasarela.

La prioridad es estabilizar el checkout para que puedan seguir cobrando sin fricción. En cuanto tenga el primer diagnóstico (causa probable + siguiente acción), te escribo con el detalle y los pasos a seguir.

---

## Comandos iniciales (diagnóstico) + qué busco

1. **Errores del gateway (Nginx)**
sudo tail -n 200 /var/log/nginx/error.log
**Busco:** si el servidor frontal está esperando demasiado al backend (upstream timed out) o ni siquiera logra conectarse (connect() failed / Bad Gateway).
Si el proxy fuese Apache, revisaría también `/var/log/apache2/error.log`.

2. **Errores del backend PHP (PHP-FPM)**
sudo tail -n 200 /var/log/php8.2-fpm.log
*(Ajustaría ruta/versión según servidor: php-fpm.log, www-error.log)*
**Busco:** si PHP se quedó sin capacidad (pm.max_children), sin memoria (out of memory) o está respondiendo lento (slow requests).

3. **Salud general del sistema (carga/memoria)**
uptime && free -h
**Busco:** si el servidor está saturado (carga alta) o sin memoria.
Esto confirma si es un problema de capacidad (toca escalar/optimizar) o si hay un cuello de botella (DB/PHP/checkout).