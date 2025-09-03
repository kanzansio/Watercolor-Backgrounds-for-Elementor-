<?php
/**
 * Plugin Name: Watercolor Backgrounds for Elementor
 * Plugin URI: https://kanzansio.digital/
 * Description: Widget de contenedor con fondos de acuarela animados para Elementor
 * Version: 4.0.0
 * Author: Kanzansio.Digital
 * Text Domain: watercolor-bg
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Elementor tested up to: 3.28
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WATERCOLOR_BG_VERSION', '4.0.0');
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
        add_action('plugins_loaded', [$this, 'on_plugins_loaded']);
    }

    public function on_plugins_loaded() {
        // Load text domain
        load_plugin_textdomain('watercolor-bg', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_main_plugin']);
            return;
        }

        // Check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, WATERCOLOR_BG_MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
            return;
        }

        // Register Widget
        add_action('elementor/widgets/register', [$this, 'register_widgets']);

        // Enqueue styles
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'widget_styles']);
        add_action('elementor/editor/after_enqueue_styles', [$this, 'widget_styles']);
    }

    public function widget_styles() {
        // Base styles for watercolor effects
        $css = '
        /* Watercolor Container Base Styles */
        .watercolor-container {
            position: relative;
            isolation: isolate;
            overflow: hidden;
        }

        .watercolor-container .watercolor-content {
            position: relative;
            z-index: 10;
        }

        /* Hover pause animation */
        .watercolor-container:hover:before,
        .watercolor-container:hover:after {
            animation-play-state: paused;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .watercolor-container:before,
            .watercolor-container:after {
                filter: blur(20px) !important;
            }
        }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            .watercolor-container:before,
            .watercolor-container:after {
                animation: none !important;
            }
        }
        ';

        wp_add_inline_style('elementor-frontend', $css);
    }

    public function register_widgets($widgets_manager) {
        // Include widget file
        require_once(__DIR__ . '/widgets/watercolor-container-widget.php');

        // Register widget
        $widgets_manager->register(new \Watercolor_Container_Widget());
    }

    public function admin_notice_missing_main_plugin() {
        $message = sprintf(
            esc_html__('"%1$s" requiere "%2$s" para ser instalado y activado.', 'watercolor-bg'),
            '<strong>' . esc_html__('Watercolor Backgrounds for Elementor', 'watercolor-bg') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'watercolor-bg') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    public function admin_notice_minimum_elementor_version() {
        $message = sprintf(
            esc_html__('"%1$s" requiere "%2$s" versión %3$s o superior.', 'watercolor-bg'),
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
            ['back_link' => true]
        );
    }
});

// Add settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('post.php?action=elementor') . '">' . 
                     esc_html__('Abrir Elementor', 'watercolor-bg') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});