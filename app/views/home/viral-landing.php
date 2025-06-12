<?php
// Set the title if not already set
if (!isset($title)) {
    $title = 'Cosmic Hub - Reveal Your Blueprint';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet">

    <style>
        /* --- Core Styles & Background Animation --- */
        body {
            font-family: 'Inter', sans-serif;
            overflow: hidden; /* Prevents scrollbars from the canvas */
            background-color: #0c0a1a; /* Fallback background */
        }

        .hero-section {
            position: relative;
            height: 100vh;
            width: 100vw;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #ffffff; /* Light text for high contrast on dark background */
            /* Deep space gradient */
            background: linear-gradient(245deg, #0c0a1a, #1e1a3e, #2a225a, #4a3a9a);
            background-size: 400% 400%;
            animation: cosmicGradient 20s ease infinite;
        }

        /* The subtle, slow-moving cosmic gradient animation */
        @keyframes cosmicGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* --- Particle Canvas --- */
        #particles-js {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1; 
        }

        /* --- ✨ Zodiac Glyphs Layer --- */
        .zodiac-glyphs {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0; /* Sits behind the particles */
            overflow: hidden;
        }

        .glyph {
            position: absolute;
            width: 70px;
            height: 70px;
            color: rgba(255, 255, 255, 0.15);
            font-size: 55px;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: floatFade 20s infinite alternate;
            /* ✨ NEW: Added transition for smooth hover effect */
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        
        /* ✨ NEW: Hover effect for the glyphs */
        .glyph:hover {
            opacity: 1 !important; /* Forces full visibility on hover */
            transform: scale(1.1) !important; /* Makes it slightly larger */
            animation-play-state: paused; /* Pauses the float animation */
            cursor: pointer;
        }


        @keyframes floatFade {
            from {
                opacity: 0.1; /* Never fully disappears */
                transform: translateY(20px) scale(0.95);
            }
            50% {
                opacity: 0.4; /* Increased peak opacity */
            }
            to {
                opacity: 0.1; /* Never fully disappears */
                transform: translateY(-20px) scale(1.05);
            }
        }

        /* Positioning and animation delays for each glyph */
        .glyph:nth-child(1) { top: 15%; left: 10%; animation-duration: 25s; }
        .glyph:nth-child(2) { top: 20%; left: 85%; animation-duration: 30s; animation-delay: 5s; }
        .glyph:nth-child(3) { top: 70%; left: 5%; animation-duration: 22s; animation-delay: 2s; }
        .glyph:nth-child(4) { top: 80%; left: 90%; animation-duration: 28s; animation-delay: 7s; }
        .glyph:nth-child(5) { top: 50%; left: 50%; animation-duration: 35s; animation-delay: 3s; }
        .glyph:nth-child(6) { top: 5%; left: 40%; animation-duration: 26s; animation-delay: 1s; }
        .glyph:nth-child(7) { top: 90%; left: 25%; animation-duration: 29s; animation-delay: 4s; }
        .glyph:nth-child(8) { top: 40%; left: 75%; animation-duration: 32s; animation-delay: 6s; }
        .glyph:nth-child(9) { top: 60%; left: 30%; animation-duration: 24s; animation-delay: 8s; }
        .glyph:nth-child(10) { top: 10%; left: 65%; animation-duration: 31s; animation-delay: 9s; }
        .glyph:nth-child(11) { top: 75%; left: 60%; animation-duration: 27s; animation-delay: 10s; }
        .glyph:nth-child(12) { top: 25%; left: 20%; animation-duration: 33s; animation-delay: 11s; }


        /* --- Hero Content --- */
        .hero-content {
            position: relative;
            z-index: 2; /* Sits on top of the particle canvas */
            max-width: 700px; 
            padding: 4rem;
            background: rgba(12, 10, 26, 0.2);
            backdrop-filter: blur(5px);
            border-radius: 1.5rem;
        }

        .hero-title {
            font-weight: 900;
            font-size: 3.8rem;
            letter-spacing: -2.5px;
            color: #ffffff;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
        }

        .date-input-form {
            margin-top: 2.5rem;
        }
        
        #feedback-message {
            display: none;
            font-weight: 500;
        }

        .form-control, .form-select {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
            opacity: 1;
        }

        .form-select {
             background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        }
        
        .btn-primary {
            background-color: #5865f2;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            transition: background-color 0.2s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #4752c4;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, rgba(12, 10, 26, 0.95) 0%, rgba(30, 26, 62, 0.9) 50%, rgba(42, 34, 90, 0.85) 100%); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(138, 92, 246, 0.3); box-shadow: 0 4px 20px rgba(138, 92, 246, 0.1);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/" style="background: linear-gradient(135deg, #8b5cf6, #6366f1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 1.5rem;">✨ CosmicHub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                     <?php if (isset($_SESSION['user_id'])): ?>
                         <li class="nav-item"><a href="/dashboard" class="nav-link" style="color: #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.color='#8b5cf6'; this.style.textShadow='0 0 10px rgba(138, 92, 246, 0.6)';" onmouseout="this.style.color='#e2e8f0'; this.style.textShadow='none';">Dashboard</a></li>
                         <li class="nav-item"><a href="/reports" class="nav-link" style="color: #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.color='#8b5cf6'; this.style.textShadow='0 0 10px rgba(138, 92, 246, 0.6)';" onmouseout="this.style.color='#e2e8f0'; this.style.textShadow='none';">My Reports</a></li>
                         <li class="nav-item"><a href="/celebrity-reports" class="nav-link" style="color: #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.color='#8b5cf6'; this.style.textShadow='0 0 10px rgba(138, 92, 246, 0.6)';" onmouseout="this.style.color='#e2e8f0'; this.style.textShadow='none';">Celebrity Almanac</a></li>
                         <li class="nav-item"><a href="/archetypes" class="nav-link" style="color: #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.color='#8b5cf6'; this.style.textShadow='0 0 10px rgba(138, 92, 246, 0.6)';" onmouseout="this.style.color='#e2e8f0'; this.style.textShadow='none';">Archetype Hubs</a></li>
                         <li class="nav-item"><a href="/credits" class="nav-link" style="color: #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.color='#8b5cf6'; this.style.textShadow='0 0 10px rgba(138, 92, 246, 0.6)';" onmouseout="this.style.color='#e2e8f0'; this.style.textShadow='none';">Credits</a></li>
                         <li class="nav-item"><a href="/gift" class="nav-link fw-semibold" style="color: #10b981; transition: all 0.3s ease; text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);" onmouseover="this.style.color='#34d399'; this.style.textShadow='0 0 15px rgba(52, 211, 153, 0.8)';" onmouseout="this.style.color='#10b981'; this.style.textShadow='0 0 10px rgba(16, 185, 129, 0.3)';">
                             <i class="fas fa-gift me-1"></i> Gift Credits
                         </a></li>
                         <li class="nav-item"><a href="/daily-vibe" class="nav-link" style="color: #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.color='#8b5cf6'; this.style.textShadow='0 0 10px rgba(138, 92, 246, 0.6)';" onmouseout="this.style.color='#e2e8f0'; this.style.textShadow='none';">Daily Vibe</a></li>
                         <li class="nav-item"><a href="/compatibility" class="nav-link" style="color: #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.color='#8b5cf6'; this.style.textShadow='0 0 10px rgba(138, 92, 246, 0.6)';" onmouseout="this.style.color='#e2e8f0'; this.style.textShadow='none';">Compatibility</a></li>
                         <li class="nav-item"><a href="/rarity-score" class="nav-link" style="color: #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.color='#8b5cf6'; this.style.textShadow='0 0 10px rgba(138, 92, 246, 0.6)';" onmouseout="this.style.color='#e2e8f0'; this.style.textShadow='none';">Rarity Score</a></li>
                         <li class="nav-item dropdown">
                             <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: #e2e8f0;">
                                 <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; font-weight: bold; font-size: 14px; box-shadow: 0 0 15px rgba(138, 92, 246, 0.5); border: 2px solid rgba(138, 92, 246, 0.3);">
                                     <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                                 </div>
                             </a>
                             <ul class="dropdown-menu dropdown-menu-end" style="background: rgba(12, 10, 26, 0.95); border: 1px solid rgba(138, 92, 246, 0.3); backdrop-filter: blur(10px);">
                                 <li><a class="dropdown-item" href="/profile" style="color: #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(138, 92, 246, 0.2)'; this.style.color='#8b5cf6';" onmouseout="this.style.background='transparent'; this.style.color='#e2e8f0';">Profile</a></li>
                                 <li><a class="dropdown-item" href="/settings" style="color: #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(138, 92, 246, 0.2)'; this.style.color='#8b5cf6';" onmouseout="this.style.background='transparent'; this.style.color='#e2e8f0';">Settings</a></li>
                                 <li><hr class="dropdown-divider" style="border-color: rgba(138, 92, 246, 0.3);"></li>
                                 <li><a class="dropdown-item" href="/logout" style="color: #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(138, 92, 246, 0.2)'; this.style.color='#8b5cf6';" onmouseout="this.style.background='transparent'; this.style.color='#e2e8f0';">Sign out</a></li>
                             </ul>
                         </li>
                    <?php else: ?>
                         <li class="nav-item">
                             <a class="nav-link" href="/login" style="color: #e2e8f0; transition: all 0.3s ease; text-shadow: 0 0 10px rgba(138, 92, 246, 0.3);" onmouseover="this.style.color='#8b5cf6'; this.style.textShadow='0 0 15px rgba(138, 92, 246, 0.8)';" onmouseout="this.style.color='#e2e8f0'; this.style.textShadow='0 0 10px rgba(138, 92, 246, 0.3)';">Sign in</a>
                         </li>
                         <li class="nav-item">
                             <a class="btn ms-2" href="/register" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border: none; color: white; padding: 8px 20px; border-radius: 25px; box-shadow: 0 4px 15px rgba(138, 92, 246, 0.4); transition: all 0.3s ease; text-shadow: 0 1px 2px rgba(0,0,0,0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 25px rgba(138, 92, 246, 0.6)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(138, 92, 246, 0.4)';">✨ Sign up</a>
                         </li>
                     <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <!-- The particle effect canvas will be placed here by JavaScript -->
        <div id="particles-js"></div>
        
        <!-- ✨ Animated Zodiac Glyphs -->
        <div class="zodiac-glyphs">
            <div class="glyph" title="Aries">♈</div>
            <div class="glyph" title="Taurus">♉</div>
            <div class="glyph" title="Gemini">♊</div>
            <div class="glyph" title="Cancer">♋</div>
            <div class="glyph" title="Leo">♌</div>
            <div class="glyph" title="Virgo">♍</div>
            <div class="glyph" title="Libra">♎</div>
            <div class="glyph" title="Scorpio">♏</div>
            <div class="glyph" title="Sagittarius">♐</div>
            <div class="glyph" title="Capricorn">♑</div>
            <div class="glyph" title="Aquarius">♒</div>
            <div class="glyph" title="Pisces">♓</div>
        </div>

        <div class="hero-content">
            <h1 class="hero-title">Reveal Your Cosmic Blueprint</h1>
            <p class="hero-subtitle my-3">Enter your birthday to generate a personalized almanac of your life's journey, powered by AI.</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div id="feedback-message" class="mt-3 alert alert-danger" style="display: block;">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form id="date-form" action="/generate-snapshot" method="POST" class="date-input-form row g-2 justify-content-center align-items-center">
                <div class="col-auto">
                    <label for="month" class="visually-hidden">Month</label>
                    <select class="form-select" id="month" name="month" required>
                        <option value="" selected disabled>Month...</option>
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5">May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label for="day" class="visually-hidden">Day</label>
                    <input type="number" class="form-control" id="day" name="day" placeholder="Day" min="1" max="31" required>
                </div>
                <div class="col-auto">
                    <label for="year" class="visually-hidden">Year</label>
                    <input type="number" class="form-control" id="year" name="year" placeholder="Year" min="1900" max="<?php echo date('Y'); ?>" required>
                </div>
                <div class="col-12">
                     <button type="submit" class="btn btn-primary mt-3 w-100">Generate My Blueprint</button>
                </div>
            </form>
            <div id="feedback-message" class="mt-3 alert alert-info"></div>
        </div>
    </div>

    <!-- particles.js library -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

    <script>
        // --- Initialize particles.js ---
        particlesJS('particles-js', {
            "particles": {
                "number": { "value": 160, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#ffffff" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.8, "random": true, "anim": { "enable": true, "speed": 1, "opacity_min": 0.1, "sync": false } },
                "size": { "value": 1.5, "random": true },
                "line_linked": { "enable": true, "distance": 100, "color": "#ffffff", "opacity": 0.1, "width": 1 },
                "move": { "enable": true, "speed": 0.5, "direction": "none", "random": true, "straight": false, "out_mode": "out", "bounce": false }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": { "enable": true, "mode": "bubble" },
                    "onclick": { "enable": true, "mode": "push" }
                },
                "modes": {
                    "bubble": { "distance": 200, "size": 2, "duration": 2, "opacity": 1 },
                    "push": { "particles_nb": 4 }
                }
            },
            "retina_detect": true
        });

        // --- Handle Form Submission ---
        const dateForm = document.getElementById('date-form');
        const feedbackMessage = document.getElementById('feedback-message');

        dateForm.addEventListener('submit', function(event) {
            // Get the values from the input fields
            const month = document.getElementById('month').value;
            const day = document.getElementById('day').value;
            const year = document.getElementById('year').value;
            
            // Basic validation
            if (!month || !day || !year) {
                event.preventDefault();
                feedbackMessage.textContent = 'Please fill out all fields.';
                feedbackMessage.style.display = 'block';
                feedbackMessage.className = 'mt-3 alert alert-danger';
                return;
            }

            const birthDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
            
            // Display feedback
            feedbackMessage.textContent = `Generating report for ${birthDate}...`;
            feedbackMessage.style.display = 'block';
            feedbackMessage.className = 'mt-3 alert alert-info';

            console.log('Form Submitted!');
            console.log('Birthday:', birthDate);

            // Update button state
            const submitButton = dateForm.querySelector('button[type="submit"]');
            submitButton.innerHTML = 'Generating Your Cosmic Blueprint...';
            submitButton.disabled = true;
        });
    </script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>