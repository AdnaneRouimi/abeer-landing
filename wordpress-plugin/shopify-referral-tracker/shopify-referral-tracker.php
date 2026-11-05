<?php
/**
 * Plugin Name: Shopify Referral Tracker
 * Description: Tracks outbound Shopify referral clicks and forwards click events to an n8n webhook.
 * Version: 1.0.0
 * Author: Demo Project
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Shopify_Referral_Tracker {
    const OPTION_STORE = 'srt_shopify_store_url';
    const OPTION_WEBHOOK = 'srt_n8n_webhook_url';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('shopify_referral_button', [$this, 'render_button']);
        add_action('init', [$this, 'add_rewrite_rule']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'handle_redirect']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function activate() {
        $this->add_rewrite_rule();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function add_rewrite_rule() {
        add_rewrite_rule('^go/shopify/?$', 'index.php?srt_shopify_redirect=1', 'top');
    }

    public function register_query_vars($vars) {
        $vars[] = 'srt_shopify_redirect';
        return $vars;
    }

    public function admin_menu() {
        add_options_page(
            'Shopify Referral Tracker',
            'Shopify Referral Tracker',
            'manage_options',
            'shopify-referral-tracker',
            [$this, 'settings_page']
        );
    }

    public function register_settings() {
        register_setting('srt_settings', self::OPTION_STORE, [
            'sanitize_callback' => [$this, 'sanitize_store_url']
        ]);
        register_setting('srt_settings', self::OPTION_WEBHOOK, [
            'sanitize_callback' => 'esc_url_raw'
        ]);
    }

    public function sanitize_store_url($url) {
        $url = esc_url_raw(trim($url));
        if (!$url || !in_array(wp_parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            add_settings_error(self::OPTION_STORE, 'invalid_store', 'Enter a valid Shopify store URL.');
            return get_option(self::OPTION_STORE, '');
        }
        return untrailingslashit($url);
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>Shopify Referral Tracker</h1>
            <p>Configure the Shopify destination and the n8n webhook that receives referral click events.</p>
            <form method="post" action="options.php">
                <?php settings_fields('srt_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_STORE); ?>">Shopify store URL</label></th>
                        <td><input class="regular-text" type="url" id="<?php echo esc_attr(self::OPTION_STORE); ?>"
                            name="<?php echo esc_attr(self::OPTION_STORE); ?>"
                            value="<?php echo esc_attr(get_option(self::OPTION_STORE, '')); ?>"
                            placeholder="https://your-store.myshopify.com" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_WEBHOOK); ?>">n8n webhook URL</label></th>
                        <td><input class="regular-text" type="url" id="<?php echo esc_attr(self::OPTION_WEBHOOK); ?>"
                            name="<?php echo esc_attr(self::OPTION_WEBHOOK); ?>"
                            value="<?php echo esc_attr(get_option(self::OPTION_WEBHOOK, '')); ?>"
                            placeholder="http://n8n:5678/webhook/shopify-referral-click"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <hr>
            <h2>How to add the referral button</h2>
            <p>Insert this shortcode into any WordPress page or post:</p>
            <code>[shopify_referral_button label="Visit our store" campaign="homepage"]</code>
        </div>
        <?php
    }

    public function render_button($atts) {
        $atts = shortcode_atts([
            'label' => 'Shop now',
            'campaign' => 'default'
        ], $atts, 'shopify_referral_button');

        $url = add_query_arg([
            'campaign' => sanitize_key($atts['campaign']),
            'source' => 'wordpress'
        ], home_url('/go/shopify/'));

        return sprintf(
            '<a class="srt-shopify-button" href="%s" style="display:inline-block;padding:12px 20px;background:#171717;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">%s</a>',
            esc_url($url),
            esc_html($atts['label'])
        );
    }

    public function handle_redirect() {
        if (!get_query_var('srt_shopify_redirect')) return;

        $store_url = get_option(self::OPTION_STORE, '');
        if (!$store_url) {
            wp_die('The Shopify destination has not been configured yet.', 'Referral destination missing', ['response' => 503]);
        }

        $campaign = isset($_GET['campaign']) ? sanitize_key(wp_unslash($_GET['campaign'])) : 'default';
        $event = [
            'event' => 'shopify_referral_click',
            'source' => 'wordpress',
            'campaign' => $campaign,
            'clicked_at' => gmdate('c'),
            'landing_page' => wp_get_referer() ?: home_url('/'),
            'destination' => $store_url,
            'visitor' => substr(hash('sha256', wp_salt('auth') . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')), 0, 16),
            'user_agent' => substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 250)
        ];

        $webhook = get_option(self::OPTION_WEBHOOK, '');
        if ($webhook) {
            wp_remote_post($webhook, [
                'timeout' => 2,
                'blocking' => false,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode($event)
            ]);
        }

        // The destination is an external, admin-configured store URL, so wp_safe_redirect()
        // would reject it and fall back to wp-admin. Use wp_redirect() instead.
        wp_redirect($store_url, 302, 'Shopify Referral Tracker');
        exit;
    }
}

new Shopify_Referral_Tracker();
