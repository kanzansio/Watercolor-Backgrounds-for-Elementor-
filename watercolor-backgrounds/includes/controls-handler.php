<?php
/**
 * Controls Handler for Watercolor Backgrounds
 * 
 * This file handles the registration and management of Elementor controls
 * for the Watercolor Backgrounds plugin.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Watercolor Controls Handler
 */
class Watercolor_Controls_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // Future custom controls can be initialized here
    }

    /**
     * Get watercolor presets
     */
    public static function get_watercolor_presets() {
        return array(
            'ocean' => array(
                'label' => esc_html__('Océano', 'watercolor-bg'),
                'colors' => array('#87CEEB', '#4682B4', '#B0E0E6')
            ),
            'sunset' => array(
                'label' => esc_html__('Atardecer', 'watercolor-bg'),
                'colors' => array('#FFB6C1', '#FFA07A', '#FF69B4')
            ),
            'forest' => array(
                'label' => esc_html__('Bosque', 'watercolor-bg'),
                'colors' => array('#98FB98', '#90EE90', '#8FBC8F')
            ),
            'lavender' => array(
                'label' => esc_html__('Lavanda', 'watercolor-bg'),
                'colors' => array('#E6E6FA', '#DDA0DD', '#D8BFD8')
            )
        );
    }

    /**
     * Validate color value
     */
    public static function validate_color($color) {
        // Remove # if present
        $color = ltrim($color, '#');
        
        // Check if valid hex color
        if (preg_match('/^[a-f0-9]{6}$/i', $color) || preg_match('/^[a-f0-9]{3}$/i', $color)) {
            return '#' . $color;
        }
        
        return '#ffffff'; // Default fallback
    }

    /**
     * Sanitize intensity value
     */
    public static function sanitize_intensity($intensity) {
        $allowed_values = array('light', 'medium', 'strong');
        return in_array($intensity, $allowed_values) ? $intensity : 'medium';
    }

    /**
     * Sanitize style value
     */
    public static function sanitize_style($style) {
        $allowed_values = array('organic', 'classic');
        return in_array($style, $allowed_values) ? $style : 'organic';
    }
}