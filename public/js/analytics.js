/**
 * CosmicHub Analytics Tracking Library
 * 
 * Client-side analytics and event tracking for Phase 3 beta testing
 * 
 * Usage:
 * - Include this script in your pages
 * - Call trackEvent() to track custom events
 * - Call trackPageView() to track page views
 * - Call trackUserAction() to track user interactions
 * - Call trackError() to track client-side errors
 */

(function(window, document) {
    'use strict';
    
    // Configuration
    const CONFIG = {
        endpoint: '/analytics/track',
        batchSize: 10,
        flushInterval: 5000, // 5 seconds
        maxRetries: 3,
        retryDelay: 1000,
        sessionTimeout: 30 * 60 * 1000, // 30 minutes
        enableDebug: false,
        enableAutoTracking: true,
        enableErrorTracking: true,
        enablePerformanceTracking: true,
        enableUserEngagement: true
    };
    
    // State management
    let eventQueue = [];
    let sessionId = null;
    let userId = null;
    let isInitialized = false;
    let flushTimer = null;
    let sessionTimer = null;
    let pageLoadTime = Date.now();
    let lastActivityTime = Date.now();
    
    // Performance tracking
    let performanceMetrics = {
        pageLoadStart: performance.now(),
        domContentLoaded: null,
        windowLoaded: null,
        firstPaint: null,
        firstContentfulPaint: null,
        largestContentfulPaint: null,
        firstInputDelay: null,
        cumulativeLayoutShift: 0
    };
    
    // User engagement tracking
    let engagementMetrics = {
        scrollDepth: 0,
        timeOnPage: 0,
        clickCount: 0,
        keystrokes: 0,
        mouseMovements: 0,
        focusTime: 0,
        idleTime: 0
    };
    
    // Error tracking
    let errorCount = 0;
    let lastError = null;
    
    /**
     * Initialize the analytics library
     */
    function init(options = {}) {
        if (isInitialized) {
            debug('Analytics already initialized');
            return;
        }
        
        // Merge configuration
        Object.assign(CONFIG, options);
        
        // Generate session ID
        sessionId = generateSessionId();
        
        // Get user ID from global variable or cookie
        userId = window.currentUserId || getCookie('user_id') || null;
        
        // Set up automatic tracking
        if (CONFIG.enableAutoTracking) {
            setupAutoTracking();
        }
        
        // Set up error tracking
        if (CONFIG.enableErrorTracking) {
            setupErrorTracking();
        }
        
        // Set up performance tracking
        if (CONFIG.enablePerformanceTracking) {
            setupPerformanceTracking();
        }
        
        // Set up user engagement tracking
        if (CONFIG.enableUserEngagement) {
            setupEngagementTracking();
        }
        
        // Start flush timer
        startFlushTimer();
        
        // Start session timer
        startSessionTimer();
        
        // Track page load
        trackPageView();
        
        isInitialized = true;
        debug('Analytics initialized', { sessionId, userId });
    }
    
    /**
     * Track a custom event
     */
    function trackEvent(eventType, data = {}, immediate = false) {
        if (!isInitialized) {
            debug('Analytics not initialized, queuing event');
        }
        
        const event = createEvent(eventType, data);
        
        if (immediate) {
            sendEvents([event]);
        } else {
            queueEvent(event);
        }
        
        debug('Event tracked', event);
    }
    
    /**
     * Track page view
     */
    function trackPageView(page = null, title = null) {
        const data = {
            page: page || window.location.pathname,
            title: title || document.title,
            url: window.location.href,
            referrer: document.referrer,
            user_agent: navigator.userAgent,
            screen_resolution: `${screen.width}x${screen.height}`,
            viewport_size: `${window.innerWidth}x${window.innerHeight}`,
            language: navigator.language,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            connection_type: getConnectionType(),
            device_type: getDeviceType()
        };
        
        trackEvent('page_view', data);
    }
    
    /**
     * Track user action
     */
    function trackUserAction(action, data = {}) {
        const actionData = {
            action: action,
            page: window.location.pathname,
            timestamp: Date.now(),
            ...data
        };
        
        trackEvent('user_action', actionData);
    }
    
    /**
     * Track error
     */
    function trackError(error, context = {}) {
        errorCount++;
        lastError = {
            message: error.message || error,
            stack: error.stack,
            timestamp: Date.now()
        };
        
        const errorData = {
            error_message: error.message || error,
            error_stack: error.stack,
            error_type: error.name || 'Error',
            page: window.location.pathname,
            user_agent: navigator.userAgent,
            error_count: errorCount,
            context: context
        };
        
        trackEvent('error', errorData, true); // Send immediately
    }
    
    /**
     * Track performance metrics
     */
    function trackPerformance(metrics = {}) {
        const performanceData = {
            ...performanceMetrics,
            ...metrics,
            page: window.location.pathname,
            timestamp: Date.now()
        };
        
        trackEvent('performance', performanceData);
    }
    
    /**
     * Track user engagement
     */
    function trackEngagement() {
        const engagementData = {
            ...engagementMetrics,
            time_on_page: Date.now() - pageLoadTime,
            page: window.location.pathname,
            timestamp: Date.now()
        };
        
        trackEvent('user_engagement', engagementData);
    }
    
    /**
     * Track conversion event
     */
    function trackConversion(conversionType, value = null, currency = 'USD') {
        const conversionData = {
            conversion_type: conversionType,
            value: value,
            currency: currency,
            page: window.location.pathname,
            session_id: sessionId,
            timestamp: Date.now()
        };
        
        trackEvent('conversion', conversionData, true);
    }
    
    /**
     * Track feature usage
     */
    function trackFeatureUsage(featureName, action = 'used', metadata = {}) {
        const featureData = {
            feature_name: featureName,
            action: action,
            metadata: metadata,
            page: window.location.pathname,
            timestamp: Date.now()
        };
        
        trackEvent('feature_usage', featureData);
    }
    
    /**
     * Set user ID
     */
    function setUserId(id) {
        userId = id;
        setCookie('user_id', id, 365);
        debug('User ID set', id);
    }
    
    /**
     * Set user properties
     */
    function setUserProperties(properties) {
        trackEvent('user_properties', properties);
    }
    
    /**
     * Flush events immediately
     */
    function flush() {
        if (eventQueue.length > 0) {
            sendEvents([...eventQueue]);
            eventQueue = [];
        }
    }
    
    /**
     * Create event object
     */
    function createEvent(eventType, data) {
        return {
            event_type: eventType,
            session_id: sessionId,
            user_id: userId,
            timestamp: Date.now(),
            page_url: window.location.href,
            data: data
        };
    }
    
    /**
     * Queue event for batch sending
     */
    function queueEvent(event) {
        eventQueue.push(event);
        
        if (eventQueue.length >= CONFIG.batchSize) {
            flush();
        }
    }
    
    /**
     * Send events to server
     */
    function sendEvents(events, retryCount = 0) {
        if (events.length === 0) return;
        
        fetch(CONFIG.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ events: events })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            debug('Events sent successfully', data);
        })
        .catch(error => {
            debug('Failed to send events', error);
            
            if (retryCount < CONFIG.maxRetries) {
                setTimeout(() => {
                    sendEvents(events, retryCount + 1);
                }, CONFIG.retryDelay * Math.pow(2, retryCount));
            }
        });
    }
    
    /**
     * Setup automatic tracking
     */
    function setupAutoTracking() {
        // Track clicks
        document.addEventListener('click', function(e) {
            engagementMetrics.clickCount++;
            
            const element = e.target;
            const tagName = element.tagName.toLowerCase();
            const className = element.className;
            const id = element.id;
            const text = element.textContent?.substring(0, 100);
            
            if (tagName === 'a' || tagName === 'button' || element.onclick) {
                trackUserAction('click', {
                    element_tag: tagName,
                    element_class: className,
                    element_id: id,
                    element_text: text,
                    x: e.clientX,
                    y: e.clientY
                });
            }
        });
        
        // Track form submissions
        document.addEventListener('submit', function(e) {
            const form = e.target;
            trackUserAction('form_submit', {
                form_id: form.id,
                form_class: form.className,
                form_action: form.action,
                form_method: form.method
            });
        });
        
        // Track scroll depth
        let maxScrollDepth = 0;
        window.addEventListener('scroll', throttle(function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollDepth = Math.round((scrollTop / documentHeight) * 100);
            
            if (scrollDepth > maxScrollDepth) {
                maxScrollDepth = scrollDepth;
                engagementMetrics.scrollDepth = scrollDepth;
                
                // Track milestone scroll depths
                if (scrollDepth >= 25 && scrollDepth < 50 && maxScrollDepth < 25) {
                    trackUserAction('scroll_depth', { depth: 25 });
                } else if (scrollDepth >= 50 && scrollDepth < 75 && maxScrollDepth < 50) {
                    trackUserAction('scroll_depth', { depth: 50 });
                } else if (scrollDepth >= 75 && scrollDepth < 100 && maxScrollDepth < 75) {
                    trackUserAction('scroll_depth', { depth: 75 });
                } else if (scrollDepth >= 100 && maxScrollDepth < 100) {
                    trackUserAction('scroll_depth', { depth: 100 });
                }
            }
        }, 250));
        
        // Track page visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                trackUserAction('page_hidden');
            } else {
                trackUserAction('page_visible');
            }
        });
        
        // Track page unload
        window.addEventListener('beforeunload', function() {
            trackEngagement();
            flush();
        });
    }
    
    /**
     * Setup error tracking
     */
    function setupErrorTracking() {
        window.addEventListener('error', function(e) {
            trackError({
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno,
                stack: e.error?.stack
            });
        });
        
        window.addEventListener('unhandledrejection', function(e) {
            trackError({
                message: 'Unhandled Promise Rejection',
                reason: e.reason,
                stack: e.reason?.stack
            });
        });
    }
    
    /**
     * Setup performance tracking
     */
    function setupPerformanceTracking() {
        // Track DOM content loaded
        document.addEventListener('DOMContentLoaded', function() {
            performanceMetrics.domContentLoaded = performance.now();
        });
        
        // Track window loaded
        window.addEventListener('load', function() {
            performanceMetrics.windowLoaded = performance.now();
            
            // Track paint metrics
            if ('getEntriesByType' in performance) {
                const paintEntries = performance.getEntriesByType('paint');
                paintEntries.forEach(entry => {
                    if (entry.name === 'first-paint') {
                        performanceMetrics.firstPaint = entry.startTime;
                    } else if (entry.name === 'first-contentful-paint') {
                        performanceMetrics.firstContentfulPaint = entry.startTime;
                    }
                });
            }
            
            // Track performance after a delay
            setTimeout(() => {
                trackPerformance();
            }, 1000);
        });
        
        // Track Largest Contentful Paint
        if ('PerformanceObserver' in window) {
            try {
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    performanceMetrics.largestContentfulPaint = lastEntry.startTime;
                });
                observer.observe({ entryTypes: ['largest-contentful-paint'] });
            } catch (e) {
                debug('LCP observer not supported');
            }
        }
        
        // Track First Input Delay
        if ('PerformanceObserver' in window) {
            try {
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    entries.forEach(entry => {
                        if (entry.processingStart && entry.startTime) {
                            performanceMetrics.firstInputDelay = entry.processingStart - entry.startTime;
                        }
                    });
                });
                observer.observe({ entryTypes: ['first-input'] });
            } catch (e) {
                debug('FID observer not supported');
            }
        }
    }
    
    /**
     * Setup engagement tracking
     */
    function setupEngagementTracking() {
        // Track mouse movements
        document.addEventListener('mousemove', throttle(function() {
            engagementMetrics.mouseMovements++;
            lastActivityTime = Date.now();
        }, 1000));
        
        // Track keystrokes
        document.addEventListener('keydown', function() {
            engagementMetrics.keystrokes++;
            lastActivityTime = Date.now();
        });
        
        // Track focus/blur
        window.addEventListener('focus', function() {
            lastActivityTime = Date.now();
        });
        
        window.addEventListener('blur', function() {
            engagementMetrics.idleTime += Date.now() - lastActivityTime;
        });
        
        // Track engagement periodically
        setInterval(function() {
            engagementMetrics.timeOnPage = Date.now() - pageLoadTime;
            
            if (Date.now() - lastActivityTime > 60000) { // 1 minute idle
                engagementMetrics.idleTime += Date.now() - lastActivityTime;
                lastActivityTime = Date.now();
            }
        }, 30000); // Every 30 seconds
    }
    
    /**
     * Start flush timer
     */
    function startFlushTimer() {
        flushTimer = setInterval(flush, CONFIG.flushInterval);
    }
    
    /**
     * Start session timer
     */
    function startSessionTimer() {
        sessionTimer = setInterval(function() {
            if (Date.now() - lastActivityTime > CONFIG.sessionTimeout) {
                // Session expired, generate new session ID
                sessionId = generateSessionId();
                lastActivityTime = Date.now();
                trackEvent('session_start');
            }
        }, 60000); // Check every minute
    }
    
    /**
     * Utility functions
     */
    function generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
    
    function setCookie(name, value, days) {
        const expires = new Date(Date.now() + days * 864e5).toUTCString();
        document.cookie = `${name}=${value}; expires=${expires}; path=/`;
    }
    
    function getConnectionType() {
        return navigator.connection?.effectiveType || 'unknown';
    }
    
    function getDeviceType() {
        const width = window.innerWidth;
        if (width < 768) return 'mobile';
        if (width < 1024) return 'tablet';
        return 'desktop';
    }
    
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    function debug(...args) {
        if (CONFIG.enableDebug) {
            console.log('[Analytics]', ...args);
        }
    }
    
    // Public API
    window.CosmicAnalytics = {
        init: init,
        trackEvent: trackEvent,
        trackPageView: trackPageView,
        trackUserAction: trackUserAction,
        trackError: trackError,
        trackPerformance: trackPerformance,
        trackEngagement: trackEngagement,
        trackConversion: trackConversion,
        trackFeatureUsage: trackFeatureUsage,
        setUserId: setUserId,
        setUserProperties: setUserProperties,
        flush: flush
    };
    
    // Backward compatibility
    window.trackEvent = trackEvent;
    window.trackPageView = trackPageView;
    window.trackUserAction = trackUserAction;
    window.trackError = trackError;
    
    // Auto-initialize in production
if (typeof window !== 'undefined') {
        document.addEventListener('DOMContentLoaded', function() {
            init();
        });
    }
    
})(window, document);