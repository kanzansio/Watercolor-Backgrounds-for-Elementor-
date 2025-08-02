/**
 * Watercolor Backgrounds - Frontend JavaScript v3.0
 * Optimized for performance and smooth animations
 */

(function($) {
    'use strict';

    var WatercolorFrontend = {
        
        // Configuration
        config: {
            intersectionThreshold: 0.1,
            performanceCheckDelay: 3000,
            animationRestartDelay: 100
        },

        // State
        state: {
            activeElements: new Map(),
            observer: null,
            isInitialized: false
        },

        // Initialize
        init: function() {
            if (this.state.isInitialized) return;
            
            console.log('Watercolor Frontend v3.0 initializing...');
            
            this.state.isInitialized = true;
            
            // Setup intersection observer for performance
            this.setupIntersectionObserver();
            
            // Initialize all elements
            this.initializeElements();
            
            // Bind events
            this.bindEvents();
            
            // Fix animations after page load
            this.ensureAnimationsRunning();
        },

        // Setup intersection observer
        setupIntersectionObserver: function() {
            var self = this;
            
            if (!window.IntersectionObserver) {
                // Fallback: activate all elements if IntersectionObserver not supported
                $('.watercolor-active').each(function() {
                    self.activateElement(this);
                });
                return;
            }

            this.state.observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        self.activateElement(entry.target);
                    } else {
                        self.deactivateElement(entry.target);
                    }
                });
            }, {
                threshold: this.config.intersectionThreshold
            });
        },

        // Initialize elements
        initializeElements: function() {
            var self = this;
            
            $('.watercolor-active').each(function() {
                // Store element data
                var $element = $(this);
                var elementId = this.className.match(/watercolor-element-(\w+)/);
                
                if (elementId) {
                    self.state.activeElements.set(elementId[1], {
                        element: this,
                        $element: $element,
                        settings: self.parseSettings($element)
                    });
                }
                
                // Observe element if observer exists
                if (self.state.observer) {
                    self.state.observer.observe(this);
                } else {
                    self.activateElement(this);
                }
            });
        },

        // Parse element settings
        parseSettings: function($element) {
            var settingsData = $element.attr('data-watercolor-settings');
            
            if (!settingsData) {
                return {
                    watercolor_enable: 'yes',
                    watercolor_effect: 'acuarela'
                };
            }
            
            try {
                return JSON.parse(settingsData);
            } catch (e) {
                console.warn('Error parsing watercolor settings:', e);
                return {
                    watercolor_enable: 'yes',
                    watercolor_effect: 'acuarela'
                };
            }
        },

        // Activate element
        activateElement: function(element) {
            var $element = $(element);
            
            // Add activation class
            $element.addClass('watercolor-activated');
            
            // Ensure animations are running
            this.restartAnimations($element);
            
            // Setup interaction events
            this.setupInteractionEvents($element);
        },

        // Deactivate element (when out of viewport)
        deactivateElement: function(element) {
            var $element = $(element);
            
            // Don't fully deactivate, just pause animations for performance
            $element.addClass('watercolor-paused');
        },

        // Restart animations
        restartAnimations: function($element) {
            // Force animation restart by toggling a class
            $element.removeClass('watercolor-animation-active');
            
            // Use requestAnimationFrame for smooth restart
            requestAnimationFrame(function() {
                $element.addClass('watercolor-animation-active');
            });
        },

        // Setup interaction events
        setupInteractionEvents: function($element) {
            // Prevent multiple event bindings
            $element.off('.watercolor');
            
            // Pause on hover (optional, can be removed if not desired)
            $element.on('mouseenter.watercolor', function() {
                $(this).addClass('watercolor-hover');
            });
            
            $element.on('mouseleave.watercolor', function() {
                $(this).removeClass('watercolor-hover');
            });
        },

        // Bind global events
        bindEvents: function() {
            var self = this;
            
            // Handle visibility changes
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    // Restart animations when page becomes visible
                    self.ensureAnimationsRunning();
                }
            });
            
            // Handle window focus
            $(window).on('focus', function() {
                self.ensureAnimationsRunning();
            });
            
            // Reinitialize on Elementor frontend init (for preview)
            $(window).on('elementor/frontend/init', function() {
                setTimeout(function() {
                    self.reinitialize();
                }, 1000);
            });
        },

        // Ensure animations are running
        ensureAnimationsRunning: function() {
            var self = this;
            
            setTimeout(function() {
                $('.watercolor-active').each(function() {
                    var $element = $(this);
                    
                    // Check if animations are running
                    var computedStyle = window.getComputedStyle(this, ':before');
                    var animationName = computedStyle.getPropertyValue('animation-name');
                    
                    if (animationName === 'none' || !animationName) {
                        console.log('Restarting animation for element');
                        self.restartAnimations($element);
                    }
                });
            }, this.config.animationRestartDelay);
        },

        // Reinitialize (for dynamic content)
        reinitialize: function() {
            console.log('Reinitializing Watercolor Frontend...');
            
            // Clear existing observers
            if (this.state.observer) {
                this.state.observer.disconnect();
            }
            
            // Clear state
            this.state.activeElements.clear();
            
            // Reinitialize
            this.setupIntersectionObserver();
            this.initializeElements();
            this.ensureAnimationsRunning();
        },

        // Public API
        refresh: function() {
            this.reinitialize();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        WatercolorFrontend.init();
    });

    // Also initialize on window load to ensure everything is ready
    $(window).on('load', function() {
        WatercolorFrontend.ensureAnimationsRunning();
    });

    // Expose to global scope for debugging
    window.WatercolorFrontend = WatercolorFrontend;

})(jQuery);