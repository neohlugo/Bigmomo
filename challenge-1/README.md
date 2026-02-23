# CRM Post Alert (WordPress mini-plugin)

Este mini-plugin (PHP) notifica a un CRM externo cada vez que una entrada del Blog se **publica por primera vez**, enviando **título**, **URL** y **meta description** (Yoast SEO o RankMath).

La idea principal es simple: **la publicación jamás debe depender del CRM**. Si el CRM está caído o lento, el editor publica igual y el error queda registrado.

---

## Qué resuelve (requisitos del challenge)

- Detecta **primera publicación** (no actualizaciones) usando el hook correcto (`transition_post_status`)
- Extrae la **Meta Description** desde:
  - Yoast: `_yoast_wpseo_metadesc` / `WPSEO_Meta::get_value()`
  - RankMath: `rank_math_description` / `RankMath\Helper::get_post_meta()`
  - Fallback: excerpt / contenido recortado
- Envía un **POST** a `https://api.fake-crm.com/v1/alert`
- No bloquea el editor: el envío ocurre **asíncrono** (WP-Cron)
- Timeout estricto: **3s**
- Si falla, registra el error en `crm-errors.log` dentro del plugin
- Evita duplicados marcando `_crm_alert_sent`

---

## Diseño (por qué está hecho así)

### 1) Solo “primera publicación”
`transition_post_status` permite detectar la transición **a** `publish` desde cualquier otro estado.  
Esto evita disparos en “Actualizar” y en cambios menores editoriales.

### 2) Envío asíncrono para no colgar al editor
El POST al CRM **no** se ejecuta dentro del request de publicación.  
En su lugar se encola un evento con `wp_schedule_single_event()` y se procesa en segundo plano.

### 3) Delay corto (15s) para metadatos SEO
Yoast/RankMath a veces persisten metadatos “un poco después” del publish.  
Por eso el cron corre con un delay de 15s: asegura que la meta description ya exista cuando la leemos.

---

## Flujo de ejecución (paso a paso)

1. Un post pasa a `publish` por primera vez (`transition_post_status`).
2. Se encola un evento de WP-Cron (con delay de 15s).
3. El cron ejecuta el POST con `wp_remote_post()` y `timeout => 3`.
4. Si el CRM responde **2xx**, se guarda `_crm_alert_sent = 1`.
5. Si falla (timeout, caída, HTTP != 2xx), se registra en `crm-errors.log`.

---

## Payload enviado

Se envía JSON con `Content-Type: application/json`:

```json
{
  "title": "Título del post",
  "url": "https://example.com/mi-post/",
  "meta_description": "Meta description (Yoast/RankMath)"
}
