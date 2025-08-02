/**
 * Watercolor Backgrounds - Editor JavaScript v3.0
 * Real-time preview in Elementor editor
 */

(function($) {
    'use strict';

    var WatercolorEditor = {
        
        // Configuration
        config: {
            debounceDelay: 150,
            updateDelay: 100
        },

        // State
        activeElements: new Map(),
        updateQueue: new Set(),
        isUpdating: false,
        
        // Initialize
        init: function() {
            console.log('Watercolor Editor v3.0 initializing...');
            
            this.bindEvents();
            this.setupMutationObserver();
            
            // Initial setup with delay
            setTimeout(() => {
                this.updateAllElements();
            }, 1000);
        },

        // Bind events
        bindEvents: function() {
            var self = this;
            
            // Listen for control changes
            elementor.channels.editor.on('change', function(controlView) {
                self.handleControlChange(controlView);
            });

            // Listen for element selection
            elementor.channels.editor.on('section:activated', function(activeSection) {
                self.onElementActivated(activeSection);
            });

            // Listen for preview updates
            elementor.on('preview:loaded', function() {
                console.log('Preview loaded, updating watercolor elements...');
                setTimeout(() => {
                    self.updateAllElements();
                }, 500);
            });

            // Handle page settings changes
            elementor.on('preview:reload', function() {
                self.updateAllElements();
            });
        },

        // Setup mutation observer for DOM changes
        setupMutationObserver: function() {
            var self = this;
            
            // Wait for preview iframe
            var checkPreview = setInterval(function() {
                var $preview = $('#elementor-preview-iframe');
                if ($preview.length && $preview[0].contentDocument) {
                    clearInterval(checkPreview);
                    
                    var previewDoc = $preview[0].contentDocument;
                    var previewBody = previewDoc.body;
                    
                    // Create observer
                    var observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'childList' || mutation.type === 'attributes') {
                                self.debouncedUpdate();
                            }
                        });
                    });
                    
                    // Start observing
                    observer.observe(previewBody, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['class', 'data-id']
                    });
                    
                    console.log('Mutation observer setup complete');
                }
            }, 500);
        },

        // Debounced update
        debouncedUpdate: (function() {
            var timeout;
            return function() {
                var self = WatercolorEditor;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    self.updateAllElements();
                }, self.config.debounceDelay);
            };
        })(),

        // Handle control changes
        handleControlChange: function(controlView) {
            if (!controlView || !controlView.model) return;
            
            var controlName = controlView.model.get('name');
            if (!controlName || controlName.indexOf('watercolor_') !== 0) return;
            
            var elementModel = controlView.options.elementSettingsModel;
            if (!elementModel) return;
            
            var elementId = elementModel.get('id');
            
            console.log('Watercolor control changed:', controlName, 'for element:', elementId);
            
            // Queue update
            this.queueElementUpdate(elementId);
        },

        // Queue element update
        queueElementUpdate: function(elementId) {
            this.updateQueue.add(elementId);
            this.processUpdateQueue();
        },

        // Process update queue
        processUpdateQueue: function() {
            var self = this;
            
            if (this.isUpdating) return;
            
            this.isUpdating = true;
            
            setTimeout(function() {
                self.updateQueue.forEach(function(elementId) {
                    self.updateElement(elementId);
                });
                
                self.updateQueue.clear();
                self.isUpdating = false;
            }, this.config.updateDelay);
        },

        // Update specific element
        updateElement: function(elementId) {
            var element = elementor.elements.findWhere({ id: elementId });
            if (!element) return;
            
            var settings = element.get('settings').toJSON();
            var $previewElement = this.findPreviewElement(elementId);
            
            if (!$previewElement.length) {
                console.log('Preview element not found:', elementId);
                return;
            }
            
            this.applyWatercolorToElement($previewElement, elementId, settings);
        },

        // Find preview element
        findPreviewElement: function(elementId) {
            var $preview = $('#elementor-preview-iframe');
            if (!$preview.length) return $();
            
            var previewDoc = $preview[0].contentDocument || $preview[0].contentWindow.document;
            return $(previewDoc).find('.elementor-element-' + elementId);
        },

        // Apply watercolor to element
        applyWatercolorToElement: function($element, elementId, settings) {
            // Remove all watercolor classes first
            $element.removeClass(function(index, className) {
                return (className.match(/\bwatercolor-\S+/g) || []).join(' ');
            });
            
            // Remove inline styles
            this.removeInlineStyles(elementId);
            
            if (settings.watercolor_enable === 'yes') {
                // Add classes
                $element.addClass('watercolor-active');
                $element.addClass('watercolor-element-' + elementId);
                $element.addClass('watercolor-effect-' + (settings.watercolor_effect || 'acuarela'));
                
                // Apply inline styles
                this.applyInlineStyles($element, elementId, settings);
                
                console.log('Applied watercolor to element:', elementId);
            } else {
                console.log('Removed watercolor from element:', elementId);
            }
        },

        // Apply inline styles
        applyInlineStyles: function($element, elementId, settings) {
            var css = this.generateCSS(elementId, settings);
            
            if (!css) return;
            
            // Inject CSS into preview
            var $preview = $('#elementor-preview-iframe');
            if ($preview.length) {
                var previewDoc = $preview[0].contentDocument || $preview[0].contentWindow.document;
                var $head = $(previewDoc).find('head');
                
                // Remove old styles
                $head.find('#watercolor-inline-' + elementId).remove();
                
                // Add new styles
                $head.append('<style id="watercolor-inline-' + elementId + '">' + css + '</style>');
            }
        },

        // Remove inline styles
        removeInlineStyles: function(elementId) {
            var $preview = $('#elementor-preview-iframe');
            if ($preview.length) {
                var previewDoc = $preview[0].contentDocument || $preview[0].contentWindow.document;
                $(previewDoc).find('#watercolor-inline-' + elementId).remove();
            }
        },

        // Generate CSS
        generateCSS: function(elementId, settings) {
            var baseColor = settings.watercolor_base_color || '#ffffff';
            var color1 = settings.watercolor_color_1 || '#87CEEB';
            var color2 = settings.watercolor_color_2 || '#FFB6C1';
            var opacity = (settings.watercolor_opacity && settings.watercolor_opacity.size) ? 
                         settings.watercolor_opacity.size / 100 : 0.6;
            var speed = (settings.watercolor_animation_speed && settings.watercolor_animation_speed.size) ? 
                       settings.watercolor_animation_speed.size : 20;
            var blur = (settings.watercolor_blur && settings.watercolor_blur.size) ? 
                      settings.watercolor_blur.size : 40;
            var effect = settings.watercolor_effect || 'acuarela';
            
            if (effect === 'barrido') {
                return this.generateBarridoCSS(elementId, baseColor, color1, color2, opacity, speed, blur);
            } else {
                return this.generateAcuarelaCSS(elementId, baseColor, color1, color2, opacity, speed, blur);
            }
        },

        // Generate Acuarela CSS
        generateAcuarelaCSS: function(elementId, baseColor, color1, color2, opacity, speed, blur) {
            var rgba1 = this.hexToRgba(color1, opacity);
            var rgba2 = this.hexToRgba(color2, opacity);
            
            return `
            .watercolor-element-${elementId} {
                background-color: ${baseColor} !important;
            }
            
            .watercolor-element-${elementId}:before {
                background: radial-gradient(circle at 30% 40%, ${rgba1} 0%, transparent 50%),
                           radial-gradient(circle at 70% 60%, ${rgba1} 0%, transparent 40%) !important;
                filter: blur(${blur}px) !important;
                animation: watercolor-acuarela-1-${elementId} ${speed}s ease-in-out infinite !important;
            }
            
            .watercolor-element-${elementId}:after {
                background: radial-gradient(circle at 60% 30%, ${rgba2} 0%, transparent 50%),
                           radial-gradient(circle at 40% 70%, ${rgba2} 0%, transparent 40%) !important;
                filter: blur(${blur}px) !important;
                animation: watercolor-acuarela-2-${elementId} ${speed * 1.5}s ease-in-out infinite !important;
            }
            
            @keyframes watercolor-acuarela-1-${elementId} {
                0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); }
                33% { transform: translate(2%, -3%) rotate(1deg) scale(1.02); }
                66% { transform: translate(-1%, 2%) rotate(-1deg) scale(0.98); }
            }
            
            @keyframes watercolor-acuarela-2-${elementId} {
                0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); }
                33% { transform: translate(-2%, 1%) rotate(-1deg) scale(0.98); }
                66% { transform: translate(3%, -2%) rotate(1deg) scale(1.03); }
            }`;
        },

        // Generate Barrido CSS
        generateBarridoCSS: function(elementId, baseColor, color1, color2, opacity, speed, blur) {
            var rgba1 = this.hexToRgba(color1, opacity);
            var rgba2 = this.hexToRgba(color2, opacity);
            
            return `
            .watercolor-element-${elementId} {
                background-color: ${baseColor} !important;
            }
            
            .watercolor-element-${elementId}:before {
                background: linear-gradient(45deg, 
                    ${rgba1} 0%, 
                    ${rgba2} 25%, 
                    ${rgba1} 50%, 
                    ${rgba2} 75%, 
                    ${rgba1} 100%) !important;
                background-size: 400% 400% !important;
                filter: blur(${blur}px) !important;
                animation: watercolor-barrido-${elementId} ${speed}s ease-in-out infinite !important;
            }
            
            @keyframes watercolor-barrido-${elementId} {
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
            }`;
        },

        // Update all elements
        updateAllElements: function() {
            var self = this;
            
            console.log('Updating all watercolor elements...');
            
            if (!elementor.elements) return;
            
            elementor.elements.each(function(element) {
                var settings = element.get('settings').toJSON();
                if (settings.watercolor_enable === 'yes') {
                    var elementId = element.get('id');
                    self.updateElement(elementId);
                }
            });
        },

        // On element activated
        onElementActivated: function(activeSection) {
            // Force update when element is selected
            setTimeout(() => {
                this.updateAllElements();
            }, 100);
        },

        // Utility: hex to rgba
        hexToRgba: function(hex, alpha) {
            hex = hex.replace('#', '');
            
            var r, g, b;
            if (hex.length === 3) {
                r = parseInt(hex[0] + hex[0], 16);
                g = parseInt(hex[1] + hex[1], 16);
                b = parseInt(hex[2] + hex[2], 16);
            } else {
                r = parseInt(hex.substr(0, 2), 16);
                g = parseInt(hex.substr(2, 2), 16);
                b = parseInt(hex.substr(4, 2), 16);
            }
            
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
    };

    // Initialize when Elementor is ready
    $(window).on('elementor:init', function() {
        WatercolorEditor.init();
    });

})(jQuery);