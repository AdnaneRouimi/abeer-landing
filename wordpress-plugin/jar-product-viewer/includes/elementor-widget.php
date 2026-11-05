<?php
/**
 * Elementor widget for the ABEER 3D product story.
 *
 * All copy, panels, products, and buttons are editable from the Elementor
 * panel. The scroll choreography and the 3D scene stay automatic.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Abeer_Jar_Viewer_Widget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'abeer_jar_viewer';
    }

    public function get_title()
    {
        return 'ABEER 3D Product Story';
    }

    public function get_icon()
    {
        return 'eicon-3d';
    }

    public function get_categories()
    {
        return ['general'];
    }

    public function get_keywords()
    {
        return ['abeer', 'jar', '3d', 'product', 'scroll', 'shopify'];
    }

    protected function register_controls()
    {
        $this->start_controls_section('section_hero', [
            'label' => 'Hero',
        ]);

        $this->add_control('eyebrow', [
            'label'       => 'Sur-titre',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Soin corps · 200 ml',
            'label_block' => true,
        ]);

        $this->add_control('brand', [
            'label'       => 'Marque',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'ABEER',
            'label_block' => true,
        ]);

        $this->add_control('nav_brand', [
            'label'   => 'Logo / texte du menu',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'ABEER',
        ]);

        $this->add_control('nav_heading', [
            'label'     => 'Liens du menu',
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('nav_accueil', [
            'label'   => 'Lien 1',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Accueil',
        ]);

        $this->add_control('nav_soin', [
            'label'   => 'Lien 2',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Soin',
        ]);

        $this->add_control('nav_produits', [
            'label'   => 'Lien 3',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Produits',
        ]);

        $this->add_control('nav_contact', [
            'label'   => 'Lien 4',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Contact',
        ]);

        $this->add_control('product', [
            'label'       => 'Nom du produit',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Breath of Honey',
            'label_block' => true,
        ]);

        $this->add_control('tagline', [
            'label'       => 'Accroche',
            'type'        => \Elementor\Controls_Manager::TEXTAREA,
            'default'     => 'Le soin corps signature, riche en actifs marocains.',
            'rows'        => 2,
        ]);

        $this->add_control('initial_rotation', [
            'label'   => 'Rotation initiale (°)',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'default' => ['size' => 270],
            'range'   => [
                'px' => ['min' => -180, 'max' => 180, 'step' => 5],
            ],
            'description' => 'Ajustez pour que le devant du pot fasse face à la caméra au départ.',
        ]);

        $this->end_controls_section();

        // ---- Story panels -------------------------------------------------
        $this->start_controls_section('section_panels', [
            'label' => 'Panneaux (histoire)',
        ]);

        $panel_controls = new \Elementor\Repeater();

        $panel_controls->add_control('panel_index', [
            'label'       => 'Numéro',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => '01',
            'description' => 'Laissez vide pour numéroter automatiquement.',
        ]);

        $panel_controls->add_control('panel_title', [
            'label'       => 'Titre',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Hydratation profonde',
            'label_block' => true,
        ]);

        $panel_controls->add_control('panel_text', [
            'label' => 'Texte',
            'type'  => \Elementor\Controls_Manager::TEXTAREA,
            'rows'  => 3,
            'default' => 'Une texture riche en beurre de karité qui nourrit la peau pendant 24 heures, sans effet gras.',
        ]);

        $panel_controls->add_control('panel_side', [
            'label'   => 'Côté',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'right' => 'Droite',
                'left'  => 'Gauche',
            ],
            'default' => 'right',
        ]);

        $this->add_control('panels', [
            'label'       => 'Panneaux',
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $panel_controls->get_controls(),
            'title_field' => '{{{ panel_title }}}',
            'default'     => [
                [
                    'panel_index' => '01',
                    'panel_title' => 'Hydratation profonde',
                    'panel_text'  => 'Une texture riche en beurre de karité qui nourrit la peau pendant 24 heures, sans effet gras.',
                    'panel_side'  => 'right',
                ],
                [
                    'panel_index' => '02',
                    'panel_title' => 'Éclat naturel',
                    'panel_text'  => 'Des actifs d’origine naturelle ravivent le teint et révèlent la luminosité de votre peau.',
                    'panel_side'  => 'left',
                ],
                [
                    'panel_index' => '03',
                    'panel_title' => 'Texture fondante',
                    'panel_text'  => 'La crème fond instantanément et laisse un fini satiné, jamais collant, dès la première application.',
                    'panel_side'  => 'right',
                ],
                [
                    'panel_index' => '04',
                    'panel_title' => 'Rituel précieux',
                    'panel_text'  => 'Un moment de soin sensoriel à intégrer matin et soir à votre routine, pour une peau souple et lumineuse.',
                    'panel_side'  => 'left',
                ],
            ],
        ]);

        $this->add_control('panels_note', [
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw'             => 'Le nombre de panneaux pilote automatiquement la durée de l\'histoire : chaque panneau apparaît pendant que le pot tourne.',
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
        ]);

        $this->end_controls_section();

        // ---- Products ------------------------------------------------------
        $this->start_controls_section('section_products', [
            'label' => 'Autres produits',
        ]);

        $product_controls = new \Elementor\Repeater();

        $product_controls->add_control('product_name', [
            'label'       => 'Nom',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Breath of Honey',
            'label_block' => true,
        ]);

        $product_controls->add_control('product_note', [
            'label'       => 'Sous-titre',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Le soin signature',
            'label_block' => true,
        ]);

        $product_controls->add_control('product_tone', [
            'label'   => 'Couleur',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'red'  => 'Rouge ABEER',
                'gold' => 'Or',
                'dark' => 'Noir',
            ],
            'default' => 'red',
        ]);

        $product_controls->add_control('product_image', [
            'label'   => 'Image du produit',
            'type'    => \Elementor\Controls_Manager::MEDIA,
            'default' => [],
        ]);

        $this->add_control('products', [
            'label'       => 'Produits',
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $product_controls->get_controls(),
            'title_field' => '{{{ product_name }}}',
            'default'     => [
                ['product_name' => 'Breath of Purity Honey and Musk', 'product_note' => 'Honey & Musk', 'product_tone' => 'red'],
                ['product_name' => 'Precious Oils Shea Butter and Oud', 'product_note' => 'Shea Butter & Oud', 'product_tone' => 'gold'],
            ],
        ]);

        $this->add_control('collection', [
            'label'   => 'Sur-titre de la section',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'La collection',
        ]);

        $this->add_control('products_title', [
            'label'   => 'Titre de la section',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Découvrez les autres soins ABEER',
            'label_block' => true,
        ]);

        $this->add_control('products_lead', [
            'label'   => 'Texte de la section',
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'rows'    => 2,
            'default' => 'Des formules pensées pour prolonger le rituel, du bain au soin quotidien.',
        ]);

        $this->add_control('product_link_label', [
            'label'   => 'Texte du lien produit',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Découvrir',
        ]);

        $this->end_controls_section();

        // ---- Buttons -------------------------------------------------------
        $this->start_controls_section('section_links', [
            'label' => 'Boutons',
        ]);

        $this->add_control('shop_url', [
            'label'       => 'Lien boutique Shopify',
            'type'        => \Elementor\Controls_Manager::URL,
            'default'     => [
                'url' => home_url('/go/shopify/?campaign=jar-page'),
            ],
            'label_block' => true,
            'description' => 'Laissez la valeur par défaut pour conserver le suivi des clics.',
        ]);

        $this->add_control('shop_label', [
            'label'       => 'Texte du bouton Shopify',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Boutique Shopify',
            'label_block' => true,
        ]);

        $this->add_control('whatsapp_label', [
            'label'       => 'Texte du bouton WhatsApp',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'WhatsApp',
            'label_block' => true,
        ]);

        $this->add_control('whatsapp_url', [
            'label'       => 'Lien WhatsApp',
            'type'        => \Elementor\Controls_Manager::URL,
            'default'     => [
                'url' => 'https://wa.me/message/XLJAND4I3ZWZJ1',
            ],
            'label_block' => true,
            'description' => 'Lien direct WhatsApp (wa.me/...). Laissez vide pour utiliser le numéro ci-dessous.',
        ]);

        $this->add_control('whatsapp', [
            'label'       => 'Numéro WhatsApp (optionnel)',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => '2126XXXXXXXX',
            'label_block' => true,
            'description' => 'Utilisé seulement si le lien WhatsApp est vide.',
        ]);

        $this->add_control('whatsapp_text', [
            'label'   => 'Message WhatsApp prérempli',
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'rows'    => 2,
            'default' => 'Bonjour ABEER, je souhaite commander la Breath of Honey.',
        ]);

        $this->end_controls_section();

        // ---- Contact / social ---------------------------------------------
        $this->start_controls_section('section_contact', [
            'label' => 'Contact / Réseaux',
        ]);

        $this->add_control('address', [
            'label'       => 'Adresse',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Ménara Mall, Marrakech',
            'label_block' => true,
        ]);

        $this->add_control('map_embed', [
            'label'       => 'URL de la carte (embed)',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'https://maps.google.com/maps?q=31.6186366,-8.0092535&z=16&output=embed',
            'label_block' => true,
            'description' => 'Collez une URL Google Maps terminée par &output=embed.',
        ]);

        $this->add_control('social_heading', [
            'label'     => 'Boutons réseaux',
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('show_whatsapp', [
            'label'   => 'Afficher WhatsApp',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('show_instagram', [
            'label'   => 'Afficher Instagram',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('show_tiktok', [
            'label'   => 'Afficher TikTok',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('show_website', [
            'label'   => 'Afficher Site web',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('instagram_url', [
            'label'       => 'Instagram',
            'type'        => \Elementor\Controls_Manager::URL,
            'default'     => ['url' => 'https://instagram.com/abeermoroccanskincare/'],
            'label_block' => true,
        ]);

        $this->add_control('tiktok_url', [
            'label'       => 'TikTok',
            'type'        => \Elementor\Controls_Manager::URL,
            'default'     => ['url' => 'https://www.tiktok.com/@abeermoroccanskincare'],
            'label_block' => true,
        ]);

        $this->add_control('website_url', [
            'label'       => 'Site web',
            'type'        => \Elementor\Controls_Manager::URL,
            'default'     => ['url' => 'http://www.abeerms.com'],
            'label_block' => true,
        ]);

        $this->end_controls_section();

        // ---- Footer --------------------------------------------------------
        $this->start_controls_section('section_footer', [
            'label' => 'Pied de page',
        ]);

        $this->add_control('footer_text', [
            'label'   => 'Texte',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Site réalisé par',
        ]);

        $this->add_control('footer_name', [
            'label'   => 'Nom',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Adnane Rouimi',
        ]);

        $this->add_control('linkedin_url', [
            'label'       => 'Lien LinkedIn',
            'type'        => \Elementor\Controls_Manager::URL,
            'default'     => ['url' => 'https://www.linkedin.com/in/adnane-rouimi-652209233'],
            'label_block' => true,
        ]);

        $this->end_controls_section();

        // ---- Style ---------------------------------------------------------
        $this->start_controls_section('section_style', [
            'label' => 'Style',
        ]);

        $this->add_control('accent_color', [
            'label'     => 'Couleur d\'accent',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#8a1622',
            'selectors' => [
                '{{WRAPPER}} .jp-viewer' => '--jp-red: {{VALUE}}; --jp-red-dark: {{VALUE}};',
            ],
        ]);

        $this->add_control('gold_color', [
            'label'     => 'Couleur or',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#c9a227',
            'selectors' => [
                '{{WRAPPER}} .jp-viewer' => '--jp-gold: {{VALUE}};',
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        $panels = [];
        foreach ((array) $settings['panels'] as $index => $panel) {
            $number = trim((string) ($panel['panel_index'] ?? ''));
            $panels[] = [
                'index' => '' !== $number ? $number : sprintf('%02d', $index + 1),
                'title' => $panel['panel_title'] ?? '',
                'text'  => $panel['panel_text'] ?? '',
                'side'  => $panel['panel_side'] ?? 'right',
            ];
        }

        $products = [];
        foreach ((array) $settings['products'] as $product) {
            $image = $product['product_image'] ?? '';
            $image_url = '';
            $image_id = '';
            if (is_array($image)) {
                $image_url = !empty($image['url']) ? $image['url'] : '';
                $image_id = $image['id'] ?? '';
            } elseif (is_numeric($image)) {
                $image_id = $image;
            } elseif (is_string($image) && '' !== $image) {
                $image_url = $image;
            }
            if (!$image_url && $image_id && is_numeric($image_id)) {
                $image_url = wp_get_attachment_image_url((int) $image_id, 'full');
            }
            $products[] = [
                'name'      => $product['product_name'] ?? '',
                'note'      => $product['product_note'] ?? '',
                'tone'      => $product['product_tone'] ?? 'red',
                'image_url' => $image_url,
            ];
        }

        $shop_url = !empty($settings['shop_url']['url'])
            ? $settings['shop_url']['url']
            : home_url('/go/shopify/?campaign=jar-page');

        echo Jar_Product_Viewer::markup([
            'eyebrow'        => $settings['eyebrow'],
            'brand'          => $settings['brand'],
            'product'        => $settings['product'],
            'tagline'        => $settings['tagline'],
            'panels'         => $panels,
            'products'       => $products,
            'collection'     => $settings['collection'],
            'products_title' => $settings['products_title'],
            'products_lead'  => $settings['products_lead'],
            'shop_url'       => $shop_url,
            'shop_label'     => $settings['shop_label'],
            'whatsapp'       => $settings['whatsapp'],
            'whatsapp_url'   => $settings['whatsapp_url']['url'] ?? '',
            'whatsapp_label' => $settings['whatsapp_label'],
            'whatsapp_text'  => $settings['whatsapp_text'],
            'product_link_label' => $settings['product_link_label'],
            'nav_brand'      => $settings['nav_brand'],
            'nav_accueil'    => $settings['nav_accueil'],
            'nav_soin'       => $settings['nav_soin'],
            'nav_produits'   => $settings['nav_produits'],
            'nav_contact'    => $settings['nav_contact'],
            'address'        => $settings['address'],
            'map_embed'      => $settings['map_embed'],
            'instagram_url'  => $settings['instagram_url']['url'] ?? '',
            'tiktok_url'     => $settings['tiktok_url']['url'] ?? '',
            'website_url'    => $settings['website_url']['url'] ?? '',
            'show_whatsapp'  => ('yes' === ($settings['show_whatsapp'] ?? 'yes')) ? 1 : 0,
            'show_instagram' => ('yes' === ($settings['show_instagram'] ?? 'yes')) ? 1 : 0,
            'show_tiktok'    => ('yes' === ($settings['show_tiktok'] ?? 'yes')) ? 1 : 0,
            'show_website'   => ('yes' === ($settings['show_website'] ?? 'yes')) ? 1 : 0,
            'footer_text'    => $settings['footer_text'],
            'footer_name'    => $settings['footer_name'],
            'linkedin_url'   => $settings['linkedin_url']['url'] ?? '',
            'initial_rotation' => $settings['initial_rotation']['size'] ?? 270,
        ]);
    }
}
