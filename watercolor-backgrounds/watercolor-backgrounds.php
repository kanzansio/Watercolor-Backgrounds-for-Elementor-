<?php
/**
 * Plugin Name: Watercolor Backgrounds for Elementor
 * Plugin URI: https://kanzansio.digital/
 * Description: Añade fondos de acuarela personalizables a los contenedores de Elementor - Compatible con Elementor 3.28+
 * Version: 1.2.0
 * Author: Kanzansio.Digital
 * Text Domain: watercolor-bg
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Elementor tested up to: 3.28
 * Elementor Pro tested up to: 3.28
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WATERCOLOR_BG_VERSION', '1.2.0');
define('WATERCOLOR_BG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WATERCOLOR_BG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WATERCOLOR_BG_MINIMUM_ELEMENTOR_VERSION', '3.0.0');

/**
 * Main Plugin Class
 */
final class WatercolorBackgroundPlugin {
    
    /**
     * Plugin instance.
     */
    private static $_instance = null;

    /**
     * Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', array($this, 'admin_notice_missing_main_plugin'));
            return;
        }

        // Check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, WATERCOLOR_BG_MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', array($this, 'admin_notice_minimum_elementor_version'));
            return;
        }

        // Load plugin textdomain
        add_action('init', array($this, 'i18n'));

        // Initialize plugin
        add_action('elementor/init', array($this, 'elementor_init'));
    }

    /**
     * Load plugin textdomain
     */
    public function i18n() {
        load_plugin_textdomain('watercolor-bg', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Initialize Elementor integration
     */
    public function elementor_init() {
        // Add plugin actions
        add_action('elementor/controls/register', array($this, 'register_controls'));
        add_action('elementor/widgets/register', array($this, 'register_widgets'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('elementor/frontend/after_enqueue_styles', array($this, 'enqueue_frontend_styles'));
        
        // Add controls to existing elements
        $this->include_controls_files();
        $this->register_element_hooks();
    }

    /**
     * Admin notice for missing Elementor
     */
    public function admin_notice_missing_main_plugin() {
        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'watercolor-bg'),
            '<strong>' . esc_html__('Watercolor Backgrounds for Elementor', 'watercolor-bg') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'watercolor-bg') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice for minimum Elementor version
     */
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

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'watercolor-bg-style',
            WATERCOLOR_BG_PLUGIN_URL . 'assets/watercolor-bg.css',
            array(),
            WATERCOLOR_BG_VERSION
        );
    }

    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style(
            'watercolor-bg-frontend',
            WATERCOLOR_BG_PLUGIN_URL . 'assets/watercolor-bg.css',
            array(),
            WATERCOLOR_BG_VERSION
        );
    }

    /**
     * Register controls (for future custom controls)
     */
    public function register_controls($controls_manager) {
        // Future custom controls can be registered here
    }

    /**
     * Register widgets (for future custom widgets)
     */
    public function register_widgets($widgets_manager) {
        // Future custom widgets can be registered here
    }

    /**
     * Include controls files
     */
    private function include_controls_files() {
        require_once WATERCOLOR_BG_PLUGIN_PATH . 'includes/controls-handler.php';
    }

    /**
     * Register element hooks
     */
    private function register_element_hooks() {
        // Add watercolor controls to sections, containers, and columns
        add_action('elementor/element/section/section_background/after_section_end', array($this, 'add_watercolor_controls'), 10, 2);
        add_action('elementor/element/container/section_background/after_section_end', array($this, 'add_watercolor_controls'), 10, 2);
        add_action('elementor/element/column/section_background/after_section_end', array($this, 'add_watercolor_controls'), 10, 2);
        
        // Render hooks
        add_action('elementor/frontend/section/before_render', array($this, 'before_render_element'));
        add_action('elementor/frontend/container/before_render', array($this, 'before_render_element'));
        add_action('elementor/frontend/column/before_render', array($this, 'before_render_element'));
    }

    /**
     * Add watercolor controls to elements
     */
    public function add_watercolor_controls($element, $args) {
        $element->start_controls_section(
            'watercolor_background_section',
            array(
                'label' => esc_html__('Fondo de Acuarela', 'watercolor-bg'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        // Enable/Disable switch
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

        // Base background color
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

        // Style selector
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
                'frontend_available' => true,
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
                    ),
                    'frontend_available' => true,
                )
            );
        }

        // Opacity control
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
                'frontend_available' => true,
            )
        );

        // Intensity control
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
                'frontend_available' => true,
            )
        );

        $element->end_controls_section();
    }

    /**
     * Get default colors for spots
     */
    private function get_default_color($spot_number) {
        $colors = array(
            1 => '#87CEEB',
            2 => '#98D8E8', 
            3 => '#B0E0E6'
        );
        return $colors[$spot_number] ?? '#87CEEB';
    }

    /**
     * Before render element
     */
    public function before_render_element($element) {
        $settings = $element->get_settings_for_display();
        
        if (!empty($settings['watercolor_enable']) && $settings['watercolor_enable'] === 'yes') {
            $this->render_watercolor_styles($element, $settings);
        }
    }

    /**
     * Render watercolor styles
     */
    private function render_watercolor_styles($element, $settings) {
        $element_id = $element->get_id();
        
        // Add CSS class and data attributes
        $element->add_render_attribute('_wrapper', 'class', 'watercolor-bg-element');
        $element->add_render_attribute('_wrapper', 'data-watercolor-id', $element_id);
        
        // Generate and add inline CSS
        $css = $this->generate_watercolor_css($element_id, $settings);
        
        // Add CSS to the page
        wp_add_inline_style('watercolor-bg-style', $css);
    }

    /**
     * Generate watercolor CSS
     */
    private function generate_watercolor_css($element_id, $settings) {
        // Extract settings with defaults
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

    /**
     * Generate organic style CSS
     */
    private function generate_organic_css($element_id, $base_color, $colors, $opacity, $intensity) {
        $settings = array(
            'light' => array('blur' => '15px', 'contrast' => '1.5', 'size' => '80px'),
            'medium' => array('blur' => '20px', 'contrast' => '2', 'size' => '120px'),
            'strong' => array('blur' => '25px', 'contrast' => '2.5', 'size' => '160px')
        );
        
        $current = $settings[$intensity];
        
        // Create organic spots
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
        [data-watercolor-id='{$element_id}'] {
            position: relative;
            background-color: {$base_color} !important;
            overflow: hidden;
        }
        
        [data-watercolor-id='{$element_id}']:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
            {$background_string};
            filter: blur({$current['blur']}) contrast({$current['contrast']});
            z-index: 0;
            pointer-events: none;
            opacity: 0.8;
        }
        
        [data-watercolor-id='{$element_id}'] > .elementor-container,
        [data-watercolor-id='{$element_id}'] > .elementor-column-wrap,
        [data-watercolor-id='{$element_id}'] > .elementor-widget-wrap {
            position: relative;
            z-index: 2;
        }
        ";
    }

    /**
     * Generate classic style CSS
     */
    private function generate_classic_css($element_id, $base_color, $colors, $opacity, $intensity) {
        $blur_values = array(
            'light' => array('blur1' => '40px', 'blur2' => '35px'),
            'medium' => array('blur1' => '60px', 'blur2' => '50px'),
            'strong' => array('blur1' => '80px', 'blur2' => '70px')
        );
        
        $current_blur = $blur_values[$intensity];
        
        return "
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
                radial-gradient(circle at 20% 30%, " . $this->hex_to_rgba($colors[0], $opacity) . " 0%, transparent 50%),
                radial-gradient(circle at 70% 60%, " . $this->hex_to_rgba($colors[1], $opacity) . " 0%, transparent 40%),
                radial-gradient(circle at 40% 80%, " . $this->hex_to_rgba($colors[2], $opacity) . " 0%, transparent 35%);
            filter: blur({$current_blur['blur1']});
            z-index: 0;
            pointer-events: none;
        }
        
        [data-watercolor-id='{$element_id}'] > .elementor-container,
        [data-watercolor-id='{$element_id}'] > .elementor-column-wrap,
        [data-watercolor-id='{$element_id}'] > .elementor-widget-wrap {
            position: relative;
            z-index: 2;
        }
        ";
    }

    /**
     * Convert hex color to rgba
     */
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

// Initialize the plugin
WatercolorBackgroundPlugin::instance();

// Activation hook
register_activation_hook(__FILE__, 'watercolor_bg_activate');
function watercolor_bg_activate() {
    // Check for Elementor
    if (!class_exists('\Elementor\Plugin')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Este plugin requiere Elementor para funcionar. Por favor instala y activa Elementor primero.', 'watercolor-bg'),
            esc_html__('Plugin Activation Error', 'watercolor-bg'),
            array('back_link' => true)
        );
    }
}

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'watercolor_bg_action_links');
function watercolor_bg_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=elementor') . '">' . esc_html__('Configurar en Elementor', 'watercolor-bg') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}