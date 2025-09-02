<?php
/**
 * Plugin Name: Watercolor Backgrounds for Elementor 2025
 * Plugin URI: https://kanzansio.digital/
 * Description: Fondos de acuarela animados para Elementor - Versión 2025 optimizada
 * Version: 4.0.0
 * Author: Kanzansio.Digital
 * Text Domain: watercolor-bg
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.5
 * Requires PHP: 8.0
 * Elementor tested up to: 3.30
 * Elementor Pro tested up to: 3.30
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constantes del plugin
define('WATERCOLOR_BG_VERSION', '4.0.0');
define('WATERCOLOR_BG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WATERCOLOR_BG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WATERCOLOR_BG_MINIMUM_ELEMENTOR_VERSION', '3.20.0');

final class WatercolorBackgroundPlugin2025 {
    
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
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', array($this, 'admin_notice_missing_main_plugin'));
            return false;
        }

        if (!version_compare(ELEMENTOR_VERSION, WATERCOLOR_BG_MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', array($this, 'admin_notice_minimum_elementor_version'));
            return false;
        }

        return true;
    }

    public function init() {
        add_action('init', array($this, 'i18n'));
        add_action('wp_enqueue_scripts', array($this, 'widget_scripts'));
        add_action('elementor/frontend/after_enqueue_styles', array($this, 'widget_styles'));
        add_action('elementor/editor/after_enqueue_scripts', array($this, 'editor_scripts'));
        
        $this->register_controls_injection();
    }

    public function i18n() {
        load_plugin_textdomain('watercolor-bg');
    }

    public function widget_scripts() {
        wp_enqueue_script(
            'watercolor-bg-frontend',
            WATERCOLOR_BG_PLUGIN_URL . 'assets/watercolor-frontend.js',
            array('jquery'),
            WATERCOLOR_BG_VERSION,
            array('in_footer' => true)
        );
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
            array('in_footer' => true)
        );
    }

    private function register_controls_injection() {
        // Soporte para containers modernos de Elementor 2025
        add_action('elementor/element/section/section_background/before_section_end', array($this, 'register_watercolor_controls'), 10, 2);
        add_action('elementor/element/container/section_background/before_section_end', array($this, 'register_watercolor_controls'), 10, 2);
        add_action('elementor/element/column/section_background/before_section_end', array($this, 'register_watercolor_controls'), 10, 2);
        
        // Nuevos elementos de Elementor 2025
        add_action('elementor/element/e-container/section_background/before_section_end', array($this, 'register_watercolor_controls'), 10, 2);

        // Rendering hooks
        add_action('elementor/frontend/section/before_render', array($this, 'before_render_element'));
        add_action('elementor/frontend/container/before_render', array($this, 'before_render_element'));
        add_action('elementor/frontend/column/before_render', array($this, 'before_render_element'));
    }

    public function register_watercolor_controls($element, $args) {
        $element->start_controls_section(
            'watercolor_section',
            array(
                'label' => esc_html__('🎨 Fondo de Acuarela', 'watercolor-bg'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

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
            )
        );

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
            )
        );

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
            )
        );

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
            )
        );

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
            )
        );

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
            )
        );

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
            )
        );

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
            )
        );

        $element->end_controls_section();
    }

    public function before_render_element($element) {
        $settings = $element->get_settings_for_display();
        
        if (!empty($settings['watercolor_enable']) && $settings['watercolor_enable'] === 'yes') {
            $element_id = $element->get_id();
            
            $element->add_render_attribute('_wrapper', 'class', array(
                'watercolor-active',
                'watercolor-element-' . $element_id,
                'watercolor-effect-' . ($settings['watercolor_effect'] ?? 'acuarela')
            ));
            
            $element->add_render_attribute('_wrapper', 'data-watercolor-settings', wp_json_encode($settings));
            
            $this->inject_element_styles($element_id, $settings);
        }
    }

    private function inject_element_styles($element_id, $settings) {
        $css = $this->generate_watercolor_css($element_id, $settings);
        
        wp_add_inline_style('watercolor-bg-style', $css);
        
        if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
            add_action('wp_head', function() use ($css) {
                echo "<style>{$css}</style>";
            }, 999);
        }
    }

    private function generate_watercolor_css($element_id, $settings) {
        $base_color = $settings['watercolor_base_color'] ?? '#ffffff';
        $color1 = $settings['watercolor_color_1'] ?? '#87CEEB';
        $color2 = $settings['watercolor_color_2'] ?? '#FFB6C1';
        $opacity = ($settings['watercolor_opacity']['size'] ?? 60) / 100;
        $speed = $settings['watercolor_animation_speed']['size'] ?? 20;
        $blur = $settings['watercolor_blur']['size'] ?? 40;
        $effect = $settings['watercolor_effect'] ?? 'acuarela';

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
            filter: blur({$blur}px) !important;
            will-change: transform !important;
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
            0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); }
            33% { transform: translate(2%, -3%) rotate(1deg) scale(1.02); }
            66% { transform: translate(-1%, 2%) rotate(-1deg) scale(0.98); }
        }
        
        @keyframes watercolor-acuarela-2-{$element_id} {
            0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); }
            33% { transform: translate(-2%, 1%) rotate(-1deg) scale(0.98); }
            66% { transform: translate(3%, -2%) rotate(1deg) scale(1.03); }
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
                {$rgba1} 0%, {$rgba2} 25%, {$rgba1} 50%, {$rgba2} 75%, {$rgba1} 100%) !important;
            background-size: 400% 400% !important;
            filter: blur({$blur}px) !important;
            animation: watercolor-barrido-{$element_id} {$speed}s ease-in-out infinite !important;
        }
        
        .watercolor-element-{$element_id} > * {
            position: relative !important;
            z-index: 10 !important;
        }
        
        @keyframes watercolor-barrido-{$element_id} {
            0% { background-position: 0% 50%; transform: rotate(0deg) scale(1); }
            50% { background-position: 100% 50%; transform: rotate(180deg) scale(1.1); }
            100% { background-position: 0% 50%; transform: rotate(360deg) scale(1); }
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
            esc_html__('"%1$s" requiere "%2$s" para funcionar.', 'watercolor-bg'),
            '<strong>Watercolor Backgrounds</strong>',
            '<strong>Elementor</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    public function admin_notice_minimum_elementor_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
            esc_html__('"%1$s" requiere Elementor versión %2$s o superior.', 'watercolor-bg'),
            '<strong>Watercolor Backgrounds</strong>',
            WATERCOLOR_BG_MINIMUM_ELEMENTOR_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

WatercolorBackgroundPlugin2025::instance();

register_activation_hook(__FILE__, function() {
    if (!did_action('elementor/loaded')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Este plugin requiere Elementor para funcionar.', 'watercolor-bg'),
            esc_html__('Error de Activación', 'watercolor-bg'),
            array('back_link' => true)
        );
    }
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=elementor') . '">Configurar</a>';
    array_unshift($links, $settings_link);
    return $links;
});
?>