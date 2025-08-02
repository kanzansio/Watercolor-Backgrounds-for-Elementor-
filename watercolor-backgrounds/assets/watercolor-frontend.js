/**
 * Watercolor Backgrounds - Frontend JavaScript
 * Optimizaciones y efectos para el frontend
 */

(function($) {
    'use strict';

    var WatercolorFrontend = {
        
        // Configuración
        config: {
            reducedMotionQuery: '(prefers-reduced-motion: reduce)',
            intersectionThreshold: 0.1,
            performanceMonitoring: true,
            lazyLoadOffset: 100
        },

        // Estado
        state: {
            isReducedMotion: false,
            visibleElements: new Set(),
            performanceMode: 'auto' // auto, high, low
        },

        // Inicializar
        init: function() {
            this.detectCapabilities();
            this.setupIntersectionObserver();
            this.bindEvents();
            this.initializeElements();
            this.optimizePerformance();
            
            console.log('Watercolor Frontend initialized');
        },

        // Detectar capacidades del dispositivo
        detectCapabilities: function() {
            // Detectar preferencias de movimiento
            this.state.isReducedMotion = window.matchMedia && 
                window.matchMedia(this.config.reducedMotionQuery).matches;

            // Detectar rendimiento del dispositivo
            if (navigator.hardwareConcurrency) {
                if (navigator.hardwareConcurrency <= 2) {
                    this.state.performanceMode = 'low';
                } else if (navigator.hardwareConcurrency >= 8) {
                    this.state.performanceMode = 'high';
                }
            }

            // Detectar conexión lenta
            if (navigator.connection && navigator.connection.effectiveType) {
                if (navigator.connection.effectiveType === 'slow-2g' || 
                    navigator.connection.effectiveType === '2g') {
                    this.state.performanceMode = 'low';
                }
            }

            console.log('Performance mode:', this.state.performanceMode);
        },

        // Configurar Intersection Observer para lazy loading
        setupIntersectionObserver: function() {
            var self = this;
            
            if (!window.IntersectionObserver) return;

            this.observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        self.activateWatercolorElement(entry.target);
                        self.state.visibleElements.add(entry.target);
                    } else {
                        self.deactivateWatercolorElement(entry.target);
                        self.state.visibleElements.delete(entry.target);
                    }
                });
            }, {
                threshold: this.config.intersectionThreshold,
                rootMargin: this.config.lazyLoadOffset + 'px'
            });
        },

        // Activar elemento watercolor cuando es visible
        activateWatercolorElement: function(element) {
            var $element = $(element);
            
            if ($element.hasClass('watercolor-activated')) return;
            
            $element.addClass('watercolor-activated');
            
            // Aplicar optimizaciones según el modo de rendimiento
            this.applyPerformanceOptimizations($element);
            
            // Inicializar animaciones si están habilitadas
            this.initializeAnimations($element);
            
            // Configurar eventos de interacción
            this.setupInteractionEvents($element);
        },

        // Desactivar elemento cuando no es visible
        deactivateWatercolorElement: function(element) {
            var $element = $(element);
            
            if (this.state.performanceMode === 'low') {
                // En modo bajo rendimiento, pausar animaciones cuando no es visible
                $element.find(':before').css('animation-play-state', 'paused');
            }
        },

        // Aplicar optimizaciones de rendimiento
        applyPerformanceOptimizations: function($element) {
            var $pseudo = $element.find(':before');
            
            switch (this.state.performanceMode) {
                case 'low':
                    // Reducir efectos en dispositivos lentos
                    $element.addClass('watercolor-performance-low');
                    break;
                
                case 'high':
                    // Activar efectos adicionales en dispositivos potentes
                    $element.addClass('watercolor-performance-high');
                    break;
                
                default:
                    $element.addClass('watercolor-performance-auto');
            }

            // Optimizar para reduced motion
            if (this.state.isReducedMotion) {
                $element.addClass('watercolor-reduced-motion');
            }
        },

        // Inicializar animaciones
        initializeAnimations: function($element) {
            var settings = this.getElementSettings($element);
            
            if (!settings || settings.watercolor_animation !== 'yes') return;
            
            var animationSpeed = settings.watercolor_animation_speed && settings.watercolor_animation_speed.size ? 
                               settings.watercolor_animation_speed.size : 30;
            
            // Aplicar velocidad de animación personalizada
            $element.css('--watercolor-animation-duration', animationSpeed + 's');
            
            // Sincronizar animaciones múltiples
            this.synchronizeAnimations($element);
        },

        // Sincronizar animaciones de múltiples elementos
        synchronizeAnimations: function($element) {
            // Añadir un delay aleatorio pequeño para evitar sincronización perfecta
            var delay = Math.random() * 2; // 0-2 segundos
            $element.css('animation-delay', delay + 's');
        },

        // Configurar eventos de interacción
        setupInteractionEvents: function($element) {
            var self = this;
            
            // Pausar animación al hacer hover
            $element.on('mouseenter.watercolor', function() {
                $(this).addClass('watercolor-paused');
            });
            
            $element.on('mouseleave.watercolor', function() {
                $(this).removeClass('watercolor-paused');
            });
            
            // Optimizar para dispositivos táctiles
            if ('ontouchstart' in window) {
                $element.on('touchstart.watercolor', function() {
                    $(this).addClass('watercolor-touched');
                });
                
                $element.on('touchend.watercolor', function() {
                    setTimeout(function() {
                        $element.removeClass('watercolor-touched');
                    }, 300);
                });
            }
        },

        // Obtener configuraciones del elemento
        getElementSettings: function($element) {
            var settingsData = $element.attr('data-watercolor-settings');
            if (!settingsData) return null;
            
            try {
                return JSON.parse(settingsData);
            } catch (e) {
                console.warn('Error parsing watercolor settings:', e);
                return null;
            }
        },

        // Inicializar todos los elementos existentes
        initializeElements: function() {
            var self = this;
            
            $('.watercolor-active').each(function() {
                if (self.observer) {
                    self.observer.observe(this);
                } else {
                    // Fallback sin Intersection Observer
                    self.activateWatercolorElement(this);
                }
            });
        },

        // Optimizar rendimiento general
        optimizePerformance: function() {
            // Throttle scroll events
            this.throttledScrollHandler = this.throttle(this.handleScroll.bind(this), 16);
            $(window).on('scroll.watercolor', this.throttledScrollHandler);
            
            // Optimizar resize events
            this.debouncedResizeHandler = this.debounce(this.handleResize.bind(this), 250);
            $(window).on('resize.watercolor', this.debouncedResizeHandler);
            
            // Monitorear rendimiento
            if (this.config.performanceMonitoring) {
                this.monitorPerformance();
            }
        },

        // Manejar scroll
        handleScroll: function() {
            // Pausar animaciones de elementos no visibles en viewport
            if (this.state.performanceMode === 'low') {
                this.pauseOffscreenAnimations();
            }
        },

        // Manejar resize
        handleResize: function() {
            // Recalcular optimizaciones en cambio de tamaño
            this.detectCapabilities();
            this.updateAllElements();
        },

        // Pausar animaciones fuera de pantalla
        pauseOffscreenAnimations: function() {
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            
            $('.watercolor-active').each(function() {
                var $this = $(this);
                var elementTop = $this.offset().top;
                var elementBottom = elementTop + $this.height();
                
                var isVisible = elementBottom >= viewportTop && elementTop <= viewportBottom;
                
                if (isVisible) {
                    $this.removeClass('watercolor-offscreen');
                } else {
                    $this.addClass('watercolor-offscreen');
                }
            });
        },

        // Actualizar todos los elementos
        updateAllElements: function() {
            var self = this;
            
            $('.watercolor-active').each(function() {
                self.applyPerformanceOptimizations($(this));
            });
        },

        // Monitorear rendimiento
        monitorPerformance: function() {
            var self = this;
            
            // Monitorear FPS
            if (window.performance && window.performance.now) {
                this.monitorFPS();
            }
            
            // Ajustar automáticamente según el rendimiento
            setTimeout(function() {
                self.autoAdjustPerformance();
            }, 5000);
        },

        // Monitorear FPS
        monitorFPS: function() {
            var self = this;
            var frames = 0;
            var startTime = performance.now();
            
            function countFrames() {
                frames++;
                if (frames % 60 === 0) {
                    var currentTime = performance.now();
                    var fps = Math.round(60000 / (currentTime - startTime));
                    startTime = currentTime;
                    
                    if (fps < 30 && self.state.performanceMode !== 'low') {
                        console.log('Low FPS detected, switching to performance mode');
                        self.state.performanceMode = 'low';
                        self.updateAllElements();
                    }
                }
                requestAnimationFrame(countFrames);
            }
            
            requestAnimationFrame(countFrames);
        },

        // Ajustar rendimiento automáticamente
        autoAdjustPerformance: function() {
            var activeElements = $('.watercolor-active').length;
            
            if (activeElements > 5 && this.state.performanceMode === 'auto') {
                this.state.performanceMode = 'low';
                this.updateAllElements();
                console.log('Many watercolor elements detected, optimizing performance');
            }
        },

        // Eventos del window
        bindEvents: function() {
            var self = this;
            
            // Detectar cambios en preferencias de movimiento
            if (window.matchMedia) {
                var mediaQuery = window.matchMedia(this.config.reducedMotionQuery);
                mediaQuery.addListener(function(e) {
                    self.state.isReducedMotion = e.matches;
                    self.updateAllElements();
                });
            }
            
            // Detectar cambios de visibilidad de la página
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    $('.watercolor-active').addClass('watercolor-page-hidden');
                } else {
                    $('.watercolor-active').removeClass('watercolor-page-hidden');
                }
            });
        },

        // Métodos de utilidad
        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var args = arguments;
                var context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() {
                        inThrottle = false;
                    }, limit);
                }
            };
        },

        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        // API pública
        refresh: function() {
            this.initializeElements();
        },

        setPerformanceMode: function(mode) {
            this.state.performanceMode = mode;
            this.updateAllElements();
        },

        destroy: function() {
            // Limpiar eventos
            $(window).off('.watercolor');
            $('.watercolor-active').off('.watercolor');
            
            // Limpiar observer
            if (this.observer) {
                this.observer.disconnect();
            }
            
            console.log('Watercolor Frontend destroyed');
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        WatercolorFrontend.init();
    });

    // Reinicializar en Elementor preview
    $(window).on('elementor/frontend/init', function() {
        setTimeout(function() {
            WatercolorFrontend.refresh();
        }, 1000);
    });

    // API global
    window.WatercolorFrontend = WatercolorFrontend;

})(jQuery);