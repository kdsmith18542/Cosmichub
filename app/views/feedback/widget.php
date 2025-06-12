<?php
/**
 * Quick Feedback Widget
 * 
 * Embeddable widget for collecting quick feedback during Phase 3 beta testing
 */

$widgetId = $widgetId ?? 'feedback-widget-' . uniqid();
$position = $position ?? 'bottom-right'; // bottom-right, bottom-left, top-right, top-left
$theme = $theme ?? 'light'; // light, dark
$showRating = $showRating ?? true;
$showEmail = $showEmail ?? false;
$placeholder = $placeholder ?? 'Share your feedback...';
$triggerText = $triggerText ?? 'Feedback';
?>

<style>
    .feedback-widget {
        position: fixed;
        z-index: 9999;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .feedback-widget.bottom-right {
        bottom: 20px;
        right: 20px;
    }
    
    .feedback-widget.bottom-left {
        bottom: 20px;
        left: 20px;
    }
    
    .feedback-widget.top-right {
        top: 20px;
        right: 20px;
    }
    
    .feedback-widget.top-left {
        top: 20px;
        left: 20px;
    }
    
    .feedback-trigger {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .feedback-trigger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    .feedback-trigger.dark {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
    }
    
    .feedback-trigger.dark:hover {
        box-shadow: 0 6px 20px rgba(44, 62, 80, 0.4);
    }
    
    .feedback-panel {
        position: absolute;
        width: 320px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        padding: 20px;
        display: none;
        animation: slideIn 0.3s ease;
    }
    
    .feedback-panel.dark {
        background: #2c3e50;
        color: white;
    }
    
    .feedback-widget.bottom-right .feedback-panel,
    .feedback-widget.top-right .feedback-panel {
        right: 0;
    }
    
    .feedback-widget.bottom-left .feedback-panel,
    .feedback-widget.top-left .feedback-panel {
        left: 0;
    }
    
    .feedback-widget.bottom-right .feedback-panel,
    .feedback-widget.bottom-left .feedback-panel {
        bottom: 60px;
    }
    
    .feedback-widget.top-right .feedback-panel,
    .feedback-widget.top-left .feedback-panel {
        top: 60px;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .feedback-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .feedback-panel.dark .feedback-header {
        border-bottom-color: #34495e;
    }
    
    .feedback-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }
    
    .feedback-close {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: #999;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .feedback-close:hover {
        color: #666;
    }
    
    .feedback-panel.dark .feedback-close {
        color: #bdc3c7;
    }
    
    .feedback-panel.dark .feedback-close:hover {
        color: white;
    }
    
    .feedback-form {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .feedback-rating {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-bottom: 10px;
    }
    
    .rating-star {
        font-size: 24px;
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s ease;
    }
    
    .rating-star:hover,
    .rating-star.active {
        color: #ffc107;
    }
    
    .feedback-input,
    .feedback-textarea {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 10px;
        font-size: 14px;
        font-family: inherit;
        transition: border-color 0.2s ease;
    }
    
    .feedback-input:focus,
    .feedback-textarea:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .feedback-panel.dark .feedback-input,
    .feedback-panel.dark .feedback-textarea {
        background: #34495e;
        border-color: #4a5f7a;
        color: white;
    }
    
    .feedback-panel.dark .feedback-input::placeholder,
    .feedback-panel.dark .feedback-textarea::placeholder {
        color: #bdc3c7;
    }
    
    .feedback-textarea {
        resize: vertical;
        min-height: 80px;
        max-height: 120px;
    }
    
    .feedback-submit {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .feedback-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(67, 233, 123, 0.3);
    }
    
    .feedback-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .feedback-success {
        text-align: center;
        padding: 20px;
        color: #28a745;
    }
    
    .feedback-panel.dark .feedback-success {
        color: #2ecc71;
    }
    
    .feedback-error {
        color: #dc3545;
        font-size: 12px;
        margin-top: 5px;
    }
    
    .feedback-panel.dark .feedback-error {
        color: #e74c3c;
    }
    
    .feedback-type-selector {
        display: flex;
        gap: 5px;
        margin-bottom: 10px;
    }
    
    .type-btn {
        flex: 1;
        padding: 6px 8px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .type-btn:hover,
    .type-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .feedback-panel.dark .type-btn {
        background: #34495e;
        border-color: #4a5f7a;
        color: white;
    }
    
    .feedback-panel.dark .type-btn:hover,
    .feedback-panel.dark .type-btn.active {
        background: #667eea;
        border-color: #667eea;
    }
    
    @media (max-width: 480px) {
        .feedback-panel {
            width: 280px;
            padding: 15px;
        }
        
        .feedback-widget.bottom-right,
        .feedback-widget.bottom-left,
        .feedback-widget.top-right,
        .feedback-widget.top-left {
            bottom: 10px;
            right: 10px;
            left: auto;
            top: auto;
        }
        
        .feedback-widget .feedback-panel {
            right: 0;
            left: auto;
            bottom: 60px;
            top: auto;
        }
    }
</style>

<div id="<?= $widgetId ?>" class="feedback-widget <?= htmlspecialchars($position) ?>">
    <button class="feedback-trigger <?= htmlspecialchars($theme) ?>" onclick="toggleFeedbackPanel('<?= $widgetId ?>')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
        </svg>
        <?= htmlspecialchars($triggerText) ?>
    </button>
    
    <div class="feedback-panel <?= htmlspecialchars($theme) ?>" id="panel-<?= $widgetId ?>">
        <div class="feedback-header">
            <h4 class="feedback-title">Quick Feedback</h4>
            <button class="feedback-close" onclick="closeFeedbackPanel('<?= $widgetId ?>')">
                ×
            </button>
        </div>
        
        <div id="form-<?= $widgetId ?>">
            <form class="feedback-form" onsubmit="submitFeedback(event, '<?= $widgetId ?>')">
                <div class="feedback-type-selector">
                    <button type="button" class="type-btn active" data-type="general">General</button>
                    <button type="button" class="type-btn" data-type="bug">Bug</button>
                    <button type="button" class="type-btn" data-type="feature">Feature</button>
                    <button type="button" class="type-btn" data-type="improvement">Improve</button>
                </div>
                
                <?php if ($showRating): ?>
                    <div class="feedback-rating">
                        <span class="rating-star" data-rating="1">★</span>
                        <span class="rating-star" data-rating="2">★</span>
                        <span class="rating-star" data-rating="3">★</span>
                        <span class="rating-star" data-rating="4">★</span>
                        <span class="rating-star" data-rating="5">★</span>
                    </div>
                <?php endif; ?>
                
                <?php if ($showEmail): ?>
                    <input type="email" class="feedback-input" name="email" placeholder="Your email (optional)">
                <?php endif; ?>
                
                <textarea class="feedback-textarea" name="message" placeholder="<?= htmlspecialchars($placeholder) ?>" required></textarea>
                
                <button type="submit" class="feedback-submit">
                    Send Feedback
                </button>
                
                <div class="feedback-error" id="error-<?= $widgetId ?>" style="display: none;"></div>
            </form>
        </div>
        
        <div id="success-<?= $widgetId ?>" class="feedback-success" style="display: none;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor" style="margin-bottom: 10px;">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
            <h5>Thank you!</h5>
            <p>Your feedback has been submitted successfully.</p>
        </div>
    </div>
</div>

<script>
(function() {
    let currentRating = 0;
    let currentType = 'general';
    
    // Initialize widget
    function initFeedbackWidget(widgetId) {
        const widget = document.getElementById(widgetId);
        if (!widget) return;
        
        // Rating stars
        const stars = widget.querySelectorAll('.rating-star');
        stars.forEach(star => {
            star.addEventListener('click', function() {
                currentRating = parseInt(this.dataset.rating);
                updateStars(stars, currentRating);
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                updateStars(stars, rating);
            });
        });
        
        const ratingContainer = widget.querySelector('.feedback-rating');
        if (ratingContainer) {
            ratingContainer.addEventListener('mouseleave', function() {
                updateStars(stars, currentRating);
            });
        }
        
        // Type selector
        const typeButtons = widget.querySelectorAll('.type-btn');
        typeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                typeButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentType = this.dataset.type;
            });
        });
        
        // Close panel when clicking outside
        document.addEventListener('click', function(e) {
            const panel = document.getElementById(`panel-${widgetId}`);
            const trigger = widget.querySelector('.feedback-trigger');
            
            if (panel && panel.style.display === 'block' && 
                !panel.contains(e.target) && 
                !trigger.contains(e.target)) {
                closeFeedbackPanel(widgetId);
            }
        });
    }
    
    function updateStars(stars, rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }
    
    // Global functions
    window.toggleFeedbackPanel = function(widgetId) {
        const panel = document.getElementById(`panel-${widgetId}`);
        if (panel.style.display === 'block') {
            closeFeedbackPanel(widgetId);
        } else {
            openFeedbackPanel(widgetId);
        }
    };
    
    window.openFeedbackPanel = function(widgetId) {
        const panel = document.getElementById(`panel-${widgetId}`);
        panel.style.display = 'block';
        
        // Track widget open
        if (typeof trackEvent === 'function') {
            trackEvent('user_action', {
                action: 'feedback_widget_opened',
                widget_id: widgetId
            });
        }
    };
    
    window.closeFeedbackPanel = function(widgetId) {
        const panel = document.getElementById(`panel-${widgetId}`);
        panel.style.display = 'none';
        
        // Reset form
        setTimeout(() => {
            const form = document.getElementById(`form-${widgetId}`);
            const success = document.getElementById(`success-${widgetId}`);
            const error = document.getElementById(`error-${widgetId}`);
            
            form.style.display = 'block';
            success.style.display = 'none';
            error.style.display = 'none';
            
            // Reset form fields
            const formElement = form.querySelector('form');
            if (formElement) {
                formElement.reset();
            }
            
            // Reset rating
            currentRating = 0;
            const stars = panel.querySelectorAll('.rating-star');
            updateStars(stars, 0);
            
            // Reset type
            const typeButtons = panel.querySelectorAll('.type-btn');
            typeButtons.forEach(btn => btn.classList.remove('active'));
            typeButtons[0]?.classList.add('active');
            currentType = 'general';
        }, 300);
    };
    
    window.submitFeedback = function(event, widgetId) {
        event.preventDefault();
        
        const form = event.target;
        const submitBtn = form.querySelector('.feedback-submit');
        const errorDiv = document.getElementById(`error-${widgetId}`);
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
        errorDiv.style.display = 'none';
        
        // Collect form data
        const formData = new FormData(form);
        const data = {
            feedback_type: currentType,
            message: formData.get('message'),
            rating: currentRating || null,
            email: formData.get('email') || null,
            page_url: window.location.href,
            user_agent: navigator.userAgent,
            widget_id: widgetId
        };
        
        // Submit feedback
        fetch('/feedback/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Show success message
                document.getElementById(`form-${widgetId}`).style.display = 'none';
                document.getElementById(`success-${widgetId}`).style.display = 'block';
                
                // Auto-close after 3 seconds
                setTimeout(() => {
                    closeFeedbackPanel(widgetId);
                }, 3000);
                
                // Track successful submission
                if (typeof trackEvent === 'function') {
                    trackEvent('user_action', {
                        action: 'feedback_submitted',
                        feedback_type: currentType,
                        rating: currentRating,
                        widget_id: widgetId
                    });
                }
            } else {
                throw new Error(result.message || 'Failed to submit feedback');
            }
        })
        .catch(error => {
            console.error('Feedback submission error:', error);
            errorDiv.textContent = error.message || 'Failed to submit feedback. Please try again.';
            errorDiv.style.display = 'block';
            
            // Track error
            if (typeof trackEvent === 'function') {
                trackEvent('error', {
                    error_type: 'feedback_submission_failed',
                    error_message: error.message,
                    widget_id: widgetId
                });
            }
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Feedback';
        });
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initFeedbackWidget('<?= $widgetId ?>');
        });
    } else {
        initFeedbackWidget('<?= $widgetId ?>');
    }
})();
</script>