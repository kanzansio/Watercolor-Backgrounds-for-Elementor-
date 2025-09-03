<?php
/**
 * Watercolor Container Widget for Elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Watercolor_Container_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'watercolor-container';
    }

    public function get_title() {
        return esc_html__('🎨 Contenedor Acuarela', 'watercolor-bg');
    }

    public function get_icon() {
        return 'eicon-paint-brush';
    }

    public function get_categories() {
        return ['general', 'layout'];
    }

    public function get_keywords() {
        return ['watercolor', 'acuarela', 'background', 'fondo', 'container'];
    }

    protected function register_controls() {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Contenido', 'watercolor-bg'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'content_type',
            [
                'label' => esc_html__('Tipo de Contenido', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'text',
                'options' => [
                    'text' => esc_html__('Texto', 'watercolor-bg'),
                    'template' => esc_html__('Plantilla Guardada', 'watercolor-bg'),
                    'none' => esc_html__('Sin Contenido', 'watercolor-bg'),
                ],
            ]
        );

        $this->add_control(
            'content_text',
            [
                'label' => esc_html__('Contenido', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => esc_html__('Contenido del contenedor acuarela', 'watercolor-bg'),
                'condition' => [
                    'content_type' => 'text',
                ],
            ]
        );

        $this->add_control(
            'template_id',
            [
                'label' => esc_html__('Seleccionar Plantilla', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_elementor_templates(),
                'condition' => [
                    'content_type' => 'template',
                ],
            ]
        );

        $this->end_controls_section();

        // Watercolor Effect Section
        $this->start_controls_section(
            'watercolor_section',
            [
                'label' => esc_html__('🎨 Efecto Acuarela', 'watercolor-bg'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'watercolor_effect',
            [
                'label' => esc_html__('Tipo de Efecto', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'acuarela',
                'options' => [
                    'acuarela' => esc_html__('💧 Acuarela', 'watercolor-bg'),
                    'barrido' => esc_html__('🌊 Barrido', 'watercolor-bg'),
                ],
            ]
        );

        $this->add_control(
            'watercolor_preset',
            [
                'label' => esc_html__('Preset de Colores', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'custom',
                'options' => [
                    'custom' => esc_html__('Personalizado', 'watercolor-bg'),
                    'ocean' => esc_html__('🌊 Océano', 'watercolor-bg'),
                    'sunset' => esc_html__('🌅 Atardecer', 'watercolor-bg'),
                    'forest' => esc_html__('🌲 Bosque', 'watercolor-bg'),
                    'lavender' => esc_html__('💜 Lavanda', 'watercolor-bg'),
                ],
            ]
        );

        $this->add_control(
            'watercolor_base_color',
            [
                'label' => esc_html__('Color de Fondo Base', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'condition' => [
                    'watercolor_preset' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'watercolor_color_1',
            [
                'label' => esc_html__('Color Principal', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#87CEEB',
                'condition' => [
                    'watercolor_preset' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'watercolor_color_2',
            [
                'label' => esc_html__('Color Secundario', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FFB6C1',
                'condition' => [
                    'watercolor_preset' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'watercolor_opacity',
            [
                'label' => esc_html__('Opacidad', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 60,
                ],
            ]
        );

        $this->add_control(
            'watercolor_animation_speed',
            [
                'label' => esc_html__('Velocidad de Animación', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 5,
                        'max' => 60,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 20,
                ],
                'description' => esc_html__('Segundos por ciclo de animación', 'watercolor-bg'),
            ]
        );

        $this->add_control(
            'watercolor_blur',
            [
                'label' => esc_html__('Intensidad del Desenfoque', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 100,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'size' => 40,
                ],
            ]
        );

        $this->end_controls_section();

        // Container Settings
        $this->start_controls_section(
            'container_section',
            [
                'label' => esc_html__('Configuración del Contenedor', 'watercolor-bg'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'container_min_height',
            [
                'label' => esc_html__('Altura Mínima', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                    'vh' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .watercolor-container' => 'min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label' => esc_html__('Relleno', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => 40,
                    'right' => 40,
                    'bottom' => 40,
                    'left' => 40,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .watercolor-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'content_align',
            [
                'label' => esc_html__('Alineación', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Izquierda', 'watercolor-bg'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Centro', 'watercolor-bg'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Derecha', 'watercolor-bg'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .watercolor-content' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'container_border_radius',
            [
                'label' => esc_html__('Radio del Borde', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .watercolor-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Typography Section
        $this->start_controls_section(
            'typography_section',
            [
                'label' => esc_html__('Tipografía', 'watercolor-bg'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'content_type' => 'text',
                ],
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Color del Texto', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .watercolor-content' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .watercolor-content',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'text_shadow',
                'selector' => '{{WRAPPER}} .watercolor-content',
            ]
        );

        $this->end_controls_section();

        // Advanced Section
        $this->start_controls_section(
            'advanced_section',
            [
                'label' => esc_html__('Avanzado', 'watercolor-bg'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'pause_on_hover',
            [
                'label' => esc_html__('Pausar al Pasar el Ratón', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Sí', 'watercolor-bg'),
                'label_off' => esc_html__('No', 'watercolor-bg'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'blend_mode',
            [
                'label' => esc_html__('Modo de Mezcla', 'watercolor-bg'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'multiply',
                'options' => [
                    'normal' => esc_html__('Normal', 'watercolor-bg'),
                    'multiply' => esc_html__('Multiplicar', 'watercolor-bg'),
                    'screen' => esc_html__('Pantalla', 'watercolor-bg'),
                    'overlay' => esc_html__('Superponer', 'watercolor-bg'),
                    'soft-light' => esc_html__('Luz Suave', 'watercolor-bg'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function get_elementor_templates() {
        $templates = ['0' => esc_html__('-- Seleccionar --', 'watercolor-bg')];
        
        if (class_exists('\Elementor\Plugin')) {
            $templates_query = new \WP_Query([
                'post_type' => 'elementor_library',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => '_elementor_template_type',
                        'value' => ['section', 'page'],
                        'compare' => 'IN',
                    ],
                ],
            ]);

            if ($templates_query->have_posts()) {
                while ($templates_query->have_posts()) {
                    $templates_query->the_post();
                    $templates[get_the_ID()] = get_the_title();
                }
                wp_reset_postdata();
            }
        }

        return $templates;
    }

    protected function get_preset_colors($preset) {
        $presets = [
            'ocean' => [
                'base' => '#f0f8ff',
                'color1' => '#87CEEB',
                'color2' => '#4682B4',
            ],
            'sunset' => [
                'base' => '#fff5f5',
                'color1' => '#FFB6C1',
                'color2' => '#FFA07A',
            ],
            'forest' => [
                'base' => '#f5fff5',
                'color1' => '#98FB98',
                'color2' => '#90EE90',
            ],
            'lavender' => [
                'base' => '#faf5ff',
                'color1' => '#E6E6FA',
                'color2' => '#DDA0DD',
            ],
        ];

        return isset($presets[$preset]) ? $presets[$preset] : $presets['ocean'];
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $widget_id = $this->get_id();

        // Get colors based on preset or custom
        if ($settings['watercolor_preset'] !== 'custom') {
            $colors = $this->get_preset_colors($settings['watercolor_preset']);
            $base_color = $colors['base'];
            $color1 = $colors['color1'];
            $color2 = $colors['color2'];
        } else {
            $base_color = $settings['watercolor_base_color'];
            $color1 = $settings['watercolor_color_1'];
            $color2 = $settings['watercolor_color_2'];
        }

        // Generate inline CSS
        $this->generate_inline_css($widget_id, $settings, $base_color, $color1, $color2);

        // Container classes
        $container_classes = [
            'watercolor-container',
            'watercolor-effect-' . $settings['watercolor_effect'],
            'watercolor-widget-' . $widget_id,
        ];

        if ($settings['pause_on_hover'] === 'yes') {
            $container_classes[] = 'pause-on-hover';
        }

        ?>
        <div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>">
            <div class="watercolor-content">
                <?php
                switch ($settings['content_type']) {
                    case 'text':
                        echo wp_kses_post($settings['content_text']);
                        break;
                    
                    case 'template':
                        if (!empty($settings['template_id'])) {
                            echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($settings['template_id']);
                        }
                        break;
                    
                    case 'none':
                        // No content
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    protected function generate_inline_css($widget_id, $settings, $base_color, $color1, $color2) {
        $opacity = isset($settings['watercolor_opacity']['size']) ? $settings['watercolor_opacity']['size'] / 100 : 0.6;
        $speed = isset($settings['watercolor_animation_speed']['size']) ? $settings['watercolor_animation_speed']['size'] : 20;
        $blur = isset($settings['watercolor_blur']['size']) ? $settings['watercolor_blur']['size'] : 40;
        $effect = $settings['watercolor_effect'];
        $blend_mode = $settings['blend_mode'] ?? 'multiply';

        $rgba1 = $this->hex_to_rgba($color1, $opacity);
        $rgba2 = $this->hex_to_rgba($color2, $opacity);

        $css = "
        .watercolor-widget-{$widget_id} {
            position: relative;
            background-color: {$base_color};
            overflow: hidden;
        }

        .watercolor-widget-{$widget_id}:before,
        .watercolor-widget-{$widget_id}:after {
            content: '';
            position: absolute;
            pointer-events: none;
            z-index: 0;
            mix-blend-mode: {$blend_mode};
        }

        .watercolor-widget-{$widget_id} .watercolor-content {
            position: relative;
            z-index: 10;
        }
        ";

        if ($settings['pause_on_hover'] === 'yes') {
            $css .= "
            .watercolor-widget-{$widget_id}.pause-on-hover:hover:before,
            .watercolor-widget-{$widget_id}.pause-on-hover:hover:after {
                animation-play-state: paused;
            }
            ";
        }

        if ($effect === 'barrido') {
            $css .= "
            .watercolor-widget-{$widget_id}:before {
                width: 200%;
                height: 200%;
                top: -50%;
                left: -50%;
                background: linear-gradient(45deg, 
                    {$rgba1} 0%, 
                    {$rgba2} 25%, 
                    {$rgba1} 50%, 
                    {$rgba2} 75%, 
                    {$rgba1} 100%);
                background-size: 400% 400%;
                filter: blur({$blur}px);
                animation: watercolor-barrido-{$widget_id} {$speed}s ease-in-out infinite;
            }

            @keyframes watercolor-barrido-{$widget_id} {
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
        } else {
            $css .= "
            .watercolor-widget-{$widget_id}:before,
            .watercolor-widget-{$widget_id}:after {
                width: 150%;
                height: 150%;
                top: -25%;
                left: -25%;
                filter: blur({$blur}px);
            }

            .watercolor-widget-{$widget_id}:before {
                background: radial-gradient(circle at 30% 40%, {$rgba1} 0%, transparent 50%),
                           radial-gradient(circle at 70% 60%, {$rgba1} 0%, transparent 40%);
                animation: watercolor-acuarela-1-{$widget_id} {$speed}s ease-in-out infinite;
            }

            .watercolor-widget-{$widget_id}:after {
                background: radial-gradient(circle at 60% 30%, {$rgba2} 0%, transparent 50%),
                           radial-gradient(circle at 40% 70%, {$rgba2} 0%, transparent 40%);
                animation: watercolor-acuarela-2-{$widget_id} " . ($speed * 1.5) . "s ease-in-out infinite;
            }

            @keyframes watercolor-acuarela-1-{$widget_id} {
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

            @keyframes watercolor-acuarela-2-{$widget_id} {
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

        // Add responsive CSS
        $css .= "
        @media (max-width: 768px) {
            .watercolor-widget-{$widget_id}:before,
            .watercolor-widget-{$widget_id}:after {
                filter: blur(" . max(20, $blur * 0.5) . "px);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .watercolor-widget-{$widget_id}:before,
            .watercolor-widget-{$widget_id}:after {
                animation: none !important;
            }
        }
        ";

        echo "<style>{$css}</style>";
    }

    protected function hex_to_rgba($hex, $alpha = 1) {
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

    protected function content_template() {
        ?>
        <#
        var widgetId = view.getID();
        var colors = {};
        
        if (settings.watercolor_preset !== 'custom') {
            var presets = {
                'ocean': {base: '#f0f8ff', color1: '#87CEEB', color2: '#4682B4'},
                'sunset': {base: '#fff5f5', color1: '#FFB6C1', color2: '#FFA07A'},
                'forest': {base: '#f5fff5', color1: '#98FB98', color2: '#90EE90'},
                'lavender': {base: '#faf5ff', color1: '#E6E6FA', color2: '#DDA0DD'}
            };
            colors = presets[settings.watercolor_preset] || presets.ocean;
        } else {
            colors = {
                base: settings.watercolor_base_color,
                color1: settings.watercolor_color_1,
                color2: settings.watercolor_color_2
            };
        }
        
        var containerClasses = 'watercolor-container watercolor-effect-' + settings.watercolor_effect + ' watercolor-widget-' + widgetId;
        if (settings.pause_on_hover === 'yes') {
            containerClasses += ' pause-on-hover';
        }
        #>
        <div class="{{ containerClasses }}">
            <div class="watercolor-content">
                <# if (settings.content_type === 'text') { #>
                    {{{ settings.content_text }}}
                <# } else if (settings.content_type === 'template' && settings.template_id) { #>
                    <div class="elementor-template-placeholder">
                        <?php echo esc_html__('La plantilla se mostrará aquí', 'watercolor-bg'); ?>
                    </div>
                <# } #>
            </div>
        </div>
        <?php
    }
}