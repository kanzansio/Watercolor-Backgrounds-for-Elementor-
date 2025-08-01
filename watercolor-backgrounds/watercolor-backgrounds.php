<?php
/**
 * Plugin Name: Watercolor Backgrounds for Elementor
 * Plugin URI: https://kanzansio.digital/
 * Description: Añade fondos de acuarela personalizables a los contenedores de Elementor
 * Version: 1.3.0
 * Author: Kanzansio.Digital
 * Text Domain: watercolor-bg
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WATERCOLOR_BG_VERSION', '1.3.0');
define('WATERCOLOR_BG_PLUGIN_URL', plugin_dir_url(__FILE__));

final class WatercolorBackgroundPlugin {
    
    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
    }

    public function init() {
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Watercolor Backgrounds requiere Elementor para funcionar.</p></div>';
            });
            return;
        }
        
        // Cargar textdomain
        load_plugin_textdomain('watercolor-bg', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Enqueue styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        
        // INYECTAR controles en secciones existentes (método correcto)
        add_action('elementor/element/section/section_background/before_section_end', array($this, 'add_watercolor_controls'), 10, 2);
        add_action('elementor/element/container/section_background/before_section_end', array($this, 'add_watercolor_controls'), 10, 2);
        add_action('elementor/element/column/section_background/before_section_end', array($this, 'add_watercolor_controls'), 10, 2);
        
        // Render hooks
        add_action('elementor/frontend/section/before_render', array($this, 'render_watercolor'));
        add_action('elementor/frontend/container/before_render', array($this, 'render_watercolor'));
        add_action('elementor/frontend/column/before_render', array($this, 'render_watercolor'));
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'watercolor-bg-style',
            WATERCOLOR_BG_PLUGIN_URL . 'assets/watercolor-bg.css',
            array(),
            WATERCOLOR_BG_VERSION
        );
    }

    public function add_watercolor_controls($element, $args) {
        // Separador visual
        $element->add_control(
            'watercolor_separator',
            array(
                'type' => \Elementor\Controls_Manager::DIVIDER,
            )
        );
        
        // Título de sección
        $element->add_control(
            'watercolor_heading',
            array(
                'label' => esc_html__('Fondo de Acuarela', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::HEADING,
            )
        );

        // Switch principal
        $element->add_control(
            'watercolor_enable',
            array(
                'label' => esc_html__('Activar Fondo de Acuarela', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Sí', 'watercolor-bg'),
                'label_off' => esc_html__('No', 'watercolor-bg'),
                'return_value' => 'yes',
                'default' => '',
            )
        );

        // Color base
        $element->add_control(
            'watercolor_base_color',
            array(
                'label' => esc_html__('Color de Fondo Base', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );

        // Estilo del efecto
        $element->add_control(
            'watercolor_style',
            array(
                'label' => esc_html__('Estilo del Efecto', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'organic',
                'options' => array(
                    'organic' => esc_html__('Orgánico (Recomendado)', 'watercolor-bg'),
                    'classic' => esc_html__('Clásico', 'watercolor-bg'),
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );

        // Colores de manchas
        for ($i = 1; $i <= 3; $i++) {
            $element->add_control(
                "watercolor_color_{$i}",
                array(
                    'label' => sprintf(esc_html__('Color de Mancha %d', 'watercolor-bg'), $i),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => $this->get_default_color($i),
                    'condition' => array(
                        'watercolor_enable' => 'yes',
                    ),
                )
            );
        }

        // Opacidad
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
                    ),
                ),
                'default' => array(
                    'unit' => '%',
                    'size' => 60,
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );

        // Intensidad
        $element->add_control(
            'watercolor_intensity',
            array(
                'label' => esc_html__('Intensidad del Efecto', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'medium',
                'options' => array(
                    'light' => esc_html__('Suave', 'watercolor-bg'),
                    'medium' => esc_html__('Medio', 'watercolor-bg'),
                    'strong' => esc_html__('Intenso', 'watercolor-bg'),
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );
    }

    private function get_default_color($spot_number) {
        $colors = array(
            1 => '#87CEEB',
            2 => '#98D8E8', 
            3 => '#B0E0E6'
        );
        return $colors[$spot_number] ?? '#87CEEB';
    }

    public function render_watercolor($element) {
        $settings = $element->get_settings_for_display();
        
        if (!empty($settings['watercolor_enable']) && $settings['watercolor_enable'] === 'yes') {
            $element_id = $element->get_id();
            
            $element->add_render_attribute('_wrapper', 'class', 'watercolor-bg-element');
            $element->add_render_attribute('_wrapper', 'data-watercolor-id', $element_id);
            
            // Generar CSS específico para este elemento
            $css = $this->generate_watercolor_css($element_id, $settings);
            
            // Añadir CSS al footer (más confiable que wp_head en este contexto)
            add_action('wp_footer', function() use ($css) {
                echo '<style type="text/css">' . $css . '</style>';
            });
        }
    }

    private function generate_watercolor_css($element_id, $settings) {
        $base_color = !empty($settings['watercolor_base_color']) ? $settings['watercolor_base_color'] : '#ffffff';
        $style = !empty($settings['watercolor_style']) ? $settings['watercolor_style'] : 'organic';
        $intensity = !empty($settings['watercolor_intensity']) ? $settings['watercolor_intensity'] : 'medium';
        $opacity = !empty($settings['watercolor_opacity']['size']) ? $settings['watercolor_opacity']['size'] / 100 : 0.6;
        
        $colors = array(
            !empty($settings['watercolor_color_1']) ? $settings['watercolor_color_1'] : '#87CEEB',
            !empty($settings['watercolor_color_2']) ? $settings['watercolor_color_2'] : '#98D8E8',
            !empty($settings['watercolor_color_3']) ? $settings['watercolor_color_3'] : '#B0E0E6'
        );

        if ($style === 'organic') {
            return $this->generate_organic_css($element_id, $base_color, $colors, $opacity, $intensity);
        } else {
            return $this->generate_classic_css($element_id, $base_color, $colors, $opacity, $intensity);
        }
    }

    private function generate_organic_css($element_id, $base_color, $colors, $opacity, $intensity) {
        $settings = array(
            'light' => array('blur' => '15px', 'contrast' => '1.5', 'size' => '80px'),
            'medium' => array('blur' => '20px', 'contrast' => '2', 'size' => '120px'),
            'strong' => array('blur' => '25px', 'contrast' => '2.5', 'size' => '160px')
        );
        
        $current = $settings[$intensity];
        
        // Crear manchas orgánicas
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
        
        return "
        .elementor-element-{$element_id} {
            position: relative !important;
            background-color: {$base_color} !important;
            overflow: hidden !important;
        }
        
        .elementor-element-{$element_id}:before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: 
            {$background_string} !important;
            filter: blur({$current['blur']}) contrast({$current['contrast']}) !important;
            z-index: 0 !important;
            pointer-events: none !important;
            opacity: 0.8 !important;
        }
        
        .elementor-element-{$element_id} > .elementor-container,
        .elementor-element-{$element_id} > .elementor-column-wrap,
        .elementor-element-{$element_id} > .elementor-widget-wrap,
        .elementor-element-{$element_id} > .e-con,
        .elementor-element-{$element_id} > .e-con-inner {
            position: relative !important;
            z-index: 10 !important;
        }
        ";
    }

    private function generate_classic_css($element_id, $base_color, $colors, $opacity, $intensity) {
        $blur_values = array(
            'light' => '40px',
            'medium' => '60px',
            'strong' => '80px'
        );
        
        $blur = $blur_values[$intensity];
        
        return "
        .elementor-element-{$element_id} {
            position: relative !important;
            background-color: {$base_color} !important;
            overflow: hidden !important;
        }
        
        .elementor-element-{$element_id}:before {
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
        }
        
        .elementor-element-{$element_id} > .elementor-container,
        .elementor-element-{$element_id} > .elementor-column-wrap,
        .elementor-element-{$element_id} > .elementor-widget-wrap,
        .elementor-element-{$element_id} > .e-con,
        .elementor-element-{$element_id} > .e-con-inner {
            position: relative !important;
            z-index: 10 !important;
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
}

WatercolorBackgroundPlugin::instance();

// Activation hook
register_activation_hook(__FILE__, function() {
    if (!did_action('elementor/loaded')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Este plugin requiere Elementor para funcionar.');
    }
});
?>