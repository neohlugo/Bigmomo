# Asunto: Estamos encima del 502 — te comparto diagnóstico apenas lo tenga

Hola Cliente Furioso,

Recibido. Ya estamos al tanto del error 502 intermitente en el checkout y lo estamos tratando como prioridad máxima, especialmente por el impacto en campaña.

Ahora mismo estoy revisando el servidor para identificar la causa (logs y estado del servicio) y confirmar si el problema es del hosting/backend o de alguna dependencia externa.

Mientras avanzamos con el diagnóstico, para no frenar ventas, vamos a habilitar una medida de contención: aviso en el checkout + canal alternativo de soporte (WhatsApp) para asistir a quienes no logren completar el pago.

Te envío un primer update apenas tenga el diagnóstico inicial y el siguiente paso concreto (y si hace falta escalar a proveedor/servicio de pago, lo hacemos de inmediato).

Saludos,
Neoh
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
