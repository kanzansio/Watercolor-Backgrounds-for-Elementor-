<?php
/**
 * Plugin Name: Watercolor Backgrounds for Elementor
 * Plugin URI: https://kanzansio.digital/
 * Description: Fondos de acuarela animados para Elementor 
 * Version: 3.0.0
 * Author: Kanzansio.Digital
 * Text Domain: watercolor-bg
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Elementor tested up to: 3.28
 * Elementor Pro tested up to: 3.28
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WATERCOLOR_BG_VERSION', '3.0.0');
define('WATERCOLOR_BG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WATERCOLOR_BG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WATERCOLOR_BG_MINIMUM_ELEMENTOR_VERSION', '3.0.0');

final class WatercolorBackgroundPlugin {
    
    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('plugins_loaded', array($this, 'on_plugins_loaded'));
    }

    public function on_plugins_loaded() {
        if ($this->is_compatible()) {
            add_action('elementor/init', array($this, 'init'));
        }
    }

    public function is_compatible() {
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', array($this, 'admin_notice_missing_main_plugin'));
            return false;
        }

        // Check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, WATERCOLOR_BG_MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', array($this, 'admin_notice_minimum_elementor_version'));
            return false;
        }

        return true;
    }

    public function init() {
        // Load textdomain
        add_action('init', array($this, 'i18n'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'widget_scripts'));
        add_action('elementor/frontend/after_enqueue_styles', array($this, 'widget_styles'));
        add_action('elementor/preview/enqueue_styles', array($this, 'widget_styles'));
        add_action('elementor/editor/after_enqueue_scripts', array($this, 'editor_scripts'));

        // Register controls injection
        $this->register_controls_injection();
    }

    public function i18n() {
        load_plugin_textdomain('watercolor-bg', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function widget_scripts() {
        wp_enqueue_script(
            'watercolor-bg-frontend',
            WATERCOLOR_BG_PLUGIN_URL . 'assets/watercolor-frontend.js',
            array('jquery'),
            WATERCOLOR_BG_VERSION,
            true
        );

        wp_localize_script('watercolor-bg-frontend', 'watercolorBg', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('watercolor_bg_nonce'),
        ));
    }

    public function widget_styles() {
        wp_enqueue_style(
            'watercolor-bg-style',
            WATERCOLOR_BG_PLUGIN_URL . 'assets/watercolor-bg.css',
            array(),
            WATERCOLOR_BG_VERSION
        );
    }

    public function editor_scripts() {
        // Enqueue preview styles in editor
        wp_enqueue_style(
            'watercolor-bg-editor-preview',
            WATERCOLOR_BG_PLUGIN_URL . 'assets/watercolor-bg.css',
            array(),
            WATERCOLOR_BG_VERSION
        );

        wp_enqueue_script(
            'watercolor-bg-editor',
            WATERCOLOR_BG_PLUGIN_URL . 'assets/watercolor-editor.js',
            array('jquery', 'elementor-editor'),
            WATERCOLOR_BG_VERSION,
            true
        );

        wp_localize_script('watercolor-bg-editor', 'watercolorEditor', array(
            'version' => WATERCOLOR_BG_VERSION,
        ));
    }

    private function register_controls_injection() {
        // Hook into elements to add controls
        add_action('elementor/element/section/section_background/before_section_end', array($this, 'register_watercolor_controls'), 10, 2);
        add_action('elementor/element/container/section_background/before_section_end', array($this, 'register_watercolor_controls'), 10, 2);
        add_action('elementor/element/column/section_background/before_section_end', array($this, 'register_watercolor_controls'), 10, 2);

        // Hook into rendering
        add_action('elementor/frontend/section/before_render', array($this, 'before_render_element'));
        add_action('elementor/frontend/container/before_render', array($this, 'before_render_element'));
        add_action('elementor/frontend/column/before_render', array($this, 'before_render_element'));
    }

    public function register_watercolor_controls($element, $args) {
        $element->add_control(
            'watercolor_divider',
            array(
                'type' => \Elementor\Controls_Manager::DIVIDER,
                'style' => 'thick',
            )
        );

        $element->add_control(
            'watercolor_heading',
            array(
                'label' => esc_html__('🎨 Fondo de Acuarela', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::HEADING,
            )
        );

        // Enable/Disable control
        $element->add_control(
            'watercolor_enable',
            array(
                'label' => esc_html__('Activar Fondo de Acuarela', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Sí', 'watercolor-bg'),
                'label_off' => esc_html__('No', 'watercolor-bg'),
                'return_value' => 'yes',
                'default' => '',
                'frontend_available' => true,
                'render_type' => 'ui',
            )
        );

        // Effect type
        $element->add_control(
            'watercolor_effect',
            array(
                'label' => esc_html__('Tipo de Efecto', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'acuarela',
                'options' => array(
                    'acuarela' => esc_html__('💧 Acuarela', 'watercolor-bg'),
                    'barrido' => esc_html__('🌊 Barrido', 'watercolor-bg'),
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
                'frontend_available' => true,
                'render_type' => 'ui',
            )
        );

        // Color controls heading
        $element->add_control(
            'watercolor_colors_heading',
            array(
                'label' => esc_html__('🎨 Colores', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );

        // Base color
        $element->add_control(
            'watercolor_base_color',
            array(
                'label' => esc_html__('Color de Fondo Base', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
                'frontend_available' => true,
                'render_type' => 'ui',
            )
        );

        // Color 1
        $element->add_control(
            'watercolor_color_1',
            array(
                'label' => esc_html__('Color Principal', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#87CEEB',
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
                'frontend_available' => true,
                'render_type' => 'ui',
            )
        );

        // Color 2
        $element->add_control(
            'watercolor_color_2',
            array(
                'label' => esc_html__('Color Secundario', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FFB6C1',
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
                'frontend_available' => true,
                'render_type' => 'ui',
            )
        );

        // Settings heading
        $element->add_control(
            'watercolor_settings_heading',
            array(
                'label' => esc_html__('⚙️ Configuración', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );

        // Opacity
        $element->add_control(
            'watercolor_opacity',
            array(
                'label' => esc_html__('Opacidad', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('%'),
                'range' => array(
                    '%' => array(
                        'min' => 10,
                        'max' => 100,
                        'step' => 1,
                    ),
                ),
                'default' => array(
                    'unit' => '%',
                    'size' => 60,
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
                'frontend_available' => true,
                'render_type' => 'ui',
            )
        );

        // Animation speed
        $element->add_control(
            'watercolor_animation_speed',
            array(
                'label' => esc_html__('Velocidad de Animación', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'min' => 5,
                        'max' => 60,
                        'step' => 1,
                    ),
                ),
                'default' => array(
                    'size' => 20,
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
                'frontend_available' => true,
                'render_type' => 'ui',
            )
        );

        // Blur intensity
        $element->add_control(
            'watercolor_blur',
            array(
                'label' => esc_html__('Intensidad del Desenfoque', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'min' => 10,
                        'max' => 100,
                        'step' => 5,
                    ),
                ),
                'default' => array(
                    'size' => 40,
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
                'frontend_available' => true,
                'render_type' => 'ui',
            )
        );
    }

    public function before_render_element($element) {
        $settings = $element->get_settings_for_display();
        
        if (!empty($settings['watercolor_enable']) && $settings['watercolor_enable'] === 'yes') {
            $element_id = $element->get_id();
            
            // Add classes for styling
            $element->add_render_attribute('_wrapper', 'class', array(
                'watercolor-active',
                'watercolor-element-' . $element_id,
                'watercolor-effect-' . ($settings['watercolor_effect'] ?? 'acuarela')
            ));
            
            // Add data attributes for JS
            $element->add_render_attribute('_wrapper', 'data-watercolor-settings', wp_json_encode($settings));
            
            // Generate and inject CSS
            $this->inject_element_styles($element_id, $settings);
        }
    }

    private function inject_element_styles($element_id, $settings) {
        $css = $this->generate_watercolor_css($element_id, $settings);
        
        // Add inline style
        wp_add_inline_style('watercolor-bg-style', $css);
        
        // Also add to head for editor preview
        if (is_admin() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
            add_action('wp_head', function() use ($css) {
                echo "<style>{$css}</style>";
            }, 999);
        }
    }

    private function generate_watercolor_css($element_id, $settings) {
        // Extract settings
        $base_color = $settings['watercolor_base_color'] ?? '#ffffff';
        $color1 = $settings['watercolor_color_1'] ?? '#87CEEB';
        $color2 = $settings['watercolor_color_2'] ?? '#FFB6C1';
        $opacity = isset($settings['watercolor_opacity']['size']) ? $settings['watercolor_opacity']['size'] / 100 : 0.6;
        $speed = isset($settings['watercolor_animation_speed']['size']) ? $settings['watercolor_animation_speed']['size'] : 20;
        $blur = isset($settings['watercolor_blur']['size']) ? $settings['watercolor_blur']['size'] : 40;
        $effect = $settings['watercolor_effect'] ?? 'acuarela';

        // Generate CSS based on effect
        if ($effect === 'barrido') {
            return $this->generate_barrido_css($element_id, $base_color, $color1, $color2, $opacity, $speed, $blur);
        } else {
            return $this->generate_acuarela_css($element_id, $base_color, $color1, $color2, $opacity, $speed, $blur);
        }
    }

    private function generate_acuarela_css($element_id, $base_color, $color1, $color2, $opacity, $speed, $blur) {
        $rgba1 = $this->hex_to_rgba($color1, $opacity);
        $rgba2 = $this->hex_to_rgba($color2, $opacity);
        
        return "
        .watercolor-element-{$element_id} {
            position: relative !important;
            background-color: {$base_color} !important;
            overflow: hidden !important;
        }
        
        .watercolor-element-{$element_id}:before,
        .watercolor-element-{$element_id}:after {
            content: '' !important;
            position: absolute !important;
            width: 150% !important;
            height: 150% !important;
            top: -25% !important;
            left: -25% !important;
            z-index: 0 !important;
            pointer-events: none !important;
            mix-blend-mode: multiply;
            filter: blur({$blur}px) !important;
        }
        
        .watercolor-element-{$element_id}:before {
            background: radial-gradient(circle at 30% 40%, {$rgba1} 0%, transparent 50%),
                        radial-gradient(circle at 70% 60%, {$rgba1} 0%, transparent 40%) !important;
            animation: watercolor-acuarela-1-{$element_id} {$speed}s ease-in-out infinite !important;
        }
        
        .watercolor-element-{$element_id}:after {
            background: radial-gradient(circle at 60% 30%, {$rgba2} 0%, transparent 50%),
                        radial-gradient(circle at 40% 70%, {$rgba2} 0%, transparent 40%) !important;
            animation: watercolor-acuarela-2-{$element_id} " . ($speed * 1.5) . "s ease-in-out infinite !important;
        }
        
        .watercolor-element-{$element_id} > * {
            position: relative !important;
            z-index: 10 !important;
        }
        
        @keyframes watercolor-acuarela-1-{$element_id} {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg) scale(1);
            }
            33% {
                transform: translate(2%, -3%) rotate(1deg) scale(1.02);
            }
            66% {
                transform: translate(-1%, 2%) rotate(-1deg) scale(0.98);
            }
        }
        
        @keyframes watercolor-acuarela-2-{$element_id} {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg) scale(1);
            }
            33% {
                transform: translate(-2%, 1%) rotate(-1deg) scale(0.98);
            }
            66% {
                transform: translate(3%, -2%) rotate(1deg) scale(1.03);
            }
        }
        ";
    }

    private function generate_barrido_css($element_id, $base_color, $color1, $color2, $opacity, $speed, $blur) {
        $rgba1 = $this->hex_to_rgba($color1, $opacity);
        $rgba2 = $this->hex_to_rgba($color2, $opacity);
        
        return "
        .watercolor-element-{$element_id} {
            position: relative !important;
            background-color: {$base_color} !important;
            overflow: hidden !important;
        }
        
        .watercolor-element-{$element_id}:before {
            content: '' !important;
            position: absolute !important;
            width: 200% !important;
            height: 200% !important;
            top: -50% !important;
            left: -50% !important;
            z-index: 0 !important;
            pointer-events: none !important;
            background: linear-gradient(45deg, 
                {$rgba1} 0%, 
                {$rgba2} 25%, 
                {$rgba1} 50%, 
                {$rgba2} 75%, 
                {$rgba1} 100%) !important;
            background-size: 400% 400% !important;
            filter: blur({$blur}px) !important;
            animation: watercolor-barrido-{$element_id} {$speed}s ease-in-out infinite !important;
        }
        
        .watercolor-element-{$element_id} > * {
            position: relative !important;
            z-index: 10 !important;
        }
        
        @keyframes watercolor-barrido-{$element_id} {
            0% {
                background-position: 0% 50%;
                transform: rotate(0deg) scale(1);
            }
            50% {
                background-position: 100% 50%;
                transform: rotate(180deg) scale(1.1);
            }
            100% {
                background-position: 0% 50%;
                transform: rotate(360deg) scale(1);
            }
        }
        ";
    }

    private function hex_to_rgba($hex, $alpha = 1) {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }

    public function admin_notice_missing_main_plugin() {
        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'watercolor-bg'),
            '<strong>' . esc_html__('Watercolor Backgrounds for Elementor', 'watercolor-bg') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'watercolor-bg') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    public function admin_notice_minimum_elementor_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'watercolor-bg'),
            '<strong>' . esc_html__('Watercolor Backgrounds for Elementor', 'watercolor-bg') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'watercolor-bg') . '</strong>',
            WATERCOLOR_BG_MINIMUM_ELEMENTOR_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

WatercolorBackgroundPlugin::instance();

// Activation hook
register_activation_hook(__FILE__, function() {
    if (!did_action('elementor/loaded')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Este plugin requiere Elementor para funcionar. Por favor instala y activa Elementor primero.', 'watercolor-bg'),
            esc_html__('Plugin Activation Error', 'watercolor-bg'),
            array('back_link' => true)
        );
    }
});

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=elementor') . '">' . esc_html__('Configurar en Elementor', 'watercolor-bg') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
?>