<?php
/**
 * Plugin Name: Watercolor Backgrounds for Elementor
 * Plugin URI: https://tu-sitio.com
 * Description: Añade fondos de acuarela personalizables a los contenedores de Elementor
 * Version: 1.0.0
 * Author: Tu Nombre
 * Text Domain: watercolor-bg
 * Domain Path: /languages
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WATERCOLOR_BG_VERSION', '1.0.0');
define('WATERCOLOR_BG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WATERCOLOR_BG_PLUGIN_PATH', plugin_dir_path(__FILE__));

class WatercolorBackgroundPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('elementor/controls/controls_registered', array($this, 'register_controls'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('elementor/frontend/after_enqueue_styles', array($this, 'enqueue_frontend_styles'));
    }
    
    public function init() {
        // Cargar traducciones
        load_plugin_textdomain('watercolor-bg', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style(
            'watercolor-bg-style',
            WATERCOLOR_BG_PLUGIN_URL . 'assets/watercolor-bg.css',
            array(),
            WATERCOLOR_BG_VERSION
        );
    }
    
    public function enqueue_frontend_styles() {
        wp_enqueue_style(
            'watercolor-bg-frontend',
            WATERCOLOR_BG_PLUGIN_URL . 'assets/watercolor-bg.css',
            array(),
            WATERCOLOR_BG_VERSION
        );
    }
    
    public function register_controls() {
        // Verificar si Elementor está activo
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        // Añadir controles a secciones y contenedores
        add_action('elementor/element/section/section_background/after_section_end', array($this, 'add_watercolor_controls'), 10, 2);
        add_action('elementor/element/container/section_background/after_section_end', array($this, 'add_watercolor_controls'), 10, 2);
        add_action('elementor/element/column/section_background/after_section_end', array($this, 'add_watercolor_controls'), 10, 2);
        
        // Hook para renderizar el fondo
        add_action('elementor/frontend/section/before_render', array($this, 'before_render_element'));
        add_action('elementor/frontend/container/before_render', array($this, 'before_render_element'));
        add_action('elementor/frontend/column/before_render', array($this, 'before_render_element'));
    }
    
    public function add_watercolor_controls($element, $args) {
        $element->start_controls_section(
            'watercolor_background_section',
            array(
                'label' => __('Fondo de Acuarela', 'watercolor-bg'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        // Interruptor para activar/desactivar
        $element->add_control(
            'watercolor_enable',
            array(
                'label' => __('Activar Fondo de Acuarela', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Sí', 'watercolor-bg'),
                'label_off' => __('No', 'watercolor-bg'),
                'return_value' => 'yes',
                'default' => '',
            )
        );
        
        // Color base/fondo
        $element->add_control(
            'watercolor_base_color',
            array(
                'label' => __('Color de Fondo Base', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );
        
        // Color de mancha 1
        $element->add_control(
            'watercolor_color_1',
            array(
                'label' => __('Color de Mancha 1', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#87CEEB',
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );
        
        // Color de mancha 2
        $element->add_control(
            'watercolor_color_2',
            array(
                'label' => __('Color de Mancha 2', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#98D8E8',
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );
        
        // Color de mancha 3
        $element->add_control(
            'watercolor_color_3',
            array(
                'label' => __('Color de Mancha 3', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#B0E0E6',
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );
        
        // Opacidad de las manchas
        $element->add_control(
            'watercolor_opacity',
            array(
                'label' => __('Opacidad de Manchas', 'watercolor-bg'),
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
        
        // Intensidad del efecto
        $element->add_control(
            'watercolor_intensity',
            array(
                'label' => __('Intensidad del Efecto', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'medium',
                'options' => array(
                    'light' => __('Suave', 'watercolor-bg'),
                    'medium' => __('Medio', 'watercolor-bg'),
                    'strong' => __('Intenso', 'watercolor-bg'),
                ),
                'condition' => array(
                    'watercolor_enable' => 'yes',
                ),
            )
        );
        
        $element->end_controls_section();
    }
    
    public function before_render_element($element) {
        $settings = $element->get_settings_for_display();
        
        if (isset($settings['watercolor_enable']) && $settings['watercolor_enable'] === 'yes') {
            $this->render_watercolor_background($element, $settings);
        }
    }
    
    private function render_watercolor_background($element, $settings) {
        $base_color = isset($settings['watercolor_base_color']) ? $settings['watercolor_base_color'] : '#ffffff';
        $color1 = isset($settings['watercolor_color_1']) ? $settings['watercolor_color_1'] : '#87CEEB';
        $color2 = isset($settings['watercolor_color_2']) ? $settings['watercolor_color_2'] : '#98D8E8';
        $color3 = isset($settings['watercolor_color_3']) ? $settings['watercolor_color_3'] : '#B0E0E6';
        $opacity = isset($settings['watercolor_opacity']['size']) ? $settings['watercolor_opacity']['size'] / 100 : 0.6;
        $intensity = isset($settings['watercolor_intensity']) ? $settings['watercolor_intensity'] : 'medium';
        
        // Generar ID único para este elemento
        $element_id = $element->get_id();
        
        // Añadir clase CSS al elemento
        $element->add_render_attribute('_wrapper', 'class', 'watercolor-bg-element');
        $element->add_render_attribute('_wrapper', 'data-watercolor-id', $element_id);
        
        // Generar CSS inline para este elemento específico
        $css = $this->generate_watercolor_css($element_id, $base_color, $color1, $color2, $color3, $opacity, $intensity);
        
        // Añadir el CSS al head
        add_action('wp_head', function() use ($css) {
            echo '<style type="text/css">' . $css . '</style>';
        });
    }
    
    private function generate_watercolor_css($element_id, $base_color, $color1, $color2, $color3, $opacity, $intensity) {
        // Ajustar valores según intensidad
        $blur_values = array(
            'light' => array('blur1' => '40px', 'blur2' => '35px', 'blur3' => '30px'),
            'medium' => array('blur1' => '60px', 'blur2' => '50px', 'blur3' => '45px'),
            'strong' => array('blur1' => '80px', 'blur2' => '70px', 'blur3' => '65px')
        );
        
        $current_blur = $blur_values[$intensity];
        
        $css = "
        [data-watercolor-id='{$element_id}'] {
            position: relative;
            background-color: {$base_color} !important;
            overflow: hidden;
        }
        
        [data-watercolor-id='{$element_id}']:before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 30%, " . $this->hex_to_rgba($color1, $opacity) . " 0%, transparent 50%),
                radial-gradient(circle at 70% 60%, " . $this->hex_to_rgba($color2, $opacity) . " 0%, transparent 40%),
                radial-gradient(circle at 40% 80%, " . $this->hex_to_rgba($color3, $opacity) . " 0%, transparent 35%),
                radial-gradient(circle at 80% 20%, " . $this->hex_to_rgba($color1, $opacity * 0.7) . " 0%, transparent 45%),
                radial-gradient(circle at 10% 70%, " . $this->hex_to_rgba($color2, $opacity * 0.8) . " 0%, transparent 30%);
            filter: blur({$current_blur['blur1']});
            z-index: 0;
            pointer-events: none;
        }
        
        [data-watercolor-id='{$element_id}']:after {
            content: '';
            position: absolute;
            top: -30%;
            left: -30%;
            width: 160%;
            height: 160%;
            background: 
                radial-gradient(ellipse at 60% 40%, " . $this->hex_to_rgba($color3, $opacity * 0.6) . " 0%, transparent 35%),
                radial-gradient(ellipse at 30% 70%, " . $this->hex_to_rgba($color1, $opacity * 0.5) . " 0%, transparent 40%),
                radial-gradient(ellipse at 90% 80%, " . $this->hex_to_rgba($color2, $opacity * 0.7) . " 0%, transparent 25%);
            filter: blur({$current_blur['blur2']});
            z-index: 1;
            pointer-events: none;
        }
        
        [data-watercolor-id='{$element_id}'] > .elementor-container,
        [data-watercolor-id='{$element_id}'] > .elementor-column-wrap,
        [data-watercolor-id='{$element_id}'] > .elementor-widget-wrap {
            position: relative;
            z-index: 2;
        }
        ";
        
        return $css;
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

// Inicializar el plugin
new WatercolorBackgroundPlugin();

// Hook de activación
register_activation_hook(__FILE__, 'watercolor_bg_activate');
function watercolor_bg_activate() {
    // Verificar si Elementor está instalado
    if (!is_plugin_active('elementor/elementor.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Este plugin requiere Elementor para funcionar. Por favor instala y activa Elementor primero.', 'watercolor-bg'));
    }
}

// Añadir enlace de configuración en la página de plugins
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'watercolor_bg_action_links');
function watercolor_bg_action_links($links) {
    $settings_link = '<a href="admin.php?page=elementor">' . __('Configurar en Elementor', 'watercolor-bg') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
?>