<?php
/**
 * Feedback Form View
 * 
 * User feedback collection interface for Phase 3 beta testing
 */

$title = $title ?? 'Feedback - CosmicHub Beta';
$feedbackTypes = $feedbackTypes ?? [];
$user = $user ?? null;

// Get any stored form data or errors from session
$errors = $_SESSION['feedback_errors'] ?? [];
$formData = $_SESSION['feedback_data'] ?? [];
$success = $_SESSION['feedback_success'] ?? null;

// Clear session data
unset($_SESSION['feedback_errors'], $_SESSION['feedback_data'], $_SESSION['feedback_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .feedback-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .feedback-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .feedback-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .feedback-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .feedback-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .feedback-body {
            padding: 2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
        }
        
        .rating-container {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .rating-star {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .rating-star:hover,
        .rating-star.active {
            color: #ffc107;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 172, 254, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .beta-badge {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .feedback-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .feedback-type-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .feedback-type-card:hover {
            border-color: #4facfe;
            background: rgba(79, 172, 254, 0.05);
        }
        
        .feedback-type-card.selected {
            border-color: #4facfe;
            background: rgba(79, 172, 254, 0.1);
        }
        
        .feedback-type-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #4facfe;
        }
    </style>
</head>
<body>
    <div class="feedback-container">
        <div class="feedback-card">
            <div class="feedback-header">
                <h1><i class="fas fa-comments"></i> Feedback</h1>
                <span class="beta-badge">BETA</span>
                <p>Help us improve CosmicHub with your valuable feedback</p>
            </div>
            
            <div class="feedback-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="/feedback/submit" id="feedbackForm">
                    <!-- Feedback Type Selection -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-tag"></i> What type of feedback do you have?
                        </label>
                        <div class="feedback-types">
                            <?php foreach ($feedbackTypes as $key => $label): ?>
                                <div class="feedback-type-card" data-type="<?= $key ?>">
                                    <div class="feedback-type-icon">
                                        <?php
                                        $icons = [
                                            'bug_report' => 'fas fa-bug',
                                            'feature_request' => 'fas fa-lightbulb',
                                            'general_feedback' => 'fas fa-comment',
                                            'ui_ux_feedback' => 'fas fa-paint-brush',
                                            'performance_issue' => 'fas fa-tachometer-alt',
                                            'suggestion' => 'fas fa-star'
                                        ];
                                        echo '<i class="' . ($icons[$key] ?? 'fas fa-comment') . '"></i>';
                                        ?>
                                    </div>
                                    <div><?= htmlspecialchars($label) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="feedback_type" id="feedbackType" value="<?= htmlspecialchars($formData['feedback_type'] ?? '') ?>">
                        <?php if (isset($errors['feedback_type'])): ?>
                            <div class="text-danger mt-2">
                                <small><?= htmlspecialchars($errors['feedback_type']) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Subject -->
                    <div class="mb-3">
                        <label for="subject" class="form-label">
                            <i class="fas fa-heading"></i> Subject (Optional)
                        </label>
                        <input type="text" 
                               class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>" 
                               id="subject" 
                               name="subject" 
                               value="<?= htmlspecialchars($formData['subject'] ?? '') ?>"
                               placeholder="Brief summary of your feedback">
                        <?php if (isset($errors['subject'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['subject']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Rating -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-star"></i> Overall Rating (Optional)
                        </label>
                        <div class="rating-container">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="rating-star" data-rating="<?= $i ?>">
                                    <i class="fas fa-star"></i>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="rating" value="<?= htmlspecialchars($formData['rating'] ?? '') ?>">
                        <?php if (isset($errors['rating'])): ?>
                            <div class="text-danger mt-2">
                                <small><?= htmlspecialchars($errors['rating']) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Message -->
                    <div class="mb-3">
                        <label for="message" class="form-label">
                            <i class="fas fa-comment-alt"></i> Your Feedback *
                        </label>
                        <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" 
                                  id="message" 
                                  name="message" 
                                  rows="6" 
                                  placeholder="Please share your detailed feedback, suggestions, or report any issues you've encountered..."
                                  required><?= htmlspecialchars($formData['message'] ?? '') ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['message']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Hidden fields -->
                    <input type="hidden" name="page_url" value="<?= htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '') ?>">
                    
                    <!-- Submit Button -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-submit">
                            <i class="fas fa-paper-plane"></i> Submit Feedback
                        </button>
                    </div>
                </form>
                
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt"></i> 
                        Your feedback helps us improve CosmicHub. Thank you for being part of our beta testing community!
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Feedback type selection
        document.querySelectorAll('.feedback-type-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.feedback-type-card').forEach(c => c.classList.remove('selected'));
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Set hidden input value
                document.getElementById('feedbackType').value = this.dataset.type;
            });
        });
        
        // Rating system
        const ratingStars = document.querySelectorAll('.rating-star');
        const ratingInput = document.getElementById('rating');
        
        ratingStars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                
                // Update star display
                ratingStars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                const rating = parseInt(this.dataset.rating);
                
                ratingStars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
        
        document.querySelector('.rating-container').addEventListener('mouseleave', function() {
            const currentRating = parseInt(ratingInput.value) || 0;
            
            ratingStars.forEach((s, index) => {
                if (index < currentRating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
        
        // Set initial selected feedback type if exists
        const initialType = document.getElementById('feedbackType').value;
        if (initialType) {
            document.querySelector(`[data-type="${initialType}"]`)?.classList.add('selected');
        }
        
        // Set initial rating if exists
        const initialRating = parseInt(ratingInput.value) || 0;
        if (initialRating > 0) {
            ratingStars.forEach((s, index) => {
                if (index < initialRating) {
                    s.classList.add('active');
                }
            });
        }
        
        // Form validation
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const feedbackType = document.getElementById('feedbackType').value;
            const message = document.getElementById('message').value.trim();
            
            if (!feedbackType) {
                e.preventDefault();
                alert('Please select a feedback type.');
                return;
            }
            
            if (!message || message.length < 10) {
                e.preventDefault();
                alert('Please provide a detailed message (at least 10 characters).');
                return;
            }
        });
        
        // Track analytics
        if (typeof trackEvent === 'function') {
            trackEvent('page_view', {
                page: 'feedback',
                user_authenticated: <?= $user ? 'true' : 'false' ?>
            });
        }
    </script>
</body>
</html>