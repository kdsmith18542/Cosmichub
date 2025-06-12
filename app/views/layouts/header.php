<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title ?? 'CosmicHub.Online'); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-primary" href="/">CosmicHub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a href="/dashboard" class="nav-link">Dashboard</a></li>
                        <li class="nav-item"><a href="/reports" class="nav-link">My Reports</a></li>
                        <li class="nav-item"><a href="/celebrity-reports" class="nav-link">Celebrity Almanac</a></li>
                        <li class="nav-item"><a href="/archetypes" class="nav-link">Archetype Hubs</a></li>
                        <li class="nav-item"><a href="/credits" class="nav-link">Credits</a></li>
                        <li class="nav-item"><a href="/gift" class="nav-link text-success fw-semibold">
                            <i class="fas fa-gift me-1"></i> Gift Credits
                        </a></li>
                        <li class="nav-item"><a href="/daily-vibe" class="nav-link text-primary fw-semibold">
                            <i class="fas fa-moon-stars me-1"></i> Daily Vibe
                        </a></li>
                        <li class="nav-item"><a href="/compatibility" class="nav-link text-danger fw-semibold">
                            <i class="fas fa-heart me-1"></i> Compatibility
                        </a></li>
                        <li class="nav-item"><a href="/rarity-score" class="nav-link text-info fw-semibold">
                            <i class="fas fa-star me-1"></i> Rarity Score
                        </a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <span class="badge bg-primary rounded-circle">
                                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/profile">Your Profile</a></li>
                                <li><a class="dropdown-item" href="/settings">Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout">Sign out</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a href="/login" class="nav-link">Sign in</a></li>
                        <li class="nav-item"><a href="/register" class="btn btn-primary ms-2">Sign up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main>
        <!-- Page content will be inserted here -->
        <?php if (isset($flash)): ?>
            <div class="container mt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($flash); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
