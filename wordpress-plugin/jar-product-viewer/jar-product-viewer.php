<?php
/**
 * Plugin Name:       Jar Product Viewer (3D)
 * Description:       Scroll-driven 3D product story built with Three.js and GSAP. Shows the ABEER jar, rotates it on scroll, alternates info panels, opens the lid, and closes the story with a product grid. Use the [jar_product_viewer] shortcode or the "ABEER 3D Product Story" Elementor widget.
 * Version:           1.1.0
 * Author:            ABEER
 * License:           GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Jar_Product_Viewer
{
    /** Path of the model, relative to this file. */
    const MODEL_REL = 'assets/abeer_jar.glb';

    public function __construct()
    {
        add_shortcode('jar_product_viewer', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    /**
     * Enqueue the CDN libraries and local assets, but only on pages that
     * actually use the shortcode or the Elementor widget.
     */
    public function enqueue()
    {
        if (!is_singular()) {
            return;
        }

        $post = get_post();
        if (!$post) {
            return;
        }

        $elementor_data = (string) get_post_meta($post->ID, '_elementor_data', true);
        $uses_elementor = 'builder' === get_post_meta($post->ID, '_elementor_edit_mode', true);
        $uses_widget = false !== strpos($elementor_data, 'abeer_jar_viewer');

        if (!has_shortcode((string) $post->post_content, 'jar_product_viewer') && !$uses_elementor && !$uses_widget) {
            return;
        }

        // Three.js (UMD build) + GLTFLoader + RoomEnvironment for realistic metal reflections.
        wp_enqueue_script('three', 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js', [], 'r128', true);
        wp_enqueue_script('three-gltf', 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js', ['three'], '0.128.0', true);
        wp_enqueue_script('three-room', 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/environments/RoomEnvironment.js', ['three'], '0.128.0', true);

        // GSAP + ScrollTrigger.
        wp_enqueue_script('gsap', 'https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js', [], '3.12.5', true);
        wp_enqueue_script('gsap-scrolltrigger', 'https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js', ['gsap'], '3.12.5', true);

        // Local scene script + styles.
        wp_enqueue_script(
            'jar-viewer',
            plugins_url('assets/jar-viewer.js', __FILE__),
            ['three', 'three-gltf', 'three-room', 'gsap', 'gsap-scrolltrigger'],
            (string) filemtime(dirname(__FILE__) . '/assets/jar-viewer.js'),
            true
        );

        wp_localize_script('jar-viewer', 'JAR_VIEWER', [
            'modelUrl' => plugins_url(self::MODEL_REL, __FILE__),
        ]);

        wp_enqueue_style(
            'jar-viewer',
            plugins_url('assets/jar-viewer.css', __FILE__),
            [],
            '1.5.' . filemtime(dirname(__FILE__) . '/assets/jar-viewer.css')
        );

        // Critical layout guard: keeps the story full-width even if a cached
        // copy of the stylesheet is served by the browser.
        wp_add_inline_style(
            'jar-viewer',
            '.jp-viewer{width:100%!important;max-width:none!important;margin-left:0!important;margin-right:0!important;}'
        );
    }

    /**
     * Default storytelling panels, also exposed to the Elementor repeater.
     */
    public static function default_panels()
    {
        return [
            [
                'index' => '01',
                'title' => 'Hydratation profonde',
                'text'  => 'Une texture riche en beurre de karité qui nourrit la peau pendant 24 heures, sans effet gras.',
                'side'  => 'right',
            ],
            [
                'index' => '02',
                'title' => 'Éclat naturel',
                'text'  => 'Des actifs d’origine naturelle ravivent le teint et révèlent la luminosité de votre peau.',
                'side'  => 'left',
            ],
            [
                'index' => '03',
                'title' => 'Texture fondante',
                'text'  => 'La crème fond instantanément et laisse un fini satiné, jamais collant, dès la première application.',
                'side'  => 'right',
            ],
            [
                'index' => '04',
                'title' => 'Rituel précieux',
                'text'  => 'Un moment de soin sensoriel à intégrer matin et soir à votre routine, pour une peau souple et lumineuse.',
                'side'  => 'left',
            ],
        ];
    }

    /**
     * Default product cards, also exposed to the Elementor repeater.
     */
    public static function default_products()
    {
        return [
            ['name' => 'Breath of Purity Honey and Musk', 'note' => 'Honey & Musk', 'tone' => 'red'],
            ['name' => 'Precious Oils Shea Butter and Oud', 'note' => 'Shea Butter & Oud', 'tone' => 'gold'],
        ];
    }

    /**
     * Build the full markup. Shared by the shortcode and the Elementor widget.
     *
     * @param array $args
     * @return string
     */
    public static function markup(array $args)
    {
        $defaults = [
            'eyebrow'       => 'Soin corps · 200 ml',
            'brand'         => 'ABEER',
            'product'       => 'Breath of Honey',
            'tagline'       => 'Le soin corps signature, riche en actifs marocains.',
            'shop_url'      => home_url('/go/shopify/?campaign=jar-page'),
            'shop_label'    => 'Boutique Shopify',
            'whatsapp'      => '',
            'whatsapp_url'  => 'https://wa.me/message/XLJAND4I3ZWZJ1',
            'whatsapp_label' => 'WhatsApp',
            'whatsapp_text' => 'Bonjour ABEER, je souhaite commander la Breath of Honey.',
            'product_link_label' => 'Découvrir',
            'nav_brand'     => 'ABEER',
            'nav_accueil'   => 'Accueil',
            'nav_soin'      => 'Soin',
            'nav_produits'  => 'Produits',
            'nav_contact'   => 'Contact',
            'address'       => 'Ménara Mall, Marrakech',
            'map_embed'     => 'https://maps.google.com/maps?q=31.6186366,-8.0092535&z=16&output=embed',
            'instagram_url' => 'https://instagram.com/abeermoroccanskincare/',
            'tiktok_url'    => 'https://www.tiktok.com/@abeermoroccanskincare',
            'website_url'   => 'http://www.abeerms.com',
            'show_whatsapp' => 1,
            'show_instagram' => 1,
            'show_tiktok'   => 1,
            'show_website'  => 1,
            'footer_text'   => 'Site réalisé par',
            'footer_name'   => 'Adnane Rouimi',
            'linkedin_url'  => 'https://www.linkedin.com/in/adnane-rouimi-652209233',
            'panels'        => self::default_panels(),
            'products'      => self::default_products(),
            'initial_rotation' => 270,
            'collection'    => 'La collection',
            'products_title' => 'Découvrez les autres soins ABEER',
            'products_lead' => 'Des formules pensées pour prolonger le rituel, du bain au soin quotidien.',
        ];

        $args = array_merge($defaults, $args);

        $panels = apply_filters('jar_product_viewer_panels', $args['panels'], $args);
        $products = apply_filters('jar_product_viewer_products', $args['products'], $args);

        $wa_number = preg_replace('/\D+/', '', (string) $args['whatsapp']);
        $wa_url = !empty($args['whatsapp_url'])
            ? $args['whatsapp_url']
            : ($wa_number ? 'https://wa.me/' . $wa_number . '?text=' . rawurlencode($args['whatsapp_text']) : '');

        ob_start();
        ?>
        <div class="jp-viewer" id="jp-top" data-initial-rotation="<?php echo esc_attr($args['initial_rotation']); ?>">
            <nav class="jp-nav" data-jp-nav aria-label="Navigation principale">
                <a class="jp-nav__brand" href="#jp-top"><?php echo esc_html($args['nav_brand']); ?></a>
                <div class="jp-nav__links">
                    <a href="#jp-top"><?php echo esc_html($args['nav_accueil']); ?></a>
                    <a href="#jp-story"><?php echo esc_html($args['nav_soin']); ?></a>
                    <a href="#jp-products"><?php echo esc_html($args['nav_produits']); ?></a>
                    <a href="#jp-contact"><?php echo esc_html($args['nav_contact']); ?></a>
                </div>
            </nav>

            <section class="jp-stage" id="jp-story" data-jp-stage>
                <div class="jp-sticky">
                    <div class="jp-canvas" aria-hidden="true"></div>

                    <header class="jp-hero">
                        <p class="jp-eyebrow"><?php echo esc_html($args['eyebrow']); ?></p>
                        <p class="jp-hero__brand"><?php echo esc_html($args['brand']); ?></p>
                        <h1 class="jp-hero__product"><?php echo esc_html($args['product']); ?></h1>
                        <p class="jp-hero__tagline"><?php echo esc_html($args['tagline']); ?></p>
                        <span class="jp-hero__cue" aria-hidden="true">Défiler</span>
                    </header>

                    <div class="jp-panels">
                        <?php foreach ($panels as $panel) : ?>
                            <?php $side = in_array($panel['side'] ?? 'right', ['left', 'right'], true) ? $panel['side'] : 'right'; ?>
                            <article class="jp-panel jp-panel--<?php echo esc_attr($side); ?>" data-jp-panel>
                                <span class="jp-panel__index"><?php echo esc_html($panel['index'] ?? ''); ?></span>
                                <h2 class="jp-panel__title"><?php echo esc_html($panel['title'] ?? ''); ?></h2>
                                <p class="jp-panel__text"><?php echo esc_html($panel['text'] ?? ''); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="jp-runway" aria-hidden="true"></div>
            </section>

            <section class="jp-products" id="jp-products">
                <div class="jp-products__inner">
                    <p class="jp-eyebrow"><?php echo esc_html($args['collection']); ?></p>
                    <h2 class="jp-products__title"><?php echo esc_html($args['products_title']); ?></h2>
                    <p class="jp-products__lead"><?php echo esc_html($args['products_lead']); ?></p>

                    <div class="jp-products__grid">
                        <?php foreach ($products as $item) : ?>
                            <?php $tone = in_array($item['tone'] ?? 'red', ['red', 'gold', 'dark'], true) ? $item['tone'] : 'red'; ?>
                            <article class="jp-product jp-product--<?php echo esc_attr($tone); ?>">
                                <div class="jp-product__media" aria-hidden="true">
                                    <?php if (!empty($item['image_url'])) : ?>
                                        <img class="jp-product__img" src="<?php echo esc_url($item['image_url']); ?>" alt="<?php echo esc_attr($item['name'] ?? ''); ?>" loading="lazy">
                                    <?php else : ?>
                                        <span class="jp-product__jar"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="jp-product__body">
                                    <h3 class="jp-product__name"><?php echo esc_html($item['name'] ?? ''); ?></h3>
                                    <p class="jp-product__note"><?php echo esc_html($item['note'] ?? ''); ?></p>
                                    <a class="jp-product__link" href="<?php echo esc_url($args['shop_url']); ?>"><?php echo esc_html($args['product_link_label']); ?></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="jp-products__actions">
                        <a class="jp-btn jp-btn--shop" href="<?php echo esc_url($args['shop_url']); ?>">
                            <svg class="jp-btn__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8h12l-1.2 12H7.2L6 8z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/></svg>
                            <span><?php echo esc_html($args['shop_label']); ?></span>
                        </a>
                        <?php if ($wa_url && !empty($args['show_whatsapp'])) : ?>
                            <a class="jp-btn jp-btn--wa" href="<?php echo esc_url($wa_url); ?>" target="_blank" rel="noopener noreferrer">
                                <svg class="jp-btn__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.4A10 10 0 1 0 12 2zm0 18.2c-1.5 0-2.9-.4-4.1-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A8.2 8.2 0 1 1 12 20.2z"/><path d="M17.4 14.4c-.3-.2-1.8-.9-2-.9-.3-.1-.5-.2-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.2-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3z"/></svg>
                                <span><?php echo esc_html($args['whatsapp_label']); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="jp-contact" id="jp-contact">
                <div class="jp-contact__inner">
                    <div class="jp-contact__copy">
                        <p class="jp-eyebrow">Nous trouver</p>
                        <h2 class="jp-contact__title">Venez découvrir ABEER à Marrakech</h2>
                        <p class="jp-contact__address">
                            <svg class="jp-contact__pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            <span><?php echo esc_html($args['address']); ?></span>
                        </p>
                        <div class="jp-social">
                            <?php if ($wa_url && !empty($args['show_whatsapp'])) : ?>
                                <a class="jp-social__link jp-social__link--wa" href="<?php echo esc_url($wa_url); ?>" target="_blank" rel="noopener noreferrer">
                                    <svg class="jp-social__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.4A10 10 0 1 0 12 2zm0 18.2c-1.5 0-2.9-.4-4.1-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A8.2 8.2 0 1 1 12 20.2z"/><path d="M17.4 14.4c-.3-.2-1.8-.9-2-.9-.3-.1-.5-.2-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.2-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3z"/></svg>
                                    <span>WhatsApp</span>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($args['instagram_url']) && !empty($args['show_instagram'])) : ?>
                                <a class="jp-social__link" href="<?php echo esc_url($args['instagram_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <svg class="jp-social__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.4" cy="6.6" r="1" fill="currentColor" stroke="none"/></svg>
                                    <span>Instagram</span>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($args['tiktok_url']) && !empty($args['show_tiktok'])) : ?>
                                <a class="jp-social__link" href="<?php echo esc_url($args['tiktok_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <svg class="jp-social__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 3h2.2c.2 1.7 1.2 2.9 3 3.3v2.2c-1.2-.1-2.2-.5-3-1.2v8.2a5 5 0 1 1-5-5c.2 0 .5 0 .7.05v2.3a2.7 2.7 0 1 0 1.9 2.6V3z"/></svg>
                                    <span>TikTok</span>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($args['website_url']) && !empty($args['show_website'])) : ?>
                                <a class="jp-social__link" href="<?php echo esc_url($args['website_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <svg class="jp-social__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18M12 3c-2.5 2.5-2.5 15 0 18"/></svg>
                                    <span>Site web</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="jp-contact__map">
                        <iframe src="<?php echo esc_url($args['map_embed']); ?>" loading="lazy" title="Localisation ABEER — <?php echo esc_attr($args['address']); ?>" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        <button class="jp-contact__map-overlay" type="button" data-jp-map-overlay>Cliquez pour interagir avec la carte</button>
                    </div>
                </div>
            </section>

            <footer class="jp-footer">
                <p class="jp-footer__text">
                    <?php echo esc_html($args['footer_text']); ?>
                    <a class="jp-footer__name" href="<?php echo esc_url($args['linkedin_url']); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html($args['footer_name']); ?>
                        <svg class="jp-footer__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4.98 3.5A2.5 2.5 0 1 1 0 3.5a2.5 2.5 0 0 1 4.98 0zM.5 8h4V24h-4V8zm7.5 0h3.8v2.2h.05c.53-1 1.83-2.2 3.77-2.2 4.03 0 4.78 2.65 4.78 6.1V24h-4v-7.1c0-1.7-.03-3.9-2.38-3.9-2.38 0-2.74 1.86-2.74 3.78V24H8V8z"/></svg>
                    </a>
                </p>
            </footer>

            <div class="jp-cursor" aria-hidden="true">
                <span class="jp-cursor__ring"></span>
                <span class="jp-cursor__dot"></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode renderer. Attributes (all optional):
     *  - brand, product, tagline, eyebrow, shop_url, whatsapp, whatsapp_text
     */
    public function render($atts)
    {
        $atts = shortcode_atts([
            'brand'         => 'ABEER',
            'product'       => 'Breath of Honey',
            'tagline'       => 'Le soin corps signature, riche en actifs marocains.',
            'eyebrow'       => 'Soin corps · 200 ml',
            'shop_url'      => home_url('/go/shopify/?campaign=jar-page'),
            'shop_label'    => 'Boutique Shopify',
            'whatsapp'      => '212600000000',
            'whatsapp_label' => 'WhatsApp',
            'whatsapp_text' => 'Bonjour ABEER, je souhaite commander la Breath of Honey.',
            'product_link_label' => 'Découvrir',
        ], $atts, 'jar_product_viewer');

        return self::markup($atts);
    }
}

new Jar_Product_Viewer();

/**
 * Elementor widget: every text, panel, product, and button is editable
 * from the Elementor panel.
 */
add_action('elementor/widgets/register', function ($widgets_manager) {
    require_once __DIR__ . '/includes/elementor-widget.php';

    if (class_exists('Abeer_Jar_Viewer_Widget')) {
        $widgets_manager->register(new Abeer_Jar_Viewer_Widget());
    }
});
