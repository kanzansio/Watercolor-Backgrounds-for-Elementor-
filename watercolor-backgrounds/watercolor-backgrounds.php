<?php
/**
 * Plugin Name: Watercolor Backgrounds for Elementor
 * Plugin URI: https://kanzansio.digital/
 * Description: Fondos de acuarela  para Elementor 
 * Version: 2.0.0
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
define('WATERCOLOR_BG_VERSION', '2.0.0');
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

        // Add Plugin actions
        add_action('elementor/widgets/register', array($this, 'init_widgets'));
        add_action('elementor/controls/register', array($this, 'init_controls'));

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

    public function init_widgets() {
        // Future widgets can be registered here
    }

    public function init_controls() {
        // Future custom controls can be registered here
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
        wp_enqueue_script(
            'watercolor-bg-editor',
            WATERCOLOR_BG_PLUGIN_URL . 'assets/watercolor-editor.js',
            array('jquery', 'elementor-editor'),
            WATERCOLOR_BG_VERSION,
            true
        );

        wp_localize_script('watercolor-bg-editor', 'watercolorEditor', array(
            'version' => WATERCOLOR_BG_VERSION,
            'presets' => $this->get_color_presets(),
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

        $element->start_controls_tabs('watercolor_tabs');

        $element->start_controls_tab(
            'watercolor_tab_normal',
            array(
                'label' => esc_html__('🎨 Fondo de Acuarela', 'watercolor-bg'),
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

        // Style preset
        $element->add_control(
            'watercolor_preset',
            array(
                'label' => esc_html__('Preset de Colores', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'custom',
                'options' => array(
                    'custom' => esc_html__('Personalizado', 'watercolor-bg'),
                    'ocean' => esc_html__('🌊 Océano', 'watercolor-bg'),
                    'sunset' => esc_html__('🌅 Atardecer', 'watercolor-bg'),
                    'forest' => esc_html__('🌲 Bosque', 'watercolor-bg'),
                    'lavender' => esc_html__('💜 Lavanda', 'watercolor-bg'),
                    'autumn' => esc_html__('🍂 Otoño', 'watercolor-bg'),
                    'spring' => esc_html__('🌸 Primavera', 'watercolor-bg'),
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
                'frontend_available' => true,
                'render_type' => 'ui',
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
                'selectors' => array(
                    '{{WRAPPER}}.watercolor-active' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $element->end_controls_tab();

        $element->start_controls_tab(
            'watercolor_tab_colors',
            array(
                'label' => esc_html__('🎨 Colores', 'watercolor-bg'),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );

        // Watercolor spot colors
        for ($i = 1; $i <= 3; $i++) {
            $element->add_control(
                "watercolor_color_{$i}",
                array(
                    'label' => sprintf(esc_html__('Color de Mancha %d', 'watercolor-bg'), $i),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => $this->get_default_color($i),
                    'condition' => array(
                        'watercolor_enable' => 'yes',
                        'watercolor_preset' => 'custom',
                    ),
                    'frontend_available' => true,
                    'render_type' => 'ui',
                )
            );
        }

        $element->end_controls_tab();

        $element->start_controls_tab(
            'watercolor_tab_settings',
            array(
                'label' => esc_html__('⚙️ Configuración', 'watercolor-bg'),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );

        // Style type
        $element->add_control(
            'watercolor_style',
            array(
                'label' => esc_html__('Estilo del Efecto', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'organic',
                'options' => array(
                    'organic' => esc_html__('🌿 Orgánico (Recomendado)', 'watercolor-bg'),
                    'classic' => esc_html__('🎭 Clásico', 'watercolor-bg'),
                    'modern' => esc_html__('✨ Moderno', 'watercolor-bg'),
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
                'frontend_available' => true,
                'render_type' => 'ui',
            )
        );

        // Opacity
        $element->add_control(
            'watercolor_opacity',
            array(
                'label' => esc_html__('Opacidad de Manchas', 'watercolor-bg'),
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

        // Intensity
        $element->add_control(
            'watercolor_intensity',
            array(
                'label' => esc_html__('Intensidad del Efecto', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'medium',
                'options' => array(
                    'light' => esc_html__('💧 Suave', 'watercolor-bg'),
                    'medium' => esc_html__('🌊 Medio', 'watercolor-bg'),
                    'strong' => esc_html__('🌀 Intenso', 'watercolor-bg'),
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
                'frontend_available' => true,
                'render_type' => 'ui',
            )
        );

        // Animation
        $element->add_control(
            'watercolor_animation',
            array(
                'label' => esc_html__('Animación', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Sí', 'watercolor-bg'),
                'label_off' => esc_html__('No', 'watercolor-bg'),
                'return_value' => 'yes',
                'default' => 'yes',
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
                        'min' => 10,
                        'max' => 60,
                        'step' => 1,
                    ),
                ),
                'default' => array(
                    'size' => 30,
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                    'watercolor_animation' => 'yes',
                ),
                'frontend_available' => true,
                'render_type' => 'ui',
            )
        );

        $element->end_controls_tab();

        $element->end_controls_tabs();
    }

    private function get_default_color($spot_number) {
        $colors = array(
            1 => '#87CEEB',
            2 => '#98D8E8', 
            3 => '#B0E0E6'
        );
        return $colors[$spot_number] ?? '#87CEEB';
    }

    private function get_color_presets() {
        return array(
            'ocean' => array('#87CEEB', '#4682B4', '#B0E0E6'),
            'sunset' => array('#FFB6C1', '#FFA07A', '#FF69B4'),
            'forest' => array('#98FB98', '#90EE90', '#8FBC8F'),
            'lavender' => array('#E6E6FA', '#DDA0DD', '#D8BFD8'),
            'autumn' => array('#FF8C69', '#DEB887', '#CD853F'),
            'spring' => array('#98FB98', '#FFB6C1', '#DDA0DD'),
        );
    }

    public function before_render_element($element) {
        $settings = $element->get_settings_for_display();
        
        if (!empty($settings['watercolor_enable']) && $settings['watercolor_enable'] === 'yes') {
            $element_id = $element->get_id();
            
            // Add classes for styling
            $element->add_render_attribute('_wrapper', 'class', array(
                'watercolor-active',
                'watercolor-element-' . $element_id
            ));
            
            // Add data attributes for JS
            $element->add_render_attribute('_wrapper', 'data-watercolor-settings', wp_json_encode($settings));
            
            // Generate and inject CSS
            $this->inject_element_styles($element_id, $settings);
        }
    }

    private function inject_element_styles($element_id, $settings) {
        $css = $this->generate_watercolor_css($element_id, $settings);
        
        // Multiple injection methods for maximum compatibility
        
        // Method 1: wp_add_inline_style (best for preview)
        wp_add_inline_style('elementor-frontend', $css);
        
        // Method 2: Direct head injection (backup)
        add_action('wp_head', function() use ($css, $element_id) {
            echo "<style id='watercolor-{$element_id}'>{$css}</style>";
        }, 999);
        
        // Method 3: Footer injection (fallback)
        add_action('wp_footer', function() use ($css) {
            echo "<style>{$css}</style>";
        });
    }

    private function generate_watercolor_css($element_id, $settings) {
        // Extract settings
        $base_color = $settings['watercolor_base_color'] ?? '#ffffff';
        $style = $settings['watercolor_style'] ?? 'organic';
        $intensity = $settings['watercolor_intensity'] ?? 'medium';
        $opacity = isset($settings['watercolor_opacity']['size']) ? $settings['watercolor_opacity']['size'] / 100 : 0.6;
        $preset = $settings['watercolor_preset'] ?? 'custom';
        $animation = $settings['watercolor_animation'] ?? 'yes';
        $animation_speed = isset($settings['watercolor_animation_speed']['size']) ? $settings['watercolor_animation_speed']['size'] : 30;

        // Get colors (preset or custom)
        if ($preset !== 'custom') {
            $presets = $this->get_color_presets();
            $colors = $presets[$preset] ?? array('#87CEEB', '#98D8E8', '#B0E0E6');
        } else {
            $colors = array(
                $settings['watercolor_color_1'] ?? '#87CEEB',
                $settings['watercolor_color_2'] ?? '#98D8E8',
                $settings['watercolor_color_3'] ?? '#B0E0E6'
            );
        }

        // Generate CSS based on style
        switch ($style) {
            case 'modern':
                return $this->generate_modern_css($element_id, $base_color, $colors, $opacity, $intensity, $animation, $animation_speed);
            case 'classic':
                return $this->generate_classic_css($element_id, $base_color, $colors, $opacity, $intensity, $animation, $animation_speed);
            default:
                return $this->generate_organic_css($element_id, $base_color, $colors, $opacity, $intensity, $animation, $animation_speed);
        }
    }

    private function generate_organic_css($element_id, $base_color, $colors, $opacity, $intensity, $animation, $animation_speed) {
        $settings = array(
            'light' => array('blur' => '15px', 'contrast' => '1.5', 'size' => '80px'),
            'medium' => array('blur' => '20px', 'contrast' => '2', 'size' => '120px'),
            'strong' => array('blur' => '25px', 'contrast' => '2.5', 'size' => '160px')
        );
        
        $current = $settings[$intensity];
        
        // Create organic watercolor spots
        $spots = array(
            array('x' => '15%', 'y' => '25%', 'color' => $colors[0], 'alpha' => $opacity * 0.8),
            array('x' => '75%', 'y' => '35%', 'color' => $colors[1], 'alpha' => $opacity * 0.9),
            array('x' => '45%', 'y' => '70%', 'color' => $colors[2], 'alpha' => $opacity * 0.7),
            array('x' => '85%', 'y' => '15%', 'color' => $colors[0], 'alpha' => $opacity * 0.6),
            array('x' => '25%', 'y' => '80%', 'color' => $colors[1], 'alpha' => $opacity * 0.5),
            array('x' => '60%', 'y' => '50%', 'color' => $colors[2], 'alpha' => $opacity * 0.4)
        );
        
        $backgrounds = array();
        foreach ($spots as $spot) {
            $rgba_color = $this->hex_to_rgba($spot['color'], $spot['alpha']);
            $backgrounds[] = "radial-gradient(circle {$current['size']} at {$spot['x']} {$spot['y']}, {$rgba_color}, transparent)";
        }
        
        $background_string = implode(",\n            ", $backgrounds);
        
        $animation_css = '';
        if ($animation === 'yes') {
            $animation_css = "animation: watercolor-organic-{$element_id} {$animation_speed}s ease-in-out infinite;";
        }
        
        return "
        .watercolor-element-{$element_id} {
            position: relative !important;
            background-color: {$base_color} !important;
            overflow: hidden !important;
        }
        
        .watercolor-element-{$element_id}:before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: {$background_string} !important;
            filter: blur({$current['blur']}) contrast({$current['contrast']}) !important;
            z-index: 0 !important;
            pointer-events: none !important;
            {$animation_css}
        }
        
        .watercolor-element-{$element_id} > .elementor-container,
        .watercolor-element-{$element_id} > .elementor-column-wrap,
        .watercolor-element-{$element_id} > .elementor-widget-wrap,
        .watercolor-element-{$element_id} > .e-con,
        .watercolor-element-{$element_id} > .e-con-inner {
            position: relative !important;
            z-index: 10 !important;
        }
        
        @keyframes watercolor-organic-{$element_id} {
            0%, 100% {
                transform: translate(0, 0) scale(1);
                opacity: 0.8;
            }
            25% {
                transform: translate(0.5%, -0.2%) scale(1.01);
                opacity: 0.7;
            }
            50% {
                transform: translate(-0.3%, 0.3%) scale(0.99);
                opacity: 0.9;
            }
            75% {
                transform: translate(0.2%, -0.1%) scale(1.005);
                opacity: 0.75;
            }
        }
        ";
    }

    private function generate_classic_css($element_id, $base_color, $colors, $opacity, $intensity, $animation, $animation_speed) {
        $blur_values = array(
            'light' => '40px',
            'medium' => '60px',
            'strong' => '80px'
        );
        
        $blur = $blur_values[$intensity];
        
        $animation_css = '';
        if ($animation === 'yes') {
            $animation_css = "animation: watercolor-classic-{$element_id} {$animation_speed}s ease-in-out infinite alternate;";
        }
        
        return "
        .watercolor-element-{$element_id} {
            position: relative !important;
            background-color: {$base_color} !important;
            overflow: hidden !important;
        }
        
        .watercolor-element-{$element_id}:before {
            content: '' !important;
            position: absolute !important;
            top: -50% !important;
            left: -50% !important;
            width: 200% !important;
            height: 200% !important;
            background: 
                radial-gradient(circle at 20% 30%, " . $this->hex_to_rgba($colors[0], $opacity) . " 0%, transparent 50%),
                radial-gradient(circle at 70% 60%, " . $this->hex_to_rgba($colors[1], $opacity) . " 0%, transparent 40%),
                radial-gradient(circle at 40% 80%, " . $this->hex_to_rgba($colors[2], $opacity) . " 0%, transparent 35%) !important;
            filter: blur({$blur}) !important;
            z-index: 0 !important;
            pointer-events: none !important;
            {$animation_css}
        }
        
        .watercolor-element-{$element_id} > * {
            position: relative !important;
            z-index: 10 !important;
        }
        
        @keyframes watercolor-classic-{$element_id} {
            0% {
                transform: translate(0, 0) rotate(0deg);
                opacity: 0.8;
            }
            100% {
                transform: translate(1%, 0.5%) rotate(0.5deg);
                opacity: 0.6;
            }
        }
        ";
    }

    private function generate_modern_css($element_id, $base_color, $colors, $opacity, $intensity, $animation, $animation_speed) {
        $settings = array(
            'light' => array('blur' => '10px', 'size' => '60px'),
            'medium' => array('blur' => '15px', 'size' => '90px'),
            'strong' => array('blur' => '20px', 'size' => '120px')
        );
        
        $current = $settings[$intensity];
        
        $animation_css = '';
        if ($animation === 'yes') {
            $animation_css = "animation: watercolor-modern-{$element_id} {$animation_speed}s linear infinite;";
        }
        
        return "
        .watercolor-element-{$element_id} {
            position: relative !important;
            background: linear-gradient(135deg, {$base_color} 0%, " . $this->hex_to_rgba($colors[0], 0.1) . " 100%) !important;
            overflow: hidden !important;
        }
        
        .watercolor-element-{$element_id}:before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: 
                conic-gradient(from 0deg at 30% 30%, " . $this->hex_to_rgba($colors[0], $opacity) . " 0deg, transparent 60deg),
                conic-gradient(from 120deg at 70% 40%, " . $this->hex_to_rgba($colors[1], $opacity) . " 0deg, transparent 60deg),
                conic-gradient(from 240deg at 40% 70%, " . $this->hex_to_rgba($colors[2], $opacity) . " 0deg, transparent 60deg) !important;
            filter: blur({$current['blur']}) !important;
            z-index: 0 !important;
            pointer-events: none !important;
            {$animation_css}
        }
        
        .watercolor-element-{$element_id} > * {
            position: relative !important;
            z-index: 10 !important;
        }
        
        @keyframes watercolor-modern-{$element_id} {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
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
