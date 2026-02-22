<?php
/**
 * Plugin Name: CRM Post Alert
 * Description: Envía a un CRM externo los datos de un post cuando se publica por primera vez.
 * Version: 1.0.1
 * Author: Neoh 
 */

if ( ! defined('ABSPATH') ) exit;

final class CRM_Post_Alert {
    const CRM_ENDPOINT = 'https://api.fake-crm.com/v1/alert';
    const META_SENT    = '_crm_alert_sent';
    const CRON_HOOK    = 'crm_post_alert_send';
    const LOG_FILE     = 'crm-errors.log';

    public static function init(): void {
        add_action('transition_post_status', [__CLASS__, 'on_transition_post_status'], 10, 3);
        add_action(self::CRON_HOOK, [__CLASS__, 'cron_send'], 10, 1);
    }

    /**
     * Solo dispara cuando un post pasa a "publish" por primera vez.
     * Encola un cron con delay para dar tiempo a plugins SEO (Yoast/RankMath) a persistir metadatos.
     */
    public static function on_transition_post_status(string $new_status, string $old_status, \WP_Post $post): void {
        if ($post->post_type !== 'post') return;

        // Solo primera publicación: de cualquier estado != publish -> publish
        if ($new_status !== 'publish' || $old_status === 'publish') return;

        // Evitar duplicados si ya se marcó como enviado
        if (get_post_meta($post->ID, self::META_SENT, true)) return;

        // Evitar encolar 2 veces el mismo evento
        if (wp_next_scheduled(self::CRON_HOOK, [$post->ID])) return;

        // Delay (15s) para que Yoast/RankMath guarden metadesc antes de leerla
        wp_schedule_single_event(time() + 15, self::CRON_HOOK, [$post->ID]);
    }

    /**
     * Job asíncrono: realiza el POST al CRM con timeout de 3s y loguea errores.
     */
    public static function cron_send(int $post_id): void {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post') return;
        if ($post->post_status !== 'publish') return;

        if (get_post_meta($post_id, self::META_SENT, true)) return;

        $title = get_the_title($post_id);
        $url   = get_permalink($post_id);


        $meta_description = self::get_meta_description($post_id);

        $payload = [
            'title'            => $title,
            'url'              => $url,
            'meta_description' => $meta_description,
        ];

        $args = [
            'method'      => 'POST',
            'timeout'     => 3, // requisito: si tarda más de 3s, consideramos fallo
            'redirection' => 0,
            'headers'     => [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            'body'        => wp_json_encode($payload),
        ];

        $response = wp_remote_post(self::CRM_ENDPOINT, $args);

        if (is_wp_error($response)) {
            self::log_error($post_id, 'WP_Error: ' . $response->get_error_message(), $payload);
            return;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);

        // Consideramos éxito solo 2xx
        if ($code < 200 || $code >= 300) {
            self::log_error($post_id, "HTTP $code: $body", $payload);
            return;
        }

        // OK → marcamos como enviado
        update_post_meta($post_id, self::META_SENT, 1);
    }

    /**
     * Obtiene meta description desde Yoast o Rank Math, con fallback.
     */
    private static function get_meta_description(int $post_id): string {
        // 1) Meta directa (Yoast/RankMath)
        $yoast = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        if (is_string($yoast) && trim($yoast) !== '') return trim($yoast);

        $rankmath = get_post_meta($post_id, 'rank_math_description', true);
        if (is_string($rankmath) && trim($rankmath) !== '') return trim($rankmath);

        // 2) Yoast API (por si está generada dinámicamente)
        if (class_exists('WPSEO_Meta')) {
            $val = \WPSEO_Meta::get_value('metadesc', $post_id);
            if (is_string($val) && trim($val) !== '') return trim($val);
        }

        // 3) Rank Math helper (si existiera)
        if (class_exists('\RankMath\Helper')) {
            try {
                $val = \RankMath\Helper::get_post_meta('description', $post_id);
                if (is_string($val) && trim($val) !== '') return trim($val);
            } catch (\Throwable $e) {
                // Fallback abajo
            }
        }

        // 4) Fallback: excerpt o contenido recortado
        $excerpt = get_the_excerpt($post_id);
        $excerpt = wp_strip_all_tags((string) $excerpt);

        if (trim($excerpt) === '') {
            $content = get_post_field('post_content', $post_id);
            $content = wp_strip_all_tags((string) $content);
            $excerpt = $content;
        }

        $excerpt = trim(preg_replace('/\s+/', ' ', $excerpt));
        return mb_substr($excerpt, 0, 160);
    }

    /**
     * Log dentro del plugin: crm-errors.log
     */
    private static function log_error(int $post_id, string $message, array $payload = []): void {
        $log_file = trailingslashit(plugin_dir_path(__FILE__)) . self::LOG_FILE;

        $line = sprintf(
            "[%s] post_id=%d message=%s payload=%s\n",
            gmdate('c'),
            $post_id,
            $message,
            wp_json_encode($payload)
        );

        @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
    }
}