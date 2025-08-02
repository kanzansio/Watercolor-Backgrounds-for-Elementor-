/**
 * Watercolor Backgrounds - Editor JavaScript
 * Maneja preview en tiempo real en el editor de Elementor
 */

(function($) {
    'use strict';

    var WatercolorEditor = {
        
        // Configuración
        config: {
            debounceDelay: 100,
            animationClasses: ['watercolor-animate-organic', 'watercolor-animate-classic', 'watercolor-animate-modern'],
            presetClasses: ['watercolor-preset-ocean', 'watercolor-preset-sunset', 'watercolor-preset-forest', 
                          'watercolor-preset-lavender', 'watercolor-preset-autumn', 'watercolor-preset-spring']
        },

        // Cache para estilos
        styleCache: new Map(),
        
        // Debounce function
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var later = function() {
                    clearTimeout(timeout);
                    func.apply(this, arguments);
                }.bind(this);
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Inicializar
        init: function() {
            this.bindEvents();
            this.initPreview();
            console.log('Watercolor Editor initialized');
        },

        // Eventos
        bindEvents: function() {
            var self = this;
            
            // Cuando el editor está listo
            elementor.channels.editor.on('change', self.debounce(function(controlView) {
                self.handleControlChange(controlView);
            }, self.config.debounceDelay));

            // Cuando se cambia de elemento
            elementor.channels.editor.on('element:loaded', function() {
                setTimeout(function() {
                    self.updateAllElements();
                }, 500);
            });

            // Preview refresh
            if (elementor.getPreviewView) {
                elementor.getPreviewView().on('refresh', function() {
                    setTimeout(function() {
                        self.updateAllElements();
                    }, 1000);
                });
            }
        },

        // Manejar cambios en controles
        handleControlChange: function(controlView) {
            if (!controlView || !controlView.model) return;
            
            var controlName = controlView.model.get('name');
            
            // Solo procesar controles de watercolor
            if (!controlName || controlName.indexOf('watercolor_') !== 0) return;
            
            var elementModel = controlView.options.elementSettingsModel;
            if (!elementModel) return;
            
            var elementId = elementModel.get('id');
            var settings = elementModel.toJSON();
            
            console.log('Watercolor control changed:', controlName, 'Element:', elementId);
            
            this.updateElementPreview(elementId, settings);
        },

        // Actualizar preview de elemento específico
        updateElementPreview: function(elementId, settings) {
            var self = this;
            
            // Buscar el elemento en el preview
            var $previewElement = this.findPreviewElement(elementId);
            if (!$previewElement.length) return;
            
            // Aplicar cambios
            if (settings.watercolor_enable === 'yes') {
                this.applyWatercolorStyles($previewElement, elementId, settings);
            } else {
                this.removeWatercolorStyles($previewElement, elementId);
            }
        },

        // Encontrar elemento en preview
        findPreviewElement: function(elementId) {
            var $preview = $('#elementor-preview-iframe');
            if (!$preview.length) return $();
            
            var previewDoc = $preview[0].contentDocument || $preview[0].contentWindow.document;
            return $(previewDoc).find('.elementor-element-' + elementId);
        },

        // Aplicar estilos watercolor
        applyWatercolorStyles: function($element, elementId, settings) {
            var self = this;
            
            // Añadir clase base
            $element.addClass('watercolor-active watercolor-element-' + elementId);
            
            // Aplicar preset si está seleccionado
            this.applyPreset($element, settings);
            
            // Aplicar animación
            this.applyAnimation($element, settings);
            
            // Generar CSS personalizado
            var css = this.generateCustomCSS(elementId, settings);
            if (css) {
                this.injectCSS(elementId, css);
            }
        },

        // Remover estilos watercolor
        removeWatercolorStyles: function($element, elementId) {
            // Remover clases
            $element.removeClass('watercolor-active');
            $element.removeClass('watercolor-element-' + elementId);
            
            // Remover clases de preset
            this.config.presetClasses.forEach(function(className) {
                $element.removeClass(className);
            });
            
            // Remover clases de animación
            this.config.animationClasses.forEach(function(className) {
                $element.removeClass(className);
            });
            
            // Remover CSS personalizado
            this.removeCSS(elementId);
        },

        // Aplicar preset
        applyPreset: function($element, settings) {
            var preset = settings.watercolor_preset || 'custom';
            
            // Remover clases de preset anteriores
            this.config.presetClasses.forEach(function(className) {
                $element.removeClass(className);
            });
            
            // Aplicar nuevo preset
            if (preset !== 'custom') {
                $element.addClass('watercolor-preset-' + preset);
            }
        },

        // Aplicar animación
        applyAnimation: function($element, settings) {
            var style = settings.watercolor_style || 'organic';
            var animation = settings.watercolor_animation || 'yes';
            
            // Remover clases de animación anteriores
            this.config.animationClasses.forEach(function(className) {
                $element.removeClass(className);
            });
            
            // Aplicar nueva animación
            if (animation === 'yes') {
                $element.addClass('watercolor-animate-' + style);
            }
        },

        // Generar CSS personalizado
        generateCustomCSS: function(elementId, settings) {
            if (settings.watercolor_preset !== 'custom') {
                return ''; // Los presets usan CSS predefinido
            }
            
            var style = settings.watercolor_style || 'organic';
            var intensity = settings.watercolor_intensity || 'medium';
            var opacity = (settings.watercolor_opacity && settings.watercolor_opacity.size) ? 
                         settings.watercolor_opacity.size / 100 : 0.6;
            
            var colors = [
                settings.watercolor_color_1 || '#87CEEB',
                settings.watercolor_color_2 || '#98D8E8',
                settings.watercolor_color_3 || '#B0E0E6'
            ];
            
            var baseColor = settings.watercolor_base_color || '#ffffff';
            
            // Generar CSS según el estilo
            switch (style) {
                case 'modern':
                    return this.generateModernCSS(elementId, baseColor, colors, opacity, intensity);
                case 'classic':
                    return this.generateClassicCSS(elementId, baseColor, colors, opacity, intensity);
                default:
                    return this.generateOrganicCSS(elementId, baseColor, colors, opacity, intensity);
            }
        },

        // Generar CSS orgánico
        generateOrganicCSS: function(elementId, baseColor, colors, opacity, intensity) {
            var settings = {
                'light': {blur: '15px', contrast: '1.5', size: '80px'},
                'medium': {blur: '20px', contrast: '2', size: '120px'},
                'strong': {blur: '25px', contrast: '2.5', size: '160px'}
            };
            
            var current = settings[intensity];
            
            var spots = [
                {x: '15%', y: '25%', color: colors[0], alpha: opacity * 0.8},
                {x: '75%', y: '35%', color: colors[1], alpha: opacity * 0.9},
                {x: '45%', y: '70%', color: colors[2], alpha: opacity * 0.7},
                {x: '85%', y: '15%', color: colors[0], alpha: opacity * 0.6},
                {x: '25%', y: '80%', color: colors[1], alpha: opacity * 0.5},
                {x: '60%', y: '50%', color: colors[2], alpha: opacity * 0.4}
            ];
            
            var backgrounds = [];
            spots.forEach(function(spot) {
                var rgba = this.hexToRgba(spot.color, spot.alpha);
                backgrounds.push('radial-gradient(circle ' + current.size + ' at ' + spot.x + ' ' + spot.y + ', ' + rgba + ', transparent)');
            }.bind(this));
            
            return '.watercolor-element-' + elementId + ':before { ' +
                   'background: ' + backgrounds.join(', ') + ' !important; ' +
                   'filter: blur(' + current.blur + ') contrast(' + current.contrast + ') !important; ' +
                   '} ' +
                   '.watercolor-element-' + elementId + ' { ' +
                   'background-color: ' + baseColor + ' !important; ' +
                   '}';
        },

        // Generar CSS clásico
        generateClassicCSS: function(elementId, baseColor, colors, opacity, intensity) {
            var blurValues = {
                'light': '40px',
                'medium': '60px',
                'strong': '80px'
            };
            
            var blur = blurValues[intensity];
            
            var backgrounds = [
                'radial-gradient(circle at 20% 30%, ' + this.hexToRgba(colors[0], opacity) + ' 0%, transparent 50%)',
                'radial-gradient(circle at 70% 60%, ' + this.hexToRgba(colors[1], opacity) + ' 0%, transparent 40%)',
                'radial-gradient(circle at 40% 80%, ' + this.hexToRgba(colors[2], opacity) + ' 0%, transparent 35%)'
            ];
            
            return '.watercolor-element-' + elementId + ':before { ' +
                   'background: ' + backgrounds.join(', ') + ' !important; ' +
                   'filter: blur(' + blur + ') !important; ' +
                   '} ' +
                   '.watercolor-element-' + elementId + ' { ' +
                   'background-color: ' + baseColor + ' !important; ' +
                   '}';
        },

        // Generar CSS moderno
        generateModernCSS: function(elementId, baseColor, colors, opacity, intensity) {
            var settings = {
                'light': {blur: '10px', size: '60px'},
                'medium': {blur: '15px', size: '90px'},
                'strong': {blur: '20px', size: '120px'}
            };
            
            var current = settings[intensity];
            
            var backgrounds = [
                'conic-gradient(from 0deg at 30% 30%, ' + this.hexToRgba(colors[0], opacity) + ' 0deg, transparent 60deg)',
                'conic-gradient(from 120deg at 70% 40%, ' + this.hexToRgba(colors[1], opacity) + ' 0deg, transparent 60deg)',
                'conic-gradient(from 240deg at 40% 70%, ' + this.hexToRgba(colors[2], opacity) + ' 0deg, transparent 60deg)'
            ];
            
            return '.watercolor-element-' + elementId + ':before { ' +
                   'background: ' + backgrounds.join(', ') + ' !important; ' +
                   'filter: blur(' + current.blur + ') !important; ' +
                   '} ' +
                   '.watercolor-element-' + elementId + ' { ' +
                   'background: linear-gradient(135deg, ' + baseColor + ' 0%, ' + this.hexToRgba(colors[0], 0.1) + ' 100%) !important; ' +
                   '}';
        },

        // Inyectar CSS
        injectCSS: function(elementId, css) {
            var styleId = 'watercolor-preview-' + elementId;
            var $preview = $('#elementor-preview-iframe');
            
            if ($preview.length) {
                var previewDoc = $preview[0].contentDocument || $preview[0].contentWindow.document;
                var $head = $(previewDoc).find('head');
                
                // Remover estilo anterior
                $head.find('#' + styleId).remove();
                
                // Añadir nuevo estilo
                $head.append('<style id="' + styleId + '">' + css + '</style>');
            }
        },

        // Remover CSS
        removeCSS: function(elementId) {
            var styleId = 'watercolor-preview-' + elementId;
            var $preview = $('#elementor-preview-iframe');
            
            if ($preview.length) {
                var previewDoc = $preview[0].contentDocument || $preview[0].contentWindow.document;
                $(previewDoc).find('#' + styleId).remove();
            }
        },

        // Actualizar todos los elementos
        updateAllElements: function() {
            var self = this;
            
            if (typeof elementor === 'undefined' || !elementor.elements) return;
            
            elementor.elements.models.forEach(function(element) {
                var settings = element.get('settings').toJSON();
                if (settings.watercolor_enable === 'yes') {
                    self.updateElementPreview(element.get('id'), settings);
                }
            });
        },

        // Inicializar preview
        initPreview: function() {
            var self = this;
            
            // Esperar a que el preview esté listo
            var checkPreview = function() {
                var $preview = $('#elementor-preview-iframe');
                if ($preview.length && $preview[0].contentDocument) {
                    self.updateAllElements();
                } else {
                    setTimeout(checkPreview, 500);
                }
            };
            
            setTimeout(checkPreview, 1000);
        },

        // Utilidades
        hexToRgba: function(hex, alpha) {
            hex = hex.replace('#', '');
            
            var r, g, b;
            if (hex.length === 3) {
                r = parseInt(hex.charAt(0) + hex.charAt(0), 16);
                g = parseInt(hex.charAt(1) + hex.charAt(1), 16);
                b = parseInt(hex.charAt(2) + hex.charAt(2), 16);
            } else {
                r = parseInt(hex.substr(0, 2), 16);
                g = parseInt(hex.substr(2, 2), 16);
                b = parseInt(hex.substr(4, 2), 16);
            }
            
            return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
        }
    };

    // Inicializar cuando Elementor esté listo
    $(window).on('elementor:init', function() {
        WatercolorEditor.init();
    });

    // Backup: inicializar después de un delay si elementor ya está cargado
    $(document).ready(function() {
        setTimeout(function() {
            if (typeof elementor !== 'undefined' && typeof WatercolorEditor !== 'undefined') {
                WatercolorEditor.init();
            }
        }, 2000);
    });

    // Exportar para debugging
    window.WatercolorEditor = WatercolorEditor;

})(jQuery);